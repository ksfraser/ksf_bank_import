<?php

/**
 * Invoice Detection Matching Rule
 *
 * Detects supplier invoice transactions and provides extra boost for matching.
 * PROD: Invoice transactions are more likely to be exact matches.
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
 * Invoice Type Detection Rule
 *
 * @since 7.6
 */
class InvoiceDetectionRule implements ScoringRule
{
    const ST_SUPPINVOICE = 20;

    /**
     * @inheritDoc
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        $is_invoice = $transaction['is_invoice'] ?? false;
        $transaction_type = (int)($transaction['type'] ?? 0);

        // ST_SUPPINVOICE = 20
        if ($is_invoice || $transaction_type === self::ST_SUPPINVOICE) {
            return 10.0;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'invoice_detection';
    }

    /**
     * @inheritDoc
     */
    public function getMaxBoost(): float
    {
        return 10.0;
    }

    /**
     * @inheritDoc
     */
    public function getMinReduction(): float
    {
        return 0;
    }
}
