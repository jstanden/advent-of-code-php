<?php // Jeff Standen <https://phpc.social/@jeff>
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day17;

use jstanden\AoC\Library\Collections\MinPriorityQueue;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;
use jstanden\AoC\Library\Grid2d\Vector2dRotation;

require_once('../../vendor/autoload.php');

ini_set('memory_limit', '384M');

$data = explode("\n", file_get_contents("../../data/2023/17/data.txt"));

class State {
	public array $path = [];
	public Vector2dDirection $heading = Vector2dDirection::NORTH;
	public array $headings = [];
	public int $steps = 0;
	public int $cost = 0;

	public function __construct(public Vector2d $position) {}

	public function __clone(): void {
		$this->position = clone $this->position;
	}

	public function getId() : string {
		return hash('xxh64', sprintf("%s %s,%d", $this->position, $this->heading->value, $this->steps));
	}
}

function findCheapestPath(GridMap2d $map, Vector2d $start, Vector2d $goal, int $max_steps=3, int $required_steps=1) : ?State {
	$queue = new MinPriorityQueue();
	$visited = [];

	$state = new State($start);
	$state->path[(string)$start] = true;

	// Insert our initial with top priority
	$queue->insert($state, priority: 0);

	while($queue->valid()) {
		$current = $queue->extract(); /** @var State $current */
		
		// If this path reached the goal with more than the required steps
		if((string)$current->position == (string)$goal && $current->steps >= $required_steps)
			return $current;
		
		// Get our four adjacent neighbors
		$neighbors = $map->getFourNeighborTiles($current->position);

		$backtrack = $current->heading->rotate(Vector2dRotation::FLIP)->value;
		
		foreach($neighbors as $dir => $n) {
			// Our crucible is not allowed to turn 180 degrees
			if($dir == $backtrack)
				continue;

			// Clone the current state for the next possible state
			$next_state = clone $current;
			$next_state->position = $n->origin;
			$next_state->cost += (int) $n->name;
			$next_state->path[(string)$next_state->position] = true;

			// If we're continuing to walk in the same direction
			if($dir === $current->heading->value) {
				// and if we took too many steps in this direction, abort
				if(++$next_state->steps > $max_steps)
					continue;
			} else {
				// If we turned but didn't walk straight long enough, abort
				if(count($next_state->path) > 2 && $next_state->steps < $required_steps)
					continue;

				// If we can turn, reset the heading and step counter
				$next_state->heading = Vector2dDirection::from($dir);
				$next_state->steps = 1;
			}
			
			$next_state->headings[] = $next_state->heading->value;

			// Our A* heuristic is naive Manhattan Distance
			$h = $map->manhattanDistance($next_state->position, $goal);

			$visited_key = $next_state->getId();
			
			// If we haven't already visited this new state, add to queue w/ cost
			if(!array_key_exists($visited_key, $visited)) {
				$visited[$visited_key] = $next_state->cost;
				// Our new priority is the current cost plus the heuristic
				$queue->insert($next_state, $next_state->cost + $h);
			}
		}
	}

	return null;
}

function printMap(GridMap2d $map, State $best_state) : void {
	$map->print(null, function(Vector2d $v, string $tile) use ($best_state) {
		if(array_key_exists((string)$v, $best_state->path)) {
			echo "\e[37m" . $tile . "\e[0m";
		} else {
			echo $tile;
		}
	});
}

// Initialize the pipe network as a 2D grid map
$map = new GridMap2d($data);
$start = new Vector2d(0, 0);
$goal = new Vector2d($map->extents['x1'], $map->extents['y1']);

// ==============================================================
// Part 1: 1013

if(!($best_state = findCheapestPath($map, $start, $goal, max_steps: 3, required_steps: 1)))
	die ("No best state found.\n");

echo "Part 1: ", $best_state->cost, PHP_EOL;

// ==============================================================
// Part 2: 1215

if(!($best_state = findCheapestPath($map, $start, $goal, max_steps: 10, required_steps: 4)))
	die ("No best state found.\n");

//printMap($map, $best_state);

echo "Part 2: ", $best_state->cost, PHP_EOL;
