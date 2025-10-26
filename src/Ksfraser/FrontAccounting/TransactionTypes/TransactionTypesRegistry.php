<?php

/**
 * Transaction Types Registry
 * 
 * Singleton registry for FrontAccounting transaction type definitions.
 * Provides single source of truth for ST_ constant mappings to human-readable labels
 * with metadata flags (moneyMoved, goodsMoved, affectsAR, affectsAP).
 * 
 * Supports both hardcoded defaults and dynamic plugin loading from types/ subdirectory.
 * 
 * @package    Ksfraser\FrontAccounting
 * @subpackage TransactionTypes
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────────────┐
 * │   TransactionTypesRegistry                  │
 * │   (Singleton)                               │
 * ├─────────────────────────────────────────────┤
 * │ - static $instance                          │
 * │ - $types: array                             │
 * │ - $loaded: bool                             │
 * ├─────────────────────────────────────────────┤
 * │ + static getInstance(): self                │
 * │ + getTypes(array $filters): array           │
 * │ + getType(int $code): array|null            │
 * │ + getLabel(int $code): string|null          │
 * │ + hasType(int $code): bool                  │
 * │ + getMoneyMovedTypes(): array               │
 * │ + getGoodsMovedTypes(): array               │
 * │ + reload(): void                            │
 * │ - loadTypes(): void                         │
 * │ - discoverTypes(): void                     │
 * │ - loadTypeFromFile(string): void            │
 * │ - registerType(TransactionTypeInterface)    │
 * └─────────────────────────────────────────────┘
 * @enduml
 */

namespace Ksfraser\FrontAccounting\TransactionTypes;

require_once(__DIR__ . '/TransactionTypeInterface.php');

use Ksfraser\FrontAccounting\TransactionTypes\TransactionTypeInterface;

/**
 * Singleton registry for FA transaction types
 * 
 * Manages transaction type definitions with session caching for performance.
 * Provides single source of truth for transaction type metadata.
 * 
 * Each transaction type has:
 * - code: ST_ constant numeric value
 * - label: Human-readable name (translatable)
 * - moneyMoved: Whether transaction affects bank accounts
 * - goodsMoved: Whether transaction affects inventory
 * - affectsAR: Whether transaction affects accounts receivable
 * - affectsAP: Whether transaction affects accounts payable
 * 
 * Usage Examples:
 * <code>
 * // Get all types
 * $allTypes = TransactionTypesRegistry::getInstance()->getTypes();
 * 
 * // Get only money-moved types (for bank import module)
 * $bankTypes = TransactionTypesRegistry::getInstance()->getTypes(['moneyMoved' => true]);
 * 
 * // Get label for specific type
 * $label = TransactionTypesRegistry::getInstance()->getLabel(ST_BANKPAYMENT);
 * 
 * // Check if type involves money
 * $type = TransactionTypesRegistry::getInstance()->getType(ST_BANKPAYMENT);
 * if ($type && $type['moneyMoved']) {
 *     // Process bank transaction
 * }
 * 
 * // Get all types for dropdown (code => label pairs)
 * $dropdown = [];
 * foreach (TransactionTypesRegistry::getInstance()->getMoneyMovedTypes() as $code => $data) {
 *     $dropdown[$code] = $data['label'];
 * }
 * </code>
 * 
 * @since 1.0.0
 */
class TransactionTypesRegistry
{
    /**
     * Singleton instance
     * 
     * @var TransactionTypesRegistry|null
     */
    private static $instance = null;
    
    /**
     * Transaction types array
     * 
     * Structure: [
     *   code => [
     *     'label' => string,
     *     'moneyMoved' => bool,
     *     'goodsMoved' => bool,
     *     'affectsAR' => bool,
     *     'affectsAP' => bool
     *   ]
     * ]
     * 
     * @var array
     */
    private $types = [];
    
