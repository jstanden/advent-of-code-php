<?php
$data = file_get_contents("data.txt");

for($x=3;$x<strlen($data);$x++) {
	$buffer = substr($data, $x-4, 4);
	if(4 == count(array_count_values(str_split($buffer)))) {
		echo $x, PHP_EOL;
		exit;
	}
}