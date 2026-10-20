<?php

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services\Scoring;

/**
 * @BABOK RTM: BR-011 / ARCH-011; file: Services/Scoring/AmountRangeRule.php; coverage: docs/TestCases/ (file rollup)
 * Amount Range Scoring Rule
 *
 * Recurring patterns (amounts) are more reliable for matching. Small amounts
 * are less reliable (could match many partners), while normal/large amounts
 * are more distinctive and reliable.
 *
 * Scoring:
 * - < $5: -5.0 (very unreliable, noise)
 * - $5-$25: -2.0 (somewhat unreliable)
 * - $25-$1000: +3.0 (reliable, normal range)
 * - > $1000: +2.0 (very reliable, less volatility)
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class AmountRangeRule implements ScoringRule
{
    /** @BABOK BR-011 / ARCH-011 — method: calculateScore */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        if (!isset($transaction['amount'])) {
            return 0.0;
        }

        $amountAbs = abs((float)$transaction['amount']);

        // Very small amounts (< $5) are less reliable
        if ($amountAbs < 5) {
            return -5.0;
        }

        // Small amounts ($5-$25) are somewhat unreliable
        if ($amountAbs < 25) {
            return -2.0;
        }

        // Normal amounts ($25-$1000) are reliable
        if ($amountAbs <= 1000) {
            return 3.0;
        }

        // Large amounts are very reliable (less volatility)
        return 2.0;
    }

    /** @BABOK BR-011 / ARCH-011 — method: getName */
    public function getName(): string
    {
        return 'AmountRangeRule';
    }

    /** @BABOK BR-011 / ARCH-011 — method: getMaxBoost */
    public function getMaxBoost(): float
    {
        return 3.0;
    }

    /** @BABOK BR-011 / ARCH-011 — method: getMinReduction */
    public function getMinReduction(): float
    {
        return -5.0;
    }
}
