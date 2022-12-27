<?php /** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace AoC\Year2022\Day24\Part1;

/*
 * < (563)
 * > (586)
 * ^ (619)
 * v (575)
 *   (2,345 blizzards)
 */

class Vector2d {
	public function __construct(
		public int $x,
		public int $y
	) {}
	
	static function add(Vector2d $a, Vector2d $b) : Vector2d {
		return new Vector2d($a->x + $b->x, $a->y + $b->y);
	}
	
	public function translate(Vector2d $vector) : void {
		$this->x += $vector->x;
		$this->y += $vector->y;
	}
	
	public function toString() : string {
		return sprintf('%d,%d', $this->x, $this->y);
	}
	
	public function set(Vector2d $vector) : Vector2d {
		$this->x = $vector->x;
		$this->y = $vector->y;
		return $this;
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

class GridMap {
	public array $grid = [];
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
		$this->grid = array_combine(
			array_keys($data[0]),
			array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
		);
	}
	
	// [TODO] Test this
	// [TODO] ~800ms
	private function _memoizeBlizzardStates() : void {
		// Store all possible column states
		for($t=0;$t<$this->extents['y1']-1;$t++) {
			for($x=1;$x<$this->extents['x1']-1;$x++) {
				$blizzard_col = array_filter($this->getColumn($x), fn($tile) => in_array($tile,['^','v']));
				$this->blizzardColStates[$x][$t] = array_combine(
					array_map(fn($i) => $this->_blizzardPosAtMin($i, $blizzard_col[$i], $t), array_keys($blizzard_col)),
					$blizzard_col
				);
			}
		}
		
		// Store all possible row states
		for($t=0;$t<$this->extents['x1']-1;$t++) {
			for($y=1;$y<$this->extents['y1']-1;$y++) {
				$blizzard_row = array_filter($this->getRow($y), fn($tile) => in_array($tile,['>','<']));
				$this->blizzardRowStates[$y][$t] = array_combine(
					array_map(fn($i) => $this->_blizzardPosAtMin($i, $blizzard_row[$i], $t), array_keys($blizzard_row)),
					$blizzard_row
				);
			}
		}
	}
	
	function getTile(Vector2d $loc) : ?string {
		return $this->grid[$loc->x][$loc->y] ?? null;
	}
	
	function getRow(int $y) : array {
		return array_column($this->grid, $y) ?? [];
	}
	
	function getColumn(int $x) : array {
		return $this->grid[$x] ?? [];
	}
	
	// [TODO] Can this be more efficient?
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
 
	// Use prior memoization to predict the neighbors for any (x,y) location at (t) minutes
	function getBlizzardNeighborsAtTime(Vector2d $loc, int $minute) : array {
		// Patterns repeat on the X and Y axis based on opposing extent
		$mod_row = $minute % ($this->extents['x1']-1);
		$mod_col = $minute % ($this->extents['y1']-1);
		
		// Load current and adjacent rows
		$row_before = $this->blizzardRowStates[$loc->y-1][$mod_row] ?? []; 
		$row = $this->blizzardRowStates[$loc->y][$mod_row] ?? [];
		$row_after = $this->blizzardRowStates[$loc->y+1][$mod_row] ?? [];
		
		// Load current and adjacent cols
		$col_before = $this->blizzardColStates[$loc->x-1][$mod_col] ?? [];
		$col = $this->blizzardColStates[$loc->x][$mod_col] ?? [];
		$col_after = $this->blizzardColStates[$loc->x+1][$mod_col] ?? [];
		
		// Intersect rows and columns in cardinal directions
		return array_filter([
			Vector2dDirection::NORTHWEST->value => $col_before[$loc->y-1] ?? $row_before[$loc->x-1] ?? null,
			Vector2dDirection::NORTH->value => $col[$loc->y-1] ?? $row_before[$loc->x] ?? null,
			Vector2dDirection::NORTHEAST->value => $col_after[$loc->y-1] ?? $row_before[$loc->x+1] ?? null,
			Vector2dDirection::SOUTHWEST->value => $col_before[$loc->y+1] ?? $row_after[$loc->x-1] ?? null,
			Vector2dDirection::SOUTH->value => $col[$loc->y+1] ?? $row_after[$loc->x] ?? null,
			Vector2dDirection::SOUTHEAST->value => $col_after[$loc->y+1] ?? $row_after[$loc->x+1] ?? null,
			Vector2dDirection::WEST->value => $col_before[$loc->y] ?? $row[$loc->x-1] ?? null,
			Vector2dDirection::EAST->value => $col_after[$loc->y] ?? $row[$loc->x+1] ?? null,
		], fn($v) => !is_null($v));
	}
}

class Player {
	public Vector2d $location;
	
	function __construct(
		public GridMap $map
	) {
		if(!$this->_setStartingLocation())
			die("ERROR: Player starting location is invalid.");
	}
	
	private function _setStartingLocation() : bool {
		foreach($this->map->grid as $x => $column) {
			if('.' == $column[0]) {
				$this->location = new Vector2d($x, 0);
				return true;
			}
		}
		return false;
	}
	
	// Our possible movement directions (ignore walls)
	public function getNeighbors() : array {
		$neighbors = [];
		
		foreach(Vector2dDirection::cases() as $d) {
			$n = Vector2d::add($this->location, $d->getVector());
			if('.' == ($tile = $this->map->getTile($n))) {
				$neighbors[$d->value] = [$n, $tile];
			}
		}
		
		return $neighbors;
	}
}

//$data = explode("\n", file_get_contents("test.txt"));
$data = explode("\n", file_get_contents("data.txt"));
$map = new GridMap($data);

$player = new Player($map);

// [TODO] Implement pathfinding with the dynamic landscape
// [TODO] Goal is the one `.` in the last row
// [TODO] A* pathfinding w/ heuristic Euclidean distance? (Dijkstra?)

//print_r($player->getNeighbors());
//print_r($map->getBlizzardsAtLocationAndTime($player->location, 0));
$count=0;
while($count < 1) {
	$neighbors = $map->getBlizzardNeighborsAtTime($player->location, $count);
//	$neighbors = $map->getBlizzardNeighborsAtTime(new Vector2d(10,4), $count);
	
	// Repeats every 25 on each col
	// 120 cols x 25 pattern = 3000 states
//	if($out == '.^....v.^.^^.^....v.....v')
//		echo $count, PHP_EOL;
	
	// Repeats every 120 on each row
	// 25 rows x 120 pattern = 3000 states
//	if($out == "<<<.<..>.<<.<.><><.<.<<...><<<.>><..<..>.<..>.>>.....>>...<.<<>.....<>>.>..>.<><.......<..>...<...<<>.<<>><<<...>.><<<.>")
//		echo $count, PHP_EOL;
	
	//echo $out, PHP_EOL;
	print_r($neighbors);
	
	$count++;
}

// [TODO] Render function for blizzard board

//print_r($map);