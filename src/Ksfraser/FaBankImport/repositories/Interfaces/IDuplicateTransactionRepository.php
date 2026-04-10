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
 */
interface IDuplicateTransactionRepository
{
    /**
     * Save a duplicate transaction
     * 
     * @param DuplicateTransaction $entity
     * @return int Inserted ID or existing ID
     */
    public function save(DuplicateTransaction $entity): int;

    /**
     * Find a transaction by ID
     * 
     * @param int $id
     * @return DuplicateTransaction
     * @throws \Exception If not found
     */
    public function findById(int $id): DuplicateTransaction;

    /**
     * Update a transaction
     * 
     * @param DuplicateTransaction $entity
     * @return void
     */
    public function update(DuplicateTransaction $entity): void;

    /**
     * Find pending duplicates with filtering and pagination
     * 
     * @param QueryFilters $filters
     * @return DuplicateTransaction[]
     */
    public function findPendingWithFilters(QueryFilters $filters): array;

    /**
     * Count pending duplicates with filtering
     * 
     * @param QueryFilters $filters
     * @return int
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
