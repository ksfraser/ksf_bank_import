<?php

namespace Ksfraser\FaBankImport\Contracts;

use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;
use Ksfraser\FaBankImport\DTOs\BiTransactionCollectionDTO;

/**
 * BiTransactionRepositoryInterface
 * 
 * Contract for BiTransaction data access layer.
 * Abstracts database operations from domain logic.
 * All methods return entities or DTOs - never raw database arrays.
 * 
 * @package Ksfraser\FaBankImport\Contracts
 */
interface BiTransactionRepositoryInterface
{
    /**
     * Find transaction by ID
     * 
     * @throws BiTransactionNotFoundException
     */
    public function findById(int $id): BiTransaction;

    /**
     * Find transaction or return null
     */
    public function findByIdOrNull(int $id): ?BiTransaction;

    /**
     * Find all transactions matching criteria
     * 
     * @param array $criteria Column => value pairs
     */
    public function findBy(array $criteria = [], int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Find by multiple IDs
     * 
     * @param int[] $ids
     */
    public function findByIds(array $ids): BiTransactionCollectionDTO;

    /**
     * Get all transactions (with pagination)
     */
    public function findAll(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Count total transactions matching criteria
     */
    public function count(array $criteria = []): int;

    /**
     * Save transaction (insert or update)
     */
    public function save(BiTransaction $transaction): int; // Returns ID

    /**
     * Delete transaction by ID
     */
    public function delete(int $id): bool;

    /**
     * Delete multiple transactions
     * 
     * @param int[] $ids
     */
    public function deleteMultiple(array $ids): int; // Returns count deleted

    /**
     * Check if transaction exists
     */
    public function exists(int $id): bool;

    /**
     * Find transactions by statement ID
     */
    public function findByStatementId(int $smtId, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Find matched transactions
     */
    public function findMatched(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Find unmatched transactions
     */
    public function findUnmatched(int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Find by transaction code
     */
    public function findByTransactionCode(string $code, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Find transactions in amount range
     */
    public function findByAmountRange(float $min, float $max, int $limit = 100, int $offset = 0): BiTransactionCollectionDTO;

    /**
     * Get summary statistics
     */
    public function getSummaryStats(array $criteria = []): array; // Returns: count, sum, avg, min, max
}
