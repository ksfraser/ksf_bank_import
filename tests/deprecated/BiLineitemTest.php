<?php
use PHPUnit\Framework\TestCase;

/**
 * @deprecated 20260516
 * 
 * DEPRECATION NOTICE:
 * ===================
 * This test has been moved to deprecated status because:
 * 1. A comprehensive regression test suite exists at tests/unit/BiLineItemQERegressionTest.php
 *    per REGRESSION_TESTING_SESSION_2025-11-14.md specification
 * 2. This root-level test is incomplete (only tests constructor)
 * 3. Not integrated with phpunit.xml test suites
 * 4. Requires undefined FA constants (ST_BANKPAYMENT, ST_BANKDEPOSIT, etc.)
 * 
 * REASON FOR FA CONSTANT ERROR:
 * - The bi_lineitem class depends on FrontAccounting transaction type constants
 * - These constants are defined in FA files, not in the PSR-4 src/ directory
 * - Test bootstrap doesn't load FA include files
 * - This is a bootstrap/environment setup issue, not a code issue
 * 
 * PROPER TEST LOCATION:
 * - Use: tests/unit/BiLineItemQERegressionTest.php
 * - Coverage: 14 tests, 23 assertions
 * - Proper setup: Mocks FA constants appropriately
 * 
 * REASON NOT TO FIX:
 * - bi_lineitem is a legacy class that references FA directly
 * - Proper refactoring would involve moving this to Ksfraser namespace
 * - Until refactored, testing should use mocked FA constants (see regression test)
 * 
 * LAST MAINTAINED: October 2025
 * ARCHIVED: May 16, 2026
 */
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../class.bi_lineitem.php';

class BiLineitemTest extends TestCase
{
    protected $lineitem;

    protected function setUp(): void
    {
        $trz = [
            'transactionDC' => 'C',
            'memo' => 'Test Memo',
            'our_account' => 'Test Account',
            'valueTimestamp' => '2025-04-02',
            'entryTimestamp' => '2025-04-01',
            'accountName' => 'Test Account Name',
            'transactionTitle' => 'Test Title',
            'transactionCode' => '1234',
            'transactionCodeDesc' => 'Test Code Desc',
            'currency' => 'USD',
            'status' => 1,
            'id' => 1,
            'fa_trans_type' => 1,
            'fa_trans_no' => 1,
            'transactionAmount' => 100.0,
            'transactionType' => 'COM'
        ];
        $this->lineitem = new bi_lineitem($trz);
    }

    public function testConstruct()
    {
        $this->assertEquals('C', $this->lineitem->transactionDC);
        $this->assertEquals('Test Memo', $this->lineitem->memo);
        $this->assertEquals('Test Account', $this->lineitem->our_account);
        $this->assertEquals('2025-04-02', $this->lineitem->valueTimestamp);
        $this->assertEquals('2025-04-01', $this->lineitem->entryTimestamp);
        $this->assertEquals('Test Account Name', $this->lineitem->otherBankAccountName);
        $this->assertEquals('Test Title', $this->lineitem->transactionTitle);
        $this->assertEquals('1234', $this->lineitem->transactionCode);
        $this->assertEquals('Test Code Desc', $this->lineitem->transactionCodeDesc);
        $this->assertEquals('USD', $this->lineitem->currency);
        $this->assertEquals(1, $this->lineitem->status);
        $this->assertEquals(1, $this->lineitem->id);
        $this->assertEquals(1, $this->lineitem->fa_trans_type);
        $this->assertEquals(1, $this->lineitem->fa_trans_no);
        $this->assertEquals(100.0, $this->lineitem->amount);
    }
}
