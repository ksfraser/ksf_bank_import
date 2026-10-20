<?php

namespace Ksfraser\FaBankImport\Contracts;

use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * @BABOK RTM: FR-015 / ARCH-001; file: Contracts/PartnerRepository.php; coverage: docs/TestCases/ (file rollup)
 * Partner Repository Contract
 * 
 * Defines operations for persisting and retrieving partner entities.
 * Implementations must use parameterized queries to prevent SQL injection.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
interface PartnerRepository
{
    /**
 * @BABOK FR-015 / ARCH-001 — method: getById
     * Get partner by ID
     * 
     * @param int $id Partner ID
     * @return PartnerEntity|null Partner if found, null otherwise
     */
    public function getById(int $id): ?PartnerEntity;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: getByName
     * Get partner by name and type
     * 
     * @param string $name Partner name
     * @param PartnerType $type Partner type
     * @return PartnerEntity|null Partner if found, null otherwise
     */
    public function getByName(string $name, PartnerType $type): ?PartnerEntity;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: searchByPattern
     * Search partners by pattern (name LIKE)
     * 
     * @param string $pattern Search pattern (wildcards allowed)
     * @param PartnerType|null $type Filter by type (null for all types)
     * @return PartnerEntity[] Matching partners, sorted by relevance
     */
    public function searchByPattern(string $pattern, ?PartnerType $type = null): array;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: getByType
     * Get all partners of a specific type
     * 
     * @param PartnerType $type Partner type
     * @return PartnerEntity[] All partners of that type
     */
    public function getByType(PartnerType $type): array;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: create
     * Create a new partner
     * 
     * @param PartnerEntity $partner Partner to create
     * @return int Newly assigned ID
     */
    public function create(PartnerEntity $partner): int;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: update
     * Update an existing partner
     * 
     * @param PartnerEntity $partner Partner to update (must have id > 0)
     * @throws \InvalidArgumentException If partner has id == 0
     */
    public function update(PartnerEntity $partner): void;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: delete
     * Delete a partner by ID
     * 
     * @param int $id Partner ID to delete
     * @return bool True if deleted, false if not found
     */
    public function delete(int $id): bool;
    
    /**
 * @BABOK FR-015 / ARCH-001 — method: exists
     * Check if partner exists
     * 
     * @param int $id Partner ID
     * @return bool
     */
    public function exists(int $id): bool;
}
