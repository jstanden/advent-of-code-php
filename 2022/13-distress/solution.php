<?php
$packet_pairs = array_chunk(
    array_map(
        fn($line) => json_decode($line, true),
        array_filter(
            explode("\n", file_get_contents("data.txt")),
            fn($line) => !empty($line)
        )
    ),
    2
);

function compare($a, $b) : int {
    if (is_int($a) && is_int($b)) {
        return $a <=> $b;

    } elseif(!is_array($a) && is_array($b)) {
        return compare([$a], $b);

    } else if(is_array($a) && !is_array($b)) {
        return compare($a, [$b]);

    } elseif(is_array($a) && is_array($b)) {
        // Left ran out, stop.
        if(empty($a) && !empty($b)) {
            return -1;

        } else {
            $result = null;

            foreach(array_keys($a) as $index) {
                // If right ran out first, stop
                if(!array_key_exists($index, $b))
                    return 1;

                // If one side was definitively smaller, stop.
                if(0 != ($result = compare($a[$index], $b[$index])))
                    return $result;
            }

            // If only left was empty, stop. If both empty, keep going.
            if(!isset($result)) {
                return !empty($b) ? -1 : 0;

            // Left ran out first, stop.
            } elseif (count($a) < count($b)) {
                return -1;

            // Both sides are equal, keep going.
            } else {
                return 0;
            }
        }

    } else {
        die("Unhandled case.");
    }
}

// Count how many packet pairs were already in the right order
$sum = array_sum(
    array_map(
        fn($index) =>
            // If left is smaller, or left/right are identical, pair is good
            1 != compare($packet_pairs[$index][0], $packet_pairs[$index][1])
            // 1-based counting
            ? ($index + 1)
            : 0,
        array_keys($packet_pairs)
    )
);

// Merge the packets and divider packets into one array
$packet_pairs = array_merge([[[2]],[[6]]], ...$packet_pairs);

// Sort that array
usort($packet_pairs, 'compare');

// Output the sum of packets already in the right order
echo "Part 1: ", $sum, PHP_EOL;

// Return the product of the divider packets in the sorted list
echo "Part 2: ", array_reduce(
    // Index positions of the divider packets
    array_keys(
        // Only the divider packets
        array_filter(
            $packet_pairs,
            // Match the divider packets
            fn($packet) => in_array($packet, [[[2]],[[6]]])
        )
    ),
    // Multiply index values using 1-based counting
    fn($carry, $index) => $carry * ($index+1),
    1
), PHP_EOL;
