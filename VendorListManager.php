<?php
/**
 * Manages vendor list with session caching
 * 
 * Singleton pattern ensures vendor list is loaded once per session,
 * dramatically improving performance by avoiding repeated database queries.
 * 
 * @package    KsfBankImport
 * @subpackage Managers
 * @category   Managers
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────┐
 * │   VendorListManager (Singleton)     │
 * ├─────────────────────────────────────┤
 * │ - static $instance                  │
 * │ - $vendorList                       │
 * │ - $lastLoaded                       │
 * │ - $cacheDuration                    │
 * ├─────────────────────────────────────┤
 * │ + static getInstance(): self        │
 * │ + getVendorList(bool): array        │
 * │ + clearCache(): void                │
 * │ + setCacheDuration(int): void       │
 * │ - shouldReload(): bool              │
 * │ - loadVendorList(): void            │
 * │ - loadFromSession(): void           │
 * └─────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport;

/**
 * Singleton manager for vendor list with session caching
 * 
 * Manages vendor list loading and caching to minimize database queries.
 * Uses singleton pattern to ensure single instance per PHP process.
 * Caches data in session with configurable timeout.
 * 
 * Performance Impact:
 * - Before: N database queries (one per transaction display)
 * - After: 1 database query per session (or per cache timeout)
 * - Estimated improvement: ~95% reduction in vendor list queries
 * 
 * Example usage:
 * <code>
 * // Get vendor list (loads from cache if available)
 * $vendors = VendorListManager::getInstance()->getVendorList();
 * 
 * // Force reload from database
 * $vendors = VendorListManager::getInstance()->getVendorList(true);
 * 
 * // Clear cache after vendor changes
 * VendorListManager::getInstance()->clearCache();
 * 
 * // Set custom cache duration (e.g., 30 minutes)
 * VendorListManager::getInstance()->setCacheDuration(1800);
 * </code>
 * 
 * @since 1.0.0
 */
class VendorListManager 
{
    /**
     * Singleton instance
     * 
     * @var VendorListManager|null
     * @since 1.0.0
     */
    private static $instance = null;
    
    /**
     * Cached vendor list
     * 
     * @var array|null Array of vendor data or null if not loaded
     * @since 1.0.0
     */
    private $vendorList = null;
    
    /**
     * Timestamp of last load
     * 
     * @var int|null Unix timestamp or null if never loaded
     * @since 1.0.0
     */
    private $lastLoaded = null;
    
    /**
     * Cache duration in seconds
     * 
     * @var int Default: 3600 (1 hour)
     * @since 1.0.0
     */
    private $cacheDuration = 3600;
    
    /**
     * Private constructor for singleton pattern
     * 
     * Prevents direct instantiation. Use getInstance() instead.
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
     * Returns the single instance of VendorListManager, creating it if
     * it doesn't exist yet.
     * 
     * @return VendorListManager The singleton instance
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
     * Get vendor list (from cache if available)
     * 
     * Returns cached vendor list if available and not expired.
     * Otherwise loads from database and caches in session.
     * 
     * @param bool $force_reload Force reload from database, ignoring cache
     * 
     * @return array Vendor list array
     * 
     * @since 1.0.0
     */
    public function getVendorList($forceReload = false)
    {
        if ($forceReload || $this->shouldReload()) {
            $this->loadVendorList();
        } else if ($this->vendorList === null) {
            $this->loadFromSession();
        }
        
        return $this->vendorList;
    }
    
    /**
     * Check if vendor list should be reloaded
     * 
     * Returns true if:
     * - Never loaded before
     * - Cache duration has expired
     * 
     * @return bool True if should reload, false if cache is still valid
     * 
     * @since 1.0.0
     */
    private function shouldReload()
    {
        if ($this->lastLoaded === null) {
            return true;
        }
        
        $elapsed = time() - $this->lastLoaded;
        return $elapsed > $this->cacheDuration;
    }
    
    /**
     * Load vendor list from database
     * 
     * Queries database for current vendor list and caches result
     * in both instance variable and session.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadVendorList()
    {
        $this->vendorList = get_vendor_list();
        $this->lastLoaded = time();
        
        // Cache in session
        if (session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['vendor_list'] = $this->vendorList;
            $_SESSION['vendor_list_loaded'] = $this->lastLoaded;
        }
    }
    
    /**
     * Load vendor list from session
     * 
     * Attempts to restore vendor list from session cache.
     * Falls back to database load if session data not available.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    private function loadFromSession()
    {
        if (session_status() == PHP_SESSION_ACTIVE 
            && isset($_SESSION['vendor_list']) 
            && isset($_SESSION['vendor_list_loaded'])
        ) {
            $this->vendorList = $_SESSION['vendor_list'];
            $this->lastLoaded = $_SESSION['vendor_list_loaded'];
        } else {
            $this->loadVendorList();
        }
    }
    
    /**
     * Clear cached vendor list
     * 
     * Forces reload on next access. Call this after adding/updating/deleting
     * vendors to ensure fresh data is loaded.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public function clearCache()
    {
        $this->vendorList = null;
        $this->lastLoaded = null;
        
        if (session_status() == PHP_SESSION_ACTIVE) {
            unset($_SESSION['vendor_list']);
            unset($_SESSION['vendor_list_loaded']);
        }
    }
    
    /**
     * Set cache duration
     * 
     * Configures how long vendor list should be cached before reloading.
     * 
     * @param int $seconds Cache duration in seconds
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If duration is negative
     * 
     * @since 1.0.0
     */
    public function setCacheDuration($seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException("Cache duration must be non-negative");
        }
        $this->cacheDuration = $seconds;
    }
}
