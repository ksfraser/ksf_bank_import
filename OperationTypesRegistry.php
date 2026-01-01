<?php
/**
 * Operation Types Registry
 * 
 * Singleton registry for operation type definitions with session caching.
 * Supports both static definitions and dynamic plugin loading.
 * 
 * @package    KsfBankImport
 * @subpackage OperationTypes
 * @category   Registry
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────┐
 * │   OperationTypesRegistry            │
 * │   (Singleton)                       │
 * ├─────────────────────────────────────┤
 * │ - static $instance                  │
 * │ - $types: array                     │
 * │ - $loaded: bool                     │
 * ├─────────────────────────────────────┤
 * │ + static getInstance(): self        │
 * │ + getTypes(): array                 │
 * │ + getType(string): string|null      │
 * │ + hasType(string): bool             │
 * │ + reload(): void                    │
 * │ - loadTypes(): void                 │
 * │ - discoverTypes(): void             │
 * │ - loadTypeFromFile(string): void    │
 * └─────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\OperationTypes;

require_once(__DIR__ . '/OperationTypeInterface.php');

use KsfBankImport\OperationTypes\OperationTypeInterface;

/**
 * Singleton registry for operation types
 * 
 * Manages operation type definitions with session caching for performance.
 * Supports both hardcoded types (for backward compatibility) and dynamic
 * plugin loading from types subdirectory.
 * 
 * Performance Impact:
 * - Types loaded once per session
 * - Plugin discovery happens once, then cached
 * - Zero overhead after first load
 * 
 * Example usage:
 * <code>
 * // Get all operation types
 * $types = OperationTypesRegistry::getInstance()->getTypes();
 * 
 * // Check if type exists
 * if (OperationTypesRegistry::getInstance()->hasType('BT')) {
 *     // Process bank transfer
 * }
 * 
 * // Get single type
 * $label = OperationTypesRegistry::getInstance()->getType('SP');
 * 
 * // Force reload (e.g., after installing new plugin)
 * OperationTypesRegistry::getInstance()->reload();
 * </code>
 * 
 * @since 1.0.0
 */
class OperationTypesRegistry 
{
    /**
     * Singleton instance
     * 
     * @var OperationTypesRegistry|null
     * @since 1.0.0
     */
    private static $instance = null;
    
    /**
     * Operation types array
     * 
     * @var array Associative array [code => label]
     * @since 1.0.0
     */
    private $types = array();
    
    /**
     * Loaded flag
     * 
     * @var bool True if types have been loaded
     * @since 1.0.0
     */
    private $loaded = false;
    
    /**
     * Private constructor for singleton pattern
     * 
     * @since 1.0.0
     */
    private function __construct()
    {
        // Private constructor prevents direct instantiation
    }
    
    /**
     * Get singleton instance
     * 
     * @return OperationTypesRegistry The singleton instance
     * 
     * @since 1.0.0
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get all operation types
     * 
     * Returns array of operation types, loading from cache or
     * discovering plugins if not already loaded.
     * 
     * @return array Associative array of [code => label]
     * 
     * @since 1.0.0
     */
    public function getTypes()
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        return $this->types;
    }
    
    /**
     * Load operation types from session or discover
     * 
     * Attempts to load from session cache first, falls back to
     * plugin discovery if cache not available.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadTypes()
    {
        // Try to load from session first
        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['operation_types'])) {
            $this->types = $_SESSION['operation_types'];
            $this->loaded = true;
            return;
        }
        
        // Discover operation types dynamically
        $this->discoverTypes();
        
        // Cache in session
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['operation_types'] = $this->types;
        }
        $this->loaded = true;
    }
    
    /**
     * Dynamically discover operation type classes
     * 
     * Loads default types for backward compatibility, then scans
     * types directory for plugin implementations.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function discoverTypes()
    {
        // Default types (backward compatibility)
        $this->types = array(
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'QE' => 'Quick Entry',
            'BT' => 'Bank Transfer',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        );
        
        // If types directory exists, load custom types
        $typesDir = __DIR__ . '/types';
        if (is_dir($typesDir)) {
            $files = glob($typesDir . '/*.php');
            foreach ($files as $file) {
                $this->loadTypeFromFile($file);
            }
        }
    }
    
    /**
     * Load a single operation type from file
     * 
     * Attempts to load and instantiate operation type plugin from file.
     * If class implements OperationTypeInterface, adds it to registry.
     * 
     * @param string $file Full path to PHP file
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadTypeFromFile($file)
    {
        try {
            require_once($file);
            
            // Extract class name from filename
            // e.g., SupplierOperationType.php -> SupplierOperationType
            $basename = basename($file, '.php');
            $className = 'KsfBankImport\\OperationTypes\\Types\\' . $basename;
            
            if (class_exists($className)) {
                $instance = new $className();
                if ($instance instanceof OperationTypeInterface) {
                    $this->types[$instance->getCode()] = $instance->getLabel();
                }
            }
        } catch (\Exception $e) {
            // Silently ignore invalid plugins
            // In production, might want to log this
        }
    }
    
    /**
     * Get a specific operation type
     * 
     * Returns label for given operation type code, or null if not found.
     * 
     * @param string $code Operation type code (e.g., 'SP', 'CU')
     * 
     * @return string|null Label or null if not found
     * 
     * @since 1.0.0
     */
    public function getType($code)
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        return isset($this->types[$code]) ? $this->types[$code] : null;
    }
    
    /**
     * Check if operation type exists
     * 
     * @param string $code Operation type code to check
     * 
     * @return bool True if type exists in registry
     * 
     * @since 1.0.0
     */
    public function hasType($code)
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        return isset($this->types[$code]);
    }
    
    /**
     * Force reload of types
     * 
     * Clears cache and reloads operation types. Useful for:
     * - Testing
     * - After installing new plugins
     * - After removing plugins
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function reload()
    {
        $this->loaded = false;
        if (session_status() == PHP_SESSION_ACTIVE) {
            unset($_SESSION['operation_types']);
        }
        $this->loadTypes();
    }
}
