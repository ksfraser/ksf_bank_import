<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;

class TransactionRepositoryTest extends TestCase
{
    private $repository;

    /** @BABOK NFR-016 / FR-018 — method: setUp */
    protected function setUp(): void
    {
        $this->repository = new TransactionRepository();
    }

    /** @BABOK NFR-016 / FR-018 — method: testFindByIdReturnsNullWhenNotFound */
    public function testFindByIdReturnsNullWhenNotFound()
    {
        $result = $this->repository->findById(999999);
        $this->assertNull($result);
    }

    /** @BABOK NFR-016 / FR-018 — method: testFindByStatusReturnsEmptyArrayWhenNoResults */
    public function testFindByStatusReturnsEmptyArrayWhenNoResults()
    {
        $result = $this->repository->findByStatus('nonexistent');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @BABOK NFR-016 / FR-018 — method: testUpdateReturnsTrueOnSuccess */
    public function testUpdateReturnsTrueOnSuccess()
    {
        $data = [
            'status' => 'processed',
            'transactionDC' => 'C'
        ];

        $result = $this->repository->update(1, $data);
        $this->assertTrue($result);
    }

    /** @BABOK NFR-016 / FR-018 — method: testSaveReturnsTrueOnSuccess */
    public function testSaveReturnsTrueOnSuccess()
    {
        $transaction = [
            'amount' => 100.00,
            'valueTimestamp' => '2025-05-22',
            'memo' => 'Test transaction',
            'status' => 'pending'
        ];

        $result = $this->repository->save($transaction);
        $this->assertTrue($result);
    }
}
