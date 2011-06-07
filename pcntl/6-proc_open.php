<?php

$processes = array();
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
			$descriptors = array(
			   0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
			   1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
			);
			
			$process = proc_open('php '.dirname(__FILE__).'/6-proc_open2.php', $descriptors, $pipes);
			if ($process) { 
				list($writer, $reader) = $pipes;
				stream_set_blocking($reader, 0);
				stream_set_blocking($writer, 0);
				fwrite($writer, $numForked);
				fclose($writer);
				$status = proc_get_status($process);
				$pid = $status['pid'];
				$children[$pid] = $reader;
				$processes[$pid] = $process;
				$numForked++;
			}
		}
	}
	
	$readers = $children;
	$w = $e = null;
	if (stream_select($readers, $w, $e, 0)) {
		foreach ($readers as $pid => $reader) {
			if (!isset($results[$pid])) {
				$results[$pid] = '';
				
			}
			$results[$pid] .= stream_get_contents($reader);
		}
	}
	
	foreach ($children as $pid => $reader) {
		if ((pcntl_waitpid($pid, $status, WUNTRACED|WNOHANG)) > 0) {
			fclose($reader);
			proc_close($processes[$pid]);
			unset($processes[$pid]);
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