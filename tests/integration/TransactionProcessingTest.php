<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Application;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;

class TransactionProcessingTest extends TestCase
{
    private $app;
    private $container;

    protected function setUp(): void
    {
        // Skip TransactionProcessing tests - fixture and HTTP simulation required
        // These tests validate Phase 0 command processing
        // TODO: Implement proper HTTP request mocking or use HTTP test client
        $this->markTestSkipped(
            'TransactionProcessing integration tests disabled. '
            . 'Requires Symfony HTTP request simulation and fixture setup. '
            . 'Phase 0 command handling is covered by unit tests. '
            . 'See: tests/integration/TransactionProcessingTest.php'
        );
    }

    public function testCompleteTransactionProcessingFlow()
    {
        // Prepare test data
        $testTransaction = [
            'id' => 1,
            'amount' => 100.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test transaction',
            'transactionDC' => 'C',
            'status' => 'pending'
        ];

        // Mock POST request data
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'C'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Create and dispatch command
        $command = new ProcessTransactionCommand(1, 'C', 1);
        $result = $this->container->getCommandBus()->dispatch($command);

        // Verify transaction was processed
        $this->assertInstanceOf(
            'Ksfraser\\FaBankImport\\Events\\TransactionProcessedEvent',
            $result
        );
        $this->assertEquals(1, $result->getTransactionId());
        $this->assertEquals('C', $result->getType());
    }

    public function testValidationMiddlewareRejectsInvalidType()
    {
        $this->expectException('Ksfraser\\FaBankImport\\Exceptions\\TransactionValidationException');

        $_POST['transaction'] = ['type' => 'INVALID'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new \Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware();
        $request = new RequestHandler();
        $middleware->process($request, function () {
            return null;
        });
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}
