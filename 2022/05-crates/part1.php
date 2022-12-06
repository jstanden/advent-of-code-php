<?php
$data = file_get_contents('data.txt');
$split_at = strpos($data,"\n\n");
$crates = explode("\n", substr($data, 0, $split_at));
$labels = str_split(array_pop($crates), 4);
$instructions = explode("\n", substr($data, $split_at+2));
const EMPTY_CELL = '    ';

// Split rows into equal-sized columns
$crates = array_map(
	fn($row) => array_pad(str_split($row, 4), count($labels), EMPTY_CELL),
	$crates
);

// Flip matrix (rows->columns)
$stacks = array_map(
	fn($index) => array_values(array_diff(array_column($crates, $index), [EMPTY_CELL])),
	array_keys($labels)
);

foreach($instructions as $instruction) {
	sscanf($instruction, "move %d from %d to %d", $count, $from_stack, $to_stack);
	$from_stack--; $to_stack--; // zero-indexed matrix
	
	// Move cells from the top of one stack to the top of another
	$stacks[$to_stack] = array_merge(
		// Reversing this is the only difference from part2
		array_reverse(array_splice($stacks[$from_stack], 0, $count)),
		$stacks[$to_stack]
	);
}

// Output the top crate of each stack
echo implode('', array_map(fn($stack) => trim(current($stack),'[] '), $stacks)), PHP_EOL;
