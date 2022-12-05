<?php
// https://adventofcode.com/2015/day/5
$strings = explode("\n", file_get_contents('data.txt'));

echo count(array_filter($strings, function($string) {
    // Not at least three vowels
    if(count(array_intersect(str_split($string), ['a','e','i','o','u'])) < 3)
        return false;

    // Not ab, cd, pq, xy
    foreach(['ab','cd','pq','xy'] as $forbidden)
        if(str_contains($string, $forbidden))
            return false;

    // No repeat letters
    if(!preg_match('/([a-z])\1+/', $string))
        return false;

    return true;
})), PHP_EOL;