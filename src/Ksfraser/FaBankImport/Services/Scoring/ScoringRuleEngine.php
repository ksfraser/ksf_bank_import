<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * Scoring Rule Engine
 *
 * Orchestrates multiple pluggable scoring rules to calculate context-aware
 * confidence adjustments with configurable rule weights. Rules are applied
 * independently, weighted, aggregated, then clamped to a reasonable range.
 *
 * Supports:
 * - Weighted rule scoring (adjust importance via config in future)
 * - Detailed score breakdown with raw and weighted components
 * - Human-readable score logging: "RecencyRule(5)+AmountRule(3)=8"
 * - Debug breakdown showing calculation steps
 *
 * Architecture:
 * - Rules registered with optional weight (default 1.0)
 * - Each rule score multiplied by its weight during calculation
 * - Breakdown tracks both raw and weighted scores
 * - Result clamped to [-100, +30] range
 *
 * Usage:
 *   $engine = new ScoringRuleEngine();
 *   $engine->register(new RecencyRule(), 1.5);  // Weight 1.5x
 *   $engine->register(new AmountRangeRule());   // Weight 1.0 (default)
 *   $adjustment = $engine->calculateAdjustment($transaction, $match);
 *   echo $engine->formatScoreDetails();  // "RecencyRule(7.5)+AmountRule(3)=10.5"
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
class ScoringRuleEngine
{
    /** @var array<string, ScoringRule> Registered rules by name */
    private array $rules = [];

    /** @var array<string, float> Weight for each rule (1.0 = default) */
    private array $weights = [];

    /** @var array Last detailed breakdown calculated (for debugging) */
    private array $lastBreakdown = [];

    private const MIN_ADJUSTMENT = -100;
    private const MAX_ADJUSTMENT = 30;
    private const DEFAULT_WEIGHT = 1.0;

    /**
     * Register a scoring rule with optional weight
     *
     * Weight allows adjusting rule importance (configurable in future):
     * - 1.0 = default/neutral
     * - 1.5 = increase importance by 50%
     * - 0.5 = decrease importance by 50%
     *
     * @param ScoringRule $rule Rule to register
     * @param float $weight Optional weight multiplier (default 1.0)
     * @return $this For fluent interface
     * @throws \InvalidArgumentException if weight is not positive
     */
    public function register(ScoringRule $rule, float $weight = self::DEFAULT_WEIGHT): self
    {
        if ($weight <= 0) {
            throw new \InvalidArgumentException('Rule weight must be positive, got: ' . $weight);
        }

        $ruleName = $rule->getName();
        $this->rules[$ruleName] = $rule;
        $this->weights[$ruleName] = $weight;

        return $this;
    }

    /**
     * Calculate total confidence adjustment across all rules
     *
     * Applies all registered rules, multiplies each by its weight,
     * aggregates weighted scores, and clamps to [-100, +30] range.
     *
     * Detailed breakdown available via getScoreBreakdown() or formatScoreDetails().
     *
     * @param array $transaction Transaction data
     * @param SupplierCandidate $match Partner/supplier match
     * @return float Total adjustment, clamped to reasonable range
     */
    public function calculateAdjustment(array $transaction, SupplierCandidate $match): float
    {
        $totalScore = 0.0;
        $this->lastBreakdown = [
            'rules' => [],
            'calculation' => [],
        ];

        foreach ($this->rules as $ruleName => $rule) {
            $rawScore = $rule->calculateScore($transaction, $match);
            $weight = $this->weights[$ruleName];
            $weightedScore = $rawScore * $weight;

            $totalScore += $weightedScore;

            // Track detailed breakdown
            $this->lastBreakdown['rules'][$ruleName] = [
                'raw_score' => $rawScore,
                'weight' => $weight,
                'weighted_score' => $weightedScore,
            ];

            // Build calculation string component
            if ($weightedScore !== 0) {
                $this->lastBreakdown['calculation'][] = $ruleName . '(' . $this->formatScore($weightedScore) . ')';
            }
        }

        $this->lastBreakdown['total_raw'] = array_sum(array_map(
            fn($r) => $r['raw_score'],
            $this->lastBreakdown['rules']
        ));
        $this->lastBreakdown['total_weighted'] = $totalScore;
        $this->lastBreakdown['clamped'] = max(self::MIN_ADJUSTMENT, min(self::MAX_ADJUSTMENT, $totalScore));

        return $this->lastBreakdown['clamped'];
    }

    /**
     * Get detailed breakdown of last calculated adjustment
     *
     * Returns comprehensive breakdown with:
     * - 'rules' => [ruleName => [raw_score, weight, weighted_score]]
     * - 'total_raw' => sum of raw scores before weighting
     * - 'total_weighted' => sum of weighted scores
     * - 'clamped' => final result after clamping
     * - 'calculation' => array of calculation components
     *
     * @return array Score breakdown
     */
    public function getScoreBreakdown(): array
    {
        return $this->lastBreakdown;
    }

