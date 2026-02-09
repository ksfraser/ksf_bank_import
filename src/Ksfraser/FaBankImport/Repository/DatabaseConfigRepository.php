<?php

namespace Ksfraser\FaBankImport\Repository;

/**
 * Database-backed Configuration Repository
 * 
 * Stores configuration in database table for production-safe management
 * Provides audit trail for all configuration changes
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DatabaseConfigRepository implements ConfigRepositoryInterface
{
    private const TABLE = 'bi_config';
    private const HISTORY_TABLE = 'bi_config_history';
    
    /** @var array In-memory cache of config values */
    private $cache = [];
    //private array $cache = [];
    
    /** @var bool Whether cache is loaded */
    private  $cacheLoaded = false;
    //private bool $cacheLoaded = false;
    
    /**
     * Get configuration value by key
     * 
     * @param string $key Configuration key with dot notation (e.g., 'upload.check_duplicates')
     * @param mixed $default Default value if not found
     * @return mixed Configuration value (typed according to config_type)
     */
    public function get(string $key, $default = null)
    {
        $this->loadCache();
        
        if (!isset($this->cache[$key])) {
            return $default;
        }
        
        return $this->cache[$key];
    }
    
    /**
     * Set configuration value with audit trail
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param string|null $username User making the change
     * @param string|null $reason Reason for change
     * @return bool Success
     * 
     * @throws \InvalidArgumentException If trying to modify system configuration
     */
    public function set(string $key, $value, ?string $username = null, ?string $reason = null): bool
    {
        // Check if this is a system configuration (cannot be modified)
        if ($this->isSystemConfig($key)) {
            throw new \InvalidArgumentException("Cannot modify system configuration: {$key}");
        }
        
        // Get old value for audit trail
        $oldValue = $this->get($key);
        
        // Convert value to string for storage
        $stringValue = $this->valueToString($value);
        
        // Update or insert configuration
        $sql = "INSERT INTO " . TB_PREF . self::TABLE . " 
                (config_key, config_value, updated_at, updated_by)
                VALUES (" . db_escape($key) . ", " . db_escape($stringValue) . ", NOW(), " . db_escape($username) . ")
                ON DUPLICATE KEY UPDATE 
                    config_value = " . db_escape($stringValue) . ",
                    updated_at = NOW(),
                    updated_by = " . db_escape($username);
        
        $result = db_query($sql, "Failed to update configuration");
        
        if ($result) {
            // Record change in history
            $this->recordHistory($key, $oldValue, $value, $username, $reason);
            
            // Update cache
            $this->cache[$key] = $this->castValue($value, $this->getConfigType($key));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all configuration values for a category
     * 
     * @param string $category Category name
     * @return array Configuration values
     */
    public function getByCategory(string $category): array
    {
        $sql = "SELECT config_key, config_value, config_type, description, is_system
                FROM " . TB_PREF . self::TABLE . "
                WHERE category = " . db_escape($category) . "
                ORDER BY config_key";
        
        $result = db_query($sql, "Failed to get configuration by category");
        
        $configs = [];
        while ($row = db_fetch($result)) {
            $configs[$row['config_key']] = [
                'value' => $this->castValue($row['config_value'], $row['config_type']),
                'type' => $row['config_type'],
                'description' => $row['description'],
                'is_system' => (bool)$row['is_system']
            ];
        }
        
        return $configs;
    }
    
    /**
     * Get all configuration values
     * 
     * @return array All configurations grouped by category
     */
    public function getAll(): array
    {
        $sql = "SELECT category, config_key, config_value, config_type, description, is_system
                FROM " . TB_PREF . self::TABLE . "
                ORDER BY category, config_key";
        
        $result = db_query($sql, "Failed to get all configuration");
        
        $configs = [];
        while ($row = db_fetch($result)) {
            $category = $row['category'];
            if (!isset($configs[$category])) {
                $configs[$category] = [];
            }
            
            $configs[$category][$row['config_key']] = [
                'value' => $this->castValue($row['config_value'], $row['config_type']),
                'type' => $row['config_type'],
                'description' => $row['description'],
                'is_system' => (bool)$row['is_system']
            ];
        }
        
        return $configs;
    }
    
    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public function has(string $key): bool
    {
        $this->loadCache();
        return isset($this->cache[$key]);
    }
    
    /**
     * Delete configuration value (if not system config)
     * 
     * @param string $key Configuration key
     * @return bool Success
     * 
     * @throws \InvalidArgumentException If trying to delete system configuration
     */
    public function delete(string $key): bool
    {
        if ($this->isSystemConfig($key)) {
            throw new \InvalidArgumentException("Cannot delete system configuration: {$key}");
        }
        
        $sql = "DELETE FROM " . TB_PREF . self::TABLE . "
                WHERE config_key = " . db_escape($key);
        
        $result = db_query($sql, "Failed to delete configuration");
        
        if ($result) {
            unset($this->cache[$key]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get configuration change history with audit trail
     * 
     * @param string|null $key Specific key (null for all)
     * @param int $limit Number of records
     * @return array History records
     */
    public function getHistory(?string $key = null, int $limit = 50): array
    {
        $sql = "SELECT config_key, old_value, new_value, changed_at, changed_by, change_reason
                FROM " . TB_PREF . self::HISTORY_TABLE;
        
        if ($key !== null) {
            $sql .= " WHERE config_key = " . db_escape($key);
        }
        
        $sql .= " ORDER BY changed_at DESC
                  LIMIT " . (int)$limit;
        
        $result = db_query($sql, "Failed to get configuration history");
        
        $history = [];
        while ($row = db_fetch($result)) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * Load all configuration into memory cache
     * Auto-creates tables on first use
     */
    private function loadCache(): void
    {
        if ($this->cacheLoaded) {
            return;
        }
        
        // Ensure tables exist (auto-migration on first use)
        $this->ensureTablesExist();
        
        $sql = "SELECT config_key, config_value, config_type
                FROM " . TB_PREF . self::TABLE;
        
        $result = db_query($sql, "Failed to load configuration");
        
        while ($row = db_fetch($result)) {
            $this->cache[$row['config_key']] = $this->castValue(
                $row['config_value'],
                $row['config_type']
            );
        }
        
        $this->cacheLoaded = true;
    }
    
    /**
     * Ensure database tables exist (auto-migration on first use)
     * 
     * @return void
     */
    private function ensureTablesExist(): void
    {
        // Check if config table exists
        $check = "SHOW TABLES LIKE '" . TB_PREF . self::TABLE . "'";
        $result = db_query($check);
        
        if (db_num_rows($result) === 0) {
            // Tables don't exist, create them
            $this->createTables();
            $this->insertDefaultValues();
        }
    }
    
    /**
     * Create database tables for configuration
     * 
     * @return void
     */
    private function createTables(): void
    {
        // Create bi_config table
        $sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . self::TABLE . "` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `config_key` varchar(100) NOT NULL,
          `config_value` text,
          `config_type` varchar(20) NOT NULL DEFAULT 'string',
          `description` text,
          `category` varchar(50) NOT NULL DEFAULT 'general',
          `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = cannot be changed via UI',
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `updated_by` varchar(60) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `config_key` (`config_key`),
          KEY `category` (`category`),
          KEY `is_system` (`is_system`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Bank Import Module Configuration'";
        
        db_query($sql, "Failed to create bi_config table");
        
        // Create bi_config_history table
        $sql = "CREATE TABLE IF NOT EXISTS `" . TB_PREF . self::HISTORY_TABLE . "` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `config_key` varchar(100) NOT NULL,
          `old_value` text,
          `new_value` text,
          `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `changed_by` varchar(60) NOT NULL,
          `change_reason` text,
          PRIMARY KEY (`id`),
          KEY `config_key` (`config_key`),
          KEY `changed_at` (`changed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Audit trail for configuration changes'";
        
        db_query($sql, "Failed to create bi_config_history table");
    }
    
    /**
     * Insert default configuration values
     * 
     * @return void
     */
    private function insertDefaultValues(): void
    {
        $defaults = [
            // Upload Configuration
            ['check_duplicates', '1', 'boolean', 'Enable duplicate file detection', 'upload', 0],
            ['duplicate_action', 'warn', 'string', 'Duplicate handling: allow, warn, or block', 'upload', 0],
            ['duplicate_window_days', '90', 'integer', 'Check for duplicates within X days', 'upload', 0],
            ['max_upload_size_mb', '100', 'integer', 'Maximum upload file size in MB', 'upload', 0],
            ['allowed_extensions', 'qfx,ofx,mt940,sta,csv', 'string', 'Comma-separated allowed file extensions', 'upload', 0],
            
            // Storage Configuration
            ['retention_days', '365', 'integer', 'Keep uploaded files for X days (0 = forever)', 'storage', 0],
            ['auto_delete_old_files', '0', 'boolean', 'Automatically delete old files based on retention', 'storage', 0],
            
            // Logging Configuration
            ['enable_audit_log', '1', 'boolean', 'Log all file uploads and deletions', 'logging', 0],
            ['log_duplicate_attempts', '1', 'boolean', 'Log duplicate file upload attempts', 'logging', 0],
            
            // Performance Configuration
            ['batch_size', '1000', 'integer', 'Process transactions in batches of X', 'performance', 0],
            ['memory_limit_mb', '256', 'integer', 'Memory limit for large file processing', 'performance', 0],
            
            // Security Configuration (system-protected)
            ['require_file_permission', '1', 'boolean', 'Require SA_BANKFILEVIEW permission', 'security', 1],
            ['allow_file_download', '1', 'boolean', 'Allow downloading of uploaded files', 'security', 1],
        ];
        
        foreach ($defaults as list($key, $value, $type, $description, $category, $isSystem)) {
            $sql = "INSERT INTO " . TB_PREF . self::TABLE . " 
                    (config_key, config_value, config_type, description, category, is_system, updated_by)
                    VALUES (
                        " . db_escape($key) . ",
                        " . db_escape($value) . ",
                        " . db_escape($type) . ",
                        " . db_escape($description) . ",
                        " . db_escape($category) . ",
                        " . db_escape($isSystem) . ",
                        'system'
                    )";
            
            db_query($sql, "Failed to insert default config: $key");
        }
    }
    
    /**
     * Cast string value to appropriate type
     * 
     * @param string $value String value from database
     * @param string $type Data type (string, integer, boolean, float)
     * @return mixed Typed value
     */
    private function castValue(string $value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool)(int)$value;
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'string':
            default:
                return $value;
        }
    }
    
    /**
     * Convert typed value to string for storage
     * 
     * @param mixed $value Typed value
     * @return string String representation
     */
    private function valueToString($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return (string)$value;
    }
    
    /**
     * Get configuration type for a key
     * 
     * @param string $key Configuration key
     * @return string Type (string, integer, boolean, float)
     */
    private function getConfigType(string $key): string
    {
        $sql = "SELECT config_type FROM " . TB_PREF . self::TABLE . "
                WHERE config_key = " . db_escape($key);
        
        $result = db_query($sql, "Failed to get config type");
        
        if ($row = db_fetch($result)) {
            return $row['config_type'];
        }
        
        return 'string';
    }
    
    /**
     * Check if configuration is system-only (cannot be modified)
     * 
     * @param string $key Configuration key
     * @return bool True if system config
     */
    private function isSystemConfig(string $key): bool
    {
        $sql = "SELECT is_system FROM " . TB_PREF . self::TABLE . "
                WHERE config_key = " . db_escape($key);
        
        $result = db_query($sql, "Failed to check system config");
        
        if ($row = db_fetch($result)) {
            return (bool)$row['is_system'];
        }
        
        return false;
    }
    
    /**
     * Record configuration change in history table
     * 
     * @param string $key Configuration key
     * @param mixed $oldValue Old value
     * @param mixed $newValue New value
     * @param string|null $username User making change
     * @param string|null $reason Reason for change
     */
    private function recordHistory(string $key, $oldValue, $newValue, ?string $username, ?string $reason): void
    {
        $sql = "INSERT INTO " . TB_PREF . self::HISTORY_TABLE . "
                (config_key, old_value, new_value, changed_by, change_reason)
                VALUES (
                    " . db_escape($key) . ",
                    " . db_escape($this->valueToString($oldValue)) . ",
                    " . db_escape($this->valueToString($newValue)) . ",
                    " . db_escape($username ?? 'system') . ",
                    " . db_escape($reason) . "
                )";
        
        db_query($sql, "Failed to record configuration history");
    }
}
