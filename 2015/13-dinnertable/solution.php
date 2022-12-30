<?php /** @noinspection ALL */
declare(strict_types=1);

namespace AoC\Year2015\Day13\Part1;

ini_set('memory_limit', '256M');

//$rules = explode("\n", file_get_contents('./data/example.txt'));
$rules = explode("\n", file_get_contents('./data/input.txt'));

$people = array_values(array_unique(
    array_map(fn($rule) => substr($rule, 0, strpos($rule, ' ')), $rules))
);

$rules = array_map(function($rule) {
    $row = [];
    sscanf($rule, "%s would %s %d happiness units by sitting next to %[a-zA-Z].",
        $row['actor'],
        $row['sign'],
        $row['happiness'],
        $row['target']
    );
    $row['happiness'] *= match($row['sign']) {'gain' => 1, 'lose' => -1 };
    unset($row['sign']);
    return $row;
}, $rules);

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

function score(array $set, array $rules) : int {
    $score = 0;

    foreach($rules as $rule) {
        $actor = array_search($rule['actor'], $set);
        $left = $set[0 == $actor ? array_key_last($set) : $actor-1];
        $right = $set[array_key_last($set) == $actor ? array_key_first($set) : $actor+1];

        if($left == $rule['target'] || $right == $rule['target'])
            $score += $rule['happiness'];
    }

    return $score;
}

$permutations = permutations($people);
$scores = [];

foreach($permutations as $i => $permutation) {
    $scores[$i] = score($permutation, $rules);
}

printf("Part 1: %d\n", max($scores));

$people[] = 'Me';
$permutations = permutations($people);

foreach($permutations as $i => $permutation) {
    $scores[$i] = score($permutation, $rules);
}

printf("Part 2: %d\n", max($scores));