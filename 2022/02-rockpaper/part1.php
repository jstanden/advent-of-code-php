<?php
// What would your total score be if everything goes exactly according to your strategy guide?
$games = file_get_contents('games.txt');

$beats = [
    'A' => 'Z', // Rock > Scissors
    'B' => 'X', // Paper > Rock
    'C' => 'Y', // Scissors > Paper
];

$scores_shape = [
    'A' => 1, // Rock
    'B' => 2, // Paper
    'C' => 3, // Scissors
    'X' => 1, // Rock
    'Y' => 2, // Paper
    'Z' => 3, // Scissors
];

$score = array_sum(array_map(function($game) use ($scores_shape, $beats) {
    list($them, $me) = explode(' ', $game);

    if($scores_shape[$them] == $scores_shape[$me]) { // Draw
        return 3 + $scores_shape[$me];
    } else if($beats[$them] == $me) { // Lose
        return $scores_shape[$me];
    } else { // Win
        return 6 + $scores_shape[$me];
    }
}, explode("\n", $games)));

echo $score, "\n";