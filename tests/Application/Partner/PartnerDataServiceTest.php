<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Application\Partner;

use Ksfraser\FaBankImport\Application\Partner\PartnerDataService;
use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;
use PHPUnit\Framework\TestCase;

class PartnerDataServiceTest extends TestCase
{
    private PartnerRepository $mockRepository;
    private PartnerDataService $service;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(PartnerRepository::class);
        $this->service = new PartnerDataService($this->mockRepository);
    }

    /**
     * Test 1: Set partner data creates new partner if doesn't exist
     */
    public function testSetPartnerDataCreatesNewPartner(): void
    {
        // When partnerId is 0, service creates without calling getById
        $this->mockRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (PartnerEntity $entity) {
                return $entity->name() === 'New Supplier Inc' 
                    && $entity->type() === PartnerType::SUPPLIER;
            }))
            ->willReturn(123);

        $this->service->setPartnerData(0, PartnerType::SUPPLIER, 'New Supplier Inc');
    }

    /**
     * Test 2: Set partner data throws InvalidArgumentException for empty data
     */
    public function testSetPartnerDataThrowsOnEmptyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner name cannot be empty');

        $this->service->setPartnerData(0, PartnerType::CUSTOMER, '');
    }

    /**
     * Test 3: Set partner data returns early if unchanged
     */
    public function testSetPartnerDataNoOpIfUnchanged(): void
    {
        $existing = new PartnerEntity(
            id: 1,
            name: 'Existing Partner',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 50,
            lastMatchedTs: new \DateTime('-7 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($existing);

        // Should NOT call create or update
        $this->mockRepository->expects($this->never())
            ->method('create');
        $this->mockRepository->expects($this->never())
            ->method('update');

        $this->service->setPartnerData(1, PartnerType::SUPPLIER, 'Existing Partner');
    }

    /**
     * Test 4: Set partner data updates existing partner if changed
     */
    public function testSetPartnerDataUpdatesExistingPartner(): void
    {
        $existing = new PartnerEntity(
            id: 2,
            name: 'Old Name',
            type: PartnerType::CUSTOMER,
            occurrenceCount: 30,
            lastMatchedTs: new \DateTime('-14 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(2)
            ->willReturn($existing);

        $this->mockRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (PartnerEntity $entity) {
                return $entity->id() === 2 
                    && $entity->name() === 'New Name'
                    && $entity->occurrenceCount() === 30; // Keep original count
            }));

        $this->service->setPartnerData(2, PartnerType::CUSTOMER, 'New Name');
    }

    /**
     * Test 5: Append partner data concatenates with newline
     */
    public function testAppendPartnerDataConcatenatesWithNewline(): void
    {
        $existing = new PartnerEntity(
            id: 3,
            name: 'Line 1',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 10,
            lastMatchedTs: null
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(3)
            ->willReturn($existing);

        $this->mockRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (PartnerEntity $entity) {
                return $entity->name() === "Line 1\nLine 2";
            }));

        $this->service->appendPartnerData(3, PartnerType::SUPPLIER, 'Line 2');
    }

    /**
     * Test 6: Append partner data throws when exceeding max length
     */
    public function testAppendPartnerDataThrowsOnMaxLength(): void
    {
        // Create a partner with data very close to max length
        $longName = str_repeat('x', 1950); // Already 1950 chars
        $existing = new PartnerEntity(
            id: 4,
            name: $longName,
            type: PartnerType::CUSTOMER,
            occurrenceCount: 5,
            lastMatchedTs: null
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(4)
            ->willReturn($existing);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Partner data exceeds maximum');

        // Adding 100+ chars would exceed 2000 limit
        $this->service->appendPartnerData(4, PartnerType::CUSTOMER, str_repeat('y', 100));
    }

    /**
     * Test 7: Get partner data retrieves existing partner
     */
    public function testGetPartnerDataRetrievesExisting(): void
    {
        $expected = new PartnerEntity(
            id: 5,
            name: 'Test Partner',
            type: PartnerType::BANK_TRANSFER,
            occurrenceCount: 25,
            lastMatchedTs: new \DateTime('-30 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(5)
            ->willReturn($expected);

        $result = $this->service->getPartnerData(5, PartnerType::BANK_TRANSFER);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->id());
        $this->assertEquals('Test Partner', $result->name());
    }

    /**
     * Test 8: Get partner data returns null for non-existent
     */
    public function testGetPartnerDataReturnsNullForNonExistent(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getPartnerData(999, PartnerType::SUPPLIER);

        $this->assertNull($result);
    }

    /**
     * Test 9: Delete partner removes from repository
     */
    public function testDeletePartnerRemovesFromRepository(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('delete')
            ->with(6)
            ->willReturn(true);

        $result = $this->service->deletePartnerData(6);

        $this->assertTrue($result);
    }

    /**
     * Test 10: Update occurrence count increments value
     */
    public function testUpdateOccurrenceCountIncrementsValue(): void
    {
        $existing = new PartnerEntity(
            id: 7,
            name: 'Frequent Partner',
            type: PartnerType::CUSTOMER,
            occurrenceCount: 100,
            lastMatchedTs: new \DateTime('-1 day')
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(7)
            ->willReturn($existing);

        $this->mockRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (PartnerEntity $entity) {
                return $entity->occurrenceCount() === 101;
            }));

        $this->service->updateOccurrenceCount(7, PartnerType::CUSTOMER);
    }

    /**
     * Test 11: Update last matched timestamp sets current time
     */
    public function testUpdateLastMatchedTimestampSetsCurrent(): void
    {
        $beforeTime = new \DateTime();
        
        $existing = new PartnerEntity(
            id: 8,
            name: 'Recently Matched',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 50,
            lastMatchedTs: new \DateTime('-365 days')
        );

        $this->mockRepository->expects($this->once())
            ->method('getById')
            ->with(8)
            ->willReturn($existing);

        $this->mockRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (PartnerEntity $entity) use ($beforeTime) {
                $ts = $entity->lastMatchedTs();
                return $ts !== null && $ts->getTimestamp() >= $beforeTime->getTimestamp();
            }));

        $this->service->updateLastMatchedTimestamp(8, PartnerType::SUPPLIER);
    }

    /**
     * Test 12: Service has repository dependency only
     */
    public function testServiceHasRepositoryDependencyOnly(): void
    {
        $reflection = new \ReflectionClass(PartnerDataService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        
        $paramType = $params[0]->getType()?->getName();
        $this->assertEquals(PartnerRepository::class, $paramType);
    }
}
