<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

/**
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

    public function getName(): string
    {
        return 'RecencyRule';
    }

    public function getMaxBoost(): float
    {
        return 5.0;
    }

    public function getMinReduction(): float
    {
        return -2.0;
    }
}
