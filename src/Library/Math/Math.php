<?php
declare(strict_types=1);

namespace jstanden\AoC\Library\Math;

use MathPHP\Algebra;

class Math {
	static function between(float $n, float $lower, float $upper, $inclusive=true) : bool
	{
		if($inclusive) {
			return $n >= $lower && $n <= $upper;
		} else {
			return $n > $lower && $n < $upper;
		}
	}

	// Quadratic solution
	// See: https://github.com/terminalmage/adventofcode/blob/main/2023/day06.py
	// dist = (time-wait)*wait
	// ==> d = (t-w)*w
	// ==> d = tw-w²
	// ==> w² + d = tw
	// ==> w² - tw + d = 0
	// using ax² + bx + c = 0  ==  x = (-b +/- sqrt((b)²-4ac)/2a)
	// ==> w = (t +/- sqrt(t² - 4d))/2
	static function quadratic(int $a, int $b, int $c) : array
	{
		$delta = sqrt(($b ** 2) - (4 * $a * $c));
		$lower = -1 * (($b - $delta) / (2 * $a));
		$upper = -1 * (($b + $delta) / (2 * $a));
		return ['lower'=>min($lower,$upper), 'upper'=>max($lower,$upper)];
	}
	
	// A quick wrapper to compute the lowest common multiple of (n>2) integers
	static function lcm(array $numbers) : ?int
	{
		// We can only compute lcm on 2+ integers
		if(count($numbers) < 2) return null;
		// Start with our first number
		$lcm = current($numbers);
		// Find the new lcm for each subsequent number
		foreach(array_slice($numbers,1) as $n)
			$lcm = Algebra::lcm($lcm, $n);
		return $lcm;
	}
}