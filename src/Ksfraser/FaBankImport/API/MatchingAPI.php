<?php

/**
 * Matching API - REST-like Interface for Transaction Matching
 *
 * Coordinates matching services and reporting to provide a cohesive API
 * for match operations, report generation, and analytics.
 *
 * @package    Ksfraser\FaBankImport\API
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

use Ksfraser\FaBankImport\Services\TransactionMatchingService;
use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Services\ConfidenceEnhancer;
use Ksfraser\FaBankImport\Services\Scoring\ScoringEngineFactory;
use Ksfraser\FaBankImport\Reporting\ReportingService;
use Ksfraser\FaBankImport\Reporting\MatchingReport;

/**
 * MatchingAPI - Main API interface for transaction matching operations
 *
 * Provides endpoints for:
 * - Matching a transaction to partners
 * - Retrieving aggregated reports and statistics
 * - Analyzing partner performance
 * - Debugging match decisions
 *
 * @example
 * ```php
 * $api = new MatchingAPI(
 *     transactionMatching: $txnService,
 *     keywordMatching: $kwService,
 *     confidenceEnhancer: $enhancer,
 *     reporting: $reportingService
 * );
 *
 * // Match a transaction
 * $request = MatchTransactionRequest::fromArray([
 *     'transaction_id' => 'TXN-001',
 *     'amount' => 1500.00,
 *     'description' => 'Payment to ABC Vendor Inc',
 *     'type' => 'payment'
 * ]);
 * $response = $api->matchTransaction($request);
 *
 * // Get report summary
 * $summary = $api->getReportSummary();
 * ```
 */
class MatchingAPI
{
    private TransactionMatchingService $transactionMatching;
    private KeywordMatchingService $keywordMatching;
    private ConfidenceEnhancer $confidenceEnhancer;
    private ReportingService $reporting;

    /**
     * @param TransactionMatchingService $transactionMatching Transaction matching service
     * @param KeywordMatchingService $keywordMatching Keyword matching service
     * @param ConfidenceEnhancer $confidenceEnhancer Confidence refinement
     * @param ReportingService $reporting Analytics service
     */
    public function __construct(
        TransactionMatchingService $transactionMatching,
        KeywordMatchingService $keywordMatching,
        ConfidenceEnhancer $confidenceEnhancer,
        ReportingService $reporting
    ) {
        $this->transactionMatching = $transactionMatching;
        $this->keywordMatching = $keywordMatching;
        $this->confidenceEnhancer = $confidenceEnhancer;
        $this->reporting = $reporting;
    }

    /**
     * Match a transaction to partners
     *
     * POST /api/match/transaction
     *
     * @param MatchTransactionRequest $request Match request
     * @return MatchTransactionResponse Match result
     * @throws \Exception On matching errors
     */
    public function matchTransaction(MatchTransactionRequest $request): MatchTransactionResponse
    {
        try {
            // Create transaction object for services
            $transaction = [
                'id' => $request->getTransactionId(),
                'amount' => $request->getAmount(),
                'description' => $request->getDescription(),
                'type' => $request->getTransactionType(),
                'reference_number' => $request->getReferenceNumber(),
            ];

            // Perform keyword matching
            $keywordMatches = $this->keywordMatching->search($request->getDescription());

            // Enhance with transaction context
            $enhancedMatches = [];
            if (!empty($keywordMatches)) {
                $enhancedMatches = $this->confidenceEnhancer->enhanceMultiple(
                    $keywordMatches,
                    $transaction
                );
            }

            // Get best match
            $bestMatch = !empty($enhancedMatches) 
                ? $this->confidenceEnhancer->getBestMatch($enhancedMatches, $transaction)
                : null;

            // Generate report
            if ($bestMatch) {
                $report = MatchingReport::success(
                    transactionId: $request->getTransactionId(),
                    partnerId: (int)($bestMatch['partner_id'] ?? 0),
                    confidence: $bestMatch['confidence'] ?? 0,
                    scoreBreakdown: $bestMatch['score_breakdown'] ?? [],
                    keywords: $bestMatch['keywords'] ?? [],
                    scoreFormula: $bestMatch['score_formula'] ?? null,
                    candidatesEvaluated: count($enhancedMatches)
                );

                $this->reporting->recordMatch($report);

                return new MatchTransactionResponse(
                    transactionId: $request->getTransactionId(),
                    success: true,
                    partnerId: (int)($bestMatch['partner_id'] ?? 0),
                    partnerName: $bestMatch['partner_name'] ?? "Partner {$bestMatch['partner_id']}",
                    confidence: $bestMatch['confidence'] ?? 0,
                    confidenceLevel: $this->getConfidenceLevel($bestMatch['confidence'] ?? 0),
                    scoreFormula: $bestMatch['score_formula'] ?? null,
                    scoreBreakdown: $bestMatch['score_breakdown'] ?? [],
                    keywords: $bestMatch['keywords'] ?? [],
                    reason: "Successfully matched with confidence {$bestMatch['confidence']}%"
                );
            }

            // No match found
            $failureReason = empty($keywordMatches) 
                ? 'No keywords matched in description'
                : 'Confidence below threshold';

            $report = MatchingReport::failed(
                transactionId: $request->getTransactionId(),
                reason: $failureReason,
                keywords: [],
                candidatesEvaluated: count($keywordMatches ?? [])
            );

            $this->reporting->recordMatch($report);

            return new MatchTransactionResponse(
                transactionId: $request->getTransactionId(),
                success: false,
                partnerId: null,
                partnerName: null,
                confidence: 0.0,
                confidenceLevel: 'LOW',
                scoreFormula: null,
                scoreBreakdown: [],
                keywords: [],
                reason: $failureReason
            );
        } catch (\Exception $e) {
            $report = MatchingReport::failed(
                transactionId: $request->getTransactionId(),
                reason: "Matching error: {$e->getMessage()}"
            );
            $this->reporting->recordMatch($report);

            throw $e;
        }
    }

