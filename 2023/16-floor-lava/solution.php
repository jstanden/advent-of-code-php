<?php // Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace AoC\Year2023\Day16;

use jstanden\AoC\Library\Grid2d\Entity2d;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;
use jstanden\AoC\Library\Grid2d\Vector2dRotation;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/16/data.txt'));

// Build a 2D map from the input
$grid_map = new GridMap2d($lines);

// A beam is an entity with a heading
class Beam extends Entity2d {
	function __construct(string $name, Vector2d $origin, public Vector2dDirection $heading) {
		parent::__construct($name, $origin);
	}

	function __clone(): void {
		$this->origin = clone $this->origin;
	}
}

// Simulate a beam from a starting point
function energize(GridMap2d $grid_map, Beam $start) : int {
	$beams = [$start];
	$energized = [];
	$visited = [];

	// While we have at least one beam
	while($beams) {
		// For each beam
		foreach($beams as $beam_id => $beam) {
			// Peek at the next tile
			$next_vector = Vector2d::add($beam->origin, $beam->heading->getVector());
			$next_tile = $grid_map->getTile($next_vector);

			// See if we've been on this tile with this heading
			$visited_key = $beam->origin . $beam->heading->value;

			// Don't repeat a circuit
			if(array_key_exists($visited_key, $visited)) {
				unset($beams[$beam_id]);
				continue;
			}

			// Move the beam
			$beam->origin->set($next_vector->x, $next_vector->y);

			// If we broadside a vertical splitter, split
			if('|' == $next_tile && $beam->heading->isHorizontal()) {
				$clone = clone $beam;
				$beam->heading = Vector2dDirection::NORTH;
				$clone->heading = Vector2dDirection::SOUTH;
				$beams[] = $clone;

			// If we broadside a horizontal splitter, split
			} else if('-' == $next_tile && $beam->heading->isVertical()) {
				$clone = clone $beam;
				$beam->heading = Vector2dDirection::WEST;
				$clone->heading = Vector2dDirection::EAST;
				$beams[] = $clone;

			// If we hit a left-angled mirror horizontally
			} else if('\\' == $next_tile && $beam->heading->isHorizontal()) {
				$beam->heading = $beam->heading->rotate(Vector2dRotation::RIGHT);

			// If we hit a left-angled mirror vertically
			} else if('\\' == $next_tile && $beam->heading->isVertical()) {
				$beam->heading = $beam->heading->rotate(Vector2dRotation::LEFT);

			// If we hit a right-angled mirror horizontally
			} else if('/' == $next_tile && $beam->heading->isHorizontal()) {
				$beam->heading = $beam->heading->rotate(Vector2dRotation::LEFT);

			// If we hit a right-angled mirror vertically
			} else if('/' == $next_tile && $beam->heading->isVertical()) {
				$beam->heading = $beam->heading->rotate(Vector2dRotation::RIGHT);

			// If we leave the map, remove the beam
			} elseif(null == $next_tile) {
				unset($beams[$beam_id]);
				continue;
			}

			$visited[$visited_key] = true;
			$energized[(string)$beam->origin] = true;
		}
	}

	return count($energized);
}

// =================================================
// Part 1: 7111

$start = new Beam('beam', new Vector2d(-1,0), Vector2dDirection::EAST);
echo "Part 1: " . energize($grid_map, $start) . PHP_EOL;

// =================================================
// Part 2: 7831

// Consider every possible start location on a border
$starts = array_merge(
	// Left side going right
	array_map(
		fn($y) => [-1, $y, Vector2dDirection::EAST],
		array_keys($grid_map->getColumn(0))),
	// Right side going left
	array_map(
		fn($y) => [$grid_map->extents['x1']+1, $y, Vector2dDirection::WEST],
		array_keys($grid_map->getColumn($grid_map->extents['x1']))),
	// Top side going down
	array_map(
		fn($x) => [$x, -1, Vector2dDirection::SOUTH],
		array_keys($grid_map->getRow(0))),
	// Bottom side going up
	array_map(
		fn($x) => [$x, $grid_map->extents['y1']+1, Vector2dDirection::NORTH],
		array_keys($grid_map->getRow($grid_map->extents['y1'])))
);

// Find the max energized
echo "Part 2: " . max(
	// For every possible start location
	array_map(
		fn($start) => energize(
			$grid_map,
			new Beam('beam', new Vector2d($start[0], $start[1]), $start[2])
		),
		$starts
	)
) . PHP_EOL;