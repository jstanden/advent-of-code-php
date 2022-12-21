<?php
//$lines = explode("\n", file_get_contents("test.txt"));
$lines = explode("\n", file_get_contents("data.txt"));

class ListNumber {
	public int $value;
	public int $index;
	
	function __construct(int $value, int $index) {
		$this->value = $value;
		$this->index = $index;
	}
}

$originalOrder = [];
$decryptedOrder = [];
$numberLength = count($lines);
$zero = null;

// Build the lists
foreach($lines as $index => $value) {
	$number = new ListNumber($value, $index);
	$originalOrder[] = $number;
	$decryptedOrder[] = $number;
	if(0 == $value) $zero = $number;
}

// Move the numbers in the original order
foreach($originalOrder as $number) {
	if(0 == $number->value) continue;
	
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
	} else if($number->value > 0 && $move_to < $numberLength) {
		array_splice($decryptedOrder, $move_to + 1, 0, [$number]);
		unset($decryptedOrder[$move_from]);
	}
	
	$decryptedOrder = array_values($decryptedOrder);
}

$zero_at = array_search($zero, $decryptedOrder);
$x = $decryptedOrder[($zero_at+1000) % $numberLength]->value;
$y = $decryptedOrder[($zero_at+2000) % $numberLength]->value;
$z = $decryptedOrder[($zero_at+3000) % $numberLength]->value;

printf("Part 1: %d\n", $x + $y + $z);