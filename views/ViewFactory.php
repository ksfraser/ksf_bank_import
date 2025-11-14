<?php

/**
 * ViewFactory - Factory for creating PartnerType Views
 * 
 * Single Responsibility: Create and configure PartnerType Views with all dependencies
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only responsible for View instantiation
 * - Open/Closed: Can be extended with new partner types without modification
 * - Dependency Inversion: Creates and injects dependencies for Views
 * 
 * Design Pattern: Factory Method
 * 
 * Benefits:
 * - Centralizes View creation logic
 * - Ensures consistent dependency injection
 * - Reduces boilerplate in calling code
 * - Makes it easy to swap between v1 and v2 Views
 * - Provides single point to add new partner types
 * 
 * Example usage:
 * <code>
 * // Old way (v1):
 * $view = new SupplierPartnerTypeView($id, $account, $partnerId);
 * $view->display();
 * 
 * // New way (v2 with factory):
 * $view = ViewFactory::createPartnerTypeView(
 *     'supplier',
 *     $id,
 *     [
 *         'otherBankAccount' => $account,
 *         'partnerId' => $partnerId
 *     ]
 * );
 * $view->display();
 * </code>
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.0.0
 * @since      20251024
 */

namespace KsfBankImport\Views;

// Load v2 Views
require_once(__DIR__ . '/BankTransferPartnerTypeView.v2.php');
require_once(__DIR__ . '/CustomerPartnerTypeView.v2.php');
require_once(__DIR__ . '/SupplierPartnerTypeView.v2.php');
require_once(__DIR__ . '/QuickEntryPartnerTypeView.v2.php');

// Load DataProviders
require_once(__DIR__ . '/../src/Ksfraser/BankAccountDataProvider.php');
require_once(__DIR__ . '/DataProviders/CustomerDataProvider.php');
require_once(__DIR__ . '/DataProviders/SupplierDataProvider.php');
require_once(__DIR__ . '/DataProviders/QuickEntryDataProvider.php');

use Ksfraser\BankAccountDataProvider;
use KsfBankImport\Views\DataProviders\CustomerDataProvider;
use KsfBankImport\Views\DataProviders\SupplierDataProvider;
use KsfBankImport\Views\DataProviders\QuickEntryDataProvider;

/**
 * Factory for creating PartnerType Views with dependencies
 * 
 * This factory:
 * 1. Instantiates appropriate DataProvider (singleton pattern)
 * 2. Creates PartnerFormData (handled internally by Views)
 * 3. Returns configured View ready to use
 * 
 * @since 2.0.0
 */
class ViewFactory
{
    /**
     * Partner type constants
     */
    const PARTNER_TYPE_SUPPLIER = 'supplier';
    const PARTNER_TYPE_CUSTOMER = 'customer';
    const PARTNER_TYPE_BANK_TRANSFER = 'bank_transfer';
    const PARTNER_TYPE_QUICK_ENTRY = 'quick_entry';
    
    /**
     * Create a PartnerType View with all dependencies
     * 
     * @param string $partnerType The partner type ('supplier', 'customer', 'bank_transfer', 'quick_entry')
     * @param int $lineItemId The line item ID
     * @param array $context Contextual data needed for the specific view type
     * 
     * Context array keys by partner type:
     * 
     * supplier:
     *   - otherBankAccount: string - The other bank account
     *   - partnerId: int|null - The partner ID (optional)
     * 
     * customer:
     *   - otherBankAccount: string - The other bank account
     *   - valueTimestamp: string - Transaction timestamp
     *   - partnerId: int|null - The partner ID (optional)
     *   - partnerDetailId: int|null - The partner detail ID (optional)
     * 
     * bank_transfer:
     *   - otherBankAccount: string - The other bank account
     *   - transactionDC: string - Transaction direction ('C' or 'D')
     *   - partnerId: int|null - The partner ID (optional)
     *   - partnerDetailId: int|null - The partner detail ID (optional)
     * 
     * quick_entry:
     *   - transactionDC: string - Transaction direction ('C' or 'D')
     * 
     * @return object The configured View instance
     * @throws \InvalidArgumentException If partner type is unknown
     * 
     * @since 2.0.0
     */
    public static function createPartnerTypeView(
        string $partnerType,
        int $lineItemId,
        array $context
    ) {
        switch ($partnerType) {
            case self::PARTNER_TYPE_SUPPLIER:
                return self::createSupplierView($lineItemId, $context);
                
            case self::PARTNER_TYPE_CUSTOMER:
                return self::createCustomerView($lineItemId, $context);
                
            case self::PARTNER_TYPE_BANK_TRANSFER:
                return self::createBankTransferView($lineItemId, $context);
                
            case self::PARTNER_TYPE_QUICK_ENTRY:
                return self::createQuickEntryView($lineItemId, $context);
                
            default:
                throw new \InvalidArgumentException(
                    "Unknown partner type: {$partnerType}. " .
                    "Valid types: supplier, customer, bank_transfer, quick_entry"
                );
        }
    }
    
    /**
     * Create Supplier View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return SupplierPartnerTypeView
     * 
     * @since 2.0.0
     */
    protected static function createSupplierView(int $lineItemId, array $context): SupplierPartnerTypeView
    {
        $dataProvider = SupplierDataProvider::getInstance();
        
        return new SupplierPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $context['partnerId'] ?? null,
            $dataProvider
        );
    }
    
    /**
     * Create Customer View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return CustomerPartnerTypeView
     * 
     * @since 2.0.0
     */
    protected static function createCustomerView(int $lineItemId, array $context): CustomerPartnerTypeView
    {
        $dataProvider = CustomerDataProvider::getInstance();
        
        return new CustomerPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $context['valueTimestamp'] ?? '',
            $context['partnerId'] ?? null,
            $context['partnerDetailId'] ?? null,
            $dataProvider
        );
    }
    
    /**
     * Create Bank Transfer View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return BankTransferPartnerTypeView
     * 
     * @since 2.0.0
     */
    protected static function createBankTransferView(int $lineItemId, array $context): BankTransferPartnerTypeView
    {
        $dataProvider = new BankAccountDataProvider();
        
        return new BankTransferPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $context['transactionDC'] ?? 'D',
            $dataProvider,  // DataProvider is 4th parameter, before partnerId/partnerDetailId
            $context['partnerId'] ?? null,
            $context['partnerDetailId'] ?? null
        );
    }
    
    /**
     * Create Quick Entry View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return QuickEntryPartnerTypeView
     * 
     * @since 2.0.0
     */
    protected static function createQuickEntryView(int $lineItemId, array $context): QuickEntryPartnerTypeView
    {
        $transactionDC = $context['transactionDC'] ?? 'D';
        
        // Create appropriate provider based on transaction direction
        $dataProvider = ($transactionDC === 'C') 
            ? QuickEntryDataProvider::forDeposit()
            : QuickEntryDataProvider::forPayment();
        
        return new QuickEntryPartnerTypeView(
            $lineItemId,
            $transactionDC,
            $dataProvider
        );
    }
    
    /**
     * Helper: Get all valid partner type constants
     * 
     * Useful for validation and documentation
     * 
     * @return array Array of valid partner type strings
     * 
     * @since 2.0.0
     */
    public static function getValidPartnerTypes(): array
    {
        return [
            self::PARTNER_TYPE_SUPPLIER,
            self::PARTNER_TYPE_CUSTOMER,
            self::PARTNER_TYPE_BANK_TRANSFER,
            self::PARTNER_TYPE_QUICK_ENTRY,
        ];
    }
}
