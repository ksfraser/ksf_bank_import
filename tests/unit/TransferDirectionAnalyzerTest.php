<?php
/**
 * Unit tests for TransferDirectionAnalyzer
 * 
 * Tests business logic for determining transfer direction based on DC indicators.
 * 
 * @package    KsfBankImport
 * @subpackage Tests\Unit
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KsfBankImport\Services\TransferDirectionAnalyzer;

/**
 * Test suite for TransferDirectionAnalyzer
 * 
 * @coversDefaultClass \KsfBankImport\Services\TransferDirectionAnalyzer
 */
class TransferDirectionAnalyzerTest extends TestCase
{
    /**
     * Analyzer instance
     * 
     * @var TransferDirectionAnalyzer
     */
    private $analyzer;
    
    /**
     * Set up test fixtures
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        require_once(__DIR__ . '/../../Services/TransferDirectionAnalyzer.php');
        $this->analyzer = new TransferDirectionAnalyzer();
    }
    
    /**
     * Test analyze with debit transaction (money leaving account 1)
     * 
     * @covers ::analyze
     * @covers ::buildTransferData
     * 
     * @return void
     */
    public function testAnalyzeWithDebitTransaction()
    {
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'To CIBC HISA',
            'transactionDC' => 'D',  // Debit = money leaving
            'transactionAmount' => -100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = [
            'id' => 2,
            'transactionTitle' => 'From Manulife',
            'transactionDC' => 'C',
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $account1 = [
            'id' => 10,
            'name' => 'Manulife Bank'
        ];
        
        $account2 = [
            'id' => 20,
            'name' => 'CIBC HISA'
        ];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
        
        // When transactionDC='D', money flows FROM account1 TO account2
        $this->assertEquals(10, $result['from_account'], 'FROM should be account 1 for debit');
        $this->assertEquals(20, $result['to_account'], 'TO should be account 2 for debit');
        $this->assertEquals(1, $result['from_trans_id'], 'FROM trans_id should be transaction 1');
        $this->assertEquals(2, $result['to_trans_id'], 'TO trans_id should be transaction 2');
    }
    
    /**
     * Test analyze with credit transaction (money arriving to account 1)
     * 
     * @covers ::analyze
     * @covers ::buildTransferData
     * 
     * @return void
     */
    public function testAnalyzeWithCreditTransaction()
    {
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'From Manulife',
            'transactionDC' => 'C',  // Credit = money arriving
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = [
            'id' => 2,
            'transactionTitle' => 'To CIBC HISA',
            'transactionDC' => 'D',
            'transactionAmount' => -100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $account1 = [
            'id' => 20,
            'name' => 'CIBC HISA'
        ];
        
        $account2 = [
            'id' => 10,
            'name' => 'Manulife Bank'
        ];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
        
        // When transactionDC='C', money flows FROM account2 TO account1
        $this->assertEquals(10, $result['from_account'], 'FROM should be account 2 for credit');
        $this->assertEquals(20, $result['to_account'], 'TO should be account 1 for credit');
        $this->assertEquals(2, $result['from_trans_id'], 'FROM trans_id should be transaction 2');
        $this->assertEquals(1, $result['to_trans_id'], 'TO trans_id should be transaction 1');
    }
    
    /**
     * Test amount is always converted to positive
     * 
     * @covers ::buildTransferData
     * 
     * @return void
     */
    public function testAmountIsAlwaysPositive()
    {
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            'transactionDC' => 'D',
            'transactionAmount' => -50.00,  // Negative amount
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = [
            'id' => 2,
            'transactionTitle' => 'Test',
            'transactionDC' => 'C',
            'transactionAmount' => 50.00
        ];
        
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
        
        $this->assertEquals(50.00, $result['amount'], 'Amount should always be positive');
        $this->assertGreaterThan(0, $result['amount'], 'Amount must be greater than zero');
    }
    
    /**
     * Test validation throws exception for missing DC indicator
     * 
     * @covers ::analyze
     * @covers ::validateInputs
     * 
     * @return void
     */
    public function testValidationThrowsExceptionForMissingDC()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('transactionDC');
        
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            // Missing transactionDC
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = ['id' => 2, 'transactionDC' => 'C', 'transactionAmount' => 100.00];
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
    }
    
    /**
     * Test validation throws exception for missing amount
     * 
     * @covers ::analyze
     * @covers ::validateInputs
     * 
     * @return void
     */
    public function testValidationThrowsExceptionForMissingAmount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('transactionAmount');
        
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            'transactionDC' => 'D',
            // Missing transactionAmount
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = ['id' => 2, 'transactionDC' => 'C', 'transactionAmount' => 100.00];
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
    }
    
