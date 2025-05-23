<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;

class TransactionRepositoryTest extends TestCase
{
    private $repository;

    protected function setUp(): void
    {
        $this->repository = new TransactionRepository();
    }

    public function testFindByIdReturnsNullWhenNotFound()
    {
        $result = $this->repository->findById(999999);
        $this->assertNull($result);
    }

    public function testFindByStatusReturnsEmptyArrayWhenNoResults()
    {
        $result = $this->repository->findByStatus('nonexistent');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testUpdateReturnsTrueOnSuccess()
    {
        $data = [
            'status' => 'processed',
            'transactionDC' => 'C'
        ];

        $result = $this->repository->update(1, $data);
        $this->assertTrue($result);
    }

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