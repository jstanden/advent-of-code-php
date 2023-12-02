<?php // @jeff@phpc.social
declare(strict_types=1);

$data = explode("\n", file_get_contents('../../data/2023/01/data.txt'));

// Trim letters from each line, then append the first/last digits and sum
echo "Part 1: ", array_reduce($data, function($sum, $line) {
	$line = trim(strtolower($line), implode('', range('a','z')));
	return $sum + intval($line[0] . $line[-1]);
}, 0) . PHP_EOL;

// Alternative regexp solution
/*
echo array_reduce($data, function($sum, $line) {
	preg_match('/^\D*?(\d)/', $line, $first);
	preg_match('/.*?(\d)\D*$/', $line, $last);
	return $sum + intval($first[1] . $last[1]);
}, 0) . PHP_EOL;
*/