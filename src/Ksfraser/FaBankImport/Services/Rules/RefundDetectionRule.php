<?php

/**
 * Refund Detection Matching Rule
 *
 * Detects customer refund transactions and provides extra boost for matching.
 * Complements InvoiceDetectionRule for bidirectional matching scenarios.
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
 * Refund Type Detection Rule
 *
 * Detects when a transaction is a customer refund (opposite of supplier invoice).
 * Used in customer matching scenarios where we pay refunds to customers.
 *
 * @since 7.6
 */
class RefundDetectionRule implements ScoringRule
{
    const ST_CUSTINVOICE = 10;
    const ST_CUSTREFUND = 11;

    /**
     * @inheritDoc
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        $is_refund = $transaction['is_refund'] ?? false;
        $transaction_type = (int)($transaction['type'] ?? 0);

        // ST_CUSTINVOICE = 10, ST_CUSTREFUND = 11
        if ($is_refund || $transaction_type === self::ST_CUSTINVOICE || $transaction_type === self::ST_CUSTREFUND) {
            return 10.0;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'refund_detection';
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
