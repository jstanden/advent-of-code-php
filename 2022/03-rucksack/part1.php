<?php
$backpacks = file_get_contents('backpacks.txt');

$priorities = array_merge(
    array_combine(range('a', 'z'), range(1, 26)),
    array_combine(range('A', 'Z'), range(27, 52)),
);

$sum = array_sum(array_map(function($backpack) use ($priorities) {
    list($items_a, $items_b) = str_split($backpack, strlen($backpack)/2);
    $same = current(array_intersect(str_split($items_a,1), str_split($items_b, 1)));
    return $priorities[$same];
}, explode("\n", $backpacks)));

echo $sum, "\n";