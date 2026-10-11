<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;

/**
 * Unit tests for KeywordMatchingService
 *
 * @package Tests\Unit\Services
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class KeywordMatchingServiceTest extends TestCase
{
    /**
     * @var KeywordMatchingService
     */
    private KeywordMatchingService $service;

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
        
        $this->service = new KeywordMatchingService(
            $this->mockRepository,
            $this->mockExtractor,
            null // No ConfigService - will use defaults
        );
    }

    /**
     * Test search returns matches
     */
    public function testSearchReturnsMatches(): void
    {
        $searchText = 'shoppers drug mart';
        $keywords = [
            new Keyword('shoppers'),
            new Keyword('drug'),
            new Keyword('mart')
        ];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($searchText)
            ->willReturn($keywords);
        
        $repositoryResults = [
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 100,
                'partner_name' => 'Shoppers Drug Mart',
                'matched_keywords' => 'shoppers,drug,mart',
                'keyword_count' => 3,
                'total_score' => 15
            ]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $matches = $this->service->search($searchText, null, 5);
        
        $this->assertIsArray($matches);
        $this->assertCount(1, $matches);
        $this->assertContainsOnlyInstancesOf(KeywordMatch::class, $matches);
        
        $match = $matches[0];
        $this->assertEquals(1, $match->getPartnerId());
        $this->assertEquals('Shoppers Drug Mart', $match->getPartnerName());
        $this->assertEquals(3, $match->getMatchedKeywordCount());
    }

    /**
     * Test search filters by confidence threshold
     */
    public function testSearchFiltersByConfidence(): void
    {
        $searchText = 'test word example';
        $keywords = [
            new Keyword('test'),
            new Keyword('word'),
            new Keyword('example')
        ];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $repositoryResults = [
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 100,
                'partner_name' => 'High Confidence',
                'matched_keywords' => 'test,word,example',
                'keyword_count' => 3,
                'total_score' => 50 // High score, all keywords matched
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 200,
                'partner_name' => 'Low Confidence',
                'matched_keywords' => 'test',
                'keyword_count' => 1,
                'total_score' => 1 // Low score, only 1/3 keywords matched
            ]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $matches = $this->service->search($searchText, null, 10);
        
        // Should only return high confidence match (default threshold 30%)
        // Low confidence: (1/3) * 60% + (1/50) * 40% = 20% + 0.8% = 20.8% (below 30%)
        $this->assertCount(1, $matches);
        $this->assertEquals('High Confidence', $matches[0]->getPartnerName());
    }

    /**
     * Test search applies clustering bonus
     */
    public function testSearchAppliesClusteringBonus(): void
    {
        $searchText = 'shoppers drug mart';
        $keywords = [
            new Keyword('shoppers'),
            new Keyword('drug'),
            new Keyword('mart')
        ];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $repositoryResults = [
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 100,
                'partner_name' => 'Shoppers Drug Mart',
                'matched_keywords' => 'shoppers,drug,mart',
                'keyword_count' => 3,
                'total_score' => 30
            ]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $matches = $this->service->search($searchText, null, 5);
        
        $this->assertCount(1, $matches);
        $match = $matches[0];
        
        // Final score should be higher than raw score due to clustering bonus
        // Formula: rawScore * (1 + ((keywordCount - 1) * 0.2))
        // 30 * (1 + ((3 - 1) * 0.2)) = 30 * 1.4 = 42
        $this->assertEquals(30, $match->getRawScore());
        $this->assertEquals(42.0, $match->getFinalScore());
    }

    /**
     * Test search sorts by keyword count then score
     */
    public function testSearchSortsByKeywordCountThenScore(): void
    {
        $searchText = 'test word example';
        $keywords = [
            new Keyword('test'),
            new Keyword('word'),
            new Keyword('example')
        ];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $repositoryResults = [
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 100,
                'partner_name' => 'One Keyword High Score',
                'matched_keywords' => 'test',
                'keyword_count' => 1,
                'total_score' => 100
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 200,
                'partner_name' => 'Two Keywords Low Score',
                'matched_keywords' => 'test,word',
                'keyword_count' => 2,
                'total_score' => 50
            ]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $matches = $this->service->search($searchText, null, 10);
        
        // Should sort by keyword count first (2 > 1)
        $this->assertCount(2, $matches);
        $this->assertEquals('Two Keywords Low Score', $matches[0]->getPartnerName());
        $this->assertEquals('One Keyword High Score', $matches[1]->getPartnerName());
    }

    /**
     * Test search respects limit
     */
    public function testSearchRespectsLimit(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $repositoryResults = [];
        for ($i = 1; $i <= 10; $i++) {
            $repositoryResults[] = [
                'partner_id' => $i,
                'partner_type' => 1,
                'partner_detail_id' => $i * 100,
                'partner_name' => "Partner {$i}",
                'matched_keywords' => 'test',
                'keyword_count' => 1,
                'total_score' => 50
            ];
        }
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $matches = $this->service->search($searchText, null, 3);
        
        $this->assertCount(3, $matches);
    }

    /**
     * Test search with partner type filter
     */
    public function testSearchWithPartnerTypeFilter(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        $partnerType = 2;
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->with(
                $this->anything(),
                $this->equalTo($partnerType),
                $this->anything()
            )
            ->willReturn([]);
        
        $this->service->search($searchText, $partnerType, 5);
    }

    /**
     * Test search with no keywords returns empty
     */
    public function testSearchWithNoKeywordsReturnsEmpty(): void
    {
        $searchText = 'the and or'; // All stopwords
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($searchText)
            ->willReturn([]);
        
        $this->mockRepository
            ->expects($this->never())
            ->method('searchByKeywords');
        
        $matches = $this->service->search($searchText, null, 5);
        
        $this->assertIsArray($matches);
        $this->assertCount(0, $matches);
    }

    /**
     * Test search with no matches returns empty
     */
    public function testSearchWithNoMatchesReturnsEmpty(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn([]);
        
        $matches = $this->service->search($searchText, null, 5);
        
        $this->assertIsArray($matches);
        $this->assertCount(0, $matches);
    }

    /**
     * Test getTopMatch returns best match
     */
    public function testGetTopMatchReturnsBestMatch(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $repositoryResults = [
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 100,
                'partner_name' => 'Best Match',
                'matched_keywords' => 'test',
                'keyword_count' => 1,
                'total_score' => 100
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 200,
                'partner_name' => 'Second Match',
                'matched_keywords' => 'test',
                'keyword_count' => 1,
                'total_score' => 50
            ]
        ];
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn($repositoryResults);
        
        $match = $this->service->getTopMatch($searchText, null);
        
        $this->assertInstanceOf(KeywordMatch::class, $match);
        $this->assertEquals('Best Match', $match->getPartnerName());
    }

    /**
     * Test getTopMatch returns null when no matches
     */
    public function testGetTopMatchReturnsNullWhenNoMatches(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->willReturn([]);
        
        $match = $this->service->getTopMatch($searchText, null);
        
        $this->assertNull($match);
    }

    /**
     * Test calculateScore with single keyword
     */
    public function testCalculateScoreWithSingleKeyword(): void
    {
        $score = $this->service->calculateScore(100, 1);
        
        // No clustering bonus for single keyword
        // 100 * (1 + ((1 - 1) * 0.2)) = 100
        $this->assertEquals(100.0, $score);
    }

    /**
     * Test calculateScore with multiple keywords
     */
    public function testCalculateScoreWithMultipleKeywords(): void
    {
        $score = $this->service->calculateScore(100, 3);
        
        // Clustering bonus: 100 * (1 + ((3 - 1) * 0.2)) = 140
        $this->assertEquals(140.0, $score);
    }

    /**
     * Test calculateScore with many keywords
     */
    public function testCalculateScoreWithManyKeywords(): void
    {
        $score = $this->service->calculateScore(100, 5);
        
        // Clustering bonus: 100 * (1 + ((5 - 1) * 0.2)) = 180
        $this->assertEquals(180.0, $score);
    }

    /**
     * Test search handles empty search text
     */
    public function testSearchHandlesEmptyText(): void
    {
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->with('')
            ->willReturn([]);
        
        $this->mockRepository
            ->expects($this->never())
            ->method('searchByKeywords');
        
        $matches = $this->service->search('', null, 5);
        
        $this->assertIsArray($matches);
        $this->assertCount(0, $matches);
    }

    /**
     * Test search extracts keywords correctly
     */
    public function testSearchExtractsKeywordsCorrectly(): void
    {
        $searchText = 'shoppers drug mart';
        $keywords = [
            new Keyword('shoppers'),
            new Keyword('drug'),
            new Keyword('mart')
        ];
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($searchText)
            ->willReturn($keywords);
        
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->with(
                $this->equalTo(['shoppers', 'drug', 'mart']),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([]);
        
        $this->service->search($searchText, null, 5);
    }

    /**
     * Test search passes limit multiplier to repository
     */
    public function testSearchPassesLimitMultiplierToRepository(): void
    {
        $searchText = 'test';
        $keywords = [new Keyword('test')];
        $limit = 5;
        
        $this->mockExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($keywords);
        
        // Should request limit * 2 from repository for filtering
        $this->mockRepository
            ->expects($this->once())
            ->method('searchByKeywords')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo($limit * 2)
            )
            ->willReturn([]);
        
        $this->service->search($searchText, null, $limit);
    }
}
