<?php

namespace Tests\Integration;

use Ksfraser\FaBankImport\Repositories\TransactionRepository;

class TransactionRepositoryIntegrationTest extends DatabaseTestCase
{
    private $repository;
    private $testTransactionId;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionRepository();
    }

    /** @BABOK NFR-016 / FR-018 — method: seedTestData */
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

    /** @BABOK NFR-016 / FR-018 — method: cleanTestData */
    protected function cleanTestData(): void
    {
        $sql = "DELETE FROM bi_transactions WHERE id = ?";
        self::$pdo->prepare($sql)->execute([$this->testTransactionId]);
    }

    /** @BABOK NFR-016 / FR-018 — method: testFindByIdReturnsCorrectTransaction */
    public function testFindByIdReturnsCorrectTransaction()
    {
        $transaction = $this->repository->find($this->testTransactionId);

        $this->assertNotNull($transaction);
        $this->assertEquals(100.00, $transaction['amount']);
        $this->assertEquals('Test transaction', $transaction['memo']);
    }

    /** @BABOK NFR-016 / FR-018 — method: testUpdateTransactionStatus */
    public function testUpdateTransactionStatus()
    {
        $success = $this->repository->update($this->testTransactionId, [
            'status' => 'processed'
        ]);

        $this->assertTrue($success);

        $transaction = $this->repository->find($this->testTransactionId);
        $this->assertEquals('processed', $transaction['status']);
    }

    /** @BABOK NFR-016 / FR-018 — method: testFindByStatusReturnsPendingTransactions */
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
