<?php

namespace Tests\Unit\Import\Queries;

use Ksfraser\FaBankImport\Import\Queries\InsertBiTransaction;
use Ksfraser\FaBankImport\Import\Queries\MarkFileProcessed;
use Ksfraser\FaBankImport\Import\Queries\UpdateTransactionStatus;
use Ksfraser\FaBankImport\Import\Services\TransactionDatabaseManager;
use Ksfraser\FaBankImport\Import\Exceptions\BankTransferException;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class QueryClassesTest extends TestCase
{
    private MockObject $dbManager;

    protected function setUp(): void
    {
        $this->dbManager = $this->createMock(TransactionDatabaseManager::class);
        $this->dbManager->method('isActive')->willReturn(true);
        $this->dbManager->method('createSavepoint')->willReturn(null);
        $this->dbManager->method('rollbackToSavepoint')->willReturn(null);
    }

    /**
     * Test MarkFileProcessed with valid data.
     *
     * @test
     */
    public function testMarkFileProcessedWithValidData(): void
    {
        $marker = new MarkFileProcessed($this->dbManager);

        // Mock db_query to return success
        $GLOBALS['test_db_query_result'] = 1;

        $result = $marker->execute(1, 'processed', ['import_count' => 10, 'duration' => 5000]);

        $this->assertNotEmpty($result->getData());
        $this->assertEquals(1, $result->getData()['file_id']);
        $this->assertEquals('processed', $result->getData()['status']);
    }

    /**
     * Test MarkFileProcessed with invalid file ID.
     *
     * @test
     */
    public function testMarkFileProcessedWithInvalidFileId(): void
    {
        $this->expectException(\Ksfraser\FaBankImport\Import\Exceptions\TransactionProcessingException::class);

        $marker = new MarkFileProcessed($this->dbManager);
        $marker->execute(0, 'processed');
    }

    /**
     * Test UpdateTransactionStatus with valid transition.
     *
     * @test
     */
    public function testUpdateTransactionStatusValidTransition(): void
    {
        $updater = new UpdateTransactionStatus($this->dbManager);

        // Test valid transitions
        $validTransitions = $updater->getValidTransitions('pending');
        $this->assertContains('processing', $validTransitions);
        $this->assertContains('skipped', $validTransitions);
    }

    /**
     * Test UpdateTransactionStatus retrieved valid transitions.
     *
     * @test
     */
    public function testUpdateTransactionStatusTransitionMap(): void
    {
        $updater = new UpdateTransactionStatus($this->dbManager);

        // Verify specific transition paths
        $this->assertContains('completed', $updater->getValidTransitions('processing'));
        $this->assertContains('failed', $updater->getValidTransitions('processing'));

        $this->assertEmpty($updater->getValidTransitions('cancelled'));
    }

    /**
     * Test **CRITICAL**: InsertBiTransaction detects same-account transfer.
     *
     * @test
     */
    public function testInsertBiTransactionDetectsSameAccountTransfer(): void
    {
        $this->expectException(BankTransferException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'TXN-001',
            'type' => 'TRANSFER',
            'from_account_id' => 100,
            'to_account_id' => 100  // SAME Account = ERROR
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test InsertBiTransaction allows transfer between different accounts.
     *
     * @test
     */
    public function testInsertBiTransactionAllowsDifferentAccountTransfer(): void
    {
        $inserter = new InsertBiTransaction($this->dbManager);

        // This should NOT throw exception for different accounts
        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'TXN-001',
            'type' => 'TRANSFER',
            'from_account_id' => 100,
            'to_account_id' => 200,  // DIFFERENT accounts = OK
            'counterparty_name' => 'Other Bank'
        ];

        // Note: This will still fail in actual DB implementation, but should not throw
        // same-account exception. We're just testing the validation logic here.
        try {
            // Would throw validation or processing exception, NOT BankTransferException
            $inserter->execute($transactionData, 1, 100);
        } catch (BankTransferException $e) {
            $this->fail('Should not throw BankTransferException for different accounts');
        } catch (\Exception $e) {
            // Other exceptions are expected (missing DB, etc)
            $this->assertNotInstanceOf(BankTransferException::class, $e);
        }
    }

    /**
     * Test InsertBiTransaction with missing required field.
     *
     * @test
     */
    public function testInsertBiTransactionMissingRequiredField(): void
    {
        $this->expectException(TransactionValidationException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '2025-01-15',
            // Missing 'amount'
            'reference' => 'TXN-001'
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test InsertBiTransaction with zero amount.
     *
     * @test
     */
    public function testInsertBiTransactionZeroAmount(): void
    {
        $this->expectException(TransactionValidationException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 0,
            'reference' => 'TXN-001'
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test InsertBiTransaction with amount exceeding maximum.
     *
     * @test
     */
    public function testInsertBiTransactionAmountExceedsMax(): void
    {
        $this->expectException(TransactionValidationException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 1000000000.00,  // Exceeds max
            'reference' => 'TXN-001'
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test InsertBiTransaction with invalid date format.
     *
     * @test
     */
    public function testInsertBiTransactionInvalidDateFormat(): void
    {
        $this->expectException(TransactionValidationException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '01-15-2025',  // Wrong format
            'amount' => 100.00,
            'reference' => 'TXN-001'
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test InsertBiTransaction with non-numeric amount.
     *
     * @test
     */
    public function testInsertBiTransactionNonNumericAmount(): void
    {
        $this->expectException(TransactionValidationException::class);

        $inserter = new InsertBiTransaction($this->dbManager);

        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 'ABC',  // Not numeric
            'reference' => 'TXN-001'
        ];

        $inserter->execute($transactionData, 1, 100);
    }

    /**
     * Test non-transfer transaction ignores same-account check.
     *
     * @test
     */
    public function testNonTransferIgnoresSameAccountCheck(): void
    {
        $inserter = new InsertBiTransaction($this->dbManager);

        // Deposit type should not trigger same-account check even if from/to same
        $transactionData = [
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'DEP-001',
            'type' => 'DEPOSIT',
            'from_account_id' => 100,
            'to_account_id' => 100,
            'counterparty_name' => 'Bank'
        ];

        // Should not throw BankTransferException
        try {
            $inserter->execute($transactionData, 1, 100);
        } catch (BankTransferException $e) {
            $this->fail('Should not throw BankTransferException for non-transfer type');
        } catch (\Exception $e) {
            // Other exceptions OK (DB errors, etc)
            $this->assertNotInstanceOf(BankTransferException::class, $e);
        }
    }
}
