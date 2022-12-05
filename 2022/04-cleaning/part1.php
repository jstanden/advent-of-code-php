<?php
$assignments = explode("\n", file_get_contents('data.txt'));

$results = array_filter($assignments, function($pair) {
    $ranges = array_map(fn($range) => explode('-', $range), explode(',', $pair));
    $a = range(...$ranges[0]);
    $b = range(...$ranges[1]);
    return !count(array_diff($a, $b)) || !count(array_diff($b, $a));
});

echo count($results), PHP_EOL;