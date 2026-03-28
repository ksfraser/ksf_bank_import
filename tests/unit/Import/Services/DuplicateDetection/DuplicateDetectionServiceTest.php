<?php

namespace Ksfraser\FaBankImport\tests\unit\Import\Services\DuplicateDetection;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateCheckResult;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DirectCodeMatcher;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\FuzzyMatcher;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateRulesProvider;
use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateDetectionService;

/**
 * Unit Tests for Duplicate Detection Service
 * 
 * Tests all three levels of duplicate detection and decision logic.
 */
class DuplicateDetectionServiceTest extends TestCase
{
    private $service;
    
    protected function setUp(): void
    {
        $this->service = new DuplicateDetectionService();
    }
    
    // ===== DuplicateCheckResult Tests =====
    
    /**
     * Test exact match result creation
     */
    public function test_exactMatch_creates_skip_result()
    {
        $existing = ['id' => 1, 'transactionCode' => 'RBC-001'];
        $result = DuplicateCheckResult::exactMatch($existing);
        
        $this->assertTrue($result->isDuplicate());
        $this->assertEquals('EXACT', $result->getLevel());
        $this->assertEquals('SKIP', $result->getRecommendedAction());
        $this->assertTrue($result->shouldSkip());
        $this->assertFalse($result->shouldImport());
    }
    
    /**
     * Test not duplicate result
     */
    public function test_notDuplicate_creates_import_result()
    {
        $result = DuplicateCheckResult::notDuplicate();
        
        $this->assertFalse($result->isDuplicate());
        $this->assertEquals('NONE', $result->getLevel());
        $this->assertEquals('IMPORT', $result->getRecommendedAction());
        $this->assertTrue($result->shouldImport());
        $this->assertFalse($result->shouldSkip());
    }
    
    /**
     * Test fuzzy match with whitelist rule
     */
    public function test_fuzzyMatchAllowed_with_rule()
    {
        $match = ['id' => 2, 'merchant' => 'SHOPPERS'];
        $rule = ['rule_name' => 'SHOPPERS_RETAIL', 'allow_duplicates' => 1];
        
        $result = DuplicateCheckResult::fuzzyMatchAllowed($match, $rule);
        
        $this->assertTrue($result->isDuplicate());
        $this->assertEquals('FUZZY', $result->getLevel());
        $this->assertEquals('ALLOWED_REPEAT', $result->getRecommendedAction());
        $this->assertTrue($result->shouldImport());
        $this->assertNotNull($result->getWhitelistRule());
    }
    
    /**
     * Test fuzzy match needs review
     */
    public function test_fuzzyMatchNeedsReview()
    {
        $matches = [['id' => 1], ['id' => 2]];
        $result = DuplicateCheckResult::fuzzyMatchNeedsReview($matches);
        
        $this->assertTrue($result->isDuplicate());
        $this->assertEquals('FUZZY', $result->getLevel());
        $this->assertEquals('REVIEW', $result->getRecommendedAction());
        $this->assertTrue($result->needsReview());
        $this->assertFalse($result->shouldImport());
        $this->assertCount(2, $result->getFuzzyMatches());
    }
    
    // ===== Integration Tests (all three levels) =====
    
    /**
     * Test Level 1: Exact duplicate found
     */
    public function test_level1_exact_code_match_returns_skip()
    {
        // Mock: DirectCodeMatcher finds existing transaction
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn([
            'id' => 1,
            'transactionCode' => 'RBC-001',
            'acctid' => 'ACC123'
        ]);
        
        $service = new DuplicateDetectionService($directMatcher, new FuzzyMatcher(), new DuplicateRulesProvider());
        
        $result = $service->detect([
            'transactionCode' => 'RBC-001',
            'acctid' => 'ACC123'
        ]);
        
        $this->assertTrue($result->shouldSkip());
        $this->assertEquals('EXACT', $result->getLevel());
    }
    
    /**
     * Test Level 2: No fuzzy match found
     */
    public function test_level2_no_fuzzy_match_returns_import()
    {
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn(null);
        
        $fuzzyMatcher = $this->createMock(FuzzyMatcher::class);
        $fuzzyMatcher->method('find')->willReturn([]);  // No matches
        
        $service = new DuplicateDetectionService($directMatcher, $fuzzyMatcher, new DuplicateRulesProvider());
        
        $result = $service->detect([
            'valueTimestamp' => '2025-01-15',
            'transactionAmount' => 100.00,
            'merchant' => 'NEW MERCHANT',
            'acctid' => 'ACC123'
        ]);
        
        $this->assertTrue($result->shouldImport());
        $this->assertEquals('NONE', $result->getLevel());
    }
    
