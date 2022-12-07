<?php
//$data = explode("\n", file_get_contents('test.txt'));
$data = explode("\n", file_get_contents('data.txt'));

$code_length = array_sum(array_map(fn($line) => strlen($line), $data));
$escaped_length = array_sum(array_map(fn($line) => strlen('"' . addslashes($line) . '"'), $data));

echo $escaped_length - $code_length, PHP_EOL;