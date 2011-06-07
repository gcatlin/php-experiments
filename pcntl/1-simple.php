<?php

$pid = pcntl_fork();
if ($pid == 0) {
	echo "Child\n";
} elseif ($pid > 0) {
	echo "Parent\n";
	pcntl_wait($status);
}

?>