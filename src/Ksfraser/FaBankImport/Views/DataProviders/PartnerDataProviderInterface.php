<?php
// @BABOK RTM: FR-009 / IR-001 (domain layer)
/** @BABOK RTM: FR-009 / IR-001*/

namespace Ksfraser\FaBankImport\Views\DataProviders;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

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
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get all partners
     * 
     * @return array<int, array> Associative array of partner data indexed by ID
     */
    public function getPartners(): array;
    
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get label for a specific partner
     * 
     * @param int $partnerId The partner ID
     * @return string|null Partner label or null if not found
     */
    public function getPartnerLabel(int $partnerId): ?string;
    
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Check if partner exists
     * 
     * @param int $partnerId The partner ID to check
     * @return bool True if partner exists in dataset
     */
    public function hasPartner(int $partnerId): bool;
    
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     * Get count of partners
     * 
     * @return int Number of partners available
     */
    public function getCount(): int;
}
