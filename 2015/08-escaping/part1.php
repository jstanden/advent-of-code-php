<?php
//$data = explode("\n", file_get_contents('test.txt'));
$data = explode("\n", file_get_contents('data.txt'));

$code_length = array_sum(array_map(fn($line) => strlen($line), $data));
$memory_length = array_sum(array_map(fn($line) => strlen(stripcslashes(substr($line,1,-1))), $data));

echo $code_length - $memory_length, PHP_EOL;