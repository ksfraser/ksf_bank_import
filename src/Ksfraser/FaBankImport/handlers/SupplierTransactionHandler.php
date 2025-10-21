<?php

/**
 * Supplier Transaction Handler
 *
 * Handles processing of supplier (SP) transactions in the bank import system.
 * Extracted from the large switch statement in process_statements.php and
 * the processSupplierTransaction() method from bank_import_controller.php.
 *
 * @package    Ksfraser\FaBankImport\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Handlers;

use Exception;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\SupplierPartnerType;

/**
 * Supplier Transaction Handler
 *
 * Single Responsibility: Process supplier transactions only
 * 
 * Handles two types of supplier transactions:
 * 1. Debit (D) - Supplier Payment (ST_SUPPAYMENT)
 * 2. Credit (C) - Supplier Refund (ST_BANKDEPOSIT)
 *
 * Design:
 * - Uses SupplierPartnerType value object for type safety
 * - Receives filtered transaction-specific POST data (not entire $_POST)
 * - Constructor validates partner type on instantiation (fail-fast)
 */
class SupplierTransactionHandler extends AbstractTransactionHandler
{
    /**
     * @inheritDoc
     *
     * Returns a SupplierPartnerType instance which provides:
     * - Short code: 'SP'
     * - Label: 'Supplier'
     * - Description: 'Vendor or supplier transactions (accounts payable)'
     * - Priority: 10 (high priority - common type)
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new SupplierPartnerType();
    }

    /**
     * @inheritDoc
     */
    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult {
        try {
            // Validate required data (using parent's method)
            $this->validateTransaction($transaction);
            
            // Extract partner ID from filtered POST data (using parent's method)
            $partnerId = $this->extractPartnerId($transactionPostData);
            
            // Calculate bank charges (using parent's method)
            $charge = $this->calculateCharge($transactionId);
            
            // Process based on transaction type (Debit or Credit)
            if ($transaction['transactionDC'] === 'D') {
                return $this->processSupplierPayment(
                    $transaction,
                    $partnerId,
                    $ourAccount,
                    $transactionId,
                    $collectionIds,
                    $charge
                );
            } elseif ($transaction['transactionDC'] === 'C') {
                return $this->processSupplierRefund(
                    $transaction,
                    $partnerId,
                    $ourAccount,
                    $transactionId,
                    $collectionIds,
                    $charge
                );
            } else {
                throw new Exception("Invalid transaction DC type: {$transaction['transactionDC']}");
            }
        } catch (Exception $e) {
            return $this->createErrorResult(
                'Error processing supplier transaction: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process supplier payment (Debit transaction)
     *
     * @param array $transaction Transaction data
     * @param int $partnerId Supplier ID
     * @param array $ourAccount Bank account data
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * @param float $charge Bank charge amount
     * @return TransactionResult Processing result
     * @throws Exception if processing fails
     */
    private function processSupplierPayment(
        array $transaction,
        int $partnerId,
        array $ourAccount,
        int $transactionId,
        string $collectionIds,
        float $charge
    ): TransactionResult {
        global $Refs;
        
        $trans_no = 0; // NEW payment (not an update)
        $trans_type = ST_SUPPAYMENT;
        
        // Get new reference number
        $reference = $Refs->get_next($trans_type);
        while (!is_new_reference($reference, $trans_type)) {
            $reference = $Refs->get_next($trans_type);
        }
        
        // Write supplier payment using FrontAccounting function
        $payment_id = write_supp_payment(
            $trans_no,
            $partnerId,
            $ourAccount['id'],
            sql2date($transaction['valueTimestamp']),
            $reference,
            user_numeric($transaction['transactionAmount']),
            0, // discount
            $transaction['transactionTitle'],
            user_numeric($charge),
            0  // bank_amount
        );
        
        if (!$payment_id) {
            throw new Exception('Failed to create supplier payment');
        }
        
        // Update transaction status
        $counterparty_arr = get_trans_counterparty($payment_id, $trans_type);
        update_transactions(
            $transactionId,
            $collectionIds,
            1, // status
            $payment_id,
            $trans_type,
            false, // matched
            true,  // created
            'SP',
            $partnerId
        );
        
        // Update partner data (suppliers don't have branches)
        update_partner_data($partnerId, PT_SUPPLIER, null, $transaction['account'] ?? '');
        
        return $this->createSuccessResult(
            $payment_id,
            $trans_type,
            "Supplier Payment Processed: {$payment_id}",
            [
                'view_link' => "../../gl/view/gl_trans_view.php?type_id={$trans_type}&trans_no={$payment_id}",
                'allocate_link' => "../../purchasing/allocations/supplier_allocate.php?trans_type={$trans_type}&trans_no={$payment_id}&supplier_id={$partnerId}"
            ]
        );
    }

    /**
     * Process supplier refund (Credit transaction)
     *
     * @param array $transaction Transaction data
     * @param int $partnerId Supplier ID
     * @param array $ourAccount Bank account data
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * @param float $charge Bank charge amount
     * @return TransactionResult Processing result
     * @throws Exception if processing fails
     */
    private function processSupplierRefund(
        array $transaction,
        int $partnerId,
        array $ourAccount,
        int $transactionId,
        string $collectionIds,
        float $charge
    ): TransactionResult {
        global $Refs;
        
        $trans_no = 0;
        $trans_type = ST_BANKDEPOSIT;
        
        // Get new reference
        $reference = $Refs->get_next($trans_type);
        while (!is_new_reference($reference, $trans_type)) {
            $reference = $Refs->get_next($trans_type);
        }
        
        // Create items cart for bank deposit
        $cart = new \items_cart($trans_type);
        $cart->order_id = $trans_no;
        $cart->tran_date = sql2date($transaction['valueTimestamp']);
        $cart->reference = $reference;
        
        // Make amount positive for refund
        $amount = abs(user_numeric($transaction['transactionAmount']));
        
        // Get supplier accounts
        $supplier_accounts = get_supplier($partnerId);
        
        // Add GL item for supplier payable account
        $cart->add_gl_item(
            $supplier_accounts["payable_account"],
            $supplier_accounts["dimension_id"] ?? 0,
            $supplier_accounts["dimension2_id"] ?? 0,
            $amount,
            $transaction['transactionTitle']
        );
        
        // Validate cart
        if ($cart->count_gl_items() < 1) {
            throw new Exception('You must enter at least one payment line');
        }
        
        if ($cart->gl_items_total() == 0.0) {
            throw new Exception('The total bank amount cannot be 0');
        }
        
        // Write bank transaction
        $payment_id = write_bank_transaction(
            $cart->trans_type,
            $cart->order_id,
            $ourAccount['id'],
            $cart,
            sql2date($transaction['valueTimestamp']),
            PT_SUPPLIER,
            $partnerId,
            ANY_NUMERIC,
            $cart->reference,
            $transaction['transactionTitle'],
            true,
            number_format2(abs($cart->gl_items_total()))
        );
        
        if (!$payment_id || !isset($payment_id[1])) {
            throw new Exception('Failed to create supplier refund');
        }
        
        $trans_no_created = $payment_id[1];
        
        // Update transaction status
        $counterparty_arr = get_trans_counterparty($trans_no_created, $trans_type);
        update_transactions(
            $transactionId,
            $collectionIds,
            1, // status
            $trans_no_created,
            $trans_type,
            false, // matched
            true,  // created
            'SP',
            $partnerId
        );
        
        // Update partner data
        update_partner_data($partnerId, PT_SUPPLIER, null, $transaction['account'] ?? '');
        
        return $this->createSuccessResult(
            $trans_no_created,
            $trans_type,
            "Supplier Refund Processed: {$trans_no_created}",
            [
                'view_link' => "../../gl/view/gl_trans_view.php?type_id={$trans_type}&trans_no={$trans_no_created}"
            ]
        );
    }
}
