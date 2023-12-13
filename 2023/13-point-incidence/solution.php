<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day13;

use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Math\Combinations;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/13/data.txt'));
//$lines = explode("\n", file_get_contents('example.txt'));

// Split the input data into separate maps on blank lines
$maps = [];
while(true) {
	if(false !== ($i = array_search("", $lines))) {
		$maps[] = array_slice(array_splice($lines, 0, $i+1), 0, $i);
	} else {
		$maps[] = $lines;
		break;
	}
}

// Given a set of hashes, find the mirror line
function findMirror(array $hashes, array $excluding=[]) : ?int {
	$split_at = null;

	// Look forward through hashes until we find an adjacent match
	while(false !== ($current = current($hashes))) {
		if ($current === next($hashes)) {
			// If we're ignoring this mirror line, skip
			if(in_array(key($hashes), $excluding))
				continue;

			// If we found a matching pair, set them as our candidate mirror line
			$split_at = key($hashes);

			// Divide the hashes at this line
			$halves = [
				array_slice($hashes, 0, $split_at),
				array_slice($hashes, $split_at),
			];

			// Flip the first half to match orientations
			$halves[0] = array_reverse($halves[0]);

			// Find the smaller half and crop the other half to the same length
			if(count($halves[0]) < count($halves[1])) {
				$halves[1] = array_slice($halves[1], 0, count($halves[0]));
			} else {
				$halves[0] = array_slice($halves[0], 0, count($halves[1]));
			}

			// If our two halves are the same, this is the mirror line
			if($halves[0] === $halves[1])
				break;

			// Otherwise continue trying candidates
			$split_at = null;
		}
	}

	return $split_at;
}

function findSmudge(array $array, array $excluding=[]) : ?int {
	$pairs = Combinations::pairs($array, preserve_keys: true);

	foreach($pairs as $pair) {
		// Track our original row/col positions
		$a = array_key_first($pair);
		$b = array_key_last($pair);

		// Find which tiles are different between these paired arrays
		$diff = array_filter(array_keys($pair[$a]), fn($i) => $pair[$a][$i] != $pair[$b][$i]);

		// If we found exactly one difference between a pair
		if(1 == count($diff)) {
			// Try flipping those tiles -- does it create a new mirror?
			foreach([$a, $b] as $i) {
				$new_array = $array;
				$new_array[$i][key($diff)] = $pair[$i][key($diff)] == '#' ? '.' : '#';
				$hashes = array_map(fn($row) => hash('xxh64', implode($row)), $new_array);

				// Did we find a new mirror line? (excluding our original)
				if (null != ($split_at = findMirror($hashes, $excluding)))
					return $split_at;
			}
		}
	}

	return null;
}

function solve(array &$maps, int $part=1) : int {
	$sum = 0;
	foreach ($maps as $map_id => $map) {
		$grid_map = new GridMap2d($map);

		// Hash every row and look for a mirror line
		$row_hashes = array_map(fn($row) => hash('xxh64', implode($row)), $grid_map->getRows());
		$split_at_row = findMirror($row_hashes);

		if(2 == $part)
			$split_at_row = findSmudge(
				$grid_map->getRows(),
				excluding: $split_at_row ? [$split_at_row] : []
			);

		// Hash every column and look for a mirror line
		$col_hashes = array_map(fn($col) => hash('xxh64', implode($col)), $grid_map->getColumns());
		$split_at_col = findMirror($col_hashes);

		if(2 == $part)
			$split_at_col = findSmudge(
				$grid_map->getColumns(),
				excluding: $split_at_col ? [$split_at_col] : []
			);

		if (!is_null($split_at_row)) { // Vertical mirror
			$sum += 100 * $split_at_row;
		} elseif (!is_null($split_at_col)) { // Horizontal mirror
			$sum += $split_at_col;
		} else { // No mirror
			die("ERROR: " . $map_id);
		}
	}

	return $sum;
}

// Part 1: 37381
echo "Part 1: " . solve($maps, part: 1) . PHP_EOL;

// Part 2: 28210
echo "Part 2: " . solve($maps, part: 2) . PHP_EOL;