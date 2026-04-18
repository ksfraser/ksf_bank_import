<?php

/**
 * Scoring Engine Factory - Configuration-Driven Builder
 *
 * Creates and configures ScoringRuleEngine instances with weights from BankImportConfig.
 * Decouples scoring configuration from rule instantiation.
 *
 * @package    Ksfraser\FaBankImport\Services\Scoring
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services\Scoring;

use Ksfraser\FaBankImport\Config\BankImportConfig;

/**
 * ScoringEngineFactory
 *
 * Builds ScoringRuleEngine instances with rules registered at weights from config.
 * Enables changing scoring behavior without modifying code - just adjust config.
 *
 * @example
 * ```php
 * // Create engine with config-driven weights
 * $engine = ScoringEngineFactory::create();
 *
 * // Score a transaction against a match
 * $adjustment = $engine->calculateAdjustment($transaction, $match);
 * echo $engine->formatScoreDetails();  // "RecencyRule(10)+AmountRule(1.5)=11.5"
 * ```
 */
class ScoringEngineFactory
{
    /**
     * Create a ScoringRuleEngine with all rules registered at config-specified weights
     *
     * Reads scoring weights from BankImportConfig and registers:
     * - RecencyRule at weight from config (default: 1.0)
     * - AmountRangeRule at weight from config (default: 1.0)
     * - TypeConsistencyRule at weight from config (default: 1.0)
     *
     * All weights default to 1.0 if not configured, meaning rules contribute equally to scoring.
     *
     * @return ScoringRuleEngine Configured engine ready for use
     *
     * @example
     * ```php
     * $engine = ScoringEngineFactory::create();
     * // Engine now has all three rules registered with config weights
     * ```
     */
    public static function create(): ScoringRuleEngine
    {
        $engine = new ScoringRuleEngine();

        // Get weights from configuration
        $weights = BankImportConfig::getScoringWeights();

        // Register all three scoring rules at their configured weights
        $engine->register(new RecencyRule(), $weights['recency']);
        $engine->register(new AmountRangeRule(), $weights['amount']);
        $engine->register(new TypeConsistencyRule(), $weights['type']);

        return $engine;
    }

    /**
     * Create a ScoringRuleEngine with custom weights (for testing or advanced usage)
     *
     * @param float|null $recencyWeight Override recency weight (null = use config)
     * @param float|null $amountWeight Override amount weight (null = use config)
     * @param float|null $typeWeight Override type weight (null = use config)
     * @return ScoringRuleEngine Configured engine
     *
     * @throws \InvalidArgumentException if any weight is provided but not positive
     *
     * @example
     * ```php
     * // Create engine with custom weights for testing
     * $engine = ScoringEngineFactory::createWithWeights(2.0, 0.5, 1.0);
     *
     * // Or mix custom with config defaults
     * $engine = ScoringEngineFactory::createWithWeights(2.0, null, null);
     * ```
     */
    public static function createWithWeights(
        ?float $recencyWeight = null,
        ?float $amountWeight = null,
        ?float $typeWeight = null
    ): ScoringRuleEngine {
        $engine = new ScoringRuleEngine();

        // Use provided weights or fall back to config
        $weights = BankImportConfig::getScoringWeights();

        $recencyWeight = $recencyWeight ?? $weights['recency'];
        $amountWeight = $amountWeight ?? $weights['amount'];
        $typeWeight = $typeWeight ?? $weights['type'];

        // Validate provided weights
        foreach (['recency' => $recencyWeight, 'amount' => $amountWeight, 'type' => $typeWeight] as $name => $weight) {
            if ($weight <= 0) {
                throw new \InvalidArgumentException(
                    "Scoring weight for '{$name}' must be positive, got: {$weight}"
                );
            }
        }

        // Register all three scoring rules at their weights
        $engine->register(new RecencyRule(), $recencyWeight);
        $engine->register(new AmountRangeRule(), $amountWeight);
        $engine->register(new TypeConsistencyRule(), $typeWeight);

        return $engine;
    }
}
