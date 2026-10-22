<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\Application\Application.
 *
 * Application::run() needs a request handler that exposes isPost(), which
 * the production RequestHandler does not have, so the scenario runs with the
 * request handler test double.
 */

use Ksfraser\Application\Application;
use Ksfraser\Application\Config\Config;
use Ksfraser\Application\Controllers\BankImportController;
use Ksfraser\Application\Http\RequestHandler;

$mode = isset($args[0]) ? (string) $args[0] : 'index';
$logPath = isset($args[1]) ? (string) $args[1] : sys_get_temp_dir();

$config = Config::getInstance();
$config->set('logging.path', $logPath);

$_SESSION = ['user_id' => 1, 'is_admin' => true];

RequestHandler::$isPost = ($mode === 'process');
BankImportController::$shouldThrow = ($mode === 'error');

$application = new Application();
$application->run();

echo "APP_COMPLETED";
