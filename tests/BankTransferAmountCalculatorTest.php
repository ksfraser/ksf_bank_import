<?php
/**
 * Unit tests for BankTransferAmountCalculator
 * 
 * @package    KsfBankImport
 * @subpackage Tests
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 */

// Define mock function in the global namespace where FA functions are called
namespace {
    if (!function_exists('get_bank_account')) {
        function get_bank_account($id) {
            // Mock bank accounts for testing
            $accounts = [
                1 => ['id' => 1, 'bank_curr_code' => 'CAD', 'bank_name' => 'TD Canada CAD'],
                2 => ['id' => 2, 'bank_curr_code' => 'USD', 'bank_name' => 'TD Canada USD'],
                3 => ['id' => 3, 'bank_curr_code' => 'EUR', 'bank_name' => 'European Bank EUR'],
                4 => ['id' => 4, 'bank_curr_code' => 'CAD', 'bank_name' => 'RBC CAD'],
            ];
            
            return $accounts[$id] ?? null;
        }
    }
}

// Define mock in Services namespace for ExchangeRateService
namespace KsfBankImport\Services {
    if (!function_exists('KsfBankImport\Services\get_exchange_rate_from_to')) {
        function get_exchange_rate_from_to($from, $to, $date) {
            // Mock exchange rates for testing
            $rates = [
                'USD_CAD' => 1.30,
                'CAD_USD' => 0.77,
                'USD_EUR' => 0.85,
                'EUR_USD' => 1.18,
                'CAD_EUR' => 0.65,
                'EUR_CAD' => 1.54,
            ];
            
            $key = "{$from}_{$to}";
            return $rates[$key] ?? 1.0;
        }
    }
}

// Now define the test class
namespace KsfBankImport\Tests\Services {
    use PHPUnit\Framework\TestCase;
    
    require_once(__DIR__ . '/../Services/BankTransferAmountCalculator.php');
    
    use KsfBankImport\Services\BankTransferAmountCalculator;
    use KsfBankImport\Services\ExchangeRateService;
    
    /**
     * Test suite for BankTransferAmountCalculator
     * 
     * @since 1.0.0
     */
    class BankTransferAmountCalculatorTest extends TestCase
    {
        /**
         * Calculator instance
         * 
         * @var BankTransferAmountCalculator
         */
        private $calculator;
        
        /**
         * Set up test fixtures
         * 
         * @return void
         */
        protected function setUp(): void
        {
            $this->calculator = new BankTransferAmountCalculator();
        }
        
        /**
         * Test same currency transfer returns same amount
         * 
         * @return void
         */
        public function testSameCurrencyReturnsSameAmount()
        {
            // CAD to CAD transfer (accounts 1 and 4)
            $targetAmount = $this->calculator->calculateTargetAmount(1, 4, 1000.00, '2025-10-18');
            
            $this->assertEquals(1000.00, $targetAmount);
        }
        
        /**
         * Test forex transfer applies exchange rate
         * 
         * @return void
         */
        public function testForexTransferAppliesExchangeRate()
        {
            // USD to CAD transfer (accounts 2 to 1)
            // Rate: 1.30
            $targetAmount = $this->calculator->calculateTargetAmount(2, 1, 1000.00, '2025-10-18');
            
            $this->assertEquals(1300.00, $targetAmount);
        }
        
        /**
         * Test reverse forex transfer
         * 
         * @return void
         */
        public function testReverseForexTransfer()
        {
            // CAD to USD transfer (accounts 1 to 2)
            // Rate: 0.77
            $targetAmount = $this->calculator->calculateTargetAmount(1, 2, 1000.00, '2025-10-18');
            
            $this->assertEquals(770.00, $targetAmount);
        }
        
        /**
         * Test EUR to CAD transfer
         * 
         * @return void
         */
        public function testEurToCadTransfer()
        {
            // EUR to CAD transfer (accounts 3 to 1)
            // Rate: 1.54
            $targetAmount = $this->calculator->calculateTargetAmount(3, 1, 1000.00, '2025-10-18');
            
            $this->assertEquals(1540.00, $targetAmount);
        }
        
