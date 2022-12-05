<?php
$stairs = file_get_contents('data.txt');

$counts = array_intersect_key(count_chars($stairs), [40=>true,41=>true]);

echo $counts[40] - $counts[41], PHP_EOL;