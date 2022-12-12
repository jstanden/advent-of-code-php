<?php
ini_set('memory_limit', '384M');

function playKeepAway(int $max_rounds) : int{
	$monkeys = Monkeys::init();
	$round = 0;
	
	while(++$round <= $max_rounds) {
		foreach(array_keys($monkeys) as $turn) {
			while($item = array_shift($monkeys[$turn]['items'])) {
				// Monkey inspects item
				$monkeys[$turn]['operation']($item);
				$monkeys[$turn]['inspections']++;
				
				// Monkey tests our reaction to determine target
				$target = $monkeys[$turn]['test']($item);
				
				// Monkey tosses the revalued item to the target
				$monkeys[$target]['items'][] = $item;
			}
		}
	}
	
	// Sort monkeys by inspections in descending order
	uasort($monkeys, fn($a, $b) => $b['inspections'] <=> $a['inspections']);
	
	// Calculate monkey business
	return array_product(
		array_column(array_slice($monkeys,0,2), 'inspections')
	);
}

class Monkeys {
	static function init() : array {
		return [
			[ // 0
				'items' => array_map(fn($n) => new Item($n), [99, 67, 92, 61, 83, 64, 98]),
				'operation' => fn(Item $item) => $item->enqueueOperation('*', 17),
				'test' => fn(Item $item) => 0 == $item->mod(3) ? 4 : 2,
				'inspections' => 0,
			],
			[ // 1
				'items' => array_map(fn($n) => new Item($n), [78, 74, 88, 89, 50]),
				'operation' => fn(Item $item) => $item->enqueueOperation('*', 11),
				'test' => fn(Item $item) => 0 == $item->mod(5) ? 3 : 5,
				'inspections' => 0,
			],
			[ // 2
				'items' => array_map(fn($n) => new Item($n), [98, 91]),
				'operation' => fn(Item $item) => $item->enqueueOperation('+', 4),
				'test' => fn(Item $item) => 0 == $item->mod(2) ? 6 : 4,
				'inspections' => 0,
			],
			[ // 3
				'items' => array_map(fn($n) => new Item($n), [59, 72, 94, 91, 79, 88, 94, 51]),
				'operation' => fn(Item $item) => $item->enqueueOperation('^', 2),
				'test' => fn(Item $item) => 0 == $item->mod(13) ? 0 : 5,
				'inspections' => 0,
			],
			[ // 4
				'items' => array_map(fn($n) => new Item($n), [95, 72, 78]),
				'operation' => fn(Item $item) => $item->enqueueOperation('+', 7),
				'test' => fn(Item $item) => 0 == $item->mod(11) ? 7 : 6,
				'inspections' => 0,
			],
			[ // 5
				'items' => array_map(fn($n) => new Item($n), [76]),
				'operation' => fn(Item $item) => $item->enqueueOperation('+', 8),
				'test' => fn(Item $item) => 0 == $item->mod(17) ? 0 : 2,
				'inspections' => 0,
			],
			[ // 6
				'items' => array_map(fn($n) => new Item($n), [69, 60, 53, 89, 71, 88]),
				'operation' => fn(Item $item) => $item->enqueueOperation('+', 5),
				'test' => fn(Item $item) => 0 == $item->mod(19) ? 7 : 1,
				'inspections' => 0,
			],
			[ // 7
				'items' => array_map(fn($n) => new Item($n), [72, 54, 63, 80]),
				'operation' => fn(Item $item) => $item->enqueueOperation('+', 3),
				'test' => fn(Item $item) => 0 == $item->mod(7) ? 1 : 3,
				'inspections' => 0,
			],
		];
	}
}

class Item {
	// Keep track of the operation history so that we can calculate new modulo
	private array $operations = [];
	// Cache the last result by modulo
	private array $_mod_cache = [];
	// Cache the last operation index by modulo
	private array $_mod_index = [];
	
	// Treat our initial number as the first operation
	public function __construct(int $initial) {
		$this->enqueueOperation('', $initial);
	}
	
	/**
	 * We can efficiently calculate a modulo on a huge number by instead
	 * performing the modulo on an equivalent set of smaller expressions.
	 * 
	 * Example: (a*b) MOD n == (a MOD n)*(b MOD n) MOD n
	 * 
	 * Where `a*b` may overflow, but `a` or `b` independently do not.
	 * Hat tip: https://stackoverflow.com/a/278468/321872
	 */
	public function mod(int $mod) : int {
		$result = $this->_mod_cache[$mod] ?? 0;
		$start_at = ($this->_mod_index[$mod] ?? -1) + 1;
		$new_operations = array_slice($this->operations, $start_at, null, true);
		
		// Reuse our last result and only calculate new operations
		foreach($new_operations as $index => $operation) {
			// Calculate the modulo of this integer
			$n = $operation[1] % $mod;
			
			$result = match($operation[0]) {
				// If we're squaring, just square the previous modulo result instead
				'^' => $result * $result,
				'*' => $result * $n,
				'+' => $result + $n,
				// If this is our initial operation
				default => $n
			};
			
			// Now modulo that result
			$result = $result % $mod;
			
			// Save work by remembering up our last result for the same modulo
			$this->_mod_index[$mod] = $index;
			$this->_mod_cache[$mod] = $result;
		}
		
		return $result;
	}
	
	public function enqueueOperation($operator, $number) : void {
		$this->operations[] = [$operator, $number];
	}
}

echo 'Part 2: ', playKeepAway(10_000), PHP_EOL;