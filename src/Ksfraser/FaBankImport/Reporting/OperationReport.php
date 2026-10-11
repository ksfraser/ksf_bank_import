<?php

/**
 * Operation Report - Aggregated Matching Results
 *
 * Aggregates multiple MatchingReport instances to provide summary statistics,
 * trend analysis, and rule effectiveness metrics.
 *
 * @package    Ksfraser\FaBankImport\Reporting
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Reporting;

/**
 * OperationReport - Summary of matching operation batch
 *
 * Analyzes success rates, confidence distribution, and rule effectiveness
 * across multiple match attempts.
 *
 * @example
 * ```php
 * $operation = new OperationReport($matchingReports);
 *
 * echo $operation->getSuccessRate();           // 0.85 (85%)
 * echo $operation->getAverageConfidence();     // 71.5
 * echo $operation->getConfidenceDistribution(); // ['HIGH' => 17, 'MEDIUM' => 2, 'LOW' => 1]
 * ```
 */
class OperationReport
{
    private array $reports;
    private int $totalAttempted;
    private int $totalSuccessful;

    /**
     * @param array<MatchingReport> $reports All matching reports for this operation
     */
    public function __construct(array $reports = [])
    {
        $this->reports = array_values($reports);
        $this->totalAttempted = count($this->reports);
        $this->totalSuccessful = count(array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->isSuccess()
        ));
    }

    /**
     * Add a report to the operation
     *
     * @param MatchingReport $report
     * @return self Fluent interface
     */
    public function addReport(MatchingReport $report): self
    {
        $this->reports[] = $report;
        $this->totalAttempted++;
        if ($report->isSuccess()) {
            $this->totalSuccessful++;
        }
        return $this;
    }

    /**
     * Get all reports in operation
     *
     * @return array<MatchingReport>
     */
    public function getReports(): array
    {
        return $this->reports;
    }

    /**
     * Get total match attempts
     *
     * @return int
     */
    public function getTotalAttempted(): int
    {
        return $this->totalAttempted;
    }

    /**
     * Get successful matches
     *
     * @return int
     */
    public function getTotalSuccessful(): int
    {
        return $this->totalSuccessful;
    }

    /**
     * Get failed matches
     *
     * @return int
     */
    public function getTotalFailed(): int
    {
        return $this->totalAttempted - $this->totalSuccessful;
    }

    /**
     * Get success rate (0.0 - 1.0)
     *
     * @return float Percentage of successful matches
     */
    public function getSuccessRate(): float
    {
        if ($this->totalAttempted === 0) {
            return 0.0;
        }
        return $this->totalSuccessful / $this->totalAttempted;
    }

    /**
     * Get success rate as percentage (0-100)
     *
     * @return float Percentage
     */
    public function getSuccessRatePercentage(): float
    {
        return $this->getSuccessRate() * 100;
    }

    /**
     * Get average confidence across all reports
     *
     * @return float Average (0-100)
     */
    public function getAverageConfidence(): float
    {
        if ($this->totalAttempted === 0) {
            return 0.0;
        }
        $total = array_reduce(
            $this->reports,
            fn(float $sum, MatchingReport $r) => $sum + $r->getConfidence(),
            0.0
        );
        return $total / $this->totalAttempted;
    }

    /**
     * Get average confidence for successful matches only
     *
     * @return float Average (0-100)
     */
    public function getAverageSuccessConfidence(): float
    {
        $successReports = array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->isSuccess()
        );

        if (empty($successReports)) {
            return 0.0;
        }

        $total = array_reduce(
            $successReports,
            fn(float $sum, MatchingReport $r) => $sum + $r->getConfidence(),
            0.0
        );

        return $total / count($successReports);
    }

    /**
     * Get average confidence for failed matches only
     *
     * @return float Average (0-100)
     */
    public function getAverageFailureConfidence(): float
    {
        $failureReports = array_filter(
            $this->reports,
            fn(MatchingReport $r) => !$r->isSuccess()
        );

        if (empty($failureReports)) {
            return 0.0;
        }

        $total = array_reduce(
            $failureReports,
            fn(float $sum, MatchingReport $r) => $sum + $r->getConfidence(),
            0.0
        );

        return $total / count($failureReports);
    }

    /**
     * Get confidence distribution (HIGH/MEDIUM/LOW)
     *
     * @return array<string, int> ['HIGH' => count, 'MEDIUM' => count, 'LOW' => count]
     */
    public function getConfidenceDistribution(): array
    {
        $distribution = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];

        foreach ($this->reports as $report) {
            $level = $report->getConfidenceLevel();
            $distribution[$level]++;
        }

        return $distribution;
    }

    /**
     * Get percentile confidence levels
     *
     * @return array<string, float> Percentiles: p50, p75, p90, p95
     */
    public function getConfidencePercentiles(): array
    {
        if ($this->totalAttempted === 0) {
            return ['p50' => 0, 'p75' => 0, 'p90' => 0, 'p95' => 0];
        }

        $scores = array_map(
            fn(MatchingReport $r) => $r->getConfidence(),
            $this->reports
        );

        sort($scores);

        return [
            'p50' => $this->percentile($scores, 50),
            'p75' => $this->percentile($scores, 75),
            'p90' => $this->percentile($scores, 90),
            'p95' => $this->percentile($scores, 95),
        ];
    }

    /**
     * Get rule effectiveness analysis
     *
     * @return array<string, array> {
     *     'rule_name' => {
     *         'avg_contribution': float,
     *         'firing_count': int,
     *         'avg_when_successful': float,
     *         'avg_when_failed': float
     *     }
     * }
     */
    public function getRuleEffectiveness(): array
    {
        $ruleStats = [];

        foreach ($this->reports as $report) {
            $breakdown = $report->getScoreBreakdown();
            $isSuccess = $report->isSuccess();

            foreach ($breakdown as $ruleName => $score) {
                if (!isset($ruleStats[$ruleName])) {
                    $ruleStats[$ruleName] = [
                        'scores' => [],
                        'success_scores' => [],
                        'failure_scores' => [],
                        'firing_count' => 0,
                    ];
                }

                $ruleStats[$ruleName]['scores'][] = $score;
                $ruleStats[$ruleName]['firing_count']++;

                if ($isSuccess) {
                    $ruleStats[$ruleName]['success_scores'][] = $score;
                } else {
                    $ruleStats[$ruleName]['failure_scores'][] = $score;
                }
            }
        }

        $result = [];
        foreach ($ruleStats as $ruleName => $stats) {
            $result[$ruleName] = [
                'avg_contribution' => array_sum($stats['scores']) / count($stats['scores']),
                'firing_count' => $stats['firing_count'],
                'avg_when_successful' => !empty($stats['success_scores'])
                    ? array_sum($stats['success_scores']) / count($stats['success_scores'])
                    : 0.0,
                'avg_when_failed' => !empty($stats['failure_scores'])
                    ? array_sum($stats['failure_scores']) / count($stats['failure_scores'])
                    : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Get most impactful rule (highest average contribution)
     *
     * @return ?string Rule name with highest impact
     */
    public function getMostImpactfulRule(): ?string
    {
        $effectiveness = $this->getRuleEffectiveness();

        if (empty($effectiveness)) {
            return null;
        }

        $maxRule = null;
        $maxImpact = -PHP_FLOAT_MAX;

        foreach ($effectiveness as $ruleName => $stats) {
            $impact = abs($stats['avg_contribution']);
            if ($impact > $maxImpact) {
                $maxImpact = $impact;
                $maxRule = $ruleName;
            }
        }

        return $maxRule;
    }

    /**
     * Get average keywords per transaction
     *
     * @return float
     */
    public function getAverageKeywordsPerTransaction(): float
    {
        if ($this->totalAttempted === 0) {
            return 0.0;
        }

        $totalKeywords = array_reduce(
            $this->reports,
            fn(int $sum, MatchingReport $r) => $sum + count($r->getKeywords()),
            0
        );

        return $totalKeywords / $this->totalAttempted;
    }

    /**
     * Get average candidates evaluated per transaction
     *
     * @return float
     */
    public function getAverageCandidatesEvaluated(): float
    {
        if ($this->totalAttempted === 0) {
            return 0.0;
        }

        $total = array_reduce(
            $this->reports,
            fn(int $sum, MatchingReport $r) => $sum + $r->getCandidatesEvaluated(),
            0
        );

        return $total / $this->totalAttempted;
    }

    /**
     * Get summary statistics
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'total_attempted' => $this->getTotalAttempted(),
            'total_successful' => $this->getTotalSuccessful(),
            'total_failed' => $this->getTotalFailed(),
            'success_rate' => $this->getSuccessRate(),
            'success_rate_percentage' => $this->getSuccessRatePercentage(),
            'average_confidence' => $this->getAverageConfidence(),
            'average_success_confidence' => $this->getAverageSuccessConfidence(),
            'average_failure_confidence' => $this->getAverageFailureConfidence(),
            'confidence_distribution' => $this->getConfidenceDistribution(),
            'confidence_percentiles' => $this->getConfidencePercentiles(),
            'average_keywords' => $this->getAverageKeywordsPerTransaction(),
            'average_candidates_evaluated' => $this->getAverageCandidatesEvaluated(),
            'most_impactful_rule' => $this->getMostImpactfulRule(),
        ];
    }

    /**
     * Helper: Calculate percentile from sorted array
     *
     * @param array<float> $sortedValues Already sorted values
     * @param int $percentile Percentile (0-100)
     * @return float
     */
    private function percentile(array $sortedValues, int $percentile): float
    {
        if (empty($sortedValues)) {
            return 0.0;
        }

        $count = count($sortedValues);
        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return (float)$sortedValues[$lower];
        }

        $weight = $index - $lower;
        return (1 - $weight) * $sortedValues[$lower] + $weight * $sortedValues[$upper];
    }
}
