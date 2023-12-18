<?php // Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace jstanden\AoC\Library\Math;

use jstanden\AoC\Library\Grid2d\Vector2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;

class Polygon {
	// Calculate the area of an irregular polygon from an ordered loop of vertices
	static function calculateIrregularArea(array $vertices) : int
	{
		$area = 0;
		$numVertices = count($vertices);
		
		// An irregular polygon must have at least 3 vertices
		if ($numVertices < 3) return 0;
		
		// Loop through each pair of vertices (e.g. A->B, B->C, C->D)
		for ($i = 0; $i < $numVertices; $i++) {
			// Wrap around to finish the loop for Z->A
			$nextIndex = ($i + 1) % $numVertices;
			$a = $vertices[$i]; /** @var $a Vector2d */
			$b = $vertices[$nextIndex]; /** @var $b Vector2d */
			// Add or subtract depending on our direction
			$area += ($a->x * $b->y) - ($b->x * $a->y);
		}
		
		return (int) (abs($area) / 2);
	}

	// Expand the outer border of an ordered loop of vertices.
	// For instance, if a path runs through the middle of grid cells.
	static function offsetPolygon(array $vertices, float $offset=0.5) : array
	{
		$expandedVertices = [];
		$count = count($vertices);
		
		// Create a tuple for each vertex (prev, current, next)
		for ($i = 0; $i < $count; $i++) {
			$prev = $vertices[$i > 0 ? $i - 1 : $count - 1];
			$current = $vertices[$i];
			$next = $vertices[($i + 1) % $count];
			
			// Determine the previous and next directions for this vertex
			$prevDirection = Vector2dDirection::between($prev, $current);
			$nextDirection = Vector2dDirection::between($current, $next);
			
			// Expand the path outward based on the angle of directions
			$expandedVertices[] = match([$prevDirection, $nextDirection]) {
				[Vector2dDirection::EAST, Vector2dDirection::NORTH],
				[Vector2dDirection::NORTH, Vector2dDirection::EAST] =>
				new Vector2d($current->x + $offset, $current->y - $offset),
				[Vector2dDirection::EAST, Vector2dDirection::SOUTH],
				[Vector2dDirection::SOUTH, Vector2dDirection::EAST] =>
				new Vector2d($current->x - $offset, $current->y - $offset),
				[Vector2dDirection::WEST, Vector2dDirection::NORTH],
				[Vector2dDirection::NORTH, Vector2dDirection::WEST] =>
				new Vector2d($current->x + $offset, $current->y + $offset),
				[Vector2dDirection::WEST, Vector2dDirection::SOUTH],
				[Vector2dDirection::SOUTH, Vector2dDirection::WEST] =>
				new Vector2d($current->x - $offset, $current->y + $offset),
			};
		}
		
		return $expandedVertices;
	}
}