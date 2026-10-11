<?php

/**
 * Match Transaction Response DTO
 *
 * Response returning the result of a transaction match operation.
 * Contains partner details, confidence metrics, and reasoning.
 *
 * @author Kevin Fraser
 * @since 2.4.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * MatchTransactionResponse
 *
 * Response object containing match results.
 * Includes confidence metrics, partner details, and score breakdown.
 */
class MatchTransactionResponse
{
    /**
     * Constructor
     *
     * @param string $transactionId Transaction identifier
     * @param bool $success Whether match was successful
     * @param int|null $partnerId Partner ID (null if no match)
     * @param string|null $partnerName Partner name (null if no match)
     * @param float $confidence Confidence score 0-100
     * @param string $confidenceLevel HIGH|MEDIUM|LOW
     * @param string|null $scoreFormula Formula used to calculate score
     * @param array $scoreBreakdown Rule-by-rule score breakdown
     * @param array $keywords Keywords used in matching
     * @param string|null $reason Reason for failure or additional context
     */
    public function __construct(
        private readonly string $transactionId,
        private readonly bool $success,
        private readonly ?int $partnerId,
        private readonly ?string $partnerName,
        private readonly float $confidence,
        private readonly string $confidenceLevel,
        private readonly ?string $scoreFormula,
        private readonly array $scoreBreakdown,
        private readonly array $keywords,
        private readonly ?string $reason
    ) {
    }

    /**
     * Check if match was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Get matched partner ID
     */
    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    /**
     * Get matched partner name
     */
    public function getPartnerName(): ?string
    {
        return $this->partnerName;
    }

    /**
     * Get confidence score (0-100)
     */
    public function getConfidence(): float
    {
        return $this->confidence;
    }

    /**
     * Get confidence level classification
     */
    public function getConfidenceLevel(): string
    {
        return $this->confidenceLevel;
    }

    /**
     * Get score formula
     */
    public function getScoreFormula(): ?string
    {
        return $this->scoreFormula;
    }

    /**
     * Get score breakdown
     */
    public function getScoreBreakdown(): array
    {
        return $this->scoreBreakdown;
    }

    /**
     * Get keywords used in matching
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * Get reason/result message
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'success' => $this->success,
            'partner' => $this->partnerId ? [
                'id' => $this->partnerId,
                'name' => $this->partnerName,
            ] : null,
            'confidence' => [
                'score' => $this->confidence,
                'level' => $this->confidenceLevel,
                'formula' => $this->scoreFormula,
                'breakdown' => $this->scoreBreakdown,
            ],
            'keywords' => $this->keywords,
            'reason' => $this->reason,
        ];
    }
}
