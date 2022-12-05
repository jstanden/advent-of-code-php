<?php
$stops = file_get_contents('data.txt');
$at = [0,0]; // Origin
$world = ['0,0' => 1];

for($i=0; $i<strlen($stops); $i++) {
    $vector = match($stops[$i]) {
      '^' => [0,1], 'v' => [0,-1], '<' => [-1,0], '>' => [1,0],
    };
    $key = ($at[0] += $vector[0]) . ',' . ($at[1] += $vector[1]);
    $world[$key] = ($world[$key] ?? 0) + 1;
}

echo count($world), PHP_EOL;