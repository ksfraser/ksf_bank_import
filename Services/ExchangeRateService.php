<?php
/**
 * Exchange Rate Service
 * 
 * Single Responsibility: Retrieve exchange rates between currencies.
 * Always returns a rate (1.0 for same currency, actual rate for different currencies).
 * 
 * UML Class Diagram:
 * <code>
 * ┌─────────────────────────────────────────┐
 * │      ExchangeRateService                │
 * ├─────────────────────────────────────────┤
 * │ + getRate(from, to, date): float        │
 * │ + calculateTargetAmount(...): float     │
 * │ - validateCurrency(code, name): void    │
 * │ - validateDate(date): void              │
 * └─────────────────────────────────────────┘
 *            │
 *            │ uses
 *            ▼
 * ┌─────────────────────────────────────────┐
 * │  FrontAccounting Functions              │
 * ├─────────────────────────────────────────┤
 * │  get_exchange_rate_from_to(...)         │
 * └─────────────────────────────────────────┘
 * </code>
 * 
 * UML Sequence Diagram (Forex Transfer):
 * <code>
 * Caller          ExchangeRateService    FrontAccounting
 *   │                    │                      │
 *   │ getRate(USD,CAD)  │                      │
 *   ├──────────────────>│                      │
 *   │                    │                      │
 *   │                    │ validateCurrency()   │
 *   │                    ├──────┐               │
 *   │                    │<─────┘               │
 *   │                    │                      │
 *   │                    │ validateDate()       │
 *   │                    ├──────┐               │
 *   │                    │<─────┘               │
 *   │                    │                      │
 *   │                    │ get_exchange_rate_from_to()
 *   │                    ├─────────────────────>│
 *   │                    │                      │
 *   │                    │      rate = 1.30     │
 *   │                    │<─────────────────────┤
 *   │                    │                      │
 *   │   rate = 1.30      │                      │
 *   │<───────────────────┤                      │
 *   │                    │                      │
 *   │ target = amount * rate                    │
 *   ├───────┐            │                      │
 *   │<──────┘            │                      │
 * </code>
 * 
 * UML Sequence Diagram (Same Currency):
 * <code>
 * Caller          ExchangeRateService
 *   │                    │
 *   │ getRate(CAD,CAD)  │
 *   ├──────────────────>│
 *   │                    │
 *   │                    │ validateCurrency()
 *   │                    ├──────┐
 *   │                    │<─────┘
 *   │                    │
 *   │                    │ currencies match?
 *   │                    ├──────┐
 *   │                    │ YES  │
 *   │                    │<─────┘
 *   │                    │
 *   │   rate = 1.0       │
 *   │<───────────────────┤
 *   │                    │
 *   │ target = amount * 1.0
 *   ├───────┐            │
 *   │<──────┘            │
 * </code>
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 */

namespace KsfBankImport\Services;

/**
 * Service for retrieving currency exchange rates
 * 
 * This class has a single responsibility: get the exchange rate between
 * two currencies for a given date. It simplifies the calling code by
 * always returning a rate (1.0 for same currency pairs).
 * 
 * Background:
 * Created to fix Mantis Bug #3198 - Forex transfer bug where exchange
 * variance was accumulating (8¢, 16¢, etc.) due to incorrect target_amount
 * calculation in forex transfers.
 * 
 * Design Pattern: Single Responsibility Principle (SRP)
 * - This service has ONE job: retrieve exchange rates
 * - Always returns a usable rate (no null/false to check)
 * - Simplifies caller code to always multiply: target = amount × rate
 * 
 * Example usage:
 * <code>
 * $service = new ExchangeRateService();
 * 
 * // Same currency
 * $rate = $service->getRate('CAD', 'CAD', '2025-10-18'); // Returns 1.0
 * 
 * // Different currencies
 * $rate = $service->getRate('USD', 'CAD', '2025-10-18'); // Returns 1.30 (example)
 * 
 * // Use the rate
 * $target_amount = $source_amount * $rate;
 * </code>
 * 
 * @since 1.0.0
 */