        /**
         * Test invalid from bank account ID throws exception
         * 
         * @return void
         */
        public function testInvalidFromBankAccountThrowsException()
        {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('From bank account ID must be a positive integer');
            
            $this->calculator->calculateTargetAmount(0, 1, 1000.00, '2025-10-18');
        }
        
        /**
         * Test invalid to bank account ID throws exception
         * 
         * @return void
         */
        public function testInvalidToBankAccountThrowsException()
        {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('To bank account ID must be a positive integer');
            
            $this->calculator->calculateTargetAmount(1, -1, 1000.00, '2025-10-18');
        }
        
        /**
         * Test negative amount throws exception
         * 
         * @return void
         */
        public function testNegativeAmountThrowsException()
        {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Source amount must be a non-negative number');
            
            $this->calculator->calculateTargetAmount(1, 2, -100.00, '2025-10-18');
        }
        
        /**
         * Test zero amount is allowed
         * 
         * @return void
         */
        public function testZeroAmountIsAllowed()
        {
            $targetAmount = $this->calculator->calculateTargetAmount(2, 1, 0.00, '2025-10-18');
            
            $this->assertEquals(0.00, $targetAmount);
        }
        
        /**
         * Test non-existent bank account throws exception
         * 
         * @return void
         */
        public function testNonExistentBankAccountThrowsException()
        {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to retrieve source bank account');
            
            $this->calculator->calculateTargetAmount(999, 1, 1000.00, '2025-10-18');
        }
        
        /**
         * Test getBankCurrencies returns correct currencies
         * 
         * @return void
         */
        public function testGetBankCurrencies()
        {
            $result = $this->calculator->getBankCurrencies(1, 2);
            
            $this->assertEquals('CAD', $result['from_currency']);
            $this->assertEquals('USD', $result['to_currency']);
            $this->assertTrue($result['is_forex']);
        }
        
        /**
         * Test getBankCurrencies identifies same currency
         * 
         * @return void
         */
        public function testGetBankCurrenciesSameCurrency()
        {
            $result = $this->calculator->getBankCurrencies(1, 4);
            
            $this->assertEquals('CAD', $result['from_currency']);
            $this->assertEquals('CAD', $result['to_currency']);
            $this->assertFalse($result['is_forex']);
        }
        
        /**
         * Test constructor with custom exchange rate service
         * 
         * @return void
         */
        public function testConstructorWithCustomService()
        {
            $customService = new ExchangeRateService();
            $calculator = new BankTransferAmountCalculator($customService);
            
            // Should work normally
            $targetAmount = $calculator->calculateTargetAmount(2, 1, 1000.00, '2025-10-18');
            $this->assertEquals(1300.00, $targetAmount);
        }
        
        /**
         * Test decimal amounts are handled correctly
         * 
         * @return void
         */
        public function testDecimalAmounts()
        {
            // USD to CAD with decimal amount
            $targetAmount = $this->calculator->calculateTargetAmount(2, 1, 1234.56, '2025-10-18');
            
            // Use equalsWithDelta for floating point comparison
            $this->assertEqualsWithDelta(1604.928, $targetAmount, 0.0001);
        }
        
        /**
         * Test large amounts
         * 
         * @return void
         */
        public function testLargeAmounts()
        {
            // USD to CAD with large amount
            $targetAmount = $this->calculator->calculateTargetAmount(2, 1, 1000000.00, '2025-10-18');
            
            $this->assertEquals(1300000.00, $targetAmount);
        }
        
        /**
         * Test consistent results for multiple calls
         * 
         * @return void
         */
        public function testConsistentResults()
        {
            $amount1 = $this->calculator->calculateTargetAmount(2, 1, 1000.00, '2025-10-18');
            $amount2 = $this->calculator->calculateTargetAmount(2, 1, 1000.00, '2025-10-18');
            
            $this->assertEquals($amount1, $amount2);
        }
        
        /**
         * Test string bank account IDs are converted
         * 
         * @return void
         */
        public function testStringBankAccountIds()
        {
            // Should accept numeric strings
            $targetAmount = $this->calculator->calculateTargetAmount('2', '1', 1000.00, '2025-10-18');
            
            $this->assertEquals(1300.00, $targetAmount);
        }
    }
}  // End of namespace KsfBankImport\Tests\Services
