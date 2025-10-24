<?php

/**
 * Customer Data Provider with Branch Handling
 * 
 * Loads customer and branch data once and caches it for multiple line item displays.
 * Most complex provider - handles multi-branch customers and invoice allocation.
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
 * ┌──────────────────────────────────────────────┐
 * │  CustomerDataProvider                        │
 * │  (Singleton)                                 │
 * ├──────────────────────────────────────────────┤
 * │ - static $instance                           │
 * │ - $customers: array                          │
 * │ - $branches: array                           │
 * │ - $customerBranches: array                   │
 * │ - $loaded: bool                              │
 * ├──────────────────────────────────────────────┤
 * │ + static getInstance(): self                 │
 * │ + static reset(): void                       │
 * │ + getCustomers(): array                      │
 * │ + getCustomer(int): array|null               │
 * │ + getBranches(int): array                    │
 * │ + getBranch(int, int): array|null            │
 * │ + hasBranches(int): bool                     │
 * │ + getLabel(int): string|null                 │
 * │ + hasCustomer(int): bool                     │
 * │ + getCount(): int                            │
 * │ - loadCustomers(): void                      │
 * │ - loadBranches(): void                       │
 * └──────────────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\Views\DataProviders;

require_once(__DIR__ . '/PartnerDataProviderInterface.php');

/**
 * Provider for customer and branch data with singleton caching
 * 
 * Uses Singleton pattern to ensure customers and branches are loaded only once per page.
 * Handles complex multi-branch customer scenarios.
 * Loads data from FrontAccounting database and caches for page lifetime.
 * 
 * Design Patterns:
 * - Singleton: Ensures single data load per page
 * - Lazy Loading: Loads data on first access
 * - Cache-Aside: Caches database results
 * 
 * Complexity Factors:
 * - Customer can have multiple branches
 * - Branch selection depends on customer
 * - Invoice allocation per customer
 * - Separate data structures for customers and branches
 * 
 * Performance:
 * - Zero queries per line item after first load
 * - All customer and branch data cached in memory
 * - Fast lookups by customer ID and branch ID
 * 
 * Example usage:
 * <code>
 * // Get singleton instance
 * $provider = CustomerDataProvider::getInstance();
 * 
 * // Get all customers
 * $customers = $provider->getCustomers();
 * 
 * // Check if customer has branches
 * if ($provider->hasBranches(42)) {
 *     $branches = $provider->getBranches(42);
 * }
 * 
 * // Get specific branch
 * $branch = $provider->getBranch(42, 1);
 * 
 * // Use in View
 * $view = new CustomerPartnerTypeView($lineItemId, $bankAccount, $date, $custId, $branchId, $provider);
 * </code>
 * 
 * @since 1.0.0
 */
class CustomerDataProvider implements PartnerDataProviderInterface
{
    /**
     * Singleton instance
     * 
     * @var CustomerDataProvider|null
     */
    private static $instance = null;
    
    /**
     * Customer data
     * 
     * @var array<int, array> Indexed by customer ID
     */
    private $customers = [];
    
    /**
     * All branches data
     * 
     * @var array<int, array> Indexed by branch ID
     */
    private $branches = [];
    
    /**
     * Customer branches mapping
     * 
     * Maps customer ID to array of branch IDs
     * 
     * @var array<int, array<int>> [customer_id => [branch_id, branch_id, ...]]
     */
    private $customerBranches = [];
    
    /**
     * Loaded flag
     * 
     * @var bool True if customers have been loaded
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
     * @return CustomerDataProvider The singleton instance
     * 
     * @since 1.0.0
     */
    public static function getInstance(): self
    {
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
    public static function reset(): void
    {
        self::$instance = null;
    }
    
    /**
     * Get all customers (implements PartnerDataProviderInterface)
     * 
     * @return array<int, array> Customer data indexed by customer ID
     * 
     * @since 1.0.0
     */
    public function getPartners(): array
    {
        return $this->getCustomers();
    }
    
    /**
     * Get all customers
     * 
     * @return array<int, array> Customer data indexed by customer ID
     * 
     * @since 1.0.0
     */
    public function getCustomers(): array
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        return $this->customers;
    }
    
