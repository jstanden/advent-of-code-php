<?php /** @noinspection DuplicatedCode */
// Jeff Standen <https://phpc.social/@jeff>
declare(strict_types=1);

namespace AoC\Year2022\Day19\Part1;

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
	
	public function canAfford(int $ore=0, int $clay=0, int $obsidian=0, int $geode=0) : bool {
		return $this->ore >= $ore 
			&& $this->clay >= $clay 
			&& $this->obsidian >= $obsidian 
			&& $this->geode >= $geode
		;
	}
	
	public function credit(int $ore=0, int $clay=0, int $obsidian=0, int $geode=0) : bool {
		$this->ore += $ore;
		$this->clay += $clay;
		$this->obsidian += $obsidian;
		$this->geode += $geode;
		return true;
	}
	
	public function debit(int $ore=0, int $clay=0, int $obsidian=0, int $geode=0) : bool {
		if(!$this->canAfford($ore, $clay, $obsidian, $geode))
			return false;
		
		$this->ore -= $ore;
		$this->clay -= $clay;
		$this->obsidian -= $obsidian;
		$this->geode -= $geode;
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
		if($this->robots->canAfford(1, 0, 1)) {
			$futureResources = clone $this->resources;
			$time = 0;
			while(!$futureResources->canAfford(...$this->blueprint->geodeRobotCost->toArray())) {
				$futureResources->credit(...$this->robots->toArray());
				$time++;
			}
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit(...$this->resources->toArray());
				$possibleBuilds[] = [$time, self::ACTION_BUILD_GEODE, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next obsidian robot? Stop building them if we produce more obsidian
		// per turn than a geode robot costs.
		if($this->robots->canAfford(1, 1) && $this->blueprint->geodeRobotCost->obsidian > $this->robots->obsidian) {
			$futureResources = clone $this->resources;
			$time = 0;
			while(!$futureResources->canAfford(...$this->blueprint->obsidianRobotCost->toArray())) {
				$futureResources->credit(...$this->robots->toArray());
				$time++;
			}
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit(...$this->resources->toArray());
				$possibleBuilds[] = [$time, self::ACTION_BUILD_OBSIDIAN, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next clay robot? Stop building them if we produce more clay
		// per turn than an obsidian robot costs.
		if($this->robots->canAfford(1) && $this->blueprint->obsidianRobotCost->clay > $this->robots->clay) {
			$futureResources = clone $this->resources;
			$time = 0;
			while(!$futureResources->canAfford(...$this->blueprint->clayRobotCost->toArray())) {
				$futureResources->credit(...$this->robots->toArray());
				$time++;
			}
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit(...$this->resources->toArray());
				$possibleBuilds[] = [$time, self::ACTION_BUILD_CLAY, $futureResources];
			}
		}
		
		// If we have the prerequisite robot types, at what point can we build
		// the next ore robot? Stop building them if we produce more ore
		// per turn than the MAX cost of a geode/obsidian/clay robot.
		if($this->robots->canAfford(1) && max($this->blueprint->geodeRobotCost->ore, $this->blueprint->obsidianRobotCost->ore, $this->blueprint->clayRobotCost->ore) > $this->robots->ore) {
			$futureResources = clone $this->resources;
			$time = 0;
			while(!$futureResources->canAfford(...$this->blueprint->oreRobotCost->toArray())) {
				$futureResources->credit(...$this->robots->toArray());
				$time++;
			}
			if($this->timeRemaining - $time > 0) {
				$futureResources->debit(...$this->resources->toArray());
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
			$this->resources->debit(...$this->blueprint->oreRobotCost->toArray());
			$produced->ore++;
			
		} elseif ($action == self::ACTION_BUILD_CLAY) {
			$this->resources->debit(...$this->blueprint->clayRobotCost->toArray());
			$produced->clay++;
			
		} elseif ($action == self::ACTION_BUILD_OBSIDIAN) {
			$this->resources->debit(...$this->blueprint->obsidianRobotCost->toArray());
			$produced->obsidian++;
			
		} elseif ($action == self::ACTION_BUILD_GEODE) {
			$this->resources->debit(...$this->blueprint->geodeRobotCost->toArray());
			$produced->geode++;
		}
		
		return $produced;
	}
	
	// Robots produce resources for a minute's turn
	public function produceResources() : void {
		$this->resources->credit(...$this->robots->toArray());
	}
}

class Planner {
	private int $simulationsCount = 0;
	
	/* @throws Exception */
	public function simulate(array $data, int $time_remaining) : array {
		$blueprints = array_map(fn($line) => new Blueprint($line), $data);
		$max_geodes = [];
		
		foreach($blueprints as $blueprint) {
			$game_state = new GameState($blueprint, $time_remaining);
			$best_outcome = clone $game_state;
			$this->_simulateGameRound($game_state, $best_outcome);
			
			$max_geodes[$blueprint->id] = $best_outcome->resources->geode;
			
			printf("Blueprint %d best game: %s | %s | %s\n",
				$blueprint->id,
				implode(',', $best_outcome->actionsTaken),
				implode(',', $best_outcome->resources->toArray()),
				implode(',', $best_outcome->robots->toArray()),
			);
		}
		
		return $max_geodes;
	}
	
	// Advance a game state to the next state
	private function _simulateGameRound(GameState $state, GameState &$best_outcome) : void {
		// If we run out of time, compare to our best result
		if($state->timeRemaining <= 0) {
			$this->simulationsCount++;
			
			// We only care about the most cracked geodes
			if($state->resources->geode > $best_outcome->resources->geode)
				$best_outcome = clone $state;
			
			return;
		}
		
		// Find the minutes of our next possible robot builds
		$next_actions = $state->nextPossibleBuilds();
		
		// If we have no possible moves, credit remaining resource production and end
		if(empty($next_actions)) {
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
			
			// Build the robot and finish this minute's turn
			$produced = $new_state->performAction($action[1]);
			$new_state->produceResources();
			$new_state->robots->credit(...$produced->toArray());
			$new_state->timeRemaining--;
			
			// Recurse
			$this->_simulateGameRound($new_state, $best_outcome);
		}
	}
}

try {
//	$data = explode("\n", file_get_contents("test.txt"));
	$data = explode("\n", file_get_contents("data.txt"));
	
	$planner = new Planner();
	$blueprint_max_geodes = $planner->simulate($data, 24);
	
	printf("Part 1: %d\n", 
		array_sum(array_map(fn($id) => $id * $blueprint_max_geodes[$id], array_keys($blueprint_max_geodes)))
	);
	
} catch (Exception $e) {
	print_r($e);
	die(1);
}