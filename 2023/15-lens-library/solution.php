<?php // Jeff Standen <https://phpc.social/@jeff>
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day15;

$instructions = explode(',', file_get_contents('../../data/2023/15/data.txt'));

function hash(string $string) : int {
	return array_reduce(
		str_split($string),
		fn($value, $c) => (17 * ($value + ord($c))) % 256,
		initial: 0
	);
}

// ==========================================
// Part 1: 513158

echo "Part 1: ", array_sum(array_map(fn($i) => hash($i), $instructions)), PHP_EOL;

// ==========================================
// Part 2: 200277

$boxes = array_fill_keys(range(0,255), []);

foreach($instructions as $i) {
	// Process instructions in the format abc=5,def-
	preg_match('/(.*?)([=-])(\d+)?/', $i, $matches);
	// Hash the label for the box (0-255)
	$box = hash($matches[1]);
	// Execute one of two commands
	if('=' == $matches[2]) { // replace lens
		$boxes[$box][$matches[1]] = $matches[3];
	} else { // remove lens
		unset($boxes[$box][$matches[1]]);
	}
}

// Sum (box# * item# * lens)
echo "Part 2: ", array_reduce(
	// For each non-empty box
	array_map(
		fn($box) =>
			// For each item in the box
			array_map(
				// (box# * item# * lens)
				fn($label) =>
					(1+$box)
					* (1+array_keys(array_keys($boxes[$box]), $label)[0])
					* $boxes[$box][$label],
				array_keys($boxes[$box])
			)
		,
		array_keys(array_filter($boxes))
	),
	// Sum the products per box
	fn($sum, $box) => $sum + array_sum($box),
	0
), PHP_EOL;