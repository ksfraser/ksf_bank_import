<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

/**
 * Service responsible for archiving rejected transactions.
 * Creates immutable archive records for audit trail.
 */
interface IArchiveService
{
    /**
     * Archive a rejected or skipped duplicate transaction.
     * 
     * Creates immutable record in bi_transactions_rejected_archive with:
     * - Original transaction data snapshot (JSON)
     * - Rejection reason and metadata
     * - Audit trail (who, when, why)
     * 
     * @param int $duplicateId The duplicate transaction ID
     * @param string $reason Why it was skipped (REJECTED, INVESTIGATE)
     * @param string $notes Optional detailed reason/notes
     * 
     * @return bool True if successfully archived
     * 
     * @throws \RuntimeException If transaction not found
     */
    public function archive(int $duplicateId, string $reason, string $notes = ''): bool;
}
