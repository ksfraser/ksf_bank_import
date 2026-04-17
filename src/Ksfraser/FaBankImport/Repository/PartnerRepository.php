<?php

namespace Ksfraser\FaBankImport\Repository;

/**
 * Interface for partner data repository operations
 *
 * Defines CRUD operations for partner data using modern PDO patterns.
 *
 * @package Ksfraser\FaBankImport\Repository
 */
interface PartnerRepository
{
    /**
     * Create a new partner record
     *
     * @param array $data Partner data: name, partner_type, occurrence_count, last_matched_ts
     * @return int Partner ID
     * @throws \RuntimeException On creation failure
     */
    public function create(array $data): int;

    /**
     * Read a partner by ID
     *
     * @param int $id Partner ID
     * @return array|null Partner data or null if not found
     */
    public function read(int $id): ?array;

    /**
     * Update an existing partner
     *
     * @param int $id Partner ID
     * @param array $data Fields to update
     * @return bool True if updated
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a partner
     *
     * @param int $id Partner ID
     * @return bool True if deleted
     */
    public function delete(int $id): bool;

    /**
     * Find partners by name
     *
     * @param string $name Partner name (supports partial matching)
     * @param string|null $partnerType Optional type filter
     * @return array List of matching partners
     */
    public function findByName(string $name, ?string $partnerType = null): array;

    /**
     * Find partners by type
     *
     * @param string $partnerType Partner type value
     * @return array List of partners with this type
     */
    public function findByType(string $partnerType): array;

    /**
     * Get all partners
     *
     * @return array All partner records
     */
    public function findAll(): array;

    /**
     * Get partner count
     *
     * @return int Total partners
     */
    public function count(): int;

    /**
     * Increment occurrence count for a partner
     *
     * @param int $id Partner ID
     * @return bool True if incremented
     */
    public function incrementOccurrenceCount(int $id): bool;

    /**
     * Update last matched timestamp for a partner
     *
     * @param int $id Partner ID
     * @param string|null $timestamp ISO8601 timestamp or null for current time
     * @return bool True if updated
     */
    public function updateLastMatched(int $id, ?string $timestamp = null): bool;
}
