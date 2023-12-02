<?php // @jeff@phpc.social
declare(strict_types=1);

$data = explode("\n", file_get_contents('../../data/2023/01/data.txt'));

echo "Part 2: ", array_reduce($data, function($sum, $line) {
	$positions = []; // Keep track of term positions
	
	$terms = [ // Build a list of terms to find
		'zero','one','two','three','four','five','six','seven','eight','nine',
		...range(0, 9)
	];
	
	foreach($terms as $term) {
		$from = 0;
		// Find all term positions with a sliding window
		while(false !== ($i = strpos($line, strval($term), $from))) {
			// Replace spelled numbers with digits
			$positions[$i] = is_numeric($term) ? $term : array_search($term, $terms);
			// Resume from the end of a term
			$from += strlen(strval($term));
		}
	}
	
	// Sort the term positions in ascending order
	ksort($positions);
	
	// Make a number from the first/last digits and sum it
	return $sum + intval(reset($positions) . end($positions));
}, 0) . PHP_EOL;