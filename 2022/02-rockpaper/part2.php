<?php
// Following the Elf's instructions for the second column, what would your
//   total score be if everything goes exactly according to your strategy guide?
$games = file_get_contents('games.txt');

$beats = [
    'A' => 'C', // Rock > Scissors
    'B' => 'A', // Paper > Rock
    'C' => 'B', // Scissors > Paper
];

$scores = [
    'A' => 1, // Rock
    'B' => 2, // Paper
    'C' => 3, // Scissors
    'X' => 0, // Lose
    'Y' => 3, // Draw
    'Z' => 6, // Win
];

$score = array_sum(array_map(function($game) use ($scores, $beats) {
    list($them, $outcome) = explode(' ', $game);

    $me = match($outcome) {
      'X' => $beats[$them],
      'Y' => $them,
      'Z' => array_keys($beats, $them)[0],
    };

    return $scores[$me] + $scores[$outcome];
}, explode("\n", $games)));

echo $score, "\n";