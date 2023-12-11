<?php // @jeff@phpc.social
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

declare(strict_types=1);
namespace AoC\Year2023\Day3;

use jstanden\AoC\Library\Grid2d\Bounds2d;
use jstanden\AoC\Library\Grid2d\Entity2d;
use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Grid2d\Vector2d;
use jstanden\AoC\Library\Grid2d\Vector2dDirection;

require_once('../../vendor/autoload.php');

class Schematic extends GridMap2d {
	function findRowEntitiesByPattern(string $pattern) : array {
		$results = [];

		foreach($this->getRows() as $y => $row) {
			// Match entities with patterns and return offsets
			if(preg_match_all(
				$pattern,
				implode('', $row),
				$matches,
				PREG_OFFSET_CAPTURE
			)) {
				// Create a dictionary for each entity
				$results = array_merge($results, array_map(
					fn($match) => new Entity2d($match[0], new Vector2d($match[1], $y), strlen($match[0])),
					$matches[0]
				));
			}
		}

		return $results;
	}

	function getNumbers() : array {
		// Find contiguous numbers
		return $this->findRowEntitiesByPattern('/\d+/');
	}

	function getGears() : array {
		// Gears are asterisks
		return $this->findRowEntitiesByPattern('/\*/');
	}

	function hasNeighborsExcluding(Vector2d $loc, array $exclusions) : bool {
		// Check the eight cardinal directions from our vector
		return (bool) array_filter(
			$this->getEightNeighbors($loc),
			// Exclude neighbors with a forbidden tile/symbol
			fn($vector) => !in_array($this->getTile($vector), $exclusions)
		);
	}

	function getNumbersAdjacentToSymbols() : array {
		$numbers = $this->getNumbers();

		return array_filter($numbers, function(Entity2d $match) {
			return (bool) array_filter(
				// Split a number into an array of single digits
				str_split($match->name),
				// Keep any digits touching a non-numeric non-dot tile/symbol
				fn($digit, $delta) => $this->hasNeighborsExcluding(
					// Delta is +x for the length of a number
					Vector2d::add($match->origin, new Vector2d($delta,0)),
					// Exclude other numbers and dots
					array_merge(range(0,9), ['.'])
				),
				ARRAY_FILTER_USE_BOTH
			);
		});
	}

	// Return entities that overlap the given 2d bounds
	public function getEntitiesInBounds(Bounds2d $bounds, array $entities) : array {
		return array_filter(
			$entities,
			fn(Entity2d $entity) => $bounds->overlaps($entity)
		);
	}
}

$data = explode("\n", file_get_contents("../../data/2023/03/data.txt"));
$schematic = new Schematic($data);

// Part 1: 532331

echo 'Part 1: ' . array_sum(
	// Sum numbers next to non-numeric non-dot symbols
	array_column($schematic->getNumbersAdjacentToSymbols(),'name')
) . PHP_EOL;

// Part 2: 82301120

$gears = $schematic->getGears();
$numbers = $schematic->getNumbers();

echo 'Part 2: ' . array_sum(array_map(
	function(Entity2d $gear) use ($numbers, $schematic) {
		// A border one wide around the gear
		$bounds = new Bounds2d(
			origin: Vector2d::add($gear->origin, Vector2dDirection::NORTHWEST->getVector()),
			width: 3,
			height: 3
		);

		// Find numbers that collide with our bounding box
		$collisions = $schematic->getEntitiesInBounds($bounds, $numbers);

		// If our gear collides with exactly two numbers, add the product
		if(2 == count($collisions))
			return array_product(array_column($collisions, 'name'));

		return 0;
	},
	$gears
)) . PHP_EOL;