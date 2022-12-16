<?php
// Jeff Standen - https://phpc.social/@jeff

// See: https://en.wikipedia.org/wiki/Taxicab_geometry#/media/File:TaxicabGeometryCircle.svg
//      https://en.wikipedia.org/wiki/Taxicab_geometry#Balls

function manhattanDistance($x1,$y1,$x2,$y2) : int {
	return abs($x2 - $x1) + abs($y2 - $y1);
}

function mergeRanges(array $ranges) : array {
	$ranges = array_unique($ranges, SORT_REGULAR);
	sort($ranges);
	
	for($i=0;array_key_exists($i+1, $ranges);$i++) {
		// Next within: Next start is bigger than my start, and next end is less than my end
		if($ranges[$i+1][0] >= $ranges[$i][0] && $ranges[$i+1][1] <= $ranges[$i][1]) {
			unset($ranges[$i+1]);
			$i--;
			$ranges = array_values($ranges);
			
		// Overlap right: Next start is less than my end, and next end is bigger than my end
		} else if($ranges[$i+1][0] <= $ranges[$i][1] && $ranges[$i+1][1] >= $ranges[$i][1]) {
			$ranges[$i][1] = $ranges[$i+1][1];
			unset($ranges[$i+1]);
			$i--;
			$ranges = array_values($ranges);
		}
	}
	
	return $ranges;
}

// Parse and format sensor data
$sensors = array_map(
	function($reading) {
		$sensor = [];
		
		sscanf(
			$reading,
			"Sensor at x=%d, y=%d: closest beacon is at x=%d, y=%d",
			$sensor['x'],
			$sensor['y'],
			$sensor['beacon_x'],
			$sensor['beacon_y']
		);

		// Cache our radius
		$sensor['radius'] = manhattanDistance(...array_values(array_slice($sensor, 0, 4)));

		// Cache our extents
		$sensor['extents'] = [
			'x' => [$sensor['x'] - $sensor['radius'], $sensor['x'] + $sensor['radius']],
			'y' => [$sensor['y'] - $sensor['radius'], $sensor['y'] + $sensor['radius']],
		];

		return $sensor;
	},
	explode("\n", file_get_contents('data.txt'))
);

function sensorRangesAtTargetAxis(string $axis, int $target, $min=PHP_INT_MIN, $max=PHP_INT_MAX) : array {
	global $sensors;
	
	if(!in_array($axis, ['x', 'y']))
		die("sensorRangesAtTargetAxis() axis must be 'x' or 'y'");
	
	$ranges = [];
	$inverse_axis = $axis == 'x' ? 'y' : 'x';
	
	foreach($sensors as $sensor) {
		// Quickly exclude values outside of our radius
		if($target < $sensor['extents'][$axis][0] || $target > $sensor['extents'][$axis][1])
			continue;

		// Block out known beacons
		if($target == $sensor['beacon_' . $axis])
			$ranges[] = [$sensor['beacon_' . $inverse_axis], $sensor['beacon_' . $inverse_axis]];

		// Straight line x distance from our sensor to the target
		$target_dist = abs($sensor[$axis] - $target);

		// What is its radius?
		$range = $sensor['radius'] - $target_dist;
		$axis_min = $sensor[$inverse_axis]-$range;
		$axis_max = $sensor[$inverse_axis]+$range;

		if($axis_min > $max || $axis_max < $min)
			continue;

		// Mark our sensor radius overlap in this y band
		$axis_range = [
			max($min, $sensor[$inverse_axis]-$range),
			min($max, $sensor[$inverse_axis]+$range)
		];

		$ranges[] = $axis_range;
	}
	
	return mergeRanges($ranges);
}

// Sort sensors by x min
usort($sensors, fn($a, $b) => $a['extents']['x'][0] <=> $b['extents']['x'][0]);

// Part 1: Find the length of the coverage on the Y axis at 2M
$sum = array_reduce(
	sensorRangesAtTargetAxis('y', 2_000_000),
	fn($carry, $range) => $carry + array_sum(array_map('abs', $range)),
	0
);

$y = null;

// Part 2: Find the beacon not covered by any sensor radius (brute force)
for($x=0; $x<4_000_000; $x++) {
	$target_ranges = sensorRangesAtTargetAxis('x', $x, 0, 4_000_000);

	// We found the missing beacon if there's more than one range
	if(count($target_ranges) > 1) {
		$y = $target_ranges[0][1]+1;
		break;
	}
}

printf("Part 1: %d\n", $sum);
printf("Part 2: %d\n", $x * 4_000_000 + $y);