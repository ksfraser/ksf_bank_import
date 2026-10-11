<?php

/**
 * Transaction Processor Test
 *
 * Tests for the TransactionProcessor class that coordinates transaction handlers.
 * STEP 3: Extract transaction processing switch to dedicated class.
 *
 * @package    Tests\Unit
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\TransactionProcessor;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use InvalidArgumentException;

/**
 * Transaction Processor Test
 *
 * Verifies TransactionProcessor correctly routes transactions to handlers
 */
class TransactionProcessorTest extends TestCase
{
    private TransactionProcessor $processor;

    /**
     * Set up test fixtures
     * 
     * Pass empty array to constructor to prevent auto-discovery during tests.
     * This allows controlled testing of registration logic.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Pass empty array to prevent auto-discovery
        $this->processor = new TransactionProcessor([]);
    }

    /**
     * Test processor can be instantiated
     *
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TransactionProcessor::class, $this->processor);
    }

    /**
     * Test processor with empty constructor has no handlers
     *
     * @test
     */
    public function it_starts_with_no_handlers_when_passed_empty_array(): void
    {
        $processor = new TransactionProcessor([]);
        $this->assertEmpty($processor->getRegisteredTypes());
        $this->assertFalse($processor->hasHandler('SP'));
    }

    /**
     * Test processor auto-discovers handlers when instantiated without arguments
     *
     * @test
     */
    public function it_auto_discovers_handlers_by_default(): void
    {
        $processor = new TransactionProcessor();
        
        // Should have discovered all 6 standard handlers
        $this->assertCount(6, $processor->getRegisteredTypes());
        $this->assertTrue($processor->hasHandler('SP'));
        $this->assertTrue($processor->hasHandler('CU'));
        $this->assertTrue($processor->hasHandler('QE'));
        $this->assertTrue($processor->hasHandler('BT'));
        $this->assertTrue($processor->hasHandler('MA'));
        $this->assertTrue($processor->hasHandler('ZZ'));
    }

    /**
     * Test can register a handler
     *
     * @test
     */
    public function it_can_register_handler(): void
    {
        $handler = $this->createMockHandler('SP');
        
        $result = $this->processor->registerHandler($handler);
        
        // Should support method chaining
        $this->assertSame($this->processor, $result);
        $this->assertTrue($this->processor->hasHandler('SP'));
        $this->assertContains('SP', $this->processor->getRegisteredTypes());
    }

    /**
     * Test can register multiple handlers
     *
     * @test
     */
    public function it_can_register_multiple_handlers(): void
    {
        $spHandler = $this->createMockHandler('SP');
        $cuHandler = $this->createMockHandler('CU');
        $qeHandler = $this->createMockHandler('QE');
        
        $this->processor
            ->registerHandler($spHandler)
            ->registerHandler($cuHandler)
            ->registerHandler($qeHandler);
        
        $this->assertCount(3, $this->processor->getRegisteredTypes());
        $this->assertTrue($this->processor->hasHandler('SP'));
        $this->assertTrue($this->processor->hasHandler('CU'));
        $this->assertTrue($this->processor->hasHandler('QE'));
    }

    /**
     * Test can retrieve registered handler
     *
     * @test
     */
    public function it_can_retrieve_handler(): void
    {
        $handler = $this->createMockHandler('SP');
        $this->processor->registerHandler($handler);
        
        $retrieved = $this->processor->getHandler('SP');
        
        $this->assertSame($handler, $retrieved);
    }

    /**
     * Test returns null for non-existent handler
     *
     * @test
     */
    public function it_returns_null_for_non_existent_handler(): void
    {
        $handler = $this->processor->getHandler('INVALID');
        
        $this->assertNull($handler);
    }

    /**
     * Test throws exception when processing without handler
     *
     * @test
     */
    public function it_throws_exception_when_no_handler_registered(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No handler registered for partner type: SP');
        
        $this->processor->process(
            'SP',
            ['transactionAmount' => 100],
            [],
            1,
            'cid123',
            ['id' => 1]
        );
    }

