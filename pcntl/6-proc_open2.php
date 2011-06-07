<?php

$stdin = file_get_contents('php://stdin');
echo str_repeat(md5($stdin), 512); // 16K

?>