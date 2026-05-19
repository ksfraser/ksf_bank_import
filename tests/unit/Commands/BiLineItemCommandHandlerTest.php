<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Commands\BiLineItemCommandHandler;
use Ksfraser\FaBankImport\Services\BiLineItemService;
use Ksfraser\FaBankImport\Repositories\BiLineItemRepository;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\Exceptions\RepositoryException;

/**
 * Test suite for BiLineItemCommandHandler
 *
 * Tests the command/request handling layer that coordinates
 * with services and provides structured response formatting.
 *
 * @covers \Ksfraser\FaBankImport\Commands\BiLineItemCommandHandler
 */
class BiLineItemCommandHandlerTest extends TestCase
{
    private BiLineItemCommandHandler $handler;
    private BiLineItemService $service;

    protected function setUp(): void
    {
        $repository = new BiLineItemRepository();
        $this->service = new BiLineItemService($repository);
        $this->handler = new BiLineItemCommandHandler($this->service);
    }

    /**
     * Test handler initialization
     */
    public function testHandlerInitializesWithService(): void
    {
        $this->assertInstanceOf(BiLineItemCommandHandler::class, $this->handler);
    }

    /**
     * Test listing all line items
     */
    public function testHandleListAllReturnsFormattedCollection(): array
    {
        $result = $this->handler->handleListAll();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(0, $result['count']);

        return $result;
    }

    /**
     * Test listing matched line items
     */
    public function testHandleListMatchedReturnsOnlyMatched(): void
    {
        $result = $this->handler->handleListMatched();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $this->assertTrue($item['matched'] ?? false);
            }
        }
    }

    /**
     * Test listing unmatched line items
     */
    public function testHandleListUnmatchedReturnsOnlyUnmatched(): void
    {
        $result = $this->handler->handleListUnmatched();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $this->assertFalse($item['matched'] ?? true);
            }
        }
    }

    /**
     * Test getting single item
     */
    public function testHandleGetByIdReturnsItem(): void
    {
        $result = $this->handler->handleGetById(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    /**
     * Test getting non-existent item returns error
     */
    public function testHandleGetByIdReturnsErrorForNotFound(): void
    {
        $result = $this->handler->handleGetById(9999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test counting items
     */
    public function testHandleCountReturnsCount(): void
    {
        $result = $this->handler->handleCount();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('matched_count', $result);
        $this->assertArrayHasKey('unmatched_count', $result);
        $this->assertIsInt($result['count']);
    }

    /**
     * Test getting statistics
     */
    public function testHandleGetStatsReturnsStatistics(): void
    {
        $result = $this->handler->handleGetStats();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        $stats = $result['data'];
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('matched_count', $stats);
        $this->assertArrayHasKey('unmatched_count', $stats);
        $this->assertArrayHasKey('match_percentage', $stats);
    }

    /**
     * Test filtering by amount range
     */
    public function testHandleFilterByAmountRangeReturnsFiltered(): void
    {
        $result = $this->handler->handleFilterByAmountRange(100.00, 300.00);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);

        foreach ($result['data'] as $item) {
            $this->assertGreaterThanOrEqual(100.00, $item['amount']);
            $this->assertLessThanOrEqual(300.00, $item['amount']);
        }
    }

    /**
     * Test filtering by partner type
     */
    public function testHandleFilterByPartnerTypeReturnsFiltered(): void
    {
        $result = $this->handler->handleFilterByPartnerType('Supplier');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $this->assertEquals('Supplier', $item['partnerType']);
            }
        }
    }

    /**
     * Test filtering by transaction code
     */
    public function testHandleFilterByTransactionCodeReturnsFiltered(): void
    {
        $result = $this->handler->handleFilterByTransactionCode('DEP');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $this->assertEquals('DEP', $item['transactionCode']);
            }
        }
    }

    /**
     * Test getting unassigned partners
     */
    public function testHandleGetUnassignedPartnersReturnsItems(): void
    {
        $result = $this->handler->handleGetUnassignedPartners();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $this->assertNull($item['partnerType']);
                $this->assertNull($item['partnerId']);
            }
        }
    }

    /**
     * Test bulk operations response format
     */
    public function testBulkOperationsReturnFormattedResponse(): void
    {
        $result = $this->handler->handleListAll();

        // Verify standard response format
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsString($result['timestamp']);
    }

    /**
     * Test error response format
     */
    public function testErrorResponseHasStandardFormat(): void
    {
        $result = $this->handler->handleGetById(9999);

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertIsString($result['error']);
    }

    /**
     * Test handler converts DTOs to arrays
     */
    public function testHandlerConvertsDataToArrayFormat(): void
    {
        $result = $this->handler->handleListAll();

        if (isset($result['data']) && count($result['data']) > 0) {
            $firstItem = $result['data'][0];
            $this->assertIsArray($firstItem);
            // Verify it has standard DTO properties
            $this->assertArrayHasKey('id', $firstItem);
            $this->assertArrayHasKey('amount', $firstItem);
            $this->assertArrayHasKey('matched', $firstItem);
        }
    }

    /**
     * Test handler with pagination parameters
     *
     * @depends testHandleListAllReturnsFormattedCollection
     */
    public function testHandleListWithPaginationReturnsLimited(array $allResults): void
    {
        $result = $this->handler->handleListAll(10, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        // Count should be limited
        $this->assertLessThanOrEqual(10, count($result['data'] ?? []));
    }

    /**
     * Test statistics by partner type
     */
    public function testHandleGetStatsByPartnerTypeReturnsArray(): void
    {
        $result = $this->handler->handleGetStatsByPartnerType();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $partnerType => $stats) {
            $this->assertArrayHasKey('count', $stats);
            $this->assertArrayHasKey('total_amount', $stats);
            $this->assertArrayHasKey('matched', $stats);
        }
    }

    /**
     * Test statistics by transaction code
     */
    public function testHandleGetStatsByTransactionCodeReturnsArray(): void
    {
        $result = $this->handler->handleGetStatsByTransactionCode();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $code => $stats) {
            $this->assertArrayHasKey('count', $stats);
            $this->assertArrayHasKey('total_amount', $stats);
            $this->assertArrayHasKey('matched', $stats);
        }
    }

    /**
     * Test saving a line item
     */
    public function testHandleSaveReturnsSuccess(): void
    {
        $lineItemData = [
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
        ];

        $result = $this->handler->handleSave($lineItemData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    /**
     * Test deleting a line item
     */
    public function testHandleDeleteReturnsSuccess(): void
    {
        $result = $this->handler->handleDelete(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    /**
     * Test handler provides consistent response metadata
     */
    public function testAllResponsesHaveConsistentMetadata(): void
    {
        $responses = [
            $this->handler->handleListAll(),
            $this->handler->handleCount(),
            $this->handler->handleGetStats(),
        ];

        foreach ($responses as $response) {
            $this->assertArrayHasKey('success', $response);
            $this->assertArrayHasKey('timestamp', $response);
            $this->assertIsString($response['timestamp']);
        }
    }
}
