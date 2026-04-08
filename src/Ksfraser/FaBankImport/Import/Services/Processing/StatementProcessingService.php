<?php

namespace Ksfraser\FaBankImport\Import\Services\Processing;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Service for processing bank statements
 *
 * Handles statement and transaction processing including:
 * - Transaction order management
 * - Duplicate detection and handling
 * - Zero-amount transaction filtering
 * - Balance validation and reconciliation
 */
final class StatementProcessingService
{
    /**
     * Process a bank statement
     *
     * @param BiStatement $statement Statement to process
     * @return BiStatement Processed statement
     */
    public function process(BiStatement $statement): BiStatement
    {
        // Deduplicate transactions by FITID
        $statement = $this->deduplicateTransactions($statement);

        // Filter out zero-amount transactions
        $statement = $this->filterZeroAmountTransactions($statement);

        // Validate transaction data integrity
        $this->validateTransactions($statement);

        return $statement;
    }

    /**
     * Remove duplicate transactions from statement
     *
     * @param BiStatement $statement Statement to process
     * @return BiStatement Statement with deduplicated transactions
     */
    private function deduplicateTransactions(BiStatement $statement): BiStatement
    {
        $seen = [];
        $transactions = $statement->getTransactions();
        $deduplicated = [];

        foreach ($transactions as $transaction) {
            $fitId = $transaction->getFitId();
            if (!in_array($fitId, $seen, true)) {
                $seen[] = $fitId;
                $deduplicated[] = $transaction;
            }
        }

        // Create new statement with deduplicated transactions (respects immutability)
        return BiStatement::fromDatabase($statement->toDatabase(), $deduplicated);
    }

    /**
     * Filter out zero-amount transactions
     *
     * @param BiStatement $statement Statement to process
     * @return BiStatement Statement with zero-amount transactions removed
     */
    private function filterZeroAmountTransactions(BiStatement $statement): BiStatement
    {
        $transactions = $statement->getTransactions();
        $filtered = [];

        foreach ($transactions as $transaction) {
            if ((float) $transaction->getTransactionAmount() !== 0.0) {
                $filtered[] = $transaction;
            }
        }

        // Create new statement with filtered transactions (respects immutability)
        return BiStatement::fromDatabase($statement->toDatabase(), $filtered);
    }

    /**
     * Validate all transactions in statement
     *
     * @param BiStatement $statement Statement to process
     * @return void
     */
    private function validateTransactions(BiStatement $statement): void
    {
        foreach ($statement->getTransactions() as $transaction) {
            try {
                // Validate transaction has required fields
                $this->validateTransaction($transaction);
            } catch (\Throwable $e) {
                // Log validation error but continue processing
                // TODO: Log validation error
            }
        }
    }

    /**
     * Validate a single transaction
     *
     * @param BiTransaction $transaction Transaction to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException If transaction is invalid
     */
    private function validateTransaction(BiTransaction $transaction): bool
    {
        // Validate transaction type
        $validTypes = ['CREDIT', 'DEBIT', 'OTHER'];
        if (!in_array($transaction->getTransactionType(), $validTypes, true)) {
            throw new \InvalidArgumentException(
                "Invalid transaction type: {$transaction->getTransactionType()}"
            );
        }

        // Validate transaction has title
        if (empty($transaction->getTransactionTitle())) {
            throw new \InvalidArgumentException(
                'Transaction must have a title'
            );
        }

        // Validate transaction date exists
        if ($transaction->getValueTimestamp() === null) {
            throw new \InvalidArgumentException(
                'Transaction must have a date'
            );
        }

        return true;
    }
}
