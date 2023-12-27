<?php // @jeff@phpc.social

namespace AoC\Year2023\Day25;

require_once('../../vendor/autoload.php');

//$lines = explode("\n", file_get_contents('example.txt'));
$lines = explode("\n", file_get_contents('../../data/2023/25/data.txt'));

$set = [];

foreach($lines as $line) {
	[$a, $remainder] = explode(': ', $line);
	
	foreach(explode(' ', $remainder) as $b) {
		$pair = [$a, $b];
		sort($pair);
		$set[$pair[0] . ' -- ' . $pair[1]] = true;
	}
}

foreach(array_keys($set) as $dot)
	echo $dot, PHP_EOL;

// Part 1: 550080 (764 * 720)
// See: images/aoc-2023-day25.svg

// Part 2: ????