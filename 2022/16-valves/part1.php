<?php /** @noinspection DuplicatedCode */
// Jeff Standen <https://phpc.social/@jeff>
// Goal: Release the most valve pressure possible within 30 mins

// Implement the A* algorithm
class ValvePathFinder {
	private array $_valves = [];
	
	public function __construct() {
		$this->_loadData();
	}
	
	// Dijkstra
	public function findShortestPath($start, $goal) : array {
		// Keep track of our explored nodes
		$openSet = new SplQueue();
		$openSet->enqueue($start);
		
		$seen = [$start];
		$cameFrom = [];
		$lowestCostPathFromStart = [$start => 0];
		
		while(!$openSet->isEmpty()) {
			// Explore the next node with the highest estimated total cost
			$current = $openSet->dequeue();
			
			// If we opened every valve and have time left
			if($current == $goal) {
				return $this->_buildPath($cameFrom, $current);
			}
			
			// Find the highest cost neighbor (edge)
			foreach($this->_getTunnels($current) as $tunnel) {
				$costFromStartToN = $lowestCostPathFromStart[$current] + 1;
				
				if($costFromStartToN < ($lowestCostPathFromStart[$tunnel] ?? PHP_FLOAT_MAX)) {
					$cameFrom[$tunnel] = $current;
					$lowestCostPathFromStart[$tunnel] = $costFromStartToN;
					
					if(!array_key_exists($tunnel, $seen)) {
						$openSet->push($tunnel);
						$seen[$tunnel] = true;
					}
				}
			}
		}
		
		return [];
	}
	
	private function _getTunnels(string $valve) : array {
		return $this->_valves[$valve]['tunnels'] ?? [];
	}
	
	private function _buildPath(array $cameFrom, string $current) : array {
		$totalPath = [$current];
		
		// Reverse the path from the goal
		while(null != ($step = ($cameFrom[$current] ?? null))) {
			array_unshift($totalPath, $step);
			$current = $step;
		}
		
		return $totalPath;
	}
	
//	public function getValves() : array {
//		return $this->_valves;
//	}
	
	public function scorePath($path, $t) : int {
		$score = 0;
		
		array_unshift($path, 'AA');

		for($i=0; array_key_exists($i+1, $path); $i++) {
			// Cost: Travel time (1 min per room)
			$t -= count($this->_valves[$path[$i]]['distances'][$path[$i+1]]);
			// Cost: Opening the valve (1 min)
			$t--;
			// Reward: The cumulative pressure relieved until end
			$score += $this->_valves[$path[$i+1]]['rate'] * $t;
		}
		
		return $score;
	}

	public function findOptimalValvePath(string $valve, int $t) {
		$best_score = PHP_INT_MIN;
		$best_path = [];
		
		$this->_dfsOptimalValvePath($valve, $t, [], $best_score, $best_path);
		
		return [$best_score, $best_path];
	}
	
	private function _dfsOptimalValvePath(string $valve, int $t, array $visited, &$best_score, &$best_path) {
		if($t <= 0)
			return;
		
		foreach($this->_valves[$valve]['distances'] as $next_valve => $next_path) {
			if(!$this->_valves[$next_valve]['rate'])
				continue;
			
			if(array_key_exists($next_valve, $visited))
				continue;
			
			if($t - count($next_path) <= 0)
				continue;
			
			$this->_dfsOptimalValvePath(
				$next_valve, 
				$t - count($next_path),
				array_merge($visited, [$next_valve=>true]),
				$best_score,
				$best_path
			);
		}
		
		$score = $this->scorePath(array_keys($visited), 30);
		
		if($score > $best_score) {
			$best_score = $score;
			$best_path = array_keys($visited);
		}
	}
	
	private function _loadData() : void {
		// Parse and build the valve data
		$valves = array_map(
			function($line) {
				$valve = [
					'open' => 0,
//					'actions' => [],
				];
				sscanf($line, "Valve %s has flow rate=%d; %[tunnels* leads* to valves*] %[^$]",
					$valve['key'],
					$valve['rate'],
					$null,
					$valve['tunnels']
				);
				
				$valve['tunnels'] = explode(', ', $valve['tunnels'] ?? '');
				
				return $valve;
			},
//			explode("\n", file_get_contents('test.txt'))
			explode("\n", file_get_contents('data.txt'))
		);

		// Re-key the array with valve names
		$this->_valves = array_combine(
			array_column($valves, 'key'),
			$valves
		);
		
		// Cache the shortest paths from all valves to each other
		foreach($this->_valves as $from_k => $from_valve) {
			$this->_valves[$from_k]['distances'] = [];
			foreach ($this->_valves as $to_k => $to_valve) {
				// Ignore self
				if($from_k == $to_k)
					continue;
				
				// Only working valves
				if(!$this->_valves[$to_k]['rate'])
					continue;
				
				$this->_valves[$from_k]['distances'][$to_k] = array_slice($this->findShortestPath($from_k, $to_k), 1);
			}
		}
	}
}

$pathfinder = new ValvePathFinder();

//$valves = $pathfinder->getValves();

$best = $pathfinder->findOptimalValvePath('AA', 30);

print_r($best);
