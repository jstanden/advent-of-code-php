<?php
declare(strict_types=1);

namespace jstanden\AoC\Library\Math;

class Math {
	// Quadratic solution
	// See: https://github.com/terminalmage/adventofcode/blob/main/2023/day06.py
	// dist = (time-wait)*wait
	// ==> d = (t-w)*w
	// ==> d = tw-w²
	// ==> w² + d = tw
	// ==> w² - tw + d = 0
	// using ax² + bx + c = 0  ==  x = (-b +/- sqrt((b)²-4ac)/2a)
	// ==> w = (t +/- sqrt(t² - 4d))/2
	static function quadratic(int $a, int $b, int $c) : array {
		$delta = sqrt(($b ** 2) - (4 * $a * $c));
		$lower = -1 * (($b - $delta) / (2 * $a));
		$upper = -1 * (($b + $delta) / (2 * $a));
		return ['lower'=>min($lower,$upper), 'upper'=>max($lower,$upper)];
	}
}