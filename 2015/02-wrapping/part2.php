<?php
$dimensions = file_get_contents('data.txt');
//$dimensions = "2x3x4";

$total = array_sum(array_map(function($box) {
    list($l,$w,$h) = array_map(fn($n) => intval($n), explode('x', $box));
    $volume = $l*$w*$h;
    $perimeters = [2*($l+$w),2*($w+$h),2*($h+$l)];
    return min($perimeters) + $volume;
}, explode("\n", $dimensions)));

echo $total, PHP_EOL;