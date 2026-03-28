<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException;
use PHPUnit\Framework\TestCase;

class TransactionDatabaseManagerTest extends TestCase
{
    private TransactionDatabaseManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TransactionDatabaseManager();
    }

    /**
     * Test starting a transaction.
     *
     * @test
     */
    public function testStartTransaction(): void
    {
        $this->assertFalse($this->manager->isActive());

        $this->manager->startTransaction();

        $this->assertTrue($this->manager->isActive());
    }

    /**
     * Test cannot create savepoint without active transaction.
     *
     * @test
     */
    public function testCannotCreateSavepointWithoutTransaction(): void
    {
        $this->expectException(TransactionProcessingException::class);

        $this->manager->createSavepoint();
    }

    /**
     * Test creating savepoint during transaction.
     *
     * @test
     */
    public function testCreateSavepointDuringTransaction(): void
    {
        $this->manager->startTransaction();

        $savepointName = $this->manager->createSavepoint();

        $this->assertNotEmpty($savepointName);
        $this->assertEquals(1, $this->manager->getSavepointDepth());
    }

    /**
     * Test multiple savepoints.
     *
     * @test
     */
    public function testMultipleSavepoints(): void
    {
        $this->manager->startTransaction();

        $sp1 = $this->manager->createSavepoint();
        $sp2 = $this->manager->createSavepoint();
        $sp3 = $this->manager->createSavepoint();

        $this->assertEquals(3, $this->manager->getSavepointDepth());
    }

    /**
     * Test named savepoint.
     *
     * @test
     */
    public function testNamedSavepoint(): void
    {
        $this->manager->startTransaction();

        $spName = $this->manager->createSavepoint('my_savepoint');

        $this->assertEquals('my_savepoint', $spName);
    }

    /**
     * Test commit clears transaction state.
     *
     * @test
     */
    public function testCommit(): void
    {
        $this->manager->startTransaction();
        $this->manager->createSavepoint();

        $this->assertTrue($this->manager->isActive());

        $this->manager->commit();

        $this->assertFalse($this->manager->isActive());
        $this->assertEquals(0, $this->manager->getSavepointDepth());
    }

    /**
     * Test rollback clears transaction state.
     *
     * @test
     */
    public function testRollback(): void
    {
        $this->manager->startTransaction();
        $this->manager->createSavepoint();

        $this->assertTrue($this->manager->isActive());

        $this->manager->rollback();

        $this->assertFalse($this->manager->isActive());
        $this->assertEquals(0, $this->manager->getSavepointDepth());
    }

    /**
     * Test rollback with exception.
     *
     * @test
     */
    public function testRollbackWithException(): void
    {
        $this->manager->startTransaction();

        $exception = new \Exception('Test error');
        $this->manager->rollback($exception);

        $this->assertFalse($this->manager->isActive());
    }

    /**
     * Test commit without transaction is safe.
     *
     * @test
     */
    public function testCommitWithoutTransactionIsSafe(): void
    {
        $this->manager->commit();
        $this->assertFalse($this->manager->isActive());
    }

    /**
     * Test rollback without transaction is safe.
     *
     * @test
     */
    public function testRollbackWithoutTransactionIsSafe(): void
    {
        $this->manager->rollback();
        $this->assertFalse($this->manager->isActive());
    }
}
