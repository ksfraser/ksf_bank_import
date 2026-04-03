<?php
$xml = simplexml_load_file('test-results-statement.xml');
echo 'Total: ' . $xml->testsuite['tests'] . "\n";
echo 'Passed: ' . ($xml->testsuite['tests'] - $xml->testsuite['failures'] - $xml->testsuite['errors']) . "\n";
echo 'Failures: ' . $xml->testsuite['failures'] . "\n";
echo 'Errors: ' . $xml->testsuite['errors'] . "\n";
echo "\nFirst 5 test failures/errors:\n";
$count = 0;
foreach ($xml->testsuite->testcase as $test) {
  if ($test->failure || $test->error) {
    echo "\n[$count] Test: " . $test['name'] . "\n";
    if ($test->failure) {
      $msg = (string)$test->failure;
      echo 'Failure: ' . substr($msg, 0, 200) . (strlen($msg) > 200 ? '...' : '') . "\n";
    }
    if ($test->error) {
      $msg = (string)$test->error;
      echo 'Error: ' . substr($msg, 0, 200) . (strlen($msg) > 200 ? '...' : '') . "\n";
    }
    $count++;
    if ($count >= 5) break;
  }
}
