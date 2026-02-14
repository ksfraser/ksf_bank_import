<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :CustomerTransactionHandler [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for CustomerTransactionHandler.
 */
/**
 * Customer Transaction Handler
 *
 * Handles processing of customer (CU) transactions in the bank import system.
 * Extracted from the large switch statement in process_statements.php (lines 205-371).
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
use Ksfraser\PartnerTypes\CustomerPartnerType;

/**
 * Customer Transaction Handler
 *
 * Single Responsibility: Process customer transactions only
 * 
 * Handles customer payments (Credit transactions only):
 * - Credit (C) - Customer Payment (ST_CUSTPAYMENT)
 * - Processes payment allocation against invoices
 * - Updates customer/branch partner data
 *
 * Design:
 * - Uses CustomerPartnerType value object for type safety
 * - Receives filtered transaction-specific POST data (not entire $_POST)
 * - Constructor validates partner type on instantiation (fail-fast)
 * - Only processes Credit transactions (customer payments)
 */
class CustomerTransactionHandler extends AbstractTransactionHandler
{
    /**
     * @inheritDoc
     *
     * Returns a CustomerPartnerType instance which provides:
     * - Short code: 'CU'
     * - Label: 'Customer'
     * - Description: 'Customer transactions (accounts receivable)'
     * - Priority: 20 (high priority - common type)
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new CustomerPartnerType();
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
            // Validate required data
            $this->validateTransaction($transaction);
            
            // Extract partner ID (customer ID)
            $partnerId = $this->extractPartnerId($transactionPostData);
            
            // Customer handler only processes Credit transactions (payments)
            if ($transaction['transactionDC'] !== 'C') {
                return $this->createErrorResult(
                    "Customer transactions must be Credit (C). Got: {$transaction['transactionDC']}"
                );
            }
            
            // Calculate bank charges
            $charge = $this->calculateCharge($transactionId);
            
            // Process customer payment
            return $this->processCustomerPayment(
                $transaction,
                $partnerId,
                $transactionPostData,
                $ourAccount,
                $transactionId,
                $collectionIds,
                $charge
            );
        } catch (Exception $e) {
            return $this->createErrorResult(
                'Error processing customer transaction: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process customer payment (Credit transaction)
     *
     * @param array $transaction Transaction data
     * @param int $partnerId Customer ID
     * @param array $transactionPostData Filtered POST data
     * @param array $ourAccount Bank account data
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * @param float $charge Bank charge amount
     * @return TransactionResult Processing result
     * @throws Exception if processing fails
     */
    private function processCustomerPayment(
        array $transaction,
        int $partnerId,
        array $transactionPostData,
        array $ourAccount,
        int $transactionId,
        string $collectionIds,
        float $charge
    ): TransactionResult {
        $trans_no = 0;
        $trans_type = ST_CUSTPAYMENT;
        
        // Get unique reference using service
        $reference = $this->referenceService->getUniqueReference($trans_type);
        
        // Extract branch ID and invoice number from POST data
        $branchId = $transactionPostData['partnerDetailId'] ?? 0;
        $invoiceNo = $transactionPostData['invoice'] ?? null;
        $comment = $transactionPostData['comment'] ?? '';
        
        // Build transaction title
        $memo = $transaction['transactionTitle'] ?? '';
        if (strlen($memo) < 4 && !empty($transaction['memo'])) {
            $memo .= " : " . $transaction['memo'];
        }
        if (!empty($comment)) {
            $memo .= "::" . $comment;
        }
        
        // Calculate amount
        $amount = user_numeric($transaction['transactionAmount']);
        
        // Write customer payment
        $payment_id = my_write_customer_payment(
            $trans_no,
            $partnerId,
            $branchId,
            $ourAccount['id'],
            sql2date($transaction['valueTimestamp']),
            $reference,
            $amount,
            $discount = 0,
            $memo,
            $rate = 0,
            user_numeric($charge),
            $bank_amount = 0,
            $trans_type
        );
        
        if (!$payment_id) {
            throw new Exception('Failed to create customer payment');
        }
        
        // If invoice number provided, allocate payment against invoice
        if ($invoiceNo) {
            add_cust_allocation(
                $amount,
                ST_CUSTPAYMENT,
                $payment_id,
                ST_SALESINVOICE,
                $invoiceNo,
                $partnerId,
                sql2date($transaction['valueTimestamp'])
            );
            
            update_debtor_trans_allocation(ST_SALESINVOICE, $invoiceNo, $partnerId);
            update_debtor_trans_allocation(ST_CUSTPAYMENT, $payment_id, $partnerId);
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
            'CU',
            $partnerId
        );
        
        // Update partner data (customer and branch)
        update_partner_data($partnerId, PT_CUSTOMER, $branchId, $transaction['memo'] ?? '');
        update_partner_data($partnerId, $trans_type, $branchId, $transaction['memo'] ?? '');
        
        $successMessage = $invoiceNo 
            ? "Customer Payment Processed and Allocated to Invoice {$invoiceNo}: {$payment_id}"
            : "Customer Payment Processed: {$payment_id}";
        
        return $this->createSuccessResult(
            $payment_id,
            $trans_type,
            $successMessage,
            [
                'invoice_no' => $invoiceNo,
                'view_gl_link' => "../../gl/view/gl_trans_view.php?type_id={$trans_type}&trans_no={$payment_id}",
                'view_receipt_link' => "../../sales/view/view_receipt.php?type_id={$trans_type}&trans_no={$payment_id}",
                'allocate_link' => $invoiceNo ? null : "../../sales/allocations/customer_allocate.php?trans_no={$payment_id}&trans_type={$trans_type}&debtor_no={$partnerId}"
            ]
        );
    }
}
