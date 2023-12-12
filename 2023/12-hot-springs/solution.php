<?php // @jeff@phpc.social
declare(strict_types=1);

namespace AoC\Year2023\Day12;

use jstanden\AoC\Library\Services\Cache;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/12/data.txt'));

function computeArrangements(string $conditions, array $counts) : int {
	$cache = Cache::getInstance();
	
	// We can discard leading dots
	if(str_starts_with($conditions, '.'))
		$conditions = ltrim($conditions, '.');
	
	$cache_key = hash('xxh64', $conditions . implode(',', $counts));
	
	if(null !== ($cached_response = $cache->get($cache_key)))
		return $cached_response;
	
	$arrangements = 0;
	
	// Did we find an arrangement?
	if (empty($conditions) && empty($counts)) {
		$arrangements = 1;
		
	// Fill in wildcards
	} else if(str_starts_with($conditions, '?')) {
		$arrangements = computeArrangements('#' . substr($conditions, 1), $counts)
			+ computeArrangements('.' . substr($conditions, 1), $counts);
		
	// Check our broken spring groups
	} else if(str_starts_with($conditions, '#')) {
		$hashes = substr($conditions, 0, strlen($conditions) - strlen(ltrim($conditions, '#')));
		$remainder = substr($conditions, strlen($hashes));
		
		// If this is a complete chunk
		if(!$remainder || str_starts_with($remainder, '?') || str_starts_with($remainder, '.')) {
			// And we have enough broken strings for the goal
			if(strlen($hashes) == current($counts)) {
				array_shift($counts);
				
				// If there's a wildcard next, it must be a dot
				if(str_starts_with($remainder, '?'))
					$remainder = '.' . substr($remainder, 1);
				
				$arrangements = computeArrangements($remainder, $counts);
				
			// If our next character is a wildcard, replace it
			} else if(str_starts_with($remainder, '?')) {
				$arrangements = computeArrangements($hashes . '#' . substr($remainder, 1), $counts);
			}
		}
	}
	
	$cache->set($cache_key, $arrangements);
	return $arrangements;
}

// ==========================================
// Part 1: 8419

$total_arrangements = 0;

foreach($lines as $line) {
	list($conditions, $counts) = explode(' ', $line);
	$arrangements = computeArrangements($conditions, explode(',', $counts));
	$total_arrangements += $arrangements;
}

echo "Part 1: " . $total_arrangements . PHP_EOL;

// ==========================================
// Part 2: 160500973317706

$total_arrangements = 0;

foreach($lines as $line) {
	list($conditions, $counts) = explode(' ', $line);
	$conditions = substr(str_repeat($conditions . '?', 5), 0, -1);
	$counts = substr(str_repeat($counts . ',', 5), 0, -1);
	$arrangements = computeArrangements($conditions, explode(',', $counts));
	$total_arrangements += $arrangements;
}

echo "Part 2: " . $total_arrangements . PHP_EOL;
