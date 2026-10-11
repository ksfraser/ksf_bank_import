#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Bank Import CLI Application Entry Point
 * 
 * This script serves as the main entry point for CLI commands.
 * It bootstraps the application and dispatches commands.
 * 
 * Usage:
 *   php app.php train [options]
 *   php app.php train --help
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */

// Determine base path
$basePath = __DIR__;

// Load Composer autoloader
$autoloadPaths = [
    $basePath . '/vendor/autoload.php',
    dirname($basePath) . '/vendor/autoload.php',
    dirname($basePath) . '/../vendor/autoload.php',
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    fprintf(STDERR, "ERROR: Could not find composer autoloader\n");
    exit(1);
}

use Ksfraser\FaBankImport\Cli\Kernel;
use Ksfraser\FaBankImport\Cli\Commands\TrainingCommand;
use Ksfraser\FaBankImport\Cli\Commands\MigrationCommand;
use Ksfraser\FaBankImport\Infrastructure\Logger\FileLogger;
use Ksfraser\FaBankImport\Infrastructure\Error\ErrorHandler;
use Ksfraser\FaBankImport\Infrastructure\Factory\PartnerServiceFactory;

try {
    // Get PDO connection (would normally come from config)
    // For now, this is a placeholder - in production this would load from config
    $dsn = $_ENV['DB_DSN'] ?? 'mysql:host=localhost;dbname=angular_frontend_framework';
    $usuario = $_ENV['DB_USER'] ?? 'root';
    $contraseña = $_ENV['DB_PASS'] ?? '';
    
    $pdo = new PDO($dsn, $usuario, $contraseña);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create logger (can be changed to NullLogger for testing)
    $logPath = $_ENV['LOG_PATH'] ?? sys_get_temp_dir() . '/ksf_bank_import.log';
    $logger = new FileLogger($logPath);

    // Create error handler
    $errorHandler = new ErrorHandler($logger);

    // Create kernel and register commands
    $kernel = new Kernel($logger);
    
    // Create factory for services
    $factory = new PartnerServiceFactory();
    
    // Register training command
    $trainingService = $errorHandler->handle(
        fn() => new \Ksfraser\FaBankImport\Application\Partner\TrainingService(
            $factory->createPartnerRepository($pdo),
            $factory->createPartnerDataService($pdo)
        ),
        'creating training service'
    );
    
    $trainingCommand = new TrainingCommand($trainingService, $logger, $errorHandler);
    $kernel->register($trainingCommand);

    // Register migration command
    $migrationCommand = new MigrationCommand($logger);
    $kernel->register($migrationCommand);

    // Run the kernel
    exit($kernel->runFromArgv($argv));
} catch (\Throwable $e) {
    fprintf(STDERR, "FATAL ERROR: %s\n", $e->getMessage());
    fprintf(STDERR, "File: %s\n", $e->getFile());
    fprintf(STDERR, "Line: %s\n", $e->getLine());
    exit(1);
}
