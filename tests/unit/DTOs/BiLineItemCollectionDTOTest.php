<?php

namespace Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO;
use Ksfraser\FaBankImport\DTOs\BiLineItemDTO;

class BiLineItemCollectionDTOTest extends TestCase
{
    /**
     * Helper to create sample BiLineItemDTO instances
     */
    private function createSampleDTO(int $id, float $amount, bool $matched = false, string $partnerType = null): BiLineItemDTO
    {
        return BiLineItemDTO::fromArray([
            'id' => $id,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => $amount,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => $partnerType,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => $matched,
            'created' => false,
            'formData' => null,
        ]);
    }

    /**
     * Test that collection can be created and implements Countable
     */
    public function testCollectionImplementsCountable()
    {
        $collection = new BiLineItemCollectionDTO();
        $this->assertEquals(0, count($collection));

        $collection->add($this->createSampleDTO(1, 100.00));
        $this->assertEquals(1, count($collection));

        $collection->add($this->createSampleDTO(2, 200.00));
        $this->assertEquals(2, count($collection));
    }

    /**
     * Test that collection implements IteratorAggregate
     */
    public function testCollectionImplementsIteratorAggregate()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00));
        $collection->add($this->createSampleDTO(2, 200.00));

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item->getId();
        }

        $this->assertEquals([1, 2], $items);
    }

    /**
     * Test filter() method
     */
    public function testFilterReturnsNewCollection()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, true));
        $collection->add($this->createSampleDTO(2, 200.00, false));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $matched = $collection->filter(fn(BiLineItemDTO $dto) => $dto->isMatched());

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $matched);
        $this->assertEquals(2, count($matched));
    }

    /**
     * Test getMatched() convenience method
     */
    public function testGetMatchedReturnsOnlyMatched()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, true));
        $collection->add($this->createSampleDTO(2, 200.00, false));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $matched = $collection->getMatched();

        $this->assertEquals(2, count($matched));
    }

    /**
     * Test getUnmatched() convenience method
     */
    public function testGetUnmatchedReturnsOnlyUnmatched()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, true));
        $collection->add($this->createSampleDTO(2, 200.00, false));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $unmatched = $collection->getUnmatched();

        $this->assertEquals(1, count($unmatched));
    }

    /**
     * Test sumAmounts() method
     */
    public function testSumAmountsCalculatesTotal()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00));
        $collection->add($this->createSampleDTO(2, 200.00));
        $collection->add($this->createSampleDTO(3, 300.00));

        $total = $collection->sumAmounts();

        $this->assertEquals(600.00, $total);
    }

    /**
     * Test any() returns true if predicate matches
     */
    public function testAnyReturnsTrueWhenFound()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, false));
        $collection->add($this->createSampleDTO(2, 200.00, false));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $hasMatched = $collection->any(fn(BiLineItemDTO $dto) => $dto->isMatched());

        $this->assertTrue($hasMatched);
    }

    /**
     * Test any() returns false if no match
     */
    public function testAnyReturnsFalseWhenNotFound()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, false));
        $collection->add($this->createSampleDTO(2, 200.00, false));

        $hasMatched = $collection->any(fn(BiLineItemDTO $dto) => $dto->isMatched());

        $this->assertFalse($hasMatched);
    }

    /**
     * Test all() returns true when all match predicate
     */
    public function testAllReturnsTrueWhenAllMatch()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, true));
        $collection->add($this->createSampleDTO(2, 200.00, true));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $allMatched = $collection->all(fn(BiLineItemDTO $dto) => $dto->isMatched());

        $this->assertTrue($allMatched);
    }

    /**
     * Test all() returns false when not all match
     */
    public function testAllReturnsFalseWhenNotAllMatch()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, true));
        $collection->add($this->createSampleDTO(2, 200.00, false));
        $collection->add($this->createSampleDTO(3, 300.00, true));

        $allMatched = $collection->all(fn(BiLineItemDTO $dto) => $dto->isMatched());

        $this->assertFalse($allMatched);
    }

    /**
     * Test groupBy() groups items by a key
     */
    public function testGroupByGroupsItemsByKey()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00, false, 'Supplier'));
        $collection->add($this->createSampleDTO(2, 200.00, false, 'Customer'));
        $collection->add($this->createSampleDTO(3, 300.00, false, 'Supplier'));

        $grouped = $collection->groupBy(fn(BiLineItemDTO $dto) => $dto->getPartnerType() ?? 'Unknown');

        $this->assertIsArray($grouped);
        $this->assertCount(2, $grouped);
        $this->assertEquals(2, count($grouped['Supplier']));
        $this->assertEquals(1, count($grouped['Customer']));
    }

    /**
     * Test map() transforms items
     */
    public function testMapTransformsItems()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00));
        $collection->add($this->createSampleDTO(2, 200.00));

        $amounts = $collection->map(fn(BiLineItemDTO $dto) => $dto->getAmount());

        $this->assertIsArray($amounts);
        $this->assertEquals([100.00, 200.00], $amounts);
    }

    /**
     * Test reduce() accumulates values
     */
    public function testReduceAccumulatesValues()
    {
        $collection = new BiLineItemCollectionDTO();
        $collection->add($this->createSampleDTO(1, 100.00));
        $collection->add($this->createSampleDTO(2, 200.00));
        $collection->add($this->createSampleDTO(3, 300.00));

        $total = $collection->reduce(fn($carry, BiLineItemDTO $dto) => $carry + $dto->getAmount(), 0);

        $this->assertEquals(600.00, $total);
    }
}
