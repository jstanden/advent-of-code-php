<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day5;

//$data = explode("\n", file_get_contents('example.txt'));
$data = explode("\n", file_get_contents('../../data/2023/05/data.txt'));
$seeds = $maps = [];

// Load and parse the mapping data
while(false !== ($line = current($data))) {
	if(preg_match('/^([a-z\- ]+?)( map)?: ?(.*)$/', $line, $matches)) {
		$map_name = $matches[1];
		
		// If map data begins with seeds, they're on the same line
		if('seeds' == $map_name) {
			$seeds = array_map('intval', explode(' ', $matches[3]));
			next($data);
			
		// Otherwise, we have sets of ranges until a blank line
		} else {
			$rows = [];
			
			// For each line
			while($line = next($data)) {
				// Split ranges on spaces as (dest start, src start, length)
				$row = array_map('intval', explode(' ', $line));
				// Store the ranges as (from, to, delta) for simpler calculations
				$row = [$row[1], $row[1]+$row[2]-1, -($row[1]-$row[0])];
				$rows[] = $row;
			}
			
			// Sort ranges by destination ascending (we scan left to right)
			usort($rows, fn($a,$b) => $a[0] <=> $b[0]);
			
			$maps[$map_name] = $rows;
		}
	}
	next($data);
}

// Return the destination value for a given map and src value
function mapValue(int $value, string $from, string $to) : int {
	global $maps;
	foreach($maps[sprintf("%s-to-%s", $from, $to)] as $map) {
		// If this source range contains our value, return +delta
		if($value >= $map[0] && $value <= $map[1])
			return $value + $map[2];
	};
	// If no match, keep the same value
	return $value;
}

function getDestinationRangesBySourceRange(array $range, string $from, string $to) : array {
	global $maps;
	$outcomes = [];
	
	while($range) {
		foreach($maps[sprintf("%s-to-%s", $from, $to)] as $map) {
			// Range before target
			if($range[1] < $map[0]) {
				$outcomes[] = [$range[0], $range[1], 0];
				$range = [];
				
			// Range within target
			} else if($range[0] >= $map[0] && $range[1] <= $map[1]) {
				$outcomes[] = [$range[0], $range[1], $map[2]];
				$range = [];
				
			// Range overlaps target left
			} else if($range[0] < $map[0] && $range[1] > $map[0]) {
				$outcomes[] = [$range[0], $map[0]-1, 0];
				$range = [$map[0], $range[1]];
				
			// Range overlaps target right
			} else if($range[0] >= $map[0] && $range[0] <= $map[1] && $range[1] > $map[1]) {
				$outcomes[] = [$range[0], $map[1], $map[2]];
				$range = [$map[1]+1, $range[1]];
			}
			
			if(!$range)
				break;
		}
		
		if($range) {
			$outcomes[] = [$range[0], $range[1], 0];
			$range = [];
		}
	}
	
	return $outcomes;
}

// Part 1: 535088217

echo "Part 1: " . min(array_map(function(int $seed) {
	$soil = mapValue((int)$seed, 'seed', 'soil');
	$fertilizer = mapValue($soil, 'soil', 'fertilizer');
	$water = mapValue($fertilizer, 'fertilizer', 'water');
	$light = mapValue($water, 'water', 'light');
	$temperature = mapValue($light, 'light', 'temperature');
	$humidity = mapValue($temperature, 'temperature', 'humidity');
	return mapValue($humidity, 'humidity', 'location');
}, $seeds)) . PHP_EOL;

// Part 2: 51399228

$min = PHP_INT_MAX;
$sequence = ['seed','soil','fertilizer','water','light','temperature','humidity','location'];
$stack = new \SplStack();

// Extract pairs from the list
foreach(array_chunk($seeds, 2) as $seed_range) {
	// Ingest our initial seeds as [from,to] ranges rather than [from,len]
	$stack->push(['seed', [$seed_range[0], $seed_range[0] + $seed_range[1] - 1]]);
}

// Depth first search
while(!$stack->isEmpty()) {
	$node = $stack->pop();
	
	// If we hit a location node, compare the min
	if('location' == $node[0]) {
		$min = min($min, $node[1][0]);
		
	} else {
		// Find the next node in our sequence
		$next = $sequence[array_search($node[0], $sequence)+1];
		// Partition our source range into possible destination ranges
		foreach(getDestinationRangesBySourceRange($node[1], $node[0], $next) as $branch) {
			// Explore each possible destination range (add delta to from/to for src->dst)
			$stack->push([$next, [$branch[0] + $branch[2], $branch[1] + $branch[2]]]);
		}
	}
}

echo "Part 2: " . $min . PHP_EOL;