<?php

namespace Ksfraser\FaBankImport\Import\Services\Quality;

use Ksfraser\FaBankImport\Shared\Entities\BiStatement;

/**
 * Quality scorer for statements and transactions
 *
 * Calculates 0-100 confidence score based on:
 * - Field completeness (missing optional fields)
 * - Format compliance (valid formats, no truncation)
 * - Data consistency (balances calculated correctly)
 *
 * Implements SRP: Single responsibility = quality assessment
 */
final class QualityScorer
{
    /**
     * Base score for complete statement
     *
     * @var int
     */
    private const BASE_SCORE = 100;

    /**
     * Points deducted per missing optional field
     *
     * @var int
     */
    private const POINTS_PER_MISSING_FIELD = 5;

    /**
     * Points deducted per format inconsistency
     *
     * @var int
     */
    private const POINTS_PER_FORMAT_ISSUE = 2;

    /**
     * Points deducted for truncation detection
     *
     * @var int
     */
    private const POINTS_FOR_TRUNCATION = 10;

    /**
     * Score statement quality
     *
     * Process:
     * 1. Start with 100 points
     * 2. Deduct for missing optional fields
     * 3. Deduct for format inconsistencies
     * 4. Deduct for data integrity issues
     * 5. Return score (min 0, max 100)
     *
     * @param BiStatement $statement Statement to score
     * @return int Score from 0-100
     */
    public function scoreStatement(BiStatement $statement): int
    {
        $score = self::BASE_SCORE;

        // Check field completeness
        $score -= $this->scoreFieldCompleteness($statement);

        // Check format compliance
        $score -= $this->scoreFormatCompliance($statement);

        // Check for truncation
        $score -= $this->scoreForTruncation($statement);

        // Ensure score stays in 0-100 range
        return max(0, min(100, $score));
    }

    /**
     * Get confidence rating (human-readable)
     *
     * Maps score to confidence level
     *
     * @param int $score Score from 0-100
     * @return string One of: 'high', 'medium', 'low'
     */
    public function getConfidenceRating(int $score): string
    {
        return match (true) {
            $score >= 85 => 'high',
            $score >= 70 => 'medium',
            default => 'low'
        };
    }

    /**
     * Score based on field completeness
     *
     * Check for required fields and common optional fields
     *
     * @param BiStatement $statement
     * @return int Points deducted (0+)
     */
    private function scoreFieldCompleteness(BiStatement $statement): int
    {
        $deductions = 0;

        // Check optional fields presence
        $optionalFields = [
            'bank' => $statement->getBank(),
            'account' => $statement->getAccount(),
            'currency' => $statement->getCurrency(),
            'startBalance' => $statement->getStartBalance(),
            'endBalance' => $statement->getEndBalance()
        ];

        foreach ($optionalFields as $fieldName => $fieldValue) {
            if (empty($fieldValue) && $fieldValue !== 0 && $fieldValue !== 0.0) {
                $deductions += self::POINTS_PER_MISSING_FIELD;
            }
        }

        // Check transaction count (empty statements lose points)
        $transactionCount = count($statement->getTransactions());
        if ($transactionCount === 0) {
            $deductions += self::POINTS_PER_MISSING_FIELD * 2; // Double penalty for no transactions
        }

        return $deductions;
    }

    /**
     * Score based on format compliance
     *
     * Check format issues in statement data
     *
     * @param BiStatement $statement
     * @return int Points deducted (0+)
     */
    private function scoreFormatCompliance(BiStatement $statement): int
    {
        $deductions = 0;

        // Check currency format (should be 3-letter ISO code)
        $currency = $statement->getCurrency();
        if (!$this->isValidCurrencyCode($currency)) {
            $deductions += self::POINTS_PER_FORMAT_ISSUE;
        }

        // Check transaction data consistency
        $startBalance = $statement->getStartBalance();
        $endBalance = $statement->getEndBalance();

        // Calculate net change from transactions
        $netChange = 0.0;
        foreach ($statement->getTransactions() as $txn) {
            $amount = $txn->getTransactionAmount() ?? 0;
            $dc = $txn->getTransactionDC() ?? 'D';

            if ($dc === 'C') {
                $netChange += $amount;
            } else {
                $netChange -= $amount;
            }
        }

        // Check if closing balance matches opening + net change (with tolerance for rounding)
        $expectedClosing = $startBalance + $netChange;
        $tolerance = 0.01; // 1 cent tolerance for rounding

        if (abs($expectedClosing - $endBalance) > $tolerance) {
            $deductions += self::POINTS_PER_FORMAT_ISSUE * 2; // Higher penalty for balance mismatch
        }

        return $deductions;
    }

