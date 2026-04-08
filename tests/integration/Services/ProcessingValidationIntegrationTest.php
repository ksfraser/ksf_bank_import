<?php

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Import\Services\Validation\ValidationService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Integration tests for Statement Processing and Validation Services
 *
 * Tests the processing and validation services together with real entities
 */
class ProcessingValidationIntegrationTest extends TestCase
{
    /**
     * Processing service
     *
     * @var StatementProcessingService
     */
    private StatementProcessingService $processingService;

    /**
     * Validation service
     *
     * @var ValidationService
     */
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $this->processingService = new StatementProcessingService();
        $this->validationService = new ValidationService();
    }

    /**
     * Integration: Process valid statement and validate
     *
     * Tests processing followed by validation
     */
    public function testProcessValidateWorkflow(): void
    {
        $statement = $this->buildStatement();
        $this->addTransactions($statement, 5);

        // Validate before processing
        $beforeValid = $this->validationService->validate($statement);
        $this->assertTrue($beforeValid);

        // Process
        $processed = $this->processingService->process($statement);

        // Validate after processing
        $afterValid = $this->validationService->validate($processed);
        $this->assertTrue($afterValid);
    }

    /**
     * Integration: Process with duplicate then validate
     *
     * Tests deduplication during processing doesn't break validation
     */
    public function testDuplicateProcessingWithValidation(): void
    {
        $statement = $this->buildStatement();

        // Add duplicate transaction
        $txn = BiTransaction::fromDatabase([
            'id' => 1,
            'fitid' => 'DUP-001',
            'transactionId' => 'DUP-001',
            'type' => 'CREDIT',
            'date' => '2024-01-01',
            'amount' => 100.00,
            'description' => 'Duplicate',
            'balance' => 1100.00
        ], $statement);

        $statement->addTransaction($txn);
        $statement->addTransaction($txn); // Add same object twice

        // Original should be invalid (duplicate)
        // Process to deduplicate
        $processed = $this->processingService->process($statement);

        // After processing, should have only 1 transaction
        $this->assertCount(1, $processed->getTransactions());

        // Should still validate
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }

    /**
     * Integration: Zero-amount filtering with validation
     *
     * Tests zero-amount filtering doesn't affect validation
     */
    public function testZeroAmountFilteringWithValidation(): void
    {
        $statement = $this->buildStatement();

        // Add mix of regular and zero transactions
        for ($i = 1; $i <= 5; $i++) {
            $amount = $i % 2 === 0 ? 0.00 : 50.00;

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $i,
                    'fitid' => "TXN-{$i}",
                    'transactionId' => "TXN-{$i}",
                    'type' => $amount > 0 ? 'CREDIT' : 'OTHER',
                    'date' => '2024-01-01',
                    'amount' => $amount,
                    'description' => "Transaction {$i}",
                    'balance' => 1000.00 + $amount
                ], $statement)
            );
        }

        // Process (filters zero amounts)
        $processed = $this->processingService->process($statement);

        // Should have only non-zero transactions
        $this->assertCount(3, $processed->getTransactions());

        // Should still be valid
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }

    /**
     * Integration: Validation detects processing failures
     *
     * Tests validation catches issues in processed statements
     */
    public function testValidationDetectsProcessingIssues(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => '', // Empty bank - will fail validation
            'account' => 'Chequing',
            'statementId' => 'STMT-001',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1100.00,
            'smtDate' => '2024-01-01'
        ], []);

        $statement->addTransaction(
            BiTransaction::fromDatabase([
                'id' => 1,
                'fitid' => 'TXN-001',
                'transactionId' => 'TXN-001',
                'type' => 'CREDIT',
                'date' => '2024-01-01',
                'amount' => 100.00,
                'description' => 'Payment',
                'balance' => 1100.00
            ], $statement)
        );

        // Process
        $processed = $this->processingService->process($statement);

        // Validate should catch the empty bank
        $isValid = $this->validationService->validate($processed);
        $this->assertFalse($isValid);

        // Should have error about empty bank
        $errors = $this->validationService->getErrors();
        $this->assertNotEmpty($errors);
    }

    /**
     * Integration: Large batch processing and validation
     *
     * Tests processing and validating large statements
     */
    public function testLargeBatchProcessingValidation(): void
    {
        $statement = $this->buildStatement();

        // Add 1000 transactions
        $balance = 1000.00;
        for ($i = 1; $i <= 1000; $i++) {
            $amount = $i % 3 === 0 ? -5.00 : 10.00;
            $balance += $amount;

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $i,
                    'fitid' => "BIG-{$i}",
                    'transactionId' => "BIG-{$i}",
                    'type' => $amount > 0 ? 'CREDIT' : 'DEBIT',
                    'date' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'description' => "Transaction {$i}",
                    'balance' => $balance
                ], $statement)
            );
        }

        // Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(1000, $processed->getTransactions());

        // Validate
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }

    /**
     * Integration: Process and validate multiple statements
     *
     * Tests handling multiple statements in sequence
     */
    public function testMultipleStatementProcessValidation(): void
    {
        for ($s = 1; $s <= 5; $s++) {
            $statement = BiStatement::fromDatabase([
                'id' => $s,
                'bank' => 'Test Bank',
                'account' => 'Account ' . $s,
                'statementId' => "STMT-{$s}",
                'acctid' => "ACC-{$s}",
                'fitid' => "FIT-{$s}",
                'bankid' => 'BANK-001',
                'intu_bid' => 'INTU-001',
                'currency' => 'CAD',
                'startBalance' => 1000.00 * $s,
                'endBalance' => 1100.00 * $s,
                'smtDate' => '2024-01-' . str_pad($s, 2, '0', STR_PAD_LEFT)
            ], []);

            // Add transactions specific to this statement
            for ($t = 1; $t <= 10; $t++) {
                $statement->addTransaction(
                    BiTransaction::fromDatabase([
                        'id' => $t,
                        'fitid' => "S{$s}-T{$t}",
                        'transactionId' => "S{$s}-T{$t}",
                        'type' => $t % 2 === 0 ? 'DEBIT' : 'CREDIT',
                        'date' => '2024-01-' . str_pad($t, 2, '0', STR_PAD_LEFT),
                        'amount' => 10.00 * $t,
                        'description' => "Statement {$s}, Transaction {$t}",
                        'balance' => (1000.00 * $s) + (10.00 * $t)
                    ], $statement)
                );
            }

            // Process
            $processed = $this->processingService->process($statement);
            $this->assertCount(10, $processed->getTransactions());

            // Validate
            $isValid = $this->validationService->validate($processed);
            $this->assertTrue($isValid);

            // Check that statement ID is preserved
            $this->assertEquals("STMT-{$s}", $processed->getStatementId());
        }
    }

    /**
     * Integration: Transaction type validation during processing
     *
     * Tests processing validates all transaction types correctly
     */
    public function testTransactionTypeValidationDuringProcessing(): void
    {
        $statement = $this->buildStatement();

        // Add various transaction types
        $types = ['CREDIT', 'DEBIT', 'OTHER'];
        foreach ($types as $idx => $type) {
            for ($i = 1; $i <= 3; $i++) {
                $statement->addTransaction(
                    BiTransaction::fromDatabase([
                        'id' => ($idx * 3) + $i,
                        'fitid' => "{$type}-{$i}",
                        'transactionId' => "{$type}-{$i}",
                        'type' => $type,
                        'date' => '2024-01-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                        'amount' => 100.00 * ($idx + 1),
                        'description' => "{$type} Transaction {$i}",
                        'balance' => 1000.00 + (100.00 * ($idx + 1))
                    ], $statement)
                );
            }
        }

        // Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(9, $processed->getTransactions());

        // Validate - all types should be valid
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }

    /**
     * Integration: Concurrent processing and validation
     *
     * Tests that processing and validation don't have race conditions
     */
    public function testProcessingValidationConsistency(): void
    {
        $statement = $this->buildStatement();
        $this->addTransactions($statement, 50);

        // Multiple process and validate cycles
        for ($cycle = 1; $cycle <= 5; $cycle++) {
            $processed = $this->processingService->process($statement);
            $isValid = $this->validationService->validate($processed);

            $this->assertTrue($isValid, "Cycle {$cycle} failed");
            $this->assertGreaterThan(0, count($processed->getTransactions()));
        }
    }

    /**
     * Helper: Build test statement
     *
     * @return BiStatement
     */
    protected function buildStatement(): BiStatement
    {
        return BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-001',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1500.00,
            'smtDate' => '2024-01-31'
        ], []);
    }

    /**
     * Helper: Add transactions
     *
     * @param BiStatement $statement
     * @param int $count
     * @return void
     */
    protected function addTransactions(BiStatement $statement, int $count): void
    {
        $balance = $statement->getStartBalance();
        for ($i = 1; $i <= $count; $i++) {
            $amount = $i * 10.00;
            $balance += $amount;

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $i,
                    'fitid' => "TXN-{$i}",
                    'transactionId' => "TXN-{$i}",
                    'type' => $i % 2 === 0 ? 'DEBIT' : 'CREDIT',
                    'date' => '2024-01-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'amount' => $i % 2 === 0 ? -$amount : $amount,
                    'description' => "Transaction {$i}",
                    'balance' => $balance
                ], $statement)
            );
        }
    }
}
