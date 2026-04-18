<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * Scoring Rule Engine
 *
 * Orchestrates multiple pluggable scoring rules to calculate context-aware
 * confidence adjustments. Rules are applied independently and their results
 * are aggregated, then clamped to a reasonable range.
 *
 * Architecture:
 * - Rules are registered via register()
 * - All registered rules are applied to calculate total adjustment
 * - Result is clamped to [-100, +30] range
 * - Detailed scoring breakdown available via getScoreBreakdown()
 *
 * Usage:
 *   $engine = new ScoringRuleEngine();
 *   $engine->register(new RecencyRule());
 *   $engine->register(new AmountRangeRule());
 *   $engine->register(new TypeConsistencyRule());
 *   $adjustment = $engine->calculateAdjustment($transaction, $match);
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class ScoringRuleEngine
{
    /** @var ScoringRule[] */
    private array $rules = [];

    /** @var array Last breakdown calculated (for debugging) */
    private array $lastBreakdown = [];

    private const MIN_ADJUSTMENT = -100;
    private const MAX_ADJUSTMENT = 30;

    /**
     * Register a scoring rule to be applied
     *
     * @param ScoringRule $rule Rule to register
     * @return $this For fluent interface
     */
    public function register(ScoringRule $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Calculate total confidence adjustment across all rules
     *
     * Applies all registered rules and aggregates their scores.
     * Result is clamped to [-100, +30] range.
     *
     * @param array $transaction Transaction data
     * @param KeywordMatch $match Partner match
     * @return float Total adjustment, clamped to reasonable range
     */
    public function calculateAdjustment(array $transaction, KeywordMatch $match): float
    {
        $totalScore = 0.0;
        $this->lastBreakdown = [];

        foreach ($this->rules as $rule) {
            $score = $rule->calculateScore($transaction, $match);
            $totalScore += $score;
            $this->lastBreakdown[$rule->getName()] = $score;
        }

        $this->lastBreakdown['total'] = $totalScore;
        $this->lastBreakdown['clamped'] = max(self::MIN_ADJUSTMENT, min(self::MAX_ADJUSTMENT, $totalScore));

        return $this->lastBreakdown['clamped'];
    }

    /**
     * Get detailed breakdown of last calculated adjustment
     *
     * Useful for logging/debugging. Returns array with:
     * - Each rule name => its score
     * - 'total' => unclamped sum
     * - 'clamped' => final clamped value
     *
     * @return array Score breakdown
     */
    public function getScoreBreakdown(): array
    {
        return $this->lastBreakdown;
    }

    /**
     * Calculate adjustments with detailed results
     *
     * Returns array with both the clamped adjustment and full breakdown.
     *
     * @param array $transaction Transaction data
     * @param KeywordMatch $match Partner match
     * @return array Result with keys: 'adjustment' (clamped), 'breakdown' (detailed)
     */
    public function calculateAdjustmentWithBreakdown(array $transaction, KeywordMatch $match): array
    {
        $adjustment = $this->calculateAdjustment($transaction, $match);
        return [
            'adjustment' => $adjustment,
            'breakdown' => $this->getScoreBreakdown(),
        ];
    }

    /**
     * Get count of registered rules
     *
     * @return int Number of rules
     */
    public function getRuleCount(): int
    {
        return count($this->rules);
    }

    /**
     * Get total possible maximum boost
     *
     * Sum of all positive boosts from all registered rules.
     * Useful for validation and testing.
     *
     * @return float Maximum theoretical boost
     */
    public function getMaxPossibleBoost(): float
    {
        $max = 0.0;
        foreach ($this->rules as $rule) {
            $max += $rule->getMaxBoost();
        }
        return $max;
    }

    /**
     * Get total possible maximum reduction
     *
     * Sum of all negative reductions from all registered rules.
     * Useful for validation and testing.
     *
     * @return float Maximum theoretical reduction (negative value)
     */
    public function getMaxPossibleReduction(): float
    {
        $min = 0.0;
        foreach ($this->rules as $rule) {
            $min += $rule->getMinReduction();
        }
        return $min;
    }

    /**
     * Reset all registered rules
     *
     * Clears the rules list. Useful for testing.
     *
     * @return $this For fluent interface
     */
    public function reset(): self
    {
        $this->rules = [];
        $this->lastBreakdown = [];
        return $this;
    }
}
