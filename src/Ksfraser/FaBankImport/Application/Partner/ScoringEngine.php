<?php

namespace Ksfraser\FaBankImport\Application\Partner;

/**
 * Scoring Engine Service
 * 
 * Implements 6-factor partner matching score calculation:
 * - Substring: Full phrase match in description (+100)
 * - Keyword: Individual keyword matches (+10 each)
 * - Account: Transaction account matches pattern account (+80)
 * - Occurrence: Diminishing returns for repeated patterns (×0.5-1.0)
 * - Recency: Exponential decay over time (×0.5-1.0)
 * - Clustering: Bonus for account clusters (×0.2+)
 * 
 * Formula: (substring + keyword + account) × occurrence × recency × clustering
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class ScoringEngine
{
    private const SUBSTRING_WEIGHT = 100;
    private const KEYWORD_WEIGHT = 10;
    private const ACCOUNT_WEIGHT = 80;
    
    /**
     * Recency half-life in days
     * After 365 days, decay = 0.5
     */
    private const RECENCY_HALF_LIFE_DAYS = 365;
    
    /**
     * Calculate substring match score
     * 
     * Returns 100 if pattern appears (case-insensitive) anywhere in text, 0 otherwise
     * 
     * @param string $text Full description to search
     * @param string $pattern Pattern to look for
     * @return float 100 if match, 0 otherwise
     */
    public function calculateSubstringScore(string $text, string $pattern): float
    {
        if (stripos($text, $pattern) !== false) {
            return self::SUBSTRING_WEIGHT;
        }
        return 0;
    }
    
    /**
     * Calculate keyword match score
     * 
     * Scores 10 points for each keyword overlap between transaction and pattern
     * 
     * @param array $keywords Transaction keywords
     * @param array $patternKeywords Pattern keywords to match against
     * @return float Count of matching keywords × 10
     */
    public function calculateKeywordScore(array $keywords, array $patternKeywords): float
    {
        $matches = count(array_intersect($keywords, $patternKeywords));
        return $matches * self::KEYWORD_WEIGHT;
    }
    
    /**
     * Calculate account match score
     * 
     * Returns 80 if transaction account appears in pattern's known accounts
     * 
     * @param string $accountNumber Transaction account
     * @param array $patternAccounts Accounts associated with this pattern
     * @return float 80 if match, 0 otherwise
     */
    public function calculateAccountScore(string $accountNumber, array $patternAccounts): float
    {
        return in_array($accountNumber, $patternAccounts, true) ? self::ACCOUNT_WEIGHT : 0;
    }
    
    /**
     * Calculate occurrence multiplier
     * 
     * Diminishing returns: first match=1.0, heavily-matched=0.5+
     * Used to prefer patterns that are more consistently matched
     * 
     * Formula: 1 / sqrt(occurrenceCount) with floor of 0.5
     * 
     * @param int $occurrenceCount How many times this pattern has been matched
     * @return float Multiplier 0.5-1.0
     */
    public function calculateOccurrenceMultiplier(int $occurrenceCount): float
    {
        if ($occurrenceCount <= 0) {
            return 1.0;
        }
        
        // Diminishing returns but no lower than 0.5
        return max(0.5, 1.0 / sqrt($occurrenceCount));
    }
    
    /**
     * Calculate recency multiplier
     * 
     * Exponential decay: recent matches score higher, old matches score lower
     * Half-life is RECENCY_HALF_LIFE_DAYS (365 days)
     * 
     * Formula: 2^(-days / 365)
     * - 0 days old = 1.0 (max)
     * - 365 days old = 0.5 (half-life)
     * - 730 days old = 0.25
     * - Never below 0.0
     * 
     * @param \DateTime $lastMatched When pattern was last matched
     * @param ?\DateTime $now Current time (default: now)
     * @return float Multiplier 0.0-1.0
     */
    public function calculateRecencyMultiplier(\DateTime $lastMatched, ?\DateTime $now = null): float
    {
        $now = $now ?? new \DateTime();
        $days = $lastMatched->diff($now)->days;
        
        // Exponential decay: 2^(-days / half-life)
        $multiplier = pow(2, -$days / self::RECENCY_HALF_LIFE_DAYS);
        
        return max(0.0, min(1.0, $multiplier));
    }
    
    /**
     * Calculate clustering bonus
     * 
     * Accounts that appear in multiple transactions (clusters) should have
     * higher weight to differentiate them
     * 
     * Formula: 1.0 + (0.2 / clusterSize)
     * - Single occurrence = 1.0 + 0.2 = 1.2
     * - Two occurrences = 1.0 + 0.1 = 1.1
     * - Five occurrences = 1.0 + 0.04 = 1.04
     * 
     * @param string $account Account number
     * @param int $clusterSize How many different patterns use this account
     * @return float Multiplier 1.0+
     */
    public function calculateClusteringBonus(string $account, int $clusterSize): float
    {
        if ($clusterSize <= 0) {
            $clusterSize = 1;
        }
        
        return 1.0 + (0.2 / $clusterSize);
    }
    
    /**
     * Calculate combined score from all factors
     * 
     * Applies scoring formula:
     * (substring + keyword + account) × occurrence × recency × clustering
     * 
     * @param array $factors Scoring factors [
     *     'substring' => float,
     *     'keyword' => float,
     *     'account' => float,
     *     'occurrence' => float (multiplier),
     *     'recency' => float (multiplier),
     *     'clustering' => float (multiplier)
     * ]
     * @return float Combined score
     */
    public function calculateCombinedScore(array $factors): float
    {
        // Sum base factors
        $base = ($factors['substring'] ?? 0) +
                ($factors['keyword'] ?? 0) +
                ($factors['account'] ?? 0);
        
        if ($base === 0) {
            return 0.0;
        }
        
        // Apply multipliers (default to 1.0 if not provided)
        $multiplier = ($factors['occurrence'] ?? 1.0) *
                      ($factors['recency'] ?? 1.0) *
                      ($factors['clustering'] ?? 1.0);
        
        return $base * $multiplier;
    }
}
