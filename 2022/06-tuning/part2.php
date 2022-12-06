<?php
$data = file_get_contents("data.txt");

for($x=13;$x<strlen($data);$x++) {
	$buffer = substr($data, $x-14, 14);
	if(14 == count(array_count_values(str_split($buffer)))) {
		echo $x, PHP_EOL;
		exit;
	}
}