<?php /** @noinspection DuplicatedCode */
//$instructions = explode("\n", file_get_contents("test.txt"));
$instructions = explode("\n", file_get_contents("data.txt"));
$instructions = array_map(fn($inst) => explode(' -> ', $inst), $instructions);

$wires = array_combine(
  array_column($instructions, 1),
  array_column($instructions, 0),
);

$signals = [];

function getWireValue(string $key) : int {
    global $wires, $signals;

    if(array_key_exists($key, $signals))
        return $signals[$key];

    $argv = explode(' ', $wires[$key]);
    $argc = count($argv);

    echo $key, ' = ', $wires[$key], PHP_EOL;

    if(1 == $argc) {
        $a = is_numeric($argv[0]) ? $argv[0] : getWireValue($argv[0]);
        $signal = intval($a);
    } elseif (2 == $argc) { // unary
        $a = is_numeric($argv[1]) ? $argv[1] : getWireValue($argv[1]);
        $signal = match($argv[0]) {
            'NOT' => 65535 - $a,
        };
    } elseif (3 == $argc) { // binary
        $a = (is_numeric($argv[0]) ? $argv[0] : getWireValue($argv[0]));
        $b = (is_numeric($argv[2]) ? $argv[2] : getWireValue($argv[2]));
        $signal = match($argv[1]) {
            'AND' => $a & $b,
            'OR' => $a | $b,
            'LSHIFT' => $a << $b,
            'RSHIFT' => $a >> $b,
        };
    } else {
        die("Unknown instruction: " . $wires[$key]);
    }

    $signals[$key] = $signal;
    return $signal;
}

echo getWireValue('a'), PHP_EOL;