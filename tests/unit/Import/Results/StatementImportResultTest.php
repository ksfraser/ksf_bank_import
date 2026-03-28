<?php

namespace Tests\Unit\Import\Results;

use Ksfraser\FaBankImport\Import\Results\StatementImportResult;
use PHPUnit\Framework\TestCase;

class StatementImportResultTest extends TestCase
{
    /**
     * Test successful import result creation.
     *
     * @test
     */
    public function testSuccessfulImportResult(): void
    {
        $result = StatementImportResult::successfulImport(
            statementId: 42,
            importedCount: 10,
            skippedCount: 2,
            failedCount: 1
        );

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(42, $result->getStatementId());
        $this->assertEquals(10, $result->getImportedTransactionCount());
        $this->assertEquals(2, $result->getSkippedTransactionCount());
        $this->assertEquals(1, $result->getFailedTransactionCount());
    }

    /**
     * Test failed import result.
     *
     * @test
     */
    public function testFailedImportResult(): void
    {
        $result = StatementImportResult::importFailed('Statement validation failed', 3, 2);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(3, $result->getSkippedTransactionCount());
        $this->assertEquals(2, $result->getFailedTransactionCount());
    }

    /**
     * Test recording imported transactions.
     *
     * @test
     */
    public function testRecordImportedTransactions(): void
    {
        $result = StatementImportResult::successfulImport(1);

        $result->recordImportedTransaction(100)
            ->recordImportedTransaction(101)
            ->recordImportedTransaction(102);

        $this->assertEquals(3, $result->getImportedTransactionCount());
        $this->assertEquals([100, 101, 102], $result->getImportedTransactionIds());
    }

    /**
     * Test recording skipped transactions.
     *
     * @test
     */
    public function testRecordSkippedTransactions(): void
    {
        $result = StatementImportResult::successfulImport(1);

        $result->recordSkippedTransaction(200, 'Invalid amount')
            ->recordSkippedTransaction(201, 'Missing counterparty');

        $this->assertEquals(2, $result->getSkippedTransactionCount());
        $failed = $result->getFailedTransactions();
        $this->assertEquals('skipped', $failed[200]['status']);
        $this->assertEquals('Invalid amount', $failed[200]['reason']);
    }

    /**
     * Test recording failed transactions.
     *
     * @test
     */
    public function testRecordFailedTransactions(): void
    {
        $result = StatementImportResult::successfulImport(1);

        $result->recordFailedTransaction(300, 'Database error');

        $this->assertEquals(1, $result->getFailedTransactionCount());
        $failed = $result->getFailedTransactions();
        $this->assertEquals('failed', $failed[300]['status']);
    }
}