    /**
     * Get aggregated report summary
     *
     * GET /api/report/summary
     *
     * @return ReportSummaryResponse Summary statistics
     */
    public function getReportSummary(): ReportSummaryResponse
    {
        $operation = $this->reporting->generateOperationReport();
        $summary = $operation->getSummary();

        return new ReportSummaryResponse(
            totalAttempted: $operation->getTotalAttempted(),
            totalSuccessful: $operation->getTotalSuccessful(),
            totalFailed: $operation->getTotalFailed(),
            successRate: $operation->getSuccessRate(),
            averageConfidence: $operation->getAverageConfidence(),
            confidenceDistribution: $operation->getConfidenceDistribution(),
            confidencePercentiles: $operation->getConfidencePercentiles(),
            mostImpactfulRule: $operation->getMostImpactfulRule() ?? 'Unknown',
            averageKeywords: $operation->getAverageKeywordsPerTransaction(),
            averageCandidatesEvaluated: $operation->getAverageCandidatesEvaluated()
        );
    }

    /**
     * Get partner-specific statistics
     *
     * GET /api/partner/{partnerID}/stats
     *
     * @param int $partnerId Partner ID to analyze
     * @return PartnerStatsResponse Partner statistics
     */
    public function getPartnerStats(int $partnerId): PartnerStatsResponse
    {
        $partnerReports = $this->reporting->getReportsByPartner($partnerId);

        if (empty($partnerReports)) {
            return new PartnerStatsResponse(
                partnerId: $partnerId,
                partnerName: "Partner {$partnerId}",
                totalMatches: 0,
                successfulMatches: 0,
                successRate: 0.0,
                averageConfidence: 0.0,
                confidenceDistribution: ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0],
                mostRecentMatch: 0
            );
        }

        $operation = new \Ksfraser\FaBankImport\Reporting\OperationReport($partnerReports);

        $successCount = count(array_filter($partnerReports, fn($r) => $r->isSuccess()));
        $totalCount = count($partnerReports);

        // Find most recent match timestamp
        $mostRecent = 0;
        foreach ($partnerReports as $report) {
            $timestamp = $report->getTimestamp()->getTimestamp();
            if ($timestamp > $mostRecent) {
                $mostRecent = $timestamp;
            }
        }

