<?php

/**
 * Abstract Transaction Handler Test
 *
 * Tests for the AbstractTransactionHandler base class.
 * Uses a concrete test implementation to verify abstract functionality.
 *
 * @package    Tests\Unit\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\AbstractTransactionHandler;
use Ksfraser\PartnerTypes\PartnerTypeInterface;

/**
 * Abstract Transaction Handler Test
 *
 * Tests for the AbstractTransactionHandler base class.
 * Uses a concrete test implementation to verify abstract functionality.
 *
 * Tests updated for Phase 1 refactoring:
 * - Uses PartnerTypeConstants::getCodeByConstant() instead of PartnerType objects
 * - canProcess() now takes string partner type instead of transaction/postData
 * - extractPartnerId() now takes filtered transactionPostData instead of full postData
 *
 * @package    Tests\Unit\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */
class AbstractTransactionHandlerTest extends TestCase
{
    /**
     * Test getPartnerType returns short code from PartnerTypeConstants
     *
     * @test
     */
    public function it_returns_partner_type_from_constant(): void
    {
        $handler = new TestTransactionHandler();
        
        $this->assertSame('CU', $handler->getPartnerType());
    }

    /**
     * Test canProcess checks partner type match
     *
     * @test
     */
    public function it_checks_partner_type_in_can_process(): void
    {
        $handler = new TestTransactionHandler();
        
        // Should return true for CU (Customer)
        $this->assertTrue($handler->canProcess('CU'));
        
        // Should return false for SP (Supplier) - not CU
        $this->assertFalse($handler->canProcess('SP'));
        
        // Should return false for QE (Quick Entry) - not CU
        $this->assertFalse($handler->canProcess('QE'));
    }

    /**
     * Test constructor validates partner type on instantiation
     *
     * @test
     */
    public function it_validates_partner_type_in_constructor(): void
    {
        // Valid handler should instantiate fine
        $handler = new TestTransactionHandler();
        $this->assertInstanceOf(TestTransactionHandler::class, $handler);
    }

    /**
     * Test constructor throws exception for invalid constant
     *
     * @test
     */
    public function it_throws_exception_for_invalid_constant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type short code');
        
        new InvalidConstantHandler();
    }

    /**
     * Test validateTransaction throws exception for missing required fields
     *
     * @test
     */
    public function it_validates_required_transaction_fields(): void
    {
        $handler = new TestTransactionHandler();
        
        $transaction = ['transactionAmount' => 100]; // Missing other fields
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required field 'transactionDC' not set");
        
        $handler->testValidateTransaction($transaction);
    }

    /**
     * Test validateTransaction succeeds with all required fields
     *
     * @test
     */
    public function it_passes_validation_with_complete_transaction(): void
    {
        $handler = new TestTransactionHandler();
        
        $transaction = [
            'transactionDC' => 'D',
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test'
        ];
        
        // Should not throw exception
        $handler->testValidateTransaction($transaction);
        
        $this->assertTrue(true); // Assertion to confirm no exception
    }

    /**
     * Test extractPartnerId extracts from correct key
     *
     * @test
     */
    public function it_extracts_partner_id_from_post_data(): void
    {
        $handler = new TestTransactionHandler();
        
        $postData = ['partnerId' => 42];
        
        $this->assertSame(42, $handler->testExtractPartnerId($postData));
    }

    /**
     * Test extractPartnerId throws exception when key missing
     *
     * @test
     */
    public function it_throws_exception_when_partner_id_not_found(): void
    {
        $handler = new TestTransactionHandler();
        
        $postData = [];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Partner ID not found in transaction data');
        
        $handler->testExtractPartnerId($postData);
    }

    /**
     * Test createErrorResult returns standard error format
     *
     * @test
     */
    public function it_creates_standard_error_result(): void
    {
        $handler = new TestTransactionHandler();
        
        $result = $handler->testCreateErrorResult('Test error');
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame(0, $result->getTransType());
        $this->assertSame('Test error', $result->getMessage());
    }

    /**
     * Test createSuccessResult returns standard success format
     *
     * @test
     */
    public function it_creates_standard_success_result(): void
    {
        $handler = new TestTransactionHandler();
        
        $result = $handler->testCreateSuccessResult(123, 12, 'Success');
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(123, $result->getTransNo());
        $this->assertSame(12, $result->getTransType());
        $this->assertSame('Success', $result->getMessage());
    }

    /**
     * Test createSuccessResult merges additional data
     *
     * @test
     */
    public function it_merges_additional_data_in_success_result(): void
    {
        $handler = new TestTransactionHandler();
        
        $result = $handler->testCreateSuccessResult(
            123, 
            12, 
            'Success',
            ['view_link' => '/view', 'extra' => 'data']
        );
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('/view', $result->getData('view_link'));
        $this->assertSame('data', $result->getData('extra'));
    }

    /**
     * Test getPartnerTypeLabel returns label from partner type
     *
     * @test
     */
    public function it_returns_partner_type_label(): void
    {
        $handler = new TestTransactionHandler();
        
        $this->assertSame('Customer', $handler->testGetPartnerTypeLabel());
    }

    /**
     * Test partner type object is cached
     *
     * @test
     */
    public function it_caches_partner_type_object(): void
    {
        $handler = new TestTransactionHandler();

        $obj1 = $handler->getPartnerTypeObject();
        $obj2 = $handler->getPartnerTypeObject();

        $this->assertSame($obj1, $obj2);
        $this->assertSame('CU', $obj1->getShortCode());
    }
}

/**
 * Test Transaction Handler
 *
 * Concrete implementation for testing AbstractTransactionHandler.
 * Uses CUSTOMER partner type for testing.
 */
class TestTransactionHandler extends AbstractTransactionHandler
{
    /**
     * @inheritDoc
     */
    protected function getPartnerTypeInstance(): \Ksfraser\PartnerTypes\PartnerTypeInterface
    {
        return new \Ksfraser\PartnerTypes\CustomerPartnerType();
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
    ): \Ksfraser\FaBankImport\Results\TransactionResult {
        return $this->createSuccessResult(0, 0, 'Test handler');
    }

    // Expose protected methods for testing

    public function testValidateTransaction(array $transaction): void
    {
        $this->validateTransaction($transaction);
    }

    public function testGetPartnerTypeLabel(): string
    {
        return $this->getPartnerTypeObject()->getLabel();
    }

    public function testExtractPartnerId(array $transactionPostData): int
    {
        return $this->extractPartnerId($transactionPostData);
    }

    public function testCreateErrorResult(string $message): \Ksfraser\FaBankImport\Results\TransactionResult
    {
        return $this->createErrorResult($message);
    }

    public function testCreateSuccessResult(int $transNo, int $transType, string $message, array $additionalData = []): \Ksfraser\FaBankImport\Results\TransactionResult
    {
        return $this->createSuccessResult($transNo, $transType, $message, $additionalData);
    }
}

class InvalidConstantHandler extends AbstractTransactionHandler
{
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new class implements PartnerTypeInterface {
            public function getShortCode(): string { return 'BAD'; }
            public function getLabel(): string { return 'Broken'; }
            public function getConstantName(): string { return 'BROKEN'; }
            public function getPriority(): int { return 999; }
            public function getDescription(): ?string { return null; }
            public function getViewClassName(): string { return 'BrokenView'; }
            public function getStrategyMethodName(): string { return 'displayBroken'; }
        };
    }

    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): \Ksfraser\FaBankImport\Results\TransactionResult {
        return \Ksfraser\FaBankImport\Results\TransactionResult::error('invalid');
    }
}
