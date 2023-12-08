<?php // Jeff Standen <@jeff@phpc.social>
/** @noinspection DuplicatedCode */
/** @noinspection SpellCheckingInspection */

namespace AoC\Year2023\Day8;

use MathPHP\Algebra;

require_once('../../vendor/autoload.php');

$data = explode("\n", file_get_contents("../../data/2023/08/data.txt"));

/*
We're given a dataset representing a graph of nodes, edges left/right from
each node, and traversal instructions (left/right cycle).

LLLLLLLRRRLRRRLRLRLRLRRLLRRRL[...]

NBA = (SMR, QRS)
DGK = (KVQ, XHR)
KTC = (TVB, MTH)
LCG = (FQC, KHX)
PSZ = (FSF, QSM)
[...]

Our challenge is to find the number of steps required where traversing every
'A' suffixed node simultaneously finds each traverser on a 'Z' suffixed node
at exactly the same time. A quick check of the graph shows that each A->Z
segment has a constant cycle without revisiting its start node.

We notice that the length of the instructions (269) is prime (coincidence?).
*/

$path = array_shift($data); // first line is the left/right path
array_shift($data); // skip the next blank line
$keys = []; // This will hold our node keys during parsing

$node_map = array_map(
	// For each input line
	function($line) use (&$keys) {
		$result = [];
		// Extract the nodes and edges
		sscanf($line, "%s = (%[A-Z0-9], %[A-Z0-9])", $keys[], $result[0], $result[1]);
		return $result;
	},
	$data
);

// Map node keys to their left/right edges
$node_map = array_combine($keys, $node_map);

// Find all the nodes that end with an 'A'
$start_nodes = array_values(
	array_filter(
		array_keys($node_map),
		fn($k) => str_ends_with($k, 'A')
	)
);

/*
Our path instructions are relatively short at 269 steps. This cycle will
repeat many times. We need to figure out how many steps it takes for each
start node ending with 'A' to reach a node ending with 'Z'. We store this
in $segment_steps.
*/

$segment_steps = array_fill_keys($start_nodes, []);
$path_length = strlen($path);

foreach($start_nodes as $at) {
	$steps = 0;
	$start = $at;
	while(true) {
		// Our cyclic position in the path at this step (L | R)
		$i = $path[$steps++ % $path_length];
		// The edge we follow left or right from this node
		$at = $node_map[$at]['L' == $i ? 0 : 1];
		// If our new node ends in 'Z'
		if(str_ends_with($at, 'Z')) {
			// Record how many steps it took to get here and stop
			$segment_steps[$start][$at] = $steps;
			break;
		}
	}
}

/*
We know the path instructions repeat every 269 steps (which is prime). We
have 6 start node paths with prime-factorized step intervals of:

  XSA->TKZ == 11567 (1, 43, 269, 11567)
  VVA->PSZ == 12643 (1, 47, 269, 12643)
  TTA->RFZ == 15871 (1, 59, 269, 15871)
  AAA->ZZZ == 16409 (1, 61, 269, 16409)
  NBA->HGZ == 19637 (1, 73, 269, 19637)
  MHA->GJZ == 21251 (1, 79, 269, 21251)

We recognize our path (269) as a factor of all of these intervals, so we can
use the corresponding multiples to keep the math from eventually overflowing
a 64-bit integer.

If we multiply the LCM of those primes by the path length, we get our answer:
lcm(43,47,59,61,73,79) * 269 == 11,283,670,395,017
*/

// A quick wrapper to compute the lowest common multiple of (n>2) integers
function lcm(array $numbers) : ?int {
	// We can only compute lcm on 2+ integers
	if(count($numbers) < 2) return null;
	// Start with our first number
	$lcm = current($numbers);
	// Find the new lcm for each subsequent number
	foreach(array_slice($numbers,1) as $n)
		$lcm = Algebra::lcm($lcm, $n);
	return $lcm;
}

// Part 2: 11283670395017
echo sprintf("Part 2: %d\n",
	$path_length * lcm(array_map(fn($at) => current($at)/$path_length, $segment_steps))
);