<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Support;

use Ksfraser\Application\Config\Config;
use Ksfraser\Application\Services\PerformanceMonitor;

/**
 * Small helpers shared by the unit tests of the generic MVC shell.
 */
final class ApplicationTestSupport
{
    /** @var Config|null */
    private static $savedConfig;

    /** @var object|null */
    private static $savedPerformanceMonitor;

    /** @var object|null */
    private static $savedContainer;

    /**
     * Creates an empty temporary directory.
     *
     * @param string $prefix Directory name prefix.
     * @return string Absolute path of the created directory.
     */
    public static function createTempDir(string $prefix = 'ksf_app_'): string
    {
        $path = sys_get_temp_dir() . '/' . uniqid($prefix, true);
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create temporary directory ' . $path);
        }

        return $path;
    }

    /**
     * Removes a directory and everything below it.
     *
     * @param string $path Directory to remove.
     * @return void
     */
    public static function removeDir(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $child = $path . '/' . $item;
                if (is_dir($child)) {
                    self::removeDir($child);
                } else {
                    @unlink($child);
                }
            }
        }

        @rmdir($path);
    }

    /**
     * Replaces the configuration singleton with a fresh instance that has
     * the given overrides applied.
     *
     * @param array $overrides Dot separated key => value pairs.
     * @return Config The active configuration.
     */
    public static function useConfig(array $overrides = []): Config
    {
        if (self::$savedConfig === null) {
            self::$savedConfig = Config::getInstance();
        }

        Config::resetInstance();
        $config = Config::getInstance();

        foreach ($overrides as $key => $value) {
            $config->set((string) $key, $value);
        }

        return $config;
    }

    /**
     * Restores the configuration singleton that was active before
     * useConfig() was called.
     *
     * @return void
     */
    public static function restoreConfig(): void
    {
        if (self::$savedConfig !== null) {
            Config::setInstance(self::$savedConfig);
            self::$savedConfig = null;
        }
    }

    /**
     * Drops the performance monitor singleton so that a new instance picks up
     * the current configuration (the singleton has no reset method).
     *
     * @return void
     */
    public static function resetPerformanceMonitor(): void
    {
        $property = new \ReflectionProperty(PerformanceMonitor::class, 'instance');
        $property->setAccessible(true);

        if (self::$savedPerformanceMonitor === null) {
            $current = $property->getValue();
            self::$savedPerformanceMonitor = $current === null ? false : $current;
        }

        $property->setValue(null, null);
    }

    /**
     * Restores the performance monitor singleton.
     *
     * @return void
     */
    public static function restorePerformanceMonitor(): void
    {
        if (self::$savedPerformanceMonitor === null) {
            return;
        }

        $property = new \ReflectionProperty(PerformanceMonitor::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, self::$savedPerformanceMonitor === false ? null : self::$savedPerformanceMonitor);

        self::$savedPerformanceMonitor = null;
    }

    /**
     * Installs a fake database connection in the database factory.
     *
     * @param FakeDatabaseConnection $connection Connection to install.
     * @return void
     */
    public static function useDatabaseConnection(FakeDatabaseConnection $connection): void
    {
        $property = new \ReflectionProperty(\Ksfraser\Application\Database\DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, $connection);
    }

    /**
     * Removes the fake database connection again.
     *
     * @return void
     */
    public static function restoreDatabaseConnection(): void
    {
        \Ksfraser\Application\Database\DatabaseFactory::closeConnection();
    }

    /**
     * Drops the container singleton so that a new container picks up the
     * current configuration.
     *
     * @return void
     */
    public static function resetContainer(): void
    {
        $property = new \ReflectionProperty(\Ksfraser\Application\Container::class, 'instance');
        $property->setAccessible(true);

        if (self::$savedContainer === null) {
            $current = $property->getValue();
            self::$savedContainer = $current === null ? false : $current;
        }

        $property->setValue(null, null);
    }

    /**
     * Restores the container singleton.
     *
     * @return void
     */
    public static function restoreContainer(): void
    {
        if (self::$savedContainer === null) {
            return;
        }

        $property = new \ReflectionProperty(\Ksfraser\Application\Container::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, self::$savedContainer === false ? null : self::$savedContainer);

        self::$savedContainer = null;
    }
}
