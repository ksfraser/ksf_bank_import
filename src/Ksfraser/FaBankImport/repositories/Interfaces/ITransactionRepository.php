<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repositories\Interfaces;

use Ksfraser\FaBankImport\Shared\Entities\Transaction;

/**
 * Interface for Transaction repository.
 * Manages reading/writing transactions in the main bi_transactions table.
 * 
 * Transactions marked with source='duplicate_review' come from approved duplicates.
 */
interface ITransactionRepository
{
    /**
     * Create/insert a new transaction.
     * @param Transaction $entity
     * @return int The inserted transaction ID
     */
    public function create($entity): int;

    /**
     * Update an existing transaction.
     * @param Transaction $entity
     * @return bool True if updated
     */
    public function update($entity): bool;

    /**
     * Delete a transaction by ID.
     * @param int $id
     * @return bool True if deleted
     */
    public function delete(int $id): bool;

    /**
     * Find transaction by ID.
     * @param int $id
     * @return Transaction|null
     */
    public function findById(int $id): ?object;

    /**
     * Find transaction by transaction code.
     * @param string $code
     * @return Transaction|null
     */
    public function findByCode(string $code): ?object;
}
