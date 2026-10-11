<?php

/**
 * Supplier Matching Configuration
 *
 * Configurable settings for supplier matching rules including score weights,
 * confidence thresholds, and maximum match counts. Replicates PROD bank account
 * matching behavior with configurable tolerance.
 *
 * FLOW:
 * ```
 * Configuration
 *     ├─ Weights (bank_account, vendor_name, amount_match, invoice_detection)
 *     ├─ Minimum Confidence Threshold (default: 50%)
 *     ├─ Maximum Auto Matches (default: 2, meaning 3+ requires manual selection)
 *     └─ Amount Tolerance (default: 1%)
 * ```
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
 * Configuration for Supplier Matching Rules
 *
 * Manages configurable weights and thresholds used by the scoring engine
 * when matching suppliers to transactions. Provides sensible defaults based
 * on PROD behavior while allowing customization.
 *
 * @since 7.6
 */
final class SupplierMatchingConfiguration
{
    /**
     * Score weights for each matching criterion
     *
     * @var array<string, int>
     * @since 7.6
     */
    private array $weights = [
        'bank_account' => 100,          // Bank account exact match (required)
        'vendor_name' => 30,            // Vendor name keyword search
        'amount_match' => 20,           // Amount range matching
        'invoice_detection' => 10,      // Invoice type detection
    ];

    /**
     * Minimum confidence threshold for auto-matching
     *
     * PROD: Score must be >= 50 to auto-process
     *
     * @var int
     * @since 7.6
     */
    private int $minimumConfidenceThreshold = 50;

    /**
     * Maximum number of matches for auto-selection
     *
     * PROD: 3+ matches require manual intervention (max = 2)
     *
     * @var int
     * @since 7.6
     */
    private int $maximumAutoMatches = 2;

    /**
     * Amount matching tolerance (as percentage)
     *
     * Amounts within this % are considered matching
     *
     * @var float
     * @since 7.6
     */
    private float $amountTolerance = 1.0;

    /**
     * Get score weights for all matching criteria
     *
     * @return array<string, int> Score weights indexed by criterion name
     * @since 7.6
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Set custom score weights for matching criteria
     *
     * @param array<string, int> $weights Score weights indexed by criterion name
     * @return self Fluent interface
     * @since 7.6
     */
    public function setWeights(array $weights): self
    {
        $this->weights = $weights;
        return $this;
    }

    /**
     * Get individual weight for a criterion
     *
     * @param string $criterion Criterion name (bank_account, vendor_name, etc.)
     * @return int Weight value (0-100)
     * @since 7.6
     */
    public function getWeight(string $criterion): int
    {
        return $this->weights[$criterion] ?? 0;
    }

    /**
     * Set weight for individual criterion
     *
     * @param string $criterion Criterion name
     * @param int $weight Weight value (0-100)
     * @return self Fluent interface
     * @since 7.6
     */
    public function setWeight(string $criterion, int $weight): self
    {
        $this->weights[$criterion] = $weight;
        return $this;
    }

    /**
     * Get minimum confidence threshold for auto-match
     *
     * @return int Threshold percentage (0-100)
     * @since 7.6
     */
    public function getMinimumConfidenceThreshold(): int
    {
        return $this->minimumConfidenceThreshold;
    }

    /**
     * Set minimum confidence threshold for auto-match
     *
     * @param int $threshold Threshold percentage (0-100)
     * @return self Fluent interface
     * @since 7.6
     */
    public function setMinimumConfidenceThreshold(int $threshold): self
    {
        $this->minimumConfidenceThreshold = $threshold;
        return $this;
    }

    /**
     * Get maximum number of matches for auto-selection
     *
     * @return int Maximum (3+ require manual selection)
     * @since 7.6
     */
    public function getMaximumAutoMatches(): int
    {
        return $this->maximumAutoMatches;
    }

    /**
     * Set maximum number of matches for auto-selection
     *
     * @param int $maximum Maximum count
     * @return self Fluent interface
     * @since 7.6
     */
    public function setMaximumAutoMatches(int $maximum): self
    {
        $this->maximumAutoMatches = $maximum;
        return $this;
    }

    /**
     * Get amount matching tolerance percentage
     *
     * @return float Tolerance as percentage (e.g., 1.0 = 1%)
     * @since 7.6
     */
    public function getAmountTolerance(): float
    {
        return $this->amountTolerance;
    }

    /**
     * Set amount matching tolerance percentage
     *
     * @param float $tolerance Tolerance as percentage
     * @return self Fluent interface
     * @since 7.6
     */
    public function setAmountTolerance(float $tolerance): self
    {
        $this->amountTolerance = $tolerance;
        return $this;
    }

    /**
     * Apply production defaults matching PROD behavior
     *
     * PROD BASELINE:
     * - Bank account: must match exactly (100)
     * - Confidence threshold: >= 50
     * - Max matches: 2 (3+ manual selection)
     * - Amount tolerance: 1%
     *
     * @return self Fluent interface
     * @since 7.6
     */
    public function setProdDefaults(): self
    {
        $this->weights = [
            'bank_account' => 100,
            'vendor_name' => 30,
            'amount_match' => 20,
            'invoice_detection' => 10,
        ];
        $this->minimumConfidenceThreshold = 50;
        $this->maximumAutoMatches = 2;
        $this->amountTolerance = 1.0;

        return $this;
    }
}
