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

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services\Rules;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRule;
use Ksfraser\FaBankImport\Services\Scoring\SupplierCandidate;

/**
 * @BABOK CLASS ROLLUP: InvoiceDetectionRule — 4 methods; BR-001 (4)
 * @BABOK RTM: BR-001 / ARCH-001; file: Services/Rules/InvoiceDetectionRule.php; coverage: docs/TestCases/ (file rollup)
 * Invoice Type Detection Rule
 *
 * @since 7.6
 */
class InvoiceDetectionRule implements ScoringRule
{
    const ST_SUPPINVOICE = 20;

    /**
 * @BABOK BR-001 / ARCH-001 — method: calculateScore
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
 * @BABOK BR-001 / ARCH-001 — method: getName
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'invoice_detection';
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMaxBoost
     * @inheritDoc
     */
    public function getMaxBoost(): float
    {
        return 10.0;
    }

    /**
 * @BABOK BR-001 / ARCH-001 — method: getMinReduction
     * @inheritDoc
     */
    public function getMinReduction(): float
    {
        return 0;
    }
}
