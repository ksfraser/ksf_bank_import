<?php
/**
 * Parse PHPUnit XML results to extract clean summary
 */
$output = shell_exec('php vendor\bin\phpunit phpunit.xml --log-junit="test-results.xml" --no-coverage 2>&1');

if (file_exists('test-results.xml')) {
    $xml = simplexml_load_file('test-results.xml');
    
    $tests = (int)$xml['tests'];
    $failures = (int)$xml['failures'];
    $errors = (int)$xml['errors'];
    $skipped = (int)$xml['skipped'];
    
    echo "TEST SUMMARY\n";
    echo "============\n";
    echo "Tests:     $tests\n";
    echo "Errors:    $errors\n";
    echo "Failures:  $failures\n";
    echo "Skipped:   $skipped\n";
    echo "Passed:    " . ($tests - $errors - $failures - $skipped) . "\n";
    echo "\nStatus: " . (($errors === 0 && $failures === 0) ? "✓ PASS" : "✗ FAIL") . "\n";
} else {
    echo "Could not find test-results.xml\n";
    echo "\nLast 50 lines of output:\n";
    $lines = explode(PHP_EOL, $output);
    foreach (array_slice($lines, -50) as $line) {
        if (trim($line)) echo $line . "\n";
    }
}
