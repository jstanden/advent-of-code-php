<?php // Jeff Standen <https://phpc.social/@jeff>
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day18;

use jstanden\AoC\Library\Grid2d\Vector2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;
use jstanden\AoC\Library\Math\Polygon;

require_once('../../vendor/autoload.php');

$data = explode("\n", file_get_contents("../../data/2023/18/data.txt"));

/*
Since we want to include our '#' border in the irregular polygon area, we'll
start digging at an offset of (0.5, 0.5) rather than (0,0). This puts the loop
path through the middle of each cell. Afterward, we'll expand the path by ±0.5
to calculate the total area including the border.

See: <images/path-and-offset.png>
*/

$cursor = new Vector2d(0.5, 0.5);
$polygon_points = [ clone $cursor ];

// ==========================================================
// Part 1: 46394

foreach($data as $instruction) {
	// Instructions are in the format (dir, count, color): R 6 (#70c710)
	sscanf($instruction, "%s %d (%[0-9a-f#])", $direction, $count, $color);
	
	// Set our cursor's heading based on the direction
	$heading = match($direction) {
		'L' => Vector2dDirection::WEST,
		'R' => Vector2dDirection::EAST,
		'U' => Vector2dDirection::NORTH,
		'D' => Vector2dDirection::SOUTH,
	};
	
	// Add a new polygon vertex at the given direction and distance
	$cursor = Vector2d::add($cursor, $heading->getVector()->multiply(new Vector2d($count, $count)));
	
	// Since we already added the origin, don't close the loop path
	if(!($cursor->x == $polygon_points[0]->x && $cursor->y == $polygon_points[0]->y))
		$polygon_points[] = clone $cursor;
}

// Fix our ±0.5 offset by expanding the path outward to include vertex segments
$outer_polygon = Polygon::offsetPolygon($polygon_points, offset: 0.5);

echo "Part 1: ", Polygon::calculateIrregularArea($outer_polygon), PHP_EOL;

// ==========================================================
// Part 2: 201398068194715

$cursor = new Vector2d(0.5,0.5);
$polygon_points = [ clone $cursor ];

foreach($data as $instruction) {
	// Now we only care about the HEX color as a 5-byte hex->int count + 1-byte int direction
	sscanf($instruction, "%s %d (#%5x%1d)", $null, $null, $count, $direction);
	
	// Directions are now integers
	$heading = match($direction) {
		0 => Vector2dDirection::EAST,
		1 => Vector2dDirection::SOUTH,
		2 => Vector2dDirection::WEST,
		3 => Vector2dDirection::NORTH,
	};
	
	$cursor = Vector2d::add($cursor, $heading->getVector()->multiply(new Vector2d($count, $count)));
	
	if(!($cursor->x == $polygon_points[0]->x && $cursor->y == $polygon_points[0]->y))
		$polygon_points[] = clone $cursor;
}

$outer_polygon = Polygon::offsetPolygon($polygon_points);

echo "Part 2: ", Polygon::calculateIrregularArea($outer_polygon), PHP_EOL;
