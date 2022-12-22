<?php /** @noinspection DuplicatedCode */
// Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace AoC\Year2022\Day19\Part2;

use Exception;

ini_set('memory_limit', '2G');

// A helper class for working with gathered resources, built robots, or costs
class ResourceAmount {
	public int $ore = 0;
	public int $clay = 0;
	public int $obsidian = 0;
	public int $geode = 0;
	
	// Initialize a resource stack
	public function __construct(int $ore=0, int $clay=0, int $obsidian=0, int $geode=0) {
		$this->ore = $ore;
		$this->clay = $clay;
		$this->obsidian = $obsidian;
		$this->geode = $geode;
	}
	
	// Can this stack afford the given amount
	public function canAfford(ResourceAmount $amount) : bool {
		return $this->ore >= $amount->ore
			&& $this->clay >= $amount->clay
			&& $this->obsidian >= $amount->obsidian
			&& $this->geode >= $amount->geode
			;
	}
	
	// Do these stacks intersect on resource types
	public function atLeastOneOfEach(ResourceAmount $amount) : bool {
		// Check most advanced first
		if(($amount->geode && !$this->geode)
			|| ($amount->obsidian && !$this->obsidian)
			|| ($amount->clay && !$this->clay)
			|| ($amount->ore && !$this->ore))
			return false;
		
		return true;
	}
	
	public function credit(ResourceAmount $amount) : bool {
		$this->ore += $amount->ore;
		$this->clay += $amount->clay;
		$this->obsidian += $amount->obsidian;
		$this->geode += $amount->geode;
		return true;
	}
	
	public function debit(ResourceAmount $amount) : bool {
		if(!$this->canAfford($amount))
			return false;
		
		$this->ore -= $amount->ore;
		$this->clay -= $amount->clay;
		$this->obsidian -= $amount->obsidian;
		$this->geode -= $amount->geode;
		return true;
	}
	
	public function toArray() : array {
		return [$this->ore, $this->clay, $this->obsidian, $this->geode];
	}
}

// A blueprint plan for different robot resource cost variations
class Blueprint {
	public int $id = 0;
	public ResourceAmount $oreRobotCost;
	public ResourceAmount $clayRobotCost;
	public ResourceAmount $obsidianRobotCost;
	public ResourceAmount $geodeRobotCost;
	public int $highestOreCost;
	
	/* @throws Exception */
	public function __construct(string $schematic) {
		$numbers = $this->_getNumbers($schematic);
		
		if(7 != count($numbers))
			throw new Exception(("Invalid blueprint input."));
		
		$this->id = $numbers[0];
		
		$this->oreRobotCost = new ResourceAmount($numbers[1]);
		$this->clayRobotCost = new ResourceAmount($numbers[2]);
		$this->obsidianRobotCost = new ResourceAmount($numbers[3], $numbers[4]);
		$this->geodeRobotCost = new ResourceAmount($numbers[5], 0, $numbers[6]);
		
		$this->highestOreCost = max(
			$this->geodeRobotCost->ore,
			$this->obsidianRobotCost->ore,
			$this->clayRobotCost->ore
		);
	}
	
	// We can just extract numbers per line and ignore the text
	private function _getNumbers(string $string) : array {
		if(!preg_match_all('/(\d+)/', $string, $matches))
			return [];
		
		return array_map(fn($n) => intval($n), $matches[1] ?? []);
	}
}

// Our game state at a given minute
class GameState {
	public Blueprint $blueprint;
	public int $timeRemaining;
	public ResourceAmount $robots;
	public ResourceAmount $resources;
	public array $actionsTaken;
	
	public const ACTION_DO_NOTHING = 0;
	public const ACTION_BUILD_ORE = 1;
	public const ACTION_BUILD_CLAY = 2;
	public const ACTION_BUILD_OBSIDIAN = 3;
	public const ACTION_BUILD_GEODE = 4;
	
	public function __construct(Blueprint $blueprint, $timeRemaining=24) {
		$this->blueprint = $blueprint;
		$this->timeRemaining = $timeRemaining;
		$this->robots = new ResourceAmount(1);
		$this->resources = new ResourceAmount();
		$this->actionsTaken = [];
	}
	
	public function __clone(): void {
		$this->robots = clone $this->robots;
		$this->resources = clone $this->resources;
	}
	
