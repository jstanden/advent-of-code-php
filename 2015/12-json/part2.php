<?php
$json = json_decode(file_get_contents('input.json'), false);

function countWithoutRed(mixed $node) : int {
	$sum = 0;
	
	foreach($node as $v) {
		if(is_object($v)) {
			$is_red = false;
			array_walk($v, function($vv) use (&$is_red) { if('red' === $vv) $is_red = true; } );
			if(!$is_red)
				$sum += countWithoutRed($v);
		} elseif (is_array($v)) {
			$sum += countWithoutRed($v);
		} elseif(is_int($v)) {
			$sum += $v;
		}
	}
	
	return $sum;
}

printf("Part 2: %d\n", countWithoutRed($json)); // 68466