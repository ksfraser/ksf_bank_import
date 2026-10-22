<?php

declare(strict_types=1);

/**
 * Loads one class in a clean process and reports whether it can be declared.
 *
 * Usage: php class_load_runner.php <class-name>
 *
 * Exit code 0 = the class could be declared, 1 = it could not.
 */

if (!defined('KSF_TEST_COMPAT')) {
    define('KSF_TEST_COMPAT', true);
}

$class = isset($argv[1]) ? (string) $argv[1] : '';
if ($class === '') {
    fwrite(STDERR, "no class given\n");
    exit(2);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (class_exists($class) || interface_exists($class) || trait_exists($class)) {
    exit(0);
}

fwrite(STDERR, 'could not be declared: ' . $class . "\n");
exit(1);