    /**
     * Score for truncation detection
     *
     * Check for suspiciously truncated fields
     *
     * @param BiStatement $statement
     * @return int Points deducted (0+)
     */
    private function scoreForTruncation(BiStatement $statement): int
    {
        $deductions = 0;

        // Check for truncated merchant names (very short, ending with ellipsis or similar)
        foreach ($statement->getTransactions() as $txn) {
            $title = $txn->getTransactionTitle() ?? '';

            // Check for suspicious patterns
            if (strlen($title) > 0) {
                // Ends with ellipsis or triple dot
                if (str_ends_with($title, '...') || str_ends_with($title, '…')) {
                    $deductions += self::POINTS_FOR_TRUNCATION;
                    break; // Only deduct once for any truncation
                }

                // Suspiciously short (1-2 chars)
                if (strlen($title) <= 2) {
                    $deductions += self::POINTS_PER_FORMAT_ISSUE;
                }
            }
        }

        return $deductions;
    }

    /**
     * Validate ISO 4217 currency code
     *
     * @param mixed $code
     * @return bool
     */
    private function isValidCurrencyCode($code): bool
    {
        if (!is_string($code)) {
            return false;
        }

        // Must be exactly 3 uppercase letters
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }

    /**
     * Get field completeness percentage
     *
     * @param BiStatement $statement
     * @return float Percentage (0-100)
     */
    public function getFieldCompleteness(BiStatement $statement): float
    {
        $required = 0;
        $present = 0;

        $fields = [
            'bank' => $statement->getBank(),
            'account' => $statement->getAccount(),
            'currency' => $statement->getCurrency(),
            'startBalance' => $statement->getStartBalance(),
            'endBalance' => $statement->getEndBalance(),
            'transactions' => count($statement->getTransactions())
        ];

        foreach ($fields as $fieldName => $fieldValue) {
            $required++;

            if (!empty($fieldValue) || $fieldValue === 0 || $fieldValue === 0.0) {
                $present++;
            }
        }

        return $required > 0 ? ($present / $required) * 100 : 0;
    }

    /**
     * Get format compliance score
     *
     * @param BiStatement $statement
     * @return float Percentage (0-100)
     */
    public function getFormatCompliance(BiStatement $statement): float
    {
        $checks = 0;
        $passed = 0;

        // Check 1: Valid currency code
        $checks++;
        if ($this->isValidCurrencyCode($statement->getCurrency())) {
            $passed++;
        }

        // Check 2: Balance consistency
        $checks++;
        $startBalance = $statement->getStartBalance();
        $endBalance = $statement->getEndBalance();
        $netChange = 0.0;

        foreach ($statement->getTransactions() as $txn) {
            $amount = $txn->getTransactionAmount() ?? 0;
            $dc = $txn->getTransactionDC() ?? 'D';
            $netChange += ($dc === 'C') ? $amount : -$amount;
        }

        $expectedClosing = $startBalance + $netChange;
        if (abs($expectedClosing - $endBalance) <= 0.01) {
            $passed++;
        }

        return $checks > 0 ? ($passed / $checks) * 100 : 0;
    }

    /**
     * Get overall confidence rating
     *
     * Combines score into readable assessment
     *
     * @param BiStatement $statement
     * @return string Confidence rating ('high', 'medium', 'low')
     */
    public function getConfidenceAssessment(BiStatement $statement): string
    {
        $score = $this->scoreStatement($statement);
        return $this->getConfidenceRating($score);
    }
}
