<?php

namespace Ksfraser\FaBankImport\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing match confidence calculation
 *
 * Encapsulates the multi-factor confidence score calculation based on:
 * - Keyword coverage (how many search keywords were matched)
 * - Score strength (how this partner's score compares to the top score)
 *
 * Formula: confidence = (keyword_coverage * 0.6) + (score_strength * 0.4)
 *
 * @package Ksfraser\FaBankImport\Domain\ValueObjects
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class MatchConfidence
{
    /**
     * @var float Keyword coverage percentage (0-100)
     */
    private float $keywordCoverage;

    /**
     * @var float Score strength percentage (0-100)
     */
    private float $scoreStrength;

    /**
     * @var float Overall confidence percentage (0-100)
     */
    private float $percentage;

    /**
     * @var float Weight for keyword coverage in confidence calculation
     */
    private const COVERAGE_WEIGHT = 0.6;

    /**
     * @var float Weight for score strength in confidence calculation
     */
    private const STRENGTH_WEIGHT = 0.4;

    /**
     * Create a new MatchConfidence value object
     *
     * @param float $keywordCoverage Percentage of search keywords matched (0-100)
     * @param float $scoreStrength   Percentage of top score achieved (0-100)
     *
     * @throws InvalidArgumentException If percentages are invalid
     */
    public function __construct(float $keywordCoverage, float $scoreStrength)
    {
        $this->validatePercentage($keywordCoverage, 'keyword coverage');
        $this->validatePercentage($scoreStrength, 'score strength');

        $this->keywordCoverage = $keywordCoverage;
        $this->scoreStrength = $scoreStrength;
        $this->percentage = $this->calculate();
    }

    /**
     * Get keyword coverage percentage
     *
     * @return float
     */
    public function getKeywordCoverage(): float
    {
        return $this->keywordCoverage;
    }

    /**
     * Get score strength percentage
     *
     * @return float
     */
    public function getScoreStrength(): float
    {
        return $this->scoreStrength;
    }

    /**
     * Get overall confidence percentage
     *
     * @return float
     */
    public function getPercentage(): float
    {
        return $this->percentage;
    }

    /**
     * Check if confidence meets threshold
     *
     * @param float $threshold Minimum confidence percentage (0-100)
     *
     * @return bool
     */
    public function meetsThreshold(float $threshold): bool
    {
        return $this->percentage >= $threshold;
    }

    /**
     * Check if this is a high confidence match
     *
     * Defined as >= 70% confidence
     *
     * @return bool
     */
    public function isHighConfidence(): bool
    {
        return $this->percentage >= 70.0;
    }

    /**
     * Check if this is a medium confidence match
     *
     * Defined as 40% - 69% confidence
     *
     * @return bool
     */
    public function isMediumConfidence(): bool
    {
        return $this->percentage >= 40.0 && $this->percentage < 70.0;
    }

    /**
     * Check if this is a low confidence match
     *
     * Defined as < 40% confidence
     *
     * @return bool
     */
    public function isLowConfidence(): bool
    {
        return $this->percentage < 40.0;
    }

    /**
     * Get confidence level as string
     *
     * @return string "high", "medium", or "low"
     */
    public function getLevel(): string
    {
        if ($this->isHighConfidence()) {
            return 'high';
        }
        if ($this->isMediumConfidence()) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Create from match statistics
     *
     * @param int   $matchedKeywordCount Number of keywords that matched
     * @param int   $totalSearchKeywords Total keywords in search query
     * @param float $partnerScore        This partner's final score
     * @param float $topScore            Highest score among all matches
     *
     * @return self
     * @throws InvalidArgumentException If statistics are invalid
     */
    public static function fromMatchStatistics(
        int $matchedKeywordCount,
        int $totalSearchKeywords,
        float $partnerScore,
        float $topScore
    ): self {
        if ($totalSearchKeywords <= 0) {
            throw new InvalidArgumentException(
                "Total search keywords must be positive, got: {$totalSearchKeywords}"
            );
        }

        if ($matchedKeywordCount < 0 || $matchedKeywordCount > $totalSearchKeywords) {
            throw new InvalidArgumentException(
                sprintf(
                    'Matched keyword count (%d) must be between 0 and %d',
                    $matchedKeywordCount,
                    $totalSearchKeywords
                )
            );
        }

        if ($partnerScore < 0 || $topScore < 0) {
            throw new InvalidArgumentException('Scores cannot be negative');
        }

        // Calculate keyword coverage
        $keywordCoverage = ($matchedKeywordCount / $totalSearchKeywords) * 100;

        // Calculate score strength
        $scoreStrength = $topScore > 0 
            ? ($partnerScore / $topScore) * 100 
            : 0.0;

        return new self($keywordCoverage, $scoreStrength);
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'keyword_coverage' => $this->keywordCoverage,
            'score_strength' => $this->scoreStrength,
            'percentage' => $this->percentage,
            'level' => $this->getLevel(),
        ];
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            '%.1f%% confidence [coverage:%.1f%%, strength:%.1f%%, level:%s]',
            $this->percentage,
            $this->keywordCoverage,
            $this->scoreStrength,
            $this->getLevel()
        );
    }

    /**
     * Calculate overall confidence percentage
     *
     * Formula: (keyword_coverage * 0.6) + (score_strength * 0.4)
     *
     * @return float
     */
    private function calculate(): float
    {
        return ($this->keywordCoverage * self::COVERAGE_WEIGHT)
            + ($this->scoreStrength * self::STRENGTH_WEIGHT);
    }

    /**
     * Validate percentage value
     *
     * @param float  $value Percentage value
     * @param string $name  Name for error message
     *
     * @throws InvalidArgumentException If percentage is invalid
     */
    private function validatePercentage(float $value, string $name): void
    {
        if ($value < 0.0 || $value > 100.0) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be between 0 and 100, got: %.2f',
                    ucfirst($name),
                    $value
                )
            );
        }
    }
}
