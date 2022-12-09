<?php
function simulateRope(array $steps, int $knots=1) : int {
    if($knots < 1) return 0;

    // Store the positions of each knot, default to origin (start)
    $knots = array_fill_keys(
        range(0,$knots-1),
        [0,0]
    );

    // Keep track of where our last knot has been, including start
    $visited  = ['0,0' => true];

    // Vectors for each direction we can move in two dimensions
    $vectors = [
        'U' => [0,-1],   // up
        'D' => [0,1],    // down
        'L' => [-1,0],   // left
        'R' => [1,0],    // right
        'UL' => [-1,-1], // up + left
        'UR' => [1,-1],  // up + right
        'DL' => [-1,1],  // down + left
        'DR' => [1,1],   // down + right
    ];

    // Run the step instructions
    foreach($steps as $step) {
        list($direction, $count) = explode(' ', $step);

        // For each step count
        while($count--) {
            // Move the head knot in that direction
            $knots[0][0] += $vectors[$direction][0];
            $knots[0][1] += $vectors[$direction][1];

            // For the remaining knots of the rope
            foreach(array_slice(array_keys($knots),1) as $knot) {
                // Vector between Cartesian coordinates of knot and parent
                $force = [
                    $knots[$knot-1][0] - $knots[$knot][0],
                    $knots[$knot-1][1] - $knots[$knot][1],
                ];

                // 2D Euclidean distance of knot and parent
                $distance = sqrt(pow($force[0], 2) + pow($force[1], 2));

                // Direction the knot is being pulled in by its parent (if any)
                // Pulled if not directly adjacent (1 step horizontal is sqrt(2))
                $pull = match(true) {
                    $force[1] < 0 && !$force[0] && $distance > 1 => 'U',
                    $force[1] > 0 && !$force[0] && $distance > 1 => 'D',
                    $force[0] < 0 && !$force[1] && $distance > 1 => 'L',
                    $force[0] > 0 && !$force[1] && $distance > 1 => 'R',
                    $force[1] < 0 && $force[0] < 0 && $distance > sqrt(2) => 'UL',
                    $force[1] < 0 && $force[0] > 0 && $distance > sqrt(2) => 'UR',
                    $force[1] > 0 && $force[0] < 0 && $distance > sqrt(2) => 'DL',
                    $force[1] > 0 && $force[0] > 0 && $distance > sqrt(2) => 'DR',
                    default => null,
                };

                // If the knot is being pulled, move it
                if($pull) {
                    $knots[$knot][0] += $vectors[$pull][0];
                    $knots[$knot][1] += $vectors[$pull][1];
                }
            }

            // Keep an idempotent hash of our tail position
            $visited[implode(',', $knots[array_key_last($knots)])] = true;
        }
    }

    // Return the number of distinct tail positions
    return count($visited);
}

$steps = explode("\n", file_get_contents('data.txt'));

echo 'Part 1: ', simulateRope($steps, 2), PHP_EOL;
echo 'Part 2: ', simulateRope($steps, 10), PHP_EOL;