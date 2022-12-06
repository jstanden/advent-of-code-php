<?php
$instructions = explode("\n", file_get_contents('data.txt'));
$lights = array_fill_keys(range(0,999), array_fill_keys(range(0,999), 0));
$im = imagecreate(1000,1000);
$grays = [0 => imagecolorallocate($im, 0, 0, 0)];
imagerectangle($im, 0, 0, 999, 999, $grays[0]);

foreach($instructions as $instruction) {
    sscanf($instruction, "%[a-z ] %d,%d through %d,%d", $command, $x1, $y1, $x2, $y2);

    for($x=$x1;$x<=$x2;$x++) {
        for($y=$y1;$y<=$y2;$y++) {
            $lights[$x][$y] = max(0, $lights[$x][$y] + match(rtrim($command)) {
              'turn on' => 1,
              'turn off' => -1,
              'toggle' => 2,
            });

            $intensity = min(255, intval(255*$lights[$x][$y]/54));

            if(!array_key_exists($intensity, $grays))
                $grays[$intensity] = imagecolorallocate($im, $intensity, $intensity, $intensity);

            imagesetpixel($im, $x, $y, $grays[$intensity]);
        }
    }
}

imagepng($im, 'part2.png');

echo array_sum(array_map(fn($column) => array_sum($column), $lights)), PHP_EOL;