<?php
/**
 * Quick test suite counter - counts pass/fail without verbose output
 */

// Run phpunit with JSON output
$cmd = 'cd ' . escapeshellarg(__DIR__) . ' && php ./vendor/bin/phpunit --no-coverage --log-json=test-results.json 2>&1';
echo "Running tests (this will take ~3 minutes)...\n";
$output = shell_exec($cmd);

// Parse JSON results
if (file_exists(__DIR__ . '/test-results.json')) {
    $results = json_decode(file_get_contents(__DIR__ . '/test-results.json'), true);
    if ($results) {
        echo "\n=== TEST RESULTS ===\n";
        echo "Tests run: " . ($results['tests'] ?? 'unknown') . "\n";
        echo "Failures: " . ($results['failures'] ?? 0) . "\n";
        echo "Errors: " . ($results['errors'] ?? 0) . "\n";
        echo "Skipped: " . ($results['skipped'] ?? 0) . "\n";
        
        $passed = ($results['tests'] ?? 0) - (($results['failures'] ?? 0) + ($results['errors'] ?? 0));
        echo "PASSED: $passed\n";
        
        if ($results['tests'] > 0) {
            $pct = round(($passed / $results['tests']) * 100, 1);
            echo "Pass Rate: {$pct}%\n";
        }
    }
} else {
    echo "Test results file not found\n";
}

// Show last few lines of output
$lines = explode("\n", trim($output));
echo "\nLast 20 lines of output:\n";
echo implode("\n", array_slice($lines, -20));
