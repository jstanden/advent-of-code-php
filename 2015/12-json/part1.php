<?php
$json = json_decode(file_get_contents('input.json'), true);

$numbers = [];
array_walk_recursive($json, function($n) use (&$numbers) {
	if(is_int($n)) $numbers[] = $n;
});

printf("Part 1: %d\n", array_sum($numbers)); // 119433