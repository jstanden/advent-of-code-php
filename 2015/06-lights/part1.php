<?php
$instructions = explode("\n", file_get_contents('data.txt'));
$lights = array_fill_keys(range(0,999), array_fill_keys(range(0,999), 0));
$im = imagecreate(1000,1000);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
imagerectangle($im, 0, 0, 999, 999, $black);

foreach($instructions as $instruction) {
    sscanf($instruction, "%[a-z ] %d,%d through %d,%d", $command, $x1, $y1, $x2, $y2);

    for($x=$x1;$x<=$x2;$x++) {
        for($y=$y1;$y<=$y2;$y++) {
            $lights[$x][$y] = match(rtrim($command)) {
              'turn on' => 1,
              'turn off' => 0,
              'toggle' => $lights[$x][$y] ? 0 : 1,
            };
            imagesetpixel($im, $x, $y, $lights[$x][$y] ? $white : $black);
        }
    }
}

imagepng($im, 'part1.png');

echo array_sum(array_map(fn($column) => array_sum($column), $lights)), PHP_EOL;