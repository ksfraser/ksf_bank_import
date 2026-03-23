<?php
/**
 * Contact Data Factory
 *
 * Builds ContactData DTO objects from various transaction sources (bank statements, 
 * counterparty records, FA customer/supplier data).
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20250322
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\ContactDTO\ContactData;

/**
 * Factory for creating ContactData DTOs from bank import transaction data
 */
class ContactDataFactory
{
    /**
     * Build ContactData for a supplier transaction
     *
     * @param array $transaction          Transaction data from bi_transactions
     * @param array|null $counterparty    Counterparty record (optional)
     * @param array|null $faSupplier      FA suppliers master record (optional)
     * @return ContactData
     */
    public static function buildFromSupplier(array $transaction, ?array $counterparty = null, ?array $faSupplier = null): ContactData
    {
        $contactData = new ContactData();
        
        // Primary source: transaction title/memo
        $name = trim($transaction['transactionTitle'] ?? $transaction['title'] ?? '');
        
        // Fallback to counterparty name
        if (!$name && $counterparty) {
            $name = trim($counterparty['name'] ?? $counterparty['partner_name'] ?? '');
        }
        
        // Tertiary: FA supplier name
        if (!$name && $faSupplier) {
            $name = trim($faSupplier['supp_name'] ?? '');
        }
        
        if ($name) {
            $contactData->name = $name;
        }
        
        // Contact type: Supplier
        $contactData->contact_type = 'S';
        
        // Extract phone/email/address from counterparty if available
        if ($counterparty) {
            if (!empty($counterparty['phone'])) {
                $contactData->phone = trim($counterparty['phone']);
            }
            if (!empty($counterparty['email'])) {
                $contactData->email = trim($counterparty['email']);
            }
            if (!empty($counterparty['address'])) {
                $contactData->address = trim($counterparty['address']);
            }
        }
        
        // Extract from FA supplier if available
        if ($faSupplier) {
            if (!empty($faSupplier['supp_ref'])) {
                $contactData->reference = trim($faSupplier['supp_ref']);
            }
            if (!empty($faSupplier['address'])) {
                $contactData->address = trim($faSupplier['address']);
            }
        }
        
        // Transaction reference for deduplication
        $contactData->source_reference = $transaction['transactionCode'] ?? '';
        
        // Bank account context
        $contactData->context = [
            'account' => $transaction['account'] ?? $transaction['our_account'] ?? '',
            'statement_date' => $transaction['valueTimestamp'] ?? '',
            'transaction_id' => $transaction['id'] ?? '',
        ];
        
        return $contactData;
    }
    
    /**
     * Build ContactData for a customer transaction
     *
     * @param array $transaction          Transaction data from bi_transactions
     * @param array|null $counterparty    Counterparty record (optional)
     * @param array|null $faCustomer      FA customers master record (optional)
     * @param array|null $faBranch        FA customer branch record (optional)
     * @return ContactData
     */
    public static function buildFromCustomer(array $transaction, ?array $counterparty = null, ?array $faCustomer = null, ?array $faBranch = null): ContactData
    {
        $contactData = new ContactData();
        
        // Primary source: transaction title/memo
        $name = trim($transaction['transactionTitle'] ?? $transaction['title'] ?? '');
        
        // Fallback to counterparty name
        if (!$name && $counterparty) {
            $name = trim($counterparty['name'] ?? $counterparty['partner_name'] ?? '');
        }
        
        // Tertiary: FA customer name
        if (!$name && $faCustomer) {
            $name = trim($faCustomer['name'] ?? $faCustomer['debtor_name'] ?? '');
        }
        
        // Quaternary: FA branch contact name
        if (!$name && $faBranch) {
            $name = trim($faBranch['contact_name'] ?? '');
        }
        
        if ($name) {
            $contactData->name = $name;
        }
        
        // Contact type: Customer
        $contactData->contact_type = 'C';
        
        // Extract from counterparty
        if ($counterparty) {
            if (!empty($counterparty['phone'])) {
                $contactData->phone = trim($counterparty['phone']);
            }
            if (!empty($counterparty['email'])) {
                $contactData->email = trim($counterparty['email']);
            }
            if (!empty($counterparty['address'])) {
                $contactData->address = trim($counterparty['address']);
            }
        }
        
        // Extract from FA customer/branch
        if ($faBranch) {
            if (!empty($faBranch['email'])) {
                $contactData->email = trim($faBranch['email']);
            }
            if (!empty($faBranch['br_address'])) {
                $contactData->address = trim($faBranch['br_address']);
            }
        }
        
        if ($faCustomer && empty($contactData->email)) {
            if (!empty($faCustomer['email'])) {
                $contactData->email = trim($faCustomer['email']);
            }
        }
        
        // FA reference
        if ($faCustomer && !empty($faCustomer['debtor_ref'])) {
            $contactData->reference = trim($faCustomer['debtor_ref']);
        }
        
        // Transaction reference
        $contactData->source_reference = $transaction['transactionCode'] ?? '';
        
        // Bank account context
        $contactData->context = [
            'account' => $transaction['account'] ?? $transaction['our_account'] ?? '',
            'statement_date' => $transaction['valueTimestamp'] ?? '',
            'transaction_id' => $transaction['id'] ?? '',
            'customer_id' => $faCustomer['debtor_no'] ?? $faCustomer['id'] ?? '',
            'branch_id' => $faBranch['branch_code'] ?? '',
        ];
        
        return $contactData;
    }
    
    /**
     * Build ContactData from minimal transaction data (quick entry, generic source)
     *
     * @param array $transaction Transaction data
     * @param string $contactType Contact type ('C' for customer, 'S' for supplier)
     * @return ContactData
     */
    public static function buildFromTransaction(array $transaction, string $contactType = 'S'): ContactData
    {
        $contactData = new ContactData();
        
        // Extract name from transaction title/memo
        $name = trim($transaction['transactionTitle'] ?? $transaction['title'] ?? $transaction['memo'] ?? '');
        
        if ($name) {
            $contactData->name = $name;
        }
        
        $contactData->contact_type = $contactType;
        $contactData->source_reference = $transaction['transactionCode'] ?? '';
        
        // Context
        $contactData->context = [
            'account' => $transaction['account'] ?? $transaction['our_account'] ?? '',
            'statement_date' => $transaction['valueTimestamp'] ?? '',
            'transaction_id' => $transaction['id'] ?? '',
            'amount' => $transaction['transactionAmount'] ?? $transaction['amount'] ?? 0,
        ];
        
        return $contactData;
    }
}
