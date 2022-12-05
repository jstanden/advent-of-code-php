<?php
$backpacks = file_get_contents('backpacks.txt');

$priorities = array_combine(
    array_merge(range('a','z'), range('A','Z')),
    range(1,52)
);

$sum = array_sum(array_map(
    function($group) use ($priorities) {
        $badge = current(array_intersect(...array_map(fn($backpack) => str_split($backpack), $group)));
        return $priorities[$badge];
    },
    array_chunk(explode("\n", $backpacks), 3)
));

echo $sum, "\n";