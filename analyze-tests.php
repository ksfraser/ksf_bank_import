<?php

if (!file_exists('test-results.xml')) {
    echo "No test results found. Running tests...\n";
    passthru('php vendor/bin/phpunit phpunit.xml --log-junit=test-results.xml --no-coverage 2>&1');
}

if (!file_exists('test-results.xml')) {
    echo "Failed to generate test results\n";
    exit(1);
}

$xml = simplexml_load_file('test-results.xml');

echo "TEST SUMMARY\n";
echo "============\n\n";

echo "Total Tests: " . (int)$xml['tests'] . "\n";
echo "Failures: " . (int)$xml['failures'] . "\n";
echo "Errors: " . (int)$xml['errors'] . "\n";
echo "Skipped: " . (int)$xml['skipped'] . "\n";
echo "Passed: " . ((int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures']) . "\n\n";

// Parse failures and errors
$failures = [];
$errors = [];

foreach ($xml->testsuite as $suite) {
    foreach ($suite->testcase as $test) {
        if ($test->failure) {
            $failures[] = [
                'name' => (string)$test['name'],
                'class' => (string)$test['class'],
                'message' => (string)$test->failure['message'],
                'type' => 'failure'
            ];
        }
        if ($test->error) {
            $errors[] = [
                'name' => (string)$test['name'],
                'class' => (string)$test['class'],
                'message' => (string)$test->error['message'],
                'type' => 'error'
            ];
        }
    }
}

if (!empty($failures)) {
    echo "FAILURES (" . count($failures) . ")\n";
    echo "---------\n";
    foreach (array_slice($failures, 0, 10) as $f) {
        echo "\n[" . $f['class'] . "::" . $f['name'] . "]\n";
        echo "  " . substr($f['message'], 0, 100) . "...\n";
    }
    if (count($failures) > 10) {
        echo "\n... and " . (count($failures) - 10) . " more failures\n";
    }
}

if (!empty($errors)) {
    echo "\n\nERRORS (" . count($errors) . ")\n";
    echo "-------\n";
    foreach (array_slice($errors, 0, 10) as $e) {
        echo "\n[" . $e['class'] . "::" . $e['name'] . "]\n";
        echo "  " . substr($e['message'], 0, 100) . "...\n";
    }
    if (count($errors) > 10) {
        echo "\n... and " . (count($errors) - 10) . " more errors\n";
    }
}

// Output JSON for analysis
$summary = [
    'total' => (int)$xml['tests'],
    'passed' => (int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures'],
    'failures' => count($failures),
    'errors' => count($errors),
    'skipped' => (int)$xml['skipped'],
    'failure_list' => array_slice($failures, 0, 5),
    'error_list' => array_slice($errors, 0, 5),
];

echo "\n\nJSON Summary:\n";
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n";
