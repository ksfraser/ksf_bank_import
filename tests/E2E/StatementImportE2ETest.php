<?php

namespace Tests\E2E\StatementImport;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Import\Services\Validation\ValidationService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * End-to-End tests for statement import workflow
 *
 * Tests complete workflows through the full import pipeline:
 * - Raw statement -> Processing -> Enrichment -> Validation -> Ready for import
 */
class StatementImportE2ETest extends TestCase
{
    /**
     * Statement processing service
     *
     * @var StatementProcessingService
     */
    private StatementProcessingService $processingService;

    /**
     * Statement enrichment service
     *
     * @var EnrichmentService
     */
    private EnrichmentService $enrichmentService;

    /**
     * Statement validation service
     *
     * @var ValidationService
     */
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $this->processingService = new StatementProcessingService();
        $this->enrichmentService = new EnrichmentService();
        $this->validationService = new ValidationService();
    }

    /**
     * E2E: Simple single-transaction import workflow
     *
     * Tests basic workflow with a single transaction
     */
    public function testSimpleSingleTransactionImportWorkflow(): void
    {
        // Step 1: Create raw statement with one transaction
        $statement = $this->createRawStatement('STMT-SIMPLE-001');
        $statement->addTransaction($this->createTransaction(1, 'TXN-001', 100.00, 'Order Payment'));

        // Step 2: Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(1, $processed->getTransactions());

        // Step 3: Enrich
        $enriched = $this->enrichmentService->enrich($processed);
        $this->assertNotNull($enriched);

        // Step 4: Validate - must be ready for import
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid, 'Statement failed validation: ' . implode(', ', $this->validationService->getErrors()));

        // Verify statement properties
        $this->assertEquals('Test Bank', $enriched->getBank());
        $this->assertEquals('Chequing', $enriched->getAccount());
        $this->assertNotEmpty($enriched->getStatementId());
    }

    /**
     * E2E: Multi-transaction import workflow
     *
     * Tests workflow with multiple transactions and balance tracking
     */
    public function testMultiTransactionImportWorkflow(): void
    {
        // Step 1: Create statement with multiple transactions
        $statement = $this->createRawStatement('STMT-MULTI-001');
        $this->addMultipleTransactions($statement, [
            ['id' => 1, 'fitid' => 'TXN-001', 'amount' => 1000.00, 'desc' => 'Opening Balance'],
            ['id' => 2, 'fitid' => 'TXN-002', 'amount' => -50.00, 'desc' => 'Withdrawal'],
            ['id' => 3, 'fitid' => 'TXN-003', 'amount' => 75.00, 'desc' => 'Deposit'],
            ['id' => 4, 'fitid' => 'TXN-004', 'amount' => -25.00, 'desc' => 'Fee'],
        ]);

        // Step 2: Process - should validate all transactions
        $processed = $this->processingService->process($statement);
        $this->assertCount(4, $processed->getTransactions());

        // Step 3: Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Step 4: Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);

        // Verify transactions remained intact
        $this->assertCount(4, $enriched->getTransactions());
    }

    /**
     * E2E: Import with duplicate detection
     *
     * Tests that duplicate transactions are handled during processing
     */
    public function testImportWorkflowWithDuplicateDetection(): void
    {
        $statement = $this->createRawStatement('STMT-DUP-001');

        // Add same transaction twice
        $duplicate = $this->createTransaction(1, 'TXN-DUP-001', 100.00, 'Duplicate Payment');
        $statement->addTransaction($duplicate);
        $statement->addTransaction($duplicate);

        // Also add unique transactions
        $statement->addTransaction($this->createTransaction(2, 'TXN-UNIQUE-001', 50.00, 'Unique Payment'));

        // Process should deduplicate
        $processed = $this->processingService->process($statement);

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);

        // Should have 2 transactions after deduplication
        $this->assertCount(2, $enriched->getTransactions());
    }

    /**
     * E2E: Import workflow with zero-amount filtering
     *
     * Tests that zero-amount transactions are filtered during processing
     */
    public function testImportWorkflowWithZeroAmountFiltering(): void
    {
        $statement = $this->createRawStatement('STMT-ZERO-FILTER-001');

        // Mix of regular and zero-amount transactions
        $statement->addTransaction($this->createTransaction(1, 'TXN-001', 100.00, 'Regular Payment'));
        $statement->addTransaction($this->createTransaction(2, 'TXN-ZERO', 0.00, 'Zero Amount'));
        $statement->addTransaction($this->createTransaction(3, 'TXN-002', 50.00, 'Another Payment'));
        $statement->addTransaction($this->createTransaction(4, 'TXN-ZERO-2', 0.00, 'Another Zero'));

        // Process should filter out zero amounts
        $processed = $this->processingService->process($statement);

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);

        // Should have only 2 transactions after filtering
        $this->assertCount(2, $enriched->getTransactions());
    }

    /**
     * E2E: Large statement import workflow
     *
     * Tests workflow with large number of transactions
     */
    public function testLargeStatementImportWorkflow(): void
    {
        $statement = $this->createRawStatement('STMT-LARGE-001');

        // Add 100 transactions
        $balance = 1000.00;
        for ($i = 1; $i <= 100; $i++) {
            $amount = ($i % 3) === 0 ? -10.00 : 5.00;
            $balance += $amount;

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $i,
                    'fitid' => "TXN-{$i}",
                    'transactionId' => "TXN-{$i}",
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
        $this->assertCount(100, $processed->getTransactions());

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);

        // All 100 transactions should be preserved
        $this->assertCount(100, $enriched->getTransactions());
    }

    /**
     * E2E: Transaction type validation workflow
     *
     * Tests that various transaction types are handled correctly
     */
    public function testTransactionTypeValidationWorkflow(): void
    {
        $statement = $this->createRawStatement('STMT-TYPES-001');

        // Different transaction types
        $types = ['CREDIT', 'DEBIT', 'OTHER'];
        foreach ($types as $idx => $type) {
            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $idx + 1,
                    'fitid' => "TXN-{$type}-1",
                    'transactionId' => "TXN-{$type}-1",
                    'type' => $type,
                    'date' => '2024-01-' . str_pad($idx + 1, 2, '0', STR_PAD_LEFT),
                    'amount' => 100.00,
                    'description' => "{$type} Transaction",
                    'balance' => 1100.00 + ($idx * 100)
                ], $statement)
            );
        }

        // Process
        $processed = $this->processingService->process($statement);

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate - all types should be valid
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);

        $this->assertCount(3, $enriched->getTransactions());
    }

    /**
     * E2E: Currency validation workflow
     *
     * Tests statement import with different currency codes
     */
    public function testCurrencyValidationWorkflow(): void
    {
        foreach (['CAD', 'USD', 'EUR'] as $currency) {
            $statement = BiStatement::fromDatabase([
                'id' => 1,
                'bank' => 'Test Bank',
                'account' => "{$currency} Account",
                'statementId' => "STMT-{$currency}-001",
                'acctid' => "ACC-{$currency}-001",
                'fitid' => "FIT-{$currency}-001",
                'bankid' => 'BANK-001',
                'intu_bid' => 'INTU-001',
                'currency' => $currency,
                'startBalance' => 1000.00,
                'endBalance' => 1100.00,
                'smtDate' => '2024-01-31'
            ], []);

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => 1,
                    'fitid' => 'TXN-001',
                    'transactionId' => 'TXN-001',
                    'type' => 'CREDIT',
                    'date' => '2024-01-15',
                    'amount' => 100.00,
                    'description' => 'Payment',
                    'balance' => 1100.00
                ], $statement)
            );

            // Process
            $processed = $this->processingService->process($statement);

            // Enrich
            $enriched = $this->enrichmentService->enrich($processed);

            // Validate
            $isValid = $this->validationService->validate($enriched);
            $this->assertTrue($isValid, "Currency {$currency} failed validation");
            $this->assertEquals($currency, $enriched->getCurrency());
        }
    }

    /**
     * E2E: End-to-end workflow idempotency
     *
     * Tests that running the workflow multiple times produces same result
     */
    public function testWorkflowIdempotency(): void
    {
        // Create original statement
        $statement = $this->createRawStatement('STMT-IDEMPOTENT-001');
        $this->addMultipleTransactions($statement, [
            ['id' => 1, 'fitid' => 'TXN-001', 'amount' => 100.00, 'desc' => 'Payment 1'],
            ['id' => 2, 'fitid' => 'TXN-002', 'amount' => 50.00, 'desc' => 'Payment 2'],
        ]);

        // First run
        $run1 = $statement;
        $run1 = $this->processingService->process($run1);
        $run1 = $this->enrichmentService->enrich($run1);
        $valid1 = $this->validationService->validate($run1);

        // Second run on same statement
        $run2 = $statement;
        $run2 = $this->processingService->process($run2);
        $run2 = $this->enrichmentService->enrich($run2);
        $valid2 = $this->validationService->validate($run2);

        // Third run
        $run3 = $statement;
        $run3 = $this->processingService->process($run3);
        $run3 = $this->enrichmentService->enrich($run3);
        $valid3 = $this->validationService->validate($run3);

        // All runs should have same validity
        $this->assertTrue($valid1);
        $this->assertTrue($valid2);
        $this->assertTrue($valid3);

        // All should have same transaction count
        $count1 = count($run1->getTransactions());
        $count2 = count($run2->getTransactions());
        $count3 = count($run3->getTransactions());

        $this->assertEquals($count1, $count2);
        $this->assertEquals($count2, $count3);
    }

    /**
     * Helper: Create raw statement for testing
     *
     * @param string $statementId
     * @return BiStatement
     */
    protected function createRawStatement(string $statementId): BiStatement
    {
        return BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => $statementId,
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1100.00,
            'smtDate' => '2024-01-31'
        ], []);
    }

    /**
     * Helper: Create transaction
     *
     * @param int $id
     * @param string $fitId
     * @param float $amount
     * @param string $description
     * @return BiTransaction
     */
    protected function createTransaction(int $id, string $fitId, float $amount, string $description): BiTransaction
    {
        return BiTransaction::fromDatabase([
            'id' => $id,
            'fitid' => $fitId,
            'transactionId' => $fitId,
            'type' => $amount >= 0 ? 'CREDIT' : 'DEBIT',
            'date' => '2024-01-15',
            'amount' => $amount,
            'description' => $description,
            'balance' => 1100.00
        ], null); // Balance will be set by statement
    }

    /**
     * Helper: Add multiple transactions
     *
     * @param BiStatement $statement
     * @param array $transactions Array of transaction specs
     * @return void
     */
    protected function addMultipleTransactions(BiStatement $statement, array $transactions): void
    {
        $balance = $statement->getStartBalance();
        foreach ($transactions as $txn) {
            $balance += $txn['amount'];

            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $txn['id'],
                    'fitid' => $txn['fitid'],
                    'transactionId' => $txn['fitid'],
                    'type' => $txn['amount'] >= 0 ? 'CREDIT' : 'DEBIT',
                    'date' => '2024-01-15',
                    'amount' => $txn['amount'],
                    'description' => $txn['desc'],
                    'balance' => $balance
                ], $statement)
            );
        }
    }
}