    /**
     * Get detailed score information for logging
     *
     * Returns comprehensive data about the last calculation:
     * - Each rule's raw score, weight, weighted score
     * - Calculation formula components
     * - Raw total, weighted total, clamped result
     *
     * Useful for debugging scoring decisions and rule contributions.
     *
     * @return array Details: rules, calculation, totals, clamped
     */
    public function getScoreDetails(): array
    {
        return [
            'rules' => $this->lastBreakdown['rules'] ?? [],
            'calculation_parts' => $this->lastBreakdown['calculation'] ?? [],
            'total_raw' => $this->lastBreakdown['total_raw'] ?? 0.0,
            'total_weighted' => $this->lastBreakdown['total_weighted'] ?? 0.0,
            'clamped' => $this->lastBreakdown['clamped'] ?? 0.0,
        ];
    }

    /**
     * Format score details as human-readable calculation string
     *
     * Returns format like: "RecencyRule(5)+AmountRule(3)=8"
     * Shows weighted scores with rule names and final total.
     *
     * @return string Calculation string (empty if no scores)
     */
    public function formatScoreDetails(): string
    {
        $parts = $this->lastBreakdown['calculation'] ?? [];

        if (empty($parts)) {
            return 'no_adjustments=0';
        }

        $formula = implode('+', array_map(function ($part) {
            // Handle negative values: "Rule(-5)" -> "-Rule(5)"
            if (str_contains($part, '(') && str_contains($part, '-')) {
                preg_match('/^(.+?)\((-[\d.]+)\)$/', $part, $matches);
                if ($matches) {
                    return '-' . $matches[1] . '(' . abs($matches[2]) . ')';
                }
            }
            return $part;
        }, $parts));

        // Replace +- with -
        $formula = str_replace('+-', '-', $formula);

        return $formula . '=' . $this->formatScore($this->lastBreakdown['clamped'] ?? 0);
    }

    /**
     * Calculate adjustments with detailed results
     *
     * Returns array with both the clamped adjustment and full breakdown.
     *
     * @param array $transaction Transaction data
     * @param SupplierCandidate $match Partner/supplier match
     * @return array Result with keys: 'adjustment' (clamped), 'breakdown' (detailed)
     */
    public function calculateAdjustmentWithBreakdown(array $transaction, SupplierCandidate $match): array
    {
        $adjustment = $this->calculateAdjustment($transaction, $match);
        return [
            'adjustment' => $adjustment,
            'breakdown' => $this->getScoreBreakdown(),
            'score_formula' => $this->formatScoreDetails(),
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
     * Get weight for a specific rule
     *
     * @param string $ruleName Rule name to get weight for
     * @return float Weight (default 1.0), or 0 if rule not registered
     */
    public function getRuleWeight(string $ruleName): float
    {
        return $this->weights[$ruleName] ?? 0.0;
    }

    /**
     * Get all rules with their weights
     *
     * @return array<string, float> Map of rule name => weight
     */
    public function getRuleWeights(): array
    {
        return $this->weights;
    }

    /**
     * Get total possible maximum boost
     *
     * Sum of all positive boosts from all registered rules, accounting for weights.
     * Useful for validation and testing.
     *
     * @return float Maximum theoretical weighted boost
     */
    public function getMaxPossibleBoost(): float
    {
        $max = 0.0;
        foreach ($this->rules as $ruleName => $rule) {
            $weight = $this->weights[$ruleName];
            $max += $rule->getMaxBoost() * $weight;
        }
        return $max;
    }

    /**
     * Get total possible maximum reduction
     *
     * Sum of all negative reductions from all registered rules, accounting for weights.
     * Useful for validation and testing.
     *
     * @return float Maximum theoretical weighted reduction (negative value)
     */
    public function getMaxPossibleReduction(): float
    {
        $min = 0.0;
        foreach ($this->rules as $ruleName => $rule) {
            $weight = $this->weights[$ruleName];
            $min += $rule->getMinReduction() * $weight;
        }
        return $min;
    }

    /**
     * Reset all registered rules and weights
     *
     * Clears the rules and breakdown lists. Useful for testing.
     *
     * @return $this For fluent interface
     */
    public function reset(): self
    {
        $this->rules = [];
        $this->weights = [];
        $this->lastBreakdown = [];
        return $this;
    }

    /**
     * Format a score value (number) for display
     *
     * @param float $score Score to format
     * @return string Formatted score with proper sign
     */
    private function formatScore(float $score): string
    {
        $formatted = $score === (int)$score ? (string)(int)$score : (string)$score;

        if ($score > 0 && !str_starts_with($formatted, '+')) {
            return '+' . $formatted;
        }

        return $formatted;
    }
}
