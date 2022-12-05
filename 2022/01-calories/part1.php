<?php
// Find the Elf carrying the most Calories. How many total Calories is that Elf carrying?
$calories = file_get_contents('calories.txt');

$elves = array_map(
    fn($items) => array_sum(explode("\n", $items)),
    explode("\n\n", $calories)
);

echo max($elves), "\n";