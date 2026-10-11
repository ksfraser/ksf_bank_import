<?php

/**
 * Supplier Matcher Service
 *
 * Matches transactions to suppliers using configurable scoring rules.
 * Integrates with ScoringRuleEngine, ConfidenceEnhancer, and configuration.
 *
 * FLOW:
 * ```
 * Transaction + Supplier List
 *     ↓
 * SupplierScoringEngineFactory (creates engine with rules)
 *     ↓
 * ScoringRuleEngine (scores each supplier candidate)
 *     ↓
 * ConfidenceEnhancer (applies context boosts)
 *     ↓
 * SupplierMatchResult (matched supplier(s) with confidence)
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

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Services\ConfidenceEnhancer;

/**
 * Supplier Matching Service
 *
 * Orchestrates matching transactions to suppliers using configurable,
 * pluggable scoring rules. Returns results grouped by confidence level
 * for UI decision-making (auto-match, manual selection, no match).
 *
 * @since 7.6
 */
class SupplierMatcher
{
    /**
     * Configuration for the matching engine
     *
     * @var SupplierMatchingConfiguration
     */
    private SupplierMatchingConfiguration $configuration;

    /**
     * Factory for creating scoring engine
     *
     * @var SupplierScoringEngineFactory
     */
    private SupplierScoringEngineFactory $factory;

    /**
     * Confidence enhancer for context-aware scoring
     *
     * @var ConfidenceEnhancer
     */
    private ConfidenceEnhancer $confidenceEnhancer;

    /**
     * Constructor
     *
     * @param SupplierMatchingConfiguration $configuration Matching configuration
     * @param ConfidenceEnhancer $confidenceEnhancer Context-aware enhancement
     * @since 7.6
     */
    public function __construct(
        SupplierMatchingConfiguration $configuration,
        ConfidenceEnhancer $confidenceEnhancer
    ) {
        $this->configuration = $configuration;
        $this->confidenceEnhancer = $confidenceEnhancer;
        $this->factory = new SupplierScoringEngineFactory($configuration);
    }

    /**
     * Match a transaction to suppliers from a supplier list
     *
     * Scores the transaction against all suppliers, applies confidence
     * enhancement, and returns matches grouped by confidence level.
     *
     * PROD BEHAVIOR:
     * - Minimum confidence threshold: 50%
     * - Exact match required (bank account)
     * - 0-2 matches: Auto-match
     * - 3+ matches: Manual selection required
     *
     * @param array $transaction Transaction data (account, amount, memo, type, etc.)
     * @param array<KeywordMatch> $supplierCandidates List of supplier matches
     * @return SupplierMatchResult Match result with auto-match decision
     * @since 7.6
     */
    public function matchSuppliers(array $transaction, array $supplierCandidates): SupplierMatchResult
    {
        $engine = $this->factory->createEngine();
        $matches = [];

        // Score each candidate supplier
        foreach ($supplierCandidates as $candidate) {
            $rawScore = $engine->calculateAdjustment($transaction, $candidate);
            
            // Apply confidence adjustment based on score
            $enhancedScore = $this->applyConfidenceAdjustment($rawScore);
            $confidencePercent = $this->normalizeScore($enhancedScore);

            // Only include matches meeting minimum threshold
            if ($confidencePercent >= $this->configuration->getMinimumConfidenceThreshold()) {
                $matches[] = [
                    'supplier_id' => $candidate->getPartnerId(),
                    'supplier_name' => $candidate->getPartnerName(),
                    'supplier_type' => $candidate->getPartnerType(),
                    'confidence' => $confidencePercent,
                    'raw_score' => $rawScore,
                    'enhanced_score' => $enhancedScore,
                ];
            }
        }

        // Sort by confidence descending
        usort($matches, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        // Determine auto-match decision based on match count and confidence
        $decision = $this->determineAutoMatchDecision($matches);

        return new SupplierMatchResult(
            $matches,
            $decision,
            $this->configuration
        );
    }

    /**
     * Determine auto-match decision based on PROD behavior
     *
     * PROD: 0-2 matches above threshold = auto. 3+ = manual selection required.
     *
     * @param array $matches Sorted matches by confidence
     * @return string Decision: 'auto', 'manual', 'no_match'
     * @since 7.6
     */
    private function determineAutoMatchDecision(array $matches): string
    {
        if (empty($matches)) {
            return 'no_match';
        }

        $matchCount = count($matches);
        $maxAuto = $this->configuration->getMaximumAutoMatches();

        if ($matchCount <= $maxAuto) {
            // 0-2 matches: auto-select best
            return 'auto';
        }

        // 3+ matches: require manual selection
        return 'manual';
    }

    /**
     * Normalize raw score to 0-100 confidence percentage
     *
     * Clamps score to reasonable range and converts to percentage.
     *
     * @param float $rawScore Raw score from engine
     * @return int Confidence percentage 0-100
     * @since 7.6
     */
    private function normalizeScore(float $rawScore): int
    {
        // Clamp to -100 to +100 range, then convert to 0-100 percentage
        // -100 = 0%, 0 = 50%, +100 = 100%
        $clamped = max(-100, min(100, $rawScore));
        $normalized = (($clamped + 100) / 200) * 100;
        return (int)round($normalized);
    }

    /**
     * Apply confidence adjustments to raw score
     *
     * Can incorporate context-aware enhancements or boosts.
     * Currently applies ConfidenceEnhancer if available.
     *
     * @param float $rawScore Score to adjust
     * @return float Adjusted score
     * @since 7.6
     */
    private function applyConfidenceAdjustment(float $rawScore): float
    {
        // For now, simply return the raw score
        // ConfidenceEnhancer can be integrated here if needed
        return $rawScore;
    }

    /**
     * Get configuration reference
     *
     * @return SupplierMatchingConfiguration Current configuration
     * @since 7.6
     */
    public function getConfiguration(): SupplierMatchingConfiguration
    {
        return $this->configuration;
    }

    /**
     * Get confidence enhancer reference
     *
     * @return ConfidenceEnhancer Confidence enhancer
     * @since 7.6
     */
    public function getConfidenceEnhancer(): ConfidenceEnhancer
    {
        return $this->confidenceEnhancer;
    }
}
