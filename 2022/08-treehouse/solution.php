<?php
// Load the matrix (rows of columns, Y/X)
$trees = array_map(
    fn($row) => str_split($row),
    explode("\n", file_get_contents("data.txt"))
);

// Rotate the matrix (columns of rows, X/Y)
$trees = array_map(
    fn($x) => array_column($trees, $x),
    array_keys($trees[0])
);

$extents = [0,count($trees[0])-1,0,count($trees)-1]; // inclusive
$visibility = [];
$max_scenic_score = 0;

// Visit each tree in the matrix
for($x=$extents[0]; $x <= $extents[1]; $x++) {
    for($y=$extents[2]; $y <= $extents[3]; $y++) {
        $height = $trees[$x][$y];

        // Lines of sight from this tree (reverse n+w to look outward)
        $directions = [
            'north' => array_reverse(array_slice($trees[$x], 0, $y)),
            'south' => array_slice($trees[$x], $y+1),
            'west' => array_reverse(array_slice(array_column($trees, $y), 0, $x)),
            'east' => array_slice(array_column($trees, $y), $x+1),
        ];

        // Try the shortest paths first
        asort($directions);

        // If any line of sight is visible to the edge, we're visible
        foreach($directions as $direction => $ray) {
            if(count($ray) == count(array_filter($ray, fn($h) => $h < $height))) {
                $visibility[$x][$y] = 1;
                break;
            }
        }

        // Is this our new best scenic score?
        $max_scenic_score = max(
            $max_scenic_score,
            array_reduce(
                // For each directional ray from this tree outward
                array_map(
                    function($ray) use ($height) {
                        // Find how many trees we can see until obstructed
                        return array_reduce(
                            $ray,
                            function($state, $tree) use ($height) {
                                // Don't count if we're already obstructed
                                if(!$state['visibility']) {
                                    return $state;
                                // If this is the first obstruction in this direction
                                } else if($tree >= $height) {
                                    // We can no longer see farther with this ray
                                    $state['visibility'] = 0;
                                }
                                // Count the tree if this reduction started unobstructed
                                $state['trees']++;
                                return $state;
                            },
                            // Initial reduction state
                            ['trees' => 0, 'visibility' => 1]
                        );
                    },
                    $directions
                ),
                // Collapse our directional counts into a score product
                fn($product, $stat) => ($product ?? 1) * $stat['trees']
            )
        );
    }
}

echo "Part 1: ", array_sum(array_map(fn($row) => array_sum($row), $visibility)), PHP_EOL;
echo "Part 2: ", $max_scenic_score, PHP_EOL;