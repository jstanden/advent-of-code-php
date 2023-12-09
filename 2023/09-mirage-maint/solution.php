<?php // Jeff Standen <@jeff@phpc.social>
/** @noinspection DuplicatedCode */
/** @noinspection SpellCheckingInspection */

namespace AoC\Year2023\Day9;

$data = explode("\n", file_get_contents("../../data/2023/09/data.txt"));

// Build trees top to bottom for each input line
$trees = array_map(function($line) {
	$tree = [];

	// Start our tree with a line of values from the input
	$tree[] = explode(' ', $line);

	// While the current row of the tree isn't all zeroes
	while(array_filter(end($tree))) {
		$new = [];
		// Track the previous last row of the tree
		$last = end($tree);

		// Build the next row using delta from the previous line
		for ($i = 1; $i < count($last); $i++) {
			$new[] = $last[$i] - $last[$i - 1];
		}

		// Add the new row to the tree
		$tree[] = $new;
	}

	return $tree;
}, $data);

// Part 1: 1782868781
echo "Part 1: " . array_sum(array_map(function($tree) {
	// Propagate backwards to create new row values on the right
	for($i=array_key_last($tree); $i >= 0; $i--) {
		if($i == array_key_last($tree)) {
			// In the bottom row of the tree we just add a new 0 to the right
			$tree[$i][] = 0;
		} else {
			// For higher rows, we add a new end row value as (our rightmost - lower rightmost)
			$tree[$i][] = end($tree[$i]) + end($tree[$i+1]);
		}
	}

	// Return the predicted (top left) value of each tree
	$predict = reset($tree);
	return end($predict);
}, $trees)) . PHP_EOL;

// Part 2: 1057
echo "Part 2: " . array_sum(array_map(function($tree) {
	// Propagate backwards to create new row values on the left
	for($i=array_key_last($tree); $i >= 0; $i--) {
		if($i == array_key_last($tree)) {
			// In the bottom row of the tree we just add a new 0 to the left
			array_unshift($tree[$i], 0);
		} else {
			// For higher rows, we add a new first row value as (our leftmost - lower leftmost)
			array_unshift($tree[$i], reset($tree[$i]) - reset($tree[$i+1]));
		}
	}

	// Return the predicted (top left) value of each tree
	$predict = reset($tree);
	return reset($predict);
}, $trees)) . PHP_EOL;