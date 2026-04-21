<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Repository\ReconciliationSessionRepositoryInterface;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Service\ReconciliationCommitServiceInterface;

/**
 * Commits an approved ReconciliationSession to FA by replicating FA's native
 * bank reconciliation persistence — without touching any FA core file.
 *
 * What it does on commit:
 *  1. Loads and approves the ReconciliationSession.
 *  2. For each matched pair that carries FA composite key fields:
 *       UPDATE 0_bank_trans SET reconciled = '<statementEndDate>'
 *        WHERE type = <faTransType> AND trans_no = <faTransNo>
 *  3. Updates the bank account record:
 *       UPDATE 0_bank_accounts
 *          SET last_reconciled_date   = '<statementEndDate>',
 *              ending_reconcile_balance = <closingBalance>
 *        WHERE id = <bankAccountId>
 *  4. Persists the updated ReconciliationSession (status → 'approved') in our
 *     supplementary `bi_reconciliation_session` table.
 *
 * FA globals `db_query()`, `db_escape()`, and `TB_PREF` must be available
 * (i.e. FA's bootstrap has been executed before calling commit()).
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Application
 * @author  Kevin Fraser
 */
final class ReconciliationCommitService implements ReconciliationCommitServiceInterface
{
    /** @var ReconciliationSessionRepositoryInterface */
    private $sessionRepo;

    /**
     * @param ReconciliationSessionRepositoryInterface $sessionRepo
     */
    public function __construct(ReconciliationSessionRepositoryInterface $sessionRepo)
    {
        $this->sessionRepo = $sessionRepo;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReconciliationException If the session is not found or already approved.
     * @throws \RuntimeException       If the FA database helpers are unavailable.
     */
    public function commit(
        int $sessionId,
        int $userId,
        int $bankAccountId,
        string $statementEndDate,
        float $closingBalance
    ): void {
        if (!function_exists('db_query')) {
            throw new \RuntimeException(
                'ReconciliationCommitService requires FA db_query() — '
                . 'ensure FA session bootstrap has run before calling commit().'
            );
        }

        // 1. Load and approve the session aggregate.
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            throw ReconciliationException::sessionNotFound($sessionId);
        }

        $session->approve($userId);

        // 2. Mark each matched FA bank transaction as reconciled.
        //    Pairs with null FA keys are skipped (legacy unit-test fixtures only).
        foreach ($session->getMatchedPairs() as $pair) {
            $faType = $pair->getFaTransType();
            $faNo   = $pair->getFaTransNo();

            if ($faType === null || $faNo === null) {
                error_log(
                    'ReconciliationCommitService: pair for statementLine='
                    . $pair->getStatementLineId()
                    . ' has no FA keys — skipping 0_bank_trans update.'
                );
                continue;
            }

            $this->markFaBankTransactionReconciled($faType, $faNo, $statementEndDate);
        }

        // 3. Update bank account's last reconciled date and balance.
        $this->updateBankAccount($bankAccountId, $statementEndDate, $closingBalance);

        // 4. Persist the approved session in our metadata table.
        $this->sessionRepo->save($session);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Replicate FA's reconciliation commit for a single 0_bank_trans row.
     *
     * Sets `reconciled` to the statement end date (FA's convention for marking
     * a transaction as reconciled).  Rows already reconciled are left unchanged
     * (the AND clause guards against double-reconciliation).
     *
     * @param int    $faTransType
     * @param int    $faTransNo
     * @param string $reconciledDate  YYYY-MM-DD
     */
    private function markFaBankTransactionReconciled(
        int $faTransType,
        int $faTransNo,
        string $reconciledDate
    ): void {
        $sql = 'UPDATE ' . TB_PREF . "bank_trans
                   SET reconciled = " . db_escape($reconciledDate) . "
                 WHERE type     = " . db_escape($faTransType) . "
                   AND trans_no = " . db_escape($faTransNo) . "
                   AND (reconciled = '0000-00-00' OR reconciled IS NULL)";

        db_query($sql, 'ReconciliationCommitService: could not update 0_bank_trans.reconciled');
    }

    /**
     * Replicate FA's bank account update after reconciliation.
     *
     * @param int    $bankAccountId
     * @param string $statementEndDate  YYYY-MM-DD
     * @param float  $closingBalance
     */
    private function updateBankAccount(
        int $bankAccountId,
        string $statementEndDate,
        float $closingBalance
    ): void {
        $sql = 'UPDATE ' . TB_PREF . 'bank_accounts
                   SET last_reconciled_date    = ' . db_escape($statementEndDate) . ',
                       ending_reconcile_balance = ' . db_escape($closingBalance) . '
                 WHERE id = ' . db_escape($bankAccountId);

        db_query($sql, 'ReconciliationCommitService: could not update 0_bank_accounts');
    }
}
