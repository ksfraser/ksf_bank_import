<?php

namespace Tests\Integration;

use Ksfraser\FaBankImport\Repositories\TransactionRepository;

class TransactionRepositoryIntegrationTest extends DatabaseTestCase
{
    private $repository;
    private $testTransactionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionRepository();
    }

    protected function seedTestData(): void
    {
        $this->testTransactionId = $this->createTestTransaction([
            'amount' => 100.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test transaction',
            'transactionDC' => 'C',
            'status' => 'pending'
        ]);
    }

    protected function cleanTestData(): void
    {
        $sql = "DELETE FROM bi_transactions WHERE id = ?";
        self::$pdo->prepare($sql)->execute([$this->testTransactionId]);
    }

    public function testFindByIdReturnsCorrectTransaction()
    {
        $transaction = $this->repository->find($this->testTransactionId);

        $this->assertNotNull($transaction);
        $this->assertEquals(100.00, $transaction['amount']);
        $this->assertEquals('Test transaction', $transaction['memo']);
    }

    public function testUpdateTransactionStatus()
    {
        $success = $this->repository->update($this->testTransactionId, [
            'status' => 'processed'
        ]);

        $this->assertTrue($success);

        $transaction = $this->repository->find($this->testTransactionId);
        $this->assertEquals('processed', $transaction['status']);
    }

    public function testFindByStatusReturnsPendingTransactions()
    {
        $transactions = $this->repository->findBy(['status' => 'pending']);

        $this->assertNotEmpty($transactions);
        $this->assertContains(
            $this->testTransactionId,
            array_column($transactions, 'id')
        );
    }
}
