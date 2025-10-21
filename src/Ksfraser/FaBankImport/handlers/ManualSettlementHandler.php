<?php
/**
 * ManualSettlementHandler.php
 * 
 * Handles Manual Settlement (MA) transactions for manually linking bank transactions
 * to existing FrontAccounting entries. This is used when automatic matching fails or
 * when the user wants to explicitly link a bank transaction to a specific FA transaction.
 * 
 * Purpose:
 * Manual settlement allows users to associate a bank statement transaction with an
 * existing FrontAccounting transaction (supplier payment, customer receipt, etc.)
 * without creating a new entry. This is useful for:
 * - Reconciling transactions that didn't auto-match
 * - Correcting mismatched transactions
 * - Linking historical transactions
 * 
 * Processing Flow:
 * 1. Validate required fields (Existing_Entry and Existing_Type from POST)
 * 2. Get counterparty information from the existing FA entry
 * 3. Update the bank transaction status to link it to the existing entry
 * 4. Update partner data with transaction memo
 * 5. Provide links to view the linked entry
 * 
 * Special Cases:
 * - Type 12 (ST_CUSTPAYMENT): Provides additional link to view payment and invoice
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Handlers
 */

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\ManualSettlementPartnerType;

class ManualSettlementHandler extends AbstractTransactionHandler
{
    /**
     * Get the partner type instance for Manual Settlement transactions
     * 
     * @return PartnerTypeInterface Returns ManualSettlementPartnerType value object
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new ManualSettlementPartnerType();
    }

    /**
     * Process a Manual Settlement transaction
     * 
     * Links a bank transaction to an existing FrontAccounting entry.
     * Does not create new transactions, only updates the linkage.
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
        // Manual settlement requires Existing_Entry and Existing_Type
        // These are passed in transactionPostData but with different keys
        
        // Extract existing entry number
        if (!isset($transactionPostData['existingEntry'])) {
            return $this->createErrorResult(
                'Existing Entry number is required for manual settlement'
            );
        }
        
        // Extract existing entry type
        if (!isset($transactionPostData['existingType'])) {
            return $this->createErrorResult(
                'Existing Entry type is required for manual settlement'
            );
        }
        
        $existingEntry = (int) $transactionPostData['existingEntry'];
        $existingType = (int) $transactionPostData['existingType'];
        
        if ($existingEntry <= 0) {
            return $this->createErrorResult(
                'Invalid existing entry number'
            );
        }
        
        if ($existingType < 0) {
            return $this->createErrorResult(
                'Invalid existing entry type'
            );
        }
        
        // Process the manual settlement
        return $this->processManualSettlement(
            $transaction,
            $existingEntry,
            $existingType,
            $transactionId,
            $collectionIds
        );
    }

    /**
     * Process manual settlement by linking to existing FA entry
     * 
     * @param array $transaction Complete transaction data
     * @param int $existingEntry Existing FA transaction number
     * @param int $existingType Existing FA transaction type
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * 
     * @return TransactionResult Success with links or error
     */
    private function processManualSettlement(
        array $transaction,
        int $existingEntry,
        int $existingType,
        int $transactionId,
        string $collectionIds
    ): TransactionResult {
        // Get counterparty information from the existing entry
        try {
            $counterpartyArr = get_trans_counterparty($existingEntry, $existingType);
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to get counterparty information: ' . $e->getMessage()
            );
        }
        
        if (empty($counterpartyArr)) {
            return $this->createErrorResult(
                'Could not find counterparty for existing entry'
            );
        }
        
        // Update the bank transaction to link it to the existing FA entry
        try {
            update_transactions(
                $transactionId,
                $collectionIds,
                1, // status = processed
                $existingEntry,
                $existingType,
                true, // manual flag
                false,
                null,
                ""
            );
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to update transaction: ' . $e->getMessage()
            );
        }
        
        // Update partner data with transaction memo
        try {
            $memo = $transaction['memo'] ?? '';
            $personType = $counterpartyArr['person_type'] ?? '';
            $personTypeId = $counterpartyArr['person_type_id'] ?? 0;
            
            if (!empty($personType) && $personTypeId > 0) {
                set_partner_data($personType, $existingType, $personTypeId, $memo);
            }
        } catch (\Exception $e) {
            // Non-critical error, continue processing
            // Partner data update is optional
        }
        
        // Build success message
        $message = sprintf(
            'Transaction manually settled against existing entry (Type: %d, Entry: %d)',
            $existingType,
            $existingEntry
        );
        
        // Build result data with links
        $resultData = [
            'existing_entry' => $existingEntry,
            'existing_type' => $existingType,
            'view_gl_link' => "../../gl/view/gl_trans_view.php?type_id={$existingType}&trans_no={$existingEntry}"
        ];
        
        // Special case: Type 12 (ST_CUSTPAYMENT) - add link to view payment and invoice
        if ($existingType == 12) {
            $resultData['view_receipt_link'] = "../../sales/view/view_receipt.php?type_id={$existingType}&trans_no={$existingEntry}";
        }
        
        return $this->createSuccessResult(
            $existingEntry,
            $existingType,
            $message,
            $resultData
        );
    }
}
