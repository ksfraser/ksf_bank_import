<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\KeywordMatchingService;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Config\ConfigService;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;

/**
 * Behavioural tests for the keyword matching service.
 */
final class KeywordMatchingServiceCoverageTest extends TestCase
{
    /** @var PartnerDataRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PartnerDataRepositoryInterface::class);
    }

    public function testSearchReturnsNothingWhenTheSearchTextHasNoKeywords(): void
    {
        $this->repository->expects($this->never())->method('searchByKeywords');

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertSame([], $service->search('the and or'));
    }

    public function testSearchReturnsNothingWhenTheRepositoryFindsNoPartner(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertSame([], $service->search('shoppers drug'));
    }

    public function testSearchBuildsAMatchForEveryRepositoryHit(): void
    {
        $this->repository->expects($this->once())
            ->method('searchByKeywords')
            ->with(['shoppers', 'drug'], null, 10)
            ->willReturn([
                [
                    'partner_id' => 7,
                    'partner_type' => 1,
                    'partner_detail_id' => 0,
                    'partner_name' => 'Shoppers Drug Mart',
                    'matched_keywords' => 'shoppers,drug',
                    'total_score' => 6,
                    'keyword_count' => 2,
                ],
            ]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());
        $matches = $service->search('shoppers drug');

        $this->assertCount(1, $matches);
        $this->assertSame(7, $matches[0]->getPartnerId());
        $this->assertSame('Shoppers Drug Mart', $matches[0]->getPartnerName());
        $this->assertSame(2, $matches[0]->getMatchedKeywordCount());
        $this->assertSame(6, $matches[0]->getRawScore());
        $this->assertEqualsWithDelta(7.2, $matches[0]->getFinalScore(), 1e-9);
        $this->assertGreaterThanOrEqual(30.0, $matches[0]->getConfidencePercentage());
    }

    public function testSearchFallsBackToAPartnerLabelWhenNoNameIsAvailable(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 3,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'matched_keywords' => 'shoppers',
                'total_score' => 4,
                'keyword_count' => 1,
            ],
        ]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());
        $matches = $service->search('shoppers drug');

        $this->assertCount(1, $matches);
        $this->assertSame('Partner #3', $matches[0]->getPartnerName());
    }

    public function testSearchSortsByMatchedKeywordCountAndThenByScore(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'One keyword',
                'matched_keywords' => 'shoppers',
                'total_score' => 20,
                'keyword_count' => 1,
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'Two keywords',
                'matched_keywords' => 'shoppers,drug',
                'total_score' => 3,
                'keyword_count' => 2,
            ],
        ]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());
        $matches = $service->search('shoppers drug');

        $this->assertCount(2, $matches);
        $this->assertSame(2, $matches[0]->getPartnerId());
        $this->assertSame(1, $matches[1]->getPartnerId());
    }

    public function testSearchAppliesTheResultLimit(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'One',
                'matched_keywords' => 'shoppers',
                'total_score' => 20,
                'keyword_count' => 1,
            ],
            [
                'partner_id' => 2,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'Two',
                'matched_keywords' => 'drug',
                'total_score' => 20,
                'keyword_count' => 1,
            ],
        ]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());
        $matches = $service->search('shoppers drug', null, 1);

        $this->assertCount(1, $matches);
    }

    public function testSearchDropsMatchesBelowTheConfidenceThreshold(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 1,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'One',
                'matched_keywords' => 'shoppers',
                'total_score' => 20,
                'keyword_count' => 1,
            ],
        ]);

        $service = new KeywordMatchingService(
            $this->repository,
            new KeywordExtractorService(),
            $this->configService(['pattern_matching.min_confidence_threshold' => 99.0])
        );

        $this->assertSame([], $service->search('shoppers drug'));
    }

    public function testGetTopMatchReturnsTheBestMatch(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([
            [
                'partner_id' => 9,
                'partner_type' => 1,
                'partner_detail_id' => 0,
                'partner_name' => 'Nine',
                'matched_keywords' => 'shoppers,drug',
                'total_score' => 8,
                'keyword_count' => 2,
            ],
        ]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());
        $match = $service->getTopMatch('shoppers drug');

        $this->assertNotNull($match);
        $this->assertSame(9, $match->getPartnerId());
    }

    public function testGetTopMatchReturnsNullWhenNothingMatches(): void
    {
        $this->repository->method('searchByKeywords')->willReturn([]);

        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertNull($service->getTopMatch('shoppers drug'));
    }

    public function testCalculateScoreAppliesTheClusteringBonus(): void
    {
        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertEqualsWithDelta(10.0, $service->calculateScore(10, 1), 1e-9);
        $this->assertEqualsWithDelta(12.0, $service->calculateScore(10, 2), 1e-9);
        $this->assertEqualsWithDelta(14.0, $service->calculateScore(10, 3), 1e-9);
    }

    public function testCalculateScoreReturnsZeroForEmptyInput(): void
    {
        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertSame(0.0, $service->calculateScore(0, 3));
        $this->assertSame(0.0, $service->calculateScore(10, 0));
    }

    public function testConfigurationIsReadFromTheConfigurationService(): void
    {
        $service = new KeywordMatchingService(
            $this->repository,
            new KeywordExtractorService(),
            $this->configService([
                'pattern_matching.keyword_clustering_factor' => 0.5,
                'pattern_matching.min_confidence_threshold' => 80.0,
                'pattern_matching.max_suggestions' => 9,
            ])
        );

        $this->assertSame(0.5, $service->getClusteringFactor());
        $this->assertSame(80.0, $service->getMinConfidenceThreshold());
        $this->assertSame(9, $service->getMaxSuggestions());
    }

    public function testDefaultConfigurationIsUsedWithoutAConfigurationService(): void
    {
        $service = new KeywordMatchingService($this->repository, new KeywordExtractorService());

        $this->assertSame(0.2, $service->getClusteringFactor());
        $this->assertSame(30.0, $service->getMinConfidenceThreshold());
        $this->assertSame(5, $service->getMaxSuggestions());
    }

    /**
     * @param array<string,mixed> $values
     */
    private function configService(array $values): ConfigService
    {
        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository->method('get')->willReturnCallback(
            function ($key, $default = null) use ($values) {
                return array_key_exists($key, $values) ? $values[$key] : $default;
            }
        );

        return ConfigService::create($repository);
    }
}
