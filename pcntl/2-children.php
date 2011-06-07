<?php

for ($i = 0, $imax = 5; $i < $imax; $i++) {
	$pid = pcntl_fork();
	if ($pid == 0) {
		echo "Child {$i}\n";
		exit(mt_rand(1,100));
	} elseif ($pid > 0) {
		echo "Parent\n";
		pcntl_wait($status);
		echo "Child {$i} exit status was {$status}\n";
	}	
}

?>