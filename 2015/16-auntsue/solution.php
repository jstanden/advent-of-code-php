<?php
$data = explode("\n", file_get_contents('./data/input.txt'));
$aunt_sues = [];

$sample = [
	'children' => 3,
	'cats' => 7,
	'samoyeds' => 2,
	'pomeranians' => 3,
	'akitas' => 0,
	'vizslas' => 0,
	'goldfish' => 5,
	'trees' => 3,
	'cars' => 2,
	'perfumes' => 1,
];

foreach($data as $line) {
	// Sue 1: cars: 9, akitas: 3, goldfish: 0
	sscanf($line, "Sue %d: %[a-z 0-9,:]", $id, $properties);
	
	$properties = array_merge(...array_map(function($key_val) {
		return explode(': ', $key_val);
	}, explode(', ', $properties)));
	
	$aunt_sues[$id] = [
		'properties' => array_combine(
			array_filter($properties, fn($k) => 0 == $k % 2, ARRAY_FILTER_USE_KEY),
			array_filter($properties, fn($k) => 0 != $k % 2, ARRAY_FILTER_USE_KEY),
		),
		'score' => 0,
	];
}

$part1 = array_filter($aunt_sues, function($sue) use ($sample) {
	foreach($sue['properties'] as $k => $v) {
		if($sample[$k] != $v) return false;
	}
	return true;
});

$part2 = array_filter($aunt_sues, function($sue) use ($sample) {
	foreach($sue['properties'] as $k => $v) {
		if(in_array($k, ['cats','trees']) && $v <= $sample[$k]) return false;
		else if(in_array($k, ['pomeranians','goldfish']) && $v >= $sample[$k]) return false;
		else if(in_array($k, ['akitas', 'vizslas']) && $v != 0) return false;
		else if(in_array($k, ['children', 'samoyeds', 'perfumes', 'cars']) && $v != $sample[$k]) return false;
	}
	return true;
});

printf("Part 1: %d\n", array_key_first($part1)); // 373
printf("Part 2: %d\n", array_key_first($part2)); // 260