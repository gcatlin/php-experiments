<?php


$children = array();
$maxChildren = 10;
$numForked = 0;
$numReaped = 0;
$numBatches = 500;

require_once 'Console/ProgressBar.php';
$bar = new Console_ProgressBar('%fraction% [%bar%] %percent%   Time: %elapsed%', '=', ' ', 60, $numBatches);
$results = array();

while ($numReaped < $numBatches) {
	if ($numForked < $numBatches) {
		$imax = min($maxChildren - count($children), $numBatches - $numForked);
		for ($i = 0; $i < $imax; $i++) {
			$sockets = array();
			socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);
			list($reader, $writer) = $sockets;
	
			$pid = pcntl_fork();
			if ($pid == 0) { // child
				socket_close($reader);
				$result = str_repeat(md5($numForked), 512); // 16K
				socket_write($writer, $result, strlen($result));
				socket_close($writer);
				exit(0);
			} elseif ($pid > 0) { // parent
				socket_close($writer);
				socket_set_nonblock($reader);
				$children[$pid] = $reader;
				$numForked++;
			}
		}
	}

	if (socket_select($readers=$children, $w=null, $e=null, 0)) {
		foreach ($readers as $pid => $reader) {
			while ($result = socket_read($reader, 8192)) {
				$results[$pid] .= $result;
			}
		}
	}
	
	foreach ($children as $pid => $reader) {
		if ((pcntl_waitpid($pid, $status, WUNTRACE|WNOHANG)) > 0) {
			unset($children[$pid]);
			$numReaped++;
			$bar->update($numReaped);
		}
	}
}

echo "\n";
// echo number_format(memory_get_usage())."\n";
// unset($results);
// echo number_format(memory_get_usage())."\n";

?>