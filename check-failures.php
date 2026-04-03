<?php
$xml = simplexml_load_file('test-results-statement-v2.xml');
echo "Failing tests:\n";
$failures = [];
foreach ($xml->testsuite->testcase as $test) {
    if ($test->failure || $test->error) {
        $name = (string)$test['name'];
        $msg = '';
        if ($test->error) {
            $msg = (string)$test->error;
        } elseif ($test->failure) {
            $msg = (string)$test->failure;
        }
        echo "\n[$name]\n";
        echo substr($msg, 0, 150) . (strlen($msg) > 150 ? '...' : '') . "\n";
        if (count($failures) < 5) {
            $failures[] = $name;
        }
    }
}
