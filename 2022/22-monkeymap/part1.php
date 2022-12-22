<?php
$data = explode("\n", file_get_contents('test.txt'));
$instructions = preg_split('#([LR])#', array_pop($data), -1, PREG_SPLIT_DELIM_CAPTURE);
array_pop($data); // blank line

// Store the grid extents
$extents = [
	'x0' => 0,
	'y0' => 0,
	'x1' => array_reduce($data, fn($carry, $row) => max($carry, strlen($row)), 0),
	'y1' => count($data),
];

// Convert row strings to character arrays and pad all to the widest
$data = array_map(fn($row) => array_pad(str_split($row), $extents['x1'], ' '), $data);

// Flip to an X,Y grid
$grid = array_combine(
	array_keys($data[0]),
	array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
);

unset($data);
	
// Player heading (facing right, +x)
$heading_vector = [1,0];

// Player location (first walkable tile in the top row)
$location = [array_search('.', array_column($grid, 0)), 0];

print_r($grid);
print_r($instructions);
print_r($heading_vector);
print_r($location);