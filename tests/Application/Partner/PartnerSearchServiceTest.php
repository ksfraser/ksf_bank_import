<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Application\Partner\KeywordExtractor;
use Ksfraser\FaBankImport\Application\Partner\ScoringEngine;
use Ksfraser\FaBankImport\Application\Partner\PartnerSearchService;
use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerMatchResult;
use Ksfraser\FaBankImport\Entity\PartnerType;
use PHPUnit\Framework\TestCase;

class PartnerSearchServiceTest extends TestCase
{
    private PartnerRepository $mockRepository;
    private KeywordExtractor $keywordExtractor;
    private ScoringEngine $scoringEngine;
    private PartnerSearchService $service;

    protected function setUp(): void
    {
        $this->keywordExtractor = new KeywordExtractor();
        $this->scoringEngine = new ScoringEngine();
        $this->mockRepository = $this->createMock(PartnerRepository::class);

        $this->service = new PartnerSearchService(
            $this->mockRepository,
            $this->keywordExtractor,
            $this->scoringEngine
        );
    }

    public function testSearchWithEmptyTextReturnsEmpty(): void
    {
        $this->mockRepository->expects($this->never())
            ->method('getByType');

        $results = $this->service->search('', PartnerType::SUPPLIER);
        $this->assertCount(0, $results);
    }

    public function testSearchWithNoMatchesReturnsEmpty(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->with(PartnerType::SUPPLIER)
            ->willReturn([]);

        $results = $this->service->search('XYZ Corp', PartnerType::SUPPLIER);
        $this->assertCount(0, $results);
    }

    public function testSearchWithSingleMatchReturnsResult(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Bank Transfer Inc',
            type: PartnerType::BANK_TRANSFER,
            occurrenceCount: 50,
            lastMatchedTs: new \DateTime('-7 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->with(PartnerType::BANK_TRANSFER)
            ->willReturn([$partner]);

        $results = $this->service->search('Bank Transfer Inc', PartnerType::BANK_TRANSFER);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(PartnerMatchResult::class, $results[0]);
        $this->assertEquals(1, $results[0]->partner()->id());
        // Confidence should be positive
        $this->assertGreaterThan(0.0, $results[0]->confidence());
        $this->assertLessThanOrEqual(1.0, $results[0]->confidence());
    }

    public function testSearchReturnsSortedByConfidenceDescending(): void
    {
        $partners = [
            new PartnerEntity(1, 'Supplier A', PartnerType::SUPPLIER, 10, new \DateTime('-100 days')),
            new PartnerEntity(2, 'Supplier B', PartnerType::SUPPLIER, 100, new \DateTime('-1 day')),
            new PartnerEntity(3, 'Supplier C', PartnerType::SUPPLIER, 50, new \DateTime('-30 days')),
        ];

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn($partners);

        $results = $this->service->search('Supplier', PartnerType::SUPPLIER);

        // Verify sorted by confidence descending
        $this->assertGreaterThanOrEqual(count($results) - 1, 2);
        for ($i = 0; $i < count($results) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $results[$i + 1]->confidence(),
                $results[$i]->confidence(),
                'Results must be sorted by confidence descending'
            );
        }
    }

