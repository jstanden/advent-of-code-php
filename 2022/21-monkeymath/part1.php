<?php /** @noinspection DuplicatedCode */
/*
 * Jeff Standen <https://phpc.social/@jeff>
 */
declare(strict_types=1);

namespace AoC\Year2022\Day21\Part1;

use SplQueue;

class Monkey {
	public string $name;
	public string $catchphrase;
	public ?int $number = null;
	public array $watching = [];
	public string $job = '';
	
	public function __construct(string $name, string $job) {
		$this->name = $name;
		$this->catchphrase = $job;
		if(is_numeric($job)) {
			$this->number = intval($job);
		} else {
			list($monkey1, $this->job, $monkey2) = explode(' ', $job);
			$this->watching = array_unique(array_merge($this->watching, [$monkey1, $monkey2]));
		}
	}
	
	public function getNumber() : ?int {
		return $this->number;
	}
	
	public function getWatching() : array {
		return $this->watching;
	}
}

class RiddleSolver {
	static function dfs(Monkey $monkey) : ?int {
		return static::_dfs($monkey);
	}
	
	private static function _dfs(Monkey $monkey) : ?int {
		global $monkeys;
		
		if(($number = $monkey->getNumber()))
			return $number;
		
		$queue = new SplQueue();
		$queue->push($monkey);
		
		while(!$queue->isEmpty()) {
			$current = $queue->pop(); /* @var $current Monkey */
			$waiting_for = array_fill_keys($current->getWatching(), null);
			printf("Monkey %s is checking neighbors (%s)...\n", $current->name, implode(',', array_keys($waiting_for)));
			
			foreach(array_keys($waiting_for) as $observed_name) {
				$observed = $monkeys[$observed_name];
				
				if(($number = $observed->getNumber())) {
					$waiting_for[$observed->name] = $number;
					printf("Monkey %s yells %d...\n", $observed->name, $number);
				} else {
					$number = self::dfs($observed);
					$waiting_for[$observed->name] = intval($number);
				}
			}
			
			// If we're not waiting on any numbers
			if(!array_filter($waiting_for, fn($number) => is_null($number))) {
				$current->number = intval(match($current->job) {
					'+' => reset($waiting_for) + next($waiting_for),
					'-' => reset($waiting_for) - next($waiting_for),
					'*' => reset($waiting_for) * next($waiting_for),
					'/' => reset($waiting_for) / next($waiting_for),
				});
				printf("Monkey %s eventually yells (%d %s %d = %d)...\n", $current->name, reset($waiting_for), $current->job, next($waiting_for), $current->number);
				return $current->number;
				
			} else {
				$queue->push($current);
			}
		}
		
		return null;
	}
}

$data = array_map(
	fn($line) => explode(': ', $line),
	explode("\n", file_get_contents("data.txt"))
//	explode("\n", file_get_contents("test.txt"))
);

$monkeys = array_map(fn($properties) => new Monkey(...$properties), $data);
$monkeys = array_combine(array_column($monkeys, 'name'), $monkeys);

echo "Part 1: ", RiddleSolver::dfs($monkeys['root']), PHP_EOL;

//print_r($monkeys);