<?php
/**
 * Quick test runner to capture and output test summary
 */

chdir(__DIR__);

// Capture output from test run
ob_start();
passthru('php vendor/bin/phpunit --no-coverage 2>&1');
$output = ob_get_clean();

// Extract key metrics from output
if (preg_match('/(\d+)\s+tests?,\s+(\d+)\s+failures?,\s+(\d+)\s+skipped?/i', $output, $matches)) {
    $tests = $matches[1];
    $failures = $matches[2];
    $skipped = $matches[3];
    $passed = $tests - $failures - $skipped;
    $percentage = ($passed / $tests) * 100;
    
    echo "\n========================================\n";
    echo "TEST SUMMARY\n";
    echo "========================================\n";
    echo "Total Tests: $tests\n";
    echo "Passed: $passed\n";
    echo "Failed: $failures\n";
    echo "Skipped: $skipped\n";
    echo "Pass Rate: " . number_format($percentage, 1) . "%\n";
    echo "========================================\n\n";
} elseif (preg_match('/OK\s*\(\s*(\d+)\s+tests?,\s+(\d+)\s+assertions?\s*\)/i', $output, $matches)) {
    echo "\n========================================\n";
    echo "ALL TESTS PASSING!\n";
    echo "========================================\n";
    echo "Tests Passed: " . $matches[1] . "\n";
    echo "Assertions: " . $matches[2] . "\n";
    echo "Pass Rate: 100%\n";
    echo "========================================\n\n";
} else {
    // Fallback: just show last 100 lines
    echo "\n========================================\n";
    echo "RAW OUTPUT (Last 100 lines):\n";
    echo "========================================\n";
    $lines = explode("\n", $output);
    $lastLines = array_slice($lines, -100);
    echo implode("\n", $lastLines);
    echo "\n========================================\n";
}
