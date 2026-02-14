<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :QuickEntryTransactionHandler [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for QuickEntryTransactionHandler.
 */
/**
 * QuickEntryTransactionHandler.php
 * 
 * Handles Quick Entry (QE) transactions for both bank payments and deposits.
 * Quick Entry transactions use FrontAccounting's Quick Entry system to create
 * GL entries with predefined templates.
 * 
 * Transaction Types:
 * - Debit (D): ST_BANKPAYMENT - Bank payment using Quick Entry template
 * - Credit (C): ST_BANKDEPOSIT - Bank deposit using Quick Entry template
 * 
 * Processing Flow:
 * 1. Determine transaction type based on Debit/Credit
 * 2. Create items_cart with appropriate transaction type
 * 3. Load Quick Entry template into cart (qe_to_cart)
 * 4. Add transaction reference tracking entries (0.01 offset)
 * 5. Add bank charges if applicable
 * 6. Write bank transaction to FrontAccounting
 * 7. Update transaction status and partner data
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Handlers
 */

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\FaBankImport\Config\BankImportConfig;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\QuickEntryPartnerType;

class QuickEntryTransactionHandler extends AbstractTransactionHandler
{
    /**
     * Get the partner type instance for Quick Entry transactions
     * 
     * @return PartnerTypeInterface Returns QuickEntryPartnerType value object
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new QuickEntryPartnerType();
    }

    /**
     * Process a Quick Entry transaction
     * 
     * @param array $transaction Complete transaction record from bi_transactions
     * @param array $transactionPostData Filtered POST data for this transaction
     * @param int $transactionId The transaction ID
     * @param string $collectionIds Collection IDs string
     * @param array $ourAccount Bank account information
     * 
     * @return TransactionResult Success result with links, or error result
     */
    public function process(
        array $transaction,
        array $transactionPostData,
        int $transactionId,
        string $collectionIds,
        array $ourAccount
    ): TransactionResult {
        // Validate required fields
        if (empty($transaction['transactionAmount'])) {
            return $this->createErrorResult(
                'Missing required field: transactionAmount'
            );
        }

        if (empty($transaction['transactionDC'])) {
            return $this->createErrorResult(
                'Missing required field: transactionDC'
            );
        }

        // Extract partner ID (Quick Entry template ID)
        try {
            $partnerId = $this->extractPartnerId($transactionPostData);
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Partner ID (Quick Entry template) is required'
            );
        }

        // Calculate bank charges (using parent's method)
        $charge = $this->calculateCharge($transactionId);

        // Determine transaction type based on Debit/Credit
        $transType = ($transaction['transactionDC'] === 'D') 
            ? ST_BANKPAYMENT 
            : ST_BANKDEPOSIT;

        // Process the Quick Entry transaction
        return $this->processQuickEntry(
            $transaction,
            $partnerId,
            $transactionPostData,
            $ourAccount,
            $transactionId,
            $collectionIds,
            $charge,
            $transType
        );
    }

    /**
     * Process a Quick Entry transaction
     * 
     * Creates an items_cart, loads the Quick Entry template, adds transaction
     * references and charges, then writes to FrontAccounting.
     * 
     * @param array $transaction Complete transaction data
     * @param int $partnerId Quick Entry template ID
     * @param array $transactionPostData Filtered POST data
     * @param array $ourAccount Bank account information
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * @param float $charge Bank charge amount
     * @param int $transType ST_BANKPAYMENT or ST_BANKDEPOSIT
     * 
     * @return TransactionResult Success with links or error
     */
    private function processQuickEntry(
        array $transaction,
        int $partnerId,
        array $transactionPostData,
        array $ourAccount,
        int $transactionId,
        string $collectionIds,
        float $charge,
        int $transType
    ): TransactionResult {
        // Create items cart with appropriate transaction type
        $cart = new \items_cart($transType);
        $cart->order_id = 0;
        $cart->original_amount = $transaction['transactionAmount'] + $charge;

        // Generate unique reference using service
        $cart->reference = $this->referenceService->getUniqueReference($cart->trans_type);

        // Set transaction date
        $cart->tran_date = sql2date($transaction['valueTimestamp']);

        // Ensure date is in fiscal year
        if (!is_date_in_fiscalyear($cart->tran_date)) {
            $cart->tran_date = end_fiscalyear();
        }

        // Build transaction title/memo
        $transactionTitle = $transaction['transactionTitle'];
        if (strlen($transactionTitle) < 4 && !empty($transaction['memo'])) {
            $transactionTitle .= " : " . $transaction['memo'];
        }

        // Build QE memo with account and transaction details
        $comment = $transactionPostData['comment'] ?? '';
        $qeMemo = sprintf(
            "%s A:%s:%s M:%s:%s: %s",
            $comment,
            $ourAccount['bank_account_name'],
            $transaction['account_name'] ?? '',
            $transaction['account'] ?? '',
            $transactionTitle,
            $transaction['transactionCode'] ?? ''
        );

        // Load Quick Entry template into cart
        try {
            $qeType = ($transaction['transactionDC'] === 'C') 
                ? QE_DEPOSIT 
                : QE_PAYMENT;
            
            $rval = qe_to_cart(
                $cart,
                $partnerId,
                $transaction['transactionAmount'],
                $qeType,
                $qeMemo
            );
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to load Quick Entry template: ' . $e->getMessage()
            );
        }

        // Add transaction reference tracking entries (offset to 0)
        // This allows tracking the original bank transaction code
        // Configurable via BankImportConfig::getTransRefLoggingEnabled() and getTransRefAccount()
        if (BankImportConfig::getTransRefLoggingEnabled()) {
            $transCode = $transaction['transactionCode'] ?? 'N/A';
            $refAccount = BankImportConfig::getTransRefAccount();
            
            $cart->add_gl_item(
                $refAccount, 
                0, 
                0, 
                0.01, 
                'TransRef::' . $transCode, 
                "Trans Ref"
            );
            $cart->add_gl_item(
                $refAccount, 
                0, 
                0, 
                -0.01, 
                'TransRef::' . $transCode, 
                "Trans Ref"
            );
        }

        // Check if cart has items
        $total = $cart->gl_items_total();
        if ($total == 0) {
            return $this->createErrorResult(
                "Quick Entry not loaded: rval=$rval, total=$total"
            );
        }

        // Add bank charge if applicable
        if ($charge != 0) {
            $chargeAccount = get_company_pref('bank_charge_act');
            $cart->add_gl_item(
                $chargeAccount,
                0,
                0,
                $charge,
                'Charge/' . $transactionTitle
            );
        }

        // Write transaction to FrontAccounting
        begin_transaction();

        $trans = write_bank_transaction(
            $cart->trans_type,
            $cart->order_id,
            $ourAccount['id'],
            $cart,
            sql2date($transaction['valueTimestamp']),
            PT_QUICKENTRY,
            $partnerId,
            0,
            $cart->reference,
            $qeMemo,
            true,
            null
        );

        // Update transaction status
        $counterpartyArr = get_trans_counterparty($trans[1], $transType);
        update_transactions(
            $transactionId,
            $collectionIds,
            1, // status = processed
            $trans[1], // trans_no
            $transType,
            false,
            true,
            "QE",
            $partnerId
        );

        commit_transaction();

        // Update bank partner data
        set_bank_partner_data(
            $ourAccount['id'],
            $transType,
            $partnerId,
            $transactionTitle
        );

        // Build success message with links
        $transTypeName = ($transType === ST_BANKPAYMENT) ? 'Bank Payment' : 'Bank Deposit';
        $message = sprintf(
            'Quick Entry %s processed successfully (Trans #%d)',
            $transTypeName,
            $trans[1]
        );

        return $this->createSuccessResult(
            $trans[1],    // trans_no
            $transType,   // trans_type
            $message,
            [
                'view_gl_link' => "../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$trans[1]}",
                'attach_link' => "http://fhsws002.ksfraser.com/infra/accounting/admin/attachments.php?filterType={$transType}&trans_no={$trans[1]}"
            ]
        );
    }
}
