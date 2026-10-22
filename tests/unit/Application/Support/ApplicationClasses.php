<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Support;

/**
 * Loads the classes of the generic MVC shell that composer cannot autoload.
 *
 * composer.json only maps the Http, Middleware, Interfaces and Models
 * sub directories of src/Ksfraser/Application (in lower case). The classes
 * living in
 *   src/Ksfraser/Application/config
 *   src/Ksfraser/Application/controllers
 *   src/Ksfraser/Application/database
 *   src/Ksfraser/Application/exceptions
 *   src/Ksfraser/Application/handlers
 *   src/Ksfraser/Application/repositories
 *   src/Ksfraser/Application/services
 * are therefore NOT resolvable through the PSR-4 autoloader on a case
 * sensitive file system, and
 *   src/Ksfraser/Application/services/PerformanceMonitoring.php
 * additionally declares PerformanceMonitor, so its file name does not match
 * its class name either.
 *
 * The tests load those files explicitly (in dependency order) so the classes
 * under test can be exercised. This is a test harness workaround for a
 * production autoloading defect - the defect itself is not fixed here.
 */
final class ApplicationClasses
{
    /**
     * Files that must be loaded, in dependency order.
     *
     * @var array
     */
    private const FILES = [
        'config/Config.php',
        'database/DatabaseFactory.php',
        'database/QueryBuilder.php',
        'exceptions/UnauthorizedException.php',
        'handlers/ErrorHandler.php',
        'services/EventDispatcher.php',
        'services/SimpleCommandBus.php',
        'services/MetricsAggregator.php',
        'services/PerformanceMonitoring.php',
        'services/TransactionLogger.php',
        'services/AlertService.php',
        'repositories/AbstractRepository.php',
        'controllers/AbstractController.php',
        'controllers/AdminController.php',
    ];

    /**
     * @var bool
     */
    private static $loaded = false;

    /**
     * Loads every class of the shell that the autoloader cannot resolve.
     *
     * @return string The base directory of the application sources.
     */
    public static function load(): string
    {
        $base = self::basePath();

        foreach (self::FILES as $file) {
            require_once $base . '/' . $file;
        }

        self::$loaded = true;

        return $base;
    }

    /**
     * @return string
     */
    public static function basePath(): string
    {
        return dirname(__DIR__, 4) . '/src/Ksfraser/Application';
    }

    /**
     * @return bool
     */
    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}

// Load the classes as soon as this file is required so that test files can
// declare subclasses of them at file scope.
ApplicationClasses::load();
