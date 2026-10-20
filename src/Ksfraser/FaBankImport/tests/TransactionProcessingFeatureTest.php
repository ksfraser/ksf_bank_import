<?php

namespace Tests\Feature;

use Tests\Integration\DatabaseTestCase;
use Ksfraser\FaBankImport\Application;

class TransactionProcessingFeatureTest extends DatabaseTestCase
{
    private $app;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION['user_id'] = 1;
        $this->app = new Application();
    }

    /** @BABOK NFR-016 / FR-018 — method: seedTestData */
    protected function seedTestData(): void
    {
        $this->createTestTransaction([
            'amount' => 500.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test Payment',
            'transactionDC' => 'C',
            'status' => 'pending'
        ]);
    }

    /** @BABOK NFR-016 / FR-018 — method: testDisplaysTransactionList */
    public function testDisplaysTransactionList()
    {
        ob_start();
        $this->app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Payment', $output);
        $this->assertStringContainsString('500.00', $output);
        $this->assertStringContainsString('Process', $output);
    }

    /** @BABOK NFR-016 / FR-018 — method: testProcessTransactionSuccessfully */
    public function testProcessTransactionSuccessfully()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'C'];

        ob_start();
        $this->app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('success=1', $output);

        // Verify database state
        $transaction = $this->repository->find(1);
        $this->assertEquals('processed', $transaction['status']);
    }

    /** @BABOK NFR-016 / FR-018 — method: testDisplaysValidationErrors */
    public function testDisplaysValidationErrors()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['ProcessTransaction'] = [1 => 'Process'];
        $_POST['partnerType'] = [1 => 'INVALID'];

        ob_start();
        $this->app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid transaction type', $output);
    }

    /** @BABOK NFR-016 / FR-018 — method: tearDown */
    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_clean();
    }
}
