<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Service for fetching statements and transactions for process_statements.php.
 * 
 * Responsibility: Fetch statements with guarded POST access and consistent error handling.
 * Delegates data retrieval to StatementsDataProvider.
 * 
 * Guardrails:
 * - Validates and sanitizes all POST parameters before use
 * - Enforces maximum limit to prevent resource exhaustion
 * - Whitelist-only filters
 * - Date range validation
 */
class ProcessStatementsFetchService
{
    protected $dataProvider;

    public function __construct($dataProvider = null)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Fetch statements for processing with optional filters.
     *
     * Guardrails:
     * - Validates POST['limit'] is between 1-1000 (default 100)
     * - Validates date_from <= date_to if both provided
     * - Only allows whitelisted filter fields
     *
     * @param int|null $statusFilter
     * @param array $filters Additional filters (must be whitelisted)
     * @param array $post $_POST array for guarded access
     * @return array Array of statement data
     * @throws TransactionFetchException
     */
    public function fetch(?int $statusFilter = null, array $filters = [], array $post = []): array
    {
        try {
            // Guardrail: Whitelist allowed filters first
            $allowedFilters = $this->getWhitelistedFilters($filters);

            // Build query object with validation
            $query = StatementFetchQuery::fromPost($statusFilter, $post, $allowedFilters);

            // Delegate to data provider
            if (!$this->dataProvider) {
                return $this->fetchDirect($query);
            }

            return $this->dataProvider->fetch(
                $query->statusFilter,
                $query->filters,
                $query->dateFrom,
                $query->dateTo,
                $query->limit
            );
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_statements",
                $e->getMessage()
            );
        }
    }

    /**
     * Fetch a single statement with all its transactions.
     *
     * @param int $statementId
     * @return array Statement with transactions
     * @throws TransactionFetchException
     */
    public function fetchWithTransactions(int $statementId): array
    {
        try {
            if (!$statementId || $statementId <= 0) {
                throw TransactionFetchException::notFound($statementId);
            }

            // Delegate to data provider
            if (!$this->dataProvider) {
                return $this->fetchWithTransactionsDirect($statementId);
            }

            return $this->dataProvider->fetchWithTransactions($statementId);
        } catch (TransactionFetchException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_statements WHERE id = {$statementId}",
                $e->getMessage()
            );
        }
    }

    /**
     * Fetch unprocessed statements.
     *
     * @param int $limit Maximum records to return
     * @return array
     * @throws TransactionFetchException
     */
    public function fetchUnprocessed(int $limit = 50): array
    {
        $limit = $this->validateLimit($limit);
        return $this->fetch(
            0, // Assuming 0 = unprocessed status
            [],
            ['limit' => $limit]
        );
    }

    /**
     * Count total statements matching criteria.
     *
     * @param int|null $statusFilter
     * @return int
     */
    public function count(?int $statusFilter = null): int
    {
        try {
            if ($this->dataProvider) {
                return $this->dataProvider->count($statusFilter);
            }
            return $this->countDirect($statusFilter);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ===== GUARDRAIL METHODS =====

    /**
     * Validate and constrain limit parameter.
     *
     * @param mixed $limit
     * @return int Validated limit (1-1000)
     */
    protected function validateLimit($limit): int
    {
        $limit = (int)$limit;
        if ($limit <= 0 || $limit > 1000) {
            return 100; // Default
        }
        return $limit;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     *
     * @param string $date
     * @return bool
     */
    protected function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Only allow whitelisted filter fields to prevent SQL injection.
     *
     * @param array $filters
     * @return array Whitelisted filters only
     */
    protected function getWhitelistedFilters(array $filters): array
    {
        $whitelist = ['status', 'bank', 'account', 'currency'];
        $result = [];
        foreach ($whitelist as $field) {
            if (isset($filters[$field]) && $filters[$field] !== null && $filters[$field] !== '') {
                $result[$field] = $filters[$field];
            }
        }
        return $result;
    }

    // ===== DIRECT DATABASE ACCESS (fallback if no data provider) =====

    /**
     * Direct database fetch (fallback implementation).
     *
     * @param StatementFetchQuery $query Query parameters
     * @return array
     */
    protected function fetchDirect(StatementFetchQuery $query): array
    {
        // Build query
        $sql = "SELECT * FROM " . TB_PREF . "bi_statements WHERE 1=1";

        if ($query->statusFilter !== null) {
            $sql .= " AND status = " . (int)$query->statusFilter;
        }

        // Apply whitelisted filters
        foreach ($query->filters as $field => $value) {
            $sql .= " AND " . preg_replace('/[^a-zA-Z0-9_]/', '', $field) . " = " . db_escape($value);
        }

        // Date range
        if ($query->dateFrom) {
            $sql .= " AND smtDate >= " . db_escape($query->dateFrom);
        }
        if ($query->dateTo) {
            $sql .= " AND smtDate <= " . db_escape($query->dateTo);
        }

        $sql .= " ORDER BY smtDate DESC, id DESC LIMIT " . (int)$query->limit;

        $result = db_query($sql, "Could not fetch statements");
        $statements = [];
        while ($row = db_fetch_assoc($result)) {
            $statements[] = $row;
        }
        return $statements;
    }

    /**
     * Direct database fetch with transactions (fallback implementation).
     *
     * @param int $statementId
     * @return array
     */
    protected function fetchWithTransactionsDirect(int $statementId): array
    {
        $query = "SELECT * FROM " . TB_PREF . "bi_statements WHERE id = " . (int)$statementId;
        $stmt_result = db_query($query, "Could not fetch statement");
        $statement = db_fetch_assoc($stmt_result);

        if (!$statement) {
            throw TransactionFetchException::notFound($statementId);
        }

        // Fetch transactions for this statement
        $trans_query = "SELECT * FROM " . TB_PREF . "bi_transactions WHERE statement_id = " . (int)$statementId;
        $trans_result = db_query($trans_query, "Could not fetch transactions");
        $statement['transactions'] = [];
        while ($row = db_fetch_assoc($trans_result)) {
            $statement['transactions'][] = $row;
        }

        return $statement;
    }

    /**
     * Direct database count (fallback implementation).
     *
     * @param int|null $statusFilter
     * @return int
     */
    protected function countDirect(?int $statusFilter): int
    {
        $query = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "bi_statements WHERE 1=1";

        if ($statusFilter !== null) {
            $query .= " AND status = " . (int)$statusFilter;
        }

        $row = db_fetch_assoc(db_query($query, "Could not count statements"));
        return (int)($row['cnt'] ?? 0);
    }
}
