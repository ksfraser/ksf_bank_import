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
/**
 * @BABOK CLASS ROLLUP: ReportingService — 23 methods; FR-012 (23)
 * @BABOK RTM: FR-012 / ARCH-007; file: Reporting/ReportingService.php; coverage: docs/TestCases/ (file rollup)
 */
class ReportingService
{
    private array $reports = [];
    private DateTime $sessionStart;

    /** @BABOK FR-012 / ARCH-007 — method: __construct */
    public function __construct()
    {
        $this->sessionStart = new DateTime();
    }

    /**
 * @BABOK FR-012 / ARCH-007 — method: recordMatch
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
 * @BABOK FR-012 / ARCH-007 — method: recordMatches
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
 * @BABOK FR-012 / ARCH-007 — method: getTotalRecorded
     * Get total matches recorded
     *
     * @return int
     */
    public function getTotalRecorded(): int
    {
        return count($this->reports);
    }

    /**
 * @BABOK FR-012 / ARCH-007 — method: getSessionStart
     * Get session start time
     *
     * @return DateTime
     */
    public function getSessionStart(): DateTime
    {
        return $this->sessionStart;
    }

    /**
 * @BABOK FR-012 / ARCH-007 — method: getSessionDuration
     * Get session duration in seconds
     *
     * @return int
     */
    public function getSessionDuration(): int
    {
        return (int)(new DateTime())->diff($this->sessionStart)->s;
    }

    /**
 * @BABOK FR-012 / ARCH-007 — method: generateOperationReport
     * Generate operation report for all recorded matches
     *
     * @return OperationReport
     */
    public function generateOperationReport(): OperationReport
    {
        return new OperationReport($this->reports);
    }

    /**
 * @BABOK FR-012 / ARCH-007 — method: generateSuccessReport
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
 * @BABOK FR-012 / ARCH-007 — method: generateFailureReport
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsByTransaction
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsByPartner
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsByConfidenceRange
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsBefore
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsAfter
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
 * @BABOK FR-012 / ARCH-007 — method: getReportsByConfidenceLevel
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
 * @BABOK FR-012 / ARCH-007 — method: getLowestConfidenceTransactions
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
 * @BABOK FR-012 / ARCH-007 — method: getHighestConfidenceTransactions
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
 * @BABOK FR-012 / ARCH-007 — method: getTopPartnersBySuccessRate
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
 * @BABOK FR-012 / ARCH-007 — method: getFailureReasonsSummary
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
 * @BABOK FR-012 / ARCH-007 — method: getAllKeywords
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
 * @BABOK FR-012 / ARCH-007 — method: getKeywordFrequency
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
 * @BABOK FR-012 / ARCH-007 — method: reset
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
 * @BABOK FR-012 / ARCH-007 — method: getAllReports
     * Get all raw reports
     *
     * @return array<MatchingReport>
     */
    public function getAllReports(): array
    {
        return $this->reports;
    }
}
