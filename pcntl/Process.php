<?php

/*
~/icontact/publisher/trunk/lib/classes/ICPBillingReconciler.php 
~/icontact/community/trunk/batch/eventhandler.php

Beej's Guide to Unix IPC
http://beej.us/guide/bgipc/output/html/multipage/index.html

Network Programming Unix Pipes
http://www.cs.sunysb.edu/~cse533/asgn1/pipes.html

Python: How to fork and return text from the child process
http://www.myelin.co.nz/post/2003/3/13/#200303135

Something Like Threading - PHP Process Forking and Interprocess Communication
http://www.workingsoftware.com.au/index.php?h=WebLog&author_id=1&entry_id=61

Socket Programming HOWTO
http://www.amk.ca/python/howto/sockets/

A Socket-based IPC Tutorial
http://people.cis.ksu.edu/~singh/CIS725/Fall99/programs/sock_ipc_tut.html
*/

abstract class Process {
	protected $alarmCallback = null;
	protected $children = array();
	protected $isParent = true;
	protected $maxChildren = 64;
	protected $numForked = 0;
	protected $numReaped = 0;
	protected $pid = null;
	protected $readLength = 4096;
	protected $results = array();
	protected $usleep = 1000;

	abstract public function onComplete();
	abstract public function onForkChild($batch);
	abstract public function onReapChild($result);
	
	public function __construct() {
		$this->registerSignalHandler(array($this, "handleSignals"), array(SIGINT, SIGTERM));
		$this->pid = getmypid();
	}
	
	public function alarm($seconds, $callback) {
		if ($callback != $this->alarmCallback) {
			pcntl_signal(SIGALRM, $callback);
			$this->alarmCallback = $callback;
		}
		return pcntl_alarm($seconds);
	}

	public function daemonize() {
		$lockfile = '/var/run/'.reset(get_included_files()).'.pid';
		$running = file_exists($lockfile);
		if ($running || posix_getppid() == 1) return; // already a daemon
		
		$pid = pcntl_fork(); // fork
		if ($pid < 0) exit(1); // fork error
		if ($pid > 0) exit(0); // parent exits

		$sid = posix_setsid(); // set as session leader
		if ($sid == -1) exit(1); // setsid error

		umask(0);
		chdir('/');
		
		$this->pid = posix_getpid();
		file_put_contents($lockfile, $this->pid, LOCK_EX);
	}
	
	public function handleSignals($signal) {
		switch ($signal) {
			case SIGINT:
			case SIGTERM:
				if ($this->isParent) {
					if ($this->isStopping) {
						$this->signalChildren(SIGKILL);
					}
					$this->isStopping = true;
					$this->signalChildren($signal);
					$this->reapChildren($wait=true);
					$this->onComplete();
				}
				exit(0);
		}
	}

	public function reapChildren($block=false) {
		$options = ($block ? WUNTRACED : WUNTRACE|WNOHANG);
		foreach ($this->children as $pid => $socket) {
			if ((pcntl_waitpid($pid, $status, $options)) > 0) {
				$this->numReaped++;
				while ($result = socket_read($socket, $this->readLength)) {
					$this->results[$pid] .= $result;
				}
				socket_close($socket);
				unset($this->children[$pid]);
				$this->onReapChild($this->results[$pid]);
			}
		}
	}

	public function registerSignalHandler($callback, $signals) {
		$signals = (array) $signals;
		foreach ($signals as $signal) {
			pcntl_signal($signal, $callback);
		}
	}
	
	public function run($batches) {
		$numBatches = count($batches);
		$sockets = array();
		while ($this->numForked < $numBatches) {
			$max = min($this->maxChildren, $numBatches - $this->numForked);
			for ($i = 0; $i < $max; $i++) {
				socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);
				list($reader, $writer) = $sockets;

				$pid = @pcntl_fork();
				if ($pid > 0) { // parent
					socket_close($writer);
					socket_set_nonblock($reader);
					$this->children[$pid] = $reader;
					$this->numForked++;
				} elseif ($pid == 0) { // child
					socket_close($reader);
					$this->pid = getmypid();
					$this->isParent = false;
					$result = $this->onForkChild($batches[$this->numForked]);
					socket_write($writer, $result, strlen($result));
					socket_close($writer);
					exit(0);
				}
			}

			if (socket_select($r=$this->children, $w=null, $e=null, 0)) {
				foreach ($this->children as $pid => $socket) {
					while ($result = socket_read($socket, $this->readLength)) {
						$this->results[$pid] .= $result;
					}
				}
			}

			$this->reapChildren();
			usleep($this->usleep);
		}

		$this->reapChildren($block=true);
		$this->onComplete();
	}
	
	public function signalChildren($signal=SIGTERM) {
		foreach ($this->children as $pid => $socket) {
			posix_kill($pid, $signal);
		}
	}
}

require_once 'Console/ProgressBar.php';
class TestRunner extends Process {
	protected $bar = null;
	
	public function run($batches) {
		$this->bar = new Console_ProgressBar(
			'%fraction% [%bar%] %percent%   Time: %elapsed%   Remaining: %estimate%',
			'#',
			' ',
			76,
			count($batches),
			array('percent_precision'=>0, 'num_datapoints'=>(int) count($batches)*.2));
		parent::run($batches);
	}
	
	public function onComplete() {
		echo "\n{$this->numForked} forked, {$this->numReaped} reaped\n";
	}
	
	public function onForkChild($batch) {
		return str_repeat(md5($batch), 1);
	}
	
	public function onReapChild($result) {
		$this->bar->update($this->numReaped);
//		echo "{$result}\n";
	}
}

declare(ticks=1);
$p = new TestRunner();
$p->run(range(1, 1024));
