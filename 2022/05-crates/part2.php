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

// Output the stacks by flipping the matrix again (cols->rows)
/*
function print_stacks(array $stacks, array $labels, string $title) : void {
	echo "\e[H\e[J"; // cls
	echo $title, PHP_EOL;
	$height = max(array_map(fn($stack) => count($stack), $stacks));
	$rows = array_map(fn($stack) => array_pad($stack, -$height, EMPTY_CELL), $stacks);
	for ($y = 0; $y < $height; $y++)
		echo implode('', array_map(fn($n) => str_pad($n, 4, ' '), array_column($rows, $y))), PHP_EOL;
	echo implode('', $labels);
	echo PHP_EOL;
	usleep(50_000);
}
*/

foreach($instructions as $instruction) {
	sscanf($instruction, "move %d from %d to %d", $count, $from_stack, $to_stack);
	$from_stack--; $to_stack--; // zero-indexed matrix
	
	// Move cells from the top of one stack to the top of another
	$stacks[$to_stack] = array_merge(
		// Not reversing this is the only difference from part2
		array_splice($stacks[$from_stack], 0, $count),
		$stacks[$to_stack]
	);
	
//	echo $instruction, PHP_EOL;
//	print_stacks($stacks, $labels, $instruction);
}

// Output the top crate of each stack
echo implode('', array_map(fn($stack) => trim(current($stack),'[] '), $stacks)), PHP_EOL;