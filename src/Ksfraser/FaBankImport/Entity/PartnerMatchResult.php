<?php

namespace Ksfraser\FaBankImport\Entity;

/**
 * Partner Match Result - Immutable value object for search results
 * 
 * Represents a single match result from partner searching.
 * Includes the matched partner, confidence score, and all scoring factors.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class PartnerMatchResult
{
    /**
     * Minimum confidence threshold for auto-selection
     */
    public const CONFIDENCE_AUTO_SELECT_THRESHOLD = 0.75;
    
    /**
     * The matched partner entity
     */
    private readonly PartnerEntity $partner;
    
    /**
     * Confidence score (0.0 to 1.0)
     * 1.0 = very confident this is the right match
     * 0.0 = no confidence
     */
    private readonly float $confidence;
    
    /**
     * Breakdown of all scoring factors
     * Keys: 'substring', 'keyword', 'account', 'occurrence', 'recency', 'clustering'
     * Base factors are added, multipliers are multiplied
     */
    private readonly array $factors;
    
    /**
     * Construct a match result
     * 
     * @param PartnerEntity $partner The matched partner
     * @param float $confidence Confidence 0.0-1.0 (will be clamped)
     * @param array $factors Scoring breakdown (default empty)
     */
    public function __construct(
        PartnerEntity $partner,
        float $confidence = 0.0,
        array $factors = []
    ) {
        $this->partner = $partner;
        // Clamp confidence to 0.0-1.0
        $this->confidence = max(0.0, min(1.0, $confidence));
        $this->factors = $factors;
    }
    
    /**
     * Get the matched partner
     */
    public function partner(): PartnerEntity
    {
        return $this->partner;
    }
    
    /**
     * Get confidence score (0.0 to 1.0)
     */
    public function confidence(): float
    {
        return $this->confidence;
    }
    
    /**
     * Get all scoring factors
     */
    public function factors(): array
    {
        return $this->factors;
    }
    
    /**
     * Calculate total score from factors
     * 
     * Base factors (substring, keyword, account) are summed
     * Multiplier factors (occurrence, recency, clustering) are multiplied
     * 
     * Formula: (substring + keyword + account) * occurrence * recency * clustering
     * 
     * @return float Total combined score
     */
    public function totalScore(): float
    {
        if (empty($this->factors)) {
            return 0.0;
        }
        
        // Sum base factors
        $base = ($this->factors['substring'] ?? 0) +
                ($this->factors['keyword'] ?? 0) +
                ($this->factors['account'] ?? 0);
        
        if ($base === 0) {
            return 0.0;
        }
        
        // Apply multipliers
        $multiplier = ($this->factors['occurrence'] ?? 1.0) *
                      ($this->factors['recency'] ?? 1.0) *
                      ($this->factors['clustering'] ?? 1.0);
        
        return $base * $multiplier;
    }
    
    /**
     * Check if confidence is high enough for auto-selection
     */
    public function isAutoSelectable(): bool
    {
        return $this->confidence >= self::CONFIDENCE_AUTO_SELECT_THRESHOLD;
    }
    
    /**
     * Prevent dynamic property assignment (enforce immutability)
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \Error(
            "Cannot set property {$name} on immutable " . self::class
        );
    }
}
