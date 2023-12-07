<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day6;

use MathPHP\Algebra;
use MathPHP\Exception\IncorrectTypeException;

require_once('../../vendor/autoload.php');

function race(array $times, $distances) : array {
	$winning_combos = 0;
	$score = 1;

	// Run each time/distance simulation
	foreach (array_keys($times) as $i) {
		// let w=wait, t=time(max), d=dist(max)
		// 1w² - tw + d = 0
		try {
			$roots = Algebra::quadratic(a: 1, b: -$times[$i], c: $distances[$i]);
		} catch (IncorrectTypeException) { continue; }
		
		// We need to beat the previous dist(max) record
		$winning_combos = (ceil($roots[1]-1) - floor($roots[0]+1)+1);

		// If this is a winning combo, add to the score (part 1)
		if($winning_combos)
			$score *= $winning_combos;
	}

	return [$winning_combos, $score];
}

//$data = explode("\n", file_get_contents("example.txt"));
$data = explode("\n", file_get_contents("../../data/2023/06/data.txt"));
$times = array_slice(preg_split('/\s+/', $data[0]), 1);
$distances = array_slice(preg_split('/\s+/', $data[1]), 1);

// 800280
echo "Part 1: ", race($times, $distances)[1], PHP_EOL;

// 45128024
$times = [implode('', $times)];
$distances = [implode('', $distances)];
echo "Part 2: ", race($times, $distances)[0], PHP_EOL;