        return new PartnerStatsResponse(
            partnerId: $partnerId,
            partnerName: "Partner {$partnerId}",
            totalMatches: $totalCount,
            successfulMatches: $successCount,
            successRate: $totalCount > 0 ? $successCount / $totalCount : 0.0,
            averageConfidence: $operation->getAverageConfidence(),
            confidenceDistribution: $operation->getConfidenceDistribution(),
            mostRecentMatch: $mostRecent
        );
    }

    /**
     * Get lowest confidence transactions
     *
     * GET /api/report/lowest-confidence
     *
     * @param int $limit Number of results
     * @return array<array> Transactions with lowest confidence
     */
    public function getLowestConfidenceTransactions(int $limit = 10): array
    {
        $transactions = $this->reporting->getLowestConfidenceTransactions($limit);

        return array_map(function(string $txnId, float $confidence) {
            return [
                'transaction_id' => $txnId,
                'confidence' => $confidence,
                'confidence_level' => $this->getConfidenceLevel($confidence),
            ];
        }, array_keys($transactions), $transactions);
    }

    /**
     * Get highest confidence transactions
     *
     * GET /api/report/highest-confidence
     *
     * @param int $limit Number of results
     * @return array<array> Transactions with highest confidence
     */
    public function getHighestConfidenceTransactions(int $limit = 10): array
    {
        $transactions = $this->reporting->getHighestConfidenceTransactions($limit);

        return array_map(function(string $txnId, float $confidence) {
            return [
                'transaction_id' => $txnId,
                'confidence' => $confidence,
                'confidence_level' => $this->getConfidenceLevel($confidence),
            ];
        }, array_keys($transactions), $transactions);
    }

    /**
     * Get top performing partners by success rate
     *
     * GET /api/report/top-partners
     *
     * @param int $minMatches Minimum matches to include
     * @param int $limit Number of results
     * @return array<array> Partners ranked by success rate
     */
    public function getTopPartnersBySuccessRate(int $minMatches = 5, int $limit = 10): array
    {
        $partners = $this->reporting->getTopPartnersBySuccessRate($minMatches, $limit);

        return array_map(function(int $partnerId, float $rate) {
            return [
                'partner_id' => $partnerId,
                'success_rate' => $rate,
                'success_rate_percentage' => $rate * 100,
            ];
        }, array_keys($partners), $partners);
    }

    /**
     * Get failure reasons summary
     *
     * GET /api/report/failure-reasons
     *
     * @return array<array> Failure reasons with counts
     */
    public function getFailureReasonsSummary(): array
    {
        $reasons = $this->reporting->getFailureReasonsSummary();

        return array_map(function(string $reason, int $count) {
            return [
                'reason' => $reason,
                'count' => $count,
            ];
        }, array_keys($reasons), $reasons);
    }

    /**
     * Get keyword usage summary
     *
     * GET /api/report/keywords
     *
     * @param int $limit Top N keywords
     * @return array<array> Keywords with frequency
     */
    public function getKeywordFrequency(int $limit = 50): array
    {
        $frequency = $this->reporting->getKeywordFrequency();

        return array_slice(
            array_map(function(string $keyword, int $count) {
                return [
                    'keyword' => $keyword,
                    'frequency' => $count,
                ];
            }, array_keys($frequency), $frequency),
            0,
            $limit,
            true
        );
    }

    /**
     * Get detailed report for specific transaction
     *
     * GET /api/report/transaction/{transactionID}
     *
     * @param string $transactionId Transaction ID
     * @return array<array> All reports for transaction
     */
    public function getTransactionReports(string $transactionId): array
    {
        $reports = $this->reporting->getReportsByTransaction($transactionId);

        return array_map(fn($r) => $r->toArray(), $reports);
    }

    /**
     * Helper: Convert confidence score to level
     *
     * @param float $confidence Confidence (0-100)
     * @return string 'HIGH', 'MEDIUM', 'LOW'
     */
    private function getConfidenceLevel(float $confidence): string
    {
        if ($confidence >= 70.0) {
            return 'HIGH';
        }
        if ($confidence >= 40.0) {
            return 'MEDIUM';
        }
        return 'LOW';
    }

    /**
     * Clear all recorded reports and reset session
     *
     * DELETE /api/admin/reports
     * (Requires admin authorization)
     *
     * @return void
     */
    public function clearReports(): void
    {
        $this->reporting->reset();
    }

    /**
     * Get session metrics
     *
     * GET /api/admin/session
     *
     * @return array<string, mixed> Session information
     */
    public function getSessionMetrics(): array
    {
        $operation = $this->reporting->generateOperationReport();

        return [
            'session_start' => $this->reporting->getSessionStart()->format('Y-m-d H:i:s'),
            'session_duration_seconds' => $this->reporting->getSessionDuration(),
            'total_recorded' => $this->reporting->getTotalRecorded(),
            'total_successful' => $operation->getTotalSuccessful(),
            'total_failed' => $operation->getTotalFailed(),
            'success_rate' => $operation->getSuccessRate(),
        ];
    }
}
