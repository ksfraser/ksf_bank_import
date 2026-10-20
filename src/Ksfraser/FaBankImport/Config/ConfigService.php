<?php

namespace Ksfraser\FaBankImport\Config;

use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;

/**
 * @BABOK RTM: FR-002 / FR-051 / ARCH-001; file: Config/ConfigService.php; coverage: docs/TestCases/ (file rollup)
 * Configuration Service (Refactored)
 * 
 * Now uses Repository Pattern with database storage
 * Follows Dependency Injection principle
 * 
 * @author Kevin Fraser
 * @since 2.0.0 - Refactored to use DI and Repository pattern
 */
class ConfigService
{
    private static ?self $instance = null;
    
    private ConfigRepositoryInterface $repository;
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: __construct
     * Constructor - Dependency Injection
     * 
     * @param ConfigRepositoryInterface|null $repository Configuration repository (injected)
     */
    private function __construct(?ConfigRepositoryInterface $repository = null)
    {
        // Use injected repository or create default
        $this->repository = $repository ?? new DatabaseConfigRepository();
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: getInstance
     * Get singleton instance (for backward compatibility)
     * 
     * @deprecated Use dependency injection instead
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: create
     * Create instance with custom repository (for DI)
     * 
     * @param ConfigRepositoryInterface $repository Custom repository
     * @return self New instance
     */
    public static function create(ConfigRepositoryInterface $repository): self
    {
        return new self($repository);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: get
     * Get configuration value
     * 
     * Supports dot notation: 'upload.check_duplicates'
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     * 
     * @example
     * ```php
     * $config = ConfigService::getInstance();
     * $enabled = $config->get('upload.check_duplicates', false);
     * ```
     */
    public function get(string $key, $default = null)
    {
        return $this->repository->get($key, $default);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: set
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param string|null $username User making change (for audit)
     * @param string|null $reason Reason for change (for audit)
     * @return bool Success
     * 
     * @throws \InvalidArgumentException If trying to modify system configuration
     * 
     * @example
     * ```php
     * $config->set('upload.check_duplicates', true, 'admin', 'Enable duplicate checking');
     * ```
     */
    public function set(string $key, $value, ?string $username = null, ?string $reason = null): bool
    {
        return $this->repository->set($key, $value, $username, $reason);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: getByCategory
     * Get all configuration values for a category
     * 
     * @param string $category Category name
     * @return array Configuration values
     */
    public function getByCategory(string $category): array
    {
        return $this->repository->getByCategory($category);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: getAll
     * Get all configuration values
     * 
     * @return array All configurations
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: has
     * Check if configuration exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public function has(string $key): bool
    {
        return $this->repository->has($key);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: getHistory
     * Get configuration change history
     * 
     * @param string|null $key Specific key or null for all
     * @param int $limit Number of records
     * @return array History records
     */
    public function getHistory(?string $key = null, int $limit = 50): array
    {
        return $this->repository->getHistory($key, $limit);
    }
    
    /**
 * @BABOK FR-002 / FR-051 / ARCH-001 — method: getRepository
     * Get repository instance (for advanced usage)
     * 
     * @return ConfigRepositoryInterface
     */
    public function getRepository(): ConfigRepositoryInterface
    {
        return $this->repository;
    }
}
