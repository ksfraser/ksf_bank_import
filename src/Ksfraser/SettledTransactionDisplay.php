<?php

/**
 * SettledTransactionDisplay Component
 *
 * Displays details of settled bank import transactions that have been
 * matched/linked to FrontAccounting transactions (Supplier Payments,
 * Bank Deposits, Manual Settlements, etc.).
 *
 * This component replaces the display_settled() method logic from
 * ViewBILineItems and bi_lineitem classes.
 *
 * @package    KsfBankImport
 * @subpackage Components
 * @since      20251019
 * @version    2.0.0 - Now returns HtmlFragment for composability (Option B pattern)
 */

namespace Ksfraser;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlSubmit;

require_once(__DIR__ . '/HTML/HtmlFragment.php');
require_once(__DIR__ . '/HTML/HtmlAttribute.php');
require_once(__DIR__ . '/HTML/Composites/HtmlLabelRow.php');
require_once(__DIR__ . '/HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/HTML/Elements/HtmlRaw.php');
require_once(__DIR__ . '/HTML/Elements/HtmlSubmit.php');

/**
 * SettledTransactionDisplay - Display settled transaction details with FA integration
 *
 * Features:
 * - Shows "Transaction is settled!" status
 * - Displays operation type (Payment, Deposit, Manual settlement)
 * - Shows supplier/customer/branch details based on transaction type
 * - Displays bank account information
 * - Provides "Unset Transaction Association" button (using HtmlSubmit)
 * - Handles various FA transaction types (ST_SUPPAYMENT, ST_BANKDEPOSIT, etc.)
 *
 * Returns HtmlFragment for composability (follows Option B pattern from PartnerTypeViews)
 *
 * @since 20251019
 * @version 2.0.0
 */
class SettledTransactionDisplay
{
    /**
     * FA Transaction type constants (from FrontAccounting)
     */
    private const ST_SUPPAYMENT = 22;  // Supplier Payment
    private const ST_BANKDEPOSIT = 12; // Bank Deposit
    private const ST_MANUAL = 0;       // Manual Settlement

    /**
     * @var array Transaction data (id, fa_trans_type, fa_trans_no, etc.)
     */
    private array $transactionData;

    /**
     * Constructor
     *
     * @param array $transactionData Transaction data including:
     *                               - id: Line item ID
     *                               - fa_trans_type: FA transaction type
     *                               - fa_trans_no: FA transaction number
     *                               - supplier_name: Supplier name (for payments)
     *                               - bank_account_name: Bank account name (for payments)
     *                               - customer_name: Customer name (for deposits)
     *                               - branch_name: Branch name (for deposits)
     * @since 20251019
     */
    public function __construct(array $transactionData)
    {
        $this->transactionData = $transactionData;
    }

    /**
     * Get transaction data
     *
     * @return array
     * @since 20251019
     */
    public function getTransactionData(): array
    {
        return $this->transactionData;
    }

    /**
     * Get FA transaction type
     *
     * @return int
     * @since 20251019
     */
    public function getTransactionType(): int
    {
        return $this->transactionData['fa_trans_type'] ?? 0;
    }

    /**
     * Get FA transaction number
     *
     * @return int
     * @since 20251019
     */
    public function getTransactionNumber(): int
    {
        return $this->transactionData['fa_trans_no'] ?? 0;
    }

    /**
     * Get line item ID
     *
     * @return int
     * @since 20251019
     */
    public function getLineItemId(): int
    {
        return $this->transactionData['id'] ?? 0;
    }

    /**
     * Render the settled transaction display as HtmlFragment
     *
     * @return HtmlFragment HTML fragment containing all settled transaction details
     * @since 20251019
     * @version 2.0.0 - Now returns HtmlFragment instead of string
     */
    public function render(): HtmlFragment
    {
        $fragment = new HtmlFragment();

        // Status label
        $fragment->addChild($this->renderStatusLabel());

        // Operation-specific details
        $fragment->addChild($this->renderOperationDetails());

        // Unset button
        $fragment->addChild($this->renderUnsetButton());

        return $fragment;
    }

    /**
     * Display method for backward compatibility (echoes HTML)
     * 
     * @return void
     * @since 2.0.0
     */
    public function display(): void
    {
        echo $this->render()->toHtml();
    }

