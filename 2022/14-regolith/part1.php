<?php
const GENERATE_IMAGE = true;
const GENERATE_ANIMATION = false;

$data = array_map(
	fn($line) => array_map(
		fn($coords) => explode(',', $coords),
		explode(' -> ', $line),
	),
//    explode("\n", file_get_contents('test.txt'))
	explode("\n", file_get_contents('data.txt'))
);

// Find the extents

$extents = [PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MIN, PHP_INT_MIN];

foreach($data as $line) {
	if(empty($line)) continue;
	
	foreach($line as $coords) {
		// min/max x
		$extents[0] = min($extents[0], $coords[0]);
		$extents[2] = max($extents[2], $coords[0]);
		// min/max y
		$extents[1] = min($extents[1], $coords[1]);
		$extents[3] = max($extents[3], $coords[1]);
	}
}

$width = $extents[2];
$height = $extents[3];

$im = imagecreate($width-$extents[0], $height);
$color_text = imagecolorallocate($im, 255, 255, 255);
$color_rock = imagecolorallocate($im, 128, 128, 128);
$color_sand = imagecolorallocate($im, 228, 180, 80);
$color_entry = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);

imagesetpixel($im, 500, 0, $color_text); // Spawner

for($y=0; $y <= $height; $y++) {
	for ($x = 0; $x <= $width; $x++) {
		imagesetpixel($im, $x-$extents[0], $y, $black);
	}
}

// Fill the grid with air
$grid = array_fill_keys(
	range(0, $width),
	array_fill_keys(range(0,$height), '.')
);

// Set up rock barriers
foreach($data as $rock_lines) {
	$cursor = array_shift($rock_lines);
	
	while($coords = array_shift($rock_lines)) {
		$vector = [$coords[0]-$cursor[0], $coords[1]-$cursor[1]];
		
		if($vector[0]) {
			foreach(range($cursor[0], $cursor[0] + $vector[0]) as $x) {
				$grid[$x][$cursor[1]] = '#';
				imagesetpixel(
					$im,
					$x-$extents[0],
					$cursor[1],
					$color_rock
				);
			}
		} else if($vector[1]) {
			foreach(range($cursor[1], $coords[1]) as $y) {
				$grid[$cursor[0]][$y] = '#';
				imagesetpixel(
					$im,
					$cursor[0]-$extents[0],
					$y,
					$color_rock
				);
			}
		}
		$cursor = $coords;
	}
}

// Spawn sand particles
for($count=0; $count < 1_000; $count++) {
	$sand = [500,0];
	$was = [0,0];
	
	// Keep moving until we're blocked
	while($was != $sand) {
		$was = $sand;
		
		// Go down as far as we can in air blocks
		while('.' == ($grid[$sand[0]][$sand[1]+1] ?? '.')) {
			if(++$sand[1] >= $extents[3])
				break;
		}
		
		// If we can go down + left
		if('.' == ($grid[$sand[0]-1][$sand[1]+1] ?? '.')) {
			$sand[0]--; $sand[1]++;
			
		// Or down + right
		} else if('.' == ($grid[$sand[0]+1][$sand[1]+1] ?? '.')) {
			$sand[0]++; $sand[1]++;
		}
		
		// If we're outside the map, stop adding sand particles
		if($sand[0] <= $extents[0] || $sand[0] >= $extents[2] || $sand[1] > $extents[3])
			break 2;
	}
	
	// Place our sand here
	$grid[$sand[0]][$sand[1]] = 'o';
	imagesetpixel($im, $sand[0]-$extents[0], $sand[1], $color_sand);
	
	if(GENERATE_ANIMATION) {
		$frame = imagescale($im, ($width - $extents[0]) * 5, $height * 5, IMG_NEAREST_NEIGHBOUR);
		$color_output = imagecolorallocate($frame, 255, 255, 255);
		imagettftext($frame, 15, 0, 225, 35, $color_output, 'font/Roboto-Medium.ttf', 'Part 1: ' . $count + 1);
		imagettftext($frame, 8, 0, 225, 50, $color_output, 'font/Roboto-Medium.ttf', '@jeff@phpc.social');
		imagepng($frame, sprintf('animation/cave_%05d.png', $count));
		imagedestroy($frame);
	}
}

// Visualize the rock barriers in ASCII
for($y=0;$y<=$height;$y++) {
    for($x=$extents[0]-1;$x<=$width;$x++) {
        $cell = $grid[$x][$y] ?? '.';

        if($x == 500 && $y == 0)
            $cell = '+';

        echo $cell;
    }
    echo PHP_EOL;
}

echo "Part 1: ", $count, PHP_EOL;

if(GENERATE_IMAGE) {
	$final = imagescale($im, ($width - $extents[0]) * 5, $height * 5, IMG_NEAREST_NEIGHBOUR);
	$color_output = imagecolorallocate($final, 255, 255, 255);
	imagettftext($final, 15, 0, 225, 35, $color_output, 'font/Roboto-Medium.ttf', 'Part 1: ' . $count);
	imagettftext($final, 8, 0, 225, 50, $color_output, 'font/Roboto-Medium.ttf', '@jeff@phpc.social');
	imagepng($final, 'part1.png');
	imagedestroy($im);
	imagedestroy($final);
}