<?php
// Run composer commands and capture output
$dir = 'c:/Users/prote/Documents/software-devel/ksf_bank_import';

echo "=== Clearing cache ===\n";
system('composer --working-dir="' . $dir . '" clear-cache 2>&1');

echo "\n=== Updating ksfraser/html ===\n";
system('composer --working-dir="' . $dir . '" update ksfraser/html --no-interaction 2>&1');

echo "\n=== Running tests ===\n";
system('php "' . $dir . '/vendor/phpunit/phpunit/phpunit" --configuration "' . $dir . '/phpunit.xml" 2>&1');
