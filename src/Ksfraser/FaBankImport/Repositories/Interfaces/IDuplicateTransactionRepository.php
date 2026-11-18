<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repositories\Interfaces;

use DateTimeImmutable;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\QueryFilters;

/**
 * Interface for DuplicateTransaction repository
 * 
 * Defines the contract for persisting and retrieving duplicate transaction data.
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
interface IDuplicateTransactionRepository
{
    /**
     * Save a duplicate transaction
     * 
     * @param DuplicateTransaction $entity
     * @return int Inserted ID or existing ID
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function save(DuplicateTransaction $entity): int;

    /**
     * Find a transaction by ID
     * 
     * @param int $id
     * @return DuplicateTransaction
     * @throws \Exception If not found
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function findById(int $id): DuplicateTransaction;

    /**
     * Update a transaction
     * 
     * @param DuplicateTransaction $entity
     * @return void
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function update(DuplicateTransaction $entity): void;

    /**
     * Find pending duplicates with filtering and pagination
     * 
     * @param QueryFilters $filters
     * @return DuplicateTransaction[]
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function findPendingWithFilters(QueryFilters $filters): array;

    /**
     * Count pending duplicates with filtering
     * 
     * @param QueryFilters $filters
     * @return int
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function countPendingWithFilters(QueryFilters $filters): int;

    /**
     * Create an audit record for a decision
     * 
     * @param int $transactionId
     * @param string $newStatus
     * @param string $decidedBy
     * @param DateTimeImmutable $decidedAt
     * @param string|null $reason
     * @param string|null $notes
     * @return void
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public function auditDecision(
        int $transactionId,
        string $newStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        ?string $reason,
        ?string $notes
    ): void;
}
