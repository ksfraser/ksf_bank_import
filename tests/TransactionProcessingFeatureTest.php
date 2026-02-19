<?php

namespace Tests\Feature;

use Tests\Integration\DatabaseTestCase;
use Ksfraser\FaBankImport\Application;

class TransactionProcessingFeatureTest extends DatabaseTestCase
{
    private $app;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION['user_id'] = 1;
        $this->app = new Application();
        $this->repository = $this->app->getTransactionRepository();
        $this->createTestTransaction([
            'amount' => 500.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test Payment',
            'transactionDC' => 'C',
            'status' => 'pending'
        ]);
    }

    public function testDisplaysTransactionList()
    {
        ob_start();
        $this->app->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Payment', $output);
        $this->assertStringContainsString('500.00', $output);
        $this->assertStringContainsString('Process', $output);
    }

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

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_clean();
    }

    protected function createTestTransaction(array $data): void
    {
        // This should insert a test transaction into the database for testing
        // Implementation depends on your repository/database setup
        $this->repository->insert([
            'id' => 1,
            'amount' => $data['amount'],
            'valueTimestamp' => $data['valueTimestamp'],
            'memo' => $data['memo'],
            'transactionDC' => $data['transactionDC'],
            'status' => $data['status'],
        ]);
    }
}
// Duplicate removed by Copilot. Canonical version is in src/Ksfraser/FaBankImport/tests/TransactionProcessingFeatureTest.php
