<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day11;

use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Math\Combinations;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/11/data.txt'));

// Parse the map data into a 2D grid
$universe = new GridMap2d($lines);

// Blank rows and columns have a higher move cost
$intergalactic_multiplier = 1_000_000;

// Build an index of horizontal move costs
$x_deltas = array_map(
	// If a blank column, use the multiplier
	fn($col) => !array_filter($col, fn($x)=>'.'!=$x) ? $intergalactic_multiplier : 1,
	$universe->getColumns()
);

// Build an index of vertical move costs
$y_deltas = array_map(
	// If a blank row, use the multiplier
	fn($row) => !array_filter($row, fn($y)=>'.'!=$y) ? $intergalactic_multiplier : 1,
	$universe->getRows()
);

// Find the galaxy tiles
$galaxies = $universe->findTiles(['#']);

// Pair them up into distinct sets
$pairs = Combinations::pairs($galaxies);

// Part 2: 634324905172
echo "Part 2: " . array_reduce(
	$pairs,
	// Sum the minimum distance of each galactic pair
	function(int $sum, array $pair) use (&$universe, &$x_deltas, &$y_deltas) {
		$range_x = $range_y = [];
		
		// If the pair are in different columns
		if($pair[0]->origin->x != $pair[1]->origin->x) {
			// Sort by x ascending and get a column range
			usort($pair, fn($a, $b) => $a->origin->x <=> $b->origin->x);
			$range_x = range((int)$pair[0]->origin->x+1, (int)$pair[1]->origin->x);
		}
		
		// If the pair are in different rows
		if($pair[0]->origin->y != $pair[1]->origin->y) {
			// Sort by y ascending and get a row range
			usort($pair, fn($a,$b) => $a->origin->y <=> $b->origin->y);
			$range_y = range((int)$pair[0]->origin->y+1, (int)$pair[1]->origin->y);
		}
		
		// Intersect our row/col ranges with our move cost indexes
		return $sum
			+ array_sum(array_intersect_key($x_deltas, array_flip($range_x)))
			+ array_sum(array_intersect_key($y_deltas, array_flip($range_y)))
		;
	},
	0
) . PHP_EOL;
