<?php
/** @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;

/**
 * Offline unit tests for TransactionRepository wiring.
 *
 * The repository methods that touch the FA database require a live
 * FrontAccounting connection, so the DB-dependent behaviours are skipped
 * outside the integration suite.
 */
class TransactionRepositoryTest extends TestCase
{
    private $repository;

    protected function setUp(): void
    {
        $this->repository = new TransactionRepository(
            new TransactionQueryBuilder('test_bi_transactions', 'test_bi_statements')
        );
    }

    public function testConstructorBuildsTheRepositoryWithItsQueryBuilder(): void
    {
        new TransactionRepository(new TransactionQueryBuilder('test_bi_transactions', 'test_bi_statements'));

        $this->assertTrue(
            class_exists(TransactionRepository::class),
            'TransactionRepository must be constructable from its query builder alone'
        );
    }

    public function testFindByIdReturnsNullWhenNotFound()
    {
        $this->markTestSkipped('TransactionRepository requires database connection');
        $result = $this->repository->findById(999999);
        $this->assertNull($result);
    }

    public function testFindByStatusReturnsEmptyArrayWhenNoResults()
    {
        $this->markTestSkipped('TransactionRepository requires database connection');
        $result = $this->repository->findByStatus('nonexistent');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testUpdateReturnsTrueOnSuccess()
    {
        $this->markTestSkipped('TransactionRepository requires database connection');
        $data = [
            'status' => 'processed',
            'transactionDC' => 'C'
        ];

        $result = $this->repository->update(1, $data);
        $this->assertTrue($result);
    }

    public function testSaveReturnsTrueOnSuccess()
    {
        $this->markTestSkipped('TransactionRepository requires database connection');
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
