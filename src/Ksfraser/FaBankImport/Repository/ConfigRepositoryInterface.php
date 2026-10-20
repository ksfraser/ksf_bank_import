<?php

namespace Ksfraser\FaBankImport\Repository;

/**
 * @BABOK CLASS ROLLUP: ConfigRepositoryInterface — 7 methods; FR-008 (7)
 * @BABOK RTM: FR-008 / ARCH-001; file: Repository/ConfigRepositoryInterface.php; coverage: docs/TestCases/ (file rollup)
 * Configuration Repository Interface
 * 
 * Defines contract for configuration storage/retrieval
 * Follows Repository Pattern for database abstraction
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
interface ConfigRepositoryInterface
{
    /**
 * @BABOK FR-008 / ARCH-001 — method: get
     * Get configuration value by key
     * 
     * @param string $key Configuration key (e.g., 'upload.check_duplicates')
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null);
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: set
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param string|null $username User making the change
     * @param string|null $reason Reason for change (audit trail)
     * @return bool Success
     */
    public function set(string $key, $value, ?string $username = null, ?string $reason = null): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: getByCategory
     * Get all configuration values for a category
     * 
     * @param string $category Category name (e.g., 'upload', 'security')
     * @return array Associative array of config key => value
     */
    public function getByCategory(string $category): array;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: getAll
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public function getAll(): array;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: has
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public function has(string $key): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: delete
     * Delete configuration value
     * 
     * @param string $key Configuration key
     * @return bool Success
     */
    public function delete(string $key): bool;
    
    /**
 * @BABOK FR-008 / ARCH-001 — method: getHistory
     * Get configuration change history
     * 
     * @param string|null $key Specific key (null for all)
     * @param int $limit Number of records to return
     * @return array Configuration history
     */
    public function getHistory(?string $key = null, int $limit = 50): array;
}
