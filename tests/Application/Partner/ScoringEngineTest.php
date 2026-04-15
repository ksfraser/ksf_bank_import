<?php

namespace Ksfraser\FaBankImport\Tests\Application\Partner;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Application\Partner\ScoringEngine;

/**
 * Unit tests for ScoringEngine service
 * 
 * Tests 6-factor scoring algorithm:
 * - Substring matching (+100)
 * - Keyword matching (+10 per match)
 * - Account matching (+80)
 * - Occurrence multiplier (×0.5-1.0)
 * - Recency multiplier (×0.5-1.0)
 * - Clustering bonus (×0.2-infinity)
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class ScoringEngineTest extends TestCase
{
    private ScoringEngine $engine;
    
    protected function setUp(): void
    {
        $this->engine = new ScoringEngine();
    }
    
    /**
     * Test substring match scores +100
     */
    public function test_substring_match_scores_high(): void
    {
        $score = $this->engine->calculateSubstringScore(
            text: "Pre-Auth Debit; Bank",
            pattern: "Pre-Auth"
        );
        
        $this->assertEquals(100, $score);
    }
    
    /**
     * Test substring no-match scores 0
     */
    public function test_substring_no_match(): void
    {
        $score = $this->engine->calculateSubstringScore(
            text: "Something Else",
            pattern: "Pre-Auth"
        );
        
        $this->assertEquals(0, $score);
    }
    
    /**
     * Test substring match is case-insensitive
     */
    public function test_substring_match_case_insensitive(): void
    {
        $score1 = $this->engine->calculateSubstringScore("pre-auth", "Pre-Auth");
        $score2 = $this->engine->calculateSubstringScore("PRE-AUTH", "pre-auth");
        
        $this->assertEquals(100, $score1);
        $this->assertEquals(100, $score2);
    }
    
    /**
     * Test keyword matching scores based on match count
     */
    public function test_keyword_match_scoring(): void
    {
        $keywords = ['Pre-Auth', 'Debit', 'Bank'];
        $patternKeywords = ['Pre-Auth', 'Debit', 'Card'];
        
        $score = $this->engine->calculateKeywordScore($keywords, $patternKeywords);
        
        // 2 matches (Pre-Auth, Debit) * 10 = 20
        $this->assertEquals(20, $score);
    }
    
    /**
     * Test keyword no-match scores 0
     */
    public function test_keyword_no_match(): void
    {
        $keywords = ['Debit', 'Bank'];
        $patternKeywords = ['Credit', 'Card'];
        
        $score = $this->engine->calculateKeywordScore($keywords, $patternKeywords);
        
        $this->assertEquals(0, $score);
    }
    
    /**
     * Test account match scores +80
     */
    public function test_account_match_scores_high(): void
    {
        $score = $this->engine->calculateAccountScore(
            accountNumber: '4567',
            patternAccounts: ['4567', '4568']
        );
        
        $this->assertEquals(80, $score);
    }
    
    /**
     * Test account no-match scores 0
     */
    public function test_account_no_match(): void
    {
        $score = $this->engine->calculateAccountScore(
            accountNumber: '9999',
            patternAccounts: ['4567', '4568']
        );
        
        $this->assertEquals(0, $score);
    }
    
    /**
     * Test occurrence multiplier: diminishing returns
     */
    public function test_occurrence_multiplier_diminishes(): void
    {
        $mult0 = $this->engine->calculateOccurrenceMultiplier(0);
        $mult4 = $this->engine->calculateOccurrenceMultiplier(4);
        $mult100 = $this->engine->calculateOccurrenceMultiplier(100);
        
        // New match or first occurrence = 1.0
        $this->assertEquals(1.0, $mult0);
        
        // Four occurrences = 1/sqrt(4) = 0.5 (hits floor)
        $this->assertEquals(0.5, $mult4);
        
        // Many occurrences is still bounded at 0.5
        $this->assertEquals(0.5, $mult100);
    }
    
    /**
     * Test occurrence 0 returns 1.0
     */
    public function test_occurrence_zero_is_new(): void
    {
        $mult = $this->engine->calculateOccurrenceMultiplier(0);
        
        $this->assertEquals(1.0, $mult);
    }
    
    /**
     * Test recency multiplier: recent is higher
     */
    public function test_recency_decay_applies(): void
    {
        $now = new \DateTime('2026-04-15');
        $recent = new \DateTime('2026-04-14');      // 1 day old
        $old = new \DateTime('2025-04-15');         // 365 days old
        
        $multRecent = $this->engine->calculateRecencyMultiplier($recent, $now);
        $multOld = $this->engine->calculateRecencyMultiplier($old, $now);
        
        // Recent should be higher than old
        $this->assertGreaterThan($multOld, $multRecent);
        
        // Recent should be very high (close to 1.0)
        $this->assertGreaterThan(0.99, $multRecent);
        
        // After 365 days, should be around 0.5 (half-life)
        $this->assertEqualsWithDelta(0.5, $multOld, 0.05);
    }
    
    /**
     * Test clustering bonus increases score for clusters
     */
    public function test_clustering_bonus_for_groups(): void
    {
        $bonus1 = $this->engine->calculateClusteringBonus(account: '4567', clusterSize: 1);
        $bonus3 = $this->engine->calculateClusteringBonus(account: '4567', clusterSize: 3);
        $bonus5 = $this->engine->calculateClusteringBonus(account: '4567', clusterSize: 5);
        
        // Single occurrence = higher bonus
        // Multiple occurrences = lower bonus (diminishing)
        $this->assertGreaterThan($bonus3, $bonus1);
        $this->assertGreaterThan($bonus5, $bonus3);
    }
    
    /**
     * Test combined score calculation
     */
    public function test_combined_score_with_all_factors(): void
    {
        $factors = [
            'substring' => 100,
            'keyword' => 20,
            'account' => 80,
            'occurrence' => 0.8,
            'recency' => 0.99,
            'clustering' => 1.2
        ];
        
        $score = $this->engine->calculateCombinedScore($factors);
        
        // Base = 100 + 20 + 80 = 200
        // Multipliers = 0.8 * 0.99 * 1.2 = 0.9504
        // Total ≈ 200 * 0.9504 = 190.08
        $this->assertEqualsWithDelta(200 * 0.8 * 0.99 * 1.2, $score, 0.01);
    }
    
    /**
     * Test combined score with missing factors defaults to 1.0
     */
    public function test_combined_score_defaults_missing_multipliers(): void
    {
        $factors = [
            'substring' => 100,
            'keyword' => 20,
            'account' => 80
            // No occurrence, recency, clustering
        ];
        
        $score = $this->engine->calculateCombinedScore($factors);
        
        // Should use defaults of 1.0 for missing multipliers
        $this->assertEquals(200, $score);
    }
}
