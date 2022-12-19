<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection DuplicatedCode */
// Jeff Standen <https://phpc.social/@jeff>
// Goal: Release the most valve pressure possible as a pair within 26 mins

ini_set('memory_limit', '2G');

// Reuse
function generateCombinations(array $combination, int $length, array $values, array &$combinations) : void {
	if(count($combination) == $length) {
		$combinations[] = $combination;
	} else {
		foreach($values as $value) {
			generateCombinations(array_merge($combination, [$value]), $length, $values, $combinations);
		}
	}
}

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
	
	public function scorePath($path, $t) : int {
		$score = 0;
		
		array_unshift($path, 'AA');
		
		for($i=0; array_key_exists($i+1, $path); $i++) {
			// Cost: Travel time (1 min per room)
			$t -= count($this->_valves[$path[$i]]['distances'][$path[$i+1]]);
			// Cost: Opening the valve (1 min)
			$t--;
			if($t <= 0) break;
			// Reward: The cumulative pressure relieved until end
			$score += $this->_valves[$path[$i+1]]['rate'] * $t;
		}
		
		return $score;
	}
	
	public function findOptimalValvePathPair(string $valve, int $t) : array {
		$path_scores = [];
		
		$this->_dfsOptimalValvePathPair($valve, $t, [], $path_scores);
		
		// Score every possible path
		$path_scores = array_map(fn($path_score) => [$path_score[0], $this->scorePath($path_score[0], 26)], $path_scores);
		
		// Sort by the highest scores
		usort($path_scores, fn($a,$b) => $b[1] <=> $a[1]);
		
		// Find the best non-overlapping paths
		
		$best_pair = [];
		$best_score = 0;
		
		// [TODO] This could be much more efficient
		foreach($path_scores as $path_score) {
			$mates = array_filter($path_scores, fn($candidate) => count($candidate[0]) == count(array_diff($candidate[0], $path_score[0])));
			
			if(!$mates)
				continue;
			
			$best_mate = current($mates);
			
			if($path_score[1] + $best_mate[1] > $best_score) {
				$best_score = $path_score[1] + $best_mate[1];
				$best_pair = [$path_score[0], $best_mate[0]];
				printf("BEST: %s + %s = %d\n", implode(',',$path_score[0]), implode(',',$best_mate[0]), $path_score[1] + $best_mate[1]);
			}
		}
		
		return [$best_pair, $best_score];
	}
	
	private function _dfsOptimalValvePathPair(string $valve, int $t, array $visited, array &$path_scores) : void {
		if($t <= 0)
			return;
		
		foreach($this->_valves[$valve]['distances'] as $next_valve => $next_path) {
			if(!$this->_valves[$next_valve]['rate'])
				continue;
			
			if(array_key_exists($next_valve, $visited))
				continue;
			
			if($t - count($next_path) < 0)
				continue;
			
			$this->_dfsOptimalValvePathPair(
				$next_valve, 
				$t - count($next_path),
				array_merge($visited, [$next_valve=>true]),
				$path_scores,
			);
		}
		
		$path_scores[] = [array_keys($visited)];
	}
	
	private function _loadData() : void {
		// Parse and build the valve data
		$valves = array_map(
			function($line) {
				$valve = [
					'open' => 0,
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
$best_pair = $pathfinder->findOptimalValvePathPair('AA', 26);
printf("Part 2: %d (%s + %s)\n", $best_pair[1], implode(',', $best_pair[0][0]), implode(',', $best_pair[0][1]));

// 2316 (target)