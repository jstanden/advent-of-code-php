<?php // Jeff Standen <@jeff@phpc.social>
/** @noinspection DuplicatedCode */
/** @noinspection SpellCheckingInspection */

namespace AoC\Year2023\Day10;

use jstanden\AoC\Library\Grid2d\Bounds2d;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;

require_once('../../vendor/autoload.php');

enum PipeType : string {
	case NORTH_SOUTH = '|';
	case WEST_EAST = '-';
	case NORTH_EAST = 'L';
	case NORTH_WEST = 'J';
	case SOUTH_WEST = '7';
	case SOUTH_EAST = 'F';

	static public function northerly() : array {
		return [ self::SOUTH_WEST, self::SOUTH_EAST, self::NORTH_SOUTH ];
	}

	static public function southerly() : array {
		return [ self::NORTH_WEST, self::NORTH_EAST, self::NORTH_SOUTH ];
	}

	static public function westerly() : array {
		return [ self::NORTH_EAST, self::SOUTH_EAST, self::WEST_EAST ];
	}

	static public function easterly() : array {
		return [ self::NORTH_WEST, self::SOUTH_WEST, self::WEST_EAST ];
	}

	public function getPossibleConnections() : array {
		// The possible pipe types that fit with this pipe
		return match ($this) {
			self::NORTH_SOUTH => [ 'north' => self::northerly(), 'south' => self::southerly()],
			self::WEST_EAST =>   [ 'west'  => self::westerly(),  'east'  => self::easterly()],
			self::NORTH_EAST =>  [ 'north' => self::northerly(), 'east'  => self::easterly()],
			self::NORTH_WEST =>  [ 'north' => self::northerly(), 'west'  => self::westerly()],
			self::SOUTH_EAST =>  [ 'south' => self::southerly(), 'east'  => self::easterly()],
			self::SOUTH_WEST =>  [ 'south' => self::southerly(), 'west'  => self::westerly()],
		};
	}

	// Figure out the current pipe type given a set of adjacent neigbors (used for 'S')
	static public function factoryByNeighbors(array $neighbors) : ?PipeType {
		foreach(self::cases() as $type) {
			$candidates = $type->getPossibleConnections();
			$inlet = array_key_first($candidates);
			$outlet = array_key_last($candidates);
			
			// A matching type will have a corresponding neighbor on inlet & outlet
			if(in_array($neighbors[$inlet]->name ?? null, array_column($candidates[$inlet], 'value'))
				&& in_array($neighbors[$outlet]->name ?? null, array_column($candidates[$outlet], 'value'))
			) return $type;
		}

		return null;
	}
}

$data = explode("\n", file_get_contents("../../data/2023/10/data.txt"));

// Initialize the pipe network as a 2D gridmap
$map = new GridMap2d($data);

// Find our starting tile
$start = $map->findTile('S');

// Determine the pipe type for our starting tile given its neighbors
$map->setTile(
	$start,
	PipeType::factoryByNeighbors($map->getFourNeighborTiles($start))->value
);

// Clone the starting vector for our current position
$at = clone $start;

// Keep track of coordinates we've visited in the pipe circuit
$visited = [];

// BFS
$stack = new \SplQueue();
$stack->enqueue([$at, 0]); // start at 'S' with zero steps taken

while(!$stack->isEmpty()) {
	list($at, $steps) = $stack->dequeue();
	$visited[(string)$at] = $steps;
	$tile = $map->getTile($at);
	
	// We only follow neighbors with corresponding inlets/outlets to this pipe segment
	$connections = PipeType::from($tile)->getPossibleConnections();

	foreach($map->getFourNeighborTiles($at) as $direction => $neighbor) {
		if(
			// If we haven't visited this neighbor yet
			!array_key_exists((string)$neighbor->origin, $visited)
			// And the pipe type is one of our possible connections
			&& in_array(PipeType::tryFrom($neighbor->name), $connections[$direction] ?? [])
		) {
			// Explore this neighbor next
			$stack->push([$neighbor->origin, $steps + 1]);
		}
	}
}

// ==============================================================
// Part 1: 6897

// Our highest number of steps is the max distance away
arsort($visited);
echo "Part 1: " . current($visited) . PHP_EOL;

