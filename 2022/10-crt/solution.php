<?php
//$data = explode("\n", file_get_contents('test.txt'));
$data = explode("\n", file_get_contents('data.txt'));

$cycle = 1;
$x = 1;
$sum = 0;
$crt = array_fill_keys(range(0,239), '');

foreach($data as $instruction) {
    list($op, $v) = array_pad(explode(' ', $instruction), 2, null);

    // addx costs 2 cycles, noop costs 1
    if($op == 'addx') {
        $cost = 2;
    } else { // noop
        $cost = 1;
        $v = 0;
    }

    // Consume cycles
    do {
        // Sum the signal on the 20th cycle, then every 40
        if ($cycle == 20 || ($cycle > 40 && 0 == ($cycle-20) % 40)) {
            $sum += $cycle * $x;
        }

        // Update the CRT if our pixel is before, at, or after the register
        $crt[$cycle-1] = match(true) {
            // Repeat the sequence 0-39 for each CRT line
            in_array(($cycle-1) % 40, [$x-1,$x,$x+1]) => '#',
            default => '.',
        };

    } while(++$cycle && --$cost);

    // Update the register
    $x += $v;
}

echo "Part 1: ",  $sum, PHP_EOL;

echo "Part 2: ", PHP_EOL;
foreach(array_chunk($crt, 40) as $line)
    echo implode('', $line), PHP_EOL;