<?php /** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace AoC\Year2022\Day24\Part2;

ini_set('memory_limit', '1G');

class Vector2d {
	public function __construct(
		public int $x,
		public int $y
	) {}
	
	static function add(Vector2d $a, Vector2d $b) : Vector2d {
		return new Vector2d($a->x + $b->x, $a->y + $b->y);
	}
	
	public function toString() : string {
		return sprintf('%d,%d', $this->x, $this->y);
	}
}

enum Vector2dDirection : string {
	case NORTHWEST = 'northwest';
	case NORTH = 'north';
	case NORTHEAST = 'northeast';
	case WEST = 'west';
	case EAST = 'east';
	case SOUTHWEST = 'southwest';
	case SOUTH = 'south';
	case SOUTHEAST = 'southeast';
	
	public function getVector() : Vector2d {
		return new Vector2d(...match($this) {
			self::NORTHWEST => [-1,-1],
			self::NORTH => [0,-1],
			self::NORTHEAST => [1,-1],
			self::WEST => [-1,0],
			self::EAST => [1,0],
			self::SOUTHWEST => [-1,1],
			self::SOUTH => [0,1],
			self::SOUTHEAST => [1,1],
		});
	}
}

class GameState {
	public Vector2d $start;
	public Vector2d $goal;
	public Vector2d $location;
	public ?GameState $prev = null;
	public int $minute = 0;
	
	function __clone(): void {
		$this->location = clone $this->location;
		$this->prev = null;
	}
}

class GridMap {
	public array $initialGrid = [];
	public array $extents = [];
	public array $blizzardRowStates = [];
	public array $blizzardColStates = [];
	
	public function __construct(array $data) {
		$this->_loadData($data);
		$this->_memoizeBlizzardStates();
	}
	
	private function _loadData(array $data): void {
		// Store the grid extents
		$this->extents = [
			'x0' => 0,
			'x1' => array_reduce($data, fn($carry, $row) => max($carry, strlen($row)-1), 0),
			'y0' => 0,
			'y1' => count($data)-1,
		];
		
		// Convert row strings to character arrays and pad all to the widest
		$data = array_map(fn($row) => str_split($row), $data);
		
		// Flip to an X,Y grid
		$this->initialGrid = array_combine(
			array_keys($data[0]),
			array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
		);
	}
	
	private function _memoizeBlizzardStates() : void {
		// Store all possible column states
		for($t=0;$t<$this->extents['y1']-1;$t++) {
			for($x=1;$x<$this->extents['x1']-1;$x++) {
				$blizzard_col = array_filter($this->getInitialColumn($x), fn($tile) => in_array($tile,['^','v']));
				$this->blizzardColStates[$x][$t] = array_combine(
					array_map(fn($i) => $this->_blizzardPosAtMin($i, $blizzard_col[$i], $t), array_keys($blizzard_col)),
					$blizzard_col
				);
			}
		}
		
		// Store all possible row states
		for($t=0;$t<$this->extents['x1'];$t++) {
			for($y=1;$y<$this->extents['y1'];$y++) {
				$blizzard_row = array_filter($this->getInitialRow($y), fn($tile) => in_array($tile,['>','<']));
				$this->blizzardRowStates[$y][$t] = array_combine(
					array_map(fn($i) => $this->_blizzardPosAtMin($i, $blizzard_row[$i], $t), array_keys($blizzard_row)),
					$blizzard_row
				);
			}
		}
	}
	
	/*
	public function renderMap(int $minute, ?Vector2d $marker=null) : void {
		for($y=0; $y <= $this->extents['y1']; $y++) {
			for($x=0; $x <= $this->extents['x1']; $x++) {
				$pos = new Vector2d($x, $y);
				$tile = $this->getTileAtMinute($pos, $minute);

				if('.' != $tile)
					$tile = '_';
				
				if($marker && $marker->y == $y && $marker->x == $x)
					$tile = $tile != '.' ? 'X' : 'E';
				
				echo $tile;
			}
			echo PHP_EOL;
		}
	}
	*/

	function euclideanDistance(Vector2d $a, Vector2d $b) : float {
		return sqrt(
			pow($b->x - $a->x, 2)
			+ pow($b->y - $a->y, 2)
		);
	}
	
	function getInitialTile(Vector2d $loc) : ?string {
		return $this->initialGrid[$loc->x][$loc->y] ?? null;
	}
	
	function getTileAtMinute(Vector2d $loc, int $minute) {
		$mod_row = $minute % ($this->extents['x1']-1);
		$mod_col = $minute % ($this->extents['y1']-1);
		
		$row = $this->blizzardRowStates[$loc->y][$mod_row] ?? [];
		$col = $this->blizzardColStates[$loc->x][$mod_col] ?? [];
		
		$tile = $this->getInitialTile($loc);
		
		// Walls and blank tiles never change
		if(in_array($tile, ['#', null]))
			return $tile;
		
		$tile = '.';
		
		// If a vertical storm
		if(null != ($col[$loc->y] ?? null))
			return $col[$loc->y];
		
		// If a horizontal storm
		if(null != ($row[$loc->x] ?? null))
			return $row[$loc->x];
		
		return $tile;
	}
	
	function getInitialRow(int $y) : array {
		return array_column($this->initialGrid, $y) ?? [];
	}
	
	function getInitialColumn(int $x) : array {
		return $this->initialGrid[$x] ?? [];
	}
	
	// Start location
	public function getStart() : ?Vector2d {
		foreach($this->initialGrid as $x => $column) {
			if('.' == $column[0]) {
				return new Vector2d($x, 0);
			}
		}
		return null;
	}
	
	// Goal location
	public function getGoal() : ?Vector2d {
		$last_row_idx = array_key_last($this->initialGrid[0]);
		foreach($this->initialGrid as $x => $column) {
			if('.' == $column[$last_row_idx]) {
				return new Vector2d($x, $last_row_idx);
			}
		}
		return null;
	}
	
	private function _blizzardPosAtMin(int $initial_pos, string $tile, int $minute) {
		$delta = match(true) {
			in_array($tile,['^','<']) => -1,
			default => 1,
		};
		
		$mod = match(true) {
			in_array($tile,['^','v']) => $this->extents['y1'] - 1,
			default => $this->extents['x1'] - 1,
		};
		
		$move_from = $initial_pos;
		$move_to = ($move_from + ($minute * $delta)) % $mod;
		
		// Euclidean remainder
		if($move_to <= 0)
			$move_to += $mod;
		
		return $move_to;
	}
	
	function getTraversableNeighborsAtTime(Vector2d $loc, int $t) : array {
		return array_filter([
			Vector2dDirection::SOUTH->value => Vector2d::add($loc, Vector2dDirection::SOUTH->getVector()),
			Vector2dDirection::EAST->value => Vector2d::add($loc, Vector2dDirection::EAST->getVector()),
			Vector2dDirection::WEST->value => Vector2d::add($loc, Vector2dDirection::WEST->getVector()),
			Vector2dDirection::NORTH->value => Vector2d::add($loc, Vector2dDirection::NORTH->getVector()),
		], fn($vector) => '.' == $this->getTileAtMinute($vector, $t));
	}
	
	public function findShortestPath(?GameState $state=null) : ?GameState {
		if(is_null($state)) {
			$state = new GameState();
			$state->start = $this->getStart();
			$state->goal = $this->getGoal();
			$state->location = clone $state->start;
			$state->minute = 0;
		}
		
		$queue = new \SplQueue();
		$queue->enqueue($state);
		
		$visited_mins = [];
		
		while(!$queue->isEmpty()) {
			/** @var GameState $current */
			$state = $queue->dequeue();
			
			// If we already visited this node at this minute, don't loop back through
			if(true === ($visited_mins[$state->minute][$state->location->toString()] ?? false))
				continue;
			
			$visited_mins[$state->minute][$state->location->toString()] = true;
			
			$neighbors = $this->getTraversableNeighborsAtTime($state->location, $state->minute+1);
			
			// If our current tile is still empty next turn
			if('.' == $this->getTileAtMinute($state->location, $state->minute+1)) {
				$neighbors['wait'] = $state->location;
			}
			
			// Sort by shortest distance
			uasort($neighbors, function($a, $b) use ($state) {
				return $this->euclideanDistance($a, $state->goal) <=> $this->euclideanDistance($b, $state->goal);
			});
			
			foreach($neighbors as $vector) {
				$next_state = clone $state;
				$next_state->prev = $state;
				$next_state->location = $vector;
				$next_state->minute++;
				
				// If we hit the goal
				if($next_state->location == $state->goal)
					return $next_state;
				
				$queue->enqueue($next_state);
			}
		}
		
		return null;
	}
}

//$data = explode("\n", file_get_contents("example.txt"));
$data = explode("\n", file_get_contents("data.txt"));
$map = new GridMap($data);

$end_state = $map->findShortestPath();

printf("Part 1: %d\n", $end_state->minute); // 277

// Swap start and goal
$tmp = $end_state->start;
$end_state->start = $end_state->goal;
$end_state->goal = $tmp;
$end_state = $map->findShortestPath($end_state);

// Swap back goal and start
$tmp = $end_state->start;
$end_state->start = $end_state->goal;
$end_state->goal = $tmp;
$end_state = $map->findShortestPath($end_state);

printf("Part 2: %d\n", $end_state->minute); // 877