<?php

namespace KsfBankImport\Services;

require_once(__DIR__ . '/../class.bi_transactions.php');
require_once(__DIR__ . '/../class.bi_transfer_matches.php');

/**
 * Audit checks for transfer matching and JE links.
 */
class TransferMatchAuditService
{
    /** @var \bi_transactions_model */
    private $transactions;

    /** @var \bi_transfer_matches_model */
    private $transferMatches;

    public function __construct(?\bi_transactions_model $transactions = null, ?\bi_transfer_matches_model $transferMatches = null)
    {
        $this->transactions = $transactions ?: new \bi_transactions_model();
        $this->transferMatches = $transferMatches ?: new \bi_transfer_matches_model();
    }

    /**
     * @return array<string, int>
     */
    public function runAudits(int $limit = 2000): array
    {
        $rows = $this->transferMatches->get_confirmed_matches($limit);

        $pairIssues = 0;
        $jeIssues = 0;
        $flaggedPairs = 0;

        foreach ($rows as $row) {
            $debitId = (int)($row['debit_transaction_id'] ?? 0);
            $creditId = (int)($row['credit_transaction_id'] ?? 0);
            $requiresReview = 0;

            if (!$this->hasValidPairTransactions($row)) {
                $requiresReview = 1;
                $pairIssues++;
            }

            if (!$this->hasValidSharedJeReference($row)) {
                $requiresReview = 1;
                $jeIssues++;
            }

            $this->transferMatches->set_requires_review_by_pair($debitId, $creditId, $requiresReview);

            if ($requiresReview) {
                $flaggedPairs++;
            }
        }

        return [
            'rows_checked' => count($rows),
            'pair_issues' => $pairIssues,
            'je_issues' => $jeIssues,
            'rows_flagged' => $flaggedPairs,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hasValidPairTransactions(array $row): bool
    {
        $debitId = (int)($row['debit_transaction_id'] ?? 0);
        $creditId = (int)($row['credit_transaction_id'] ?? 0);
        if ($debitId <= 0 || $creditId <= 0 || $debitId === $creditId) {
            return false;
        }

        $debit = $this->transactions->get_transaction($debitId);
        $credit = $this->transactions->get_transaction($creditId);

        if (!is_array($debit) || empty($debit) || !is_array($credit) || empty($credit)) {
            return false;
        }

        if (strtoupper((string)($debit['transactionDC'] ?? '')) !== 'D') {
            return false;
        }

        if (strtoupper((string)($credit['transactionDC'] ?? '')) !== 'C') {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hasValidSharedJeReference(array $row): bool
    {
        $debit = $this->transactions->get_transaction((int)($row['debit_transaction_id'] ?? 0));
        $credit = $this->transactions->get_transaction((int)($row['credit_transaction_id'] ?? 0));

        if (!is_array($debit) || empty($debit) || !is_array($credit) || empty($credit)) {
            return false;
        }

        if ((int)($debit['status'] ?? 0) !== 1 || (int)($credit['status'] ?? 0) !== 1) {
            return true;
        }

        $debitType = (int)($debit['fa_trans_type'] ?? 0);
        $debitTypeNo = (int)($debit['fa_trans_no'] ?? 0);
        $creditType = (int)($credit['fa_trans_type'] ?? 0);
        $creditTypeNo = (int)($credit['fa_trans_no'] ?? 0);

        if ($debitType <= 0 || $debitTypeNo <= 0 || $creditType <= 0 || $creditTypeNo <= 0) {
            return false;
        }

        if ($debitType !== $creditType || $debitTypeNo !== $creditTypeNo) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM ' . TB_PREF . 'gl_trans'
            . ' WHERE type=' . db_escape($debitType)
            . ' AND type_no=' . db_escape($debitTypeNo);
        $res = db_query($sql, 'Could not verify GL transaction existence');
        $found = db_fetch($res);

        return (int)($found['cnt'] ?? 0) > 0;
    }
}
