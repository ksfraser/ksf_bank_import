<?php

/**
 * Partner Stats Response DTO
 *
 * Statistics for matching operations with a specific partner.
 * Tracks success rates, confidence distributions, and patterns.
 *
 * @author Kevin Fraser
 * @since 2.4.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * PartnerStatsResponse
 *
 * Response object containing partner-specific matching statistics.
 */
class PartnerStatsResponse
{
    /**
     * Constructor
     *
     * @param int $partnerId Partner ID
     * @param string $partnerName Partner name
     * @param int $totalMatches Total match attempts with this partner
     * @param int $successfulMatches Successful matches
     * @param float $successRate Success rate 0.0-1.0
     * @param float $averageConfidence Average confidence for this partner
     * @param array $confidenceDistribution Distribution {HIGH, MEDIUM, LOW}
     * @param int $mostRecentMatch Unix timestamp of most recent match
     */
    public function __construct(
        private readonly int $partnerId,
        private readonly string $partnerName,
        private readonly int $totalMatches,
        private readonly int $successfulMatches,
        private readonly float $successRate,
        private readonly float $averageConfidence,
        private readonly array $confidenceDistribution,
        private readonly int $mostRecentMatch
    ) {
    }

    /**
     * Get partner ID
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * Get partner name
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * Get total matches
     */
    public function getTotalMatches(): int
    {
        return $this->totalMatches;
    }

    /**
     * Get successful matches
     */
    public function getSuccessfulMatches(): int
    {
        return $this->successfulMatches;
    }

    /**
     * Get success rate
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
     * Get last matched at timestamp
     */
    public function getLastMatchedAt(): string
    {
        return $this->mostRecentMatch > 0 
            ? date('c', $this->mostRecentMatch)
            : 'Never';
    }

    /**
     * Convert to array for JSON serialization
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
                'success_rate' => $this->successRate,
                'success_rate_percentage' => $this->successRate * 100,
            ],
            'confidence_stats' => [
                'average_confidence' => $this->averageConfidence,
                'distribution' => $this->confidenceDistribution,
            ],
            'most_recent_match' => $this->getLastMatchedAt(),
        ];
    }
}
