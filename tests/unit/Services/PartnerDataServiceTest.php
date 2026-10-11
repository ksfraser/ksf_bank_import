<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\PartnerDataService;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use InvalidArgumentException;

/**
 * Unit tests for PartnerDataService
 *
 * @package Tests\Unit\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class PartnerDataServiceTest extends TestCase
{
    /**
     * @var PartnerDataService
     */
    private PartnerDataService $service;

    /**
     * @var PartnerDataRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockRepository;

    /**
     * @var KeywordExtractorService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockExtractor;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = $this->createMock(PartnerDataRepositoryInterface::class);
        $this->mockExtractor = $this->createMock(KeywordExtractorService::class);
        
        $this->service = new PartnerDataService(
            $this->mockRepository,
            $this->mockExtractor
        );
    }

    /**
     * Test save passes PartnerData to repository
     */
    public function testSavePassesPartnerDataToRepository(): void
    {
        $partnerData = new PartnerData(1, 1, 100, 'test', 1);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->equalTo($partnerData))
            ->willReturn(true);
        
        $result = $this->service->save($partnerData);
        
        $this->assertTrue($result);
    }

    /**
     * Test saveKeyword validates and saves keyword
     */
    public function testSaveKeywordValidatesAndSaves(): void
    {
        $this->mockExtractor
            ->expects($this->once())
            ->method('isValid')
            ->with('test')
            ->willReturn(true);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);
        
        $result = $this->service->saveKeyword(1, 1, 100, 'test', 1);
        
        $this->assertTrue($result);
    }

    /**
     * Test saveKeyword throws exception for invalid keyword
     */
    public function testSaveKeywordThrowsExceptionForInvalidKeyword(): void
    {
        $this->mockExtractor
            ->expects($this->once())
            ->method('isValid')
            ->with('ab')
            ->willReturn(false);
        
        $this->mockRepository
            ->expects($this->never())
            ->method('save');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid keyword: ab');
        
        $this->service->saveKeyword(1, 1, 100, 'ab', 1);
    }

    /**
     * Test saveKeywordsFromText extracts and saves keywords
     */
    public function testSaveKeywordsFromTextExtractsAndSaves(): void
    {
        $text = 'shoppers drug mart';
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extractAsStrings')
            ->with($text)
            ->willReturn(['shoppers', 'drug', 'mart']);
        
        $this->mockExtractor
            ->expects($this->exactly(3))
            ->method('isValid')
            ->willReturn(true);
        
        $this->mockRepository
            ->expects($this->exactly(3))
            ->method('save')
            ->willReturn(true);
        
        $count = $this->service->saveKeywordsFromText(1, 1, 100, $text);
        
        $this->assertEquals(3, $count);
    }

    /**
     * Test saveKeywordsFromText skips invalid keywords
     */
    public function testSaveKeywordsFromTextSkipsInvalidKeywords(): void
    {
        $text = 'test ab valid';
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extractAsStrings')
            ->with($text)
            ->willReturn(['test', 'ab', 'valid']);
        
        $this->mockExtractor
            ->expects($this->exactly(3))
            ->method('isValid')
            ->willReturnCallback(function($keyword) {
                return $keyword !== 'ab'; // 'ab' is too short
            });
        
        $this->mockRepository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturn(true);
        
        $count = $this->service->saveKeywordsFromText(1, 1, 100, $text);
        
        $this->assertEquals(2, $count);
    }

    /**
     * Test saveKeywordsFromText returns zero for no keywords
     */
    public function testSaveKeywordsFromTextReturnsZeroForNoKeywords(): void
    {
        $text = 'the and or'; // All stopwords
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extractAsStrings')
            ->with($text)
            ->willReturn([]);
        
        $this->mockRepository
            ->expects($this->never())
            ->method('save');
        
        $count = $this->service->saveKeywordsFromText(1, 1, 100, $text);
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test incrementKeywordOccurrence calls repository
     */
    public function testIncrementKeywordOccurrenceCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('incrementOccurrence')
            ->with(1, 1, 100, 'test', 1)
            ->willReturn(true);
        
        $result = $this->service->incrementKeywordOccurrence(1, 1, 100, 'test', 1);
        
        $this->assertTrue($result);
    }

    /**
     * Test incrementKeywordOccurrence with custom increment
     */
    public function testIncrementKeywordOccurrenceWithCustomIncrement(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('incrementOccurrence')
            ->with(1, 1, 100, 'test', 5)
            ->willReturn(true);
        
        $result = $this->service->incrementKeywordOccurrence(1, 1, 100, 'test', 5);
        
        $this->assertTrue($result);
    }

    /**
     * Test find calls repository
     */
    public function testFindCallsRepository(): void
    {
        $partnerData = new PartnerData(1, 1, 100, 'test', 1);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(1, 1, 100, 'test')
            ->willReturn($partnerData);
        
        $result = $this->service->find(1, 1, 100, 'test');
        
        $this->assertSame($partnerData, $result);
    }

    /**
     * Test find returns null when not found
     */
    public function testFindReturnsNullWhenNotFound(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('find')
            ->with(1, 1, 100, 'nonexistent')
            ->willReturn(null);
        
        $result = $this->service->find(1, 1, 100, 'nonexistent');
        
        $this->assertNull($result);
    }

    /**
     * Test getPartnerKeywords calls repository
     */
    public function testGetPartnerKeywordsCallsRepository(): void
    {
        $keywords = [
            new PartnerData(1, 1, 100, 'test', 1),
            new PartnerData(1, 1, 100, 'keyword', 2)
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('findByPartner')
            ->with(1, null)
            ->willReturn($keywords);
        
        $result = $this->service->getPartnerKeywords(1);
        
        $this->assertSame($keywords, $result);
    }

    /**
     * Test getPartnerKeywords with partner type filter
     */
    public function testGetPartnerKeywordsWithPartnerTypeFilter(): void
    {
        $keywords = [
            new PartnerData(1, 2, 100, 'test', 1)
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('findByPartner')
            ->with(1, 2)
            ->willReturn($keywords);
        
        $result = $this->service->getPartnerKeywords(1, 2);
        
        $this->assertSame($keywords, $result);
    }

    /**
     * Test delete calls repository
     */
    public function testDeleteCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1, 1, 100, 'test')
            ->willReturn(true);
        
        $result = $this->service->delete(1, 1, 100, 'test');
        
        $this->assertTrue($result);
    }

    /**
     * Test deletePartnerKeywords calls repository
     */
    public function testDeletePartnerKeywordsCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('deleteByPartner')
            ->with(1, null)
            ->willReturn(5);
        
        $result = $this->service->deletePartnerKeywords(1);
        
        $this->assertEquals(5, $result);
    }

    /**
     * Test deletePartnerKeywords with partner type filter
     */
    public function testDeletePartnerKeywordsWithPartnerTypeFilter(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('deleteByPartner')
            ->with(1, 2)
            ->willReturn(3);
        
        $result = $this->service->deletePartnerKeywords(1, 2);
        
        $this->assertEquals(3, $result);
    }

    /**
     * Test exists calls repository
     */
    public function testExistsCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 1, 100, 'test')
            ->willReturn(true);
        
        $result = $this->service->exists(1, 1, 100, 'test');
        
        $this->assertTrue($result);
    }

    /**
     * Test count calls repository
     */
    public function testCountCallsRepository(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('count')
            ->with(null)
            ->willReturn(100);
        
        $result = $this->service->count();
        
        $this->assertEquals(100, $result);
    }

    /**
     * Test count with partner type filter
     */
    public function testCountWithPartnerTypeFilter(): void
    {
        $this->mockRepository
            ->expects($this->once())
            ->method('count')
            ->with(2)
            ->willReturn(50);
        
        $result = $this->service->count(2);
        
        $this->assertEquals(50, $result);
    }

    /**
     * Test getTopKeywords calls repository
     */
    public function testGetTopKeywordsCallsRepository(): void
    {
        $topKeywords = [
            ['data' => 'shoppers', 'total_occurrences' => 100],
            ['data' => 'drug', 'total_occurrences' => 80]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('getTopKeywords')
            ->with(20, null)
            ->willReturn($topKeywords);
        
        $result = $this->service->getTopKeywords(20);
        
        $this->assertSame($topKeywords, $result);
    }

    /**
     * Test getTopKeywords with custom limit
     */
    public function testGetTopKeywordsWithCustomLimit(): void
    {
        $topKeywords = [
            ['data' => 'shoppers', 'total_occurrences' => 100]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('getTopKeywords')
            ->with(10, null)
            ->willReturn($topKeywords);
        
        $result = $this->service->getTopKeywords(10);
        
        $this->assertSame($topKeywords, $result);
    }

    /**
     * Test getTopKeywords with partner type filter
     */
    public function testGetTopKeywordsWithPartnerTypeFilter(): void
    {
        $topKeywords = [
            ['data' => 'test', 'total_occurrences' => 50]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('getTopKeywords')
            ->with(20, 2)
            ->willReturn($topKeywords);
        
        $result = $this->service->getTopKeywords(20, 2);
        
        $this->assertSame($topKeywords, $result);
    }

    /**
     * Test rebuildPartnerKeywords throws exception
     */
    public function testRebuildPartnerKeywordsThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('rebuildPartnerKeywords() not yet implemented');
        
        $this->service->rebuildPartnerKeywords(1);
    }

    /**
     * Test saveKeyword with default occurrence count
     */
    public function testSaveKeywordWithDefaultOccurrenceCount(): void
    {
        $this->mockExtractor
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function($partnerData) {
                return $partnerData->getOccurrenceCount() === 1;
            }))
            ->willReturn(true);
        
        $result = $this->service->saveKeyword(1, 1, 100, 'test');
        
        $this->assertTrue($result);
    }

    /**
     * Test saveKeywordsFromText handles save failures gracefully
     */
    public function testSaveKeywordsFromTextHandlesSaveFailures(): void
    {
        $text = 'test keyword';
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extractAsStrings')
            ->willReturn(['test', 'keyword']);
        
        $this->mockExtractor
            ->expects($this->exactly(2))
            ->method('isValid')
            ->willReturn(true);
        
        $this->mockRepository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls(true, false);
        
        $count = $this->service->saveKeywordsFromText(1, 1, 100, $text);
        
        // Should only count successful saves
        $this->assertEquals(1, $count);
    }
}
