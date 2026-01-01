<?php
/**
 * Analyzes transaction pairs to determine transfer direction
 * 
 * Pure business logic class with no side effects. Determines which account
 * is the source (FROM) and which is the destination (TO) based on debit/credit
 * indicators in the transaction data.
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Business_Logic
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────────────┐
 * │   TransferDirectionAnalyzer                 │
 * ├─────────────────────────────────────────────┤
 * │ + analyze(trz1, trz2, acc1, acc2): array    │
 * │ - buildTransferData(...): array             │
 * │ - validateInputs(...): void                 │
 * └─────────────────────────────────────────────┘
 * 
 * @uml.logic
 * ┌──────────────────────────┐
 * │ Transaction 1 DC = 'D'?  │
 * └───────┬──────────────────┘
 *         │
 *    ┌────▼────┐    ┌─────────────┐
 *    │   YES   │    │     NO      │
 *    └────┬────┘    └──────┬──────┘
 *         │                │
 *    ┌────▼──────────┐  ┌──▼────────────┐
 *    │ FROM = Acc 1  │  │ FROM = Acc 2  │
 *    │ TO   = Acc 2  │  │ TO   = Acc 1  │
 *    └───────────────┘  └───────────────┘
 * @enduml
 */

namespace KsfBankImport\Services;

/**
 * Business logic for determining bank transfer direction
 * 
 * Analyzes paired transactions to determine which account is sending money
 * (FROM) and which is receiving (TO) based on debit/credit indicators.
 * 
 * Rules:
 * - If transaction 1 is Debit ('D'): money leaving account 1 → FROM=acc1, TO=acc2
 * - If transaction 1 is Credit ('C'): money arriving to account 1 → FROM=acc2, TO=acc1
 * 
 * This is a pure function class - no side effects, easy to test.
 * 
 * Example usage:
 * <code>
 * $analyzer = new TransferDirectionAnalyzer();
 * $result = $analyzer->analyze(
 *     ['id' => 1, 'transactionDC' => 'D', 'transactionAmount' => 100],
 *     ['id' => 2, 'transactionDC' => 'C', 'transactionAmount' => 100],
 *     ['id' => 10, 'name' => 'Checking'],
 *     ['id' => 20, 'name' => 'Savings']
 * );
 * // Result: FROM=10, TO=20 (money leaving checking, arriving savings)
 * </code>
 * 
 * @since 1.0.0
 */
class TransferDirectionAnalyzer 
{
    /**
     * Determine transfer direction from transaction pair
     * 
     * Analyzes two paired transactions and their associated accounts to
     * determine the direction of money flow (FROM → TO).
     * 
     * The logic is based on the Debit/Credit (DC) indicator of the first
     * transaction:
     * - 'D' (Debit) = money leaving first account
     * - 'C' (Credit) = money arriving to first account
     * 
     * @param array $trz1 First transaction with keys:
     *                    - id: int Transaction ID
     *                    - transactionDC: string 'D' or 'C'
     *                    - transactionAmount: float Amount
     *                    - valueTimestamp: string Transaction date
     *                    - transactionTitle: string Description
     * @param array $trz2 Second transaction (paired with first)
     * @param array $account1 First bank account with keys:
     *                        - id: int Account ID
     *                        - name: string Account name (optional)
     * @param array $account2 Second bank account
     * 
     * @return array Transfer configuration with keys:
     *               - from_account: int Source account ID
     *               - to_account: int Destination account ID
     *               - from_trans_id: int Source transaction ID
     *               - to_trans_id: int Destination transaction ID
     *               - amount: float Transfer amount (absolute value)
     *               - date: string Transaction date
     *               - memo: string Combined transaction descriptions
     * 
     * @throws \InvalidArgumentException If required fields are missing
     * 
     * @since 1.0.0
     */
    public function analyze(array $trz1, array $trz2, array $account1, array $account2)
    {
        $this->validateInputs($trz1, $trz2, $account1, $account2);
        
        if ($trz1['transactionDC'] == 'D') {
            // trz1 is Debit (money leaving account1)
            // account1 is FROM, account2 is TO
            return $this->buildTransferData(
                $account1['id'], 
                $account2['id'],
                $trz1['id'], 
                $trz2['id'],
                $trz1, 
                $trz2
            );
        } else {
            // trz1 is Credit (money arriving to account1)
            // account2 is FROM, account1 is TO
            return $this->buildTransferData(
                $account2['id'], 
                $account1['id'],
                $trz2['id'], 
                $trz1['id'],
                $trz1, 
                $trz2
            );
        }
    }
    
    /**
     * Build transfer data array from components
     * 
     * Constructs the transfer configuration array with all necessary
     * information for creating the bank transfer.
     * 
     * @param int   $fromAccount  Source account ID
     * @param int   $toAccount    Destination account ID
     * @param int   $fromTransId  Source transaction ID
     * @param int   $toTransId    Destination transaction ID
     * @param array $trz1         First transaction (for amount/date)
     * @param array $trz2         Second transaction (for memo)
     * 
     * @return array Transfer configuration
     * 
     * @since 1.0.0
     */
    private function buildTransferData(
        $fromAccount, 
        $toAccount, 
        $fromTransId, 
        $toTransId, 
        $trz1, 
        $trz2
    ) {
        return array(
            'from_account' => $fromAccount,
            'to_account' => $toAccount,
            'from_trans_id' => $fromTransId,
            'to_trans_id' => $toTransId,
            'amount' => abs($trz1['transactionAmount']),
            'date' => $trz1['valueTimestamp'],
            'memo' => "Paired Transfer: {$trz1['transactionTitle']} :: {$trz2['transactionTitle']}"
        );
    }
    
    /**
     * Validate input data contains all required fields
     * 
     * Checks that all necessary fields are present before attempting
     * to analyze transfer direction.
     * 
     * @param array $trz1     First transaction
     * @param array $trz2     Second transaction
     * @param array $account1 First account
     * @param array $account2 Second account
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If required fields are missing
     * 
     * @since 1.0.0
     */
    private function validateInputs($trz1, $trz2, $account1, $account2)
    {
        if (!isset($trz1['transactionDC']) || !isset($trz1['transactionAmount'])) {
            throw new \InvalidArgumentException(
                "Invalid transaction 1 data - missing transactionDC or transactionAmount"
            );
        }
        
        if (!isset($trz2['transactionDC']) || !isset($trz2['transactionAmount'])) {
            throw new \InvalidArgumentException(
                "Invalid transaction 2 data - missing transactionDC or transactionAmount"
            );
        }
        
        if (!isset($account1['id']) || !isset($account2['id'])) {
            throw new \InvalidArgumentException(
                "Invalid account data - missing account ID"
            );
        }
    }
}
