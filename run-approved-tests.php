<?php

/**
 * PHPUnit Test Runner
 * 
 * Reads test-filters.conf and executes each filter in sequence.
 * Edit test-filters.conf to change which tests run.
 * Approve this script once, then just edit the config file.
 */

$configFile = __DIR__ . '/test-filters.conf';

if (!file_exists($configFile)) {
    echo "ERROR: test-filters.conf not found\n";
    exit(1);
}

$lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    echo "ERROR: Could not read test-filters.conf\n";
    exit(1);
}

$filters = [];
foreach ($lines as $line) {
    $line = trim($line);
    // Skip comments and empty lines
    if (empty($line) || substr($line, 0, 2) === '//') {
        continue;
    }
    $filters[] = $line;
}

if (empty($filters)) {
    echo "ERROR: No filters found in test-filters.conf\n";
    exit(1);
}

echo "================================================\n";
echo "PHPUNIT TEST RUNNER\n";
echo "================================================\n";
echo "Running " . count($filters) . " filter(s):\n";
foreach ($filters as $filter) {
    echo "  - {$filter}\n";
}
echo "\n================================================\n\n";

foreach ($filters as $filter) {
    echo "\n>>> Running filter: {$filter}\n";
    echo "================================================\n";
    
    $command = sprintf(
        'php .\\vendor\\bin\\phpunit --configuration phpunit.xml --colors=never --no-coverage --testsuite "Unit (Legacy)" --filter "%s"',
        $filter
    );
    
    passthru($command);
}

echo "\n================================================\n";
echo "ALL FILTERS COMPLETE\n";
echo "================================================\n";
