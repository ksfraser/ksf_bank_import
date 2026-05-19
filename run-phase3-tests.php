#!/usr/bin/env php
<?php
/**
 * Run Phase 3 Service Tests
 */

$workingDir = __DIR__;
chdir($workingDir);

// Load composer autoloader
require_once 'vendor/autoload.php';

// Run PHPUnit
$argv = [
    'phpunit',
    'tests/unit/Services/BiLineItemServiceTest.php',
    '--no-coverage',
];

$_SERVER['argv'] = $argv;
$_SERVER['argc'] = count($argv);

// Import PHPUnit's main function
require_once 'vendor/bin/phpunit';
