<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository;
use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/../Support/fa_db_stubs_repository.php';

/**
 * Unit tests for the database backed partner data repository.
 */
final class DatabasePartnerDataRepositoryTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var DatabasePartnerDataRepository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
        $this->repository = new DatabasePartnerDataRepository();
    }

    protected function tearDown(): void
    {
        FaDbFake::deactivate();

        parent::tearDown();
    }

    public function testFindReturnsTheStoredPartnerData(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'shoppers',
                'occurrence_count' => 4,
            ],
        ]);

        $found = $this->repository->find(1, 2, 3, 'shoppers');

        $this->assertInstanceOf(PartnerData::class, $found);
        $this->assertSame(1, $found->getPartnerId());
        $this->assertSame(2, $found->getPartnerType());
        $this->assertSame(3, $found->getPartnerDetailId());
        $this->assertSame('shoppers', $found->getData());
        $this->assertSame(4, $found->getOccurrenceCount());
    }

    public function testFindReturnsNullWhenNothingIsStored(): void
    {
        $this->fake->queue([]);

        $this->assertNull($this->repository->find(1, 2, 3, 'shoppers'));
    }

    public function testFindByPartnerReturnsEveryKeywordOfThePartner(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'shoppers',
                'occurrence_count' => 1,
            ],
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'drug',
                'occurrence_count' => 2,
            ],
        ]);

        $rows = $this->repository->findByPartner(1);

        $this->assertCount(2, $rows);
        $this->assertSame('shoppers', $rows[0]->getData());
        $this->assertStringContainsString('partner_id = 1', $this->fake->lastSql());
        $this->assertStringNotContainsString('partner_type', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testFindByPartnerCanBeRestrictedToAPartnerType(): void
    {
        $this->fake->queue([]);

        $this->repository->findByPartner(1, 2);

        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testFindByKeywordEscapesTheLikeWildcards(): void
    {
        $this->fake->queue([]);

        $this->repository->findByKeyword('50%_off');

        $this->assertStringContainsString("data LIKE '%50\\\\%\\\\_off%'", $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testFindByKeywordReturnsTheMatchingRows(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'shoppers',
                'occurrence_count' => 1,
            ],
        ]);

        $rows = $this->repository->findByKeyword('shoppers');

        $this->assertCount(1, $rows);
        $this->assertSame('shoppers', $rows[0]->getData());
    }

    public function testFindByKeywordCanBeRestrictedToAPartnerType(): void
    {
        $this->fake->queue([]);

        $this->repository->findByKeyword('shoppers', 2);

        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringContainsString("data LIKE '%shoppers%'", $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testSearchByKeywordsReturnsNothingForAnEmptyKeywordList(): void
    {
        $this->fake->queue([]);

        $this->assertSame([], $this->repository->searchByKeywords([]));
        $this->assertSame(0, $this->fake->queryCount());
    }

    public function testSearchByKeywordsGroupsTheHitsPerPartnerAndScoresThem(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'shoppers drug',
                'occurrence_count' => 5,
            ],
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'drug',
                'occurrence_count' => 2,
            ],
            [
                'partner_id' => 8,
                'partner_type' => 2,
                'partner_detail_id' => 0,
                'data' => 'drug',
                'occurrence_count' => 1,
            ],
        ]);

        $matches = $this->repository->searchByKeywords(['shoppers', 'drug'], null, 10);

        $this->assertCount(2, $matches);
        $this->assertSame(1, $matches[0]['partner_id']);
        $this->assertSame(['shoppers', 'drug'], $matches[0]['matched_keywords']);
        // 5 (row "shoppers drug" matches both keywords) + 5 + 2 (row "drug").
        $this->assertSame(12, $matches[0]['total_score']);
        $this->assertSame(8, $matches[1]['partner_id']);
        $this->assertSame(['drug'], $matches[1]['matched_keywords']);
        $this->assertSame(1, $matches[1]['total_score']);
    }

    public function testSearchByKeywordsAppliesTheLimit(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'drug',
                'occurrence_count' => 1,
            ],
            [
                'partner_id' => 8,
                'partner_type' => 2,
                'partner_detail_id' => 0,
                'data' => 'drug',
                'occurrence_count' => 1,
            ],
        ]);

        $matches = $this->repository->searchByKeywords(['drug'], null, 1);

        $this->assertCount(1, $matches);
    }

    public function testSearchByKeywordsCanBeRestrictedToAPartnerType(): void
    {
        $this->fake->queue([]);

        $this->repository->searchByKeywords(['drug'], 2, 5);

        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testSaveUpsertsTheKeyword(): void
    {
        $this->fake->queue([['affected' => 1]]);

        $partnerData = new PartnerData(1, 2, 3, 'shoppers', 5);

        $this->assertTrue($this->repository->save($partnerData));
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $this->fake->lastSql());
        $this->assertStringContainsString("VALUES (1, 2, 3, 'shoppers', 5)", $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testSaveReportsAFailure(): void
    {
        $this->fake->queue(false);

        $this->assertFalse($this->repository->save(new PartnerData(1, 2, 3, 'shoppers')));
    }

    public function testDeleteRemovesTheKeyword(): void
    {
        $this->fake->queue([['affected' => 1]]);

        $this->assertTrue($this->repository->delete(1, 2, 3, 'shoppers'));
        $this->assertStringContainsString('DELETE FROM', $this->fake->lastSql());
    }

    public function testDeleteReportsAFailure(): void
    {
        $this->fake->queue(false);

        $this->assertFalse($this->repository->delete(1, 2, 3, 'shoppers'));
    }

    public function testDeleteByPartnerReturnsOneWhenTheDeleteSucceeds(): void
    {
        $this->fake->queue([['affected' => 1]]);

        $this->assertSame(1, $this->repository->deleteByPartner(1));
    }

    public function testDeleteByPartnerReturnsZeroWhenTheDeleteFails(): void
    {
        $this->fake->queue(false);

        $this->assertSame(0, $this->repository->deleteByPartner(1));
    }

    public function testDeleteByPartnerCanBeRestrictedToAPartnerType(): void
    {
        $this->fake->queue([['affected' => 1]]);

        $this->assertSame(1, $this->repository->deleteByPartner(1, 2));
        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testIncrementOccurrenceUpsertsTheCounter(): void
    {
        $this->fake->queue([['affected' => 1]]);

        $this->assertTrue($this->repository->incrementOccurrence(1, 2, 3, 'shoppers', 4));
        $this->assertStringContainsString('occurrence_count = occurrence_count + 4', $this->fake->lastSql());
        $this->assertStringContainsString("VALUES (1, 2, 3, 'shoppers', 4)", $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testIncrementOccurrenceReportsAFailure(): void
    {
        $this->fake->queue(false);

        $this->assertFalse($this->repository->incrementOccurrence(1, 2, 3, 'shoppers', 4));
    }

    public function testCountReturnsTheNumberOfEntries(): void
    {
        $this->fake->queue([['total' => 12]]);

        $this->assertSame(12, $this->repository->count());
    }

    public function testCountCanBeRestrictedToAPartnerType(): void
    {
        $this->fake->queue([['total' => 3]]);

        $this->assertSame(3, $this->repository->count(2));
        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }

    public function testExistsUsesTheLookup(): void
    {
        $this->fake->queue([
            [
                'partner_id' => 1,
                'partner_type' => 2,
                'partner_detail_id' => 3,
                'data' => 'shoppers',
                'occurrence_count' => 1,
            ],
        ]);

        $this->assertTrue($this->repository->exists(1, 2, 3, 'shoppers'));
    }

    public function testExistsIsFalseWhenTheKeywordIsUnknown(): void
    {
        $this->fake->queue([]);

        $this->assertFalse($this->repository->exists(1, 2, 3, 'shoppers'));
    }

    public function testGetTopKeywordsReturnsTheMostFrequentKeywords(): void
    {
        $this->fake->queue([
            ['data' => 'shoppers', 'total_occurrences' => 12],
            ['data' => 'drug', 'total_occurrences' => 4],
        ]);

        $keywords = $this->repository->getTopKeywords(5, 2);

        $this->assertCount(2, $keywords);
        $this->assertSame('shoppers', $keywords[0]['data']);
        $this->assertSame(12, $keywords[0]['total_occurrences']);
        $this->assertStringContainsString('partner_type = 2', $this->fake->lastSql());
        $this->assertStringContainsString('LIMIT 5', $this->fake->lastSql());
        $this->assertStringNotContainsString('?', $this->fake->lastSql());
    }
}
