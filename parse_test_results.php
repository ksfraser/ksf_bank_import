<?php
/**
 * Parse PHPUnit XML results and output structured summary
 */

if (!file_exists('test-results.xml')) {
    echo "test-results.xml not found\n";
    exit(1);
}

$xml = simplexml_load_file('test-results.xml');

if (!$xml) {
    echo "Failed to parse XML\n";
    exit(1);
}

// Get root testsuite (first child if multiple)
$root = $xml->testsuite[0] ?? $xml;

$result = [
    'total' => (int)$root['tests'],
    'passed' => (int)$root['tests'] - ((int)$root['errors'] + (int)$root['failures']),
    'errors' => (int)$root['errors'],
    'failures' => (int)$root['failures'],
    'skipped' => (int)$root['skipped'],
    'time' => (float)$root['time'],
];

// Parse failures and errors
$failures = [];
$errors = [];

foreach ($root->testsuite as $suite) {
    foreach ($suite->testcase as $testcase) {
        $name = (string)$testcase['name'];
        $classname = (string)$testcase['class'];
        
        if ($testcase->error) {
            $errors[] = [
                'test' => $classname . '::' . $name,
                'message' => (string)$testcase->error['message'],
            ];
        }
        
        if ($testcase->failure) {
            $failures[] = [
                'test' => $classname . '::' . $name,
                'message' => (string)$testcase->failure['message'],
            ];
        }
    }
}

echo "================================\n";
echo "PHASE 1.2 REPOSITORY TEST RESULTS\n";
echo "================================\n\n";

echo "Summary:\n";
echo "--------\n";
echo "Total Tests: " . $result['total'] . "\n";
echo "Passed:      " . $result['passed'] . " ✓\n";
echo "Failed:      " . ($result['failures'] + $result['errors']) . " ✗\n";
echo "  - Failures: " . $result['failures'] . "\n";
echo "  - Errors:   " . $result['errors'] . "\n";
echo "Skipped:     " . $result['skipped'] . "\n";
echo "Time:        " . $result['time'] . "s\n\n";

if (!empty($errors)) {
    echo "ERRORS (" . count($errors) . "):\n";
    echo "------\n";
    foreach ($errors as $error) {
        echo "  ✗ " . $error['test'] . "\n";
        echo "    " . substr($error['message'], 0, 100) . "...\n\n";
    }
}

if (!empty($failures)) {
    echo "FAILURES (" . count($failures) . "):\n";
    echo "--------\n";
    foreach ($failures as $failure) {
        echo "  ✗ " . $failure['test'] . "\n";
        echo "    " . substr($failure['message'], 0, 100) . "...\n\n";
    }
}

if ($result['errors'] === 0 && $result['failures'] === 0) {
    echo "\n✅ ALL REPOSITORY TESTS PASSED!\n";
    echo "Phase 1.2 fixes validated successfully.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Review errors above.\n";
    exit(1);
}
