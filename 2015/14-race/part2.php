<?php
$data = explode("\n", file_get_contents('./data/input.txt'));

$race_duration = 2_503;
$reindeer = [];
$total_distances = [];
$points = [];

foreach($data as $line) {
	$entry = [];
	sscanf(
		$line,
		"%s can fly %d km/s for %d seconds, but then must rest for %d seconds.",
		$entry['name'],
		$entry['speed_kms'],
		$entry['speed_duration'],
		$entry['rest_duration']
	);
	$entry['cycle'] = $entry['speed_duration'] + $entry['rest_duration'];
	$reindeer[$entry['name']] = $entry;
}

for($t=1;$t<=$race_duration;$t++) {
	foreach($reindeer as $who => $deer) {
		// If active this second, credit the distance per second
		$total_distances[$who] = 
			($total_distances[$who] ?? 0) 
			+ ((($t-1) % $deer['cycle']) < $deer['speed_duration'] ? $deer['speed_kms'] : 0)
		;
	}
	
	// With ties each reindeer gets +1
	$leads = array_keys($total_distances, max($total_distances));
	foreach($leads as $lead) {
		$points[$lead] = ($points[$lead] ?? 0) + 1;
	}
}

printf("Part 2: %d\n", max($points));