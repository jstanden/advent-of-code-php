<?php // @jeff@phpc.social
declare(strict_types=1);

namespace AoC\Year2023\Day21;

use jstanden\AoC\Library\Collections\MinPriorityQueue;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;

require_once('../../vendor/autoload.php');

ini_set('memory_limit', '2G');

//$lines = explode("\n", file_get_contents('example.txt'));
$lines = explode("\n", file_get_contents('../../data/2023/21/data.txt'));

class InfiniteGridMap2d extends GridMap2d {
	function clampVector(Vector2d $v) : Vector2d {
		$new_v = clone $v;
		
		$new_v->x = $new_v->x % ($this->extents['x1']+1);
		if($new_v->x < 0) $new_v->x = $this->extents['x1']+$new_v->x+1;
		
		$new_v->y = $new_v->y % ($this->extents['y1']+1);
		if($new_v->y < 0) $new_v->y = $this->extents['y1']+$new_v->y+1;
		
		return $new_v;
	}
	
	function getTile(Vector2d $loc): ?string {
		return parent::getTile($this->clampVector($loc));
	}
}

// Radiate outward from start
function countReachableInNStepsFrom(InfiniteGridMap2d $map, int $target_steps, Vector2d $start) : int {
	$queue = new MinPriorityQueue();
	$queue->insert([$start, 0, 0], PHP_INT_MIN);
	$visited = [];
	
	while(!$queue->isEmpty()) {
		[$v, $cost, $steps] = $queue->extract(); /** @var $v Vector2d */
		
		if($steps > $target_steps || array_key_exists((string)$v, $visited))
			continue;
		
		$visited[(string)$v] = $steps;
		
		foreach($map->getFourNeighborTiles($v) as $n) {
			if($n->name != '.') continue;
			$queue->insert([$n->origin, $cost+1, $steps+1], $cost+1);
		}
	}
	
	return count(array_filter($visited, fn($steps) => $target_steps % 2 == $steps % 2));
}

$map = new InfiniteGridMap2d($lines);

// 'S' is our origin, save $start and replace map tile with '.'
$start = $map->findTile('S'); // (65,65) == map_side/2
$map->setTile($start, '.');

// ================================================
// Part 1: 3617

echo "Part 1: ", countReachableInNStepsFrom($map, 64, $start), PHP_EOL;

// ================================================
// Part 2: 596857397104703

// ax² + bx + c with x=[0,1,2] y=[y0,y1,y2]
// f(x) = (x^2-3x+2) * y0/2 - (x^2-2x)*y1 + (x^2-x) * y2/2
// See: https://www.reddit.com/r/adventofcode/comments/18nevo3/comment/keb8ud3/
function lagrangeInterpolation(array $y_values) : array {
	return [
		'a' => $y_values[0] / 2 - $y_values[1] + $y_values[2] / 2,
		'b' => -3 * ($y_values[0] / 2) + 2 * $y_values[1] - $y_values[2] / 2,
		'c' => $y_values[0]
	];
}

$map_width = $map->extents['x1']; // map input is a square

// Get the first three y values for x=[0,1,2]
$y_values = [
	countReachableInNStepsFrom($map, $map_width/2, $start),
	countReachableInNStepsFrom($map, $map_width/2 + 131, $start),
	countReachableInNStepsFrom($map, $map_width/2 + 131*2, $start),
];

// a=14584, b=14670, c=3703
['a'=>$a, 'b'=>$b, 'c'=>$c] = lagrangeInterpolation($y_values);

// ((steps - map_side/2) / map_side) with steps=26501365 map_side=131
$x = 202_300;

// y = 14584(202300^2) + (14670*202300) + 3703
$y = $a*($x**2) + $b*$x + $c;

// Part 2: 596857397104703
echo sprintf("Part 2: %d\n", $y);
