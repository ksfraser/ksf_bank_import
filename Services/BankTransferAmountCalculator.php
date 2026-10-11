<?php
/**
 * Bank Transfer Amount Calculator Service
 * 
 * Single Responsibility: Calculate target amounts for bank transfers.
 * Handles both same-currency and forex transfers by retrieving bank account
 * details and applying appropriate exchange rates.
 * 
 * UML Class Diagram:
 * <code>
 * ┌─────────────────────────────────────────┐
 * │  BankTransferAmountCalculator           │
 * ├─────────────────────────────────────────┤
 * │ - exchangeRateService                   │
 * ├─────────────────────────────────────────┤
 * │ + __construct(ExchangeRateService)      │
 * │ + calculateTargetAmount(...): float     │
 * │ + getBankCurrencies(...): array         │
 * └─────────────────────────────────────────┘
 *            │
 *            │ uses
 *            ▼
 * ┌─────────────────────────────────────────┐
 * │      ExchangeRateService                │
 * ├─────────────────────────────────────────┤
 * │ + getRate(from, to, date): float        │
 * └─────────────────────────────────────────┘
 *            │
 *            │ uses
 *            ▼
 * ┌─────────────────────────────────────────┐
 * │  FrontAccounting Functions              │
 * ├─────────────────────────────────────────┤
 * │  get_bank_account(id): array            │
 * │  get_exchange_rate_from_to(...)         │
 * └─────────────────────────────────────────┘
 * </code>
 * 
 * UML Sequence Diagram:
 * <code>
 * Caller          Calculator    ExchangeRateService   FrontAccounting
 *   │                 │                 │                    │
 *   │ calculateTargetAmount(from_id, to_id, amount, date)   │
 *   ├────────────────>│                 │                    │
 *   │                 │                 │                    │
 *   │                 │ get_bank_account(from_id)           │
 *   │                 ├─────────────────────────────────────>│
 *   │                 │                 │                    │
 *   │                 │    from_bank {currency: 'USD'}      │
 *   │                 │<─────────────────────────────────────┤
 *   │                 │                 │                    │
 *   │                 │ get_bank_account(to_id)             │
 *   │                 ├─────────────────────────────────────>│
 *   │                 │                 │                    │
 *   │                 │    to_bank {currency: 'CAD'}        │
 *   │                 │<─────────────────────────────────────┤
 *   │                 │                 │                    │
 *   │                 │ getRate('USD','CAD',date)           │
 *   │                 ├────────────────>│                    │
 *   │                 │                 │                    │
 *   │                 │                 │ get_exchange_rate_from_to()
 *   │                 │                 ├───────────────────>│
 *   │                 │                 │                    │
 *   │                 │                 │    rate = 1.30     │
 *   │                 │                 │<───────────────────┤
 *   │                 │                 │                    │
 *   │                 │   rate = 1.30   │                    │
 *   │                 │<────────────────┤                    │
 *   │                 │                 │                    │
 *   │                 │ calculate: amount * rate             │
 *   │                 ├──────┐          │                    │
 *   │                 │<─────┘          │                    │
 *   │                 │                 │                    │
 *   │  target_amount  │                 │                    │
 *   │<────────────────┤                 │                    │
 * </code>
 * 
 * Background:
 * Created as part of Mantis Bug #3198 fix to encapsulate the logic for
 * calculating target amounts in bank transfers. This follows the Facade
 * pattern to simplify the complex interaction between bank accounts and
 * exchange rates.
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

require_once(__DIR__ . '/ExchangeRateService.php');

/**
 * Service for calculating bank transfer target amounts
 * 
 * This class simplifies the calculation of target amounts for bank transfers
 * by handling all the complexity internally:
 * - Fetching bank account details
 * - Extracting currency codes
 * - Getting exchange rates
 * - Calculating target amounts
 * 
 * Design Pattern: Facade Pattern
 * - Provides a simple interface to a complex subsystem
 * - Encapsulates the interaction between multiple services and FA functions
 * 
 * Example usage:
 * <code>
 * $calculator = new BankTransferAmountCalculator();
 * 
 * // Calculate target amount for bank transfer
 * $target_amount = $calculator->calculateTargetAmount(
 *     $from_bank_id,    // From bank account ID
 *     $to_bank_id,      // To bank account ID
 *     1000.00,          // Source amount
 *     '2025-10-18'      // Transfer date
 * );
 * 
 * // Use the target amount
 * $bankTransfer->set('amount', $source_amount);
 * $bankTransfer->set('target_amount', $target_amount);
 * </code>
 * 
 * @since 1.0.0
 */
class BankTransferAmountCalculator
{
    /**
     * Exchange rate service
     * 
     * @var ExchangeRateService
     */
    private $exchangeRateService;
    
    /**
     * Constructor
     * 
     * @param ExchangeRateService|null $exchangeRateService Optional service for DI/testing
     * 
     * @since 1.0.0
     */
    public function __construct($exchangeRateService = null)
    {
        $this->exchangeRateService = $exchangeRateService ?? new ExchangeRateService();
    }
    
