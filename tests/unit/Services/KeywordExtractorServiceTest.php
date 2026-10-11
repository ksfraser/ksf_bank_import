<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\Exceptions\InvalidKeywordException;

/**
 * Unit tests for KeywordExtractorService
 *
 * @package Tests\Unit\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class KeywordExtractorServiceTest extends TestCase
{
    /**
     * @var KeywordExtractorService
     */
    private KeywordExtractorService $service;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KeywordExtractorService();
    }

    /**
     * Test extraction of simple keywords
     */
    public function testExtractsSimpleKeywords(): void
    {
        $keywords = $this->service->extract('hello world');
        
        $this->assertCount(2, $keywords);
        $this->assertContainsOnlyInstancesOf(Keyword::class, $keywords);
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        $this->assertContains('hello', $texts);
        $this->assertContains('world', $texts);
    }

    /**
     * Test extraction filters stopwords
     */
    public function testFiltersStopwords(): void
    {
        $keywords = $this->service->extract('the quick brown fox and the lazy dog');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        // Should include meaningful words
        $this->assertContains('quick', $texts);
        $this->assertContains('brown', $texts);
        $this->assertContains('fox', $texts);
        $this->assertContains('lazy', $texts);
        $this->assertContains('dog', $texts);
        
        // Should filter stopwords
        $this->assertNotContains('the', $texts);
        $this->assertNotContains('and', $texts);
    }

    /**
     * Test extraction handles punctuation
     */
    public function testHandlesPunctuation(): void
    {
        $keywords = $this->service->extract('Hello, world! How are you?');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertContains('hello', $texts);
        $this->assertContains('world', $texts);
        $this->assertContains('how', $texts);
        $this->assertNotContains('you', $texts); // "you" is a stopword
    }

    /**
     * Test extraction preserves hyphens
     */
    public function testPreservesHyphens(): void
    {
        $keywords = $this->service->extract('shoppers drug-mart');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertContains('shoppers', $texts);
        $this->assertContains('drug-mart', $texts);
    }

    /**
     * Test extraction filters by minimum length
     */
    public function testFiltersShortKeywords(): void
    {
        $service = new KeywordExtractorService([], 4); // Min length 4
        $keywords = $service->extract('the cat sat on the mat');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        // Should filter words shorter than 4 characters
        $this->assertNotContains('cat', $texts);
        $this->assertNotContains('sat', $texts);
        $this->assertNotContains('mat', $texts);
        $this->assertNotContains('the', $texts);
        $this->assertNotContains('on', $texts);
    }

    /**
     * Test extraction removes duplicates
     */
    public function testRemovesDuplicates(): void
    {
        $keywords = $this->service->extract('test test test');
        
        $this->assertCount(1, $keywords);
        $this->assertEquals('test', $keywords[0]->getText());
    }

    /**
     * Test extraction handles empty text
     */
    public function testHandlesEmptyText(): void
    {
        $keywords = $this->service->extract('');
        
        $this->assertIsArray($keywords);
        $this->assertCount(0, $keywords);
    }

    /**
     * Test extraction handles whitespace-only text
     */
    public function testHandlesWhitespaceOnly(): void
    {
        $keywords = $this->service->extract('   ');
        
        $this->assertIsArray($keywords);
        $this->assertCount(0, $keywords);
    }

    /**
     * Test extraction handles all stopwords
     */
    public function testHandlesAllStopwords(): void
    {
        $keywords = $this->service->extract('the and or but');
        
        $this->assertIsArray($keywords);
        $this->assertCount(0, $keywords);
    }

    /**
     * Test extractAsStrings returns string array
     */
    public function testExtractAsStringsReturnsStringArray(): void
    {
        $keywords = $this->service->extractAsStrings('hello world');
        
        $this->assertIsArray($keywords);
        $this->assertCount(2, $keywords);
        $this->assertContainsOnly('string', $keywords);
        $this->assertContains('hello', $keywords);
        $this->assertContains('world', $keywords);
    }

    /**
     * Test isValid accepts valid keywords
     */
    public function testIsValidAcceptsValidKeywords(): void
    {
        $this->assertTrue($this->service->isValid('test'));
        $this->assertTrue($this->service->isValid('hello'));
        $this->assertTrue($this->service->isValid('world'));
        $this->assertTrue($this->service->isValid('shoppers'));
    }

    /**
     * Test isValid rejects stopwords
     */
    public function testIsValidRejectsStopwords(): void
    {
        $this->assertFalse($this->service->isValid('the'));
        $this->assertFalse($this->service->isValid('and'));
        $this->assertFalse($this->service->isValid('or'));
    }

    /**
     * Test isValid rejects short keywords
     */
    public function testIsValidRejectsShortKeywords(): void
    {
        $this->assertFalse($this->service->isValid('ab'));
        $this->assertFalse($this->service->isValid('a'));
        $this->assertFalse($this->service->isValid(''));
    }

    /**
     * Test isValid with custom minimum length
     */
    public function testIsValidWithCustomMinLength(): void
    {
        $service = new KeywordExtractorService([], 5);
        
        $this->assertFalse($service->isValid('test')); // 4 chars
        $this->assertTrue($service->isValid('hello')); // 5 chars
    }

    /**
     * Test addStopword adds to stopword list
     */
    public function testAddStopwordAddsToList(): void
    {
        $this->service->addStopword('custom');
        
        $keywords = $this->service->extract('custom word test');
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertNotContains('custom', $texts);
        $this->assertContains('word', $texts);
        $this->assertContains('test', $texts);
    }

    /**
     * Test constructor with custom stopwords
     */
    public function testConstructorWithCustomStopwords(): void
    {
        $service = new KeywordExtractorService(['custom', 'stopword']);
        
        $keywords = $service->extract('custom stopword test');
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertNotContains('custom', $texts);
        $this->assertNotContains('stopword', $texts);
        $this->assertContains('test', $texts);
    }

    /**
     * Test extraction handles special characters
     */
    public function testHandlesSpecialCharacters(): void
    {
        $keywords = $this->service->extract('hello@world test#123 foo$bar');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertContains('hello', $texts);
        $this->assertContains('world', $texts);
        $this->assertContains('test', $texts);
        $this->assertContains('foo', $texts);
        $this->assertContains('bar', $texts);
    }

    /**
     * Test extraction handles multiple spaces
     */
    public function testHandlesMultipleSpaces(): void
    {
        $keywords = $this->service->extract('hello    world     test');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertCount(3, $keywords);
        $this->assertContains('hello', $texts);
        $this->assertContains('world', $texts);
        $this->assertContains('test', $texts);
    }

    /**
     * Test extraction handles numeric keywords
     */
    public function testHandlesNumericKeywords(): void
    {
        // Pure numbers should be filtered by Keyword validation
        $keywords = $this->service->extract('test 123 hello 456');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertContains('test', $texts);
        $this->assertContains('hello', $texts);
        // Pure numbers are invalid keywords
        $this->assertNotContains('123', $texts);
        $this->assertNotContains('456', $texts);
    }

    /**
     * Test extraction handles mixed case
     */
    public function testHandlesMixedCase(): void
    {
        $keywords = $this->service->extract('Hello WORLD TeSt');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        // Keywords are normalized to lowercase
        $this->assertContains('hello', $texts);
        $this->assertContains('world', $texts);
        $this->assertContains('test', $texts);
    }

    /**
     * Test extraction with real-world transaction memo
     */
    public function testExtractsFromTransactionMemo(): void
    {
        $memo = 'SHOPPERS DRUG MART #123 - PHARMACY PURCHASE';
        $keywords = $this->service->extract($memo);
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        $this->assertContains('shoppers', $texts);
        $this->assertContains('drug', $texts);
        $this->assertContains('mart', $texts);
        $this->assertContains('pharmacy', $texts);
        $this->assertContains('purchase', $texts);
    }

    /**
     * Test extraction filters purely numeric strings
     */
    public function testFiltersPurelyNumeric(): void
    {
        $keywords = $this->service->extract('test 12345 hello 67890');
        
        $texts = array_map(fn($k) => $k->getText(), $keywords);
        
        // Alphanumeric words should pass
        $this->assertContains('test', $texts);
        $this->assertContains('hello', $texts);
        
        // Pure numbers should be filtered
        $this->assertNotContains('12345', $texts);
        $this->assertNotContains('67890', $texts);
    }
}
