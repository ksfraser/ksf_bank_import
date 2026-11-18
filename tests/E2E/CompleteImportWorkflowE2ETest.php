<?php

namespace Tests\E2E\Import;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Enrichment\EnrichmentService;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Import\Services\Validation\ValidationService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * End-to-End Test: Complete Import Workflow
 *
 * Tests the full statement import workflow from processing through enrichment
 * and validation, ensuring all services integrate correctly
 */
class CompleteImportWorkflowE2ETest extends TestCase
{
    private StatementProcessingService $processingService;
    private EnrichmentService $enrichmentService;
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $this->processingService = new StatementProcessingService();
        $this->enrichmentService = new EnrichmentService();
        $this->validationService = new ValidationService();
    }

    /**
     * E2E: Simple Import Workflow
     *
     * Single transaction through the complete pipeline
     */
    public function testSimpleImportWorkflow(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-E2E-001',
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
                'transactionTitle' => 'Order Payment',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-15'
            ])
        ]);

        // Step 1: Process
        $processed = $this->processingService->process($statement);
        $this->assertCount(1, $processed->getTransactions());

        // Step 2: Enrich
        $enriched = $this->enrichmentService->enrich($processed);
        $this->assertNotNull($enriched);

        // Step 3: Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid, 'Statement should be valid after processing and enrichment');
    }

    /**
     * E2E: Multi-Transaction Import Workflow
     *
     * Multiple transactions, various types, complete pipeline
     */
    public function testMultiTransactionImportWorkflow(): void
    {
        $transactions = [];
        $balance = 1000.00;

        // Create mixed transaction types
        $transactionSpecs = [
            ['type' => 'CREDIT', 'amount' => 500.00, 'desc' => 'Opening Balance'],
            ['type' => 'DEBIT', 'amount' => -75.00, 'desc' => 'ATM Withdrawal'],
            ['type' => 'CREDIT', 'amount' => 250.00, 'desc' => 'Payroll Deposit'],
            ['type' => 'DEBIT', 'amount' => -30.00, 'desc' => 'Service Fee'],
            ['type' => 'CREDIT', 'amount' => 125.00, 'desc' => 'Interest Credit'],
        ];

        foreach ($transactionSpecs as $idx => $spec) {
            $balance += $spec['amount'];
            $transactions[] = BiTransaction::fromDatabase([
                'id' => $idx + 1,
                'smt_id' => 1,
                'fitid' => "TXN-" . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'acctid' => 'ACC-001',
                'transactionType' => $spec['type'],
                'valueTimestamp' => '2024-01-' . str_pad(($idx + 1) * 5, 2, '0', STR_PAD_LEFT),
                'transactionAmount' => $spec['amount'],
                'transactionTitle' => $spec['desc']
            ]);
        }

        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-E2E-MULTI',
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

        // Enrich
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
    }

    /**
     * E2E: Valid Statement Reaches Import-Ready State
     *
     * Verifies statement passes all checks and is ready for import
     */
    public function testStatementReachesImportReadyState(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Import Ready Bank',
            'account' => 'Business Account',
            'statementId' => 'STMT-IMPORT-READY',
            'acctid' => 'ACC-BUSI',
            'fitid' => 'FIT-BUSI',
            'bankid' => 'BANK-BUSI',
            'intu_bid' => 'INTU-BUSI',
            'currency' => 'CAD',
            'startBalance' => 5000.00,
            'endBalance' => 6200.00,
            'smtDate' => '2024-01-31'
        ], [
            BiTransaction::fromDatabase([
                'id' => 1,
                'smt_id' => 1,
                'fitid' => 'IMP-001',
                'acctid' => 'ACC-BUSI',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-10',
                'transactionAmount' => 1200.00,
                'transactionTitle' => 'Invoice Payment Received'
            ])
        ]);

        // Full pipeline
        $processed = $this->processingService->process($statement);
        $enriched = $this->enrichmentService->enrich($processed);
        $isValid = $this->validationService->validate($enriched);

        // Statement should be import-ready
        $this->assertTrue($isValid);
        $this->assertEquals('STMT-IMPORT-READY', $enriched->getStatementId());
        $this->assertEquals('CAD', $enriched->getCurrency());
        $this->assertNotEmpty($enriched->getBank());
    }

    /**
     * E2E: Large Statement Processing
     *
     * Tests system can handle statements with many transactions
     */
    public function testLargeStatementProcessing(): void
    {
        $transactions = [];
        $balance = 1000.00;

        // Create 100 transactions
        for ($i = 1; $i <= 100; $i++) {
            $amount = ($i % 3 === 0) ? -10.00 : 15.00;
            $balance += $amount;

            $transactions[] = BiTransaction::fromDatabase([
                'id' => $i,
                'smt_id' => 1,
                'fitid' => "LARGE-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'acctid' => 'ACC-LARGE',
                'transactionType' => $amount > 0 ? 'CREDIT' : 'DEBIT',
                'valueTimestamp' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                'transactionAmount' => $amount,
                'transactionTitle' => "Transaction " . str_pad($i, 4, '0', STR_PAD_LEFT)
            ]);
        }

        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Large Account',
            'statementId' => 'STMT-LARGE-100',
            'acctid' => 'ACC-LARGE',
            'fitid' => 'FIT-LARGE',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => $balance,
            'smtDate' => '2024-01-31'
        ], $transactions);

        // Process large
        $processed = $this->processingService->process($statement);
        $this->assertCount(100, $processed->getTransactions());

        // Enrich large
        $enriched = $this->enrichmentService->enrich($processed);

        // Validate large
        $isValid = $this->validationService->validate($enriched);
        $this->assertTrue($isValid);
    }

    /**
     * E2E: Currency Variety Workflow
     *
     * Tests handling different currencies in sequence
     */
    public function testCurrencyVarietyWorkflow(): void
    {
        $currencies = ['CAD', 'USD', 'EUR', 'GBP'];

        foreach ($currencies as $currency) {
            $statement = BiStatement::fromDatabase([
                'id' => 1,
                'bank' => 'International Bank',
                'account' => "{$currency} Account",
                'statementId' => "STMT-{$currency}",
                'acctid' => "ACC-{$currency}",
                'fitid' => "FIT-{$currency}",
                'bankid' => 'BANK-INTL',
                'intu_bid' => 'INTU-INTL',
                'currency' => $currency,
                'startBalance' => 1000.00,
                'endBalance' => 1100.00,
                'smtDate' => '2024-01-31'
            ], [
                BiTransaction::fromDatabase([
                    'id' => 1,
                    'smt_id' => 1,
                    'fitid' => "{$currency}-TRANS",
                    'acctid' => "ACC-{$currency}",
                    'transactionType' => 'CREDIT',
                    'valueTimestamp' => '2024-01-15',
                    'transactionAmount' => 100.00,
                    'transactionTitle' => "{$currency} Transaction"
                ])
            ]);

            // Process
            $processed = $this->processingService->process($statement);

            // Enrich
            $enriched = $this->enrichmentService->enrich($processed);

            // Validate
            $isValid = $this->validationService->validate($enriched);
            $this->assertTrue($isValid, "Currency {$currency} workflow failed");
            $this->assertEquals($currency, $enriched->getCurrency());
        }
    }

    /**
     * E2E: Workflow Idempotency
     *
     * Verifies that running the workflow multiple times on same statement
     * produces consistent results (idempotency)
     */
    public function testWorkflowIdempotency(): void
    {
        $statement = BiStatement::fromDatabase([
            'id' => 1,
            'bank' => 'Test Bank',
            'account' => 'Chequing',
            'statementId' => 'STMT-IDEMPOTENT',
            'acctid' => 'ACC-001',
            'fitid' => 'FIT-001',
            'bankid' => 'BANK-001',
            'intu_bid' => 'INTU-001',
            'currency' => 'CAD',
            'startBalance' => 1000.00,
            'endBalance' => 1150.00,
            'smtDate' => '2024-01-31'
        ], [
            BiTransaction::fromDatabase([
                'id' => 1,
                'smt_id' => 1,
                'fitid' => 'IDEM-001',
                'acctid' => 'ACC-001',
                'transactionType' => 'CREDIT',
                'valueTimestamp' => '2024-01-15',
                'transactionAmount' => 150.00,
                'transactionTitle' => 'Test Payment'
            ])
        ]);

        // Run workflow 3 times
        $results = [];
        for ($run = 1; $run <= 3; $run++) {
            $processed = $this->processingService->process($statement);
            $enriched = $this->enrichmentService->enrich($processed);
            $isValid = $this->validationService->validate($enriched);

            $results[] = [
                'valid' => $isValid,
                'txnCount' => count($enriched->getTransactions()),
                'bank' => $enriched->getBank()
            ];
        }

        // All runs should produce identical results
        $this->assertTrue($results[0]['valid']);
        $this->assertTrue($results[1]['valid']);
        $this->assertTrue($results[2]['valid']);

        $this->assertEquals($results[0]['txnCount'], $results[1]['txnCount']);
        $this->assertEquals($results[1]['txnCount'], $results[2]['txnCount']);

        $this->assertEquals($results[0]['bank'], $results[1]['bank']);
        $this->assertEquals($results[1]['bank'], $results[2]['bank']);
    }
}
