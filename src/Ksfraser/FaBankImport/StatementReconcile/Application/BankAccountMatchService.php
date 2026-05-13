<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

/**
 * Scores FA bank accounts against an OCR-extracted account identifier.
 *
 * Scoring rules (applied additively):
 *   1.00  Exact suffix match: the stripped account_identifier digits exactly equal
 *         the trailing N digits of the stripped bank_account_number.
 *   0.85  Substring match: account_identifier (stripped) appears anywhere within
 *         bank_account_number (stripped).
 *  +0.10  Bank-name bonus: OCR bank name partially matches FA bank_name (case-insensitive).
 *  +0.15  History bonus: a prior approved bi_reconciliation_session mapped this
 *         account_identifier to this bank_account_id (strongest signal for repeat statements).
 *
 * Results are sorted descending by score. The minimum score for pre-selection on the
 * confirmation screen is controlled by the sr_account_match_min_score config key (default 0.50).
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Application
 * @author  Kevin Fraser
 */
class BankAccountMatchService
{
    /** Exact suffix match score. */
    private const SCORE_EXACT_SUFFIX = 1.00;

    /** Substring match score. */
    private const SCORE_SUBSTRING = 0.85;

    /** Bonus when OCR bank name partially matches FA bank_name. */
    private const BONUS_BANK_NAME = 0.10;

    /** Bonus when reconciliation history maps this identifier to this account. */
    private const BONUS_HISTORY = 0.15;

    /** @var float Minimum score to pre-select a candidate. */
    private $minScore;

    /**
     * @param array $config Module config (from config.php).
     */
    public function __construct(array $config)
    {
        $this->minScore = (float) ($config['sr_account_match_min_score'] ?? 0.50);
    }

    /**
     * Score all active FA bank accounts against the given OCR account identifier.
     *
     * Requires FA bootstrap to have run (uses db_query / db_fetch / TB_PREF).
     *
     * @param string      $accountIdentifier  OCR-extracted identifier (e.g. last 4 digits, partial number).
     * @param string|null $ocrBankName        OCR-extracted bank name from statement header, or null.
     * @return array {
     *   @type array[] $candidates  Sorted desc by score. Each entry:
     *     ['bank_account_id', 'bank_account_name', 'bank_account_number', 'bank_name', 'score', 'match_method']
     *   @type int|null $bestId     ID of best candidate if score >= minScore, else null.
     * }
     */
    public function match(string $accountIdentifier, ?string $ocrBankName = null): array
    {
        $normalizedId = $this->stripNonDigits($accountIdentifier);

        // Load all active FA bank accounts.
        $accounts = $this->loadFaBankAccounts();

        // Load history map: account_identifier → bank_account_id from prior sessions.
        $historyMap = $this->loadHistoryMap($accountIdentifier);

        $candidates = [];
        foreach ($accounts as $acct) {
            $score  = 0.0;
            $method = 'none';

            $normalizedNum = $this->stripNonDigits((string) ($acct['bank_account_number'] ?? ''));

            if ($normalizedId !== '' && $normalizedNum !== '') {
                if ($normalizedNum !== '' && str_ends_with($normalizedNum, $normalizedId)) {
                    $score  = self::SCORE_EXACT_SUFFIX;
                    $method = 'exact_suffix';
                } elseif (str_contains($normalizedNum, $normalizedId)) {
                    $score  = self::SCORE_SUBSTRING;
                    $method = 'substring';
                }
            }

            // Bank-name bonus.
            if ($ocrBankName !== null && $score > 0.0) {
                $faBank = strtolower(trim((string) ($acct['bank_name'] ?? '')));
                $ocrBank = strtolower(trim($ocrBankName));
                if ($faBank !== '' && $ocrBank !== ''
                    && (str_contains($faBank, $ocrBank) || str_contains($ocrBank, $faBank))
                ) {
                    $score  += self::BONUS_BANK_NAME;
                    $method .= '+bank_name';
                }
            }

            // History bonus.
            if (isset($historyMap[(int) $acct['id']])) {
                $score  += self::BONUS_HISTORY;
                $method .= '+history';
            }

            if ($score > 0.0) {
                $candidates[] = [
                    'bank_account_id'     => (int) $acct['id'],
                    'bank_account_name'   => (string) ($acct['bank_account_name'] ?? ''),
                    'bank_account_number' => (string) ($acct['bank_account_number'] ?? ''),
                    'bank_name'           => (string) ($acct['bank_name'] ?? ''),
                    'score'               => round($score, 4),
                    'match_method'        => ltrim($method, '+'),
                ];
            }
        }

        // Sort descending by score.
        usort($candidates, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $bestId = null;
        if (!empty($candidates) && $candidates[0]['score'] >= $this->minScore) {
            $bestId = $candidates[0]['bank_account_id'];
        }

        return [
            'candidates' => $candidates,
            'best_id'    => $bestId,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Load all non-inactive FA bank accounts.
     *
     * @return array[]
     */
    protected function loadFaBankAccounts(): array
    {
        $sql = "SELECT id, bank_account_name, bank_account_number, bank_name
                  FROM " . TB_PREF . "bank_accounts
                 WHERE inactive = 0
                 ORDER BY bank_account_name ASC";

        $result   = db_query($sql, 'BankAccountMatchService: could not load FA bank accounts');
        $accounts = [];
        while ($row = db_fetch($result)) {
            $accounts[] = $row;
        }
        return $accounts;
    }

    /**
     * Return a set of bank_account_ids that were previously confirmed for the
     * given account_identifier in approved reconciliation sessions.
     *
     * Keyed by bank_account_id for O(1) lookup.
     *
     * @param string $accountIdentifier
     * @return array<int, true>
     */
    protected function loadHistoryMap(string $accountIdentifier): array
    {
        $sql = "SELECT DISTINCT s.bank_account_id
                  FROM " . TB_PREF . "bi_reconciliation_session s
                  JOIN " . TB_PREF . "bi_statement_ocr o ON o.id = s.statement_ocr_id
                 WHERE s.status = 'approved'
                   AND o.account_identifier = " . db_escape($accountIdentifier) . "
                   AND s.bank_account_id IS NOT NULL";

        $result = db_query($sql, 'BankAccountMatchService: could not load history', true);
        if (!$result) {
            return [];
        }
        $map = [];
        while ($row = db_fetch($result)) {
            $map[(int) $row['bank_account_id']] = true;
        }
        return $map;
    }

    /**
     * Strip everything but digits from a string (for account number comparison).
     *
     * @param string $value
     * @return string
     */
    protected function stripNonDigits(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }
}
