<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views\DataProviders;

/**
 * @BABOK CLASS ROLLUP: PartnerDataProviderInterface — 4 methods; FR-009 (4); IR-001 (4)
 * @BABOK RTM: FR-009 / IR-001; file: Views/DataProviders/PartnerDataProviderInterface.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 — method: getPartners
     * Get all partners
     * 
     * @return array<int, array> Associative array of partner data indexed by ID
     */
    public function getPartners(): array;
    
    /**
 * @BABOK FR-009 / IR-001 — method: getPartnerLabel
     * Get label for a specific partner
     * 
     * @param int $partnerId The partner ID
     * @return string|null Partner label or null if not found
     */
    public function getPartnerLabel(int $partnerId): ?string;
    
    /**
 * @BABOK FR-009 / IR-001 — method: hasPartner
     * Check if partner exists
     * 
     * @param int $partnerId The partner ID to check
     * @return bool True if partner exists in dataset
     */
    public function hasPartner(int $partnerId): bool;
    
    /**
 * @BABOK FR-009 / IR-001 — method: getCount
     * Get count of partners
     * 
     * @return int Number of partners available
     */
    public function getCount(): int;
}
