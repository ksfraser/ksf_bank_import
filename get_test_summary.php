<?php
$xml = simplexml_load_file('results.xml');
$attrs = $xml->attributes();
echo "Tests: " . (int)$attrs['tests'] . PHP_EOL;
echo "Passed: " . ((int)$attrs['tests'] - (int)$attrs['errors'] - (int)$attrs['failures']) . PHP_EOL;
echo "Errors: " . (int)$attrs['errors'] . PHP_EOL;
echo "Failures: " . (int)$attrs['failures'] . PHP_EOL;
echo "Skipped: " . (int)$attrs['skipped'] . PHP_EOL;

echo "\n=== First 10 Errors ===\n";
$count = 0;
foreach ($xml->testsuite as $suite) {
    foreach ($suite->testcase as $case) {
        if ($count >= 10) break 2;
        if (isset($case->error)) {
            echo $case['name'] . " (" . $suite['name'] . ")\n";
            echo "  " . trim((string)$case->error['type']) . "\n";
            $count++;
        }
    }
}

echo "\n=== First 10 Failures ===\n";
$count = 0;
foreach ($xml->testsuite as $suite) {
    foreach ($suite->testcase as $case) {
        if ($count >= 10) break 2;
        if (isset($case->failure)) {
            echo $case['name'] . " (" . $suite['name'] . ")\n";
            $count++;
        }
    }
}
