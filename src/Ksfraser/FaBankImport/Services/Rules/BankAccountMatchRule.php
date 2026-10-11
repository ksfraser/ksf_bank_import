<?php

/**
 * Bank Account Matching Rule
 *
 * PROD REQUIREMENT: Bank account MUST match exactly
 * Exact match required (case-insensitive, trimmed)
 *
 * @package    Ksfraser\FaBankImport\Services\Rules
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Rules;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRule;
use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
 * Bank Account Exact Match Rule
 *
 * @since 7.6
 */
class BankAccountMatchRule implements ScoringRule
{
    /**
     * @inheritDoc
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        $transaction_account = $transaction['account'] ?? '';
        // Bank account is typically stored in partner detail, but for now we assume
        // it's passed in the context. In real usage, retrieve from partner repository
        $match_account = $transaction['partner_account'] ?? '';

        if (empty($transaction_account) || empty($match_account)) {
            return 0;
        }

        // Exact match required (case-insensitive, trimmed)
        return strcasecmp(trim($transaction_account), trim($match_account)) === 0 ? 100.0 : -50.0;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'bank_account';
    }

    /**
     * @inheritDoc
     */
    public function getMaxBoost(): float
    {
        return 100.0;
    }

    /**
     * @inheritDoc
     */
    public function getMinReduction(): float
    {
        return -50.0;
    }
}
