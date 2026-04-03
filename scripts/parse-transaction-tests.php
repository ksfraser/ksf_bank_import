<?php
/**
 * Parse TransactionRepository test XML results
 */

$xmlFile = __DIR__ . '/../test-results-transaction.xml';

if (!file_exists($xmlFile)) {
    die("Test results file not found. Run tests first with: --log-junit=$xmlFile\n");
}

try {
    $xml = simplexml_load_file($xmlFile);
    
    $summary = [
        'total_tests' => (int)$xml['tests'],
        'passed' => (int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures'],
        'failures' => (int)$xml['failures'],
        'errors' => (int)$xml['errors'],
        'skipped' => (int)$xml['skipped'],
        'time' => (float)$xml['time']
    ];
    
    echo "=== TransactionRepository Test Results ===\n";
    echo json_encode($summary, JSON_PRETTY_PRINT) . "\n\n";
    
    // List failures
    if ($xml->testsuite->testcase) {
        $failures = [];
        foreach ($xml->testsuite->testcase as $case) {
            if ($case->failure || $case->error) {
                $failures[] = [
                    'name' => (string)$case['name'],
                    'class' => (string)$case['class'],
                    'type' => $case->failure ? 'failure' : 'error',
                    'message' => $case->failure ? (string)$case->failure['message'] : (string)$case->error['message']
                ];
            }
        }
        
        if ($failures) {
            echo "=== FAILED TESTS ===\n";
            echo json_encode($failures, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error parsing XML: {$e->getMessage()}\n";
    exit(1);
}
