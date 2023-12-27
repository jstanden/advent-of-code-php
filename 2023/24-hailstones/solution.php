<?php // @jeff@phpc.social
declare(strict_types=1);

namespace AoC\Year2023\Day24;

use jstanden\AoC\Library\Cube3d\Ray3d;
use jstanden\AoC\Library\Cube3d\Ray3dIntersection;
use jstanden\AoC\Library\Cube3d\Vector3d;
use jstanden\AoC\Library\Math\Combinations;
use jstanden\AoC\Library\Math\Math;

require_once('../../vendor/autoload.php');

//$lines = explode("\n", file_get_contents('example.txt'));
$lines = explode("\n", file_get_contents('../../data/2023/24/data.txt'));

// Parse hailstones
$hailstones = array_map(
	fn($hailstone) => new Ray3d(
		Vector3d::fromString($hailstone[0]),
		Vector3d::fromString($hailstone[1])
	),
	array_map(fn($line) => explode(' @ ', $line), $lines)
);

/**
 * @param Ray3d[] $hailstones
 * @return Ray3dIntersection[]
 */
function checkHailstoneIntersections(array $hailstones) : array {
	$pairs = Combinations::pairs($hailstones);
	$intersections = [];
	
	/**
	 * @var Ray3d $a
	 * @var Ray3d $b
	 */
	foreach ($pairs as list($a, $b)) {
		// Add non-null intersections to the results, ignore z-axis
		if (($intersection = $a->intersects2d($b, 'z'))) {
			$intersections[] = $intersection;
		}
	}
	
	return $intersections;
}

// ======================================================
// Part 1: 13754

echo "Part 1: ", array_reduce(
	checkHailstoneIntersections($hailstones),
	function(int $sum, Ray3dIntersection $intersection) {
		$lower = 200_000_000_000_000;
		$upper = 400_000_000_000_000;
		
		// Count intersections within the given bounds
		$within_bounds = Math::between($intersection->at->x, $lower, $upper)
			&& Math::between($intersection->at->y, $lower, $upper);
			
		return $within_bounds ? ($sum + 1) : $sum;
	},
	0
), PHP_EOL;

// ======================================================
// Part 2: 711031616315001

function findCommonIntersectionsWithDelta(Vector3d $rock_velocity, $projected_axis='z') : ?Ray3dIntersection {
	global $hailstones;
	
	// Adjust three hailstone velocities with an estimated rock velocity
	$adjusted_hailstones = array_map(
		function (Ray3d $hailstone) use ($rock_velocity) {
			$h0 = clone $hailstone;
			$h0->direction = $h0->direction->add($rock_velocity->invert());
			return $h0;
		},
		array_slice($hailstones, 0, 3)
	);
	
	// Pair up the three hailstones
	$pairs = Combinations::pairs($adjusted_hailstones);
	$intersections = [];
	
	foreach($pairs as $pair) {
		// If this pair of hailstones intersects in 2D
		if(($intersection = $pair[0]->intersects2d($pair[1], $projected_axis))) {
			// Ignore the projected axis
			if('x' == $projected_axis) {
				$intersection->at->x = 0;
			} else if('y' == $projected_axis) {
				$intersection->at->y = 0;
			} else {
				$intersection->at->z = 0;
			}
			
			$intersections[] = $intersection;
		}
	}

	// Only return when all intersections share a common point (our rock)
	if(
		1 == count(array_unique(array_column($intersections,'at')))
		&& count($intersections) == count($pairs)
	) return $intersections[0];
	
	return null;
}

// Approximate the largest min/max value given the hailstone velocities
$max_value = 350;

// First find 2D intersections on velocity XY
foreach(range(-$max_value, $max_value) as $vx) {
	foreach(range(-$max_value, $max_value) as $vy) {
		$rock_velocity = new Vector3d($vx, $vy, 0);
		// Stop if all hailstones intersect at this XY
		if(findCommonIntersectionsWithDelta($rock_velocity, 'z'))
			break 2;
	}
}

// We have VX + VY, now find VZ
foreach(range(-$max_value, $max_value) as $vz) {
	$rock_velocity = new Vector3d($vx, $vy, $vz);
	if(($intersection = findCommonIntersectionsWithDelta($rock_velocity, 'y'))) {
		// Calculate the original rock position backward from the intersection time
		$rock = new Ray3d(
			$intersection->ray_a->positionAtTime((int)round($intersection->time_a)),
			$rock_velocity
		);
		
		echo "Part 2: ", intval($rock->origin->x + $rock->origin->y + $rock->origin->z), PHP_EOL;
		exit;
	}
}