    /**
     * Test successfully processes transaction with registered handler
     *
     * @test
     */
    public function it_processes_transaction_with_registered_handler(): void
    {
        $expectedResult = TransactionResult::success(
            12345,
            20,
            'Transaction processed successfully'
        );
        
        $transaction = ['transactionAmount' => 100, 'transactionDC' => 'D'];
        $postData = ['partnerId_1' => 5, 'Invoice_1' => 'INV-001'];
        $transactionId = 1;
        $collectionIds = 'cid123';
        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Account'];
        
        // Create a fresh mock for this specific test
        $handler = $this->createMock(TransactionHandlerInterface::class);
        $handler->method('getPartnerType')->willReturn('SP');
        $handler->method('canProcess')
            ->with('SP')
            ->willReturn(true);
        
        // Extract transaction-specific POST data (what processor will pass)
        $transactionPostData = [
            'partnerId' => 5,
            'invoice' => 'INV-001',
            'comment' => null,
            'partnerDetailId' => null,
        ];
        
        $handler->method('process')
            ->with($transaction, $transactionPostData, $transactionId, $collectionIds, $ourAccount)
            ->willReturn($expectedResult);
        
        $this->processor->registerHandler($handler);
        
        $result = $this->processor->process(
            'SP',
            $transaction,
            $postData,
            $transactionId,
            $collectionIds,
            $ourAccount
        );
        
        $this->assertSame($expectedResult, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(12345, $result->getTransNo());
        $this->assertSame(20, $result->getTransType());
    }

    /**
     * Test returns failure when handler cannot process
     *
     * @test
     */
    public function it_returns_failure_when_handler_cannot_process(): void
    {
        // Create a fresh mock that returns false for canProcess
        $handler = $this->createMock(TransactionHandlerInterface::class);
        $handler->method('getPartnerType')->willReturn('SP');
        $handler->method('canProcess')
            ->with('SP')
            ->willReturn(false);
        
        $this->processor->registerHandler($handler);
        
        $result = $this->processor->process(
            'SP',
            ['transactionAmount' => 100],
            [],
            1,
            'cid123',
            ['id' => 1]
        );
        
        $this->assertTrue($result->isError());
        $this->assertSame(0, $result->getTransNo());
        $this->assertSame(0, $result->getTransType());
        $this->assertStringContainsString('cannot process', $result->getMessage());
    }

    /**
     * Test all six partner types can be registered
     *
     * @test
     */
    public function it_supports_all_six_partner_types(): void
    {
        $partnerTypes = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'];
        
        foreach ($partnerTypes as $type) {
            $handler = $this->createMockHandler($type);
            $this->processor->registerHandler($handler);
        }
        
        $registered = $this->processor->getRegisteredTypes();
        
        $this->assertCount(6, $registered);
        foreach ($partnerTypes as $type) {
            $this->assertContains($type, $registered);
            $this->assertTrue($this->processor->hasHandler($type));
        }
    }

    /**
     * Test replacing a handler for same partner type
     *
     * @test
     */
    public function it_can_replace_handler_for_same_type(): void
    {
        $handler1 = $this->createMockHandler('SP');
        $handler2 = $this->createMockHandler('SP');
        
        $this->processor->registerHandler($handler1);
        $this->assertSame($handler1, $this->processor->getHandler('SP'));
        
        $this->processor->registerHandler($handler2);
        $this->assertSame($handler2, $this->processor->getHandler('SP'));
        
        // Should still only have one SP handler registered
        $this->assertCount(1, $this->processor->getRegisteredTypes());
    }

    /**
     * Test processor maintains handler registration across multiple calls
     *
     * @test
     */
    public function it_maintains_handler_state(): void
    {
        $spHandler = $this->createMockHandler('SP');
        $cuHandler = $this->createMockHandler('CU');
        
        $this->processor->registerHandler($spHandler);
        $this->assertTrue($this->processor->hasHandler('SP'));
        
        $this->processor->registerHandler($cuHandler);
        
        // SP handler should still be registered
        $this->assertTrue($this->processor->hasHandler('SP'));
        $this->assertTrue($this->processor->hasHandler('CU'));
        $this->assertCount(2, $this->processor->getRegisteredTypes());
    }

    /**
     * Test processor passes correct parameters to handler
     *
     * @test
     */
    public function it_passes_correct_parameters_to_handler(): void
    {
        $transaction = [
            'transactionAmount' => 250.50,
            'transactionDC' => 'C',
            'memo' => 'Test transaction'
        ];
        $postData = ['partnerId_42' => 10, 'Invoice_42' => 'INV-001'];
        $transactionId = 42;
        $collectionIds = 'cid456';
        $ourAccount = ['id' => 3, 'bank_account_name' => 'Main Account'];
        
        $handler = $this->createMock(TransactionHandlerInterface::class);
        $handler->method('getPartnerType')->willReturn('CU');
        $handler->method('canProcess')
            ->with('CU')
            ->willReturn(true);
        
        // Extract transaction-specific POST data (what processor will pass)
        $transactionPostData = [
            'partnerId' => 10,
            'invoice' => 'INV-001',
            'comment' => null,
            'partnerDetailId' => null,
        ];
        
        // Verify exact parameters passed
        $handler->expects($this->once())
            ->method('process')
            ->with(
                $this->identicalTo($transaction),
                $this->identicalTo($transactionPostData),
                $this->identicalTo($transactionId),
                $this->identicalTo($collectionIds),
                $this->identicalTo($ourAccount)
            )
            ->willReturn(TransactionResult::success(1, 12, 'OK'));
        
        $this->processor->registerHandler($handler);
        
        $this->processor->process(
            'CU',
            $transaction,
            $postData,
            $transactionId,
            $collectionIds,
            $ourAccount
        );
    }

    /**
     * Helper: Create a mock handler for testing
     *
     * @param string $partnerType Partner type code
     * @return TransactionHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockHandler(string $partnerType): TransactionHandlerInterface
    {
        $handler = $this->createMock(TransactionHandlerInterface::class);
        $handler->method('getPartnerType')->willReturn($partnerType);
        $handler->method('canProcess')
            ->with($partnerType)
            ->willReturn(true);
        $handler->method('process')->willReturn(
            TransactionResult::success(0, 0, 'Mock handler')
        );
        
        return $handler;
    }
}