    /**
     * Test Level 2+3: Fuzzy match found and whitelisted
     */
    public function test_level2_fuzzy_match_whitelisted_returns_import()
    {
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn(null);
        
        $fuzzyMatcher = $this->createMock(FuzzyMatcher::class);
        $fuzzyMatcher->method('find')->willReturn([['id' => 1, 'merchant' => 'SHOPPERS']]);
        
        $rulesProvider = $this->createMock(DuplicateRulesProvider::class);
        $rulesProvider->method('findMatchingRule')->willReturn([
            'rule_name' => 'SHOPPERS_RETAIL',
            'allow_duplicates' => 1
        ]);
        
        $service = new DuplicateDetectionService($directMatcher, $fuzzyMatcher, $rulesProvider);
        
        $result = $service->detect([
            'transactionCode' => 'RBC-002',
            'acctid' => 'ACC123',
            'merchant' => 'SHOPPERS'
        ]);
        
        $this->assertTrue($result->shouldImport());
        $this->assertEquals('ALLOWED_REPEAT', $result->getRecommendedAction());
        $this->assertNotNull($result->getWhitelistRule());
    }
    
    /**
     * Test Level 2+3: Fuzzy match found, not whitelisted
     */
    public function test_level2_fuzzy_match_not_whitelisted_shows_review()
    {
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn(null);
        
        $fuzzyMatcher = $this->createMock(FuzzyMatcher::class);
        $fuzzyMatcher->method('find')->willReturn([['id' => 1, 'merchant' => 'PAYROLL']]);
        
        $rulesProvider = $this->createMock(DuplicateRulesProvider::class);
        $rulesProvider->method('findMatchingRule')->willReturn(null);  // No rule
        
        $service = new DuplicateDetectionService($directMatcher, $fuzzyMatcher, $rulesProvider);
        
        $result = $service->detect([
            'transactionCode' => 'RBC-002',
            'acctid' => 'ACC123',
            'merchant' => 'PAYROLL'
        ]);
        
        $this->assertTrue($result->needsReview());
        $this->assertEquals('REVIEW', $result->getRecommendedAction());
        $this->assertCount(1, $result->getFuzzyMatches());
    }
    
    /**
     * Test scenario: RBC re-download with different code
     * 
     * Level 1 fails (code changed)
     * Level 2 finds fuzzy match (same date, amount, merchant)
     * Level 3 rule says: not whitelisted, show review
     */
    public function test_rbc_redownload_scenario()
    {
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn(null);  // Code changed
        
        $fuzzyMatcher = $this->createMock(FuzzyMatcher::class);
        $fuzzyMatcher->method('find')->willReturn([
            ['id' => 1, 'transactionCode' => 'RBC-001', 'merchant' => 'PAYROLL']
        ]);  // Found by fuzzy
        
        $rulesProvider = $this->createMock(DuplicateRulesProvider::class);
        $rulesProvider->method('findMatchingRule')->willReturn([
            'rule_name' => 'PAYROLL_RECURRING',
            'allow_duplicates' => 0  // Require review for payroll
        ]);
        
        $service = new DuplicateDetectionService($directMatcher, $fuzzyMatcher, $rulesProvider);
        
        $result = $service->detect([
            'transactionCode' => 'RBC-002',  // Different code
            'acctid' => 'ACC123',
            'valueTimestamp' => '2025-01-15',
            'transactionAmount' => 500.00,
            'merchant' => 'PAYROLL',
            'memo' => 'Direct deposit'
        ]);
        
        // Should show user review (not auto-import or auto-skip)
        $this->assertTrue($result->needsReview());
        $this->assertTrue($result->isDuplicate());
    }
    
    /**
     * Test scenario: Shoppers multiple purchases
     * 
     * Two transactions, same merchant/date, different codes
     * Both should be allowed with SHOPPERS_RETAIL rule
     */
    public function test_shoppers_repeat_scenario()
    {
        $directMatcher = $this->createMock(DirectCodeMatcher::class);
        $directMatcher->method('find')->willReturn(null);  // Different codes
        
        $fuzzyMatcher = $this->createMock(FuzzyMatcher::class);
        $fuzzyMatcher->method('find')->willReturn([
            ['id' => 1, 'merchant' => 'SHOPPERS']  // First purchase
        ]);
        
        $rulesProvider = $this->createMock(DuplicateRulesProvider::class);
        $rulesProvider->method('findMatchingRule')->willReturn([
            'rule_name' => 'SHOPPERS_RETAIL',
            'allow_duplicates' => 1  // Allow repeats for retail
        ]);
        
        $service = new DuplicateDetectionService($directMatcher, $fuzzyMatcher, $rulesProvider);
        
        $result = $service->detect([
            'transactionCode' => 'TXN-002',
            'acctid' => 'ACC123',
            'valueTimestamp' => '2025-01-15',
            'transactionAmount' => 45.99,
            'merchant' => 'SHOPPERS',
            'memo' => 'Pharmacy'
        ]);
        
        // Should import (allowed repeat)
        $this->assertTrue($result->shouldImport());
        $this->assertEquals('ALLOWED_REPEAT', $result->getRecommendedAction());
    }
}
