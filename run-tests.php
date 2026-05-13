<?php

/**
 * Test Runner - Execute PHPUnit tests with configurable filters
 * 
 * Usage:
 *   php run-tests.php                    # Run all tests
 *   php run-tests.php SupplierMatching   # Run SupplierMatching tests
 *   php run-tests.php all                # Run all tests (full suite)
 *   php run-tests.php phase7             # Run Phase 7 tests
 *   php run-tests.php phase76            # Run Phase 7.6 tests only
 */

$filter = $argv[1] ?? '';
$testsuite = 'Unit (Legacy)';

// Base PHPUnit command
$baseCommand = sprintf(
    'php .\\vendor\\bin\\phpunit --configuration phpunit.xml --colors=never --no-coverage --testsuite "%s"',
    $testsuite
);

// Add filter if provided
if ($filter && $filter !== 'all') {
    $baseCommand .= sprintf(' --filter "%s"', $filter);
}

echo "================================================\n";
echo "TEST RUNNER\n";
echo "================================================\n";
echo "\nFilter: " . ($filter ?: 'NONE (all tests)') . "\n";
echo "Test Suite: {$testsuite}\n";
echo "\nCommand:\n";
echo "{$baseCommand}\n";
echo "\n================================================\n\n";

passthru($baseCommand);
