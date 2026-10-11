<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'fa_bank_import_test';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';

// Initialize error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean up test artifacts
register_shutdown_function(function() {
    // Clean up any test files or resources
    if (isset($_SESSION)) {
        session_destroy();
    }
});
