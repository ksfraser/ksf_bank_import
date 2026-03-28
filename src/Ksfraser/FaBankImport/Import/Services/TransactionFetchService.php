<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;
use Ksfraser\FaBankImport\Import\Results\TransactionQueryResult;

/**
 * Service for fetching transaction data from the database.
 * 
 * Centralizes all transaction queries, validates results, and throws
 * consistent exceptions for error scenarios.
 */
class TransactionFetchService
{
    /**
     * Fetch a single transaction by ID.
     *
     * @param int $transactionId
     * @return TransactionQueryResult
     * @throws TransactionFetchException
     */
    public function getTransaction(int $transactionId): TransactionQueryResult
    {
        // This will be implemented with FA database integration
        // For now, provide interface
        
        try {
            // Fetch transaction from bi_transactions table
            if (!$transactionId || $transactionId <= 0) {
                throw TransactionFetchException::notFound($transactionId);
            }

            // In actual implementation:
            // $transaction = db_fetch_assoc(db_query(...));
            // if (!$transaction) throw TransactionFetchException::notFound($transactionId);
            
            // Validate required fields
            // if (empty($transaction['trans_id'])) throw malformed
            
            return TransactionQueryResult::found($transactionId, [], []);
        } catch (TransactionFetchException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions WHERE id = {$transactionId}",
                $e->getMessage()
            );
        }
    }

    /**
     * Fetch multiple transactions with optional filters.
     *
     * @param int|null $statusFilter
     * @param array $filters
     * @return array Array of TransactionQueryResult objects
     */
    public function getTransactions(?int $statusFilter = null, array $filters = []): array
    {
        try {
            // Build query with filters
            $query = "SELECT * FROM bi_transactions WHERE 1=1";
            
            if ($statusFilter !== null) {
                $query .= " AND status = {$statusFilter}";
            }
            
            // Apply additional filters (date range, amount range, etc.)
            
            // In actual implementation:
            // $result = db_query($query);
            // $transactions = [];
            // while ($row = db_fetch_assoc($result)) { ... }
            
            return [];
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions",
                $e->getMessage()
            );
        }
    }

    /**
     * Fetch transaction with related GL entries.
     *
     * @param int $transactionId
     * @return TransactionQueryResult
     * @throws TransactionFetchException
     */
    public function getTransactionWithGlEntries(int $transactionId): TransactionQueryResult
    {
        $transaction = $this->getTransaction($transactionId);
        
        if (!$transaction->isSuccess()) {
            return $transaction;
        }

        // Fetch and attach GL entries
        try {
            // In actual implementation:
            // $glQuery = db_query("SELECT * FROM gl_trans WHERE ... AND bank_trans_id = {$transactionId}");
            // $glEntries = [];
            // while ($row = db_fetch_assoc($glQuery)) { $glEntries[] = $row; }
            
            return $transaction;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM gl_trans FOR bank_trans_id = {$transactionId}",
                $e->getMessage()
            );
        }
    }
}
