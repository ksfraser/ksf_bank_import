<?php

/**
 * Reporting Service - Matching Operation Analytics
 *
 * Central service for recording matching operations, generating reports,
 * and analyzing matching performance metrics.
 *
 * @package    Ksfraser\FaBankImport\Reporting
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Reporting;

use DateTime;

/**
 * ReportingService - Analytics and reporting for matching operations
 *
 * Tracks all matching attempts and provides aggregated analytics including
 * success rates, confidence distributions, rule effectiveness, and trend analysis.
 *
 * @example
 * ```php
 * $reporting = new ReportingService();
 *
 * // Record a successful match
 * $report = MatchingReport::success(
 *     transactionId: 'TXN-001',
 *     partnerId: 123,
 *     confidence: 85.5,
 *     scoreBreakdown: ['RecencyRule' => 10, 'AmountRule' => 3],
 *     keywords: ['VENDOR', 'INC']
 * );
 * $reporting->recordMatch($report);
 *
 * // Generate operation report
 * $operation = $reporting->generateOperationReport();
 * echo "Success rate: " . $operation->getSuccessRatePercentage() . "%";
 * ```
 */
class ReportingService
{
    private array $reports = [];
    private DateTime $sessionStart;

    public function __construct()
    {
        $this->sessionStart = new DateTime();
    }

    /**
     * Record a single match attempt
     *
     * @param MatchingReport $report Result of match operation
     * @return void
     */
    public function recordMatch(MatchingReport $report): void
    {
        $this->reports[] = $report;
    }

    /**
     * Record multiple match attempts
     *
     * @param array<MatchingReport> $reports Results to record
     * @return void
     */
    public function recordMatches(array $reports): void
    {
        foreach ($reports as $report) {
            $this->recordMatch($report);
        }
    }

    /**
     * Get total matches recorded
     *
     * @return int
     */
    public function getTotalRecorded(): int
    {
        return count($this->reports);
    }

    /**
     * Get session start time
     *
     * @return DateTime
     */
    public function getSessionStart(): DateTime
    {
        return $this->sessionStart;
    }

    /**
     * Get session duration in seconds
     *
     * @return int
     */
    public function getSessionDuration(): int
    {
        return (int)(new DateTime())->diff($this->sessionStart)->s;
    }

    /**
     * Generate operation report for all recorded matches
     *
     * @return OperationReport
     */
    public function generateOperationReport(): OperationReport
    {
        return new OperationReport($this->reports);
    }

