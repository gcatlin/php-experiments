<?php

require_once 'Console/ProgressBar.php';

declare(ticks=1);

pcntl_signal(SIGINT, 'handleSignals');
$parentPid = getmypid();
$children = array();
$results = array();
$pipes = array();
$maxChildren = 10;
$numForked = 0;
$numReaped = 0;

$numTests = 1000;
$bar = new Console_ProgressBar('%fraction% [%bar%] %percent%   Time: %elapsed%', '=', ' ', 60, $numTests);

// Setup tests
$tests = array();
for ($i = 0; $i < $numTests; $i++) {
	$tests[$i] = mt_rand(500000, 1000000);
}

while ($numForked < count($tests)) {
	for ($i = 0, $imax = min($maxChildren, count($tests) - $numForked); $i < $imax; $i++) {
		$pipe = rtrim(sys_get_temp_dir(),'/').'/'.uniqid("{$parentPid}-", true);
		posix_mkfifo($pipe, 0644);
		
		
		$pid = pcntl_fork();
		if ($pid == 0) { // child
			
			//usleep($tests[$numForked]);
			$result = str_repeat(md5($numForked), 512);
			$writer = fopen($pipe, 'w');
			fwrite($writer, $result, strlen($result));
			fclose($writer);
			exit(0);
		} elseif ($pid > 0) { // parent
			$numForked++;
			$reader = fopen($pipe, 'r');
			stream_set_blocking($reader, 0);
			$children[$pid] = $reader;
			$pipes[$pid] = $pipe;
		}
	}

	if (stream_select($r=$children, $w=null, $e=null, 0)) {
		foreach ($children as $pid => $reader) {
			while ($result = fread($reader, 4096)) {
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
	global $bar, $children, $pipes, $results, $numReaped;	
	$options = ($block ? WUNTRACED : WUNTRACE|WNOHANG);
	foreach ($children as $pid => $reader) {
		if ((pcntl_waitpid($pid, $status, $options)) > 0) {
			$numReaped++;
			while ($result = fread($reader, 4096)) {
				$results[$pid] .= $result;
			}
			fclose($reader);
			unlink($pipes[$pid]);
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
