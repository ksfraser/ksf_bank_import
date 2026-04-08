<?php

namespace Ksfraser\FaBankImport\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing a keyword match result
 *
 * Contains all information about a partner matched via keyword search,
 * including the score, confidence, and which keywords matched.
 *
 * @package Ksfraser\FaBankImport\Domain\ValueObjects
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class KeywordMatch
{
    /**
     * @var int Partner ID that was matched
     */
    private int $partnerId;

    /**
     * @var int Partner type
     */
    private int $partnerType;

    /**
     * @var int Partner detail ID
     */
    private int $partnerDetailId;

    /**
     * @var string Partner name (for display)
     */
    private string $partnerName;

    /**
     * @var array<Keyword> Keywords that matched
     */
    private array $matchedKeywords;

    /**
     * @var int Raw match score (sum of occurrence counts)
     */
    private int $rawScore;

    /**
     * @var float Final score with clustering bonus applied
     */
    private float $finalScore;

    /**
     * @var MatchConfidence Confidence calculation
     */
    private MatchConfidence $confidence;

    /**
     * Create a new KeywordMatch value object
     *
     * @param int             $partnerId       Partner ID
     * @param int             $partnerType     Partner type
     * @param int             $partnerDetailId Detail ID
     * @param string          $partnerName     Partner name
     * @param array<Keyword>  $matchedKeywords Matched keywords
     * @param int             $rawScore        Raw score
     * @param float           $finalScore      Final score with bonus
     * @param MatchConfidence $confidence      Confidence calculation
     *
     * @throws InvalidArgumentException If any parameter is invalid
     */
    public function __construct(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $partnerName,
        array $matchedKeywords,
        int $rawScore,
        float $finalScore,
        MatchConfidence $confidence
    ) {
        $this->validatePartnerId($partnerId);
        $this->validatePartnerName($partnerName);
        $this->validateMatchedKeywords($matchedKeywords);
        $this->validateScores($rawScore, $finalScore);

        $this->partnerId = $partnerId;
        $this->partnerType = $partnerType;
        $this->partnerDetailId = $partnerDetailId;
        $this->partnerName = trim($partnerName);
        $this->matchedKeywords = $matchedKeywords;
        $this->rawScore = $rawScore;
        $this->finalScore = $finalScore;
        $this->confidence = $confidence;
    }

    /**
     * Get partner ID
     *
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * Get partner type
     *
     * @return int
     */
    public function getPartnerType(): int
    {
        return $this->partnerType;
    }

    /**
     * Get partner detail ID
     *
     * @return int
     */
    public function getPartnerDetailId(): int
    {
        return $this->partnerDetailId;
    }

    /**
     * Get partner name
     *
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * Get matched keywords
     *
     * @return array<Keyword>
     */
    public function getMatchedKeywords(): array
    {
        return $this->matchedKeywords;
    }

    /**
     * Get number of matched keywords
     *
     * @return int
     */
    public function getMatchedKeywordCount(): int
    {
        return count($this->matchedKeywords);
    }

    /**
     * Get raw score (before clustering bonus)
     *
     * @return int
     */
    public function getRawScore(): int
    {
        return $this->rawScore;
    }

    /**
     * Get final score (with clustering bonus)
     *
     * @return float
     */
    public function getFinalScore(): float
    {
        return $this->finalScore;
    }

    /**
     * Get confidence object
     *
     * @return MatchConfidence
     */
    public function getConfidence(): MatchConfidence
    {
        return $this->confidence;
    }

    /**
     * Get confidence percentage
     *
     * @return float
     */
    public function getConfidencePercentage(): float
    {
        return $this->confidence->getPercentage();
    }

    /**
     * Check if confidence meets threshold
     *
     * @param float $threshold Minimum confidence percentage
     *
     * @return bool
     */
    public function meetsConfidenceThreshold(float $threshold): bool
    {
        return $this->getConfidencePercentage() >= $threshold;
    }

    /**
     * Get clustering bonus that was applied
     *
     * @return float
     */
    public function getClusteringBonus(): float
    {
        if ($this->rawScore === 0) {
            return 0.0;
        }
        return $this->finalScore / $this->rawScore;
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'partner_id' => $this->partnerId,
            'partner_type' => $this->partnerType,
            'partner_detail_id' => $this->partnerDetailId,
            'partner_name' => $this->partnerName,
            'matched_keywords' => array_map(
                fn(Keyword $k) => $k->getText(),
                $this->matchedKeywords
            ),
            'matched_keyword_count' => $this->getMatchedKeywordCount(),
            'raw_score' => $this->rawScore,
            'final_score' => $this->finalScore,
            'clustering_bonus' => $this->getClusteringBonus(),
            'confidence_percentage' => $this->getConfidencePercentage(),
            'keyword_coverage' => $this->confidence->getKeywordCoverage(),
            'score_strength' => $this->confidence->getScoreStrength(),
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
            'KeywordMatch[%s (ID:%d), keywords:%d, score:%.2f, confidence:%.1f%%]',
            $this->partnerName,
            $this->partnerId,
            $this->getMatchedKeywordCount(),
            $this->finalScore,
            $this->getConfidencePercentage()
        );
    }

    /**
     * Validate partner ID
     *
     * @param int $partnerId
     *
     * @throws InvalidArgumentException
     */
    private function validatePartnerId(int $partnerId): void
    {
        if ($partnerId <= 0) {
            throw new InvalidArgumentException(
                "Partner ID must be positive, got: {$partnerId}"
            );
        }
    }

    /**
     * Validate partner name
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    private function validatePartnerName(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Partner name cannot be empty');
        }
    }

    /**
     * Validate matched keywords array
     *
     * @param array<Keyword> $keywords
     *
     * @throws InvalidArgumentException
     */
    private function validateMatchedKeywords(array $keywords): void
    {
        if (empty($keywords)) {
            throw new InvalidArgumentException('Must have at least one matched keyword');
        }

        foreach ($keywords as $keyword) {
            if (!($keyword instanceof Keyword)) {
                throw new InvalidArgumentException(
                    'All matched keywords must be Keyword objects'
                );
            }
        }
    }

    /**
     * Validate scores
     *
     * @param int   $rawScore
     * @param float $finalScore
     *
     * @throws InvalidArgumentException
     */
    private function validateScores(int $rawScore, float $finalScore): void
    {
        if ($rawScore < 0) {
            throw new InvalidArgumentException(
                "Raw score must be non-negative, got: {$rawScore}"
            );
        }

        if ($finalScore < 0) {
            throw new InvalidArgumentException(
                "Final score must be non-negative, got: {$finalScore}"
            );
        }

        // Final score should generally be >= raw score (due to clustering bonus)
        // Allow small floating point variance
        if ($finalScore < $rawScore - 0.01) {
            throw new InvalidArgumentException(
                sprintf(
                    'Final score (%.2f) should not be less than raw score (%d)',
                    $finalScore,
                    $rawScore
                )
            );
        }
    }
}