    /**
     * Loaded flag
     * 
     * @var bool
     */
    private $loaded = false;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        // Private constructor prevents direct instantiation
    }
    
    /**
     * Get singleton instance
     * 
     * @return TransactionTypesRegistry The singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get all transaction types with optional filtering
     * 
     * @param array $filters Optional filters: ['moneyMoved' => true, 'goodsMoved' => false, etc.]
     * 
     * @return array Filtered transaction types [code => data]
     */
    public function getTypes(array $filters = []): array
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        
        // No filters? Return all
        if (empty($filters)) {
            return $this->types;
        }
        
        // Apply filters
        $filtered = [];
        foreach ($this->types as $code => $data) {
            $matches = true;
            foreach ($filters as $key => $value) {
                if (isset($data[$key]) && $data[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $filtered[$code] = $data;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get a specific transaction type by code
     * 
     * @param int $code Transaction type code (ST_ constant)
     * 
     * @return array|null Type data or null if not found
     */
    public function getType(int $code): ?array
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        return $this->types[$code] ?? null;
    }
    
    /**
     * Get human-readable label for transaction type
     * 
     * @param int $code Transaction type code
     * 
     * @return string|null Label or null if not found
     */
    public function getLabel(int $code): ?string
    {
        $type = $this->getType($code);
        return $type ? $type['label'] : null;
    }
    
    /**
     * Check if transaction type exists
     * 
     * @param int $code Transaction type code
     * 
     * @return bool True if type exists
     */
    public function hasType(int $code): bool
    {
        if (!$this->loaded) {
            $this->loadTypes();
        }
        return isset($this->types[$code]);
    }
    
    /**
     * Get all types where money moved
     * 
     * Useful for bank import module - returns only transaction types
     * that affect bank accounts.
     * 
     * @return array Types with moneyMoved=true [code => data]
     */
    public function getMoneyMovedTypes(): array
    {
        return $this->getTypes(['moneyMoved' => true]);
    }
    
    /**
     * Get all types where goods moved
     * 
     * Useful for inventory modules - returns only transaction types
     * that affect stock levels.
     * 
     * @return array Types with goodsMoved=true [code => data]
     */
    public function getGoodsMovedTypes(): array
    {
        return $this->getTypes(['goodsMoved' => true]);
    }
    
    /**
     * Get types as simple code => label array for dropdowns
     * 
     * @param array $filters Optional filters
     * 
     * @return array [code => label] pairs
     */
    public function getLabelsArray(array $filters = []): array
    {
        $types = $this->getTypes($filters);
        $labels = [];
        foreach ($types as $code => $data) {
            $labels[$code] = $data['label'];
        }
        return $labels;
    }
    
    /**
     * Load transaction types from cache or discover
     * 
     * @return void
     */
    private function loadTypes(): void
    {
        // Try to load from session first
        if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['fa_transaction_types'])) {
            $this->types = $_SESSION['fa_transaction_types'];
            $this->loaded = true;
            return;
        }
        
        // Discover transaction types dynamically
        $this->discoverTypes();
        
        // Cache in session
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['fa_transaction_types'] = $this->types;
        }
        $this->loaded = true;
    }
    
    /**
     * Dynamically discover transaction type classes
     * 
     * Loads default types first, then scans types/ directory for plugins.
     * 
     * @return void
     */
    private function discoverTypes(): void
    {
        // Load default types (using constants from fa_stubs.php)
        $this->loadDefaultTypes();
        
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
     * Load default transaction types
     * 
     * These are the core FA transaction types with metadata.
     * Commented types have goodsMoved=true (inventory-related).
     * 
     * @return void
     */
    private function loadDefaultTypes(): void
    {
        // Journal Entry - no money or goods moved directly
        $this->types[ST_JOURNAL] = [
            'label' => _('Journal Entry'),
            'moneyMoved' => false,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => false
        ];
        
        // Banking transactions - money moved
        $this->types[ST_BANKPAYMENT] = [
            'label' => _('Bank Payment'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => false
        ];
        
        $this->types[ST_BANKDEPOSIT] = [
            'label' => _('Bank Deposit'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => false
        ];
        
        $this->types[ST_BANKTRANSFER] = [
            'label' => _('Bank Transfer'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => false
        ];
        
        // Customer transactions - money moved for payments/credits
        $this->types[ST_CUSTCREDIT] = [
            'label' => _('Customer Credit'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => true,
            'affectsAP' => false
        ];
        
        $this->types[ST_CUSTPAYMENT] = [
            'label' => _('Customer Payment'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => true,
            'affectsAP' => false
        ];
        
        // Supplier transactions - money moved for payments/credits
        $this->types[ST_SUPPCREDIT] = [
            'label' => _('Supplier Credit'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => true
        ];
        
        $this->types[ST_SUPPAYMENT] = [
            'label' => _('Supplier Payment'),
            'moneyMoved' => true,
            'goodsMoved' => false,
            'affectsAR' => false,
            'affectsAP' => true
        ];
        
        // Inventory/Goods transactions - NOT loaded by default for bank import
        // These would be in separate plugin files if needed
        // ST_SALESINVOICE, ST_CUSTDELIVERY, ST_LOCTRANSFER, ST_INVADJUST, 
        // ST_PURCHORDER, ST_SUPPINVOICE, ST_SUPPRECEIVE
    }
    
    /**
     * Load a single transaction type from plugin file
     * 
     * @param string $file Full path to PHP file
     * 
     * @return void
     */
    private function loadTypeFromFile(string $file): void
    {
        try {
            require_once($file);
            
            // Extract class name from filename
            $basename = basename($file, '.php');
            $className = 'Ksfraser\\FrontAccounting\\TransactionTypes\\Types\\' . $basename;
            
            if (class_exists($className)) {
                $instance = new $className();
                if ($instance instanceof TransactionTypeInterface) {
                    $this->registerType($instance);
                }
            }
        } catch (\Exception $e) {
            // Silently ignore invalid plugins
            // In production, might want to log this
        }
    }
    
    /**
     * Register a transaction type instance
     * 
     * @param TransactionTypeInterface $type Type instance
     * 
     * @return void
     */
    private function registerType(TransactionTypeInterface $type): void
    {
        $this->types[$type->getCode()] = [
            'label' => $type->getLabel(),
            'moneyMoved' => $type->hasMoneyMoved(),
            'goodsMoved' => $type->hasGoodsMoved(),
            'affectsAR' => $type->affectsAR(),
            'affectsAP' => $type->affectsAP()
        ];
    }
    
    /**
     * Force reload of types
     * 
     * Clears cache and reloads. Useful for testing or after plugin changes.
     * 
     * @return void
     */
    public function reload(): void
    {
        $this->loaded = false;
        if (session_status() == PHP_SESSION_ACTIVE) {
            unset($_SESSION['fa_transaction_types']);
        }
        $this->loadTypes();
    }
}