    /**
     * Get specific customer
     * 
     * @param int $customerId Customer ID (debtor_no)
     * 
     * @return array|null Customer data or null if not found
     * 
     * @since 1.0.0
     */
    public function getCustomer(int $customerId): ?array
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        return $this->customers[$customerId] ?? null;
    }
    
    /**
     * Get all branches for a customer
     * 
     * @param int $customerId Customer ID
     * 
     * @return array<int, array> Array of branch data indexed by branch code
     * 
     * @since 1.0.0
     */
    public function getBranches(int $customerId): array
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        
        if (!isset($this->customerBranches[$customerId])) {
            return [];
        }
        
        $customerBranches = [];
        foreach ($this->customerBranches[$customerId] as $branchCode) {
            if (isset($this->branches[$branchCode])) {
                $customerBranches[$branchCode] = $this->branches[$branchCode];
            }
        }
        
        return $customerBranches;
    }
    
    /**
     * Get specific branch for a customer
     * 
     * @param int $customerId Customer ID
     * @param int $branchCode Branch code
     * 
     * @return array|null Branch data or null if not found
     * 
     * @since 1.0.0
     */
    public function getBranch(int $customerId, int $branchCode): ?array
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        
        // Verify branch belongs to customer
        if (!isset($this->customerBranches[$customerId]) || 
            !in_array($branchCode, $this->customerBranches[$customerId])) {
            return null;
        }
        
        return $this->branches[$branchCode] ?? null;
    }
    
    /**
     * Check if customer has branches
     * 
     * @param int $customerId Customer ID
     * 
     * @return bool True if customer has branches
     * 
     * @since 1.0.0
     */
    public function hasBranches(int $customerId): bool
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        
        return isset($this->customerBranches[$customerId]) && 
               count($this->customerBranches[$customerId]) > 0;
    }
    
    /**
     * Get label for customer (implements PartnerDataProviderInterface)
     * 
     * @param int $partnerId Customer ID
     * 
     * @return string|null Customer name or null if not found
     * 
     * @since 1.0.0
     */
    public function getPartnerLabel(int $partnerId): ?string
    {
        return $this->getLabel($partnerId);
    }
    
    /**
     * Get label for customer
     * 
     * @param int $customerId Customer ID
     * 
     * @return string|null Customer name or null if not found
     * 
     * @since 1.0.0
     */
    public function getLabel(int $customerId): ?string
    {
        $customer = $this->getCustomer($customerId);
        return $customer['name'] ?? null;
    }
    
    /**
     * Check if customer exists (implements PartnerDataProviderInterface)
     * 
     * @param int $partnerId Customer ID to check
     * 
     * @return bool True if customer exists
     * 
     * @since 1.0.0
     */
    public function hasPartner(int $partnerId): bool
    {
        return $this->hasCustomer($partnerId);
    }
    
    /**
     * Check if customer exists
     * 
     * @param int $customerId Customer ID to check
     * 
     * @return bool True if customer exists
     * 
     * @since 1.0.0
     */
    public function hasCustomer(int $customerId): bool
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        return isset($this->customers[$customerId]);
    }
    
    /**
     * Get count of customers
     * 
     * @return int Number of customers
     * 
     * @since 1.0.0
     */
    public function getCount(): int
    {
        if (!$this->loaded) {
            $this->loadCustomers();
        }
        return count($this->customers);
    }
    
    /**
     * Load customers and branches from database
     * 
     * Queries FrontAccounting database for all active customers and their branches.
     * Caches results in memory for page lifetime.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadCustomers(): void
    {
        if ($this->loaded) {
            return;
        }
        
        // Load customers from FrontAccounting database
        if (function_exists('get_customer_details_all')) {
            $result = get_customer_details_all();
            
            if ($result) {
                while ($row = db_fetch($result)) {
                    $this->customers[$row['debtor_no']] = [
                        'debtor_no' => $row['debtor_no'],
                        'name' => $row['name'],
                        'debtor_ref' => $row['debtor_ref'] ?? '',
                        'address' => $row['address'] ?? '',
                        'email' => $row['email'] ?? '',
                        'inactive' => $row['inactive'] ?? 0,
                    ];
                }
            }
        } else {
            // Fallback: Direct database query
            if (function_exists('db_query') && defined('TB_PREF')) {
                $sql = "SELECT debtor_no, name, debtor_ref, address, email, inactive 
                        FROM " . TB_PREF . "debtors_master 
                        WHERE inactive = 0 
                        ORDER BY name";
                
                $result = db_query($sql, 'Could not load customers');
                
                if ($result) {
                    while ($row = db_fetch($result)) {
                        $this->customers[$row['debtor_no']] = $row;
                    }
                }
            }
        }
        
        // Load branches
        $this->loadBranches();
        
        $this->loaded = true;
    }
    
    /**
     * Load customer branches from database
     * 
     * Loads all branch data and creates customer-to-branches mapping.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadBranches(): void
    {
        if (function_exists('db_query') && defined('TB_PREF')) {
            $sql = "SELECT branch_code, debtor_no, br_name, br_address, 
                           contact_name, email, inactive 
                    FROM " . TB_PREF . "cust_branch 
                    WHERE inactive = 0 
                    ORDER BY debtor_no, br_name";
            
            $result = db_query($sql, 'Could not load customer branches');
            
            if ($result) {
                while ($row = db_fetch($result)) {
                    $branchCode = $row['branch_code'];
                    $customerId = $row['debtor_no'];
                    
                    // Store branch data
                    $this->branches[$branchCode] = $row;
                    
                    // Map branch to customer
                    if (!isset($this->customerBranches[$customerId])) {
                        $this->customerBranches[$customerId] = [];
                    }
                    $this->customerBranches[$customerId][] = $branchCode;
                }
            }
        }
    }
}
