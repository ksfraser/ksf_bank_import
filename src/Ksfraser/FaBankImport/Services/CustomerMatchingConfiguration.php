<?php

/**
 * Customer Matching Configuration
 *
 * Configurable settings for customer matching with customer-optimized defaults.
 * Parallel to SupplierMatchingConfiguration but with different thresholds.
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @since      7.6 (2026-04-19)
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * Configuration for Customer Matching Rules
 *
 * Stores weights, thresholds, and tolerances for customer matching.
 * Uses fluent interface for easy configuration.
 *
 * DEFAULTS (Customer-optimized vs Supplier):
 * - Bank account weight: 100 (same - required match)
 * - Customer name weight: 20 (lower - fuzzy customer names)
 * - Amount weight: 30 (higher - refund amounts are precise)
 * - Refund detection weight: 15 (customer-specific)
 * - Minimum confidence threshold: 50% (same PROD behavior)
 * - Maximum auto matches: 2 (same PROD behavior)
 * - Amount tolerance: 2.0% (higher than supplier 1%)
 *
 * @since 7.6
 */
class CustomerMatchingConfiguration
{
    /**
     * Rule weights (used with ScoringRuleEngine weight multipliers)
     *
     * @var array<string, int>
     */
    private array $weights = [
        'bank_account' => 100,
        'customer_name' => 20,
        'amount_match' => 30,
        'refund_detection' => 15,
    ];

    /**
     * Minimum confidence required for matching (0-100)
     *
     * @var int
     */
    private int $minimumConfidenceThreshold = 50;

    /**
     * Maximum matches for auto-selection (3+ requires manual)
     *
     * @var int
     */
    private int $maximumAutoMatches = 2;

    /**
     * Amount tolerance percentage for matching
     *
     * @var float
     */
    private float $amountTolerance = 2.0;

    /**
     * Get all weights
     *
     * @return array<string, int> Weight configuration
     * @since 7.6
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Set all weights
     *
     * @param array<string, int> $weights New weights
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setWeights(array $weights): self
    {
        $this->weights = $weights;
        return $this;
    }

    /**
     * Get weight for specific rule
     *
     * @param string $rule Rule name
     * @return int Weight value
     * @since 7.6
     */
    public function getWeight(string $rule): int
    {
        return $this->weights[$rule] ?? 0;
    }

    /**
     * Set weight for specific rule
     *
     * @param string $rule Rule name
     * @param int $weight New weight
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setWeight(string $rule, int $weight): self
    {
        $this->weights[$rule] = $weight;
        return $this;
    }

    /**
     * Get minimum confidence threshold
     *
     * @return int Threshold percentage (0-100)
     * @since 7.6
     */
    public function getMinimumConfidenceThreshold(): int
    {
        return $this->minimumConfidenceThreshold;
    }

    /**
     * Set minimum confidence threshold
     *
     * @param int $threshold Threshold percentage (0-100)
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setMinimumConfidenceThreshold(int $threshold): self
    {
        $this->minimumConfidenceThreshold = max(0, min(100, $threshold));
        return $this;
    }

    /**
     * Get maximum auto-matches
     *
     * @return int Maximum matches for auto-selection
     * @since 7.6
     */
    public function getMaximumAutoMatches(): int
    {
        return $this->maximumAutoMatches;
    }

    /**
     * Set maximum auto-matches
     *
     * @param int $max Maximum matches
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setMaximumAutoMatches(int $max): self
    {
        $this->maximumAutoMatches = max(1, $max);
        return $this;
    }

    /**
     * Get amount tolerance percentage
     *
     * @return float Tolerance percentage
     * @since 7.6
     */
    public function getAmountTolerance(): float
    {
        return $this->amountTolerance;
    }

    /**
     * Set amount tolerance percentage
     *
     * @param float $tolerance Tolerance percentage
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setAmountTolerance(float $tolerance): self
    {
        $this->amountTolerance = max(0.0, $tolerance);
        return $this;
    }

    /**
     * Set PROD defaults for customer matching
     *
     * Explicitly configures PROD behavior for customer matching.
     * Same thresholds as supplier (50%, 2 max) but different rule weights.
     *
     * @return $this Fluent interface
     * @since 7.6
     */
    public function setProdDefaults(): self
    {
        $this->minimumConfidenceThreshold = 50;
        $this->maximumAutoMatches = 2;
        $this->weights = [
            'bank_account' => 100,
            'customer_name' => 20,
            'amount_match' => 30,
            'refund_detection' => 15,
        ];
        $this->amountTolerance = 2.0;
        return $this;
    }
}
