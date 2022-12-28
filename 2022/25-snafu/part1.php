<?php
/**
 * Jeff Standen <https://phpc.social/@jeff>
 */
declare(strict_types=1);

namespace AoC\Year2022\Day25;

function snafuToDecimal(string $snafu) : int {
	$chars = str_split(strrev($snafu));
	$number = 0;
	
	foreach($chars as $pow => $count) {
		$count = match($count) {
			'-' => '-1',
			'=' => '-2',
			default => intval($count),
		};
		$number += ($count * pow(5, $pow));
	}
	
	return $number;
}

function decimalToSnafu(int $decimal) : string {
	$chars = str_split(strrev(base_convert((string) $decimal, 10, 5)));
	
	for($i=array_key_last($chars); $i > 0; $i--) {
		if($chars[$i] > 2) {
			$chars[$i+1]++;
			$chars[$i] -= 5;
			$i+=2; // Backtrack in case we overflowed left
		}
	}
	
	return implode('', array_map(fn($c) => match($c) {-1=>'-',-2=>'=',default=>$c}, array_reverse($chars)));
}

//$data = explode("\n", file_get_contents('example.txt'));
$data = explode("\n", file_get_contents('data.txt'));

$sum = 0;
foreach($data as $snafu) {
	$sum += snafuToDecimal($snafu);
}

printf("Part 1: %s\n", decimalToSnafu($sum));