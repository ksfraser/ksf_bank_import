<?php

/**
 * API Request/Response Value Objects
 *
 * Type-safe DTOs for API communication and validation.
 *
 * @package    Ksfraser\FaBankImport\API
 * @author     Kevin Fraser
 * @since      2025-01-14
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * MatchTransactionRequest - Incoming match operation request
 *
 * Validates and encapsulates transaction data for matching.
 */
class MatchTransactionRequest
{
    private string $transactionId;
    private float $amount;
    private string $description;
    private string $transactionType;
    private ?string $referenceNumber;

    /**
     * @param string $transactionId Transaction identifier
     * @param float $amount Transaction amount
     * @param string $description Transaction description/narrative
     * @param string $transactionType Type: 'payment', 'transfer', 'receipt'
     * @param ?string $referenceNumber Optional reference number
     */
    public function __construct(
        string $transactionId,
        float $amount,
        string $description,
        string $transactionType,
        ?string $referenceNumber = null
    ) {
        if (empty($transactionId)) {
            throw new \InvalidArgumentException('Transaction ID required');
        }
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be non-negative');
        }
        if (empty($description)) {
            throw new \InvalidArgumentException('Description required');
        }

        $this->transactionId = $transactionId;
        $this->amount = $amount;
        $this->description = $description;
        $this->transactionType = $transactionType;
        $this->referenceNumber = $referenceNumber;
    }

    public function getTransactionId(): string { return $this->transactionId; }
    public function getAmount(): float { return $this->amount; }
    public function getDescription(): string { return $this->description; }
    public function getTransactionType(): string { return $this->transactionType; }
    public function getReferenceNumber(): ?string { return $this->referenceNumber; }

    /**
     * Create from array (typical JSON request)
     *
     * @param array $data Request data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $required = ['transaction_id', 'amount', 'description', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        return new self(
            transactionId: (string)$data['transaction_id'],
            amount: (float)$data['amount'],
            description: (string)$data['description'],
            transactionType: (string)$data['type'],
            referenceNumber: $data['reference_number'] ?? null
        );
    }
}

/**
 * MatchTransactionResponse - Match operation result
 *
 * Returns single match result with confidence and reasoning.
 */
class MatchTransactionResponse
{
    private string $transactionId;
    private bool $success;
    private ?int $partnerId;
    private ?string $partnerName;
    private float $confidence;
    private string $confidenceLevel;
    private ?string $scoreFormula;
    private array $scoreBreakdown;
    private array $keywords;
    private string $reason;

    /**
     * @param string $transactionId Transaction ID
     * @param bool $success Whether match succeeded
     * @param ?int $partnerId Matched partner ID
     * @param ?string $partnerName Matched partner name
     * @param float $confidence Confidence score (0-100)
     * @param string $confidenceLevel 'HIGH', 'MEDIUM', 'LOW'
     * @param ?string $scoreFormula Human-readable formula
     * @param array $scoreBreakdown Per-rule contributions
     * @param array $keywords Extracted keywords
     * @param string $reason Result reason/explanation
     */
    public function __construct(
        string $transactionId,
        bool $success,
        ?int $partnerId,
        ?string $partnerName,
        float $confidence,
        string $confidenceLevel,
        ?string $scoreFormula,
        array $scoreBreakdown,
        array $keywords,
        string $reason
    ) {
        $this->transactionId = $transactionId;
        $this->success = $success;
        $this->partnerId = $partnerId;
        $this->partnerName = $partnerName;
        $this->confidence = $confidence;
        $this->confidenceLevel = $confidenceLevel;
        $this->scoreFormula = $scoreFormula;
        $this->scoreBreakdown = $scoreBreakdown;
        $this->keywords = $keywords;
        $this->reason = $reason;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'success' => $this->success,
            'partner' => [
                'id' => $this->partnerId,
                'name' => $this->partnerName,
            ],
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

    // Getters for testing
    public function isSuccess(): bool { return $this->success; }
    public function getPartnerId(): ?int { return $this->partnerId; }
    public function getConfidence(): float { return $this->confidence; }
    public function getReason(): string { return $this->reason; }
}

/**
 * ReportSummaryResponse - Aggregated matching statistics
 *
 * Returns high-level metrics for a batch of matching operations.
 */
class ReportSummaryResponse
{
    private int $totalAttempted;
    private int $totalSuccessful;
    private int $totalFailed;
    private float $successRate;
    private float $averageConfidence;
    private array $confidenceDistribution;
    private array $confidencePercentiles;
    private string $mostImpactfulRule;
    private float $averageKeywords;
    private float $averageCandidatesEvaluated;

