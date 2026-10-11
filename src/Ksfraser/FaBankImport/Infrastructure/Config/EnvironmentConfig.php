<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Config;

/**
 * EnvironmentConfig - Infrastructure/Deployment Configuration Management
 * 
 * Handles loading and accessing infrastructure configuration from environment variables.
 * Distinct from app-level Config which manages runtime application settings.
 * 
 * Sources (priority order):
 * 1. Environment variables (highest priority)
 * 2. .env file (medium priority)
 * 3. Default values (lowest priority)
 * 
 * Use cases:
 * - Database credentials for migrations and integration tests
 * - Environment detection (dev, test, uat, production)
 * - Logging and deployment settings
 * 
 * Supports environment-based profiles: dev, test, uat, production
 * Provides type-safe accessors: getString, getInt, getBool
 * 
 * @author Kevin Fraser
 * @since 2.2.0
 */
final class EnvironmentConfig
{
    private array $config = [];
    private string $environment;

    public function __construct(?string $environment = null)
    {
        $this->environment = $environment ?? $_ENV['APP_ENV'] ?? 'development';
        $this->loadConfig();
    }

    /**
     * Load configuration from environment variables and .env file
     * Priority: Environment variables > .env file > defaults
     */
    private function loadConfig(): void
    {
        // Database config - check environment variables first, then default
        $this->config['database'] = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? '3306'),
            'name' => $_ENV['DB_NAME'] ?? 'fa_bank_import',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ];

        // Test database config (for integration/acceptance tests)
        $this->config['test_database'] = [
            'dsn' => $_ENV['TEST_DB_DSN'] ?? null,
            'user' => $_ENV['TEST_DB_USER'] ?? null,
            'password' => $_ENV['TEST_DB_PASS'] ?? null,
        ];

        // Application config
        $this->config['app'] = [
            'env' => $this->environment,
            'debug' => $this->parseBool($_ENV['APP_DEBUG'] ?? 'false'),
            'log_level' => $_ENV['APP_LOG_LEVEL'] ?? 'warning',
        ];

        // Logging config
        $this->config['logging'] = [
            'path' => $_ENV['LOG_PATH'] ?? sys_get_temp_dir() . '/ksf_bank_import.log',
            'enabled' => $this->parseBool($_ENV['LOG_ENABLED'] ?? 'true'),
        ];

        // Transaction config
        $this->config['transaction'] = [
            'max_amount' => (float)($_ENV['TRANSACTION_MAX_AMOUNT'] ?? '1000000.00'),
            'types' => explode(',', $_ENV['TRANSACTION_TYPES'] ?? 'C,D,B'),
        ];

        // Migration config
        $this->config['migrations'] = [
            'table' => $_ENV['MIGRATIONS_TABLE'] ?? 'schema_migrations',
            'path' => $_ENV['MIGRATIONS_PATH'] ?? 'database/migrations',
        ];
    }

    /**
     * Get a string configuration value
     * 
     * @param string $key Dot-notation key (e.g., 'database.host', 'app.env')
     * @param string|null $default Default value if not found
     * @return string
     * @throws ConfigException if required key not found
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);
        if ($value === null) {
            throw new ConfigException("Required configuration key not found: $key");
        }
        return (string)$value;
    }

    /**
     * Get an integer configuration value
     * 
     * @param string $key Dot-notation key
     * @param int|null $default Default value if not found
     * @return int
     * @throws ConfigException if required key not found
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);
        if ($value === null) {
            throw new ConfigException("Required configuration key not found: $key");
        }
        return (int)$value;
    }

    /**
     * Get a boolean configuration value
     * 
     * @param string $key Dot-notation key
     * @param bool|null $default Default value if not found
     * @return bool
     * @throws ConfigException if required key not found
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);
        if ($value === null) {
            throw new ConfigException("Required configuration key not found: $key");
        }
        return $this->parseBool($value);
    }

    /**
     * Get a raw configuration value (any type)
     * Supports dot-notation for nested keys: 'database.host'
     * 
     * @param string $key Dot-notation key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = $this->config;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Parse boolean-like string values
     */
    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return strtolower($value) === 'true'
                || strtolower($value) === '1'
                || strtolower($value) === 'yes'
                || strtolower($value) === 'on';
        }

        return (bool)$value;
    }

    /**
     * Check if running in test environment
     */
    public function isTest(): bool
    {
        return $this->environment === 'test';
    }

    /**
     * Check if running in development environment
     */
    public function isDevelopment(): bool
    {
        return $this->environment === 'development' || $this->environment === 'dev';
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool
    {
        return $this->parseBool($this->get('app.debug', false));
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get full database DSN for PDO connection
     */
    public function getDatabaseDsn(): string
    {
        $host = $this->getString('database.host');
        $port = $this->getInt('database.port');
        $name = $this->getString('database.name');
        $charset = $this->getString('database.charset');

        return "mysql:host=$host;port=$port;dbname=$name;charset=$charset";
    }

    /**
     * Get database credentials as array
     */
    public function getDatabaseCredentials(): array
    {
        return [
            'dsn' => $this->getDatabaseDsn(),
            'user' => $this->getString('database.user'),
            'password' => $this->getString('database.password'),
        ];
    }

    /**
     * Validate that all required configuration is present
     * 
     * @throws ConfigException if required configuration is missing
     */
    public function validate(): void
    {
        $required = [
            'database.host',
            'database.name',
            'database.user',
        ];

        foreach ($required as $key) {
            if ($this->get($key) === null) {
                throw new ConfigException("Missing required configuration: $key");
            }
        }
    }
}
