<?php
// Find the top three Elves carrying the most Calories. How many Calories are those Elves carrying in total?
$calories = file_get_contents('calories.txt');

$elves = array_map(
    fn($items) => array_sum(explode("\n", $items)),
    explode("\n\n", $calories)
);

rsort($elves);

echo array_sum(array_slice($elves, 0, 3)), "\n";