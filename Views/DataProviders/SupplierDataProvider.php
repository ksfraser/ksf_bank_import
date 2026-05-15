<?php

/**
 * Supplier Data Provider
 * 
 * Loads supplier data once and caches it for multiple line item displays.
 * Implements Singleton pattern to ensure single database query per page load.
 * 
 * @package    KsfBankImport\Views\DataProviders
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250422
 * 
 * @uml.diagram
 * ┌──────────────────────────────────────┐
 * │  SupplierDataProvider                │
 * │  (Singleton)                         │
 * ├──────────────────────────────────────┤
 * │ - static $instance                   │
 * │ - $suppliers: array                  │
 * │ - $loaded: bool                      │
 * ├──────────────────────────────────────┤
 * │ + static getInstance(): self         │
 * │ + static reset(): void               │
 * │ + getSuppliers(): array              │
 * │ + getSupplier(int): array|null       │
 * │ + getLabel(int): string|null         │
 * │ + hasSupplier(int): bool             │
 * │ + getCount(): int                    │
 * │ - loadSuppliers(): void              │
 * └──────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\Views\DataProviders;

require_once(__DIR__ . '/PartnerDataProviderInterface.php');

/**
 * Provider for supplier data with singleton caching
 * 
 * Uses Singleton pattern to ensure suppliers are loaded only once per page.
 * Loads data from FrontAccounting database and caches for page lifetime.
 * 
 * Design Patterns:
 * - Singleton: Ensures single data load per page
 * - Lazy Loading: Loads data on first access
 * - Cache-Aside: Caches database results
 * 
 * Performance:
 * - Zero queries per line item after first load
 * - All supplier data cached in memory
 * - Fast lookups by supplier ID
 * 
 * Example usage:
 * <code>
 * // Get singleton instance
 * $provider = SupplierDataProvider::getInstance();
 * 
 * // Get all suppliers
 * $suppliers = $provider->getSuppliers();
 * 
 * // Check if supplier exists
 * if ($provider->hasSupplier(42)) {
 *     echo $provider->getLabel(42);
 * }
 * 
 * // Use in View
 * $view = new SupplierPartnerTypeView($lineItemId, $bankAccount, $partnerId, $provider);
 * </code>
 * 
 * @since 1.0.0
 */
class SupplierDataProvider implements PartnerDataProviderInterface
{
    /**
     * Resolve first available value from candidate keys.
     *
     * @param array $row
     * @param array $keys
     * @return mixed|null
     */
    private function firstValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    /**
     * Singleton instance
     * 
     * @var SupplierDataProvider|null
     */
    private static $instance = null;
    
    /**
     * Supplier data
     * 
     * @var array<int, array> Indexed by supplier ID
     */
    private $suppliers = [];
    