// ==============================================================
// Part 2: 367

/*
Now that we know our pipe circuit, convert every other tile to dots. We only
care about collisions in Part 2.
*/
$map->fill(
	new Bounds2d(new Vector2d(0,0), $map->extents['x1'], $map->extents['y1']),
	function(Vector2d $v) use ($visited, $start, $map) {
		if(!array_key_exists((string)$v, $visited))
			$map->setTile($v, '.');
	}
);

/*
To handle flood fill between adjacent pipes, we're just going to cheat and
upsample the map 3X. This means every character becomes a 3x3 grid.

For instance, an intersection like `||` or `JL` becomes:
.x..x.      .x..x.
.x..x.  or  xx..xx
.x..x.      ......

We expand dots to a single dot in the center of a 3x3 grid so our count
remains accurate. This drastically simplifies finding the enclosed area.

Analysis of our input shows (0,0) is a suitable position to begin a flood
fill, and the entire pipe circuit is surrounded by empty space (it can be
approached from any side).
*/
$new_data = [];

foreach($map->getRows() as $y => $row) {
	$new_data[$y*3] = ''; // Each line is now three lines
	$new_data[$y*3+1] = '';
	$new_data[$y*3+2] = '';
	
	// Be lazy and manually convert each of the six characters to walls
	foreach($row as $tile) {
		if('F' == $tile) {
			$new_data[$y*3+0] .= '   ';
			$new_data[$y*3+1] .= ' xx';
			$new_data[$y*3+2] .= ' x ';
		} else if('7' == $tile) {
			$new_data[$y*3+0] .= '   ';
			$new_data[$y*3+1] .= 'xx ';
			$new_data[$y*3+2] .= ' x ';
		} else if('J' == $tile) {
			$new_data[$y*3+0] .= ' x ';
			$new_data[$y*3+1] .= 'xx ';
			$new_data[$y*3+2] .= '   ';
		} else if('L' == $tile) {
			$new_data[$y*3+0] .= ' x ';
			$new_data[$y*3+1] .= ' xx';
			$new_data[$y*3+2] .= '   ';
		} else if('|' == $tile) {
			$new_data[$y*3+0] .= ' x ';
			$new_data[$y*3+1] .= ' x ';
			$new_data[$y*3+2] .= ' x ';
		} else if('-' == $tile) {
			$new_data[$y*3+0] .= '   ';
			$new_data[$y*3+1] .= 'xxx';
			$new_data[$y*3+2] .= '   ';
		// A dot is converted to a single dot for counting purposes
		} else if('.' == $tile) {
			$new_data[$y*3+0] .= '   ';
			$new_data[$y*3+1] .= ' . ';
			$new_data[$y*3+2] .= '   ';
		} else {
			$new_data[$y*3+0] .= '   ';
			$new_data[$y*3+1] .= '   ';
			$new_data[$y*3+2] .= '   ';
		}
	}
}

// Generate the new grid map
$new_gridmap = new GridMap2d($new_data);
unset($map);

$queue = new \SplQueue();
$queue->enqueue(new Vector2d(0,0)); // from the top-left corner
$flooded = [];

// Flood fill air from (0,0) to find fully enclosed spaces
while(!$queue->isEmpty()) {
	$at = $queue->dequeue();
	$flooded[(string)$at] = true;
	$neighbors = $new_gridmap->getFourNeighborTiles($at);
	$tile = $new_gridmap->getTile($at);

	// If we're a non-wall tile, adjacent to an edge or a previously filled tile (air/water)
	if(in_array($tile, ['.',' ']) && (count($neighbors) < 4 || array_filter($neighbors, fn($n)=>'O'==$n->name))) {
		$new_gridmap->setTile($at, 'O');
		foreach($neighbors as $n) {
			// If our neighbor is not a wall, and we haven't explored it, do so
			if(!array_key_exists((string)$n->origin, $flooded))
				if($n->name != 'x') $queue->enqueue($n->origin);
		}
	}
}

// 367
echo "Part 2: " . array_sum(
	array_map(fn($row) => array_count_values($row)['.'] ?? 0, $new_gridmap->getColumns())
) . PHP_EOL;