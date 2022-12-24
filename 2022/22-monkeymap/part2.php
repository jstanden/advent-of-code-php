<?php /** @noinspection DuplicatedCode */
/**
 * Jeff Standen <https://phpc.social/@jeff>
 */
declare(strict_types=1);

namespace AoC\Year2022\Day22\Part2;

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
	
	public function getVector() {
		return match($this) {
			Vector2dDirection::DOWN => [0, 1],
			Vector2dDirection::LEFT => [-1, 0],
			Vector2dDirection::RIGHT => [1, 0],
			Vector2dDirection::UP => [0, -1],
		};
	}
}

class Vector2d {
	public function __construct(
		public int $x,
		public int $y
	) {}
	
	public function translate(Vector2d $vector) : void {
		$this->x += $vector->x;
		$this->y += $vector->y;
	}
	
	public function rotate(Vector2dRotation $rotation, ?Vector2d $origin=null) : void {
		if(is_null($origin))
			$origin = new Vector2d(0,0);
		
		if($rotation == Vector2dRotation::RIGHT) {
			$rotation_matrix = [[0,-1], [1,0]];
		} else {
			$rotation_matrix = [[0,1], [-1,0]];
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

class CubeMap {
	private array $_grid = [];
	private array $_extents = [];
	public array $edges = [];
	
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
		
		// Figure out the map dimensions
		/*
		if($extents['x1'] > $extents['y1']) {
			$aspect_ratio = [4,3];
			$side_length = $extents['x1']/4;
		} else {
			$aspect_ratio = [3,4];
			$side_length = $extents['y1']/4;
		}
		*/
		
		// Convert row strings to character arrays and pad all to the widest
		$data = array_map(fn($row) => array_pad(str_split($row), $this->_extents['x1'], ' '), $data);
		
		// Flip to an X,Y grid
		$this->_grid = array_combine(
			array_keys($data[0]),
			array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
		);
		
		// Map edges
		$this->edges = array_merge( 
			// Front (up) -> Top (right)
			array_combine(
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(50,0), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(0,150), 50, Vector2dDirection::DOWN)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(0,150), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(50,0), 50, Vector2dDirection::RIGHT)),
			),
			
			// Front (left) -> Left (right)
			array_combine(
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(50,49), 50, Vector2dDirection::UP)),
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(0,100), 50, Vector2dDirection::DOWN)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(0,100), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(50,49), 50, Vector2dDirection::UP)),
			),
			
			// Right (up) -> Top (up)
			array_combine(
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(100,0), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(0,199), 50, Vector2dDirection::RIGHT)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(0,199), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(100,0), 50, Vector2dDirection::RIGHT)),
			),
		
			// Right (right) -> Back (left)
			array_combine(
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(149,49), 50, Vector2dDirection::UP)),
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(99,100), 50, Vector2dDirection::DOWN)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(99,100), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(149,49), 50, Vector2dDirection::UP)),
			),
			
			// Right (down) -> Bottom (left)
			array_combine(
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(100,49), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(99,50), 50, Vector2dDirection::DOWN)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(99,50), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(100,49), 50, Vector2dDirection::RIGHT)),
			),
		
			// Top (right) -> Back (up)
			array_combine(
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(49,150), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(50,149), 50, Vector2dDirection::RIGHT)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(50,149), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(49,150), 50, Vector2dDirection::DOWN)),
			),
		
			// Bottom (left) -> Left (down)
			array_combine(
				array_map(fn($xy) => $xy.',left', $this->getRange(new Vector2d(50,50), 50, Vector2dDirection::DOWN)),
				array_map(fn($xy) => $xy.',down', $this->getRange(new Vector2d(0,100), 50, Vector2dDirection::RIGHT)),
			),
			array_combine(
				array_map(fn($xy) => $xy.',up', $this->getRange(new Vector2d(0,100), 50, Vector2dDirection::RIGHT)),
				array_map(fn($xy) => $xy.',right', $this->getRange(new Vector2d(50,50), 50, Vector2dDirection::DOWN)),
			),
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
	
	public function getRange(Vector2d $loc, int $count, Vector2dDirection $d) : array {
		$path = [];

		$d = $d->getVector();
		
		for($i=0; $i<$count; $i++) {
			$path[] = sprintf("%d,%d", $loc->x + ($i * $d[0]), $loc->y + ($i * $d[1]));
		}
		
		return $path;
	}
}

class Player {
	public function __construct(
		public Vector2d $heading,
		public Vector2d $location,
		public CubeMap  $map,
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
			$next_heading = $this->direction();
			
			$tile = $this->map->getTile(...$next_loc->toArray());
			
			// If the tile is out of bounds, we need to wrap
			while(in_array($tile, [' ', null])) {
				debug(sprintf("Forward was out of bounds (%d,%d)\n", ...$next_loc->toArray()));
				
				$direction = $this->direction();
				
				debug(sprintf("Direction is %s\n", $direction->name));
				
				if(!($wrap_to = ($this->map->edges[sprintf('%d,%d,%s', $this->location->x, $this->location->y, $direction->value)] ?? null))) {
					die("Out of bounds\n");
				}
				
				list($new_x, $new_y, $new_d) = explode(',', $wrap_to);

				debug(sprintf("Wrap to (%d,%d) facing %s\n", $new_x, $new_y, $new_d));
				
				$next_loc->set(new Vector2d((int) $new_x, (int) $new_y));
				$next_heading = Vector2dDirection::from($new_d);
				
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
			$this->heading->set(new Vector2d(...$next_heading->getVector()));
			debug(sprintf("Now at (%d,%d) facing %s [%s]\n", $this->location->x, $this->location->y, $next_heading->value, $tile));
 		}
	}
}

$data = explode("\n", file_get_contents('data.txt'));
//$data = explode("\n", file_get_contents('test.txt'));
$instructions = preg_split('#([LR])#', array_pop($data), -1, PREG_SPLIT_DELIM_CAPTURE);
array_pop($data); // blank line

// Initialize the grid map
$map = new CubeMap($data);

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

printf("Part 2: %d\n", 
	(1000 * ($player->location->y+1))
	+ (4 * ($player->location->x+1)) 
	+ $player->direction()->toInt()
);