<?php
// Run PHPUnit and capture output
$output = shell_exec('php vendor\bin\phpunit phpunit.xml --no-coverage --colors=never 2>&1');
$lines = explode(PHP_EOL, $output);

echo "Total lines: " . count($lines) . "\n";
echo "Last 40 lines:\n";
echo "================\n";

$tail = array_slice($lines, -40);
foreach ($tail as $i => $line) {
    echo sprintf("%3d: %s\n", $i, trim($line));
}
