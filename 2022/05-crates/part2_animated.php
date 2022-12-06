<?php
// Over-engineered for fun to show an animation for my kids
if(php_sapi_name() != 'cli') die("Must run at the command line.");
//$data = file_get_contents('test.txt');
$data = file_get_contents('data.txt');
$split_at = strpos($data,"\n\n");
$crates = explode("\n", substr($data, 0, $split_at));
$labels = str_split(array_pop($crates), 4);
$instructions = explode("\n", substr($data, $split_at+2));
$stacks = [];
$frame_ms = 100_000;
$max_height = 25;

// Matrix
$crates = array_map(fn($row) => array_pad(str_split($row . ' ',4), count($labels), '    '), $crates);

function print_stacks(array $crates, string $title='', array $colors=[]) : void {
	global $labels, $frame_ms;
	echo "\e[H\e[J"; // cls
	
	if($title) echo $title, PHP_EOL, PHP_EOL;
	
	foreach($crates as $row_idx => $row) {
		foreach($row as $crate_idx => $crate)
			echo ($colors[$row_idx][$crate_idx] ?? "\e[0m") . $crate;
		echo PHP_EOL;
	}
	echo "\e[0m" . implode('', $labels), PHP_EOL, PHP_EOL;
	
	usleep($frame_ms);
}

print_stacks($crates, 'Start');

foreach($instructions as $instruction) {
	usleep($frame_ms);
	sscanf($instruction, "move %d from %d to %d", $count, $from_stack, $to_stack);
	$from_stack--; $to_stack--; // zero-indexed
	
	$colors = [];
	
	// Remove empty top rows
	if(count($crates) > $max_height)
	while('' == trim(implode('', $crates[0])))
		array_shift($crates);
	
	$previous = $crates;
	$grabbed = [];
	
	// Grab multiple at a time
	for($row_from=0; $row_from<count($crates) && $count; $row_from++) {
		if(trim($crates[$row_from][$from_stack])) {
			$grabbed[] = $crates[$row_from][$from_stack];
			$crates[$row_from][$from_stack] = '    ';
			$colors[$row_from][$from_stack] = "\e[0;37m";
			$count--;
		}
	}
	
	// Drop
	foreach(array_reverse($grabbed) as $crate) {
		// If target is full, prepend a row
		if('' != trim($crates[0][$to_stack])) {
			$crates = array_merge([array_fill(0, count($labels), '    ')], $crates);
			$previous = array_merge([array_fill(0, count($labels), '    ')], $previous);
			$colors = array_merge([array_fill(0, count($labels), null)], $colors);
		}
		
		for($row_to=0; $row_to<count($crates); $row_to++) {
			if('' == trim($crates[$row_to][$to_stack]) && '' != trim($crates[$row_to+1][$to_stack] ?? '[END]')) {
				$crates[$row_to][$to_stack] = $crate;
				$previous[$row_to][$to_stack] = $crate;
				$colors[$row_to][$to_stack] = "\e[0;34m";
				break;
			}
		}
	}
	
	print_stacks($previous, $instruction, $colors);
}

print_stacks($crates, 'Finished');

echo "\e[0m";