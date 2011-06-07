<?php

$children = array();
$maxChildren = 10;
$numForked = 0;
$numReaped = 0;
$numBatches = 50;

while ($numReaped < $numBatches) {
	if ($numForked < $numBatches) {
		$imax = min($maxChildren - count($children), $numBatches - $numForked);
		for ($i = 0; $i < $imax; $i++) {
			$pid = pcntl_fork();
			if ($pid == 0) { // child
				usleep(rand(500000, 1000000));
				exit(0);
			} elseif ($pid > 0) { // parent
				$numForked++;
				$children[$pid] = $pid;
			}
		}
	}

	echo "{$numForked} forked, {$numReaped} reaped, ".count($children)." working\n";

	foreach ($children as $pid) {
		if ((pcntl_waitpid($pid, $status, WUNTRACE|WNOHANG)) > 0) {
			$numReaped++;
			unset($children[$pid]);
		}
	}
	
	usleep(100000);
}

echo "{$numForked} forked, {$numReaped} reaped, ".count($children)." working\n";

?>