<?php
$data = explode("\n", file_get_contents('./data/input.txt'));

$total_distances = [];
$race_duration = 2_503;

foreach($data as $line) {
	$reindeer = [];
	sscanf(
		$line,
		"%s can fly %d km/s for %d seconds, but then must rest for %d seconds.",
		$reindeer['name'],
		$reindeer['speed_kms'],
		$reindeer['speed_duration'],
		$reindeer['rest_duration']
	);
	
	$cycle = $reindeer['speed_duration'] + $reindeer['rest_duration'];
	
	$fly_secs = 
		// Whole
		($reindeer['speed_duration'] * floor($race_duration/$cycle))
		// Remainder
		+ min($race_duration % $cycle, $reindeer['speed_duration'])
	;
	
	$total_distances[$reindeer['name']] = $fly_secs * $reindeer['speed_kms'];
}

printf("Part 1: %d\n", max($total_distances)); // 2640