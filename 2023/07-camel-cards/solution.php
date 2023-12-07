<?php // @jeff@phpc.social
/** @noinspection DuplicatedCode */

namespace AoC\Year2023\Day7;

$data = explode("\n", file_get_contents("../../data/2023/07/data.txt"));

// Rank our hand types best to worst
enum HandType : int {
	case FIVE_OF_KIND = 6;
	case FOUR_OF_KIND = 5;
	case FULL_HOUSE = 4;
	case THREE_KIND = 3;
	case TWO_PAIR = 2;
	case ONE_PAIR = 1;
	case HIGH_CARD = 0;

	// Get a hand type based on its cards, with optional jokers
	static function getType(array $hand, bool $with_jokers=false): HandType {
		$counts = $hand['counts'];
		$num_jokers = $counts['J'] ?? 0;

		// If using jokers, first get our card counts without them
		if($with_jokers && $num_jokers) {
			$counts = array_diff_key($hand['counts'], ['J'=>true]);
		}

		// Use card counts to figure out hand types in rank order
		// The only special comparison here is two pair
		$type = match (true) {
			1 == count(array_intersect([5], $counts)) => self::FIVE_OF_KIND,
			1 == count(array_intersect([4], $counts)) => self::FOUR_OF_KIND,
			2 == count(array_intersect([3, 2], $counts)) => self::FULL_HOUSE,
			1 == count(array_intersect([3], $counts)) => self::THREE_KIND,
			2 == count(array_intersect($counts, [2, 2])) => self::TWO_PAIR,
			1 == count(array_intersect([2], $counts)) => self::ONE_PAIR,
			default => self::HIGH_CARD,
		};

		// If we're playing with jokers wild
		if($with_jokers && $num_jokers) {
			// Upgrade our hand progressively for each joker
			for($n=0;$n<$num_jokers;$n++) {
				$type = match($type) {
					self::HIGH_CARD => self::ONE_PAIR,
					self::ONE_PAIR => self::THREE_KIND,
					self::TWO_PAIR => self::FULL_HOUSE,
					self::THREE_KIND, self::FULL_HOUSE => self::FOUR_OF_KIND,
					self::FOUR_OF_KIND => self::FIVE_OF_KIND,
					default => $type,
				};
			}
		}
		return $type;
	}
}

// Build our hands
$hands = array_map(
	function($line) {
		$hand = array_combine(['cards','bid'], explode(' ', $line));
		// Cards as an array to make comparisons easier
		$hand['cards'] = str_split($hand['cards']);
		// Count card duplicates
		$hand['counts'] = array_count_values($hand['cards']);
		// Figure out our hand type by our cards
		$hand['type'] = HandType::getType($hand);
		return $hand;
	},
	$data
);

// Compare two hands using the given label values
$sort_hands = function($a, $b, array &$labels) {
	// If one hand type ranks better, it wins
	if($a['type']->value > $b['type']->value) return 1;
	if($b['type']->value > $a['type']->value) return -1;

	// If hand types are the same, compare cards left to right
	for($i=0;$i<5;$i++) {
		$a_label = $labels[$a['cards'][$i]];
		$b_label = $labels[$b['cards'][$i]];

		// If one card is better, it wins
		if($a_label < $b_label) return 1;
		if($b_label < $a_label) return -1;
	}

	// Otherwise we tied
	return 0;
};

// Sort labels for left-wise comparison (joker not wild)
$labels = array_flip(['A','K','Q','J','T',9,8,7,6,5,4,3,2]);
usort($hands, fn($a,$b) => $sort_hands($a,$b,$labels));

// Part 1: 251106089

echo "Part 1: " . array_sum(
	array_map(
		fn($rank) => ($rank+1)*$hands[$rank]['bid'],
		array_keys($hands)
	)
) . PHP_EOL;

// Part 2: 249620106

$hands = array_map(
	function($hand) {
		if($hand['counts']['J'] ?? false)
			$hand['type'] = HandType::getType($hand, with_jokers: true);
		return $hand;
	},
	$hands
);

// Sort jokers last in labels for left-wise comparison (jokers wild)
$labels = array_flip(['A','K','Q','T',9,8,7,6,5,4,3,2,'J']);
usort($hands, fn($a,$b) => $sort_hands($a,$b,$labels));

echo "Part 2: " . array_sum(
	array_map(
		fn($rank) => ($rank+1)*$hands[$rank]['bid'],
		array_keys($hands)
	)
) . PHP_EOL;