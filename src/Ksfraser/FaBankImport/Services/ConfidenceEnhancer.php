<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Services\Scoring\ScoringRuleEngine;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * Confidence Enhancer Service
 *
 * Applies pluggable scoring rules to existing matches to provide
 * context-aware confidence adjustments. Bridges the gap between
 * simple keyword matching and transaction-aware matching.
 *
 * Usage:
 *   $enhancer = new ConfidenceEnhancer(new ScoringRuleEngine());
 *   $enhanced = $enhancer->enhance($keywords, $transaction);
 *   echo "Original: " . $enhanced['original_confidence'];
 *   echo "Adjusted: " . $enhanced['adjusted_confidence'];
 *   echo "Auto-match: " . ($enhanced['auto_match'] ? 'yes' : 'no');
 *
 * Flow:
 *   1. Accept keywords from KeywordMatchingService
 *   2. Register scoring rules in engine
 *   3. Apply rules to calculate contextual adjustments
 *   4. Adjust confidences
 *   5. Set auto-match flag for high-confidence results
 *   6. Return enhanced matches
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class ConfidenceEnhancer
{
    private const MIN_CONFIDENCE_FOR_AUTO_MATCH = 75.0;

    public function __construct(private readonly ScoringRuleEngine $engine)
    {
    }

    /**
     * Enhance a single keyword match with context-aware confidence adjustment
     *
     * Applies all registered scoring rules to calculate a context boost,
     * then adjusts the match's confidence. Sets auto_match flag if result
     * exceeds AUTO_MATCH threshold.
     *
     * @param KeywordMatch $match Match to enhance
     * @param array $transaction Transaction context data
     * @return array Enhanced match with keys: original_confidence, adjustment,
     *               adjusted_confidence, auto_match, breakdown (detailed)
     */
    public function enhance(KeywordMatch $match, array $transaction): array
    {
        $originalConfidence = $match->getConfidence()->getPercentage();

        // Calculate scoring adjustment
        $result = $this->engine->calculateAdjustmentWithBreakdown($transaction, $match);
        $adjustment = $result['adjustment'];
        $breakdown = $result['breakdown'];

        // Apply adjustment
        $adjustedConfidence = $this->applyConfidenceAdjustment(
            $originalConfidence,
            $adjustment
        );

        return [
            'matched_keywords' => array_map(
                fn($k) => $k->getValue(),
                $match->getMatchedKeywords()
            ),
            'keyword_count' => $match->getMatchedKeywordCount(),
            'original_confidence' => $originalConfidence,
            'context_adjustment' => $adjustment,
            'adjusted_confidence' => $adjustedConfidence,
            'confidence_change' => $adjustedConfidence - $originalConfidence,
            'auto_match' => $adjustedConfidence >= self::MIN_CONFIDENCE_FOR_AUTO_MATCH,
            'breakdown' => $breakdown,
            'partner_id' => $match->getPartnerId(),
            'partner_type' => $match->getPartnerType(),
            'partner_name' => $match->getPartnerName(),
        ];
    }

    /**
     * Enhance multiple keyword matches
     *
     * Applies enhancement to each match and returns array of enhanced results.
     *
     * @param array $matches Array of KeywordMatch objects
     * @param array $transaction Transaction context data
     * @return array Array of enhanced match results, sorted by adjusted confidence
     */
    public function enhanceMultiple(array $matches, array $transaction): array
    {
        $enhanced = [];

        foreach ($matches as $match) {
            $enhanced[] = $this->enhance($match, $transaction);
        }

        // Sort by adjusted confidence (descending)
        usort($enhanced, function ($a, $b) {
            return $b['adjusted_confidence'] <=> $a['adjusted_confidence'];
        });

        return $enhanced;
    }

    /**
     * Get best enhanced match from a list
     *
     * Returns the highest confidence enhanced match, or null if empty.
     *
     * @param array $matches Array of KeywordMatch objects
     * @param array $transaction Transaction context data
     * @return array|null Best enhanced match or null
     */
    public function getBestMatch(array $matches, array $transaction): ?array
    {
        if (empty($matches)) {
            return null;
        }

        $enhanced = $this->enhanceMultiple($matches, $transaction);
        return $enhanced[0] ?? null;
    }

    /**
     * Get all auto-match candidates
     *
     * Returns enhanced matches that exceed AUTO_MATCH threshold (75%+).
     * Useful for automatic matching workflows.
     *
     * @param array $matches Array of KeywordMatch objects
     * @param array $transaction Transaction context data
     * @return array Enhanced matches that qualify for auto-matching
     */
    public function getAutoMatchCandidates(array $matches, array $transaction): array
    {
        $enhanced = $this->enhanceMultiple($matches, $transaction);

        return array_filter(
            $enhanced,
            fn($m) => $m['auto_match']
        );
    }

    /**
     * Apply confidence adjustment with clamping
     *
     * Ensures result stays within 0-100 range.
     *
     * @param float $baseConfidence Base confidence percentage
     * @param float $adjustment Adjustment amount
     * @return float Adjusted confidence 0-100
     */
    private function applyConfidenceAdjustment(float $baseConfidence, float $adjustment): float
    {
        $adjusted = $baseConfidence + $adjustment;
        return max(0, min(100, $adjusted));
    }
}
