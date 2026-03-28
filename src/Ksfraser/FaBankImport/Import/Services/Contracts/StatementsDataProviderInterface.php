<?php

namespace Ksfraser\FaBankImport\Import\Services\Contracts;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Contract for statements data provider implementations.
 * 
 * Defines how data fetching should be delegated from ProcessStatementsFetchService
 * to a separate data layer (model, repository, etc.).
 * 
 * Implementations must:
 * - Only accept whitelisted/validated input
 * - Support filtering by status, bank, account, currency
 * - Enforce date range boundaries
 * - Respect maximum limits
 */
interface StatementsDataProviderInterface
{
    /**
     * Fetch statements with optional filters.
     *
     * @param int|null $statusFilter Filter by status code
     * @param array $filters Whitelisted filters (key => value pairs)
     * @param string|null $dateFrom Fetch statements from this date (YYYY-MM-DD)
     * @param string|null $dateTo Fetch statements until this date (YYYY-MM-DD)
     * @param int $limit Maximum records to return (1-1000)
     * @return array Array of statement rows
     * @throws TransactionFetchException
     */
    public function fetch(
        ?int $statusFilter = null,
        array $filters = [],
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 100
    ): array;

    /**
     * Fetch a single statement with all its transactions.
     *
     * @param int $statementId
     * @return array Statement row with 'transactions' sub-array
     * @throws TransactionFetchException
     */
    public function fetchWithTransactions(int $statementId): array;

    /**
     * Count statements matching filter criteria.
     *
     * @param int|null $statusFilter
     * @return int
     */
    public function count(?int $statusFilter = null): int;
}
