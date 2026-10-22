<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\PartnerDataService;
use Ksfraser\FaBankImport\Services\KeywordExtractorService;
use Ksfraser\FaBankImport\Repository\PartnerDataRepositoryInterface;
use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;

/**
 * Behavioural tests for the partner data service.
 */
final class PartnerDataServiceCoverageTest extends TestCase
{
    /** @var PartnerDataRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var PartnerDataService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PartnerDataRepositoryInterface::class);
        $this->service = new PartnerDataService($this->repository, new KeywordExtractorService());
    }

    public function testSaveDelegatesToTheRepository(): void
    {
        $partnerData = new PartnerData(1, 2, 3, 'shoppers', 4);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($partnerData)
            ->willReturn(true);

        $this->assertTrue($this->service->save($partnerData));
    }

    public function testSaveKeywordStoresTheKeywordForThePartner(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (PartnerData $data): bool {
                return $data->getPartnerId() === 1
                    && $data->getPartnerType() === 2
                    && $data->getPartnerDetailId() === 3
                    && $data->getData() === 'shoppers'
                    && $data->getOccurrenceCount() === 5;
            }))
            ->willReturn(true);

        $this->assertTrue($this->service->saveKeyword(1, 2, 3, 'shoppers', 5));
    }

    public function testSaveKeywordRejectsAnInvalidKeyword(): void
    {
        $this->repository->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid keyword: the');

        $this->service->saveKeyword(1, 2, 3, 'the');
    }

    public function testSaveKeywordsFromTextSavesEveryExtractedKeyword(): void
    {
        $this->repository->method('save')->willReturn(true);

        $saved = $this->service->saveKeywordsFromText(1, 2, 3, 'shoppers drug mart');

        $this->assertSame(3, $saved);
    }

    public function testSaveKeywordsFromTextSkipsKeywordsTheRepositoryRejects(): void
    {
        $this->repository->method('save')->willReturn(false);

        $this->assertSame(0, $this->service->saveKeywordsFromText(1, 2, 3, 'shoppers drug'));
    }

    public function testIncrementKeywordOccurrenceDelegatesToTheRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('incrementOccurrence')
            ->with(1, 2, 3, 'shoppers', 4)
            ->willReturn(true);

        $this->assertTrue($this->service->incrementKeywordOccurrence(1, 2, 3, 'shoppers', 4));
    }

    public function testFindReturnsTheStoredPartnerData(): void
    {
        $partnerData = new PartnerData(1, 2, 3, 'shoppers');

        $this->repository->method('find')->with(1, 2, 3, 'shoppers')->willReturn($partnerData);

        $this->assertSame($partnerData, $this->service->find(1, 2, 3, 'shoppers'));
    }

    public function testGetPartnerKeywordsDelegatesToTheRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findByPartner')
            ->with(1, 2)
            ->willReturn([new PartnerData(1, 2, 3, 'shoppers')]);

        $this->assertCount(1, $this->service->getPartnerKeywords(1, 2));
    }

    public function testDeleteDelegatesToTheRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1, 2, 3, 'shoppers')
            ->willReturn(true);

        $this->assertTrue($this->service->delete(1, 2, 3, 'shoppers'));
    }

    public function testDeletePartnerKeywordsReturnsTheNumberOfDeletedEntries(): void
    {
        $this->repository->expects($this->once())
            ->method('deleteByPartner')
            ->with(1, null)
            ->willReturn(4);

        $this->assertSame(4, $this->service->deletePartnerKeywords(1));
    }

    public function testExistsDelegatesToTheRepository(): void
    {
        $this->repository->method('exists')->with(1, 2, 3, 'shoppers')->willReturn(true);

        $this->assertTrue($this->service->exists(1, 2, 3, 'shoppers'));
    }

    public function testCountDelegatesToTheRepository(): void
    {
        $this->repository->method('count')->with(2)->willReturn(17);

        $this->assertSame(17, $this->service->count(2));
    }

    public function testGetTopKeywordsDelegatesToTheRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getTopKeywords')
            ->with(10, 2)
            ->willReturn([['data' => 'shoppers', 'total_occurrences' => 9]]);

        $top = $this->service->getTopKeywords(10, 2);

        $this->assertSame('shoppers', $top[0]['data']);
    }

    public function testRebuildPartnerKeywordsIsNotImplemented(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('rebuildPartnerKeywords() not yet implemented');

        $this->service->rebuildPartnerKeywords(1);
    }
}