    /**
     * Calculate target amount for bank transfer
     * 
     * Given the source and destination bank account IDs, this method:
     * 1. Fetches both bank accounts from FrontAccounting
     * 2. Extracts their currency codes
     * 3. Gets the exchange rate (1.0 for same currency, actual for forex)
     * 4. Calculates and returns the target amount
     * 
     * This encapsulates all the complexity of bank transfer amount calculation
     * in one convenient method call.
     * 
     * @param int    $fromBankAccountId Source bank account ID
     * @param int    $toBankAccountId   Destination bank account ID
     * @param float  $sourceAmount      Amount in source currency
     * @param string $date              Transfer date (YYYY-MM-DD)
     * 
     * @return float Target amount in destination currency
     * 
     * @throws \InvalidArgumentException If inputs are invalid
     * @throws \RuntimeException If bank accounts cannot be retrieved or exchange rate fails
     * 
     * @since 1.0.0
     */
    public function calculateTargetAmount($fromBankAccountId, $toBankAccountId, $sourceAmount, $date)
    {
        // Validate inputs
        if (!is_numeric($fromBankAccountId) || $fromBankAccountId <= 0) {
            throw new \InvalidArgumentException(
                "From bank account ID must be a positive integer, got: " . var_export($fromBankAccountId, true)
            );
        }
        
        if (!is_numeric($toBankAccountId) || $toBankAccountId <= 0) {
            throw new \InvalidArgumentException(
                "To bank account ID must be a positive integer, got: " . var_export($toBankAccountId, true)
            );
        }
        
        if (!is_numeric($sourceAmount) || $sourceAmount < 0) {
            throw new \InvalidArgumentException(
                "Source amount must be a non-negative number, got: " . var_export($sourceAmount, true)
            );
        }
        
        // Get bank account details from FrontAccounting
        $fromBank = get_bank_account($fromBankAccountId);
        $toBank = get_bank_account($toBankAccountId);
        
        // Validate bank accounts were retrieved successfully
        if (empty($fromBank) || !isset($fromBank['bank_curr_code'])) {
            throw new \RuntimeException(
                "Failed to retrieve source bank account with ID: {$fromBankAccountId}"
            );
        }
        
        if (empty($toBank) || !isset($toBank['bank_curr_code'])) {
            throw new \RuntimeException(
                "Failed to retrieve destination bank account with ID: {$toBankAccountId}"
            );
        }
        
        // Get currencies
        $fromCurrency = $fromBank['bank_curr_code'];
        $toCurrency = $toBank['bank_curr_code'];
        
        // Get exchange rate (1.0 for same currency, actual rate for forex)
        $exchangeRate = $this->exchangeRateService->getRate($fromCurrency, $toCurrency, $date);
        
        // Calculate target amount
        $targetAmount = $sourceAmount * $exchangeRate;
        
        return $targetAmount;
    }
    
    /**
     * Get currency codes for bank accounts
     * 
     * Convenience method to retrieve the currency codes for source and
     * destination bank accounts. Useful for debugging or validation.
     * 
     * @param int $fromBankAccountId Source bank account ID
     * @param int $toBankAccountId   Destination bank account ID
     * 
     * @return array Associative array with keys: 'from_currency', 'to_currency', 'is_forex'
     * 
     * @throws \InvalidArgumentException If inputs are invalid
     * @throws \RuntimeException If bank accounts cannot be retrieved
     * 
     * @since 1.0.0
     */
    public function getBankCurrencies($fromBankAccountId, $toBankAccountId)
    {
        // Validate inputs
        if (!is_numeric($fromBankAccountId) || $fromBankAccountId <= 0) {
            throw new \InvalidArgumentException(
                "From bank account ID must be a positive integer, got: " . var_export($fromBankAccountId, true)
            );
        }
        
        if (!is_numeric($toBankAccountId) || $toBankAccountId <= 0) {
            throw new \InvalidArgumentException(
                "To bank account ID must be a positive integer, got: " . var_export($toBankAccountId, true)
            );
        }
        
        // Get bank account details
        $fromBank = get_bank_account($fromBankAccountId);
        $toBank = get_bank_account($toBankAccountId);
        
        // Validate bank accounts
        if (empty($fromBank) || !isset($fromBank['bank_curr_code'])) {
            throw new \RuntimeException(
                "Failed to retrieve source bank account with ID: {$fromBankAccountId}"
            );
        }
        
        if (empty($toBank) || !isset($toBank['bank_curr_code'])) {
            throw new \RuntimeException(
                "Failed to retrieve destination bank account with ID: {$toBankAccountId}"
            );
        }
        
        $fromCurrency = $fromBank['bank_curr_code'];
        $toCurrency = $toBank['bank_curr_code'];
        
        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'is_forex' => ($fromCurrency !== $toCurrency)
        ];
    }
}
