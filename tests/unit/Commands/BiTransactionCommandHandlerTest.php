<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Commands;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Commands\BiTransactionCommandHandler;
use Ksfraser\FaBankImport\Services\BiTransactionService;
use Ksfraser\FaBankImport\Repositories\BiTransactionRepository;
use Ksfraser\FaBankImport\Models\BiTransaction;

class BiTransactionCommandHandlerTest extends TestCase
{
    private BiTransactionCommandHandler $handler;
    private BiTransactionService $service;
    private BiTransactionRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new BiTransactionRepository();
        $this->service = new BiTransactionService($this->repository);
        $this->handler = new BiTransactionCommandHandler($this->service);
    }

    /**
     * Test handler is properly injected with service
     */
    public function testHandlerHasServiceInjected(): void
    {
        $this->assertInstanceOf(BiTransactionCommandHandler::class, $this->handler);
    }

    /**
     * Test handle update transaction command
     */
    public function testHandleUpdateTransactionCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'update',
            'data' => [
                'transactionDC' => 'C',
                'transactionAmount' => 2000.00,
            ],
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
    }

    /**
     * Test handle toggle debit credit command
     */
    public function testHandleToggleDebitCreditCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('previousDC', $response);
        $this->assertArrayHasKey('newDC', $response);
    }

    /**
     * Test handle mark matched command
     */
    public function testHandleMarkMatchedCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'markMatched',
            'matchinfo' => 'INV-001',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['matched']);
    }

    /**
     * Test handle mark created command
     */
    public function testHandleMarkCreatedCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'markCreated',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['created']);
    }

    /**
     * Test handle link to FA command
     */
    public function testHandleLinkToFACommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'linkToFA',
            'faTransNo' => 999,
            'faTransType' => 1,
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertEquals(999, $response['data']['faTransNo']);
        $this->assertEquals(1, $response['data']['faTransType']);
    }

    /**
     * Test handle set partner command
     */
    public function testHandleSetPartnerCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'setPartner',
            'partnerId' => 'CUST001',
            'partnerOption' => 'CREDIT',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('CUST001', $response['data']['gPartner']);
        $this->assertEquals('CREDIT', $response['data']['gOption']);
    }

    /**
     * Test handle invalid action returns error
     */
    public function testHandleInvalidActionReturnsError(): void
    {
        $command = [
            'id' => 1,
            'action' => 'invalidAction',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test handle missing ID returns error
     */
    public function testHandleMissingIdReturnsError(): void
    {
        $command = [
            'action' => 'update',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test handle non-existent transaction returns error
     */
    public function testHandleNonExistentTransactionReturnsError(): void
    {
        $command = [
            'id' => 99999,
            'action' => 'update',
            'data' => [],
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test response structure always includes required fields
     */
    public function testResponseStructureIsConsistent(): void
    {
        $command = [
            'id' => 1,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertTrue(is_bool($response['success']));
        $this->assertTrue(is_string($response['message']));
    }

    /**
     * Test error response includes error details
     */
    public function testErrorResponseIncludesErrorDetails(): void
    {
        $command = [
            'id' => 99999,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('errorCode', $response);
    }

    /**
     * Test successful response includes transaction data
     */
    public function testSuccessResponseIncludesTransactionData(): void
    {
        $command = [
            'id' => 1,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('transactionCode', $response['data']);
    }

    /**
     * Test validate command structure
     */
    public function testValidateCommandStructureRequiresIdAndAction(): void
    {
        $command = ['id' => 1]; // Missing action
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    /**
     * Test batch operations return array of results
     */
    public function testBatchOperationsReturnArrayOfResults(): void
    {
        $commands = [
            ['id' => 1, 'action' => 'toggleDebitCredit'],
            ['id' => 2, 'action' => 'markCreated'],
            ['id' => 3, 'action' => 'toggleDebitCredit'],
        ];
        
        $results = $this->handler->handleBatch($commands);
        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
    }

    /**
     * Test batch operations continue on error
     */
    public function testBatchOperationsContinuesOnError(): void
    {
        $commands = [
            ['id' => 1, 'action' => 'toggleDebitCredit'],
            ['id' => 99999, 'action' => 'toggleDebitCredit'], // Error
            ['id' => 2, 'action' => 'toggleDebitCredit'],
        ];
        
        $results = $this->handler->handleBatch($commands);
        
        $this->assertCount(3, $results);
        // First and third succeed, second fails
        $this->assertTrue($results[0]['success']);
        $this->assertFalse($results[1]['success']);
        $this->assertTrue($results[2]['success']);
    }

    /**
     * Test batch operations tracking
     */
    public function testBatchOperationsReturnsSummary(): void
    {
        $commands = [
            ['id' => 1, 'action' => 'toggleDebitCredit'],
            ['id' => 2, 'action' => 'markCreated'],
        ];
        
        $result = $this->handler->handleBatchWithSummary($commands);
        
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('successful', $result['summary']);
        $this->assertArrayHasKey('failed', $result['summary']);
    }

    /**
     * Test command handler converts to DTO in response
     */
    public function testResponseDataCanBeDTO(): void
    {
        $command = [
            'id' => 1,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        // Data should be array (from entity->toArray()), ready for JSON encoding
        $this->assertIsArray($response['data']);
        $this->assertArrayHasKey('transactionCode', $response['data']);
    }

    /**
     * Test command handler is idempotent for read operations
     */
    public function testReadOperationsAreIdempotent(): void
    {
        // Running same command twice should give same result
        $command = ['id' => 1, 'action' => 'toggleDebitCredit'];
        
        $response1 = $this->handler->handle($command);
        $response2 = $this->handler->handle($command);
        
        // Both succeed
        $this->assertTrue($response1['success']);
        $this->assertTrue($response2['success']);
    }

    /**
     * Test handle delete transaction command
     */
    public function testHandleDeleteTransactionCommand(): void
    {
        $command = [
            'id' => 1,
            'action' => 'delete',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('deletedId', $response);
        $this->assertEquals(1, $response['deletedId']);
    }

    /**
     * Test handle bulk operations command
     */
    public function testHandleBulkOperationsCommand(): void
    {
        $command = [
            'action' => 'bulkMarkMatched',
            'ids' => [1, 2, 3],
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('processed', $response);
        $this->assertArrayHasKey('successful', $response);
    }

    /**
     * Test proper error codes are used
     */
    public function testProperErrorCodesAreUsed(): void
    {
        $command = [
            'id' => 99999,
            'action' => 'toggleDebitCredit',
        ];
        
        $response = $this->handler->handle($command);
        
        $this->assertFalse($response['success']);
        $this->assertIsString($response['errorCode']);
        $this->assertMatchesRegularExpression('/^[A-Z_]+$/', $response['errorCode']);
    }
}
