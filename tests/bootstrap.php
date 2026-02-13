<?php

if (!ob_get_level()) {
	ob_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

// FrontAccounting function stubs for tests (not guarded)
require_once __DIR__ . '/helpers/fa_functions.php';

// Additional stubs/constants (guarded with function_exists/defined checks)
require_once __DIR__ . '/../includes/fa_stubs.php';

// Test-only class alias shims for legacy names
require_once __DIR__ . '/compat.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Initialize any test-specific configurations
define('FA_ROOT', dirname(__DIR__));

// Load test base classes (not autoloaded)
require_once __DIR__ . '/integration/DatabaseTestCase.php';
