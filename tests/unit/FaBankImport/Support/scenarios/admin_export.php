<?php

declare(strict_types=1);

/**
 * Scenario: exercises
 * \Ksfraser\FaBankImport\Controllers\AdminController::exportMetrics().
 *
 * The action sends two headers, which PHPUnit turns into an exception once
 * the test run has produced output, therefore it runs in its own process.
 */

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Controllers\AdminController;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeRequestHandler;

require_once __DIR__ . '/../FaBankImportStubs.php';

$logPath = isset($args[0]) ? (string) $args[0] : sys_get_temp_dir();

$config = Config::getInstance();
$config->set('logging.path', $logPath);

$request = new FakeRequestHandler();
FakeRequestHandler::$query = [
    'start_date' => date('Y-m-d', strtotime('-30 days')),
    'end_date' => date('Y-m-d'),
];

$controller = new AdminController();

$reflection = new ReflectionObject($controller);
$property = $reflection->getProperty('request');
$property->setAccessible(true);
$property->setValue($controller, $request);

$controller->exportMetrics();

echo "EXPORT_COMPLETED\n";
