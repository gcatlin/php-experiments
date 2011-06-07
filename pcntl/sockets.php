<?php

require_once 'Console/ProgressBar.php';

declare(ticks=1);

pcntl_signal(SIGINT, 'handleSignals');
$parentPid = getmypid();
$children = array();
$results = array();

$maxChildren = 32;
$numForked = 0;
$numReaped = 0;

$numTests = 10000;
$bar = new Console_ProgressBar('%fraction% [%bar%] %percent%   Time: %elapsed%', '=', ' ', 60, $numTests);

// Setup tests
$tests = array();
for ($i = 0; $i < $numTests; $i++) {
	$tests[$i] = mt_rand(500000, 1000000);
}

while ($numForked < count($tests)) {
	for ($i = 0, $imax = min($maxChildren, count($tests) - $numForked); $i < $imax; $i++) {
		$sockets = array();
		socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);
		list($reader, $writer) = $sockets;
		
		$pid = pcntl_fork();
		if ($pid == 0) { // child
			socket_close($reader);
			//usleep($tests[$numForked]);
			$result = str_repeat(md5($numForked), 1);
			socket_write($writer, $result, strlen($result));
			socket_close($writer);
			exit(0);
		} elseif ($pid > 0) { // parent
			socket_close($writer);
			socket_set_nonblock($reader);
			$numForked++;
			$children[$pid] = $reader;
		}
	}

	if (socket_select($r=$children, $w=null, $e=null, 0)) {
		foreach ($children as $pid => $reader) {
			while ($result = socket_read($reader, 4096)) {
				$results[$pid] .= $result;
			}
		}
	}

	reap();
	
	//usleep(10000);
}

reap($block=true);
echo "\n{$numForked} forked, {$numReaped} reaped\n";
exit;

function reap($block=false) {
	global $bar, $children, $results, $numReaped;	
	$options = ($block ? WUNTRACED : WUNTRACE|WNOHANG);
	foreach ($children as $pid => $reader) {
		if ((pcntl_waitpid($pid, $status, $options)) > 0) {
			$numReaped++;
			//socket_set_block($reader);
			while ($result = socket_read($reader, 4096)) {
				$results[$pid] .= $result;
			}
			socket_close($children[$pid]);
			unset($children[$pid]);
			//echo "{$results[$pid]}\n";
			$bar->update($numReaped);
		}
	}
} 

function handleSignals($signal) {
 	global $parentPid, $children, $numForked, $numReaped;
	switch ($signal) {
		case SIGINT:
			if ($parentPid == getmypid()) {
				foreach ($children as $pid => $reader) {
					posix_kill($pid, $signal);
				}
				reap($block=true);
				echo "{$numForked} forked, {$numReaped} reaped\n";
			}
			exit;
	}
}

?>
