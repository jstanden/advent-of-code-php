<?php
//$lines = explode("\n", file_get_contents("test.txt"));
$data = explode("\n", file_get_contents("data.txt"));

class ListNumber {
	public int $value;
	public int $index;
	
	function __construct(int $value, int $index) {
		$this->value = $value;
		$this->index = $index;
	}
}

function mix(array $data, int $loops=1, int $multiplier=1) : int {
	$originalOrder = [];
	$decryptedOrder = [];
	$zero = null;

	// Build the lists
	foreach($data as $index => $value) {
		$number = new ListNumber($value * $multiplier, $index);
		$originalOrder[] = $number;
		$decryptedOrder[] = $number;
		if(0 == $value) $zero = $number;
	}
	
	$listLength = count($originalOrder);
	
	for($i=0; $i < $loops; $i++) {
		// Move the numbers in the original order
		foreach($originalOrder as $number) {
			if(0 == $number->value)
				continue;
			
			$move_from = array_search($number, $decryptedOrder, true);
			$move_to = ($move_from + $number->value) % array_key_last($decryptedOrder);
			
			// Euclidean remainder
			if($move_to < 0)
				$move_to += array_key_last($decryptedOrder);
			
			// Move backward and wrap
			if($number->value < 0 && $move_to > $move_from) {
				array_splice($decryptedOrder, $move_to + 1, 0, [$number]);
				unset($decryptedOrder[$move_from]);
				
			// Move backward w/o wrapping
			} else if($number->value < 0 && $move_to >= 0) {
				array_splice($decryptedOrder, $move_to, 0, [$number]);
				unset($decryptedOrder[$move_from+1]);
				
			// Move forward and wrap
			} else if($number->value > 0 && $move_to < $move_from) {
				array_splice($decryptedOrder, $move_to, 0, [$number]);
				unset($decryptedOrder[$move_from+1]);
				
			// Move forward w/o wrapping
			} else if($number->value > 0 && $move_to < $listLength) {
				array_splice($decryptedOrder, $move_to + 1, 0, [$number]);
				unset($decryptedOrder[$move_from]);
			}
			
			$decryptedOrder = array_values($decryptedOrder);
		}
	}
	
	$zero_at = array_search($zero, $decryptedOrder);
	$x = $decryptedOrder[($zero_at+1000) % $listLength]->value;
	$y = $decryptedOrder[($zero_at+2000) % $listLength]->value;
	$z = $decryptedOrder[($zero_at+3000) % $listLength]->value;
	
	return $x + $y + $z;
}

printf("Part 1: %d\n", mix($data));
printf("Part 2: %d\n", mix($data, 10, 811589153));