    /**
     * Loaded flag
     * 
     * @var bool True if suppliers have been loaded
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
     * @return SupplierDataProvider The singleton instance
     * 
     * @since 1.0.0
     */
    public static function getInstance(): SupplierDataProvider {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Reset singleton instance (for testing)
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public static function reset(): void {
        self::$instance = null;
    }
    
    /**
     * Get all suppliers (implements PartnerDataProviderInterface)
     * 
     * @return array<int, array> Supplier data indexed by supplier ID
     * 
     * @since 1.0.0
     */
    public function getPartners(): array {
        return $this->getSuppliers();
    }
    
    /**
     * Get all suppliers
     * 
     * @return array<int, array> Supplier data indexed by supplier ID
     * 
     * @since 1.0.0
     */
    public function getSuppliers(): array {
        if (!$this->loaded) {
            $this->loadSuppliers();
        }
        return $this->suppliers;
    }
    
    /**
     * Get specific supplier
     * 
     * @param int $supplierId Supplier ID
     * 
     * @return array|null Supplier data or null if not found
     * 
     * @since 1.0.0
     */
    public function getSupplier(int $supplierId): ?array
    {
        if (!$this->loaded) {
            $this->loadSuppliers();
        }
        return $this->suppliers[$supplierId] ?? null;
    }
    
    /**
     * Get label for supplier (implements PartnerDataProviderInterface)
     * 
     * @param int $partnerId Supplier ID
     * 
     * @return string|null Supplier name or null if not found
     * 
     * @since 1.0.0
     */
    public function getPartnerLabel(int $partnerId): ?string
    {
        return $this->getLabel($partnerId);
    }
    
    /**
     * Get label for supplier
     * 
     * @param int $supplierId Supplier ID
     * 
     * @return string|null Supplier name or null if not found
     * 
     * @since 1.0.0
     */
    public function getLabel(int $supplierId): ?string
    {
        $supplier = $this->getSupplier($supplierId);
        return $supplier['supp_name'] ?? null;
    }
    
    /**
     * Check if supplier exists (implements PartnerDataProviderInterface)
     * 
     * @param int $partnerId Supplier ID to check
     * 
     * @return bool True if supplier exists
     * 
     * @since 1.0.0
     */
    public function hasPartner(int $partnerId): bool {
        return $this->hasSupplier($partnerId);
    }
    
    /**
     * Check if supplier exists
     * 
     * @param int $supplierId Supplier ID to check
     * 
     * @return bool True if supplier exists
     * 
     * @since 1.0.0
     */
    public function hasSupplier(int $supplierId): bool {
        if (!$this->loaded) {
            $this->loadSuppliers();
        }
        return isset($this->suppliers[$supplierId]);
    }
    
    /**
     * Get count of suppliers
     * 
     * @return int Number of suppliers
     * 
     * @since 1.0.0
     */
    public function getCount(): int {
        if (!$this->loaded) {
            $this->loadSuppliers();
        }
        return count($this->suppliers);
    }
    
    /**
     * Load suppliers from database
     * 
     * Queries FrontAccounting database for all active suppliers.
     * Caches results in memory for page lifetime.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadSuppliers(): void {
        if ($this->loaded) {
            return;
        }
        
        // Load from FrontAccounting database
        // Uses get_supplier_details_all() if available, falls back to direct query
        if (function_exists('get_supplier_details_all')) {
            $result = get_supplier_details_all();
            
            if ($result) {
                while ($row = db_fetch($result)) {
                    if (is_object($row)) {
                        $row = (array)$row;
                    }

                    if (!is_array($row)) {
                        continue;
                    }

                    $supplierId = $this->firstValue($row, ['supplier_id', 'creditor_id', 'id', 'supp_id']);
                    if ($supplierId === null) {
                        continue;
                    }

                    $supplierId = (int) $supplierId;
                    $supplierName = (string)($this->firstValue($row, ['supp_name', 'name', 'supplier_name', 'creditor_name']) ?? '');

                    $this->suppliers[$supplierId] = [
                        'supplier_id' => $supplierId,
                        'supp_name' => $supplierName,
                        'supp_ref' => (string)($this->firstValue($row, ['supp_ref', 'supplier_ref', 'reference']) ?? ''),
                        'address' => (string)($this->firstValue($row, ['address', 'supp_address']) ?? ''),
                        'email' => (string)($this->firstValue($row, ['email', 'supp_email']) ?? ''),
                        'inactive' => (int)($this->firstValue($row, ['inactive']) ?? 0),
                    ];
                }
            }
        } else {
            // Fallback: Direct database query
            if (function_exists('db_query') && defined('TB_PREF')) {
                $sql = "SELECT supplier_id, supp_name, supp_ref, address, email, inactive 
                        FROM " . TB_PREF . "suppliers 
                        WHERE inactive = 0 
                        ORDER BY supp_name";
                
                $result = db_query($sql, 'Could not load suppliers');
                
                if ($result) {
                    while ($row = db_fetch($result)) {
                        if (is_object($row)) {
                            $row = (array)$row;
                        }

                        if (!is_array($row)) {
                            continue;
                        }

                        $supplierId = $this->firstValue($row, ['supplier_id', 'creditor_id', 'id', 'supp_id']);
                        if ($supplierId === null) {
                            continue;
                        }

                        $supplierId = (int) $supplierId;
                        $row['supplier_id'] = $supplierId;
                        $row['supp_name'] = (string)($this->firstValue($row, ['supp_name', 'name', 'supplier_name', 'creditor_name']) ?? '');
                        $row['supp_ref'] = (string)($this->firstValue($row, ['supp_ref', 'supplier_ref', 'reference']) ?? '');
                        $this->suppliers[$supplierId] = $row;
                    }
                }
            }
        }
        
        $this->loaded = true;
    }
}
