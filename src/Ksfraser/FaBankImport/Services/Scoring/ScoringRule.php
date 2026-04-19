<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * ScoringRule Interface
 *
 * Contract for pluggable scoring strategies that calculate confidence adjustments
 * based on transaction context. Each rule is independently scored and results
 * are aggregated by ScoringRuleEngine.
 *
 * Rules should be:
 * - Single-responsibility (one aspect of scoring)
 * - Stateless (no internal state)
 * - Idempotent (same input always produces same output)
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
interface ScoringRule
{
    /**
     * Calculate confidence adjustment for this rule
     *
     * @param array $transaction Transaction data with keys: account, amount, type, date, memo, etc.
     * @param SupplierCandidate $match Supplier/partner candidate
     * @return float Adjustment score (can be negative or positive, no range limit here)
     */
    public function calculateScore(array $transaction, SupplierCandidate $match): float;

    /**
     * Get human-readable name of this rule
     *
     * Used for logging and debugging to identify which rule contributed which score.
     *
     * @return string Rule name (e.g., "RecencyRule", "AmountRangeRule")
     */
    public function getName(): string;

    /**
     * Get maximum possible boost this rule can apply
     *
     * ScoringRuleEngine uses this to validate that total adjustments remain reasonable.
     * Should match the maximum value calculateScore() can return.
     *
     * @return float Maximum boost (e.g., +5.0 for recency)
     */
    public function getMaxBoost(): float;

    /**
     * Get minimum possible reduction this rule can apply
     *
     * Should match the minimum (most negative) value calculateScore() can return.
     *
     * @return float Minimum reduction (e.g., -5.0 for amount range)
     */
    public function getMinReduction(): float;
}
