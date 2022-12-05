<?php
$secret = 'bgvyzdsv';
$int = 0;

while(!str_starts_with(md5($secret.$int),'000000'))
    $int++;

echo $int, PHP_EOL;