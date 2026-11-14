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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006

 * MatchTransactionResponse
 *
 * Response object containing match results.
 * Includes confidence metrics, partner details, and score breakdown.
 */
class MatchTransactionResponse
{
    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Check if match was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get transaction ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get matched partner ID
     */
    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get matched partner name
     */
    public function getPartnerName(): ?string
    {
        return $this->partnerName;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get confidence score (0-100)
     */
    public function getConfidence(): float
    {
        return $this->confidence;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get confidence level classification
     */
    public function getConfidenceLevel(): string
    {
        return $this->confidenceLevel;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get score formula
     */
    public function getScoreFormula(): ?string
    {
        return $this->scoreFormula;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get score breakdown
     */
    public function getScoreBreakdown(): array
    {
        return $this->scoreBreakdown;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get keywords used in matching
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get reason/result message
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
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
