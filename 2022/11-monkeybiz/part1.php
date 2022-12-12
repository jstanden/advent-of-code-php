<?php
function playKeepAway(int $max_rounds) : int{
	$monkeys = getMonkeys();
	$round = 0;
	
	while(++$round <= $max_rounds) {
		foreach(array_keys($monkeys) as $turn) {
			while($item = array_shift($monkeys[$turn]['items'])) {
				// Monkey inspects item
				$worry = $monkeys[$turn]['operation']($item);
				$monkeys[$turn]['inspections']++;
				
				// We're relieved that the item is not damaged by inspection
				$worry = floor($worry / 3);
				
				// Monkey tests our reaction to determine target
				$target = $monkeys[$turn]['test']($worry);
				
				// Monkey tosses the revalued item to the target
				$monkeys[$target]['items'][] = $worry;
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

function getMonkeys() : array {
	return [
		[ // 0
			'items' => [99, 67, 92, 61, 83, 64, 98],
			'operation' => fn($old) => $old * 17,
			'test' => fn($worry) => 0 == $worry % 3 ? 4 : 2,
			'inspections' => 0,
		],
		[ // 1
			'items' => [78, 74, 88, 89, 50],
			'operation' => fn($old) => $old * 11,
			'test' => fn($worry) => 0 == $worry % 5 ? 3 : 5,
			'inspections' => 0,
		],
		[ // 2
			'items' => [98, 91],
			'operation' => fn($old) => $old + 4,
			'test' => fn($worry) => 0 == $worry % 2 ? 6 : 4,
			'inspections' => 0,
		],
		[ // 3
			'items' => [59, 72, 94, 91, 79, 88, 94, 51],
			'operation' => fn($old) => $old * $old,
			'test' => fn($worry) => 0 == $worry % 13 ? 0 : 5,
			'inspections' => 0,
		],
		[ // 4
			'items' => [95, 72, 78],
			'operation' => fn($old) => $old + 7,
			'test' => fn($worry) => 0 == $worry % 11 ? 7 : 6,
			'inspections' => 0,
		],
		[ // 5
			'items' => [76],
			'operation' => fn($old) => $old + 8,
			'test' => fn($worry) => 0 == $worry % 17 ? 0 : 2,
			'inspections' => 0,
		],
		[ // 6
			'items' => [69, 60, 53, 89, 71, 88],
			'operation' => fn($old) => $old + 5,
			'test' => fn($worry) => 0 == $worry % 19 ? 7 : 1,
			'inspections' => 0,
		],
		[ // 7
			'items' => [72, 54, 63, 80],
			'operation' => fn($old) => $old + 3,
			'test' => fn($worry) => 0 == $worry % 7 ? 1 : 3,
			'inspections' => 0,
		],
	];
}

echo 'Part 1: ', playKeepAway(20), PHP_EOL;