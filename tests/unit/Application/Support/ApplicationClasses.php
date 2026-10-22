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
 *   src/Ksfraser/Application/Services/PerformanceMonitoring.php
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
        'Config/Config.php',
        'Database/DatabaseFactory.php',
        'Database/QueryBuilder.php',
        'Exceptions/UnauthorizedException.php',
        'Handlers/ErrorHandler.php',
        'Http/RequestHandler.php',
        'Http/ResponseHandler.php',
        'Interfaces/CommandBusInterface.php',
        'Middleware/AdminMiddleware.php',
        'Middleware/AuthMiddleware.php',
        'Middleware/MiddlewareInterface.php',
        'Middleware/MiddlewarePipeline.php',
        'Middleware/PerformanceMonitoringMiddleware.php',
        'Repositories/AbstractRepository.php',
        'Services/AlertService.php',
        'Services/EventDispatcher.php',
        'Services/MetricsAggregator.php',
        'Services/PerformanceMonitor.php',
        'Services/SimpleCommandBus.php',
        'Services/TransactionLogger.php',
        'Controllers/AbstractController.php',
        'Controllers/AdminController.php',
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

        /*
         * The shell used to be loaded file by file because the composer
         * autoloader could not resolve it on a case sensitive file system.
         * The PSR-4 map is fixed now, so the classes are loaded the way
         * production loads them - and a directory name that drifts away from
         * its namespace segment fails here instead of silently.
         */
        foreach (self::FILES as $file) {
            $class = 'Ksfraser\\Application\\' . str_replace('/', '\\', substr($file, 0, -4));
            if (!class_exists($class) && !interface_exists($class)) {
                throw new \RuntimeException(
                    'Ksfraser\\Application class is not autoloadable: ' . $class
                );
            }
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
