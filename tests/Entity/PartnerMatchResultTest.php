<?php

namespace Ksfraser\FaBankImport\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerMatchResult;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * Unit tests for PartnerMatchResult value object
 * 
 * Tests that match results correctly store partner, confidence, and scoring factors
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerMatchResultTest extends TestCase
{
    /**
     * Test match result stores partner and confidence
     */
    public function test_match_result_stores_partner_and_confidence(): void
    {
        $partner = new PartnerEntity(1, 'Test Partner', PartnerType::SUPPLIER);
        $result = new PartnerMatchResult(
            partner: $partner,
            confidence: 0.95
        );
        
        $this->assertSame($partner, $result->partner());
        $this->assertEquals(0.95, $result->confidence());
    }
    
    /**
     * Test match result clamps confidence between 0 and 1
     */
    public function test_match_result_clamps_confidence(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        
        // Over 1.0 should clamp to 1.0
        $result1 = new PartnerMatchResult($partner, confidence: 1.5);
        $this->assertEquals(1.0, $result1->confidence());
        
        // Negative should clamp to 0.0
        $result2 = new PartnerMatchResult($partner, confidence: -0.5);
        $this->assertEquals(0.0, $result2->confidence());
    }
    
    /**
     * Test match result stores scoring factors
     */
    public function test_match_result_stores_factors(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        $factors = [
            'substring' => 100,
            'keyword' => 10,
            'account' => 80,
            'occurrence' => 0.8,
            'recency' => 0.99,
            'clustering' => 0.2
        ];
        
        $result = new PartnerMatchResult($partner, 0.92, $factors);
        
        $this->assertEquals($factors, $result->factors());
    }
    
    /**
     * Test match result calculates total score from factors
     */
    public function test_match_result_calculates_total_score(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        $factors = [
            'substring' => 100,
            'keyword' => 10,
            'account' => 80,
            'occurrence' => 1.0,
            'recency' => 1.0,
            'clustering' => 1.0
        ];
        
        $result = new PartnerMatchResult($partner, 0.95, $factors);
        
        // totalScore = sum of base factors * multipliers
        // = (100 + 10 + 80) * (1.0 * 1.0 * 1.0) = 190
        $this->assertEquals(190, $result->totalScore());
    }
    
    /**
     * Test match result applies multipliers to factors
     */
    public function test_match_result_applies_multipliers(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        $factors = [
            'substring' => 100,
            'keyword' => 10,
            'account' => 80,
            'occurrence' => 0.5,      // Multiplier
            'recency' => 0.99,         // Multiplier
            'clustering' => 0.2        // Multiplier
        ];
        
        $result = new PartnerMatchResult($partner, 0.92, $factors);
        
        // totalScore = (100 + 10 + 80) * (0.5 * 0.99 * 0.2)
        // = 190 * 0.099 = 18.81 (use delta for floating point comparison)
        $this->assertEqualsWithDelta(
            190 * 0.5 * 0.99 * 0.2,
            $result->totalScore(),
            0.01  // Allow 0.01 delta for floating point precision
        );
    }
    
    /**
     * Test match result with default empty factors
     */
    public function test_match_result_defaults_factors(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        $result = new PartnerMatchResult($partner, 0.8);
        
        $this->assertEquals([], $result->factors());
        $this->assertEquals(0, $result->totalScore());
    }
    
    /**
     * Test confidence is normalized 0-1 range
     */
    public function test_match_result_confidence_validation(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        
        $validResults = [0.0, 0.25, 0.5, 0.75, 1.0];
        foreach ($validResults as $confidence) {
            $result = new PartnerMatchResult($partner, $confidence);
            $this->assertEquals($confidence, $result->confidence());
        }
    }
}
