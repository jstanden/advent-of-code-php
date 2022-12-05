<?php
//$dimensions = file_get_contents('test.txt');
$dimensions = file_get_contents('data.txt');

$total = array_sum(array_map(function($box) {
    list($l,$w,$h) = array_map(fn($n) => intval($n), explode('x', $box));
    $areas = [$l*$w, $w*$h, $h*$l];
    $total_area = 2*array_sum($areas);
    $smallest = min($areas);
    return $total_area + $smallest;
}, explode("\n", $dimensions)));

echo $total, PHP_EOL;