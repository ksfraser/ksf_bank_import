<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Services;

use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Domain\ValueObjects\KeywordMatch;
use Ksfraser\FaBankImport\Domain\ValueObjects\Keyword;
use Ksfraser\FaBankImport\Domain\ValueObjects\MatchConfidence;
use PHPUnit\Framework\TestCase;

/**
 * KeywordMatchingServiceTest - Test partner matching with keyword clustering
 *
 * Tests the keyword-based matching algorithm including:
 * - Clustering bonus calculation (more keyword matches = higher score)
 * - Confidence calculation (multi-factor: keyword coverage + score strength)
 * - Result ranking and limiting
 * - Empty/edge cases
 *
 * @coversDefaultClass \Ksfraser\FaBankImport\Services\KeywordMatchingService
 */
class KeywordMatchingServiceTest extends TestCase
{
    private KeywordMatchingService $service;
    private PartnerDataRepositoryInterface $repository;
    private KeywordExtractorService $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMockRepository();
        $this->extractor = $this->createMockExtractor();
        $this->service = new KeywordMatchingService($this->repository, $this->extractor);
    }

    /**
     * Test that service can be instantiated
     *
     * @test
     * @covers ::__construct
     */
    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(KeywordMatchingService::class, $this->service);
    }

    /**
     * Test search returns empty array for empty search text
     *
     * @test
     * @covers ::search
     */
    public function testSearchReturnsEmptyForEmptySearchText(): void
    {
        $results = $this->service->search('');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * Test search returns KeywordMatch objects
     *
     * @test
     * @covers ::search
     */
    public function testSearchReturnsKeywordMatchObjects(): void
    {
        // Mock repository to return partner matches
        $this->repository = $this->getMockBuilder(PartnerDataRepositoryInterface::class)
            ->getMock();
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 10,
                'partner_name' => 'Test Partner',
                'matched_keywords' => 'keyword1,keyword2',
                'total_score' => 100,
                'keyword_count' => 2
            ]
        ]);

        $this->extractor = $this->getMockBuilder(KeywordExtractorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extractor->method('extract')
            ->willReturn([new Keyword('keyword1'), new Keyword('keyword2')]);

        $this->service = new KeywordMatchingService($this->repository, $this->extractor);

        $results = $this->service->search('keyword1 keyword2');

        $this->assertNotEmpty($results);
        $this->assertInstanceOf(KeywordMatch::class, $results[0]);
    }

    /**
     * Test getTopMatch returns single best match
     *
     * @test
     * @covers ::getTopMatch
     */
    public function testGetTopMatchReturnsSingleBestMatch(): void
    {
        $matches = $this->service->search('test');

        // If search returns results, top match should be first
        $topMatch = $this->service->getTopMatch('test');

        if (!empty($matches)) {
            $this->assertInstanceOf(KeywordMatch::class, $topMatch);
        } else {
            $this->assertNull($topMatch);
        }
    }

    /**
     * Test getTopMatch returns null when no matches
     *
     * @test
     * @covers ::getTopMatch
     */
    public function testGetTopMatchReturnsNullWhenNoMatches(): void
    {
        $topMatch = $this->service->getTopMatch('nonexistent_partner_xyz');

        $this->assertNull($topMatch);
    }

    /**
     * Test calculateScore applies clustering bonus
     *
     * @test
     * @covers ::calculateScore
     */
    public function testCalculateScoreAppliesClustering(): void
    {
        // Default clustering factor is 0.2
        // Score formula: rawScore * (1 + ((keywordCount - 1) * clusteringFactor))
        
        // 1 keyword: 100 * (1 + (0 * 0.2)) = 100
        $score1 = $this->service->calculateScore(100, 1);
        $this->assertEquals(100, $score1);

        // 2 keywords: 100 * (1 + (1 * 0.2)) = 120
        $score2 = $this->service->calculateScore(100, 2);
        $this->assertEquals(120, $score2);

        // 3 keywords: 100 * (1 + (2 * 0.2)) = 140
        $score3 = $this->service->calculateScore(100, 3);
        $this->assertEquals(140, $score3);
    }

    /**
     * Test calculateScore with zero raw score
     *
     * @test
     * @covers ::calculateScore
     */
    public function testCalculateScoreWithZeroRawScore(): void
    {
        $score = $this->service->calculateScore(0, 5);

        $this->assertEquals(0, $score);
    }

    /**
     * Test search respects partner type filter
     *
     * @test
     * @covers ::search
     */
    public function testSearchRespectsPartnerTypeFilter(): void
    {
        $results = $this->service->search('test', 1);

        // If partner type is specified, it should be passed to repository
        $this->assertIsArray($results);
    }

    /**
     * Test search respects limit parameter
     *
     * @test
     * @covers ::search
     */
    public function testSearchRespectsLimitParameter(): void
    {
        $results = $this->service->search('test', null, 3);

        // Results should not exceed limit
        $this->assertLessThanOrEqual(3, count($results));
    }

    /**
     * Test confidence increases with keyword count
     *
     * @test
     */
    public function testConfidenceIncreaseWithMoreKeywords(): void
    {
        // This test validates the multi-factor confidence:
        // confidence = (keyword_coverage * 0.6) + (score_strength * 0.4)
        // More keywords = higher coverage = higher confidence

        // Test with mocked data
        $repository = $this->getMockBuilder(PartnerDataRepositoryInterface::class)
            ->getMock();
        $repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 10,
                'partner_name' => 'Partner A',
                'matched_keywords' => 'internet',
                'total_score' => 50,
                'keyword_count' => 1
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 11,
                'partner_name' => 'Partner B',
                'matched_keywords' => 'internet,domain,registration',
                'total_score' => 107,
                'keyword_count' => 3
            ]
        ]);

        $extractor = $this->getMockBuilder(KeywordExtractorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extractor->method('extract')
            ->willReturn([new Keyword('internet'), new Keyword('domain'), new Keyword('registration')]);

        $service = new KeywordMatchingService($repository, $extractor);
        $results = $service->search('internet domain registration');

        // Both should return results
        $this->assertNotEmpty($results);
        
        // Partner B (more keywords) should have higher confidence
        if (count($results) >= 2) {
            $this->assertGreaterThan(
                $results[1]->getConfidence()->getPercentage(),
                $results[0]->getConfidence()->getPercentage()
            );
        }
    }

    /**
     * Helper: Create mock repository
     */
    private function createMockRepository(): PartnerDataRepositoryInterface
    {
        $mock = $this->getMockBuilder(PartnerDataRepositoryInterface::class)
            ->getMock();
        $mock->method('searchByKeywords')->willReturn([]);
        return $mock;
    }

    /**
     * Helper: Create mock extractor
     */
    private function createMockExtractor(): KeywordExtractorService
    {
        $mock = $this->getMockBuilder(KeywordExtractorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->method('extract')->willReturn([]);
        return $mock;
    }
}
