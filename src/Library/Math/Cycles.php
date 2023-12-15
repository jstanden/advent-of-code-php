<?php
declare(strict_types=1);

namespace jstanden\AoC\Library\Math;

class Cycles {
	static function detectCycle($array) : array|false {
		$n = count($array);
		
		for ($cycleLength = 2; $cycleLength <= $n / 2; $cycleLength++) {
			for ($i = 0; $i <= $n - 2 * $cycleLength; $i++) {
				$firstSubsequence = array_slice($array, $i, $cycleLength);
				$secondSubsequence = array_slice($array, $i + $cycleLength, $cycleLength);
				
				if ($firstSubsequence === $secondSubsequence) {
					return $firstSubsequence;
				}
			}
		}
		
		//echo "No cycle detected." . PHP_EOL;
		return false;
	}
	
	static function findSubarray($haystack, $needle) : int {
		$haystackLength = count($haystack);
		$needleLength = count($needle);
		
		for ($i = 0; $i <= $haystackLength - $needleLength; $i++) {
			$match = true;
			for ($j = 0; $j < $needleLength; $j++) {
				if ($haystack[$i + $j] !== $needle[$j]) {
					$match = false;
					break;
				}
			}
			if ($match) {
				// Return the starting index of the found subarray
				return $i;
			}
		}
		
		// Return -1 if the subarray is not found
		return -1;
	}
}