    /**
     * Test validation throws exception for invalid transaction 2
     * 
     * @covers ::analyze
     * @covers ::validateInputs
     * 
     * @return void
     */
    public function testValidationThrowsExceptionForInvalidTransaction2()
    {
        $this->expectException(\TypeError::class);
        
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            'transactionDC' => 'D',
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = null;  // Invalid transaction 2
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
    }
    
    /**
     * Test validation throws exception for missing account ID
     * 
     * @covers ::analyze
     * @covers ::validateInputs
     * 
     * @return void
     */
    public function testValidationThrowsExceptionForMissingAccountId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('account');
        
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            'transactionDC' => 'D',
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = ['id' => 2, 'transactionDC' => 'C', 'transactionAmount' => 100.00];
        $account1 = [];  // Missing id
        $account2 = ['id' => 20];
        
        $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
    }
    
    /**
     * Test memo contains both transaction titles
     * 
     * @covers ::buildTransferData
     * 
     * @return void
     */
    public function testMemoContainsBothTransactionTitles()
    {
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'First Transaction',
            'transactionDC' => 'D',
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = [
            'id' => 2,
            'transactionTitle' => 'Second Transaction',
            'transactionDC' => 'C',
            'transactionAmount' => 100.00
        ];
        
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
        
        $this->assertStringContainsString('First Transaction', $result['memo']);
        $this->assertStringContainsString('Second Transaction', $result['memo']);
        $this->assertStringContainsString('::', $result['memo'], 'Memo should contain separator');
    }
    
    /**
     * Test result contains all required keys
     * 
     * @covers ::analyze
     * @covers ::buildTransferData
     * 
     * @return void
     */
    public function testResultContainsAllRequiredKeys()
    {
        $trz1 = [
            'id' => 1,
            'transactionTitle' => 'Test',
            'transactionDC' => 'D',
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = ['id' => 2, 'transactionTitle' => 'Test', 'transactionDC' => 'C', 'transactionAmount' => 100.00];
        $account1 = ['id' => 10];
        $account2 = ['id' => 20];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $account1, $account2);
        
        $requiredKeys = ['from_trans_id', 'to_trans_id', 'from_account', 'to_account', 'amount', 'date', 'memo'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Result should contain key: $key");
        }
    }
    
    /**
     * Test real-world Manulife scenario
     * 
     * @covers ::analyze
     * 
     * @return void
     */
    public function testRealWorldManulifeScenario()
    {
        // Real scenario: Transfer from Manulife to CIBC HISA
        $trz1 = [
            'id' => 12345,
            'transactionTitle' => 'TRANSFER TO CIBC HISA',
            'transactionDC' => 'D',  // Money leaving Manulife
            'transactionAmount' => -1000.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $trz2 = [
            'id' => 12346,
            'transactionTitle' => 'TRANSFER FROM MANULIFE',
            'transactionDC' => 'C',  // Money arriving at CIBC
            'transactionAmount' => 1000.00,
            'valueTimestamp' => '2025-01-15'
        ];
        
        $manulifeAccount = [
            'id' => 10,
            'name' => 'Manulife Bank'
        ];
        
        $cibcAccount = [
            'id' => 20,
            'name' => 'CIBC HISA'
        ];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $manulifeAccount, $cibcAccount);
        
        $this->assertEquals(10, $result['from_account'], 'Money should come FROM Manulife');
        $this->assertEquals(20, $result['to_account'], 'Money should go TO CIBC');
        $this->assertEquals(1000.00, $result['amount'], 'Amount should be positive 1000');
        $this->assertEquals('2025-01-15', $result['date']);
    }
    
    /**
     * Test CIBC internal transfer (HISA to Savings)
     * 
     * @covers ::analyze
     * 
     * @return void
     */
    public function testCIBCInternalTransfer()
    {
        // Real scenario: Transfer from CIBC HISA to CIBC Savings
        $trz1 = [
            'id' => 20001,
            'transactionTitle' => 'TRANSFER TO SAVINGS',
            'transactionDC' => 'D',  // Money leaving HISA
            'transactionAmount' => -500.00,
            'valueTimestamp' => '2025-01-16'
        ];
        
        $trz2 = [
            'id' => 30001,
            'transactionTitle' => 'TRANSFER FROM HISA',
            'transactionDC' => 'C',  // Money arriving at Savings
            'transactionAmount' => 500.00,
            'valueTimestamp' => '2025-01-16'
        ];
        
        $hisaAccount = [
            'id' => 20,
            'name' => 'CIBC HISA'
        ];
        
        $savingsAccount = [
            'id' => 30,
            'name' => 'CIBC Savings'
        ];
        
        $result = $this->analyzer->analyze($trz1, $trz2, $hisaAccount, $savingsAccount);
        
        $this->assertEquals(20, $result['from_account'], 'Money should come FROM HISA');
        $this->assertEquals(30, $result['to_account'], 'Money should go TO Savings');
        $this->assertEquals(500.00, $result['amount'], 'Amount should be positive 500');
    }
}
