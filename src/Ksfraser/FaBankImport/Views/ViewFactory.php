<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009 / ARCH-006*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\FaBankImport\Views\DataProviders\CustomerDataProvider;
use Ksfraser\FaBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\FaBankImport\Views\DataProviders\QuickEntryDataProvider;

/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001

 * ViewFactory - Factory for creating PartnerType Views
 * 
 * Single Responsibility: Create and configure PartnerType Views with all dependencies
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
class ViewFactory
{
    const PARTNER_TYPE_SUPPLIER = 'supplier';
    const PARTNER_TYPE_CUSTOMER = 'customer';
    const PARTNER_TYPE_BANK_TRANSFER = 'bank_transfer';
    const PARTNER_TYPE_QUICK_ENTRY = 'quick_entry';
    
    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create a PartnerType View with all dependencies
     * 
     * @param string $partnerType The partner type
     * @param int $lineItemId The line item ID
     * @param array $context Contextual data
     * @return PartnerTypeViewInterface The configured view
     * @throws \InvalidArgumentException If partner type is unknown
     */
    public static function createPartnerTypeView(
        string $partnerType,
        int $lineItemId,
        array $context
    ): PartnerTypeViewInterface {
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
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create Supplier View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return SupplierPartnerTypeView
     */
    protected static function createSupplierView(int $lineItemId, array $context): SupplierPartnerTypeView
    {
        $dataProvider = SupplierDataProvider::getInstance();
        
        return new SupplierPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $dataProvider,
            $context['partnerId'] ?? null
        );
    }
    
    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create Customer View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return CustomerPartnerTypeView
     */
    protected static function createCustomerView(int $lineItemId, array $context): CustomerPartnerTypeView
    {
        $dataProvider = CustomerDataProvider::getInstance();
        
        return new CustomerPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $context['valueTimestamp'] ?? '',
            $dataProvider,
            $context['partnerId'] ?? null,
            $context['partnerDetailId'] ?? null
        );
    }
    
    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create Bank Transfer View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return BankTransferPartnerTypeView
     */
    protected static function createBankTransferView(int $lineItemId, array $context): BankTransferPartnerTypeView
    {
        $dataProvider = new BankAccountDataProvider();
        
        return new BankTransferPartnerTypeView(
            $lineItemId,
            $context['otherBankAccount'] ?? '',
            $context['transactionDC'] ?? 'D',
            $dataProvider,
            $context['partnerId'] ?? null,
            $context['partnerDetailId'] ?? null
        );
    }
    
    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Create Quick Entry View with dependencies
     * 
     * @param int $lineItemId Line item ID
     * @param array $context Context data
     * @return QuickEntryPartnerTypeView
     */
    protected static function createQuickEntryView(int $lineItemId, array $context): QuickEntryPartnerTypeView
    {
        $transactionDC = $context['transactionDC'] ?? 'D';
        
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
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     * Get all valid partner type constants
     * 
     * @return array Array of valid partner type strings
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
