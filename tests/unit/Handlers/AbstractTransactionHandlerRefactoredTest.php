<?php

/**
 * Abstract Transaction Handler Test (Refactored)
 *
 * Tests for the refactored AbstractTransactionHandler base class.
 * Phase 1 refactoring: Uses PartnerTypeConstants, filtered POST data, simplified interface.
 *
 * @package    Tests\Unit\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.1.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\CustomerPartnerType;

/**
 * Abstract Transaction Handler Refactored Test
 *
 * Tests the refactored AbstractTransactionHandler with:
 * - Constructor-based partner type initialization
 * - Static PartnerTypeConstants::getCodeByConstant() usage
 * - Simplified canProcess(string $partnerType)
 * - Filtered transaction POST data
 */
class AbstractTransactionHandlerRefactoredTest extends TestCase
{
    /**
     * Test getPartnerType returns correct code
     *
     * @test
     */
    public function it_returns_partner_type_code(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $this->assertSame('CU', $handler->getPartnerType());
    }

    /**
     * Test canProcess returns true for matching type
     *
     * @test
     */
    public function it_can_process_matching_partner_type(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $this->assertTrue($handler->canProcess('CU'));
    }

    /**
     * Test canProcess returns false for non-matching type
     *
     * @test
     */
    public function it_cannot_process_non_matching_partner_type(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $this->assertFalse($handler->canProcess('SP'));
        $this->assertFalse($handler->canProcess('QE'));
        $this->assertFalse($handler->canProcess('BT'));
    }

    /**
     * Test constructor initializes partner type
     *
     * @test
     */
    public function it_initializes_partner_type_in_constructor(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        // Should have partner type set immediately
        $this->assertSame('CU', $handler->getPartnerType());
        
        // Calling again should return same value (no lazy loading)
        $this->assertSame('CU', $handler->getPartnerType());
    }

    /**
     * Test validateTransaction throws exception for missing fields
     *
     * @test
     */
    public function it_validates_required_transaction_fields(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $transaction = ['transactionAmount' => 100]; // Missing required fields
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required field 'transactionDC' not set");
        
        $handler->testValidateTransaction($transaction);
    }

    /**
     * Test validateTransaction passes with all fields
     *
     * @test
     */
    public function it_passes_validation_with_complete_transaction(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $transaction = [
            'transactionDC' => 'D',
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test payment'
        ];
        
        $handler->testValidateTransaction($transaction);
        
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test extractPartnerId from filtered POST data
     *
     * @test
     */
    public function it_extracts_partner_id_from_transaction_post_data(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $transactionPostData = [
            'partnerId' => 42,
            'invoice' => 'INV-001'
        ];
        
        $this->assertSame(42, $handler->testExtractPartnerId($transactionPostData));
    }

    /**
     * Test extractPartnerId throws exception when missing
     *
     * @test
     */
    public function it_throws_exception_when_partner_id_missing(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $transactionPostData = [
            'invoice' => 'INV-001'
            // No partnerId key
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Partner ID not found in transaction data');
        
        $handler->testExtractPartnerId($transactionPostData);
    }

    /**
     * Test extractPartnerId throws exception for invalid ID
     *
     * @test
     */
    public function it_throws_exception_for_invalid_partner_id(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $transactionPostData = [
            'partnerId' => 0  // Invalid: not positive
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid partner ID: must be positive integer');
        
        $handler->testExtractPartnerId($transactionPostData);
    }

    /**
     * Test createErrorResult format
     *
     * @test
     */
    public function it_creates_standard_error_result(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $result = $handler->testCreateErrorResult('Test error');
        
        $this->assertTrue($result->isError());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame(0, $result->getTransType());
        $this->assertSame('Test error', $result->getMessage());
    }

    /**
     * Test createSuccessResult format
     *
     * @test
     */
    public function it_creates_standard_success_result(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $result = $handler->testCreateSuccessResult(42, 20, 'Success');
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(42, $result->getTransNo());
        $this->assertSame(20, $result->getTransType());
        $this->assertSame('Success', $result->getMessage());
    }

    /**
     * Test createSuccessResult with additional data
     *
     * @test
     */
    public function it_merges_additional_data_in_success_result(): void
    {
        $handler = new TestTransactionHandlerRefactored();
        
        $result = $handler->testCreateSuccessResult(
            42,
            20,
            'Success',
            ['charge' => 5.00, 'ref' => 'REF-001']
        );
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(5.00, $result->getData('charge'));
        $this->assertSame('REF-001', $result->getData('ref'));
    }
}

/**
 * Test Transaction Handler
 *
 * Concrete implementation for testing AbstractTransactionHandler.
 * Uses CUSTOMER partner type.
 */
class TestTransactionHandlerRefactored extends AbstractTransactionHandler
{
    /**
     * @inheritDoc
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new CustomerPartnerType();
    }

    /**
     * @inheritDoc
     */
    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult {
        return $this->createSuccessResult(0, 0, 'Test handler');
    }

    // Expose protected methods for testing

    public function testValidateTransaction(array $transaction): void
    {
        $this->validateTransaction($transaction);
    }

    public function testExtractPartnerId(array $transactionPostData): int
    {
        return $this->extractPartnerId($transactionPostData);
    }

    public function testCreateErrorResult(string $message): TransactionResult
    {
        return $this->createErrorResult($message);
    }

    public function testCreateSuccessResult(int $transNo, int $transType, string $message, array $additionalData = []): TransactionResult
    {
        return $this->createSuccessResult($transNo, $transType, $message, $additionalData);
    }
}
