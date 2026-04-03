<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * TransactionRepositoryInterface - Abstract interface for transaction persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface TransactionRepositoryInterface
{
    /**
     * Find transaction by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BiTransaction;

    /**
     * Find transaction by OFX identifier
     * 
     * @return BiTransaction|null if not found
     */
    public function findByFitId(string $fitId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find all transactions for a statement
     * 
     * @return BiTransaction[]
     */
    public function findByStatementId(int $statementId, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find transactions by transaction code
     * 
     * @return BiTransaction[]
     */
    public function findByCode(string $code, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find transactions by status
     * 
     * @return BiTransaction[]
     */
    public function findByStatus(string $status, ?int $limit = null, ?int $offset = null): array;

    /**
     * Save transaction (insert or update)
     */
    public function save(BiTransaction $transaction): int;

    /**
     * Update an existing transaction
     */
    public function update(BiTransaction $transaction): int;

    /**
     * Delete transaction
     */
    public function delete(int $id): bool;

    /**
     * Get total count of transactions
     */
    public function count(): int;

    /**
     * Bulk insert multiple transactions
     * 
     * @param BiTransaction[] $transactions
     * @return int[] IDs of inserted transactions
     */
    public function bulkInsert(array $transactions): array;

    /**
     * Bulk update multiple transactions
     * 
     * @param BiTransaction[] $transactions
     * @return int Number of updated transactions
     */
    public function bulkUpdate(array $transactions): int;

    /**
     * Bulk delete transactions by IDs
     * 
     * @param int[] $ids
     * @return int Number of deleted transactions
     */
    public function bulkDelete(array $ids): int;

    /**
     * Find unmatched transactions
     * 
     * @return BiTransaction[]
     */
    public function findUnmatched(?int $limit = null, ?int $offset = null): array;

    /**
     * Find transactions by amount range
     * 
     * @return BiTransaction[]
     */
    public function findByAmountRange(float $minAmount, float $maxAmount, ?int $limit = null, ?int $offset = null): array;

    /**
     * Find transactions by date range
     * 
     * @return BiTransaction[]
     */
    public function findByDateRange(string $startDate, string $endDate, ?int $limit = null, ?int $offset = null): array;
}
