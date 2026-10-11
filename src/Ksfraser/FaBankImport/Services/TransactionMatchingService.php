<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;

/**
 * TransactionMatchingService - Context-aware transaction to partner matching
 *
 * Coordinates partner matching with transaction context for improved accuracy:
 * - Extracts searchable text from transaction components
 * - Filters matches by transaction type
 * - Applies contextual adjustments to confidence
 * - Tracks matching patterns for learning
 *
 * Usage:
 *   $result = $service->matchTransaction($transaction, 'supplier');
 *   echo "Partner: " . $result['partner_id'];
 *   echo "Confidence: " . $result['confidence'];
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
class TransactionMatchingService
{
    private const MIN_CONFIDENCE_FOR_AUTO_MATCH = 75.0;

    public function __construct(private readonly KeywordMatchingService $matchingService)
    {
    }

    /**
     * Match a transaction to a partner
     *
     * Extracts searchable text from transaction, searches for partners,
     * applies contextual adjustments, and returns best match.
     *
     * @param array $transaction Transaction data with keys: account, memo, amount, type, etc.
     * @param int|null $partnerType Optional filter to specific partner type
     * @return array Match result with keys: partner_id, partner_type, confidence, original_confidence, context_adjusted
     *               or empty array if no match
     * @throws \InvalidArgumentException if transaction lacks required fields
     */
    public function matchTransaction(array $transaction, ?int $partnerType = null): array
    {
        $this->validateTransaction($transaction);

        // Build searchable text from transaction
        $searchText = $this->buildSearchText($transaction);

        if (empty($searchText)) {
            return [];
        }

        // Get partner matches
        $matches = $this->matchingService->search($searchText, $partnerType, 5);

        if (empty($matches)) {
            return [];
        }

        // Apply context-aware adjustments to top match
        $topMatch = $matches[0];
        $contextBoost = $this->calculateContextBoost($transaction, $topMatch);

        $adjustedConfidence = $this->applyConfidenceAdjustment(
            $topMatch->getConfidence()->getPercentage(),
            $contextBoost
        );

        return [
            'partner_id' => $topMatch->getPartnerId(),
            'partner_type' => $topMatch->getPartnerType(),
            'partner_detail_id' => $topMatch->getPartnerDetailId(),
            'partner_name' => $topMatch->getPartnerName(),
            'original_confidence' => $topMatch->getConfidence()->getPercentage(),
            'context_adjusted' => $contextBoost !== 0,
            'confidence' => $adjustedConfidence,
            'auto_match' => $adjustedConfidence >= self::MIN_CONFIDENCE_FOR_AUTO_MATCH,
            'matched_keywords' => array_map(
                fn($k) => $k->getValue(),
                $topMatch->getMatchedKeywords()
            ),
            'keyword_count' => $topMatch->getMatchedKeywordCount(),
            'transaction_amount' => $transaction['amount'] ?? null,
            'transaction_type' => $transaction['type'] ?? null,
        ];
    }

    /**
     * Get all candidate matches for a transaction
     *
     * Returns ranked list of all potential matches for user review.
     *
     * @param array $transaction Transaction data
     * @param int|null $partnerType Optional partner type filter
     * @param int $limit Maximum number of matches to return
     * @return array Array of match results
     */
    public function getTransactionCandidates(
        array $transaction,
        ?int $partnerType = null,
        int $limit = 5
    ): array {
        $this->validateTransaction($transaction);

        $searchText = $this->buildSearchText($transaction);

        if (empty($searchText)) {
            return [];
        }

        $matches = $this->matchingService->search($searchText, $partnerType, $limit);

        $candidates = [];
        foreach ($matches as $match) {
            $contextBoost = $this->calculateContextBoost($transaction, $match);

            $candidates[] = [
                'partner_id' => $match->getPartnerId(),
                'partner_type' => $match->getPartnerType(),
                'partner_name' => $match->getPartnerName(),
                'original_confidence' => $match->getConfidence()->getPercentage(),
                'context_adjusted' => $contextBoost !== 0,
                'context_boost' => $contextBoost,
                'confidence' => $this->applyConfidenceAdjustment(
                    $match->getConfidence()->getPercentage(),
                    $contextBoost
                ),
                'matched_keywords' => array_map(
                    fn($k) => $k->getValue(),
                    $match->getMatchedKeywords()
                ),
                'keyword_count' => $match->getMatchedKeywordCount(),
            ];
        }

        return $candidates;
    }

    /**
     * Calculate confidence adjustment based on transaction context
     *
     * Returns a boost/reduction factor (negative = reduce, positive = increase)
     * based on factors like:
     * - Transaction type consistency with partner type
     * - Amount size (recurring patterns)
     * - Timing (recurring dates)
     *
     * @param array $transaction Transaction data
     * @param KeywordMatch $match Partner match
     * @return float Confidence adjustment (-100 to +30)
     */
    private function calculateContextBoost(array $transaction, KeywordMatch $match): float
    {
        $boost = 0.0;

        // Boost for recent transactions (more reliable patterns)
        if (isset($transaction['date'])) {
            $boost += $this->getRecencyBoost($transaction['date']);
        }

        // Adjustment for amount-based patterns
        if (isset($transaction['amount'])) {
            $boost += $this->getAmountRangeBoost($transaction['amount']);
        }

        // Adjustment for type consistency
        if (isset($transaction['type'])) {
            $boost += $this->getTypeConsistencyBoost($transaction['type'], $match->getPartnerType());
        }

        // Clamp adjustment to reasonable range
        return min(30, max(-100, $boost));
    }

    /**
     * Get recency boost factor
     *
     * More recent transactions have more reliable patterns
     * Clamped at +5 percentage points
     *
     * @param string $date Transaction date (Y-m-d format)
     * @return float Boost (-2 to +5)
     */
    private function getRecencyBoost(string $date): float
    {
        $daysAgo = (int)((time() - strtotime($date)) / 86400);

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

    /**
     * Get amount range boost
     *
     * Recurring patterns (same amount) are more reliable
     *
     * @param float $amount Transaction amount
     * @return float Boost (-5 to +3)
     */
    private function getAmountRangeBoost(float $amount): float
    {
        $amountAbs = abs($amount);

        // Very small amounts (< $5) are less reliable
        if ($amountAbs < 5) {
            return -5.0;
        }

        // Small amounts ($5-$25) are somewhat unreliable
        if ($amountAbs < 25) {
            return -2.0;
        }

        // Normal amounts ($25-$1000) are reliable
        if ($amountAbs <= 1000) {
            return 3.0;
        }

        // Large amounts are very reliable (less volatility)
        return 2.0;
    }

    /**
     * Get type consistency boost
     *
     * If transaction type aligns with partner type, increase confidence
     *
     * @param string $type Transaction type (check, deposit, transfer, etc.)
     * @param int $partnerType Partner classification (supplier=1, customer=2, etc.)
     * @return float Boost (-5 to +3)
     */
    private function getTypeConsistencyBoost(string $type, int $partnerType): float
    {
        // Normalize type
        $typeUpper = strtoupper($type);

        // Supplier-type transactions
        if ($partnerType === 1) { // PT_SUPPLIER
            if (in_array($typeUpper, ['CHECK', 'BANK_TRANSFER', 'WIRE', 'ACH'], true)) {
                return 3.0;
            }
        }

        // Customer-type transactions
        if ($partnerType === 2) { // PT_CUSTOMER
            if (in_array($typeUpper, ['INVOICE', 'DEPOSIT', 'CHECK_DEPOSIT'], true)) {
                return 3.0;
            }
        }

        return 0.0;
    }

    /**
     * Apply confidence adjustment with clamping
     *
     * Prevents adjusted confidence from going below 0 or above 100
     *
     * @param float $baseConfidence Base confidence percentage
     * @param float $adjustment Adjustment amount
     * @return float Adjusted confidence (0-100)
     */
    private function applyConfidenceAdjustment(float $baseConfidence, float $adjustment): float
    {
        $adjusted = $baseConfidence + $adjustment;
        return max(0, min(100, $adjusted));
    }

    /**
     * Build searchable text from transaction
     *
     * Extracts and concatenates all text fields that should be used for matching.
     *
     * @param array $transaction Transaction data
     * @return string Concatenated search text
     */
    private function buildSearchText(array $transaction): string
    {
        $parts = [];

        // Include account/recipient info
        if (!empty($transaction['account'])) {
            $parts[] = $transaction['account'];
        }

        // Include transaction title/description
        if (!empty($transaction['transactionTitle'])) {
            $parts[] = $transaction['transactionTitle'];
        }

        // Include memo
        if (!empty($transaction['memo'])) {
            $parts[] = $transaction['memo'];
        }

        return trim(implode(' ', $parts));
    }

    /**
     * Validate transaction has required fields
     *
     * @param array $transaction Transaction to validate
     * @throws \InvalidArgumentException if missing required fields
     */
    private function validateTransaction(array $transaction): void
    {
        if (empty($transaction['transactionTitle']) && empty($transaction['memo']) && empty($transaction['account'])) {
            throw new \InvalidArgumentException(
                'Transaction must have at least one of: transactionTitle, memo, account'
            );
        }
    }
}
