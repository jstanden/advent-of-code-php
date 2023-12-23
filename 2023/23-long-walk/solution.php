<?php // @jeff@phpc.social
declare(strict_types=1);

namespace AoC\Year2023\Day23;

use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;

require_once('../../vendor/autoload.php');

//$lines = explode("\n", file_get_contents('example.txt'));
$lines = explode("\n", file_get_contents('../../data/2023/23/data.txt'));

$map = new GridMap2d($lines);

// Start is the only dot on the first row, goal is the only dot on the last
$start = new Vector2d(array_search('.', $map->getRow(0)), 0);
$goal = new Vector2d(array_search('.', $map->getRow($map->extents['y1'])), $map->extents['y1']);

// Condense the maze into a graph of intersections
function findNeighborIntersections(Vector2d $start, Vector2d $goal, GridMap2d $map) : array {
	static $memoize_neighbors = [];
	
	if(array_key_exists((string)$start, $memoize_neighbors))
		return $memoize_neighbors[(string)$start];
	
	// BFS
	$queue = new \SplQueue();
	$queue->enqueue([$start, 0]);
	$visited = [];
	$intersections = [];
	
	while(!$queue->isEmpty()) {
		[$at, $steps] = $queue->dequeue();
		
		$visited[(string)$at] = true;
		
		// Find the neighbors excluding walls and out-of-bounds
		$neighbors = $map->getFourNeighborTiles($at, excluding:['','#']);
		
		// An intersection has more than two connections; stop this path
		if(!$at->equals($start) && ($at->equals($goal) || count($neighbors) > 2)) {
			$intersections[(string)$at] = [$at, $steps];
			continue;
		}
		
		// Count steps for all neighbors we haven't seen
		foreach($neighbors as $n) {
			if(array_key_exists((string)$n->origin, $visited)) continue;
			$queue->enqueue([$n->origin, $steps+1]);
		}
	}
	
	$memoize_neighbors[(string)$start] = $intersections;
	return $memoize_neighbors[(string)$start];
}

function findLongestPath(Vector2d $start, Vector2d $goal, GridMap2d $map, int $part=1) {
	$queue = new \SplStack();
	$queue->push([$start, 0, []]);
	$best = PHP_INT_MIN;
	
	// DFS
	while(!$queue->isEmpty()) {
		[$at, $steps, $visited] = $queue->pop();
		
		// If we hit the goal, see if it's our new longest length
		if($at->equals($goal)) {
			$best = max($best, $steps);
			continue;
		}
		
		$visited[(string)$at] = true;
		
		// For Part 2, use intersections rather than the grid maze
		if(2 == $part) {
			$neighbors = findNeighborIntersections($at, $goal, $map);
			
			foreach($neighbors as $n) {
				if(array_key_exists((string)$n[0], $visited)) continue;
				$queue->push([$n[0], $steps+$n[1], $visited]);
			}
			
		} else {
			// For Part 1, we restrict intersection directions
			$neighbors = $map->getFourNeighborTiles($at, excluding: ['', '#']);
			
			foreach($neighbors as $dir => $n) {
				if(array_key_exists((string)$n->origin, $visited)) continue;
				if($n->name == '>' && $dir != 'east') continue;
				if($n->name == '<' && $dir != 'west') continue;
				if($n->name == '^' && $dir != 'north') continue;
				if($n->name == 'v' && $dir != 'south') continue;
				$queue->push([$n->origin, $steps+1, $visited]);
			}
		}
	}
	
	return $best;
}

// Part 1: 2502
echo "Part 1: ", findLongestPath($start, $goal, $map, part: 1), PHP_EOL;

// Part 2: 6726
echo "Part 2: ", findLongestPath($start, $goal, $map, part: 2), PHP_EOL;