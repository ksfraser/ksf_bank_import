<?php
namespace Ksfraser\FaBankImport\Shared\Config;

/**
 * ConfigFactory - Lightweight config initialization factory
 * 
 * Provides convenient methods for initializing Config in different contexts:
 * - Web application context: auto-detect environment
 * - Testing context: override with test-specific values
 * - CLI context: read from command line or environment
 * 
 * @package Ksfraser\FaBankImport\Shared\Config
 * @internal Factory helper - use Config::load() for direct use
 */
final class ConfigFactory
{
    /**
     * Create config for current environment
     * 
     * Auto-detects environment from:
     * 1. KSF_ENV environment variable
     * 2. APP_ENV environment variable
     * 3. Defaults to 'prod'
     * 
     * @param string $configDir Optional override for config directory
     * @return Config Configured instance
     */
    public static function create(string $configDir = ''): Config
    {
        $env = $_ENV['KSF_ENV'] ?? $_ENV['APP_ENV'] ?? $_SERVER['KSF_ENV'] ?? 'prod';
        return Config::load($env, $configDir);
    }

    /**
     * Create testing config with overrides
     * 
     * Loads test environment config and applies additional overrides
     * useful for isolated testing scenarios.
     * 
     * @param array<string, mixed> $overrides Test-specific overrides
     * @param string $configDir Optional config directory
     * @return Config Configured test instance
     */
    public static function testing(array $overrides = [], string $configDir = ''): Config
    {
        $config = Config::load('test', $configDir);
        
        // Apply test-specific overrides
        foreach ($overrides as $key => $value) {
            $config->set($key, $value);
        }

        return $config;
    }

    /**
     * Create development config
     * 
     * Optimized for development with debugging enabled
     * 
     * @param string $configDir Optional config directory
     * @return Config Development config instance
     */
    public static function development(string $configDir = ''): Config
    {
        return Config::load('dev', $configDir);
    }

    /**
     * Create production config
     * 
     * @param string $configDir Optional config directory
     * @return Config Production config instance
     */
    public static function production(string $configDir = ''): Config
    {
        return Config::load('prod', $configDir);
    }
}
