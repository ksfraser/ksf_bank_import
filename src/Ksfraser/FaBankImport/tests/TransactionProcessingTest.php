<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\Application\Application;
use Ksfraser\Application\Container;
use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;

/** @BABOK CLASS ROLLUP: TransactionProcessingTest — 4 methods; NFR-016 (4); FR-018 (4) */
class TransactionProcessingTest extends TestCase
{
    private $app;
    private $container;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $_SESSION['user_id'] = 1; // Mock authenticated user
        $this->app = new Application();
        $this->container = Container::getInstance();
    }

    /** @BABOK NFR-016 / FR-018 — method: testCompleteTransactionProcessingFlow */
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

    /** @BABOK NFR-016 / FR-018 — method: testValidationMiddlewareRejectsInvalidType */
    public function testValidationMiddlewareRejectsInvalidType()
    {
        $this->expectException('Ksfraser\\FaBankImport\\Exceptions\\TransactionValidationException');

        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'INVALID'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = new RequestHandler();
        $this->app->run();
    }

    /** @BABOK NFR-016 / FR-018 — method: tearDown */
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}
