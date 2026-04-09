<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Initialize any test-specific configurations
define('FA_ROOT', dirname(__DIR__));

// Load environment variables from .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and invalid lines
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Only set if not already set
        if (!getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
}

// Auto-run database migrations for integration tests
// This ensures the test database schema is always up-to-date
register_shutdown_function(function () {
    // Optional: Clean up migrations
});

// Load test base classes (not autoloaded)
require_once __DIR__ . '/integration/DatabaseTestCase.php';
