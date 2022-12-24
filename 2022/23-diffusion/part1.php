<?php
/** @noinspection DuplicatedCode */
declare(strict_types=1);

namespace AoC\Year2022\Day23\Part1;

class Vector2d {
	public function __construct(
		public int $x,
		public int $y
	) {}
	
	public function translate(Vector2d $vector) : void {
		$this->x += $vector->x;
		$this->y += $vector->y;
	}
	
	public function toString() : string {
		return sprintf('%d,%d', $this->x, $this->y);
	}
	
	public function set(Vector2d $vector) : void {
		$this->x = $vector->x;
		$this->y = $vector->y;
	}
	
//	public function toArray() : array {
//		return [$this->x, $this->y];
//	}
}

enum ProposedDirection : string {
	case NORTH = 'north';
	case SOUTH = 'south';
	case WEST = 'west';
	case EAST = 'east';
	
	public function getKeys() : array {
		return match($this) {
			self::NORTH => ['northwest','north','northeast'],
			self::SOUTH => ['southwest','south','southeast'],
			self::WEST => ['northwest','west','southwest'],
			self::EAST => ['northeast','east','southeast'],
		};
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

class Elf {
	public function __construct(
		public Vector2d $location,
		public GridMap $map
	) {}
	
	public function checkNeighbors(array $filter=[]) : array {
		$neighbors = [];
		
		foreach(Vector2dDirection::cases() as $case) {
			$at = clone $this->location;
			$at->translate($case->getVector());
			if(($n = $this->map->getTile($at))) {
				$neighbors[$case->value] = $n;
			}
		}
		
		// Filter by target tile types
		if($filter)
			$neighbors = array_intersect($neighbors, $filter);
		
		return $neighbors;
	}
}

// [TODO] renderFrame method for animation
class GridMap {
	public array $grid = [];
	private array $_extents = [];
	
	private array $_directionOrder = [
		ProposedDirection::NORTH, ProposedDirection::SOUTH, ProposedDirection::WEST, ProposedDirection::EAST
	];
	
	/** @var Elf[] $_elves */
	private array $_elves = [];
	
	public function __construct(array $data) {
		$this->_loadData($data);
	}
	
	private function _loadData(array $data): void {
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
		$this->grid = array_combine(
			array_keys($data[0]),
			array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
		);
	}
	
	function getTile(Vector2d $loc) : ?string {
		// If we check a null tile, expand the map
		if(null == ($tile = $this->grid[$loc->x][$loc->y] ?? null)) {
			$tile = $this->grid[$loc->x][$loc->y] = '.';
			$this->_extents['x0'] = min($this->_extents['x0'], $loc->x);
			$this->_extents['x1'] = max($this->_extents['x1'], $loc->x);
			$this->_extents['y0'] = min($this->_extents['y0'], $loc->y);
			$this->_extents['y1'] = max($this->_extents['y1'], $loc->y);
		}
		
		return $tile;
	}
	
	function getElfBoundingBox() : array {
		$bounds = [PHP_INT_MAX, PHP_INT_MIN, PHP_INT_MAX, PHP_INT_MIN];
		
		foreach($this->grid as $x => $column) {
			$column = array_filter($column, fn($tile) => '#' == $tile);
			
			if($column) {
				$bounds[0] = min($bounds[0], $x);
				$bounds[1] = max($bounds[1], $x);
				$bounds[2] = min($bounds[2], array_key_first($column));
				$bounds[3] = max($bounds[3], array_key_last($column));
			}
		}
		
		return $bounds;
	}
	
	function getRectangle(array $bounds) : array {
		if(4 != count($bounds))
			return [];
		
		$rectangle = [];
		
		for($x=$bounds[0]; $x<=$bounds[1]; $x++) {
			for($y=$bounds[2]; $y<=$bounds[3]; $y++) {
				$rectangle[$x][$y] = $this->grid[$x][$y] ?? '.';
			}
		}
		
		return $rectangle;
	}
	
	/** @return Elf[] */
	function findElves() : array {
		$this->_elves = [];
		
		foreach($this->grid as $x => $column) {
			foreach($column as $y => $tile) {
				if('#' == $tile) {
					$elf = new Elf(new Vector2d($x, $y), $this);
					$this->_elves[] = $elf;
				}
			}
		}
		
		return $this->_elves;
	}
	
	public function proposeLocations() : array {
		$proposals = [];
		
		foreach($this->_elves as $elf) {
			$other_elves = $elf->checkNeighbors(['#']);
			
			// Stand still if no other elves are around
			if(!$other_elves)
				continue;
			
			foreach($this->_directionOrder as $direction) {
				if(!array_intersect_key($other_elves, array_flip($direction->getKeys()))) {
					$next_loc = new Vector2d($elf->location->x, $elf->location->y);
					$next_loc->translate(Vector2dDirection::from($direction->value)->getVector());
					$proposals[$next_loc->toString()][] = $elf;
					break;
				}
			}
		}
		
		// Rotate proposal directions
		$this->_directionOrder[] = array_shift($this->_directionOrder);
		
		return $proposals;
	}
}

$data = explode("\n", file_get_contents("test.txt"));
//$data = explode("\n", file_get_contents("data.txt"));

$map = new GridMap($data);

$map->findElves();

// [TODO] Move this into a game logic class

$rounds = 10;

while($rounds--) {
	$locations = $map->proposeLocations();
	
	foreach($locations as $xy => $elves) {
		//printf("(%s) %d\n", $xy, count($elves));
		
		if(1 == count($elves)) {
			$elf = current($elves); /** @var Elf $elf */
			// Clear the past location
			$map->grid[$elf->location->x][$elf->location->y] = '.';
			// Move to the new location
			//printf("Moving elf %s to %s\n", $elf->location->toString(), $xy);
			$xy = array_map(fn($v) => intval($v), explode(',', $xy));
			$elf->location->set(new Vector2d(...$xy));
			$map->grid[$elf->location->x][$elf->location->y] = '#';
		}
	}
}

//print_r($map->getElfBoundingBox());

$rect = $map->getRectangle($map->getElfBoundingBox());

printf("Part 1: %d\n", count(array_filter(array_merge(...$rect), fn($tile) => $tile == '.')));

// [TODO] Too low: 3486
// [TODO] Too high: 4584