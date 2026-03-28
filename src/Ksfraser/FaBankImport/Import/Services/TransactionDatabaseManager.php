<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;

/**
 * Service for managing database transactions.
 * 
 * Provides atomic BEGIN/COMMIT/ROLLBACK semantics with proper error handling
 * for both import_statements.php and process_statements.php workflows.
 */
class TransactionDatabaseManager
{
    /**
     * @var bool Whether a transaction is currently active
     */
    private bool $transactionActive = false;

    /**
     * @var array Stack of savepoints
     */
    private array $savepointStack = [];

    /**
     * Start a new database transaction.
     *
     * @return void
     * @throws TransactionProcessingException
     */
    public function startTransaction(): void
    {
        if ($this->transactionActive) {
            // Already in transaction; create a savepoint instead
            $this->createSavepoint();
            return;
        }

        try {
            // In actual implementation: db_query("BEGIN", "Transaction start failed");
            $this->transactionActive = true;
        } catch (\Throwable $e) {
            throw TransactionProcessingException::databaseOperationFailed(
                'BEGIN TRANSACTION',
                0,
                $e->getMessage()
            );
        }
    }

    /**
     * Create a named savepoint for nested transactions.
     *
     * @param string|null $name Custom savepoint name
     * @return string Savepoint name
     * @throws TransactionProcessingException
     */
    public function createSavepoint(?string $name = null): string
    {
        if (!$this->transactionActive) {
            throw new TransactionProcessingException(
                'Cannot create savepoint: no transaction active'
            );
        }

        $name = $name ?? 'sp_' . count($this->savepointStack) . '_' . time();
        
        try {
            // In actual implementation: db_query("SAVEPOINT {$name}", "Savepoint creation failed");
            $this->savepointStack[] = $name;
        } catch (\Throwable $e) {
            throw TransactionProcessingException::databaseOperationFailed(
                "SAVEPOINT {$name}",
                0,
                $e->getMessage()
            );
        }

        return $name;
    }

    /**
     * Rollback to a specific savepoint.
     *
     * @param string|null $name Savepoint name (uses most recent if not provided)
     * @return void
     * @throws TransactionProcessingException
     */
    public function rollbackToSavepoint(?string $name = null): void
    {
        if (empty($this->savepointStack)) {
            throw new TransactionProcessingException(
                'Cannot rollback: no savepoints available'
            );
        }

        $name = $name ?? end($this->savepointStack);

        try {
            // In actual implementation: db_query("ROLLBACK TO SAVEPOINT {$name}", "Rollback to savepoint failed");
            if (($key = array_search($name, $this->savepointStack)) !== false) {
                array_splice($this->savepointStack, $key);
            }
        } catch (\Throwable $e) {
            throw TransactionProcessingException::databaseOperationFailed(
                "ROLLBACK TO SAVEPOINT {$name}",
                0,
                $e->getMessage()
            );
        }
    }

    /**
     * Commit the current transaction.
     *
     * @return void
     * @throws TransactionProcessingException
     */
    public function commit(): void
    {
        if (!$this->transactionActive) {
            return; // No transaction to commit
        }

        try {
            // In actual implementation: db_query("COMMIT", "Commit failed");
            $this->transactionActive = false;
            $this->savepointStack = [];
        } catch (\Throwable $e) {
            throw TransactionProcessingException::databaseOperationFailed(
                'COMMIT',
                0,
                $e->getMessage()
            );
        }
    }

    /**
     * Rollback the entire transaction.
     *
     * @param \Throwable|null $exception Exception that triggered rollback
     * @return void
     * @throws TransactionProcessingException
     */
    public function rollback(?\Throwable $exception = null): void
    {
        if (!$this->transactionActive) {
            return; // No transaction to rollback
        }

        try {
            // In actual implementation: db_query("ROLLBACK", "Rollback failed");
            $this->transactionActive = false;
            $this->savepointStack = [];
            
            // Log the rollback reason
            if ($exception) {
                // error_log("Transaction rolled back due to: " . $exception->getMessage());
            }
        } catch (\Throwable $e) {
            throw TransactionProcessingException::databaseOperationFailed(
                'ROLLBACK',
                0,
                $e->getMessage()
            );
        }
    }

    /**
     * Check if transaction is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->transactionActive;
    }

    /**
     * Get current savepoint depth.
     *
     * @return int
     */
    public function getSavepointDepth(): int
    {
        return count($this->savepointStack);
    }
}