    /**
     * Generate operation report for successful matches only
     *
     * @return OperationReport
     */
    public function generateSuccessReport(): OperationReport
    {
        $successful = array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->isSuccess()
        );
        return new OperationReport($successful);
    }

    /**
     * Generate operation report for failed matches only
     *
     * @return OperationReport
     */
    public function generateFailureReport(): OperationReport
    {
        $failed = array_filter(
            $this->reports,
            fn(MatchingReport $r) => !$r->isSuccess()
        );
        return new OperationReport($failed);
    }

    /**
     * Get reports matching transaction ID
     *
     * @param string $transactionId
     * @return array<MatchingReport>
     */
    public function getReportsByTransaction(string $transactionId): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getTransactionId() === $transactionId
        );
    }

    /**
     * Get reports for specific partner
     *
     * @param int $partnerId
     * @return array<MatchingReport>
     */
    public function getReportsByPartner(int $partnerId): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getPartnerId() === $partnerId
        );
    }

    /**
     * Get reports with confidence in specific range
     *
     * @param float $minConfidence Minimum confidence (0-100)
     * @param float $maxConfidence Maximum confidence (0-100)
     * @return array<MatchingReport>
     */
    public function getReportsByConfidenceRange(float $minConfidence, float $maxConfidence): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getConfidence() >= $minConfidence && $r->getConfidence() <= $maxConfidence
        );
    }

    /**
     * Get reports before specific time
     *
     * @param DateTime $before
     * @return array<MatchingReport>
     */
    public function getReportsBefore(DateTime $before): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getTimestamp() <= $before
        );
    }

    /**
     * Get reports after specific time
     *
     * @param DateTime $after
     * @return array<MatchingReport>
     */
    public function getReportsAfter(DateTime $after): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getTimestamp() >= $after
        );
    }

    /**
     * Get reports with specific confidence level
     *
     * @param string $level 'HIGH', 'MEDIUM', or 'LOW'
     * @return array<MatchingReport>
     */
    public function getReportsByConfidenceLevel(string $level): array
    {
        return array_filter(
            $this->reports,
            fn(MatchingReport $r) => $r->getConfidenceLevel() === $level
        );
    }

    /**
     * Find transaction IDs with lowest confidence scores
     *
     * @param int $limit Number of results
     * @return array<string, float> Transaction ID => confidence
     */
    public function getLowestConfidenceTransactions(int $limit = 10): array
    {
        $grouped = [];
        foreach ($this->reports as $report) {
            $txnId = $report->getTransactionId();
            if (!isset($grouped[$txnId]) || $report->getConfidence() < $grouped[$txnId]) {
                $grouped[$txnId] = $report->getConfidence();
            }
        }

        asort($grouped);
        return array_slice($grouped, 0, $limit, true);
    }

    /**
     * Find transaction IDs with highest confidence scores
     *
     * @param int $limit Number of results
     * @return array<string, float> Transaction ID => confidence
     */
    public function getHighestConfidenceTransactions(int $limit = 10): array
    {
        $grouped = [];
        foreach ($this->reports as $report) {
            $txnId = $report->getTransactionId();
            if (!isset($grouped[$txnId]) || $report->getConfidence() > $grouped[$txnId]) {
                $grouped[$txnId] = $report->getConfidence();
            }
        }

        arsort($grouped);
        return array_slice($grouped, 0, $limit, true);
    }

    /**
     * Find partners with highest match success rate
     *
     * @param int $minMatches Minimum matches to include
     * @param int $limit Number of results
     * @return array<int, float> Partner ID => success rate
     */
    public function getTopPartnersBySuccessRate(int $minMatches = 5, int $limit = 10): array
    {
        $partnerStats = [];

        foreach ($this->reports as $report) {
            $partnerId = $report->getPartnerId();
            if ($partnerId === null) {
                continue;
            }

            if (!isset($partnerStats[$partnerId])) {
                $partnerStats[$partnerId] = ['success' => 0, 'total' => 0];
            }

            $partnerStats[$partnerId]['total']++;
            if ($report->isSuccess()) {
                $partnerStats[$partnerId]['success']++;
            }
        }

        // Filter by minimum matches and calculate rates
        $rates = [];
        foreach ($partnerStats as $partnerId => $stats) {
            if ($stats['total'] >= $minMatches) {
                $rates[$partnerId] = $stats['success'] / $stats['total'];
            }
        }

        arsort($rates);
        return array_slice($rates, 0, $limit, true);
    }

    /**
     * Get failure reasons summary
     *
     * @return array<string, int> Reason => count
     */
    public function getFailureReasonsSummary(): array
    {
        $reasons = [];

        foreach ($this->reports as $report) {
            if (!$report->isSuccess()) {
                $reason = $report->getReason();
                $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
            }
        }

        arsort($reasons);
        return $reasons;
    }

    /**
     * Get all unique keywords used across all reports
     *
     * @return array<string>
     */
    public function getAllKeywords(): array
    {
        $keywords = [];

        foreach ($this->reports as $report) {
            foreach ($report->getKeywords() as $keyword) {
                $keywords[$keyword] = true;
            }
        }

        return array_keys($keywords);
    }

    /**
     * Get keywords frequency distribution
     *
     * @return array<string, int> Keyword => frequency
     */
    public function getKeywordFrequency(): array
    {
        $frequency = [];

        foreach ($this->reports as $report) {
            foreach ($report->getKeywords() as $keyword) {
                $frequency[$keyword] = ($frequency[$keyword] ?? 0) + 1;
            }
        }

        arsort($frequency);
        return $frequency;
    }

    /**
     * Clear all recorded reports
     *
     * @return void
     */
    public function reset(): void
    {
        $this->reports = [];
        $this->sessionStart = new DateTime();
    }

    /**
     * Get all raw reports
     *
     * @return array<MatchingReport>
     */
    public function getAllReports(): array
    {
        return $this->reports;
    }
}
