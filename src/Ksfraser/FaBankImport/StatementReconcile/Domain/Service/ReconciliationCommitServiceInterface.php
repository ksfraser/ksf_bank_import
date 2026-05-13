<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Service;

/**
 * Contract for the commit service that posts reconciliation results back to FA.
 *
 * The concrete implementation replicates FA's bank reconciliation persistence
 * without modifying any FA core file:
 *  - Sets `0_bank_trans.reconciled` for each matched/checked entry.
 *  - Updates `0_bank_accounts.last_reconciled_date` and `ending_reconcile_balance`.
 *  - Stores a supplementary `bi_reconciliation_session` record.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Service
 * @author  Kevin Fraser
 */
interface ReconciliationCommitServiceInterface
{
    /**
     * Commit an approved ReconciliationSession to FA.
     *
     * @param int    $sessionId        ID of the approved ReconciliationSession.
     * @param int    $userId           FA user performing the commit.
     * @param int    $bankAccountId    FA 0_bank_accounts.id being reconciled.
     * @param string $statementEndDate YYYY-MM-DD — written to 0_bank_trans.reconciled.
     * @param float  $closingBalance   Statement closing balance — written to 0_bank_accounts.ending_reconcile_balance.
     * @return void
     */
    public function commit(
        int $sessionId,
        int $userId,
        int $bankAccountId,
        string $statementEndDate,
        float $closingBalance
    ): void;
}
