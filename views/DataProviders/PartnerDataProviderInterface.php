<?php

/**
 * Partner Data Provider Interface
 * 
 * Contract for classes that provide partner selection data.
 * Follows Dependency Inversion Principle - high-level Views depend on this abstraction,
 * not on concrete implementations.
 * 
 * @package    KsfBankImport\Views\DataProviders
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250422
 * 
 * @uml.diagram
 * ┌────────────────────────────────────────┐
 * │  <<interface>>                         │
 * │  PartnerDataProviderInterface          │
 * ├────────────────────────────────────────┤
 * │ + getPartners(): array                 │
 * │ + getPartnerLabel(int): string|null    │
 * │ + hasPartner(int): bool                │
 * │ + getCount(): int                      │
 * └────────────────────────────────────────┘
 *            ▲
 *            │ implements
 *            │
 *   ┌────────┴─────────┬──────────────┐
 *   │                  │              │
 * Supplier      Customer        BankAccount
 * Provider      Provider        Provider
 * @enduml
 */

namespace KsfBankImport\Views\DataProviders;

/**
 * Interface for partner data providers
 * 
 * Defines the contract for classes that provide partner selection data.
 * Implementations load data once and cache it for multiple uses.
 * 
 * Design Pattern: Strategy Pattern
 * - Different strategies for loading different partner types
 * 
 * SOLID Principles:
 * - Single Responsibility: Only responsible for providing partner data
 * - Open/Closed: Open for extension (new implementations), closed for modification
 * - Liskov Substitution: All implementations must be substitutable
 * - Interface Segregation: Minimal interface, only what Views need
 * - Dependency Inversion: Views depend on this interface, not concrete classes
 * 
 * Example usage:
 * <code>
 * $provider = new SupplierDataProvider();
 * $partners = $provider->getPartners();
 * 
 * foreach ($partners as $id => $data) {
 *     echo $data['name'] . "\n";
 * }
 * 
 * if ($provider->hasPartner(42)) {
 *     echo $provider->getPartnerLabel(42);
 * }
 * </code>
 * 
 * @since 1.0.0
 */
interface PartnerDataProviderInterface
{
    /**
     * Get all partners
     * 
     * Returns associative array of partner data indexed by partner ID.
     * Data structure varies by implementation but typically includes:
     * - 'id' => Partner ID
     * - 'name' => Partner name
     * - Additional fields specific to partner type
     * 
     * Performance: Data should be cached after first load.
     * 
     * @return array<int, array> Associative array of partner data
     * 
     * @since 1.0.0
     */
    public function getPartners(): array;
    
    /**
     * Get label for a specific partner
     * 
     * Returns human-readable label (typically name) for given partner ID.
     * 
     * @param int $partnerId The partner ID
     * 
     * @return string|null Partner label or null if not found
     * 
     * @since 1.0.0
     */
    public function getPartnerLabel(int $partnerId): ?string;
    
    /**
     * Check if partner exists
     * 
     * @param int $partnerId The partner ID to check
     * 
     * @return bool True if partner exists in dataset
     * 
     * @since 1.0.0
     */
    public function hasPartner(int $partnerId): bool;
    
    /**
     * Get count of partners
     * 
     * @return int Number of partners available
     * 
     * @since 1.0.0
     */
    public function getCount(): int;
}