    public function __construct(
        int $totalAttempted,
        int $totalSuccessful,
        int $totalFailed,
        float $successRate,
        float $averageConfidence,
        array $confidenceDistribution,
        array $confidencePercentiles,
        string $mostImpactfulRule,
        float $averageKeywords,
        float $averageCandidatesEvaluated
    ) {
        $this->totalAttempted = $totalAttempted;
        $this->totalSuccessful = $totalSuccessful;
        $this->totalFailed = $totalFailed;
        $this->successRate = $successRate;
        $this->averageConfidence = $averageConfidence;
        $this->confidenceDistribution = $confidenceDistribution;
        $this->confidencePercentiles = $confidencePercentiles;
        $this->mostImpactfulRule = $mostImpactfulRule;
        $this->averageKeywords = $averageKeywords;
        $this->averageCandidatesEvaluated = $averageCandidatesEvaluated;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'matching_summary' => [
                'total_attempted' => $this->totalAttempted,
                'total_successful' => $this->totalSuccessful,
                'total_failed' => $this->totalFailed,
            ],
            'success_metrics' => [
                'success_rate' => $this->successRate,
                'success_rate_percentage' => $this->successRate * 100,
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

    // Getters for testing
    public function getSuccessRate(): float { return $this->successRate; }
    public function getAverageConfidence(): float { return $this->averageConfidence; }
    public function getTotalAttempted(): int { return $this->totalAttempted; }
}

/**
 * PartnerStatsResponse - Statistics for a specific partner
 *
 * Returns aggregated metrics for matching performance with partner.
 */
class PartnerStatsResponse
{
    private int $partnerId;
    private string $partnerName;
    private int $totalMatches;
    private int $successfulMatches;
    private float $successRate;
    private float $averageConfidence;
    private array $confidenceDistribution;
    private int $mostRecentMatch;  // Unix timestamp

    public function __construct(
        int $partnerId,
        string $partnerName,
        int $totalMatches,
        int $successfulMatches,
        float $successRate,
        float $averageConfidence,
        array $confidenceDistribution,
        int $mostRecentMatch
    ) {
        $this->partnerId = $partnerId;
        $this->partnerName = $partnerName;
        $this->totalMatches = $totalMatches;
        $this->successfulMatches = $successfulMatches;
        $this->successRate = $successRate;
        $this->averageConfidence = $averageConfidence;
        $this->confidenceDistribution = $confidenceDistribution;
        $this->mostRecentMatch = $mostRecentMatch;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'partner' => [
                'id' => $this->partnerId,
                'name' => $this->partnerName,
            ],
            'matching_stats' => [
                'total_matches' => $this->totalMatches,
                'successful_matches' => $this->successfulMatches,
                'failed_matches' => $this->totalMatches - $this->successfulMatches,
                'success_rate' => $this->successRate,
                'success_rate_percentage' => $this->successRate * 100,
            ],
            'confidence_stats' => [
                'average_confidence' => $this->averageConfidence,
                'distribution' => $this->confidenceDistribution,
            ],
            'most_recent_match' => $this->mostRecentMatch,
        ];
    }

    // Getters for testing
    public function getPartnerId(): int { return $this->partnerId; }
    public function getSuccessRate(): float { return $this->successRate; }
    public function getTotalMatches(): int { return $this->totalMatches; }
}

/**
 * APIErrorResponse - Standardized error response
 *
 * Consistent format for all API errors.
 */
class APIErrorResponse
{
    private int $statusCode;
    private string $message;
    private ?string $code;
    private array $details;

    /**
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @param ?string $code Error code for client handling
     * @param array $details Additional error details
     */
    public function __construct(
        int $statusCode,
        string $message,
        ?string $code = null,
        array $details = []
    ) {
        $this->statusCode = $statusCode;
        $this->message = $message;
        $this->code = $code;
        $this->details = $details;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'error' => [
                'status_code' => $this->statusCode,
                'message' => $this->message,
                'code' => $this->code,
                'details' => $this->details,
            ],
        ];
    }

    public function getStatusCode(): int { return $this->statusCode; }
    public function getMessage(): string { return $this->message; }
    public function getCode(): ?string { return $this->code; }
}
