<?php // Jeff Standen <https://phpc.social/@jeff>
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day14;

use jstanden\AoC\Library\Grid2d\Entity2d;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;
use jstanden\AoC\Library\Math\Cycles;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/14/data.txt'));

// Build a 2D map from the input
$grid_map = new GridMap2d($lines);

// Extract all the round rocks as entities
$round_rocks = $grid_map->findTiles(['O']);

// We tilt the platform in this sequence
$sequence = [Vector2dDirection::NORTH, Vector2dDirection::WEST, Vector2dDirection::SOUTH, Vector2dDirection::EAST];

$loads = [];
$predictions = [];

// We don't really want to loop 1B simulations, we just want to find a predictable cycle.
// From experience, we assume this will occur within a few thousand cycles.
for($simulation=1; $simulation <= 10_000; $simulation++) {
	foreach($sequence as $direction) {
		// We have to sort the rocks each tilt to visit them in the correct order
		if($direction == Vector2dDirection::NORTH) {
			uasort($round_rocks, fn($a, $b) => $a->origin->y <=> $b->origin->y);
		} else if($direction == Vector2dDirection::SOUTH) {
			uasort($round_rocks, fn($a,$b) => $b->origin->y <=> $a->origin->y);
		} else if($direction == Vector2dDirection::WEST) {
			uasort($round_rocks, fn($a,$b) => $a->origin->x <=> $b->origin->x);
		} else if($direction == Vector2dDirection::EAST) {
			uasort($round_rocks, fn($a,$b) => $b->origin->x <=> $a->origin->x);
		}
		
		foreach ($round_rocks as $rock) {
			// If the rock is already on the intended cardinal boundary, skip it
			if(match($direction) {
				Vector2dDirection::NORTH => ($rock->origin->y == $grid_map->extents['y0']),
				Vector2dDirection::SOUTH => ($rock->origin->y == $grid_map->extents['y1']),
				Vector2dDirection::WEST => ($rock->origin->x == $grid_map->extents['x0']),
				Vector2dDirection::EAST => ($rock->origin->x == $grid_map->extents['x1']),
			}) continue;
			
			// Cast a ray from each round rock in the intended direction
			if($direction == Vector2dDirection::NORTH) {
				$path = array_reverse(array_slice(
					$grid_map->getColumn($rock->origin->x),
					0,
					$rock->origin->y,
					true
				), true);
				
			} else if($direction == Vector2dDirection::SOUTH) {
				$path = array_slice(
					$grid_map->getColumn($rock->origin->x),
					$rock->origin->y + 1,
					null,
					true
				);
				
			} else if($direction == Vector2dDirection::WEST) {
				$path = array_reverse(array_slice(
					$grid_map->getRow($rock->origin->y),
					0,
					$rock->origin->x,
					true
				), true);
				
			} else if($direction == Vector2dDirection::EAST) {
				$path = array_slice(
					$grid_map->getRow($rock->origin->y),
					$rock->origin->x + 1,
					null,
					true
				);
			} else {
				$path = [];
			}
			
			$v = clone $rock->origin;
			
			// Move the rock while the path remains passable
			foreach ($path as $i => $tile) {
				if ('.' != $tile) break;
				// We change an axis depending on direction
				if(in_array($direction, [Vector2dDirection::NORTH, Vector2dDirection::SOUTH])) {
					$grid_map->moveEntity($rock, $v->set($rock->origin->x, $i), '.');
				} else {
					$grid_map->moveEntity($rock, $v->set($i, $rock->origin->y), '.');
				}
			}
		}
	}
	
	// Save our support loads every 10 simulations
	if(0 == $simulation % 10) {
		echo $simulation , ' simulations...  load: ';
		
		$load = array_reduce(
			$round_rocks,
			fn($sum, Entity2d $rock) => $sum + ($grid_map->extents['y1']-$rock->origin->y+1),
			0
		);
		
		$loads[] = $load;
		
		echo $load, PHP_EOL;
		
		// Look for a cycle every 100 simulations
		if(0 == $simulation % 100) {
			if(($predictions = Cycles::detectCycle($loads))) {
				echo sprintf("Found a cycle (n=%d): %s\n",
					count($predictions),
					implode(',', $predictions)
				);
				break;
			}
		}
	}
}

// Part 2: 103445
if($predictions) {
	$stabilized_at_index = Cycles::findSubarray($loads, $predictions);
	$stabilized_at_cycle = (($stabilized_at_index+1)*10);
	$predict_at_cycle = 1_000_000_000;
	
	echo "Stabilized at cycle: ", $stabilized_at_cycle, PHP_EOL;
	
	$index = (($predict_at_cycle-$stabilized_at_cycle)/10) % count($predictions);
	
	echo 'Part 2: ' . $loads[$stabilized_at_index + $index] . PHP_EOL;
}