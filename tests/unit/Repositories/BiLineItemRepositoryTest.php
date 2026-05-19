<?php

namespace Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repositories\BiLineItemRepository;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;

class BiLineItemRepositoryTest extends TestCase
{
    private BiLineItemRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new BiLineItemRepository();
    }

    /**
     * Test that repository can find by ID
     */
    public function testFindByIdReturnsLineItem()
    {
        $lineItem = $this->repository->findById(1);

        $this->assertInstanceOf(BiLineItem::class, $lineItem);
        $this->assertEquals(1, $lineItem->getId());
    }

    /**
     * Test that findById throws exception for missing ID
     */
    public function testFindByIdThrowsExceptionWhenNotFound()
    {
        $this->expectException(RepositoryException::class);
        $this->repository->findById(99999);
    }

    /**
     * Test findAll returns collection
     */
    public function testFindAllReturnsCollection()
    {
        $collection = $this->repository->findAll();

        $this->assertGreaterThan(0, count($collection));
    }

    /**
     * Test count returns total number of items
     */
    public function testCountReturnsTotalItems()
    {
        $count = $this->repository->count();

        $this->assertGreaterThan(0, $count);
        $this->assertEquals(count($this->repository->findAll()), $count);
    }

    /**
     * Test findMatched returns only matched items
     */
    public function testFindMatchedReturnsOnlyMatched()
    {
        $matched = $this->repository->findMatched();

        foreach ($matched as $item) {
            $this->assertTrue($item->isMatched());
        }
    }

    /**
     * Test findUnmatched returns only unmatched items
     */
    public function testFindUnmatchedReturnsOnlyUnmatched()
    {
        $unmatched = $this->repository->findUnmatched();

        foreach ($unmatched as $item) {
            $this->assertFalse($item->isMatched());
        }
    }

    /**
     * Test findByAmountRange filters by amount
     */
    public function testFindByAmountRangeFiltersCorrectly()
    {
        $range = $this->repository->findByAmountRange(100, 500);

        foreach ($range as $item) {
            $this->assertGreaterThanOrEqual(100, $item->getAmount());
            $this->assertLessThanOrEqual(500, $item->getAmount());
        }
    }

    /**
     * Test save creates or updates line item
     */
    public function testSaveStoresLineItem()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);
        $this->repository->save($lineItem);

        // Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test delete removes line item
     */
    public function testDeleteRemovesLineItem()
    {
        $this->repository->delete(1);

        // Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test findBy filters by criteria
     */
    public function testFindByFiltersByCriteria()
    {
        $criteria = ['status' => 0];
        $collection = $this->repository->findBy($criteria);

        foreach ($collection as $item) {
            $this->assertEquals(0, $item->getStatus());
        }
    }

    /**
     * Test getSummaryStats returns statistics array
     */
    public function testGetSummaryStatsReturnsArray()
    {
        $stats = $this->repository->getSummaryStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('matched_count', $stats);
        $this->assertArrayHasKey('unmatched_count', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
    }

    /**
     * Test getMatchStats returns match statistics
     */
    public function testGetMatchStatsReturnsArray()
    {
        $stats = $this->repository->getMatchStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('matched', $stats);
        $this->assertArrayHasKey('unmatched', $stats);
        $this->assertArrayHasKey('match_percentage', $stats);
    }
}
