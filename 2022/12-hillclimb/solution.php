<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '128M');

// We want a priority queue with the lowest cost first
class MinPriorityQueue extends SplPriorityQueue {
	function compare(mixed $priority1, mixed $priority2): int {
		return $priority2 <=> $priority1;
	}
}

// Implement the A* algorithm
class HillPathFinder {
	private array $_goal = [];
	private array $_map = [];
	
	public function __construct() {
		// Load the map data from a file
		$this->_loadData();
	}
	
	// Take multiple starts and goals and return the best
	public function shortestPath(array $starts, array $goals) : array {
		if(!$starts || !$goals)
			die("A start and goal are required.\n");
		
		// We don't need to store everything, just the best (shortest)
		$best_path = [];
		
		// For each start + end permutation
		foreach($starts as $i => $start) {
			foreach($goals as $goal) {
				$path = $this->_findShortestPath($start, $goal);
				
				// Print the current status, clearing the screen each time
				if(count($starts) > 1) {
					printf("\e[H\e[JWalking %d/%d possible paths (best: %d)...\n", 
						$i + 1,
						count($starts),
						array_key_last($best_path)
					);
				}
				
				// Is this the new shortest path?
				if($path && (!$best_path || count($path) < count($best_path)))
					$best_path = $path;
			}
		}
		
		return $best_path;
	}
	
	private function _findShortestPath($start, $goal) : array {
		$this->_goal = $goal;
		
		// Keep track of our explored nodes
		$openSet = new MinPriorityQueue();
		$openSet->insert($start, PHP_FLOAT_MAX);
		
		$seen = [];
		$cameFrom = [];
		$cheapestPathFromStart = [implode(',', $start) => 0];
		
		while($openSet->count()) {
			// Explore the next node with the lowest estimated total cost
			$current = $openSet->current();
			$current_key = implode(',', $current);
			
			// If we found the shortest path
			if($current == $this->_goal)
				return $this->_buildPath($cameFrom, $current);
			
			// Remove the explored node
			$openSet->extract();
			unset($seen[implode(',', $current)]);
			
			// Find the cheapest neighbor (edge)
			foreach($this->_getNeighbors(...$current) as $n) {
				$n_key = implode(',', $n);
				
				$costFromStartToN = $cheapestPathFromStart[$current_key] + $this->_calculateEdgeCost($current, $n);
				
				if($costFromStartToN < ($cheapestPathFromStart[$n_key] ?? PHP_FLOAT_MAX)) {
					$cameFrom[$n_key] = $current;
					$cheapestPathFromStart[$n_key] = $costFromStartToN;
					
					if(!array_key_exists($n_key, $seen)) {
						$openSet->insert($n, $costFromStartToN + $this->_estimateCost(...$n));
						$seen[$n_key] = true;
					}
				}
			}
		}
		
		return [];
	}
	
	// Our heuristic is Euclidean distance
	private function _estimateCost(int $x, int $y) : float {
		return sqrt(
			pow($this->_goal[0] - $x, 2) 
			+ pow($this->_goal[1] - $y, 2)
		);
	}
	
	private function _calculateEdgeCost(array $from, array $to) : int {
		$height = $this->_getHeight(...$from);
		$to_height = $this->_getHeight(...$to);
		
		// Ascending/descending costs more than an even slope
		return ($height == $to_height) ? 1 : (abs($to_height - $height)+1);
	}
	
	private function _getHeight(int $x, int $y) : ?int {
		if(null == ($height = $this->_map[$x][$y]['height'] ?? null))
			return null;
		
		return $height;
	}
	
	private function _getNeighbors(int $x, int $y) : array {
		// If we previously visited this node, use the cache
		if(!is_null($this->_map[$x][$y]['neighbors'] ?? null))
			return $this->_map[$x][$y]['neighbors'];
		
		$height = $this->_getHeight($x, $y);
		$vectors = [[0,-1],[-1,0],[1,0],[0,1]];
		$neighbors = [];
		
		// Look N,S,W,E
		foreach($vectors as $dir) {
			$nx = $x + $dir[0];
			$ny = $y + $dir[1];
			
			// skip non-existent edges
			if(null == ($dh = $this->_getHeight($nx, $ny)))
				continue;
			
			// skip edges that require climbing more than +1
			if($dh - $height > 1)
				continue;
			
			$neighbors[] = [$nx, $ny];
		}
		
		$this->_map[$x][$y]['neighbors'] = $neighbors;
		return $neighbors;
	}
	
	private function _buildPath(array $cameFrom, array $current) : array {
		$totalPath = [$current];
		
		// Reverse the path from the goal
		while(null != ($step = ($cameFrom[implode(',', $current)] ?? null))) {
			array_unshift($totalPath, $step);
			$current = $step;
		}
		
		return $totalPath;
	}
	
	private function _loadData() : void {
		// Load rows of columns
		$data = array_map(
			fn($row) => str_split($row),
			//explode("\n", file_get_contents("test.txt"))
			explode("\n", file_get_contents("data.txt"))
		);

		// Flip to columns of rows (X/Y)
		$this->_map = array_map(
			fn($col) => array_column($data, $col),
			array_keys($data[0])
		);
		
		// Add a dictionary to each node
		$this->_map = array_map(
			fn($row) => array_map(
				fn($h) => [
					'label' => $h,
					// Remap the S and E nodes
					'height' => ord(match($h) {
						'S' => 'a',
						'E' => 'z',
						default => $h,
					}),
					'neighbors' => null
				],
				$row
			),
			$this->_map
		);
	}
	
	// Return the coordinates of nodes matching the given height label
	public function findNodesByLabel(string $height, $max_results=PHP_INT_MAX) : array {
		$nodes = [];
		
		// Find nodes with the given label
		foreach($this->_map as $x => $row) {
			$results = array_filter($row, fn($node) => $node['label'] == $height, ARRAY_FILTER_USE_BOTH);
			
			// Return their coordinates
			foreach(array_keys($results) as $y)
				$nodes[] = [$x, $y];
			
			// Stop if we hit our max results
			if(count($nodes) == $max_results)
				break;
		}
		
		return count($nodes) > $max_results ? array_slice($nodes, $max_results) : $nodes;
	}
}

$pathfinder = new HillPathFinder();

$part1 = $pathfinder->shortestPath(
	$pathfinder->findNodesByLabel('S', 1),
	$pathfinder->findNodesByLabel('E', 1)
);

$part2 = $pathfinder->shortestPath(
	$pathfinder->findNodesByLabel('a'),
	$pathfinder->findNodesByLabel('E', 1)
);

echo "Part 1: ", array_key_last($part1), PHP_EOL;
echo "Part 2: ", array_key_last($part2), PHP_EOL;