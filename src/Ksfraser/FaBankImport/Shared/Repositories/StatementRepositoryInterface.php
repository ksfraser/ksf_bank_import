<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;

/**
 * StatementRepositoryInterface - Abstract interface for statement persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface StatementRepositoryInterface
{
    /**
     * Find statement by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BiStatement;

    /**
     * Find statements by bank ID
     * 
     * @return BiStatement[]
     */
    public function findByBankId(string $bankId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find statements by account ID
     * 
     * @return BiStatement[]
     */
    public function findByAcctId(string $acctId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find statements by date range
     * 
     * @return BiStatement[]
     */
    public function findByDateRange(string $startDate, string $endDate, ?int $limit = null, ?int $offset = null): array;

    /**
     * Save statement (insert or update)
     */
    public function save(BiStatement $statement): int;

    /**
     * Update an existing statement
     */
    public function update(BiStatement $statement): int;

    /**
     * Delete statement by ID
     */
    public function delete(int $id): bool;

    /**
     * Get total count of statements
     */
    public function count(): int;

    /**
     * Bulk insert multiple statements
     * 
     * @param BiStatement[] $statements
     * @return int[] IDs of inserted statements
     */
    public function bulkInsert(array $statements): array;

    /**
     * Bulk update multiple statements
     * 
     * @param BiStatement[] $statements
     * @return int Number of updated statements
     */
    public function bulkUpdate(array $statements): int;

    /**
     * Bulk delete statements by IDs
     * 
     * @param int[] $ids
     * @return int Number of deleted statements
     */
    public function bulkDelete(array $ids): int;

    /**
     * Find statements by status
     * 
     * @return BiStatement[]
     */
    public function findByStatus(string $status, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find unprocessed statements
     * 
     * @return BiStatement[]
     */
    public function findUnprocessed(?int $limit = null, ?int $offset = null): array;

    /**
     * Find processed statements
     * 
     * @return BiStatement[]
     */
    public function findProcessed(?int $limit = null, ?int $offset = null): array;

    /**
     * Count statements by status
     */
    public function countByStatus(string $status): int;
}

