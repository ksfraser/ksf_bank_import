<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

/**
 * Service responsible for copying an approved duplicate transaction to the main bi_transactions table.
 * Handles the actual INSERT and audit logging at the data layer.
 */
interface ITransactionPostingService
{
    /**
     * Copy an approved duplicate transaction to the main transactions table.
     * 
     * Inserts into bi_transactions with:
     * - source: 'duplicate_review' (marks the origin)
     * - duplicate_id: foreign key back to bi_transactions_dupe
     * 
     * Atomically creates:
     * - bi_transactions record (and returns auto-increment id)
     * - posting_audit_log record (for audit trail)
     * 
     * @param int $duplicateId The duplicate transaction ID (from bi_transactions_dupe)
     * @param string $transactionCode The transaction code to copy
     * @param float $amount The transaction amount
     * @param string $decisionStatus The APPROVED decision status (for audit snapshot)
     * 
     * @return int The newly inserted transaction ID from bi_transactions
     * 
     * @throws \Exception If database constraint violation (e.g., duplicate transaction_code)
     * @throws \RuntimeException If transaction not found in staging table
     */
    public function copyApprovedTransaction(
        int $duplicateId,
        string $transactionCode,
        float $amount,
        string $decisionStatus
    ): int;
}
