<?php // @jeff@phpc.social
namespace AoC\Year2023\Day4;

$lines = explode("\n", file_get_contents('../../data/2023/04/data.txt'));

// Build a list of scratchcards from data and calculate matches
$cards = array_map(
	function(string $line) {
		// Parse each line as `: winning | ours`
		preg_match('/^.+?: +(.*?) \| +(.*?)$/', $line, $matches);
		
		// Split each set of numbers into arrays (winning|ours)
		$result = [
			'winning' => preg_split('/\s+/', $matches[1]),
			'ours' => preg_split('/\s+/', $matches[2]),
		];
		
		// The matches are an intersection of [winning,ours]
		$result['matches'] = array_values(array_intersect(...array_values($result)));
		
		// We start with a single copy of this scratchcard
		$result['copies'] = 1;
		
		return $result;
	},
	$lines
);

// Part 1 (19135)

echo 'Part 1: ' . array_sum( // Sum each cards points
	array_map(
		// We double our points for each match: pow(2,matches-1) if matches>0
		fn($card) => $card['matches'] ? pow(2, count($card['matches'])-1) : 0,
		$cards,
	)
) . PHP_EOL;

// Part 2 (5704953)

// Loop through scratchcards in order
foreach(array_keys($cards) as $index) {
	// For each subsequent card up to our match count
	foreach(array_keys($cards[$index]['matches']) as $n) {
		// If a subsequent card exists in that position
		if($cards[$index+1+$n] ?? false)
			// Add copies of that card equal to this card
			$cards[$index+1+$n]['copies'] += $cards[$index]['copies'];
	}
}

// Sum our total copies of each scratchcard
echo 'Part 2: ' . array_sum(array_column($cards, 'copies')) . PHP_EOL;