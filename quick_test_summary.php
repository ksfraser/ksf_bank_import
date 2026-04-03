<?php
$xml = simplexml_load_file('test-results-2.xml');
$root = $xml->testsuite[0];
echo 'Tests: ' . $root['tests'] . PHP_EOL;
echo 'Failures: ' . $root['failures'] . PHP_EOL;
echo 'Errors: ' . $root['errors'] . PHP_EOL;
echo 'Passed: ' . ((int)$root['tests'] - (int)$root['failures'] - (int)$root['errors']) . PHP_EOL;
