<?php
$xml = simplexml_load_file('test-results.xml');
echo json_encode([
    'tests' => (int)$xml['tests'],
    'passed' => (int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures'],
    'errors' => (int)$xml['errors'],
    'failures' => (int)$xml['failures'],
    'skipped' => (int)$xml['skipped'],
    'time' => (float)$xml['time'],
], JSON_PRETTY_PRINT);
