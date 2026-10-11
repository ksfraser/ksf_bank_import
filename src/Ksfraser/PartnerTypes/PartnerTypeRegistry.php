<?php

/**
 * Partner Type Registry
 *
 * Dynamically discovers and registers all partner type implementations.
 * Scans the PartnerTypes directory for classes implementing PartnerTypeInterface.
 *
 * @package    Ksfraser\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Partner Type Registry
 *
 * Singleton registry that auto-discovers partner types from the filesystem.
 * Provides methods to retrieve partner types by code, constant name, or get all types.
 *
 * Example usage:
 * ```php
 * $registry = PartnerTypeRegistry::getInstance();
 * 
 * // Get by short code
 * $supplier = $registry->getByCode('SP');
 * echo $supplier->getLabel(); // "Supplier"
 * 
 * // Get all types
 * foreach ($registry->getAll() as $type) {
 *     echo $type->getShortCode() . ': ' . $type->getLabel();
 * }
 * 
 * // Validate code
 * if ($registry->isValid('SP')) { ... }
 * ```
 */
class PartnerTypeRegistry
{
    /**
     * @var PartnerTypeRegistry|null Singleton instance
     */
    private static $instance = null;

    /**
     * @var array<string, PartnerTypeInterface> Partner types indexed by short code
     */
    private $types = [];

    /**
     * @var array<string, PartnerTypeInterface> Partner types indexed by constant name
     */
    private $typesByConstant = [];

    /**
     * @var bool Whether types have been loaded
     */
    private $loaded = false;

    /**
     * Private constructor for singleton
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance
     *
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
     * Reset singleton instance (primarily for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Load all partner types from directory
     *
     * Scans the PartnerTypes directory for PHP files, instantiates classes
     * that implement PartnerTypeInterface, and registers them.
     *
     * @return void
     */
    private function loadTypes(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $directory = __DIR__;

        // Scan directory for PHP files
        $files = glob($directory . '/*.php');
        
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $basename = basename($file, '.php');
            
            // Skip interfaces, abstract classes, and this registry
            if ($basename === 'PartnerTypeInterface' || 
                $basename === 'AbstractPartnerType' ||
                $basename === 'PartnerTypeRegistry') {
                continue;
            }

            // Build fully qualified class name
            $className = __NAMESPACE__ . '\\' . $basename;

            // Check if class exists and implements interface
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);
            
            // Skip abstract classes
            if ($reflection->isAbstract()) {
                continue;
            }

            // Check if implements interface
            if (!$reflection->implementsInterface(PartnerTypeInterface::class)) {
                continue;
            }

            // Instantiate and register
            try {
                $instance = new $className();
                $this->register($instance);
            } catch (\Throwable $e) {
                // Skip types that can't be instantiated
                // In production, you might want to log this
                continue;
            }
        }

        // Sort by priority
        uasort($this->types, function (PartnerTypeInterface $a, PartnerTypeInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    /**
     * Register a partner type
     *
     * @param PartnerTypeInterface $type The partner type to register
     * @return void
     * @throws \InvalidArgumentException If short code already registered
     */
    public function register(PartnerTypeInterface $type): void
    {
        $code = $type->getShortCode();
        $constant = $type->getConstantName();

        if (isset($this->types[$code])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Partner type with code "%s" is already registered',
                    $code
                )
            );
        }

        $this->types[$code] = $type;
        $this->typesByConstant[$constant] = $type;
    }

    /**
     * Get partner type by short code
     *
     * @param string $code Two-character short code
     * @return PartnerTypeInterface|null Partner type or null if not found
     */
    public function getByCode(string $code): ?PartnerTypeInterface
    {
        $this->loadTypes();
        return $this->types[$code] ?? null;
    }

    /**
     * Get partner type by constant name
     *
     * @param string $constantName Constant name (e.g., 'SUPPLIER')
     * @return PartnerTypeInterface|null Partner type or null if not found
     */
    public function getByConstant(string $constantName): ?PartnerTypeInterface
    {
        $this->loadTypes();
        return $this->typesByConstant[$constantName] ?? null;
    }

    /**
     * Get all registered partner types
     *
     * Returns types sorted by priority (lowest first).
     *
     * @return array<string, PartnerTypeInterface> Partner types indexed by short code
     */
    public function getAll(): array
    {
        $this->loadTypes();
        return $this->types;
    }

    /**
     * Check if a short code is valid
     *
     * @param string $code Short code to validate
     * @return bool True if valid, false otherwise
     */
    public function isValid(string $code): bool
    {
        $this->loadTypes();
        return isset($this->types[$code]);
    }

    /**
     * Get label for a short code
     *
     * @param string $code Short code
     * @return string Label or 'Unknown' if not found
     */
    public function getLabel(string $code): string
    {
        $type = $this->getByCode($code);
        return $type !== null ? $type->getLabel() : 'Unknown';
    }

    /**
     * Get all short codes
     *
     * @return array<string> Array of short codes
     */
    public function getCodes(): array
    {
        $this->loadTypes();
        return array_keys($this->types);
    }

    /**
     * Get count of registered types
     *
     * @return int Number of registered types
     */
    public function count(): int
    {
        $this->loadTypes();
        return count($this->types);
    }
}