    /**
     * Render status label (Transaction is settled!)
     *
     * @return HtmlLabelRow HTML label row element
     * @since 20251019
     * @version 2.0.0 - Returns HtmlLabelRow instead of string
     */
    private function renderStatusLabel(): HtmlLabelRow
    {
        $label = new HtmlString('Status:');
        $content = new HtmlRaw('<b>Transaction is settled!</b>'); // Use HtmlRaw for HTML markup
        
        return new HtmlLabelRow($label, $content);
    }

    /**
     * Render operation-specific details based on transaction type
     *
     * @return HtmlFragment HTML fragment containing operation details
     * @since 20251019
     * @version 2.0.0 - Returns HtmlFragment instead of string
     */
    private function renderOperationDetails(): HtmlFragment
    {
        $transType = $this->getTransactionType();

        switch ($transType) {
            case self::ST_SUPPAYMENT:
                return $this->renderSupplierPaymentDetails();
            
            case self::ST_BANKDEPOSIT:
                return $this->renderBankDepositDetails();
            
            case self::ST_MANUAL:
                return $this->renderManualSettlementDetails();
            
            default:
                return $this->renderUnknownTransactionType();
        }
    }

    /**
     * Render supplier payment details
     *
     * @return HtmlFragment HTML fragment with operation, supplier, and bank account rows
     * @since 20251019
     * @version 2.0.0 - Returns HtmlFragment instead of string
     */
    private function renderSupplierPaymentDetails(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        // Operation
        $operationRow = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Payment')
        );
        $fragment->addChild($operationRow);
        
        // Supplier name
        $supplierName = $this->transactionData['supplier_name'] ?? 'Unknown Supplier';
        $supplierRow = new HtmlLabelRow(
            new HtmlString('Supplier:'),
            new HtmlString(htmlspecialchars($supplierName))
        );
        $fragment->addChild($supplierRow);
        
        // Bank account
        $bankAccountName = $this->transactionData['bank_account_name'] ?? 'Unknown Account';
        $bankRow = new HtmlLabelRow(
            new HtmlString('From bank account:'),
            new HtmlString(htmlspecialchars($bankAccountName))
        );
        $fragment->addChild($bankRow);
        
        return $fragment;
    }

    /**
     * Render bank deposit details
     *
     * @return HtmlFragment HTML fragment with operation and customer/branch rows
     * @since 20251019
     * @version 2.0.0 - Returns HtmlFragment instead of string
     */
    private function renderBankDepositDetails(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        // Operation
        $operationRow = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Deposit')
        );
        $fragment->addChild($operationRow);
        
        // Customer and branch
        $customerName = $this->transactionData['customer_name'] ?? 'Unknown Customer';
        $branchName = $this->transactionData['branch_name'] ?? 'Unknown Branch';
        $customerBranch = htmlspecialchars($customerName) . ' / ' . htmlspecialchars($branchName);
        
        $customerRow = new HtmlLabelRow(
            new HtmlString('Customer/Branch:'),
            new HtmlString($customerBranch)
        );
        $fragment->addChild($customerRow);
        
        return $fragment;
    }

    /**
     * Render manual settlement details
     *
     * @return HtmlFragment HTML fragment with manual settlement row
     * @since 20251019
     * @version 2.0.0 - Returns HtmlFragment instead of string
     */
    private function renderManualSettlementDetails(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        $row = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Manual settlement')
        );
        $fragment->addChild($row);
        
        return $fragment;
    }

    /**
     * Render unknown transaction type message
     *
     * @return HtmlFragment HTML fragment with unknown type message
     * @since 20251019
     * @version 2.0.0 - Returns HtmlFragment instead of string
     */
    private function renderUnknownTransactionType(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        $row = new HtmlLabelRow(
            new HtmlString('Status:'),
            new HtmlString('other transaction type; no info yet')
        );
        $fragment->addChild($row);
        
        return $fragment;
    }

    /**
     * Render unset transaction button
     *
     * @return HtmlLabelRow HTML label row with unset button
     * @since 20251019
     * @version 2.0.0 - Returns HtmlLabelRow instead of string
     */
    private function renderUnsetButton(): HtmlLabelRow
    {
        $lineItemId = $this->getLineItemId();
        $transNo = $this->getTransactionNumber();
        
        $buttonName = "UnsetTrans[{$lineItemId}]";
        $buttonText = "Unset Transaction {$transNo}";
        
        // Create submit button using HtmlSubmit
        $button = new HtmlSubmit(new HtmlString($buttonText));
        $button->setName($buttonName);
        $button->setClass('default'); // FA uses 'default' class
        
        return new HtmlLabelRow(
            new HtmlString('Unset Transaction Association'),
            new HtmlRaw($button->getHtml())
        );
    }
}
