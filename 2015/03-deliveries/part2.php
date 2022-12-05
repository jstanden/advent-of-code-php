<?php
$stops = file_get_contents('data.txt');
$santa_at = [0,0];
$robo_at = [0,0];
$world = ['0,0' => 2];

for($i=0; $i<strlen($stops); $i++) {
    $vector = match($stops[$i]) {
      '^' => [0,1], 'v' => [0,-1], '<' => [-1,0], '>' => [1,0],
    };
    if(0 == $i % 2) $who =& $santa_at; else $who =& $robo_at;
    $key = ($who[0] += $vector[0]) . ',' . ($who[1] += $vector[1]);
    $world[$key] = ($world[$key] ?? 0) + 1;
}

echo count($world), PHP_EOL;