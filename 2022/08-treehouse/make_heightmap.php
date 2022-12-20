<?php
// Load rows of columns
$data = array_map(
	fn($row) => str_split($row),
	//explode("\n", file_get_contents("test.txt"))
	explode("\n", file_get_contents("data.txt"))
);

// Flip to columns of rows (X/Y)
$map = array_map(
	fn($col) => array_column($data, $col),
	array_keys($data[0])
);

$im = imagecreate(count($map), count($map[0]));
$colors = [];

foreach($map as $x => $row) {
	foreach($row as $y => $height) {
		$intensity = floor(255 * ($height/9));
		
		if(!array_key_exists($intensity, $colors))
			$colors[$intensity] = imagecolorallocate($im, $intensity, $intensity, $intensity);
		
		imagesetpixel($im, $x, $y, $colors[$intensity]);
	}
}

$im = imagescale($im, count($map) * 5, count($map[0]) * 5, IMG_NEAREST_NEIGHBOUR);

imagepng($im, 'heightmap.png');