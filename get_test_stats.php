<?php
// Run PHPUnit and extract summary statistics
$output = shell_exec('php vendor\bin\phpunit phpunit.xml --no-coverage --colors=never 2>&1');
$lines = explode(PHP_EOL, $output);

// Get the last 100 lines where summary usually appears
$tail = array_slice($lines, -100);

foreach ($tail as $line) {
    // Look for the summary line with test counts
    if (preg_match('/^(OK|(FAILED|ERROR)|Tests:)/', trim($line)) || 
        preg_match('/(Tests|Errors|Failures|Skipped).*\d+/', trim($line))) {
        echo trim($line) . "\n";
    }
}

echo "\n---\n";
// Also look for the standardized format
foreach ($tail as $line) {
    if (preg_match('/\d+\s+(tests?|errors?|failures?|skipped)/i', $line)) {
        echo trim($line) . "\n";
    }
}
