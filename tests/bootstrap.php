<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../test_helpers/fa_db_mock.php';
require_once __DIR__ . '/../test_helpers/DatabaseFactoryMock.php';

// Set up test environment
$_ENV['APP_ENV'] = 'testing';

// Initialize error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Reset the FaDbMock before each test run
\Ksfraser\FaBankImport\TestHelpers\FaDbMock::reset();

// Override DatabaseFactory with mock version
class_alias('\Ksfraser\FaBankImport\Database\DatabaseFactoryMock', '\Ksfraser\FaBankImport\Database\DatabaseFactory');

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
    // Reset mock state
    \Ksfraser\FaBankImport\TestHelpers\FaDbMock::reset();
});