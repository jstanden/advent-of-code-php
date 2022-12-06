<?php
// https://adventofcode.com/2015/day/5#part2
//$strings = explode("\n", file_get_contents('test2.txt'));
$strings = explode("\n", file_get_contents('data.txt'));

echo count(array_filter($strings, function($string) {
    // Must repeat pairs
    if(!preg_match('/([a-z][a-z]).*\1/', $string))
        return false;

    // Repeat with letter between
    if(!preg_match('/([a-z]).\1/', $string, $matches2))
        return false;

    return true;
})), PHP_EOL;