<?php // @jeff@phpc.social
$lines = explode("\n", file_get_contents('../../data/2023/02/data.txt'));

// 2795
echo 'Part 1: ' . array_reduce($lines, function($sum, $line) {
	sscanf($line, "Game %d: %[a-z 0-9,;]", $id, $hints);
	$hints = preg_split('(, |; )', $hints);
	return count($hints) == count(array_filter($hints, function($hint) {
		sscanf($hint, "%d %s", $count, $color);
		return $count <= ['red' => 12, 'green' => 13, 'blue' => 14][$color];
	})) ? ($sum + $id) : $sum;
}, 0) . PHP_EOL;

// 75561
echo 'Part 2: ' . array_reduce($lines, function($sum, $line) {
	sscanf($line, "Game %d: %[a-z 0-9,;]", $id, $hints);
	$hints = preg_split('(, |; )', $hints);
	$limits = array_fill_keys(['red','green','blue'], 0);
	foreach($hints as $hint) {
		sscanf($hint, "%d %s", $count, $color);
		$limits[$color] = max($limits[$color], $count);
	}
	return $sum + array_product($limits);
}, 0) . PHP_EOL;