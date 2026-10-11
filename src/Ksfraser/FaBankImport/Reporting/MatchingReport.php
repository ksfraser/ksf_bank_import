<?php

/**
 * Matching Report - Single Match Operation Result
 *
 * Captures detailed information about a single transaction-to-partner matching attempt.
 * Used for auditing, debugging, and performance analysis.
 *
 * @package    Ksfraser\FaBankImport\Reporting
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Reporting;

use DateTime;

/**
 * MatchingReport - Immutable record of a match operation
 *
 * @example
 * ```php
 * $report = MatchingReport::success(
 *     transactionId: 'TXN-001',
 *     partnerId: 123,
 *     confidence: 85.5,
 *     scoreBreakdown: ['RecencyRule' => 10, 'AmountRule' => 3],
 *     keywords: ['VENDOR', 'INC']
 * );
 *
 * echo $report->getConfidenceLevel();  // 'HIGH'
 * echo $report->getSuccessRate();       // 1.0 (100%)
 * ```
 */
class MatchingReport
{
    private const CONFIDENCE_HIGH = 70.0;
    private const CONFIDENCE_MEDIUM = 40.0;

    private string $transactionId;
    private ?int $partnerId;
    private float $confidence;
    private bool $success;
    private string $reason;
    private array $scoreBreakdown;
    private array $keywords;
    private DateTime $timestamp;
    private ?string $scoreFormula;
    private int $candidatesEvaluated;

    /**
     * @param string $transactionId Transaction identifier
     * @param ?int $partnerId Matched partner ID (null if no match)
     * @param float $confidence Final confidence score (0-100)
     * @param bool $success Whether match was successful
     * @param string $reason Human-readable reason for outcome
     * @param array<string, float> $scoreBreakdown Per-rule score contributions
     * @param array<string> $keywords Keywords extracted from transaction
     * @param DateTime $timestamp When matching occurred
     * @param ?string $scoreFormula Human-readable score formula (e.g., "RecencyRule(10)+AmountRule(3)=13")
     * @param int $candidatesEvaluated Number of candidates considered
     */
    private function __construct(
        string $transactionId,
        ?int $partnerId,
        float $confidence,
        bool $success,
        string $reason,
        array $scoreBreakdown,
        array $keywords,
        DateTime $timestamp,
        ?string $scoreFormula = null,
        int $candidatesEvaluated = 0
    ) {
        $this->transactionId = $transactionId;
        $this->partnerId = $partnerId;
        $this->confidence = max(0, min(100, $confidence));
        $this->success = $success;
        $this->reason = $reason;
        $this->scoreBreakdown = $scoreBreakdown;
        $this->keywords = $keywords;
        $this->timestamp = $timestamp;
        $this->scoreFormula = $scoreFormula;
        $this->candidatesEvaluated = $candidatesEvaluated;
    }

    /**
     * Create a successful match report
     *
     * @param string $transactionId Transaction ID
     * @param int $partnerId Matched partner ID
     * @param float $confidence Final confidence score
     * @param array<string, float> $scoreBreakdown Per-rule contributions
     * @param array<string> $keywords Extracted keywords
     * @param ?string $scoreFormula Score calculation formula
     * @param int $candidatesEvaluated Candidates evaluated
     * @return self
     */
    public static function success(
        string $transactionId,
        int $partnerId,
        float $confidence,
        array $scoreBreakdown = [],
        array $keywords = [],
        ?string $scoreFormula = null,
        int $candidatesEvaluated = 1
    ): self {
        return new self(
            $transactionId,
            $partnerId,
            $confidence,
            true,
            "Successfully matched to partner {$partnerId} with {$confidence}% confidence",
            $scoreBreakdown,
            $keywords,
            new DateTime(),
            $scoreFormula,
            $candidatesEvaluated
        );
    }

    /**
     * Create a failed match report
     *
     * @param string $transactionId Transaction ID
     * @param string $reason Why matching failed
     * @param array<string> $keywords Extracted keywords
     * @param int $candidatesEvaluated Candidates evaluated before failure
     * @return self
     */
    public static function failed(
        string $transactionId,
        string $reason,
        array $keywords = [],
        int $candidatesEvaluated = 0
    ): self {
        return new self(
            $transactionId,
            null,
            0.0,
            false,
            $reason,
            [],
            $keywords,
            new DateTime(),
            null,
            $candidatesEvaluated
        );
    }

    /**
     * Create a partial/uncertain match report
     *
     * @param string $transactionId Transaction ID
     * @param int $suggestedPartnerId Suggested partner ID
     * @param float $confidence Confidence score (expected to be LOW)
     * @param string $reason Why match is uncertain
     * @param array<string, float> $scoreBreakdown Per-rule contributions
     * @param array<string> $keywords Extracted keywords
     * @param ?string $scoreFormula Score calculation formula
     * @return self
     */
    public static function uncertain(
        string $transactionId,
        int $suggestedPartnerId,
        float $confidence,
        string $reason,
        array $scoreBreakdown = [],
        array $keywords = [],
        ?string $scoreFormula = null
    ): self {
        return new self(
            $transactionId,
            $suggestedPartnerId,
            $confidence,
            false,
            $reason,
            $scoreBreakdown,
            $keywords,
            new DateTime(),
            $scoreFormula
        );
    }

    // ====== Getters ======

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get confidence level classification
     *
     * @return string 'HIGH' (≥70%), 'MEDIUM' (40-69%), 'LOW' (<40%)
     */
    public function getConfidenceLevel(): string
    {
        if ($this->confidence >= self::CONFIDENCE_HIGH) {
            return 'HIGH';
        }
        if ($this->confidence >= self::CONFIDENCE_MEDIUM) {
            return 'MEDIUM';
        }
        return 'LOW';
    }

    /**
     * Get score breakdown by rule
     *
     * @return array<string, float> Rule name => contribution
     */
    public function getScoreBreakdown(): array
    {
        return $this->scoreBreakdown;
    }

    /**
     * Get keywords extracted from transaction
     *
     * @return array<string>
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getScoreFormula(): ?string
    {
        return $this->scoreFormula;
    }

    public function getCandidatesEvaluated(): int
    {
        return $this->candidatesEvaluated;
    }

    /**
     * Get success as numeric value for averaging
     *
     * @return float 1.0 if successful, 0.0 if not
     */
    public function getSuccessValue(): float
    {
        return $this->success ? 1.0 : 0.0;
    }

    /**
     * Convert to array for logging/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'partner_id' => $this->partnerId,
            'confidence' => $this->confidence,
            'confidence_level' => $this->getConfidenceLevel(),
            'success' => $this->success,
            'reason' => $this->reason,
            'score_breakdown' => $this->scoreBreakdown,
            'keywords' => $this->keywords,
            'score_formula' => $this->scoreFormula,
            'candidates_evaluated' => $this->candidatesEvaluated,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get total contribution from score breakdown
     *
     * @return float Sum of all rule contributions
     */
    public function getTotalScore(): float
    {
        return array_sum($this->scoreBreakdown);
    }

    /**
     * Get average score per rule
     *
     * @return float Average contribution per rule
     */
    public function getAverageRuleContribution(): float
    {
        if (empty($this->scoreBreakdown)) {
            return 0.0;
        }
        return $this->getTotalScore() / count($this->scoreBreakdown);
    }
}
