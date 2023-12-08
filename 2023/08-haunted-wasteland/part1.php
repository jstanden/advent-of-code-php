<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day8;

$data = explode("\n", file_get_contents("../../data/2023/08/data.txt"));

$path = array_shift($data); // keep first, skip blank
array_shift($data);
$at = 'AAA';
$steps = 0;
$keys = [];

$node_map = array_map(
	function($line) use (&$keys) {
		$result = [];
		sscanf($line, "%s = (%[A-Z], %[A-Z])", $keys[], $result[0], $result[1]);
		return $result;
	},
	$data
);

$node_map = array_combine($keys, $node_map);

while('ZZZ' != $at) {
	$i = $path[$steps++ % strlen($path)];
	$at = $node_map[$at]['L' == $i ? 0 : 1];
}

// 15871
echo "Part 1: " . $steps . PHP_EOL;