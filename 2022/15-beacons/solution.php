<?php
// Jeff Standen - https://phpc.social/@jeff

// See: https://en.wikipedia.org/wiki/Taxicab_geometry#/media/File:TaxicabGeometryCircle.svg
//      https://en.wikipedia.org/wiki/Taxicab_geometry#Balls

function manhattanDistance($x1,$y1,$x2,$y2) : int {
    return abs($x2 - $x1) + abs($y2 - $y1);
}

// [TODO] This can be done better
function combineRanges(array $ranges) : array {
    $ranges = array_unique($ranges, SORT_REGULAR);
    sort($ranges);

    for($i=0;array_key_exists($i+1, $ranges);$i++) {
        if($ranges[$i+1][0] >= $ranges[$i][0] && $ranges[$i+1][1] <= $ranges[$i][1]) {
            unset($ranges[$i+1]);
            $i--;
            $ranges = array_values($ranges);

        } else if($ranges[$i+1][0] <= $ranges[$i][0] && $ranges[$i+1][1] >= $ranges[$i][0]) {
            $ranges[$i][0] = $ranges[$i + 1][0];
            unset($ranges[$i]);
            $i--;
            $ranges = array_values($ranges);

        } else if($ranges[$i+1][0] <= $ranges[$i][1] && $ranges[$i+1][1] >= $ranges[$i][1]) {
            $ranges[$i][1] = $ranges[$i+1][1];
            unset($ranges[$i+1]);
            $i--;
            $ranges = array_values($ranges);

        } else if($ranges[$i+1][0] >= $ranges[$i][0] && $ranges[$i+1][1] <= $ranges[$i][1]) {
            $ranges[$i][1] = $ranges[$i+1][1];
            unset($ranges[$i+1]);
            $i--;
            $ranges = array_values($ranges);
        }
    }

    return $ranges;
}

// Parse and format sensor data
$sensors = array_map(
    function($reading) {
        $sensor = [];
        sscanf(
            $reading,
            "Sensor at x=%d, y=%d: closest beacon is at x=%d, y=%d",
            $sensor['x'],
            $sensor['y'],
            $sensor['beacon_x'],
            $sensor['beacon_y']
        );

        // Cache our radius
        $sensor['dist'] = manhattanDistance(...array_values(array_slice($sensor, 0, 4)));

        // Cache our extents
        $sensor['extents'] = [
            'x' => [
                $sensor['x'] - $sensor['dist'],
                $sensor['x'] + $sensor['dist'],
            ],
            'y' => [
                $sensor['y'] - $sensor['dist'],
                $sensor['y'] + $sensor['dist'],
            ],
        ];

        return $sensor;
    },
//    explode("\n", file_get_contents('test.txt'))
    explode("\n", file_get_contents('data.txt'))
);

function sensorRangesAtTargetX(int $target_x, $min=PHP_INT_MIN, $max=PHP_INT_MAX) : array {
    global $sensors;
    $y_ranges = [];

    foreach($sensors as $sensor) {
        // Quickly exclude values outside of our radius
        if($target_x < $sensor['extents']['x'][0] || $target_x > $sensor['extents']['x'][1])
            continue;

        // Block out known beacons
        if($target_x == $sensor['beacon_x']) {
            $y_ranges[] = [$sensor['beacon_y'],$sensor['beacon_y']];
        }

        // Straight line x distance from our sensor to the target
        $target_dist = abs($sensor['x'] - $target_x);

        // What is its radius?
        $range = $sensor['dist'] - $target_dist;
        $y_min = $sensor['y']-$range;
        $y_max = $sensor['y']+$range;

        if($y_min > $max || $y_max < $min)
            continue;

        // Mark our sensor radius overlap in this y band
        $y_range = [
            max($min, $sensor['y']-$range),
            min($max, $sensor['y']+$range)
        ];

        $y_ranges[] = $y_range;
    }

    return combineRanges($y_ranges);
}

function sensorRangesAtTargetY(int $target_y, $min=PHP_INT_MIN, $max=PHP_INT_MAX) : array {
    global $sensors;
    $x_ranges = [];

    foreach($sensors as $sensor) {
        // Quickly exclude values outside of our radius
        if($target_y < $sensor['extents']['y'][0] || $target_y > $sensor['extents']['y'][1])
            continue;

        // Block out known beacons
        if($target_y == $sensor['beacon_y']) {
            $x_ranges[] = [$sensor['beacon_x'],$sensor['beacon_x']];
        }

        // Straight line x distance from our sensor to the target
        $target_dist = abs($sensor['y'] - $target_y);

        // What is its radius?
        $range = $sensor['dist'] - $target_dist;
        $x_min = $sensor['x']-$range;
        $x_max = $sensor['x']+$range;

        if($x_min > $max || $x_max < $min)
            continue;

        // Mark our sensor radius overlap in this x band
        $x_range = [
            max($min, $sensor['x']-$range),
            min($max, $sensor['x']+$range)
        ];

        $x_ranges[] = $x_range;
    }

    return combineRanges($x_ranges);
}

// Sort sensors by x min
usort($sensors, fn($a, $b) => $a['extents']['x'][0] <=> $b['extents']['x'][0]);

// Part 1: Find the length of the coverage on the Y axis at 2M
$sum = array_reduce(
    sensorRangesAtTargetY(2_000_000),
    fn($carry, $range) => $carry + array_sum(array_map('abs', $range)),
    0
);

$y = null;

for($x=0; $x<4_000_000; $x++) {
    $target_ranges = sensorRangesAtTargetX($x, 0, 4_000_000);

    if(count($target_ranges)-1) {
        //printf("X: %d Y: %s\n", $x, json_encode($target_ranges));
        $y = $target_ranges[0][1]+1;
        break;
    } else {
        printf("Scanned X: %d/%d...\n", $x, 4_000_000);
    }
}

printf("Part 1: %d\n", $sum);
printf("Part 2: %d\n", $x * 4_000_000 + $y);