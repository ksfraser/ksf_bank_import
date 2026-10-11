<?php
/**
 * Unit tests for ExchangeRateService
 * 
 * @package    KsfBankImport
 * @subpackage Tests
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 */

// Define mock function in the Services namespace where ExchangeRateService will look for it
namespace KsfBankImport\Services {
    if (!function_exists('KsfBankImport\Services\get_exchange_rate_from_to')) {
        function get_exchange_rate_from_to($from, $to, $date) {
            // Mock exchange rates for testing
            $rates = [
                'USD_CAD' => 1.30,
                'CAD_USD' => 0.77,
                'USD_EUR' => 0.85,
                'EUR_USD' => 1.18,
            ];
            
            $key = "{$from}_{$to}";
            return $rates[$key] ?? 1.0;
        }
    }
}

// Now define the test class in its own namespace
namespace KsfBankImport\Tests\Services {
    use PHPUnit\Framework\TestCase;
    use KsfBankImport\Services\ExchangeRateService;
    
    require_once(__DIR__ . '/../Services/ExchangeRateService.php');

/**
 * Test suite for ExchangeRateService
 * 
 * @since 1.0.0
 */
class ExchangeRateServiceTest extends TestCase
{
    /**
     * Service instance
     * 
     * @var ExchangeRateService
     */
    private $service;
    
    /**
     * Set up test fixtures
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->service = new ExchangeRateService();
    }
    
    /**
     * Test same currency returns rate of 1.0
     * 
     * @return void
     */
    public function testSameCurrencyReturnsOne()
    {
        $rate = $this->service->getRate('CAD', 'CAD', '2025-10-18');
        
        $this->assertSame(1.0, $rate);
        $this->assertIsFloat($rate);
    }
    
    /**
     * Test different currencies return actual exchange rate
     * 
     * @return void
     */
    public function testDifferentCurrenciesReturnActualRate()
    {
        $rate = $this->service->getRate('USD', 'CAD', '2025-10-18');
        
        $this->assertSame(1.30, $rate);
        $this->assertIsFloat($rate);
    }
    
    /**
     * Test reverse currency pair
     * 
     * @return void
     */
    public function testReverseCurrencyPair()
    {
        $rate = $this->service->getRate('CAD', 'USD', '2025-10-18');
        
        $this->assertSame(0.77, $rate);
    }
    
    /**
     * Test another currency pair (USD to EUR)
     * 
     * @return void
     */
    public function testUsdToEur()
    {
        $rate = $this->service->getRate('USD', 'EUR', '2025-10-18');
        
        $this->assertSame(0.85, $rate);
    }
    
    /**
     * Test empty from currency throws exception
     * 
     * @return void
     */
    public function testEmptyFromCurrencyThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fromCurrency must be a non-empty string');
        
        $this->service->getRate('', 'CAD', '2025-10-18');
    }
    
    /**
     * Test empty to currency throws exception
     * 
     * @return void
     */
    public function testEmptyToCurrencyThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('toCurrency must be a non-empty string');
        
        $this->service->getRate('USD', '', '2025-10-18');
    }
    
    /**
     * Test null currency throws exception
     * 
     * @return void
     */
    public function testNullCurrencyThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->getRate(null, 'CAD', '2025-10-18');
    }
    
    /**
     * Test empty date throws exception
     * 
     * @return void
     */
    public function testEmptyDateThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date must be a non-empty string');
        
        $this->service->getRate('USD', 'CAD', '');
    }
    
    /**
     * Test invalid date format throws exception
     * 
     * @return void
     */
    public function testInvalidDateFormatThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date must be in YYYY-MM-DD format');
        
        $this->service->getRate('USD', 'CAD', 'invalid-date');
    }
    
    /**
     * Test various valid date formats
     * 
     * @return void
     */
    public function testValidDateFormats()
    {
        // Standard format
        $rate1 = $this->service->getRate('USD', 'CAD', '2025-10-18');
        $this->assertIsFloat($rate1);
        
        // Single digit month/day
        $rate2 = $this->service->getRate('USD', 'CAD', '2025-1-5');
        $this->assertIsFloat($rate2);
    }
    
    /**
     * Test calculateTargetAmount method
     * 
     * @return void
     */
    public function testCalculateTargetAmount()
    {
        // Same currency
        $amount1 = $this->service->calculateTargetAmount(1000, 'CAD', 'CAD', '2025-10-18');
        $this->assertSame(1000.0, $amount1);
        
        // Forex (USD to CAD at 1.30)
        $amount2 = $this->service->calculateTargetAmount(1000, 'USD', 'CAD', '2025-10-18');
        $this->assertSame(1300.0, $amount2);
    }
    
    /**
     * Test calculateTargetAmount with negative amount throws exception
     * 
     * @return void
     */
    public function testCalculateTargetAmountNegativeThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source amount must be a non-negative number');
        
        $this->service->calculateTargetAmount(-100, 'USD', 'CAD', '2025-10-18');
    }
    
    /**
     * Test calculateTargetAmount with zero amount
     * 
     * @return void
     */
    public function testCalculateTargetAmountZero()
    {
        $amount = $this->service->calculateTargetAmount(0, 'USD', 'CAD', '2025-10-18');
        
        $this->assertSame(0.0, $amount);
    }
    
    /**
     * Test currency code length validation
     * 
     * @return void
     */
    public function testCurrencyCodeTooShort()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid length');
        
        $this->service->getRate('U', 'CAD', '2025-10-18');
    }
    
    /**
     * Test currency code length validation (too long)
     * 
     * @return void
     */
    public function testCurrencyCodeTooLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid length');
        
        $this->service->getRate('VERYLONGCODE', 'CAD', '2025-10-18');
    }
    
    /**
     * Test that rate is always positive and consistent
     * 
     * @return void
     */
    public function testRateIsAlwaysPositive()
    {
        $rate1 = $this->service->getRate('USD', 'CAD', '2025-10-18');
        $this->assertGreaterThan(0, $rate1);
        
        $rate2 = $this->service->getRate('CAD', 'CAD', '2025-10-18');
        $this->assertGreaterThan(0, $rate2);
    }
    
    /**
     * Test multiple calls return consistent results
     * 
     * @return void
     */
    public function testConsistentResults()
    {
        $rate1 = $this->service->getRate('USD', 'CAD', '2025-10-18');
        $rate2 = $this->service->getRate('USD', 'CAD', '2025-10-18');
        
        $this->assertSame($rate1, $rate2);
    }
}
}  // End of namespace KsfBankImport\Tests\Services
