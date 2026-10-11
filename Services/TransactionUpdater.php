<?php
/**
 * Updates imported transaction records after processing
 * 
 * Handles database updates for imported transactions once they have been
 * processed and matched to FrontAccounting transactions.
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────────────┐
 * │   TransactionUpdater                        │
 * ├─────────────────────────────────────────────┤
 * │ + updatePairedTransactions(result, data)    │
 * │ - validateUpdateData(result, data)          │
 * └─────────────────────────────────────────────┘
 *         │
 *         │ calls
 *         ▼
 * ┌─────────────────────────────────────────────┐
 * │   Global Functions (pdata.inc)              │
 * ├─────────────────────────────────────────────┤
 * │ - update_transactions()                     │
 * │ - set_bank_partner_data()                   │
 * └─────────────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\Services;

/**
 * Service class for updating transaction records
 * 
 * Provides a clean interface for updating imported transaction records
 * after they have been processed. Encapsulates all database update logic.
 * 
 * Example usage:
 * <code>
 * $updater = new TransactionUpdater();
 * $updater->updatePairedTransactions(
 *     ['trans_no' => 123, 'trans_type' => 4],
 *     [
 *         'from_trans_id' => 1,
 *         'to_trans_id' => 2,
 *         'from_account' => 100,
 *         'to_account' => 200,
 *         'memo' => 'Transfer'
 *     ]
 * );
 * </code>
 * 
 * @since 1.0.0
 */
class TransactionUpdater 
{
    /**
     * Update paired bank transfer transactions
     * 
     * Updates both sides of a paired bank transfer to mark them as processed
     * and link them to the FrontAccounting transaction.
     * 
     * Updates include:
     * - Setting status to processed (1)
     * - Linking to FA transaction number and type
     * - Setting match info to "BT" (Bank Transfer)
     * - Cross-referencing partner account
     * - Updating partner data for reporting
     * 
     * @param array $result FA transaction result containing:
     *                      - trans_no: int FrontAccounting transaction number
     *                      - trans_type: int FrontAccounting transaction type
     * @param array $transferData Transfer configuration containing:
     *                            - from_trans_id: int Source transaction ID
     *                            - to_trans_id: int Destination transaction ID
     *                            - from_account: int Source bank account ID
     *                            - to_account: int Destination bank account ID
     *                            - memo: string Transaction description
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If required fields missing in result or data
     * 
     * @since 1.0.0
     */
    public function updatePairedTransactions(array $result, array $transferData)
    {
        $this->validateUpdateData($result, $transferData);
        
        $_cids = array();  // No charges for bank transfers
        
        // Update FROM transaction (debit side)
        update_transactions(
            $transferData['from_trans_id'], 
            $_cids, 
            $status = 1,  // Processed
            $result['trans_no'], 
            $result['trans_type'], 
            false,  // Not manual
            true,   // Created new transaction
            "BT",   // Bank Transfer
            $transferData['to_account']  // Partner account
        );
        
        // Update TO transaction (credit side)
        update_transactions(
            $transferData['to_trans_id'], 
            $_cids, 
            $status = 1,  // Processed
            $result['trans_no'], 
            $result['trans_type'], 
            false,  // Not manual
            true,   // Created new transaction
            "BT",   // Bank Transfer
            $transferData['from_account']  // Partner account
        );
        
        // Update partner data for reporting
        set_bank_partner_data(
            $transferData['from_account'], 
            $result['trans_type'], 
            $transferData['to_account'], 
            $transferData['memo']
        );
    }
    
    /**
     * Validate data required for transaction updates
     * 
     * Ensures all required fields are present before attempting database updates.
     * 
     * @param array $result FA transaction result to validate
     * @param array $transferData Transfer configuration to validate
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If required fields are missing
     * 
     * @since 1.0.0
     */
    private function validateUpdateData(array $result, array $transferData)
    {
        if (!isset($result['trans_no']) || !isset($result['trans_type'])) {
            throw new \InvalidArgumentException(
                "Invalid result data for transaction update - missing trans_no or trans_type"
            );
        }
        
        $required = ['from_trans_id', 'to_trans_id', 'from_account', 'to_account', 'memo'];
        foreach ($required as $field) {
            if (!isset($transferData[$field])) {
                throw new \InvalidArgumentException(
                    "Missing required field for update: $field"
                );
            }
        }
    }
}
