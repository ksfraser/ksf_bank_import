<?php
namespace Ksfraser\FaBankImport\Views\DataProviders;

/**
 * Partner Data Provider Interface
 * 
 * Contract for classes that provide partner selection data.
 * Follows Dependency Inversion Principle - high-level Views depend on this abstraction,
 * not on concrete implementations.
 * 
 * @package Ksfraser\FaBankImport\Views\DataProviders
 * @since 20251019
 */
interface PartnerDataProviderInterface
{
    /**
     * Get all partners
     * 
     * @return array<int, array> Associative array of partner data indexed by ID
     */
    public function getPartners(): array;
    
    /**
     * Get label for a specific partner
     * 
     * @param int $partnerId The partner ID
     * @return string|null Partner label or null if not found
     */
    public function getPartnerLabel(int $partnerId): ?string;
    
    /**
     * Check if partner exists
     * 
     * @param int $partnerId The partner ID to check
     * @return bool True if partner exists in dataset
     */
    public function hasPartner(int $partnerId): bool;
    
    /**
     * Get count of partners
     * 
     * @return int Number of partners available
     */
    public function getCount(): int;
}