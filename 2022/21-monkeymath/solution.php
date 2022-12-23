<?php /** @noinspection DuplicatedCode */
/*
 * Jeff Standen <https://phpc.social/@jeff>
 */
declare(strict_types=1);

namespace AoC\Year2022\Day21;

class Monkey {
	public string $name;
	public ?int $number = null;
	public ?string $formula = null;
	public array $watching = [];
	public string $job = '';
	
	public function __construct(string $name, string $job) {
		$this->name = $name;
		if(is_numeric($job)) {
			$this->number = intval($job);
			$this->formula = (string) $job;
		} else {
			list($monkey1, $this->job, $monkey2) = explode(' ', $job);
			$this->watching = array_unique(array_merge($this->watching, [$monkey1, $monkey2]));
		}
	}
	
	public function getWatching() : array {
		return $this->watching;
	}
}

// Recurse from a starting monkey to solve its dependencies
class RiddleSolver {
	public static function solve(Monkey $monkey) : ?int {
		global $monkeys;
		
		// If we've already solved this monkey, return the solution
		if(($number = $monkey->number))
			return $number;
		
		$waiting_for = array_fill_keys($monkey->getWatching(), null);
		//printf("Monkey %s is checking neighbors (%s)...\n", $current->name, implode(',', array_keys($waiting_for)));
		
		// Loop through our dependencies
		foreach(array_keys($waiting_for) as $observed_name) {
			$observed = $monkeys[$observed_name];
			
			// If the dependencies is already solved, use that solution
			if(($number = $observed->number)) {
				$waiting_for[$observed->name] = $number;
				//printf("Monkey %s yells %d...\n", $observed->name, $number);
			} else {
				// Otherwise, recurse into that dependency
				$number = self::solve($observed);
				$waiting_for[$observed->name] = intval($number);
			}
		}
		
		// If we've solved all dependencies, store our solved number
		if(!array_filter($waiting_for, fn($number) => is_null($number))) {
			$monkey->number = intval(match($monkey->job) {
				'+' => reset($waiting_for) + next($waiting_for),
				'-' => reset($waiting_for) - next($waiting_for),
				'*' => reset($waiting_for) * next($waiting_for),
				'/' => reset($waiting_for) / next($waiting_for),
			});
			//printf("Monkey %s eventually yells (%d %s %d = %d)...\n", $current->name, reset($waiting_for), $current->job, next($waiting_for), $current->number);
			return $monkey->number;
		}
		
		return null;
	}
}

// Rather than solving numbers, we'll solve recursive formulas
class FormulaSolver {
	private static function _dfs(Monkey $monkey) : ?string {
		global $monkeys;
		
		// If we already solved this formula, return it and stop
		if(($formula = $monkey->formula))
			return $formula;
		
		$waiting_for = array_fill_keys($monkey->getWatching(), null);

		foreach(array_keys($waiting_for) as $observed_name) {
			$observed = $monkeys[$observed_name];
			
			// If we know the formula for this neighbor, use it
			if(null !== ($formula = $observed->formula)) {
				$waiting_for[$observed->name] = $formula;
				
			// Otherwise, build the formula
			} else {
				$formula = self::_dfs($observed);
				$waiting_for[$observed->name] = $formula;
			}
		}
		
		// If we have all the formulas for children
		if(!array_filter($waiting_for, fn($formula) => is_null($formula))) {
			$a = reset($waiting_for);
			$b = next($waiting_for);
			
			// Then we can build the formula for this parent
			if(is_numeric($a) && is_numeric($b)) {
				// If both parts of the formula are numeric we can solve it
				$monkey->formula = (string) (match($monkey->job) {
					'+' => $a + $b,
					'-' => $a - $b,
					'*' => $a * $b,
					'/' => $a / $b,
				});
			} else {
				// Otherwise we'll mix a formula and a number; e.g. (4 * humn)
				$monkey->formula = sprintf("(%s %s %s)", reset($waiting_for), $monkey->job, next($waiting_for));
			}
			
			// Return this formula to the parent
			return $monkey->formula;
		}
		
		return null;
	}
	
	public static function solve(string $monkey_name) : ?int {
		global $monkeys;
		
		// Override this monkey
		$monkeys[$monkey_name]->number = null;
		$monkeys[$monkey_name]->formula = 'X';
		
		// Recursively solve the formulas for root. One half will be a solved number.
		self::_dfs($monkeys['root']);

		// Index all unsolved formulas by the monkey name
		$formulas = array_column(array_filter($monkeys, fn($monkey) => !is_numeric($monkey->formula)), 'formula', 'name');

		// Sort by longest formulas first so we can condense them
		uasort($formulas, fn($a, $b) => strlen(strval($b)) <=> strlen(strval($a)));

		// Replace the sub-formula in each formula with its neighbors monkey name as a variable.
		// This returns only a single operation per monkey; e.g. (humn / 4)
		while(current($formulas)) {
			$i = key($formulas);
			next($formulas);
			
			if(!current($formulas))
				break;
			
			$j = key($formulas);
			$formulas[$i] = str_replace($formulas[$j], $j, $formulas[$i]);
		}

		// Initialize x to solve for `humn`
		$x = 0;

		// Invert all formulas starting from the number in root's equality.
		foreach($formulas as $step) {
			// If we hit X, we've solved it
			if($step == 'X')
				return $x;
			
			// This will look like (humn + 1234)
			list($a, $op, $b) = explode(' ', trim($step, '()'));
			
			// Invert each formula by operation and replace `x` with that value
			$x = match(true) {
				$op == '=' => (int) $b,
				$op == '+' => is_numeric($a) ? ($x - $a) : ($x - $b),
				$op == '-' => is_numeric($a) ? ($a - $x) : ((int) $b + $x),
				$op == '*' => is_numeric($a) ? ($x / $a) : ($x / $b),
				$op == '/' => is_numeric($a) ? ($a * $x) : ($b * $x),
			};
		}		
		
		return null;
	}
}

// Load the data
$data = array_map(
	fn($line) => explode(': ', $line),
	explode("\n", file_get_contents("data.txt"))
);

// ====================================================
// Part 1
// ====================================================

$monkeys = array_map(fn($properties) => new Monkey(...$properties), $data);
$monkeys = array_combine(array_column($monkeys, 'name'), $monkeys);

// 142707821472432
echo "Part 1: ", RiddleSolver::solve($monkeys['root']), PHP_EOL;

// ====================================================
// Part 2
// ====================================================

// Reinitialize the monkeys because we're changing the job of `humn`
$monkeys = array_map(fn($properties) => new Monkey(...$properties), $data);
$monkeys = array_combine(array_column($monkeys, 'name'), $monkeys);

// Overload the job of `root`
$monkeys['root']->job = '=';

// 3587647562851
echo "Part 2: ", FormulaSolver::solve('humn'), PHP_EOL;