	// This is similar to neighbors in Dijkstra pathfinding. We return future minutes
	// where the next robot build is possible and skip intervening 'do nothing' turns. 
	public function nextPossibleBuilds() : array {
		$possibleBuilds = [];
		
		// If we have the prerequisite robot types, at what point can we build
		// the next geode robot?
		if($this->robots->atLeastOneOfEach($this->blueprint->geodeRobotCost)) {
			
			// Extrapolate our resources at that future minute
			$futureResources = clone $this->resources;
			$time = 0;
			
			// Wait minutes until we can afford it
			while(!$futureResources->canAfford($this->blueprint->geodeRobotCost)) {
				$futureResources->credit($this->robots);
				$time++;
			}
			
			// Return how long we waited and what resources we produced
			if($this->timeRemaining - $time > 0) {
				// Delta (less starting resources)
				$futureResources->debit($this->resources);
				$possibleBuilds[] = [$time, self::ACTION_BUILD_GEODE, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next obsidian robot? Stop building them if we produce more obsidian
		// per turn than a geode robot costs.
		if($this->robots->atLeastOneOfEach($this->blueprint->obsidianRobotCost) 
			&& $this->blueprint->geodeRobotCost->obsidian > $this->robots->obsidian) {
			
			$futureResources = clone $this->resources;
			$time = 0;
			
			while(!$futureResources->canAfford($this->blueprint->obsidianRobotCost)) {
				$futureResources->credit($this->robots);
				$time++;
			}
			
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit($this->resources);
				$possibleBuilds[] = [$time, self::ACTION_BUILD_OBSIDIAN, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next clay robot? Stop building them if we produce more clay
		// per turn than an obsidian robot costs.
		if($this->robots->atLeastOneOfEach($this->blueprint->clayRobotCost) 
			&& $this->blueprint->obsidianRobotCost->clay > $this->robots->clay) {
			
			$futureResources = clone $this->resources;
			$time = 0;
			
			while(!$futureResources->canAfford($this->blueprint->clayRobotCost)) {
				$futureResources->credit($this->robots);
				$time++;
			}
			
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit($this->resources);
				$possibleBuilds[] = [$time, self::ACTION_BUILD_CLAY, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next ore robot? Stop building them if we produce more ore
		// per turn than the MAX cost of a geode/obsidian/clay robot.
		if($this->robots->atLeastOneOfEach($this->blueprint->oreRobotCost) 
			&& $this->blueprint->highestOreCost > $this->robots->ore) {
			
			$futureResources = clone $this->resources;
			$time = 0;
			
			while(!$futureResources->canAfford($this->blueprint->oreRobotCost)) {
				$futureResources->credit($this->robots);
				$time++;
			}
			
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit($this->resources);
				$possibleBuilds[] = [$time, self::ACTION_BUILD_ORE, $futureResources];
			}
		}
		
		return $possibleBuilds;
	}
	
	// Produce a robot and debit the required resources
	public function performAction(int $action) : ResourceAmount {
		$this->actionsTaken[] = $action;
		
		$produced = new ResourceAmount();
		
		if ($action == self::ACTION_BUILD_ORE) {
			$this->resources->debit($this->blueprint->oreRobotCost);
			$produced->ore++;
			
		} elseif ($action == self::ACTION_BUILD_CLAY) {
			$this->resources->debit($this->blueprint->clayRobotCost);
			$produced->clay++;
			
		} elseif ($action == self::ACTION_BUILD_OBSIDIAN) {
			$this->resources->debit($this->blueprint->obsidianRobotCost);
			$produced->obsidian++;
			
		} elseif ($action == self::ACTION_BUILD_GEODE) {
			$this->resources->debit($this->blueprint->geodeRobotCost);
			$produced->geode++;
		}
		
		return $produced;
	}
	
	// Robots produce resources for a minute's turn
	public function produceResources() : void {
		$this->resources->credit($this->robots);
	}
}

class Planner {
	private int $simulationsCount = 0;
	
	/* @throws Exception */
	public function simulate(array $data, int $time_remaining) : array {
		$blueprints = array_map(fn($line) => new Blueprint($line), $data);
		$max_geodes = [];
		
		foreach($blueprints as $blueprint) {
			// Create a new game state for this simulation
			$game_state = new GameState($blueprint, $time_remaining);
			
			// Track the best outcome so far
			$best_outcome = clone $game_state;
			
			// Run the simulation
			$this->_simulateGameRound($game_state, $best_outcome);
			
			$max_geodes[$blueprint->id] = $best_outcome->resources->geode;
			
			printf("FINAL Blueprint %d best game: %s | %s | %s\n",
				$blueprint->id,
				implode(',', $best_outcome->actionsTaken),
				implode(',', $best_outcome->resources->toArray()),
				implode(',', $best_outcome->robots->toArray()),
			);
		}
		
		return $max_geodes;
	}
	
	private function _predictBestCaseGeodes(GameState $state) : int {
		$predicted = clone $state;
		
		// What if we build a geode robot on all future turns
		while($predicted->timeRemaining > 0) {
			$predicted->resources->geode += $predicted->robots->geode++;
			$predicted->timeRemaining--;
		}
		
		return $predicted->resources->geode;
	}
	
	// Advance a game state to the next state
	private function _simulateGameRound(GameState $state, GameState &$best_outcome) : void {
		// If we run out of time, compare to our best result
		if($state->timeRemaining <= 0) {
			$this->simulationsCount++;
			
//			printf("GAME END Blueprint %d: %s | %s | %s\n",
//				$state->blueprint->id,
//				implode(',', $state->actionsTaken),
//				implode(',', $state->resources->toArray()),
//				implode(',', $state->robots->toArray()),
//			);
			
			// We only care about the most cracked geodes
			if($state->resources->geode > $best_outcome->resources->geode) {
				$best_outcome = clone $state;
				
				printf("UPDATE Blueprint %d best game: %s | %s | %s\n",
					$best_outcome->blueprint->id,
					implode(',', $best_outcome->actionsTaken),
					implode(',', $best_outcome->resources->toArray()),
					implode(',', $best_outcome->robots->toArray()),
				);
			}
			
			return;
		}
		
		// Find the minutes of our next possible robot builds
		$next_actions = $state->nextPossibleBuilds();
		
		// If we have no possible moves, credit remaining resource production and end
		if(!$next_actions) {
			while($state->timeRemaining) {
				$state->produceResources();
				$state->actionsTaken[] = $state::ACTION_DO_NOTHING;
				$state->timeRemaining--;
			}
			$this->_simulateGameRound($state, $best_outcome);
			return;
		}
		
		// Skip ahead to each future robot build
		foreach($next_actions as $action) {
			$new_state = clone $state;
			
			// Produce intervening minute resources
			for($t=0; $t < $action[0]; $t++) {
				$new_state->produceResources();
				$new_state->actionsTaken[] = $new_state::ACTION_DO_NOTHING;
				$new_state->timeRemaining--;
			}
			
			// Perform the action but credit a new robot after production
			$produced = $new_state->performAction($action[1]);
			// Produce resources for the turn
			$new_state->produceResources();
			// Build new robots after we produce resources
			$new_state->robots->credit($produced);
			// One more minute down
			$new_state->timeRemaining--;
			
			// If we can't possibly best the best from here, abort
			if(($predicted_geodes = $this->_predictBestCaseGeodes($state)) < $best_outcome->resources->geode) {
				//printf("Aborting a failing strategy (%d < %d) %s\n", $predicted_geodes, $best_outcome->resources->geode, implode(',', $state->actionsTaken));
				return;
			}
			
			// Recurse
			$this->_simulateGameRound($new_state, $best_outcome);
		}
	}
}

try {
//	$data = explode("\n", file_get_contents("test.txt"));
	$data = explode("\n", file_get_contents("data.txt"));
	$planner = new Planner();
	
	$time_limit = 24;
	$blueprint_limit = PHP_INT_MAX;
	$part1_results = $planner->simulate(array_slice($data, 0, $blueprint_limit), $time_limit);
	
	$time_limit = 32;
	$blueprint_limit = 3;
	$part2_results = $planner->simulate(array_slice($data, 0, $blueprint_limit), $time_limit);
	
	printf("Part 1: %d\n",
		array_sum(array_map(fn($id) => $id * $part1_results[$id], array_keys($part1_results)))
	);
	
	printf("Part 2: %d\n",
		array_product($part2_results)
	);
	
} catch (Exception $e) {
	print_r($e);
	die(1);
}