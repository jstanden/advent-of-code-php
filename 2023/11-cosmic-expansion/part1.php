<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day11;

use jstanden\AoC\Library\Grid2d\GridMap2d;
use jstanden\AoC\Library\Math\Combinations;

require_once('../../vendor/autoload.php');

$lines = explode("\n", file_get_contents('../../data/2023/11/data.txt'));

$universe = new GridMap2d($lines);

// Expand the universe horizontally
$x = 0;
while($col = $universe->getColumn($x)) {
	if(!array_filter($col, fn($x)=>'.'!=$x)) {
		$universe->insertColumn(++$x, $col);
	}
	$x++;
}

// Expand the universe vertically
$y = $universe->extents['y1'];
while($row = $universe->getRow($y)) {
	if(!array_filter($row, fn($y)=>'.'!=$y)) {
		$universe->insertRow($y, $row);
	}
	$y--;
}

// Find the galaxy tiles
$galaxies = $universe->findTiles(['#']);

// Pair them up into distinct sets
$pairs = Combinations::pairs($galaxies);

// Part 1: 10173804
echo "Part 1: " . array_reduce(
	$pairs,
	fn(int $sum, array $carry) =>
		$sum + $universe->manhattanDistance($carry[0]->origin, $carry[1]->origin),
	0
) . PHP_EOL;
