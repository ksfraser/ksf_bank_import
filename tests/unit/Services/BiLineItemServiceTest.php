<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\BiLineItemService;
use Ksfraser\FaBankImport\Repositories\BiLineItemRepository;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\DTOs\BiLineItemDTO;
use Ksfraser\FaBankImport\DTOs\BiLineItemCollectionDTO;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;
use DateTime;

/**
 * Test suite for BiLineItemService
 *
 * Tests the business logic orchestration layer that coordinates
 * DTOs, repositories, and complex domain operations.
 *
 * @covers \Ksfraser\FaBankImport\Services\BiLineItemService
 */
class BiLineItemServiceTest extends TestCase
{
    private BiLineItemService $service;
    private BiLineItemRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new BiLineItemRepository();
        $this->service = new BiLineItemService($this->repository);
    }

    /**
     * Test service initialization with repository
     */
    public function testServiceInitializesWithRepository(): void
    {
        $this->assertInstanceOf(BiLineItemService::class, $this->service);
    }

    /**
     * Test retrieving all line items as DTOs
     */
    public function testGetAllLineItemsReturnsDTOCollection(): void
    {
        $result = $this->service->getAllLineItems();

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * Test retrieving matched line items
     */
    public function testGetMatchedLineItemsReturnsOnlyMatched(): void
    {
        $result = $this->service->getMatchedLineItems();

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertTrue($dto->isMatched());
        }
    }

    /**
     * Test retrieving unmatched line items
     */
    public function testGetUnmatchedLineItemsReturnsOnlyUnmatched(): void
    {
        $result = $this->service->getUnmatchedLineItems();

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertFalse($dto->isMatched());
        }
    }

    /**
     * Test counting total line items
     */
    public function testCountAllLineItemsReturnsAccurateCount(): void
    {
        $count = $this->service->countAllLineItems();

        $this->assertGreaterThan(0, $count);
        $this->assertEquals(count($this->service->getAllLineItems()), $count);
    }

    /**
     * Test counting matched line items
     */
    public function testCountMatchedLineItemsReturnsMatchedCount(): void
    {
        $matchedCount = $this->service->countMatchedLineItems();
        $matchedItems = $this->service->getMatchedLineItems();

        $this->assertEquals(count($matchedItems), $matchedCount);
    }

    /**
     * Test counting unmatched line items
     */
    public function testCountUnmatchedLineItemsReturnsUnmatchedCount(): void
    {
        $unmatchedCount = $this->service->countUnmatchedLineItems();
        $unmatchedItems = $this->service->getUnmatchedLineItems();

        $this->assertEquals(count($unmatchedItems), $unmatchedCount);
    }

    /**
     * Test retrieving line item by ID
     */
    public function testGetLineItemByIdReturnsDTO(): void
    {
        $result = $this->service->getLineItemById(1);

        $this->assertInstanceOf(BiLineItemDTO::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * Test retrieving non-existent line item throws exception
     */
    public function testGetLineItemByIdThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(RepositoryException::class);
        $this->service->getLineItemById(9999);
    }

    /**
     * Test filtering by amount range
     */
    public function testFilterByAmountRangeReturnsFilteredCollection(): void
    {
        $result = $this->service->filterByAmountRange(100.00, 300.00);

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertGreaterThanOrEqual(100.00, $dto->getAmount());
            $this->assertLessThanOrEqual(300.00, $dto->getAmount());
        }
    }

    /**
     * Test filtering by partner type
     */
    public function testFilterByPartnerTypeReturnsMatchingItems(): void
    {
        $result = $this->service->filterByPartnerType('Supplier');

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertEquals('Supplier', $dto->getPartnerType());
        }
    }

    /**
     * Test filtering by transaction code
     */
    public function testFilterByTransactionCodeReturnsMatchingItems(): void
    {
        $result = $this->service->filterByTransactionCode('DEP');

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertEquals('DEP', $dto->getTransactionCode());
        }
    }

    /**
     * Test getting unassigned partners
     */
    public function testGetUnassignedPartnersReturnsItemsWithoutPartner(): void
    {
        $result = $this->service->getUnassignedPartners();

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertNull($dto->getPartnerType());
            $this->assertNull($dto->getPartnerId());
        }
    }

    /**
     * Test getting summary statistics
     */
    public function testGetSummaryStatsReturnsArray(): void
    {
        $stats = $this->service->getSummaryStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('matched_count', $stats);
        $this->assertArrayHasKey('unmatched_count', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('matched_amount', $stats);
        $this->assertArrayHasKey('unmatched_amount', $stats);
    }

    /**
     * Test statistics are accurate
     */
    public function testGetSummaryStatsValuesAreAccurate(): void
    {
        $stats = $this->service->getSummaryStats();
        $allItems = $this->service->getAllLineItems();
        $matchedItems = $this->service->getMatchedLineItems();
        $unmatchedItems = $this->service->getUnmatchedLineItems();

        $this->assertEquals(count($allItems), $stats['total_count']);
        $this->assertEquals(count($matchedItems), $stats['matched_count']);
        $this->assertEquals(count($unmatchedItems), $stats['unmatched_count']);
    }

    /**
     * Test getting statistics by partner type
     */
    public function testGetStatsByPartnerTypeReturnsArray(): void
    {
        $stats = $this->service->getStatsByPartnerType();

        $this->assertIsArray($stats);
        foreach ($stats as $partnerType => $typeStats) {
            $this->assertArrayHasKey('count', $typeStats);
            $this->assertArrayHasKey('total_amount', $typeStats);
            $this->assertArrayHasKey('matched', $typeStats);
        }
    }

    /**
     * Test getting statistics by transaction code
     */
    public function testGetStatsByTransactionCodeReturnsArray(): void
    {
        $stats = $this->service->getStatsByTransactionCode();

        $this->assertIsArray($stats);
        foreach ($stats as $code => $codeStats) {
            $this->assertArrayHasKey('count', $codeStats);
            $this->assertArrayHasKey('total_amount', $codeStats);
            $this->assertArrayHasKey('matched', $codeStats);
        }
    }

    /**
     * Test getting match statistics
     */
    public function testGetMatchStatsReturnsArray(): void
    {
        $stats = $this->service->getMatchStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('matched_items', $stats);
        $this->assertArrayHasKey('unmatched_items', $stats);
        $this->assertArrayHasKey('match_percentage', $stats);
    }

    /**
     * Test match percentage calculation
     */
    public function testGetMatchStatsCalculatesPercentageCorrectly(): void
    {
        $stats = $this->service->getMatchStats();
        $allItems = $this->service->getAllLineItems();
        $matchedItems = $this->service->getMatchedLineItems();

        if (count($allItems) > 0) {
            $expectedPercentage = (count($matchedItems) / count($allItems)) * 100;
            $this->assertEquals($expectedPercentage, $stats['match_percentage'], '', 0.01);
        }
    }

    /**
     * Test getting total amount across all items
     */
    public function testGetTotalAmountReturnsSum(): void
    {
        $total = $this->service->getTotalAmount();

        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    /**
     * Test getting matched amount
     */
    public function testGetMatchedAmountReturnsSum(): void
    {
        $total = $this->service->getMatchedAmount();

        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    /**
     * Test getting unmatched amount
     */
    public function testGetUnmatchedAmountReturnsSum(): void
    {
        $total = $this->service->getUnmatchedAmount();

        $this->assertIsFloat($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    /**
     * Test amount totals add up correctly
     */
    public function testAmountTotalsAddUpCorrectly(): void
    {
        $totalAmount = $this->service->getTotalAmount();
        $matchedAmount = $this->service->getMatchedAmount();
        $unmatchedAmount = $this->service->getUnmatchedAmount();

        $this->assertEquals($totalAmount, $matchedAmount + $unmatchedAmount, '', 0.01);
    }

    /**
     * Test saving a line item
     */
    public function testSaveLineItemStoresEntity(): void
    {
        $lineItem = BiLineItem::create([
            'id' => 999,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-01',
            'entryTimestamp' => '2024-01-01',
            'otherBankaccount' => '4000-0001',
            'otherBankaccountName' => 'Test Partner',
            'transactionTitle' => 'Test Transaction',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 100.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'TST',
            'transactionCodeDesc' => 'Test Code',
            'optypes' => [],
            'memo' => 'Test Memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ]);

        $this->service->saveLineItem($lineItem);
        $retrieved = $this->service->getLineItemById(999);

        $this->assertEquals(999, $retrieved->getId());
        $this->assertEquals(100.00, $retrieved->getAmount());
    }

    /**
     * Test deleting a line item
     */
    public function testDeleteLineItemRemovesEntity(): void
    {
        // First get initial count
        $initialCount = $this->service->countAllLineItems();

        // Delete an item
        $this->service->deleteLineItem(1);

        // Verify count decreased
        $newCount = $this->service->countAllLineItems();
        $this->assertEquals($initialCount - 1, $newCount);

        // Verify it's not found
        $this->expectException(RepositoryException::class);
        $this->service->getLineItemById(1);
    }

    /**
     * Test finding items by complex criteria
     */
    public function testFindByComplexCriteriaReturnsMatching(): void
    {
        $criteria = ['partnerType' => 'Supplier'];
        $result = $this->service->findByCriteria($criteria);

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertEquals('Supplier', $dto->getPartnerType());
        }
    }

    /**
     * Test service can apply transformations to DTOs
     */
    public function testServiceCanTransformDTOsWithFunctions(): void
    {
        $items = $this->service->getMatchedLineItems();

        // Apply transformation function
        $result = $this->service->transformLineItems(
            $items,
            fn(BiLineItemDTO $dto) => [
                'id' => $dto->getId(),
                'amount' => $dto->getAmount(),
                'matched' => $dto->isMatched(),
            ]
        );

        $this->assertIsArray($result);
        foreach ($result as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('amount', $item);
            $this->assertArrayHasKey('matched', $item);
        }
    }

    /**
     * Test service can filter with custom predicates
     */
    public function testServiceCanFilterWithCustomPredicate(): void
    {
        $items = $this->service->getAllLineItems();

        // Filter items with amount > 200
        $result = $this->service->filterLineItems(
            $items,
            fn(BiLineItemDTO $dto) => $dto->getAmount() > 200.00
        );

        $this->assertInstanceOf(BiLineItemCollectionDTO::class, $result);
        foreach ($result as $dto) {
            $this->assertGreaterThan(200.00, $dto->getAmount());
        }
    }
}
