<?php
/**
 * MatchedTransactionHandler.php
 * 
 * Handles Matched (ZZ) transactions for automatically matched bank transactions.
 * This handler processes bank transactions that have been automatically matched
 * to existing FrontAccounting entries through the matching algorithm.
 * 
 * Purpose:
 * Matched transactions are those where the system has automatically identified
 * a corresponding FrontAccounting transaction based on:
 * - Transaction amount
 * - Transaction date
 * - Counterparty information
 * - Transaction description/memo
 * - Matching score threshold
 * 
 * Processing Flow:
 * 1. Validate required fields (trans_no and trans_type from POST)
 * 2. Get counterparty information from the matched FA entry
 * 3. Extract person_id and person_type_id from counterparty data
 * 4. Update the bank transaction status to link it to the matched entry
 * 5. Update partner data with transaction memo/title
 * 6. Provide links to view the matched entry
 * 
 * Special Cases:
 * - Type 12 (ST_CUSTPAYMENT): Provides additional link to view payment and invoice
 * 
 * Differences from Manual Settlement:
 * - Matched: System automatically found the match (high confidence)
 * - Manual: User explicitly selected the match
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Handlers
 */

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\MatchedPartnerType;

class MatchedTransactionHandler extends AbstractTransactionHandler
{
    /**
     * Get the partner type instance for Matched transactions
     * 
     * @return PartnerTypeInterface Returns MatchedPartnerType value object
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new MatchedPartnerType();
    }

    /**
     * Process a Matched transaction
     * 
     * Links a bank transaction to an automatically matched FrontAccounting entry.
     * Does not create new transactions, only confirms and updates the linkage.
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
        // Matched transactions require trans_no and trans_type
        // These indicate the FA transaction that was automatically matched
        
        // Extract matched transaction number
        if (!isset($transactionPostData['transNo'])) {
            return $this->createErrorResult(
                'Matched transaction number is required'
            );
        }
        
        // Extract matched transaction type
        if (!isset($transactionPostData['transType'])) {
            return $this->createErrorResult(
                'Matched transaction type is required'
            );
        }
        
        $transNo = (int) $transactionPostData['transNo'];
        $transType = (int) $transactionPostData['transType'];
        
        if ($transNo <= 0) {
            return $this->createErrorResult(
                'Invalid matched transaction number'
            );
        }
        
        if ($transType < 0) {
            return $this->createErrorResult(
                'Invalid matched transaction type'
            );
        }
        
        // Extract partner ID (used for tracking)
        try {
            $partnerId = $this->extractPartnerId($transactionPostData);
        } catch (\Exception $e) {
            // Partner ID is optional for matched transactions
            $partnerId = 0;
        }
        
        // Process the matched transaction
        return $this->processMatchedTransaction(
            $transaction,
            $transNo,
            $transType,
            $transactionId,
            $collectionIds,
            $partnerId,
            $transactionPostData
        );
    }

    /**
     * Process matched transaction by confirming link to existing FA entry
     * 
     * @param array $transaction Complete transaction data
     * @param int $transNo Matched FA transaction number
     * @param int $transType Matched FA transaction type
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * @param int $partnerId Partner ID (if available)
     * @param array $transactionPostData POST data containing memo/title
     * 
     * @return TransactionResult Success with links or error
     */
    private function processMatchedTransaction(
        array $transaction,
        int $transNo,
        int $transType,
        int $transactionId,
        string $collectionIds,
        int $partnerId,
        array $transactionPostData
    ): TransactionResult {
        // Get counterparty information from the matched entry
        try {
            $counterpartyArr = get_trans_counterparty($transNo, $transType);
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to get counterparty information: ' . $e->getMessage()
            );
        }
        
        if (empty($counterpartyArr)) {
            return $this->createErrorResult(
                'Could not find counterparty for matched entry'
            );
        }
        
        // Extract person information from counterparty data
        $personType = '';
        $personTypeId = 0;
        
        foreach ($counterpartyArr as $row) {
            // Any given transaction should only have 1 person associated
            if (isset($row['person_id']) && is_numeric($row['person_id'])) {
                $personType = $row['person_type'] ?? '';
            }
            if (isset($row['person_type_id']) && is_numeric($row['person_type_id'])) {
                $personTypeId = (int) $row['person_type_id'];
            }
        }
        
        // Determine memo from available sources (priority: memo > title)
        $memo = '';
        if (!empty($transactionPostData['memo'])) {
            $memo = $transactionPostData['memo'];
        } elseif (!empty($transactionPostData['title'])) {
            $memo = $transactionPostData['title'];
        }
        
        // Update the bank transaction to confirm the match
        try {
            update_transactions(
                $transactionId,
                $collectionIds,
                1, // status = processed
                $transNo,
                $transType,
                true, // matched flag
                false,
                "ZZ",
                $partnerId
            );
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to update transaction: ' . $e->getMessage()
            );
        }
        
        // Update partner data with transaction memo
        try {
            if (!empty($personType) && $personTypeId > 0) {
                set_partner_data($personType, $transType, $personTypeId, $memo);
            }
        } catch (\Exception $e) {
            // Non-critical error, continue processing
            // Partner data update is optional
        }
        
        // Build success message
        $message = sprintf(
            'Transaction was MATCH settled (Type: %d, Entry: %d)',
            $transType,
            $transNo
        );
        
        // Build result data with links
        $resultData = [
            'trans_no' => $transNo,
            'trans_type' => $transType,
            'person_type' => $personType,
            'person_type_id' => $personTypeId,
            'view_gl_link' => "../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$transNo}"
        ];
        
        // Special case: Type 12 (ST_CUSTPAYMENT) - add link to view payment and invoice
        if ($transType == ST_CUSTPAYMENT) {
            $resultData['view_receipt_link'] = "../../sales/view/view_receipt.php?type_id={$transType}&trans_no={$transNo}";
        }
        
        return $this->createSuccessResult(
            $transNo,
            $transType,
            $message,
            $resultData
        );
    }
}
