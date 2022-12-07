<?php
$routes = explode("\n", file_get_contents('data.txt'));
//$routes = explode("\n", file_get_contents('test.txt'));
$locations = [];

// Index the costs for each route
array_map(function($route) use (&$locations) {
	sscanf(trim($route), "%s to %s = %d", $from, $to, $dist);
	$locations[$from][$to] = $dist;
	$locations[$to][$from] = $dist;
}, $routes);

// All permutations of a given set
function permutations(array $set) : array {
	if(0 == count($set)) return [];
	if(1 == count($set)) return [$set];
	
	$results = [];
	
	foreach(array_keys($set) as $i) {
		$first = $set[$i];
		$rest = array_merge(array_slice($set,0, $i), array_slice($set, $i+1));
		
		foreach(permutations($rest) as $p)
			$results[] = array_merge([$first], $p);
	}
	
	return $results;
}

$paths = [];

// Calculate the cost of each route
foreach(permutations(array_keys($locations)) as $p) {
	$cost = 0;
	array_reduce($p, function($last, $loc) use (&$cost, $locations) {
		if($last)
			$cost += $locations[$last][$loc];
		return $loc;
	});
	
	$paths[implode(' -> ', $p)] = $cost;
}

// Sort the routes by cost to see the lowest/highest
asort($paths);
echo "Shortest: " . key($paths) . ' = ' . current($paths) . PHP_EOL;
echo "Longest: " . array_key_last($paths) . ' = ' . end($paths) . PHP_EOL;