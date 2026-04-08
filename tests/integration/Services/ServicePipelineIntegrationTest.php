<?php

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Import\Services\Validation\ValidationService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use DateTime;

/**
 * Integration Test: Full Service Pipeline
 *
 * Tests the integration of Enrichment, Processing, and Validation services
 * with real BiStatement entities (immutable)
 */
class ServicePipelineIntegrationTest extends TestCase
{
    private EnrichmentService $enrichmentService;
    private StatementProcessingService $processingService;
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $this->enrichmentService = new EnrichmentService();
        $this->processingService = new StatementProcessingService();
        $this->validationService = new ValidationService();
    }

    /**
     * Integration Test: Process -> Enrich -> Validate Pipeline
     *
     * Verifies the complete processing pipeline works correctly
     */
    public function testProcessEnrichValidatePipeline(): void
    {
        // Create statement with transactions
        $statement = BiStatement::fromDatabase([
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
            'endBalance' => 1100.00,
            'smtDate' => '2024-01-31'
        ], [
            BiTransaction::fromDatabase([
                'id' => 1,
                'smt_id' => 1,
                'fitid' => 'TXN-001',
                'acctid' => 'ACC-001',
                'transactionAmount' => 100.00,
                'transactionTitle' => 'Deposit',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-15'
            ])
        ]);

        // Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(1, $processed->getTransactions());

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);
        $this->assertNotNull($enriched);

        // Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
    }

    /**
     * Integration Test: Empty Statement Processing
     *
     * Verifies empty statements are handled correctly
     */
    public function testEmptyStatementProcessing(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account'  => 'Chequing',
            'statementId' => 'STMT-EMPTY',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1000.00,
            'smtDate' => '2024-01-31'
        ], []);

        // Process empty
        $processed = $this->processingService->process($statement);
        $this->assertCount(0, $processed->getTransactions());

        // Enrich empty
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate empty
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
    }

    /**
     * Integration Test: Multiple Transaction Handling
     *
     * Tests processing and validating multiple transactions
     */
    public function testMultipleTransactionHandling(): void
    {
        $transactions = [];
        $balance = 1000.00;

        // Create 5 transactions
        for ($i = 1; $i <= 5; $i++) {
            $amount = $i * 50.00;
            $balance += $amount;

            $transactions[] = BiTransaction::fromDatabase([
                'id' => $i,
                'smt_id' => 1,
                'fitid' => "TXN-{$i}",
                'acctid' => 'ACC-001',
                'transactionAmount' => $amount,
                'transactionTitle' => "Transaction {$i}",
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-' . str_pad($i, 2, '0', STR_PAD_LEFT)
            ]);
        }

        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-MULTI',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => $balance,
            'smtDate' => '2024-01-31'
        ], $transactions);

        // Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(5, $processed->getTransactions());

        // Validate
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }

    /**
     * Integration Test: Foreign Currency Statements
     *
     * Tests processing foreign currency statements
     */
    public function testForeignCurrencyStatementHandling(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'International Bank',
            'account' => 'USD Account',
            'statementId' => 'STMT-USD',
            'acctid' => 'ACC-USD',
            'fitid' => 'FIT-USD',
            'bankid' => 'BANK-INTL',
            'intu_bid' => 'INTU-INTL',
            'currency' => 'USD',
            'startBalance' => 5000.00,
            'endBalance' => 5100.00,
            'smtDate' => '2024-01-31'
        ], [
            BiTransaction::fromDatabase([
                'id' => 1,
                'smt_id' => 1,
                'fitid' => 'USD-001',
                'acctid' => 'ACC-USD',
                'transactionAmount' => 100.00,
                'transactionTitle' => 'USD Payment',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-15'
            ])
        ]);

        // Process
        $processed = $this->processingService->process($statement);

        // Enrich (may attempt exchange rate lookup)
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate foreign currency
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
        $this->assertEquals('USD', $enriched->getCurrency());
    }

    /**
     * Integration Test: Transaction Type Variety
     *
     * Tests all transaction types are handled correctly
     */
    public function testTransactionTypeVariety(): void
    {
        $transactions = [
            BiTransaction::fromDatabase([
                'id' => 1,
                'smt_id' => 1,
                'fitid' => 'CREDIT-001',
                'acctid' => 'ACC-001',
                'transactionAmount' => 100.00,
                'transactionTitle' => 'Credit Transaction',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-01'
            ]),
            BiTransaction::fromDatabase([
                'id' => 2,
                'smt_id' => 1,
                'fitid' => 'DEBIT-001',
                'acctid' => 'ACC-001',
                'transactionAmount' => -50.00,
                'transactionTitle' => 'Debit Transaction',
                'transactionType' => 'DEBIT',
                'valueTimestamp' => '2024-01-02'
            ]),
            BiTransaction::fromDatabase([
                'id' => 3,
                'smt_id' => 1,
                'fitid' => 'OTHER-001',
                'acctid' => 'ACC-001',
                'transactionAmount' => 25.00,
                'transactionTitle' => 'Other Transaction',
                'transactionType' => 'OTHER',
                'valueTimestamp' => '2024-01-03'
            ])
        ];

        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-TYPES',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1075.00,
            'smtDate' => '2024-01-31'
        ], $transactions);

        // Process all types
        $processed = $this->processingService->process($statement);
        $this->assertCount(3, $processed->getTransactions());

        // Validate all types
        $isValid = $this->validationService->validate($processed);
        $this->assertTrue($isValid);
    }
}
