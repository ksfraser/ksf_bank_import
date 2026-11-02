<?php

namespace Ksfraser\FaBankImport\Tests\Application\Partner;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Application\Partner\KeywordExtractor;

/**
 * Unit tests for KeywordExtractor service
 * 
 * Tests keyword extraction, phrase recognition, and stopword filtering
 * This service is used by both search and data builder
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class KeywordExtractorTest extends TestCase
{
    private KeywordExtractor $extractor;
    
    protected function setUp(): void
    {
        $this->extractor = new KeywordExtractor();
    }
    
    /**
     * Test extract basic keywords from text
     */
    public function test_extract_basic_keywords(): void
    {
        $keywords = $this->extractor->extract('Credit Card Payment from Bank');
        
        $this->assertContains('Credit', $keywords);
        $this->assertContains('Card', $keywords);
        $this->assertContains('Payment', $keywords);
        $this->assertContains('Bank', $keywords);
    }
    
    /**
     * Test extract recognizes known phrases
     */
    public function test_extract_recognizes_known_phrases(): void
    {
        $keywords = $this->extractor->extract('Pre-Auth Debit; Credit Card from Bank of Montreal');
        
        // Should recognize "Pre-Auth" and "Credit Card" as phrases
        $this->assertContains('Pre-Auth', $keywords);
        $this->assertContains('Credit Card', $keywords);
    }
    
    /**
     * Test extract removes stopwords
     */
    public function test_extract_removes_stopwords(): void
    {
        $keywords = $this->extractor->extract('Transaction from the bank on the account');
        
        // Stopwords should be removed
        $this->assertNotContains('from', $keywords);
        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('on', $keywords);
        $this->assertNotContains('a', $keywords);
    }
    
    /**
     * Test extract filters short words
     */
    public function test_extract_filters_short_words(): void
    {
        $keywords = $this->extractor->extract('Go to the Store now for your items');
        
        // Single and double-char words should be filtered
        foreach ($keywords as $keyword) {
            $this->assertGreaterThanOrEqual(3, strlen($keyword));
        }
    }
    
    /**
     * Test extract case-insensitive for stopwords but preserves case for keywords
     */
    public function test_extract_case_insensitive_stopwords(): void
    {
        $keywords = $this->extractor->extract('THE BANK FROM MONTREAL');
        
        // Stopwords should be filtered even if uppercase
        $this->assertNotContains('THE', $keywords);
        $this->assertNotContains('FROM', $keywords);
        
        // Content words should be kept
        $this->assertContains('BANK', $keywords);
        $this->assertContains('MONTREAL', $keywords);
    }
    
    /**
     * Test extract handles special characters
     */
    public function test_extract_handles_special_characters(): void
    {
        $keywords = $this->extractor->extract('E-Transfer #123; Pre-Auth (Debit) $50.00');
        
        // Should recognize E-Transfer and Pre-Auth as valid keywords
        $this->assertContains('E-Transfer', $keywords);
        $this->assertContains('Pre-Auth', $keywords);
    }
    
    /**
     * Test extract returns consistent results
     */
    public function test_extract_returns_consistent_results(): void
    {
        $text = 'Pre-Auth Debit; Bank of Montreal; Credit Card';
        $keywords1 = $this->extractor->extract($text);
        $keywords2 = $this->extractor->extract($text);
        
        $this->assertEquals($keywords1, $keywords2);
    }
    
    /**
     * Test extract recognizes all known phrases
     */
    public function test_extract_recognizes_all_known_phrases(): void
    {
        $phrases = ['Pre-Auth', 'Credit Card', 'Debit Card', 'E-Transfer', 'Wire Transfer', 'Square Up', 'Group Benefit'];
        
        foreach ($phrases as $phrase) {
            $keywords = $this->extractor->extract("Transaction: {$phrase}");
            $this->assertContains($phrase, $keywords, "Failed to recognize phrase: {$phrase}");
        }
    }
    
    /**
     * Test extract with real transaction description
     */
    public function test_extract_real_transaction_example(): void
    {
        $description = '70;GROUP BENEFIT;Insurance Premium for 2025;Your Company Inc;PAD';
        $keywords = $this->extractor->extract($description);
        
        // Should extract meaningful keywords
        $this->assertNotEmpty($keywords);
        $this->assertContains('GROUP', $keywords);
        $this->assertContains('Insurance', $keywords);
    }
    
    /**
     * Test extract with empty string
     */
    public function test_extract_empty_string(): void
    {
        $keywords = $this->extractor->extract('');
        
        $this->assertIsArray($keywords);
        $this->assertEmpty($keywords);
    }
    
    /**
     * Test extract with only stopwords
     */
    public function test_extract_only_stopwords(): void
    {
        $keywords = $this->extractor->extract('the from and or');
        
        // All should be filtered as stopwords
        $this->assertEmpty($keywords);
    }
}
