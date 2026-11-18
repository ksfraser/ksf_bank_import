<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repositories\Interfaces;

use Ksfraser\FaBankImport\Shared\Entities\Transaction;

/**
 * Interface for Transaction repository.
 * Manages reading/writing transactions in the main bi_transactions table.
 * 
 * Transactions marked with source='duplicate_review' come from approved duplicates.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
interface ITransactionRepository
{
    /**
     * Create/insert a new transaction.
     * @param Transaction $entity
     * @return int The inserted transaction ID
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function create($entity): int;

    /**
     * Update an existing transaction.
     * @param Transaction $entity
     * @return bool True if updated
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function update($entity): bool;

    /**
     * Delete a transaction by ID.
     * @param int $id
     * @return bool True if deleted
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function delete(int $id): bool;

    /**
     * Find transaction by ID.
     * @param int $id
     * @return Transaction|null
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function findById(int $id): ?object;

    /**
     * Find transaction by transaction code.
     * @param string $code
     * @return Transaction|null
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function findByCode(string $code): ?object;
}
