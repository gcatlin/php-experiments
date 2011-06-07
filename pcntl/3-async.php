<?php

$children = array();

for ($i = 0, $imax = 10; $i < $imax; $i++) {
	$pid = pcntl_fork();
	if ($pid == 0) {
		$pid = getmypid();
		echo "Child ({$pid}) forked\n";
		usleep(rand(500000, 1000000));
		exit;
	} elseif ($pid > 0) {
		$children[] = $pid;
	}
}

foreach ($children as $pid) {
	if ((pcntl_waitpid($pid, $status)) > 0) {
		echo "Child ({$pid}) reaped\n";
	}
}

?>