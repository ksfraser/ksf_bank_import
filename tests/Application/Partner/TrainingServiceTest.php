<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Application\Partner\TrainingService;
use Ksfraser\FaBankImport\Application\Partner\PartnerDataServiceInterface;
use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;
use PHPUnit\Framework\TestCase;

class TrainingServiceTest extends TestCase
{
    private PartnerRepository $mockRepository;
    private PartnerDataServiceInterface $mockDataService;
    private TrainingService $service;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(PartnerRepository::class);
        $this->mockDataService = $this->createMock(PartnerDataServiceInterface::class);

        $this->service = new TrainingService(
            $this->mockRepository,
            $this->mockDataService
        );
    }

    /**
     * Test 1: buildTrainingData returns statistics array
     */
    public function testBuildTrainingDataReturnsStatistics(): void
    {
        // Mock getByType to return empty arrays for all types
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturn([]);

        $stats = $this->service->buildTrainingData(dryRun: true);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('processed', $stats);
        $this->assertArrayHasKey('learned', $stats);
        $this->assertArrayHasKey('skipped', $stats);
    }

    /**
     * Test 2: Dry run doesn't modify database
     */
    public function testDryRunDoesNotModifyDatabase(): void
    {
        $partner = new PartnerEntity(
            1,
            'Known Partner',
            PartnerType::SUPPLIER,
            50,
            new \DateTime('-10 days')
        );

        // Mock getByType to return partner only for SUPPLIER type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partner],  // SUPPLIER
                [],          // CUSTOMER
                [],          // BANK_TRANSFER
                []           // QUICK_ENTRY
            );

        // searchByPattern returns candidates
        $this->mockRepository->expects($this->once())
            ->method('searchByPattern')
            ->willReturn([$partner]);

        // In dry run, should not call update
        $this->mockDataService->expects($this->never())
            ->method('updateOccurrenceCount');
        $this->mockDataService->expects($this->never())
            ->method('updateLastMatchedTimestamp');

        $stats = $this->service->buildTrainingData(dryRun: true);

        $this->assertGreaterThanOrEqual(0, $stats['processed']);
    }

    /**
     * Test 3: Training data processes all partners
     */
    public function testTrainingDataProcessesAllPartners(): void
    {
        $partners = [
            new PartnerEntity(1, 'Partner A', PartnerType::SUPPLIER, 10, null),
            new PartnerEntity(2, 'Partner B', PartnerType::CUSTOMER, 20, null),
            new PartnerEntity(3, 'Partner C', PartnerType::SUPPLIER, 30, null),
        ];

        // Mock getByType to return partners by type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partners[0], $partners[2]],  // SUPPLIER
                [$partners[1]],                // CUSTOMER
                [],                            // BANK_TRANSFER
                []                             // QUICK_ENTRY
            );

        // Mock searchByPattern to return one candidate each time
        $this->mockRepository->expects($this->exactly(3))
            ->method('searchByPattern')
            ->willReturnOnConsecutiveCalls([$partners[0]], [$partners[1]], [$partners[2]]);

        $stats = $this->service->buildTrainingData(dryRun: true);

        // Should process all 3 partners
        $this->assertEquals(3, $stats['processed']);
    }

    /**
     * Test 4: Auto-select increments occurrence count
     */
    public function testAutoSelectIncrementsOccurrenceCount(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Frequent Partner',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 100,
            lastMatchedTs: new \DateTime('-5 days')
        );

        // Mock getByType to return partner only for SUPPLIER type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partner],  // SUPPLIER
                [],          // CUSTOMER
                [],          // BANK_TRANSFER
                []           // QUICK_ENTRY
            );

        // searchByPattern returns the partner
        $this->mockRepository->expects($this->once())
            ->method('searchByPattern')
            ->willReturn([$partner]);

        // When not dry run, should call update
        $this->mockDataService->expects($this->once())
            ->method('updateOccurrenceCount')
            ->with(1, PartnerType::SUPPLIER);

        $this->mockDataService->expects($this->once())
            ->method('updateLastMatchedTimestamp')
            ->with(1, PartnerType::SUPPLIER);

        $stats = $this->service->buildTrainingData(dryRun: false);

        $this->assertEquals(1, $stats['learned']);
    }

    /**
     * Test 5: Training counts learned vs skipped correctly
     */
    public function testTrainingCountsLearnedVsSkipped(): void
    {
        // Partner 1: Will match (learned)
        $partner1 = new PartnerEntity(
            1,
            'Matchable Partner',
            PartnerType::SUPPLIER,
            100,
            new \DateTime('-1 day')
        );

        // Partner 2: Won't match (skipped)
        $partner2 = new PartnerEntity(
            2,
            'Unmatchable X',
            PartnerType::CUSTOMER,
            5,
            new \DateTime('-300 days')
        );

        // Mock getByType to return partners by type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partner1],  // SUPPLIER
                [$partner2],  // CUSTOMER
                [],           // BANK_TRANSFER
                []            // QUICK_ENTRY
            );

        // First search returns match, second doesn't
        $this->mockRepository->expects($this->exactly(2))
            ->method('searchByPattern')
            ->willReturnOnConsecutiveCalls([$partner1], []);

        $this->mockDataService->expects($this->once())
            ->method('updateOccurrenceCount');

        $this->mockDataService->expects($this->once())
            ->method('updateLastMatchedTimestamp');

        $stats = $this->service->buildTrainingData(dryRun: false);

        // One should be learned, one skipped
        $this->assertEquals(1, $stats['learned']);
        $this->assertEquals(1, $stats['skipped']);
    }

    /**
     * Test 6: Statistics show accurate counts
     */
    public function testStatisticsShowAccurateCounts(): void
    {
        $partners = [
            new PartnerEntity(1, 'P1', PartnerType::SUPPLIER, 50, new \DateTime()),
            new PartnerEntity(2, 'P2', PartnerType::CUSTOMER, 50, new \DateTime()),
        ];

        // Mock getByType to return partners by type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partners[0]],  // SUPPLIER
                [$partners[1]],  // CUSTOMER
                [],              // BANK_TRANSFER
                []               // QUICK_ENTRY
            );

        // Both match
        $this->mockRepository->expects($this->exactly(2))
            ->method('searchByPattern')
            ->willReturnOnConsecutiveCalls([$partners[0]], [$partners[1]]);

        $this->mockDataService->expects($this->exactly(2))
            ->method('updateOccurrenceCount');

        $this->mockDataService->expects($this->exactly(2))
            ->method('updateLastMatchedTimestamp');

        $stats = $this->service->buildTrainingData(dryRun: false);

        // Verify counts match expectation
        $this->assertEquals(2, $stats['processed']);
        $this->assertEquals(2, $stats['learned']);
    }

    /**
     * Test 7: Empty partner list results in zero stats
     */
    public function testEmptyPartnerListResultsInZeroStats(): void
    {
        // Mock getByType to return empty arrays for all types
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturn([]);

        $stats = $this->service->buildTrainingData(dryRun: true);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['learned']);
        $this->assertEquals(0, $stats['skipped']);
    }

    /**
     * Test 8: Training service respects dry run flag
     */
    public function testTrainingServiceRespectsDryRunFlag(): void
    {
        $partner = new PartnerEntity(1, 'P1', PartnerType::SUPPLIER, 50, null);

        // Mock getByType to return partner only for SUPPLIER type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partner],  // SUPPLIER
                [],          // CUSTOMER
                [],          // BANK_TRANSFER
                []           // QUICK_ENTRY
            );

        // searchByPattern returns match
        $this->mockRepository->expects($this->any())
            ->method('searchByPattern')
            ->willReturn([$partner]);

        // In dry run, should NOT call update methods
        $this->mockDataService->expects($this->never())
            ->method('updateOccurrenceCount');

        // Build training data in dry run mode
        $stats = $this->service->buildTrainingData(dryRun: true);

        $this->assertEquals(1, $stats['processed']);
    }

    /**
     * Test 9: Service has required dependencies
     */
    public function testServiceHasRequiredDependencies(): void
    {
        $reflection = new \ReflectionClass(TrainingService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);

        $paramTypes = array_map(fn($p) => $p->getType()?->getName(), $params);
        $this->assertContains(PartnerRepository::class, $paramTypes);
        $this->assertContains(PartnerDataServiceInterface::class, $paramTypes);
    }

    /**
     * Test 10: Training handles all partner types
     */
    public function testTrainingHandlesAllPartnerTypes(): void
    {
        $partners = [
            new PartnerEntity(1, 'Supplier', PartnerType::SUPPLIER, 50, null),
            new PartnerEntity(2, 'Customer', PartnerType::CUSTOMER, 50, null),
            new PartnerEntity(3, 'Bank Transfer', PartnerType::BANK_TRANSFER, 50, null),
            new PartnerEntity(4, 'Quick Entry', PartnerType::QUICK_ENTRY, 50, null),
        ];

        // Mock getByType to return one partner for each type
        $this->mockRepository->expects($this->exactly(4))
            ->method('getByType')
            ->willReturnOnConsecutiveCalls(
                [$partners[0]],
                [$partners[1]],
                [$partners[2]],
                [$partners[3]]
            );

        // Return all as matches
        $this->mockRepository->expects($this->exactly(4))
            ->method('searchByPattern')
            ->willReturnOnConsecutiveCalls(
                [$partners[0]],
                [$partners[1]],
                [$partners[2]],
                [$partners[3]]
            );

        $stats = $this->service->buildTrainingData(dryRun: true);

        // Should process all 4 types
        $this->assertEquals(4, $stats['processed']);
    }
}
