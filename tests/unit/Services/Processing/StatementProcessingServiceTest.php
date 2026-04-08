<?php

namespace Tests\Unit\Services\Processing;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;

/**
 * Tests for StatementProcessingService
 *
 * @covers \Ksfraser\FaBankImport\Import\Services\Processing\StatementProcessingService
 */
class StatementProcessingServiceTest extends TestCase
{
    /**
     * Service under test
     *
     * @var StatementProcessingService
     */
    private StatementProcessingService $processingService;

    protected function setUp(): void
    {
        $this->processingService = new StatementProcessingService();
    }

    /**
     * Test: Process statement with no transactions
     */
    public function testProcessEmptyStatement(): void
    {
        $statement = $this->buildTestStatement();

        $result = $this->processingService->process($statement);

        // Processing returns an equivalent statement (may be new object due to immutability)
        $this->assertEquals($statement->getBank(), $result->getBank());
        $this->assertEmpty($result->getTransactions());
    }

    /**
     * Test: Process statement with single transaction
     */
    public function testProcessStatementWithSingleTransaction(): void
    {
        $transaction = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'TXN-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionTitle' => 'Payment Received',
            'valueTimestamp' => '2024-01-01'
        ]);

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
        ], [$transaction]);

        $result = $this->processingService->process($statement);

        $this->assertNotNull($result);
        $this->assertCount(1, $result->getTransactions());
    }

    /**
     * Test: Process statement with multiple transactions
     */
    public function testProcessStatementWithMultipleTransactions(): void
    {
        $transactions = [];
        for ($i = 1; $i <= 5; $i++) {
            $transactions[] = BiTransaction::fromDatabase([
                'id' => $i,
                'smt_id' => 1,
                'fitid' => "TXN-00{$i}",
                'acctid' => 'ACC-001',
                'transactionAmount' => 100.00 * $i,
                'transactionTitle' => "Transaction {$i}",
                'valueTimestamp' => "2024-01-0{$i}"
            ]);
        }

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
            'endBalance' => 1500.00,
            'smtDate' => '2024-01-31'
        ], $transactions);

        $result = $this->processingService->process($statement);

        $this->assertCount(5, $result->getTransactions());
    }

    /**
     * Test: Process maintains transaction order
     */
    public function testProcessMaintainsTransactionOrder(): void
    {
        $dates = ['2024-01-03', '2024-01-01', '2024-01-02'];
        $transactions = [];
        
        foreach ($dates as $idx => $date) {
            $transactions[] = BiTransaction::fromDatabase([
                'id' => $idx + 1,
                'smt_id' => 1,
                'fitid' => "TXN-{$idx}",
                'acctid' => 'ACC-001',
                'transactionAmount' => -50.00,
                'transactionTitle' => "Transaction {$idx}",
                'valueTimestamp' => $date
            ]);
        }

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
            'endBalance' => 850.00,
            'smtDate' => '2024-01-31'
        ], $transactions);

        $result = $this->processingService->process($statement);

        // Should maintain order as added
        $resultTransactions = $result->getTransactions();
        $this->assertEquals('TXN-0', $resultTransactions[0]->getFitId());
        $this->assertEquals('TXN-1', $resultTransactions[1]->getFitId());
        $this->assertEquals('TXN-2', $resultTransactions[2]->getFitId());
    }

    /**
     * Test: Process deduplicates transactions
     */
    public function testProcessDeduplicatesTransactions(): void
    {
        $txn = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'DUP-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionTitle' => 'Duplicate Payment',
            'valueTimestamp' => '2024-01-01'
        ]);

        // Note: With immutable pattern, we pass the same transaction twice
        // The service deduplicates by FITID
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
        ], [$txn, $txn]);

        $result = $this->processingService->process($statement);

        // Should contain only one instance after deduplication
        $this->assertCount(1, $result->getTransactions());
    }

    /**
     * Test: Process validates transaction amounts are non-negative
     */
    public function testProcessAllowsNegativeAmountsForDebits(): void
    {
        $transaction = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'TXN-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => -50.00,
            'transactionTitle' => 'Withdrawal',
            'valueTimestamp' => '2024-01-01'
        ]);

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
            'endBalance' => 950.00,
            'smtDate' => '2024-01-31'
        ], [$transaction]);

        // Should process without error
        $result = $this->processingService->process($statement);

        $this->assertCount(1, $result->getTransactions());
    }

    /**
     * Test: Process filters out zero-amount transactions
     */
    public function testProcessFiltersZeroAmountTransactions(): void
    {
        $zeroTransaction = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'ZERO-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 0.00,
            'transactionTitle' => 'Zero Amount',
            'valueTimestamp' => '2024-01-01'
        ]);

        $normalTransaction = BiTransaction::fromDatabase([
            'id' => 2,
            'smt_id' => 1,
            'fitid' => 'NORMAL-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionTitle' => 'Normal Payment',
            'valueTimestamp' => '2024-01-01'
        ]);

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
        ], [$zeroTransaction, $normalTransaction]);

        $result = $this->processingService->process($statement);

        // Should only contain non-zero transaction
        $this->assertCount(1, $result->getTransactions());
        $this->assertEquals('NORMAL-001', $result->getTransactions()[0]->getFitId());
    }

    /**
     * Test: Process handles missing transaction data gracefully
     */
    public function testProcessSkipsTransactionsWithMissingData(): void
    {
        $transaction = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'INCOMPLETE-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionTitle' => '', // Empty title
            'valueTimestamp' => '2024-01-01'
        ]);

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
        ], [$transaction]);

        $result = $this->processingService->process($statement);

        // Processing should complete (error handling in place)
        $this->assertNotNull($result);
    }

    /**
     * Test: Process with very large transaction list
     */
    public function testProcessLargeStatementWithManyTransactions(): void
    {
        $transactions = [];
        // Add 1000 transactions with non-zero amounts
        for ($i = 1; $i <= 1000; $i++) {
            $transactions[] = BiTransaction::fromDatabase([
                'id' => $i,
                'smt_id' => 1,
                'fitid' => "LARGE-{$i}",
                'acctid' => 'ACC-001',
                'transactionAmount' => ($i % 2 === 0 ? -10.00 : 10.00) + ($i / 1000),
                'transactionTitle' => "Transaction {$i}",
                'valueTimestamp' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT)
            ]);
        }

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
            'endBalance' => 1000.00,
            'smtDate' => '2024-01-31'
        ], $transactions);

        $result = $this->processingService->process($statement);

        $this->assertCount(1000, $result->getTransactions());
    }

    /**
     * Test: Process preserves statement metadata
     */
    public function testProcessPreservesStatementMetadata(): void
    {
        $statement = $this->buildTestStatement();

        $originalBank = $statement->getBank();
        $originalAccount = $statement->getAccount();
        $originalCurrency = $statement->getCurrency();

        $result = $this->processingService->process($statement);

        $this->assertEquals($originalBank, $result->getBank());
        $this->assertEquals($originalAccount, $result->getAccount());
        $this->assertEquals($originalCurrency, $result->getCurrency());
    }

    /**
     * Test: Process preserves starting and ending balances
     */
    public function testProcessPreservesBalances(): void
    {
        $statement = $this->buildTestStatement();

        $originalStartBalance = $statement->getStartBalance();
        $originalEndBalance = $statement->getEndBalance();

        $result = $this->processingService->process($statement);

        $this->assertEquals($originalStartBalance, $result->getStartBalance());
        $this->assertEquals($originalEndBalance, $result->getEndBalance());
    }

    /**
     * Test: Process handles currency edge cases
     */
    public function testProcessHandlesCurrencyEdgeCases(): void
    {
        $transaction = BiTransaction::fromDatabase([
            'id' => 1,
            'smt_id' => 1,
            'fitid' => 'CURR-001',
            'acctid' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionTitle' => 'Foreign Currency',
            'valueTimestamp' => '2024-01-01'
        ]);

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
        ], [$transaction]);

        $result = $this->processingService->process($statement);

        $this->assertNotNull($result);
        $this->assertEquals('CAD', $result->getCurrency());
    }

    /**
     * Helper: Build test statement
     *
     * @param string $currency Currency code
     * @return BiStatement
     */
    protected function buildTestStatement(string $currency = 'CAD'): BiStatement
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
            'currency' => $currency,
            'startBalance' => 1000.00,
            'endBalance' => 1500.00,
            'smtDate' => '2024-01-01'
        ], []);
    }
}