class ExchangeRateService
{
    /**
     * Get exchange rate between two currencies
     * 
     * Returns the exchange rate to convert from the source currency to
     * the target currency on the specified date. If currencies are the
     * same, returns 1.0 (no conversion needed).
     * 
     * This method simplifies caller code by always returning a usable rate:
     * - Same currency: 1.0
     * - Different currency: actual rate from FrontAccounting
     * 
     * This allows callers to always multiply by the rate without checking
     * if currencies match.
     * 
     * @param string $fromCurrency Source currency code (e.g., 'USD', 'CAD')
     * @param string $toCurrency   Target currency code (e.g., 'USD', 'CAD')
     * @param string $date         Date for exchange rate (format: YYYY-MM-DD)
     * 
     * @return float Exchange rate (always >= 0, typically > 0)
     *               Returns 1.0 if currencies are the same
     *               Returns actual rate if currencies differ
     * 
     * @throws \InvalidArgumentException If currencies are empty or invalid
     * @throws \RuntimeException If exchange rate cannot be retrieved from FA
     * 
     * @since 1.0.0
     */
    public function getRate($fromCurrency, $toCurrency, $date)
    {
        // Validate inputs
        $this->validateCurrency($fromCurrency, 'fromCurrency');
        $this->validateCurrency($toCurrency, 'toCurrency');
        $this->validateDate($date);
        
        // Same currency = no conversion needed, rate is 1.0
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        // Different currencies - get rate from FrontAccounting
        $rate = get_exchange_rate_from_to($fromCurrency, $toCurrency, $date);
        
        // Validate rate is sensible
        if (!is_numeric($rate) || $rate <= 0) {
            throw new \RuntimeException(
                "Invalid exchange rate returned for {$fromCurrency} to {$toCurrency} on {$date}: {$rate}"
            );
        }
        
        return (float) $rate;
    }
    
    /**
     * Validate currency code
     * 
     * @param string $currency Currency code to validate
     * @param string $paramName Parameter name for error messages
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If currency is invalid
     * 
     * @since 1.0.0
     */
    private function validateCurrency($currency, $paramName)
    {
        if (empty($currency) || !is_string($currency)) {
            throw new \InvalidArgumentException(
                "{$paramName} must be a non-empty string, got: " . var_export($currency, true)
            );
        }
        
        // Currency codes are typically 3 characters (ISO 4217)
        if (strlen($currency) < 2 || strlen($currency) > 10) {
            throw new \InvalidArgumentException(
                "{$paramName} has invalid length (expected 2-10 chars): {$currency}"
            );
        }
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string to validate
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If date is invalid
     * 
     * @since 1.0.0
     */
    private function validateDate($date)
    {
        if (empty($date) || !is_string($date)) {
            throw new \InvalidArgumentException(
                "Date must be a non-empty string, got: " . var_export($date, true)
            );
        }
        
        // Basic date format check (YYYY-MM-DD or similar)
        // FrontAccounting accepts various formats, so we keep validation loose
        if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}/', $date)) {
            throw new \InvalidArgumentException(
                "Date must be in YYYY-MM-DD format, got: {$date}"
            );
        }
    }
    
    /**
     * Calculate target amount using exchange rate
     * 
     * Convenience method that combines rate retrieval and calculation.
     * 
     * @param float  $sourceAmount Amount in source currency
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency   Target currency code
     * @param string $date         Date for exchange rate
     * 
     * @return float Amount in target currency
     * 
     * @throws \InvalidArgumentException If inputs are invalid
     * @throws \RuntimeException If exchange rate cannot be retrieved
     * 
     * @since 1.0.0
     */
    public function calculateTargetAmount($sourceAmount, $fromCurrency, $toCurrency, $date)
    {
        if (!is_numeric($sourceAmount) || $sourceAmount < 0) {
            throw new \InvalidArgumentException(
                "Source amount must be a non-negative number, got: {$sourceAmount}"
            );
        }
        
        $rate = $this->getRate($fromCurrency, $toCurrency, $date);
        
        return $sourceAmount * $rate;
    }
}
