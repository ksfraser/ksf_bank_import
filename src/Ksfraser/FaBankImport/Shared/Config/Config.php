<?php
namespace Ksfraser\FaBankImport\Shared\Config;

use Ksfraser\Exceptions\Domain\ConfigurationException;

/**
 * Config - Environment-aware configuration manager
 * 
 * Loads configuration from multiple sources with precedence:
 * 1. Environment variables (highest priority)
 * 2. Environment-specific config file (.env.$ENV)
 * 3. Default config file (.env)
 * 4. Programmatic defaults (lowest priority)
 * 
 * Supports hierarchical access via dot notation: `get('database.host')`
 * 
 * @package Ksfraser\FaBankImport\Shared\Config
 * @stable - Part of Shared Kernel API
 */
final class Config
{
    /** @var array<string, mixed> Configuration data */
    private array $config = [];

    /** @var string Current environment */
    private string $environment;

    /** @var bool Whether config has been loaded */
    private bool $isLoaded = false;

    /**
     * Private constructor - use static load() method
     */
    private function __construct(string $environment = 'prod')
    {
        $this->environment = $environment;
    }

    /**
     * Load configuration for environment
     * 
     * Loads configuration in order of precedence:
     * 1. Environment variables
     * 2. Environment-specific config file
     * 3. Default config file
     * 4. Programmatic defaults
     * 
     * @param string $environment Environment name (dev, test, prod, etc)
     * @param string $configDir Directory containing config files
     * @return self Configured instance
     * @throws ConfigurationException If config loading fails
     */
    public static function load(string $environment = 'prod', string $configDir = ''): self
    {
        $config = new self($environment);

        try {
            // Set defaults
            $config->setDefaults();

            // Load base .env file if exists
            if (empty($configDir)) {
                $configDir = dirname(__DIR__, 5); // Assume root
            }

            $envFile = $configDir . '/.env';
            if (is_file($envFile)) {
                $config->loadEnvFile($envFile);
            }

            // Load environment-specific file
            $envSpecificFile = $configDir . '/.env.' . $environment;
            if (is_file($envSpecificFile)) {
                $config->loadEnvFile($envSpecificFile);
            }

            // Load from environment variables (highest priority)
            $config->loadFromEnvironment();

            $config->isLoaded = true;
            return $config;
        } catch (\Exception $e) {
            throw new ConfigurationException("Failed to load configuration: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set default configuration values
     */
    private function setDefaults(): void
    {
        $this->config = [
            'app' => [
                'env' => $this->environment,
                'debug' => $this->environment === 'dev',
                'name' => 'KsfBankImport',
            ],
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'driver' => 'mysql',
            ],
            'services' => [
                'timeout' => 30,
                'retries' => 3,
            ],
            'transactions' => [
                'batch_size' => 100,
                'duplicate_check' => true,
            ],
        ];
    }

    /**
     * Load configuration from .env file
     * 
     * Parses simple KEY=value format (one per line)
     * Supports KEY_NESTED_PATH=value notation
     * 
     * @param string $filepath Path to .env file
     */
    private function loadEnvFile(string $filepath): void
    {
        if (!is_readable($filepath)) {
            throw new ConfigurationException("Config file not readable: {$filepath}");
        }

        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new ConfigurationException("Failed to read config file: {$filepath}");
        }

        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=value
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Convert underscore-separated keys to nested config
            $this->setNestedValue($key, $this->parseValue($value));
        }
    }

    /**
     * Load configuration from environment variables
     * 
     * Reads environment variables with APP_ prefix
     * Example: APP_DATABASE_HOST maps to config['database']['host']
     */
    private function loadFromEnvironment(): void
    {
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'APP_')) {
                // Remove APP_ prefix and convert to config path
                $configKey = strtolower(substr($key, 4));
                $this->setNestedValue($configKey, $this->parseValue($value));
            }
        }

        // Also check $_SERVER for common hosting environments
        foreach (['KSF_DEBUG', 'KSF_ENV', 'KSF_DB_HOST'] as $key) {
            if (isset($_SERVER[$key])) {
                $configKey = strtolower(substr($key, 4));
                $this->setNestedValue($configKey, $this->parseValue($_SERVER[$key]));
            }
        }
    }

    /**
     * Parse value from string (handles bool, int, json)
     * 
     * @param string $value Raw value
     * @return mixed Parsed value
     */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        // Remove quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        // Parse booleans
        if ($value === 'true' || $value === 'TRUE') return true;
        if ($value === 'false' || $value === 'FALSE') return false;

        // Parse integers
        if (ctype_digit($value)) return (int)$value;

        // Try parsing JSON
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * Set nested configuration value using dot notation
     * 
     * @param string $key Dot-separated key (e.g., 'database_host')
     * @param mixed $value Value to set
     */
    private function setNestedValue(string $key, mixed $value): void
    {
        $keys = explode('_', strtolower($key));
        $current = &$this->config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Get configuration value using dot notation
     * 
     * Examples:
     * - `get('app.env')`
     * - `get('database.host')`
     * - `get('services.timeout')`
     * 
     * @param string $key Dot-separated key
     * @param mixed $default Default if not found
     * @return mixed Configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (empty($key)) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $current = $this->config;

        foreach ($keys as $k) {
            if (is_array($current) && isset($current[$k])) {
                $current = $current[$k];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Dot-separated key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Set configuration value using dot notation
     * 
     * Useful for testing and runtime configuration updates.
     * 
     * @param string $key Dot-separated key
     * @param mixed $value Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config;

        // Navigate/create nested structure
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $k = $keys[$i];
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            if (!is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        // Set final value
        $current[$keys[count($keys) - 1]] = $value;
    }

    /**
     * Get configuration value as boolean
     * 
     * @param string $key Dot-separated key
     * @param bool $default Default if not found
     * @return bool Boolean value or default
     */
    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        return (bool)$value;
    }

    /**
     * Get configuration value as integer
     * 
     * @param string $key Dot-separated key
     * @param int $default Default if not found
     * @return int Integer value or default
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return (int)$value;
    }

    /**
     * Get configuration value as string
     * 
     * @param string $key Dot-separated key
     * @param string $default Default if not found
     * @return string String value or default
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return (string)$value;
    }

    /**
     * Get current environment
     * 
     * @return string Environment name
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if in development mode
     * 
     * @return bool True if env is 'dev' or 'development'
     */
    public function isDevelopment(): bool
    {
        return in_array($this->environment, ['dev', 'development', 'local'], true);
    }

    /**
     * Check if in testing mode
     * 
     * @return bool True if env is 'test'
     */
    public function isTesting(): bool
    {
        return $this->environment === 'test';
    }

    /**
     * Check if in production mode
     * 
     * @return bool True if env is 'prod' or 'production'
     */
    public function isProduction(): bool
    {
        return in_array($this->environment, ['prod', 'production'], true);
    }

    /**
     * Get all configuration
     * 
     * @return array Complete configuration array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get section of configuration
     * 
     * @param string $section Section name (e.g., 'database', 'services')
     * @return array Section configuration or empty array
     */
    public function getSection(string $section): array
    {
        return (array)$this->get($section, []);
    }
}
