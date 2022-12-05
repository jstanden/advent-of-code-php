<?php
$stairs = file_get_contents('data.txt');
$floor = 0;

for($x = 0; $x < strlen($stairs); $x++) {
    if(-1 == ($floor += ($stairs[$x] == '(' ? 1 : -1))) {
        echo ++$x, PHP_EOL;
        exit;
    }
}