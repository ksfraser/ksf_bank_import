<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ConfigValueSourceResolver [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ConfigValueSourceResolver.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Config;

/**
 * Resolve configuration values from layered sources.
 *
 * Priority:
 * 1) Database-backed config service (`ConfigService`)
 * 2) In-memory/runtime config (`Config`)
 * 3) Module `config.php`
 * 4) Caller default
 */
final class ConfigValueSourceResolver
{
    /** @var array<string,mixed>|null */
    private static $moduleConfigCache = null;

    public static function resolve(string $dbKey, string $fileKey, $default = null)
    {
        $fromDb = self::getFromDbConfig($dbKey);
        if ($fromDb !== null) {
            return $fromDb;
        }

        $fromRuntime = self::getFromRuntimeConfig($dbKey);
        if ($fromRuntime !== null) {
            return $fromRuntime;
        }

        $fromFile = self::getFromModuleConfigFile($fileKey);
        if ($fromFile !== null) {
            return $fromFile;
        }

        return $default;
    }

    /**
     * @param array<int,string> $default
     * @return array<int,string>
     */
    public static function resolveArray(string $dbKey, string $fileKey, array $default): array
    {
        $value = self::resolve($dbKey, $fileKey, $default);
        return is_array($value) ? $value : $default;
    }

    private static function getFromDbConfig(string $key)
    {
        if (!class_exists('\\Ksfraser\\FaBankImport\\Config\\ConfigService')) {
            return null;
        }

        try {
            $service = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
            return $service->get($key, null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function getFromRuntimeConfig(string $key)
    {
        if (!class_exists('\\Ksfraser\\FaBankImport\\Config\\Config')) {
            return null;
        }

        try {
            return \Ksfraser\FaBankImport\Config\Config::getInstance()->get($key, null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function getFromModuleConfigFile(string $key)
    {
        $config = self::getModuleConfig();

        if (!is_array($config)) {
            return null;
        }

        return $config[$key] ?? null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function getModuleConfig(): ?array
    {
        if (self::$moduleConfigCache !== null) {
            return self::$moduleConfigCache;
        }

        $configFile = dirname(__DIR__, 5) . '/config.php';
        if (!is_file($configFile)) {
            self::$moduleConfigCache = [];
            return self::$moduleConfigCache;
        }

        $loaded = include $configFile;
        self::$moduleConfigCache = is_array($loaded) ? $loaded : [];
        return self::$moduleConfigCache;
    }
}