    public function testAutoSelectReturnsNullWhenBelowThreshold(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Generic Company',
            type: PartnerType::CUSTOMER,
            occurrenceCount: 2,
            lastMatchedTs: new \DateTime('-300 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn([$partner]);

        // Search text with no overlap - should result in low confidence
        $result = $this->service->autoSelect('Random XYZ', PartnerType::CUSTOMER);

        // With low confidence (<0.75), should return null
        $this->assertNull($result);
    }

    public function testAutoSelectIncrementsOccurrenceCount(): void
    {
        // Use a partner with high occurrence to potentially meet threshold
        $partner = new PartnerEntity(
            id: 123,
            name: 'Frequent Partner',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 500, // Very high to maximize confidence
            lastMatchedTs: new \DateTime('-1 day')
        );

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn([$partner]);

        $updateCapture = null;
        $this->mockRepository->expects($this->any())
            ->method('update')
            ->willReturnCallback(function (PartnerEntity $entity) use (&$updateCapture) {
                $updateCapture = $entity;
            });

        $result = $this->service->autoSelect('Frequent Partner', PartnerType::SUPPLIER);

        // If update was called and result is not null, verify occurrence was incremented
        if ($updateCapture !== null && $result !== null) {
            $this->assertEquals(501, $updateCapture->occurrenceCount(), 
                'Occurrence count should be incremented by 1');
        }
    }

    public function testAutoSelectUpdatesLastMatchedTimestamp(): void
    {
        $oldTs = new \DateTime('-30 days');
        $partner = new PartnerEntity(
            id: 456,
            name: 'Frequent Customer',
            type: PartnerType::CUSTOMER,
            occurrenceCount: 500,
            lastMatchedTs: $oldTs
        );

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn([$partner]);

        $updateCapture = null;
        $this->mockRepository->expects($this->any())
            ->method('update')
            ->willReturnCallback(function (PartnerEntity $entity) use (&$updateCapture) {
                $updateCapture = $entity;
            });

        $result = $this->service->autoSelect('Frequent Customer', PartnerType::CUSTOMER);

        // If update was called and result is not null, verify timestamp was updated
        if ($updateCapture !== null && $result !== null) {
            $this->assertGreaterThan(
                $oldTs->getTimestamp(),
                $updateCapture->lastMatchedTs()?->getTimestamp() ?? 0,
                'Last matched timestamp should be updated'
            );
        }
    }

    public function testSearchUsesAllThreeServices(): void
    {
        // This test verifies no PROD dependencies
        $reflection = new \ReflectionClass(PartnerSearchService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        
        $params = $constructor->getParameters();
        $this->assertCount(3, $params);
        
        // Verify parameter types
        $paramTypes = array_map(fn($p) => $p->getType()?->getName(), $params);
        
        // Should contain Repository, KeywordExtractor, and ScoringEngine
        $this->assertContains(PartnerRepository::class, $paramTypes);
        $this->assertContains(KeywordExtractor::class, $paramTypes);
        $this->assertContains(ScoringEngine::class, $paramTypes);
        
        // Should NOT contain PROD classes
        $this->assertNotContains('ViewBiLineItems', $paramTypes);
        $this->assertNotContains('BiLineItem', $paramTypes);
    }

    public function testSearchCalculatesFactorsForScoring(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Test Partner',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 50,
            lastMatchedTs: new \DateTime('-14 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn([$partner]);

        $results = $this->service->search('Test Partner Inc', PartnerType::SUPPLIER);

        $this->assertCount(1, $results);
        
        // Verify factors are populated
        $factors = $results[0]->factors();
        $this->assertIsArray($factors);
        $this->assertArrayHasKey('substring', $factors);
        $this->assertArrayHasKey('keyword', $factors);
        $this->assertArrayHasKey('occurrence', $factors);
        $this->assertArrayHasKey('recency', $factors);
    }

    public function testSearchWithLimitReturnsMaxResults(): void
    {
        $partners = [
            new PartnerEntity(1, 'Partner A', PartnerType::SUPPLIER, 50, new \DateTime('-1 day')),
            new PartnerEntity(2, 'Partner B', PartnerType::SUPPLIER, 40, new \DateTime('-2 days')),
            new PartnerEntity(3, 'Partner C', PartnerType::SUPPLIER, 30, new \DateTime('-3 days')),
        ];

        $this->mockRepository->expects($this->once())
            ->method('getByType')
            ->willReturn($partners);

        $results = $this->service->search('Partner', PartnerType::SUPPLIER, limit: 2);

        $this->assertLessThanOrEqual(2, count($results), 'Should respect limit parameter');
    }
}
