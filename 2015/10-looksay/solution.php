<?php
function lookAndSay(string $input, int $iterations) : int {
	do {
		$output = '';
		$at = 0;
		
		while(preg_match('/(.)\1*/', $input, $matches, 0, $at)) {
			$output .= strlen($matches[0]) . $matches[1];
			$at += strlen($matches[0]);
		}
		
		$input = $output;
	} while (--$iterations);
	
	return strlen($input);
}

$input = '1321131112';

$part1 = lookAndSay($input, 40);
printf("Part 1: %d\n", $part1); // 492982

$part2 = lookAndSay($input, 50);
printf("Part 2: %d\n", $part2); // 6989950