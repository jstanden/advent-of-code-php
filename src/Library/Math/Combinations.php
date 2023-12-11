<?php
declare(strict_types=1);

namespace jstanden\AoC\Library\Math;

class Combinations {
	static function ofLength(array $combination, int $length, array $values, array &$combinations) : void {
		if(count($combination) == $length) {
			$combinations[] = $combination;
		} else {
			foreach($values as $value) {
				self::ofLength(array_merge($combination, [$value]), $length, $values, $combinations);
			}
		}
	}
	
	static function pairs(array $values) : array
	{
		$combinations = [];
		$length = count($values);
		
		for ($i = 0; $i < $length; $i++) {
			for ($j = $i + 1; $j < $length; $j++) {
				$combinations[] = [$values[$i], $values[$j]];
			}
		}
		
		return $combinations;
	}
}