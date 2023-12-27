<?php // @jeff@phpc.social
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

declare(strict_types=1);
namespace jstanden\AoC\Library\Grid2d;

use jstanden\AoC\Library\Collections\MinPriorityQueue;

class GridMap2d
{
    public array $grid = [];
    public array $extents = [];

    public function __construct(array $data)
    {
        $this->_loadData($data);
    }

    private function _loadData(array $data): void
    {
        // Store the grid extents
        $this->extents = [
            'x0' => 0,
            'x1' => array_reduce($data, fn($carry, $row) => max($carry, strlen($row) - 1), 0),
            'y0' => 0,
            'y1' => max(0, count($data) - 1),
        ];

        // Convert row strings to character arrays and pad all to the widest
        $data = array_map(fn($row) => str_split($row), $data);

        // Flip to an X,Y grid
		if($data[0] ?? []) {
			$this->grid = array_combine(
				array_keys($data[0]),
				array_values(array_map(fn($col) => array_column($data, $col), array_keys($data[0])))
			);
		}
    }

    function euclideanDistance(Vector2d $a, Vector2d $b): float
    {
        return sqrt(
            pow($b->x - $a->x, 2)
            + pow($b->y - $a->y, 2)
        );
    }
	
	function manhattanDistance(Vector2d $a, Vector2d $b) : float
	{
		return abs($a->x - $b->x) + abs($a->y - $b->y);
	}

	function setTile(Vector2d $v, string $tile) : bool
	{
		if(($this->grid[$v->x] ?? false) && ($this->grid[$v->x][$v->y] ?? false)) {
			$this->grid[$v->x][$v->y] = $tile;
			return true;
		}
		return false;
	}

    function getTile(Vector2d $loc): ?string
    {
        return $this->grid[$loc->x][$loc->y] ?? null;
    }

    function getRow(int $y): array
    {
        return array_column($this->grid, $y) ?? [];
    }

    function getRows(): array
    {
        return array_map(fn($y) => $this->getRow($y), range(0, $this->extents['y1']));
    }
	
	public function insertRow(int $at, array $row) : void
	{
		foreach($this->getColumns() as $x => $col) {
			array_splice($this->grid[$x], $at, 0, [$row[$x]]);
		}
		
		$this->extents['y1'] = array_key_last($this->grid[0]);
	}
	
    function getColumn(int $x): array
    {
        return $this->grid[$x] ?? [];
    }

    function getColumns(): array
    {
        return array_map(fn($x) => $this->getColumn($x), range(0, $this->extents['x1']));
    }

	public function insertColumn(int $at, array $col) : void
	{
		array_splice($this->grid, $at, 0, [$col]);
		$this->extents['x1'] = array_key_last($this->grid);
	}
	
    public function getFourNeighbors(Vector2d $vector) : array
    {
		return [
			Vector2dDirection::NORTH->value => Vector2d::add($vector, Vector2dDirection::NORTH->getVector()),
			Vector2dDirection::WEST->value => Vector2d::add($vector, Vector2dDirection::WEST->getVector()),
			Vector2dDirection::EAST->value => Vector2d::add($vector, Vector2dDirection::EAST->getVector()),
			Vector2dDirection::SOUTH->value => Vector2d::add($vector, Vector2dDirection::SOUTH->getVector()),
		];
    }

	/**
	 * @return Entity2d[]
	 */
    public function getFourNeighborTiles(Vector2d $vector, array $excluding=['']) : array
    {
        return array_filter(array_map(
            fn($v) => new Entity2d($this->getTile($v) ?? '', $v),
			self::getFourNeighbors($vector),
        ), fn($e) => !in_array($e->name, $excluding));
    }

    public function getEightNeighbors(Vector2d $vector) : array
    {
        return array_map(
            fn($direction) => (Vector2d::add($vector, $direction->getVector())),
            Vector2dDirection::cases()
        );
    }

	public function findTile(string $tile) : ?Vector2d
	{
		foreach($this->getRows() as $y => $row) {
			if(false !== ($x = array_search($tile, $row)))
				return new Vector2d($x, $y);
		}

		return null;
	}
	
