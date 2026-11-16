<?php

use PHPUnit\Framework\TestCase;

/**
 * @deprecated 20260516
 * 
 * DEPRECATION NOTICE:
 * ===================
 * This test has been moved to deprecated status because a comprehensive regression test
 * suite already exists at tests/unit/BiTransactionsModelRegressionTest.php that covers
 * the bi_transactions_model class per REGRESSION_TESTING_SESSION_2025-11-14.md specification.
 * 
 * REASON FOR DEPRECATION:
 * - Root-level test not integrated with phpunit.xml test suites
 * - Missing require_once for class file (bi_transactions_model not autoloaded)
 * - Incomplete coverage compared to regression test (33 tests vs 1 test)
 * - Regression test is the "official" spec per regression documentation
 * 
 * PROPER TEST LOCATION:
 * - Use: tests/unit/BiTransactionsModelRegressionTest.php
 * - Location: In phpunit.xml "Unit (Legacy)" suite
 * - Coverage: 33 tests, 50 assertions
 * 
 * TO RESTORE:
 * - If this test is needed for specific scenarios not covered by regression test
 * - Add require_once(__DIR__ . '/../../class.bi_transactions.php');
 * - Or add class file to composer.json classmap
 * - Or add @requires attribute
 * 
 * LAST MAINTAINED: October 2025
 * ARCHIVED: May 16, 2026
 */
class BiTransactionsModelTest extends TestCase
{
    protected $biTransactionsModel;

    protected function setUp(): void
    {
        $this->biTransactionsModel = new bi_transactions_model();
    }

    public function testInsertTransaction()
    {
        // Set up the necessary properties
        $this->biTransactionsModel->smt_id = 1;
        $this->biTransactionsModel->valueTimestamp = '2025-04-02';
        $this->biTransactionsModel->entryTimestamp = '2025-04-02';
        $this->biTransactionsModel->account = '123456';
        $this->biTransactionsModel->accountName = 'Test Account';
        $this->biTransactionsModel->transactionType = 'DEP';
        $this->biTransactionsModel->transactionCode = 'TX123';
        $this->biTransactionsModel->transactionCodeDesc = 'Transaction Description';
        $this->biTransactionsModel->transactionDC = 'D';
        $this->biTransactionsModel->transactionAmount = 100.00;
        $this->biTransactionsModel->transactionTitle = 'Test Transaction';
        $this->biTransactionsModel->status = 0;
        $this->biTransactionsModel->merchant = 'Test Merchant';
        $this->biTransactionsModel->category = 'Test Category';
        $this->biTransactionsModel->sic = '1234';
        $this->biTransactionsModel->memo = 'Test Memo';
        $this->biTransactionsModel->checknumber = 12345;

        // Call the insert_transaction method
        $this->biTransactionsModel->insert_transaction();

        // Verify that the transaction was inserted correctly
        // This is an example assertion, adjust it according to your actual database interaction
        $this->assertTrue($this->biTransactionsModel->trans_exists());
    }
}
?>
