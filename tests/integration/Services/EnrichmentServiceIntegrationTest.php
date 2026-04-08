<?php

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Import\Services\Validation\ValidationService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Integration tests for EnrichmentService with other services
 *
 * Tests the enrichment service in combination with:
 * - Statement processing
 * - Validation
 * - Real entity interactions
 */
class EnrichmentServiceIntegrationTest extends TestCase
{
    /**
     * Enrichment service
     *
     * @var EnrichmentService
     */
    private EnrichmentService $enrichmentService;

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
        $this->enrichmentService = new EnrichmentService();
        $this->processingService = new StatementProcessingService();
        $this->validationService = new ValidationService();
    }

    /**
     * Integration: Enrich then validate statement
     *
     * Tests the workflow: Enrich -> Validate
     */
    public function testEnrichThenValidateWorkflow(): void
    {
        $statement = $this->buildStatement();

        // Enrich the statement
        $enriched = $this->enrichmentService->enrich($statement);

        // Validate the enriched statement
        $isValid = $this->validationService->validate($enriched);

        // Should still be valid after enrichment
        $this->assertTrue($isValid);
        $this->assertEquals('CAD', $enriched->getCurrency());
    }

    /**
     * Integration: Process then enrich then validate
     *
     * Tests the workflow: Process -> Enrich -> Validate
     */
    public function testProcessEnrichValidateWorkflow(): void
    {
        $statement = $this->buildStatement();
        $this->addTransactions($statement, 5);

        // Process the statement
        $processed = $this->processingService->process($statement);
        $this->assertGreaterThanOrEqual(4, count($processed->getTransactions()));

        // Enrich the processed statement
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate the enriched statement
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
    }

    /**
     * Integration: Multiple enrichment passes
     *
     * Tests running enrichment multiple times on same statement
     */
    public function testMultipleEnrichmentPasses(): void
    {
        $statement = $this->buildStatement();
        $this->addTransactions($statement, 3);

        // First enrichment pass
        $enriched1 = $this->enrichmentService->enrich($statement);
        $this->assertTrue($this->validationService->validate($enriched1));

        // Second enrichment pass (should be idempotent)
        $enriched2 = $this->enrichmentService->enrich($enriched1);
        $this->assertTrue($this->validationService->validate($enriched2));

        // Third enrichment pass (should still be valid)
        $enriched3 = $this->enrichmentService->enrich($enriched2);
        $this->assertTrue($this->validationService->validate($enriched3));
    }

    /**
     * Integration: Enrich with processing service errors
     *
     * Tests enrichment continues even if transaction processing fails
     */
    public function testEnrichmentResilientToProcessingErrors(): void
    {
        $statement = $this->buildStatement();

        // Add transaction with zero amount (will be filtered)
        $statement->addTransaction(
            BiTransaction::fromDatabase([
                'id' => 1,
                'fitid' => 'TXN-ZERO',
                'transactionId' => 'TXN-ZERO',
                'type' => 'OTHER',
                'date' => '2024-01-01',
                'amount' => 0.00,
                'description' => 'Zero Amount',
                'balance' => 1000.00
            ], $statement)
        );

        // Add valid transaction
        $statement->addTransaction(
            BiTransaction::fromDatabase([
                'id' => 2,
                'fitid' => 'TXN-VALID',
                'transactionId' => 'TXN-VALID',
                'type' => 'CREDIT',
                'date' => '2024-01-01',
                'amount' => 100.00,
                'description' => 'Valid Transaction',
                'balance' => 1100.00
            ], $statement)
        );

        // Process (removes zero-amount transaction)
        $processed = $this->processingService->process($statement);

        // Enrich (should handle removal gracefully)
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate
        $this->assertTrue($this->validationService->validate($enriched));
    }

    /**
     * Integration: Enrichment with foreign currency statement
     *
     * Tests enrichment recognizes and handles foreign currency statements
     */
    public function testEnrichmentWithForeignCurrency(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Foreign Bank',
            'account' => 'USD Account',
            'statementId' => 'STMT-USD-001',
            'acctid' => 'ACC-USD-001',
            'fitid' => 'FIT-USD-001',
            'bankid' => 'BANK-USD',
            'intu_bid' => 'INTU-USD',
            'currency' => 'USD', // Foreign currency
            'startBalance' => 5000.00,
            'endBalance' => 5500.00,
            'smtDate' => '2024-01-31'
        ], []);

        // Add USD transactions
        $statement->addTransaction(
            BiTransaction::fromDatabase([
                'id' => 1,
                'fitid' => 'USD-TXN-001',
                'transactionId' => 'USD-TXN-001',
                'type' => 'CREDIT',
                'date' => '2024-01-15',
                'amount' => 500.00,
                'description' => 'USD Payment',
                'balance' => 5500.00
            ], $statement)
        );

        // Process
        $processed = $this->processingService->process($statement);

        // Enrich (may attempt exchange rate lookup)
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate (currency-specific validation)
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
        $this->assertEquals('USD', $enriched->getCurrency());
    }

    /**
     * Integration: Full statement lifecycle
     *
     * Tests complete lifecycle: Process -> Enrich -> Validate
     * with multiple transactions and various edge cases
     */
    public function testFullStatementLifecycle(): void
    {
        $statement = $this->buildStatement();

        // Mix of transaction types
        $transactions = [
            ['type' => 'CREDIT', 'amount' => 100.00, 'desc' => 'Deposit'],
            ['type' => 'DEBIT', 'amount' => -50.00, 'desc' => 'Withdrawal'],
            ['type' => 'OTHER', 'amount' => 25.00, 'desc' => 'Fee Reversal'],
            ['type' => 'CREDIT', 'amount' => 0.00, 'desc' => 'Zero Transaction'],
            ['type' => 'DEBIT', 'amount' => -25.00, 'desc' => 'Service Charge'],
        ];

        $balance = 1000.00;
        foreach ($transactions as $idx => $txn) {
            $balance += $txn['amount'];
            $statement->addTransaction(
                BiTransaction::fromDatabase([
                    'id' => $idx + 1,
                    'fitid' => "TXN-{$idx}",
                    'transactionId' => "TXN-{$idx}",
                    'type' => $txn['type'],
                    'date' => '2024-01-' . str_pad(($idx % 28) + 1, 2, '0', STR_PAD_LEFT),
                    'amount' => $txn['amount'],
                    'description' => $txn['desc'],
                    'balance' => $balance
                ], $statement)
            );
        }

        // Step 1: Validate original
        $originalValid = $this->validationService->validate($statement);
        $this->assertTrue($originalValid);

        // Step 2: Process (deduplicates, filters zero-amount)
        $processed = $this->processingService->process($statement);

        // Step 3: Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Step 4: Final validation
        $finalValid = $this->validationService->validate($enriched);
        $this->assertTrue($finalValid);

        // Verify statement integrity
        $this->assertEquals('CAD', $enriched->getCurrency());
        $this->assertEquals('Test Bank', $enriched->getBank());
        $this->assertNotEmpty($enriched->getStatementId());
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
     * Helper: Add transactions to statement
     *
     * @param BiStatement $statement
     * @param int $count Number of transactions to add
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
