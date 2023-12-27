<?php // Jeff Standen <https://phpc.social/@jeff>
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day14;

use jstanden\AoC\Library\Grid2d\Entity2d;
use jstanden\AoC\Library\Grid2d\GridMap2d;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/14/data.txt'));

// Load the input data into a 2D grid map
$grid_map = new GridMap2d($lines);

// Extract all the round rocks as entities
$round_rocks = $grid_map->findTiles(['O']);

// For each round rock
foreach($round_rocks as $rock) {
	// Ignoring rocks on the north border
	if($rock->origin->y == 0)
		continue;

	// Find a path to the northern border
	$path = array_reverse(array_slice(
		$grid_map->getColumn($rock->origin->x),
		0,
		$rock->origin->y,
		true
	), true);

	$v = clone $rock->origin;

	// Slide the rock until it hits a barrier
	foreach($path as $y => $tile) {
		if('.' != $tile) break;
		$grid_map->moveEntity($rock, $v->set($rock->origin->x, $y), '.');
	}
}

// Show our results
$grid_map->print();

// Calculate the support load (number rows in descending order, 1-based)
$load = array_reduce(
	$round_rocks,
	fn($sum, Entity2d $rock) => $sum + ($grid_map->extents['y1']-$rock->origin->y+1),
	0
);

// Part 1: 108840
echo 'Part 1: ' . $load . PHP_EOL;