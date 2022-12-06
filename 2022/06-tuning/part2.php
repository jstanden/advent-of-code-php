<?php
$data = file_get_contents("data.txt");
$length = 14;

for($x=$length-1;$x<strlen($data);$x++) {
    $buffer = substr($data, $x-$length, $length);
    if($length == count(array_count_values(str_split($buffer)))) {
        echo $x, PHP_EOL;
        exit;
    }
}