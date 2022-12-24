<?php /** @noinspection DuplicatedCode */
/**
 * Jeff Standen <https://phpc.social/@jeff>
 */
declare(strict_types=1);

namespace AoC\Year2022\Day22\Part1;

const DEBUG = false;

function debug(string $string) : void {
	if(!DEBUG) return;
	echo $string;
}

enum Vector2dRotation : string {
	case LEFT = 'L';
	case RIGHT = 'R';
}

enum Vector2dDirection : string {
	case DOWN = 'down';
	case LEFT = 'left';
	case RIGHT = 'right';
	case UP = 'up';
	
	public function toInt() : int {
		return match($this) {
			self::RIGHT => 0,
			self::DOWN => 1,
			self::LEFT => 2,
			self::UP => 3,
		};
	}
}

class Vector2d {
	public function __construct(
		public int $x,
		public int $y
	) {}
	
	public function rotate(Vector2dRotation $rotation, ?Vector2d $origin=null) : void {
		if(is_null($origin))
			$origin = new Vector2d(0,0);
		
		if($rotation == Vector2dRotation::RIGHT) {
			$rotation_matrix = [[0, -1], [1, 0]];
		} else {
			$rotation_matrix = [[0, 1], [-1, 0]];
		}
		
		$x_translated = $this->x - $origin->x;
		$y_translated = $this->y - $origin->y;
		
		$x_rotated = $rotation_matrix[0][0] * $x_translated + $rotation_matrix[0][1] * $y_translated;
		$y_rotated = $rotation_matrix[1][0] * $x_translated + $rotation_matrix[1][1] * $y_translated;
		
		$this->x = $x_rotated + $origin->x;
		$this->y = $y_rotated + $origin->y;
	}
	
	public function forward(Vector2d $heading) : Vector2d {
		return new Vector2d($this->x + $heading->x, $this->y + $heading->y);
	}
	
	public function set(Vector2d $vector) : void {
		$this->x = $vector->x;
		$this->y = $vector->y;
	}
	
	public function toArray() : array {
		return [$this->x, $this->y];
	}
}

class GridMap {
	private array $_grid = [];
	private array $_extents = [];
	
	public function __construct(array $data) {
		$this->_loadData($data);
	}
	
	private function _loadData(array $data) : void {
		// Store the grid extents
		$this->_extents = [
			'x0' => 0,
			'y0' => 0,
			'x1' => array_reduce($data, fn($carry, $row) => max($carry, strlen($row)), 0),
			'y1' => count($data),
		];

		// Convert row strings to character arrays and pad all to the widest
		$data = array_map(fn($row) => array_pad(str_split($row), $this->_extents['x1'], ' '), $data);

		// Flip to an X,Y grid
		$this->_grid = array_combine(
			array_keys($data[0]),
			array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
		);
	}
	
	public function getColumn(int $x) : array {
		return $this->_grid[$x] ?? [];		
	}
	
	public function getRow(int $y) : array {
		return array_column($this->_grid, $y) ?? [];		
	}
	
	public function getTile(int $x, int $y) : string {
		return $this->_grid[$x][$y] ?? '';
	}
}

class Player {
	public function __construct(
		public Vector2d $heading,
		public Vector2d $location,
		public GridMap  $map,
	) {}
	
	public function direction() : Vector2dDirection {
		return match($this->heading->toArray()) {
			[0,1] => Vector2dDirection::DOWN,
			[-1,0] => Vector2dDirection::LEFT,
			[1,0] => Vector2dDirection::RIGHT,
			[0,-1] => Vector2dDirection::UP
		};
	}
	
	public function moveForward(int $steps) : void {
		while($steps--) {
			$next_loc = $this->location->forward($this->heading);
			
			$tile = $this->map->getTile(...$next_loc->toArray());
			
			// If the tile is out of bounds, we need to wrap
			while(in_array($tile, [' ', null])) {
				debug(sprintf("Forward was out of bounds (%d,%d)\n", ...$next_loc->toArray()));
				
				$direction = $this->direction();
				
				debug(sprintf("Direction is %s\n", $direction->name));
				
				if ($direction == Vector2dDirection::RIGHT || $direction == Vector2dDirection::LEFT) {
					$row = array_filter($this->map->getRow($this->location->y), fn($tile) => !in_array($tile, [' ', null]));
					$new_x = $direction == Vector2dDirection::RIGHT
						? array_key_first($row)
						: array_key_last($row);
					$next_loc->x = (int) $new_x;
					
					debug(sprintf("Warp to X:%d\n", $new_x));
					
				} else { // UP || DOWN
					$col = array_filter($this->map->getColumn($this->location->x), fn($tile) => !in_array($tile, [' ', null]));
					$new_y = $direction == Vector2dDirection::DOWN
						? array_key_first($col)
						: array_key_last($col);
					$next_loc->y = (int) $new_y;
					
					debug(sprintf("Warp to Y:%d\n", $new_y));
				}
				
				$tile = $this->map->getTile(...$next_loc->toArray());
				
				debug(sprintf("Forward is now (%d,%d) [%s]\n", $next_loc->x, $next_loc->y, $tile));
			}
			
			// If we're blocked, stop moving
			if('#' == $tile) {
				debug(sprintf("Blocked at (%d,%d) [%s]\n", $next_loc->x, $next_loc->y, $tile));
				debug(sprintf("Remaining at (%d,%d) [%s]\n", $this->location->x, $this->location->y, $this->map->getTile(...$this->location->toArray())));
				break;
			}
			
			// Otherwise, update our location
			$this->location->set($next_loc);
			debug(sprintf("Now at (%d,%d) [%s]\n", $this->location->x, $this->location->y, $tile));
 		}
	}
}

$data = explode("\n", file_get_contents('data.txt'));
//$data = explode("\n", file_get_contents('test.txt'));
$instructions = preg_split('#([LR])#', array_pop($data), -1, PREG_SPLIT_DELIM_CAPTURE);
array_pop($data); // blank line

// Initialize the grid map
$map = new GridMap($data);

// Player location (first walkable tile in the top row)
$location = [array_search('.', $map->getRow(0)), 0];

// Initialize the player w/ heading (right-facing, +x) + location
$player = new Player(new Vector2d(1,0), new Vector2d(...$location), $map);

debug(sprintf("Player starting at (%d,%d) facing %s\n", $player->location->x, $player->location->y, $player->direction()->name));

foreach($instructions as $instruction) {
	debug(sprintf("Instruction: %s\n", $instruction));
	
	if(is_numeric($instruction)) {
		$player->moveForward((int) $instruction);
		
	} else {
		debug(sprintf("Rotating %s from %s to ", Vector2dRotation::from($instruction)->name, $player->direction()->name));
		$player->heading->rotate(Vector2dRotation::from($instruction));
		debug(sprintf("%s\n", $player->direction()->name));
	}
}

printf("Part 1: %d\n", 
	(1000 * ($player->location->y+1))
	+ (4 * ($player->location->x+1)) 
	+ $player->direction()->toInt()
);