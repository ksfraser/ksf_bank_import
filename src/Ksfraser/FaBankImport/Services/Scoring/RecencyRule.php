<?php

declare(strict_types=1);

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services\Scoring;

/**
 * @BABOK CLASS ROLLUP: RecencyRule — 4 methods; BR-011 (4)
 * @BABOK RTM: BR-011 / ARCH-011; file: Services/Scoring/RecencyRule.php; coverage: docs/TestCases/ (file rollup)
 * Recency Scoring Rule
 *
 * More recent transactions have more reliable patterns, so boost confidence
 * for recent matches. Older data may have patterns that have changed.
 *
 * Scoring:
 * - Last 7 days: +5.0 (very recent)
 * - 8-30 days: +3.0 (recent)
 * - 31-90 days: +1.0 (somewhat recent)
 * - 90+ days: -2.0 (older, patterns may have changed)
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class RecencyRule implements ScoringRule
{
    /** @BABOK BR-011 / ARCH-011 — method: calculateScore */
    public function calculateScore(array $transaction, SupplierCandidate $match): float
    {
        if (!isset($transaction['date'])) {
            return 0.0;
        }

        $daysAgo = (int)((time() - strtotime($transaction['date'])) / 86400);

        if ($daysAgo <= 7) {
            return 5.0; // Very recent
        }
        if ($daysAgo <= 30) {
            return 3.0; // Recent
        }
        if ($daysAgo <= 90) {
            return 1.0; // Somewhat recent
        }

        return -2.0; // Older data (patterns may have changed)
    }

    /** @BABOK BR-011 / ARCH-011 — method: getName */
    public function getName(): string
    {
        return 'RecencyRule';
    }

    /** @BABOK BR-011 / ARCH-011 — method: getMaxBoost */
    public function getMaxBoost(): float
    {
        return 5.0;
    }

    /** @BABOK BR-011 / ARCH-011 — method: getMinReduction */
    public function getMinReduction(): float
    {
        return -2.0;
    }
}
