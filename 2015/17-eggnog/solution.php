<?php
$containers = explode("\n", file_get_contents('./data/input.txt'));

class Combination {
	function __construct(
		public array $containers = []
	) {}
}

$combinations = [];
$least = PHP_INT_MAX;

$queue = new SplStack();

foreach($containers as $index => $container) {
	$queue->push(new Combination([$index => $container]));
}

// DFS (slow)
while(!$queue->isEmpty()) {
	$current = $queue->pop(); /** @var Combination $current */
	
	$sum = array_sum($current->containers);
	
	// Goal!
	if(150 == $sum) {
		ksort($current->containers);
		$least = min($least, count($current->containers));
		$combinations[implode(',', array_keys($current->containers))] = implode(',', $current->containers);
		continue;
		
	// Impossible from here
	} else if($sum > 150) {
		continue;
	}
	
	foreach(array_diff_key($containers, $current->containers) as $next_index => $next_container) {
		$combo = clone $current;
		$combo->containers[$next_index] = $next_container;
		$queue->push($combo);
	}
}

printf("Part 1: %d\n", count($combinations)); // 654

$part2 = count(array_filter($combinations, fn($item) => substr_count($item,',')+1 == $least));

printf("Part 2: %d\n", $part2); // 57