<?php

/**
 * Report Summary Response DTO
 *
 * Aggregated statistics of matching operations.
 * Provides overview of success rates, confidence metrics, and rule effectiveness.
 *
 * @author Kevin Fraser
 * @since 2.4.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * ReportSummaryResponse
 *
 * Response object containing aggregated matching statistics.
 * Includes success rates, confidence distribution, and rule effectiveness.
 */
class ReportSummaryResponse
{
    /**
     * Constructor
     *
     * @param int $totalAttempted Total matches attempted
     * @param int $totalSuccessful Total successful matches
     * @param int $totalFailed Total failed matches
     * @param float $successRate Success rate 0.0-1.0
     * @param float $averageConfidence Average confidence score
     * @param array $confidenceDistribution Distribution by level {HIGH, MEDIUM, LOW}
     * @param array $confidencePercentiles Percentiles {p50, p75, p90, p95}
     * @param string $mostImpactfulRule Most impactful rule name
     * @param float $averageKeywords Keywords per txn average
     * @param float $averageCandidatesEvaluated Candidates per txn average
     */
    public function __construct(
        private readonly int $totalAttempted,
        private readonly int $totalSuccessful,
        private readonly int $totalFailed,
        private readonly float $successRate,
        private readonly float $averageConfidence,
        private readonly array $confidenceDistribution,
        private readonly array $confidencePercentiles,
        private readonly string $mostImpactfulRule,
        private readonly float $averageKeywords,
        private readonly float $averageCandidatesEvaluated
    ) {
    }

    /**
     * Get total attempts
     */
    public function getTotalAttempted(): int
    {
        return $this->totalAttempted;
    }

    /**
     * Get total successful
     */
    public function getTotalSuccessful(): int
    {
        return $this->totalSuccessful;
    }

    /**
     * Get total failed
     */
    public function getTotalFailed(): int
    {
        return $this->totalFailed;
    }

    /**
     * Get success rate (0.0-1.0)
     */
    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    /**
     * Get average confidence
     */
    public function getAverageConfidence(): float
    {
        return $this->averageConfidence;
    }

    /**
     * Get confidence distribution
     */
    public function getConfidenceDistribution(): array
    {
        return $this->confidenceDistribution;
    }

    /**
     * Get confidence percentiles
     */
    public function getConfidencePercentiles(): array
    {
        return $this->confidencePercentiles;
    }

    /**
     * Get rule metrics
     */
    public function getRuleMetrics(): array
    {
        return [];
    }

    /**
     * Get most impactful rule
     */
    public function getMostImpactfulRule(): string
    {
        return $this->mostImpactfulRule;
    }

    /**
     * Get average keywords per transaction
     */
    public function getAverageKeywordsPerTransaction(): float
    {
        return $this->averageKeywords;
    }

    /**
     * Get average candidates evaluated
     */
    public function getAverageCandidatesEvaluated(): float
    {
        return $this->averageCandidatesEvaluated;
    }

    /**
     * Get failure reasons
     */
    public function getFailureReasons(): array
    {
        return [];
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'matching_summary' => [
                'total_attempted' => $this->totalAttempted,
                'total_successful' => $this->totalSuccessful,
                'total_failed' => $this->totalFailed,
                'success_rate_percentage' => number_format($this->successRate * 100, 2),
            ],
            'success_metrics' => [
                'success_rate' => $this->successRate,
                'total_successful' => $this->totalSuccessful,
                'total_failed' => $this->totalFailed,
            ],
            'confidence_metrics' => [
                'average_confidence' => $this->averageConfidence,
                'distribution' => $this->confidenceDistribution,
                'percentiles' => $this->confidencePercentiles,
            ],
            'rule_metrics' => [
                'most_impactful_rule' => $this->mostImpactfulRule,
            ],
            'operation_metrics' => [
                'average_keywords_per_transaction' => $this->averageKeywords,
                'average_candidates_evaluated' => $this->averageCandidatesEvaluated,
            ],
        ];
    }
}
