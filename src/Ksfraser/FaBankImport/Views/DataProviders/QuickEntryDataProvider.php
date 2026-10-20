<?php

/**
 * Quick Entry Data Provider
 * 
 * Loads quick entry data once and caches it for multiple line item displays.
 * Implements Singleton pattern to ensure single database query per page load.
 * 
 * @package    Ksfraser\FaBankImport\Views\DataProviders
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250422
 * 
 * @uml.diagram
 * ┌──────────────────────────────────────┐
 * │  QuickEntryDataProvider              │
 * │  (Singleton)                         │
 * ├──────────────────────────────────────┤
 * │ - static $depositInstance            │
 * │ - static $paymentInstance            │
 * │ - $entries: array                    │
 * │ - $loaded: bool                      │
 * │ - $type: int                         │
 * ├──────────────────────────────────────┤
 * │ + static forDeposit(): self          │
 * │ + static forPayment(): self          │
 * │ + getEntries(): array                │
 * │ + getEntry(int): array|null          │
 * │ + getLabel(int): string|null         │
 * │ + hasEntry(int): bool                │
 * │ + getCount(): int                    │
 * │ - loadEntries(): void                │
 * └──────────────────────────────────────┘
 * @enduml
 */
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views\DataProviders;

/**
 * Provider for quick entry data with type filtering
 * 
 * Uses Singleton pattern with separate instances for deposit and payment types.
 * Loads data once from FrontAccounting and caches for page lifetime.
 * 
 * Design Patterns:
 * - Singleton: Ensures single data load per page
 * - Lazy Loading: Loads data on first access
 * - Cache-Aside: Caches database results
 * 
 * Performance:
 * - Zero queries per line item after first load
 * - Separate caches for deposit vs payment types
 * - Session caching possible for multi-page workflows
 * 
 * Example usage:
 * <code>
 * // Get deposit entries (QE_DEPOSIT)
 * $depositProvider = QuickEntryDataProvider::forDeposit();
 * $entries = $depositProvider->getEntries();
 * 
 * // Get payment entries (QE_PAYMENT)  
 * $paymentProvider = QuickEntryDataProvider::forPayment();
 * if ($paymentProvider->hasEntry(42)) {
 *     echo $paymentProvider->getLabel(42);
 * }
 * 
 * // Use in View
 * $view = new QuickEntryPartnerTypeView($lineItemId, $transactionDC, $depositProvider);
 * </code>
 * 
 * @since 1.0.0
 */
/**
 * @BABOK RTM: FR-009 / IR-001; file: Views/DataProviders/QuickEntryDataProvider.php; coverage: docs/TestCases/ (file rollup)
 */
class QuickEntryDataProvider
{
    /**
     * Singleton instance for deposit entries
     * 
     * @var QuickEntryDataProvider|null
     */
    private static $depositInstance = null;
    
    /**
     * Singleton instance for payment entries
     * 
     * @var QuickEntryDataProvider|null
     */
    private static $paymentInstance = null;
    
    /**
     * Quick entry data
     * 
     * @var array<int, array> Indexed by entry ID
     */
    private $entries = [];
    
    /**
     * Loaded flag
     * 
     * @var bool True if entries have been loaded
     */
    private $loaded = false;
    
    /**
     * Quick entry type
     * 
     * @var int QE_DEPOSIT or QE_PAYMENT constant
     */
    private $type;
    
    /**
 * @BABOK FR-009 / IR-001 — method: __construct
     * Private constructor for singleton pattern
     * 
     * @param int $type Quick entry type (QE_DEPOSIT or QE_PAYMENT)
     */
    private function __construct(int $type)
    {
        $this->type = $type;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: forDeposit
     * Get singleton instance for deposit entries
     * 
     * @return QuickEntryDataProvider Instance for QE_DEPOSIT entries
     * 
     * @since 1.0.0
     */
    public static function forDeposit(): QuickEntryDataProvider {
        if (self::$depositInstance === null) {
            // QE_DEPOSIT constant from FrontAccounting
            if (!defined('QE_DEPOSIT')) {
                define('QE_DEPOSIT', 1);
            }
            self::$depositInstance = new self(QE_DEPOSIT);
        }
        return self::$depositInstance;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: forPayment
     * Get singleton instance for payment entries
     * 
     * @return QuickEntryDataProvider Instance for QE_PAYMENT entries
     * 
     * @since 1.0.0
     */
    public static function forPayment(): QuickEntryDataProvider {
        if (self::$paymentInstance === null) {
            // QE_PAYMENT constant from FrontAccounting
            if (!defined('QE_PAYMENT')) {
                define('QE_PAYMENT', 2);
            }
            self::$paymentInstance = new self(QE_PAYMENT);
        }
        return self::$paymentInstance;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: reset
     * Reset singletons (for testing)
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public static function reset(): void {
        self::$depositInstance = null;
        self::$paymentInstance = null;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: getEntries
     * Get all quick entries of this type
     * 
     * @return array<int, array> Quick entry data indexed by ID
     * 
     * @since 1.0.0
     */
    public function getEntries(): array {
        if (!$this->loaded) {
            $this->loadEntries();
        }
        return $this->entries;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: getEntry
     * Get specific quick entry
     * 
     * @param int $entryId Quick entry ID
     * 
     * @return array|null Entry data or null if not found
     * 
     * @since 1.0.0
     */
    public function getEntry(int $entryId): ?array
    {
        if (!$this->loaded) {
            $this->loadEntries();
        }
        return $this->entries[$entryId] ?? null;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: getLabel
     * Get label for quick entry
     * 
     * @param int $entryId Quick entry ID
     * 
     * @return string|null Entry description or null if not found
     * 
     * @since 1.0.0
     */
    public function getLabel(int $entryId): ?string
    {
        $entry = $this->getEntry($entryId);
        return $entry['description'] ?? null;
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: hasEntry
     * Check if entry exists
     * 
     * @param int $entryId Quick entry ID to check
     * 
     * @return bool True if entry exists
     * 
     * @since 1.0.0
     */
    public function hasEntry(int $entryId): bool {
        if (!$this->loaded) {
            $this->loadEntries();
        }
        return isset($this->entries[$entryId]);
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: getCount
     * Get count of entries
     * 
     * @return int Number of quick entries of this type
     * 
     * @since 1.0.0
     */
    public function getCount(): int {
        if (!$this->loaded) {
            $this->loadEntries();
        }
        return count($this->entries);
    }
    
    /**
 * @BABOK FR-009 / IR-001 — method: loadEntries
     * Load quick entries from database
     * 
     * Queries FrontAccounting database for quick entries of this type.
     * Caches results in memory for page lifetime.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadEntries(): void {
        if ($this->loaded) {
            return;
        }
        
        // Load from FrontAccounting database
        // This is a placeholder - actual implementation depends on FA structure
        if (function_exists('get_quick_entries')) {
            $result = get_quick_entries($this->type);
            
            if ($result) {
                while ($row = db_fetch($result)) {
                    $this->entries[$row['id']] = [
                        'id' => $row['id'],
                        'description' => $row['description'],
                        'base_desc' => $row['base_desc'] ?? '',
                        'type' => $row['type'],
                    ];
                }
            }
        }
        
        $this->loaded = true;
    }
}
