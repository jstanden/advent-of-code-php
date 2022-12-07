<?php
//$commands = explode("\n", file_get_contents('test.txt'));
$commands = explode("\n", file_get_contents('data.txt'));
$cwd = [];
$fs = [];

// Recursive disk usage
function du(string $path) : int {
    global $fs;
    return array_sum(array_map(
        // Recurse directories or add cwd file sizes
        fn($f) => str_ends_with($f, '/') ? du($path . $f) : explode(' ', $f)[1],
        $fs[$path]
    ));
}

// Parse console history
while($command = current($commands)) {
    if(str_starts_with($command, '$ ')) { // user input
        $argv = explode(' ', substr($command, 2));

        // Change directory command
        if('cd' == $argv[0]) {
            if('/' == $argv[1]) {
                $cwd = ['/'];
            } else if('..' == $argv[1]) {
                array_pop($cwd);
            } else {
                $cwd[] = $argv[1] . '/';
            }

        // List cwd command
        } elseif('ls' == $argv[0]) {
            $path = implode('', $cwd);

            if(!array_key_exists($path, $fs))
                $fs[$path] = [];

            do {
                if(false === ($output = next($commands))) break 2;
                if(str_starts_with($output, '$ ')) break;
                $fstat = explode(' ', $output);

                // Index files or directories, latter ending with /
                $fs[$path][] = match($fstat[0]) {
                    'dir' => $fstat[1] . '/',
                    default => $fstat[1] . ' ' . $fstat[0],
                };
            } while(true);

            // Back up the pointer to process the next command
            prev($commands);
        }
    }

    next($commands);
}

// Recursively build a hash of directory path sizes

$directory_sizes = array_combine(
    array_keys($fs),
    array_map(fn($path) => du($path), array_keys($fs)),
);

// Part 1: Output the sum of directories with recursive size <= 100KB

echo "Part 1: ", array_reduce(
    array_filter(
        $directory_sizes,
        // Only keep directories under the target size
        fn($size) => $size <= 100_000,
    ),
    // Reduce by adding the total sizes together
    fn($total, $size) => $total + $size,
    0
), PHP_EOL;

// Part 2: Find the smallest directory sufficient to free up space

$space_total = 70_000_000;
$update_total = 30_000_000;
$space_used = du('/');
$space_needed = max(0, -1*($space_total-$space_used-$update_total));

echo "Part 2: ", min(array_filter($directory_sizes, fn($size) => $size >= $space_needed)), PHP_EOL;