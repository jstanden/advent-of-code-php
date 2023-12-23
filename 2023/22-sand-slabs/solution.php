<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day22;

use jstanden\AoC\Library\Cube3d\Bounds3d;
use jstanden\AoC\Library\Cube3d\Vector3d;

require_once('../../vendor/autoload.php');

//$lines = explode("\n", file_get_contents('example.txt'));
$lines = explode("\n", file_get_contents('../../data/2023/22/data.txt'));

class Brick {
	function __construct(
		public string $name,
		public Vector3d $origin,
		public Vector3d $end
	) {}
	
	function __toString() {
		return sprintf("%s (%s ~ %s)", $this->name, $this->origin, $this->end);
	}
	
	function __clone(): void {
		$this->origin = clone $this->origin;
		$this->end = clone $this->end;
	}
	
	function getBounds() : Bounds3d {
		return new Bounds3d($this->origin, $this->end);
	}
	
	function getBlocks() : \Generator {
		$heading = Vector3d::direction($this->origin, $this->end);
		
		// If only one block in any direction, return it
		if($this->origin->equals($this->end)) {
			yield clone $this->origin;
			return;
		}
		
		yield ($at = clone $this->origin);
		
		while($at = $at->add($heading)) {
			yield $at;
			if($at->equals($this->end)) break;
		}
	}
	
	public function move(Vector3d $direction) : void {
		$this->origin = $this->origin->add($direction);
		$this->end = $this->end->add($direction);
	}
	
	public function isClearToMove(Vector3d $direction, array &$occupied, array &$collisions=[]) : bool {
		if($this->getBounds()->extents()['min_z'] == 1)
			return false;
		
		$ghost = clone $this;
		$ghost->move($direction);
		
		$collisions = isOccupied($ghost, $occupied);
		
		return 0 == count($collisions);
	}
}

$brick_letter = 'A';

// Parse input into bricks made up of blocks
$bricks = array_map(
	function($vectors) use (&$brick_letter) {
		return new Brick($brick_letter++, Vector3d::fromString($vectors[0]), Vector3d::fromString($vectors[1]));
	},
	array_map(fn($line)=>explode('~', $line), $lines)
);

$occupied = [];

function occupyBrick(Brick $brick, array &$occupied) : void {
	foreach($brick->getBlocks() as $block) {
		$occupied[(string)$block] = $brick->name; // &$brick
	}
}

function unoccupyBrick(Brick $brick, array &$occupied) : void {
	foreach($brick->getBlocks() as $block) {
		unset($occupied[(string)$block]);
	}
}

function isOccupied(Brick $brick, array $occupied) : array {
	$occupants = [];
	
	foreach($brick->getBlocks() as $block) {
		if(
			array_key_exists((string)$block, $occupied)
			// Ignore ourselves in the same space
			&& $occupied[(string)$block] != $brick->name
		)
			$occupants[$occupied[(string)$block]] = true;
	}
	
	return $occupants;
}

// Hash the current position of all brick blocks
foreach($bricks as $brick) {
	occupyBrick($brick, $occupied);
}

$downOne = new Vector3d(0, 0, -1);

// Sort by lowest bricks first
usort($bricks, fn($a,$b) => $a->getBounds()->extents()['min_z'] <=> $b->getBounds()->extents()['min_z']);

// Can we move anything down?
foreach($bricks as $brick) {
	// While we have room below the brick to move down
	while($brick->getBounds()->extents()['min_z'] > 1 // not at ground
		&& $brick->isClearToMove($downOne, $occupied)
	) {
		unoccupyBrick($brick, $occupied);
		$brick->move($downOne);
		occupyBrick($brick, $occupied);
	}
}

usort($bricks, fn($a,$b) => $a->getBounds()->extents()['min_z'] <=> $b->getBounds()->extents()['min_z']);

function disintegrate(Brick $brick, array $occupied, int $part=1) : int {
	global $bricks, $downOne;
	
	$other_moves = 0;
	
	unoccupyBrick($brick, $occupied);
	
	foreach ($bricks as $other_brick) {
		if ($other_brick === $brick) continue;
		if ($other_brick->isClearToMove($downOne, $occupied)) {
			if(2 == $part) {
				$other_brick = clone $other_brick;
				unoccupyBrick($other_brick, $occupied);
				$other_brick->move($downOne);
				occupyBrick($other_brick, $occupied);
			}
			$other_moves++;
		}
	}
	
	occupyBrick($brick, $occupied);
	
	return $other_moves;
}

// Part 1: 398

echo sprintf("Part 1: %d", count(array_filter(
	array_map(fn(Brick $brick) => disintegrate($brick, $occupied), $bricks),
	fn($moved) => 0 == $moved
))), PHP_EOL;

// Part 2: 70727

echo sprintf("Part 2: %d",
	array_sum(array_map(fn(Brick $brick) => disintegrate($brick, $occupied, part: 2), $bricks))
), PHP_EOL;