	/** @return Entity2d[] */
	public function findTiles(array $tiles) : array
	{
		$results = [];
		
		foreach($this->getRows() as $y => $row) {
			foreach(array_intersect($row, $tiles) as $x => $hit) {
				$v = new Vector2d($x, $y);
				$results[] = new Entity2d(sprintf("%s (%s)", $hit, $v), $v);
			}
		}
		
		return $results;
	}
	
	public function fill(?Bounds2d $bounds=null, ?callable $callback=null) : void
	{
		if(is_null($bounds))
			$bounds = new Bounds2d(new Vector2d(0,0),$this->extents['x1'],$this->extents['y1']);
		
		$v = clone $bounds->origin;
		for($y=$bounds->origin->y;$y<=$bounds->origin->y+$bounds->height;$y++) {
			for($x=$bounds->origin->x;$x<=$bounds->origin->x+$bounds->width;$x++) {
				$tile = $this->getTile($v->set($x, $y)) ?? '';
				if($callback) {
					$callback($v, $tile);
				}
			}
		}
	}

	public function print(?Bounds2d $bounds=null, ?callable $renderer=null) : void
	{
		if(is_null($bounds))
			$bounds = new Bounds2d(new Vector2d(0,0),$this->extents['x1'],$this->extents['y1']);

		$v = clone $bounds->origin;
		for($y=$bounds->origin->y;$y<=$bounds->origin->y+$bounds->height;$y++) {
			for($x=$bounds->origin->x;$x<=$bounds->origin->x+$bounds->width;$x++) {
				$tile = $this->getTile($v->set($x, $y));
				if($renderer) {
					$renderer($v, $tile);
				} else {
					echo $tile;
				}
			}
			echo PHP_EOL;
		}
	}

	public function moveEntity(Entity2d $entity, Vector2d $to, string $replace_with='.')
	{
		$tile = $this->getTile($entity->origin);
		$this->setTile($entity->origin, $replace_with);
		$entity->origin->set($to->x, $to->y);
		$this->setTile($to, $tile);
	}

	private function clamp(Vector2d $v) : Vector2d
	{
		$new_v = clone $v;
		$new_v->x = min(max($new_v->x, $this->extents['x0']), $this->extents['x1']);
		$new_v->y = min(max($new_v->y, $this->extents['y0']), $this->extents['y1']);
		return $new_v;
	}
	
	// A* -- Dijkstra is just h=0
	public function shortestPath(Vector2d $start, Vector2d $goal, ?callable $neighbors=null, ?callable $cost=null, ?callable $h=null) : array
	{
		$cameFrom = [];
		$distFromStart = [];
		$visited = [];
		
		$queue = new MinPriorityQueue();
		$queue->insert($start, PHP_INT_MIN);
		$distFromStart[(string)$start] = 0;
		
		if(is_null($neighbors))
			$neighbors = fn(Vector2d $at, GridMap2d $map) => $map->getFourNeighbors($at);
		
		if(is_null($cost))
			$cost = fn(Vector2d $at, Vector2d $to, GridMap2d $map) => 1;
		
		if(is_null($h))
			$h = fn(Vector2d $at, GridMap2d $map) => 0;
		
		while(!$queue->isEmpty()) {
			$at = $queue->extract(); /** @var $at Vector2d */
			
			if($at->equals($goal)) {
				$parent = $at;
				$path = [$parent];
				
				while(($parent = $cameFrom[(string)$parent] ?? null))
					array_unshift($path, $parent);
				
				return [$distFromStart[(string)$at], $path];
			}
			
			if(array_key_exists((string)$at, $visited))
				continue;
			
			$visited[(string)$at] = true;
			
			foreach($neighbors($at, $this) as $n) {
				$newDist = $distFromStart[(string)$at] + $cost($at, $n, $this) + $h($n, $this);
				
				if($newDist < ($distFromStart[(string)$n] ?? PHP_INT_MAX)) {
					$distFromStart[(string)$n] = $newDist;
					$cameFrom[(string)$n] = $at;
					$queue->insert($n, $newDist);
				}
			}
		}
		
		return [];
	}
}