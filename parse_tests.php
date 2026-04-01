<?php
/**
 * Run PHPUnit with XML output and parse to JSON summary
 */
$cmd = 'php vendor\bin\phpunit phpunit.xml --log-junit=test-results.xml --no-coverage 2>&1';
shell_exec($cmd);

// Parse XML results
if (!file_exists('test-results.xml')) {
    echo "ERROR: test-results.xml not created\n";
    exit(1);
}

$xml = simplexml_load_file('test-results.xml');

$summary = [
    'total' => (int)$xml['tests'],
    'passed' => (int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures'],
    'errors' => (int)$xml['errors'],
    'failures' => (int)$xml['failures'],
    'skipped' => (int)$xml['skipped'],
];

// Get test suites with failures/errors
$failures = [];
foreach ($xml->testsuite as $suite) {
    $suite_name = (string)$suite['name'];
    $suite_errors = (int)$suite['errors'];
    $suite_failures = (int)$suite['failures'];
    
    if ($suite_errors > 0 || $suite_failures > 0) {
        $failures[$suite_name] = [
            'errors' => $suite_errors,
            'failures' => $suite_failures
        ];
    }
}

echo json_encode([
    'summary' => $summary,
    'by_suite' => $failures,
    'status' => ($summary['errors'] === 0 && $summary['failures'] === 0) ? 'PASS' : 'FAIL'
], JSON_PRETTY_PRINT);
