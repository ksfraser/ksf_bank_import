<?php
/**
 * BankTransferTransactionHandler.php
 * 
 * Handles Bank Transfer (BT) transactions for internal transfers between bank accounts.
 * Bank transfers represent money moving from one bank account to another within the same
 * organization, using FrontAccounting's bank transfer functionality.
 * 
 * Transaction Direction:
 * - Credit (C): Money coming IN to our account (FROM partner account TO our account)
 * - Debit (D): Money going OUT of our account (FROM our account TO partner account)
 * 
 * Processing Flow:
 * 1. Validate required fields (amount, DC type, partner bank account ID)
 * 2. Create fa_bank_transfer object
 * 3. Set FROM/TO accounts based on transaction direction
 * 4. Set amount, date, and memo
 * 5. Generate unique reference
 * 6. Execute bank transfer
 * 7. Update transaction status and partner data
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Handlers
 */

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\PartnerTypes\PartnerTypeInterface;
use Ksfraser\PartnerTypes\BankTransferPartnerType;

class BankTransferTransactionHandler extends AbstractTransactionHandler
{
    /**
     * Get the partner type instance for Bank Transfer transactions
     * 
     * @return PartnerTypeInterface Returns BankTransferPartnerType value object
     */
    protected function getPartnerTypeInstance(): PartnerTypeInterface
    {
        return new BankTransferPartnerType();
    }

    /**
     * Process a Bank Transfer transaction
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

        // Extract partner ID (other bank account ID)
        try {
            $partnerBankAccountId = $this->extractPartnerId($transactionPostData);
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Partner bank account ID is required for bank transfers'
            );
        }

        // Load external fa_bank_transfer class
        $faClassPath = $this->getFaBankTransferClassPath();
        $inc = require_once($faClassPath);
        
        if (!$inc || !class_exists('fa_bank_transfer')) {
            return $this->createErrorResult(
                'Failed to load fa_bank_transfer class'
            );
        }

        // Process the bank transfer
        return $this->processBankTransfer(
            $transaction,
            $partnerBankAccountId,
            $transactionPostData,
            $ourAccount,
            $transactionId,
            $collectionIds
        );
    }

    /**
     * Get the path to the fa_bank_transfer class file
     * 
     * @return string Path to the class file
     */
    private function getFaBankTransferClassPath(): string
    {
        // Path relative to this file's location
        return dirname(__DIR__, 2) . '/../ksf_modules_common/class.fa_bank_transfer.php';
    }

    /**
     * Process a bank transfer transaction
     * 
     * Creates a fa_bank_transfer object, sets FROM/TO accounts based on direction,
     * and executes the transfer in FrontAccounting.
     * 
     * @param array $transaction Complete transaction data
     * @param int $partnerBankAccountId Partner bank account ID
     * @param array $transactionPostData Filtered POST data
     * @param array $ourAccount Our bank account information
     * @param int $transactionId Transaction ID
     * @param string $collectionIds Collection IDs
     * 
     * @return TransactionResult Success with link or error
     */
    private function processBankTransfer(
        array $transaction,
        int $partnerBankAccountId,
        array $transactionPostData,
        array $ourAccount,
        int $transactionId,
        string $collectionIds
    ): TransactionResult {
        // Create bank transfer object
        $bttrf = new \fa_bank_transfer();
        
        try {
            // Set transaction type
            $bttrf->set("trans_type", ST_BANKTRANSFER);
            
            // Determine FROM/TO accounts based on transaction direction
            $transactionDC = $transaction['transactionDC'];
            
            if ($transactionDC === 'C' || $transactionDC === 'B') {
                // Credit: Money coming IN (FROM partner TO our account)
                $bttrf->set("ToBankAccount", $ourAccount['id']);
                $bttrf->set("FromBankAccount", $partnerBankAccountId);
            } elseif ($transactionDC === 'D') {
                // Debit: Money going OUT (FROM our account TO partner)
                $bttrf->set("FromBankAccount", $ourAccount['id']);
                $bttrf->set("ToBankAccount", $partnerBankAccountId);
            } else {
                return $this->createErrorResult(
                    "Invalid transaction DC type: {$transactionDC}"
                );
            }
            
            // Set amount and date
            $bttrf->set("amount", $transaction['transactionAmount']);
            $bttrf->set("trans_date", $transaction['valueTimestamp']);

			// Calculate target amount (handles currency conversion for different currency accounts)
			require_once('Services/BankTransferAmountCalculator.php');
			$calculator = new \KsfBankImport\Services\BankTransferAmountCalculator();
			$target_amount = $calculator->calculateTargetAmount(
						$bttrf->get("FromBankAccount"),
						$bttrf->get("ToBankAccount"),
						$transaction['transactionAmount'],
						$transaction['valueTimestamp']
					);
			$bttrf->set("target_amount", $target_amount);
            
            // Build comprehensive memo
            $comment = $transactionPostData['comment'] ?? '';
            $transactionTitle = $transaction['transactionTitle'] ?? '';
            $transactionCode = $transaction['transactionCode'] ?? '';
            $memo = $transaction['memo'] ?? '';
            
            $fullMemo = sprintf(
                "%s :: %s::%s::%s",
                $comment,
                $transactionTitle,
                $transactionCode,
                $memo
            );
            
            $bttrf->set("memo_", $fullMemo);
            
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to configure bank transfer: ' . $e->getMessage()
            );
        }
        
        // Get next reference number
        try {
            $bttrf->getNextRef();
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to get reference number: ' . $e->getMessage()
            );
        }
        
        // Execute the bank transfer
        begin_transaction();
        
        try {
            // can_process validation is built into add_bank_transfer
            $bttrf->add_bank_transfer();
            
            $transNo = $bttrf->get("trans_no");
            $transType = $bttrf->get("trans_type");
            
            // Get counterparty information
            $counterpartyArr = get_trans_counterparty($transNo, $transType);
            
            // Update transaction status
            update_transactions(
                $transactionId,
                $collectionIds,
                1, // status = processed
                $transNo,
                $transType,
                false,
                true,
                "BT",
                $partnerBankAccountId
            );
            
            // Update bank partner data (short form with memo/reference)
            set_bank_partner_data(
                $bttrf->get("FromBankAccount"),
                $transType,
                $bttrf->get("ToBankAccount"),
                $memo
            );
            
            commit_transaction();
            
        } catch (\Exception $e) {
            return $this->createErrorResult(
                'Failed to execute bank transfer: ' . $e->getMessage()
            );
        }
        
        // Build success message
        $direction = ($transaction['transactionDC'] === 'D') ? 'OUT' : 'IN';
        $message = sprintf(
            'Bank Transfer processed successfully (Trans #%d, %s)',
            $transNo,
            $direction
        );
        
        return $this->createSuccessResult(
            $transNo,
            $transType,
            $message,
            [
                'from_account' => $bttrf->get("FromBankAccount"),
                'to_account' => $bttrf->get("ToBankAccount"),
                'direction' => $direction,
                'view_gl_link' => "../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$transNo}"
            ]
        );
    }
}
