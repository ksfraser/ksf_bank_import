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
 * @version    1.2.0 - Now uses HtmlLabelRow and HtmlSubmit for proper HTML generation
 */

namespace Ksfraser;

use Ksfraser\HTML\HtmlLabelRow;
use Ksfraser\HTML\HtmlString;
use Ksfraser\HTML\HtmlRaw;
use Ksfraser\HTML\HtmlSubmit;

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
 * @since 20251019
 * @version 1.2.0
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
     * Render the settled transaction display as HTML
     *
     * @return string HTML output
     * @since 20251019
     */
    public function render(): string
    {
        $html = "";

        // Status label
        $html .= $this->renderStatusLabel();

        // Operation-specific details
        $html .= $this->renderOperationDetails();

        // Unset button
        $html .= $this->renderUnsetButton();

        return $html;
    }

    /**
     * Render status label (Transaction is settled!)
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderStatusLabel(): string
    {
        $label = new HtmlString('Status:');
        $content = new HtmlRaw('<b>Transaction is settled!</b>'); // Use HtmlRaw for HTML markup
        $row = new HtmlLabelRow($label, $content);
        
        return $row->getHtml();
    }

    /**
     * Render operation-specific details based on transaction type
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderOperationDetails(): string
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
     * @return string HTML output
     * @since 20251019
     */
    private function renderSupplierPaymentDetails(): string
    {
        $html = "";
        
        // Operation
        $operationRow = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Payment')
        );
        $html .= $operationRow->getHtml();
        
        // Supplier name
        $supplierName = $this->transactionData['supplier_name'] ?? 'Unknown Supplier';
        $supplierRow = new HtmlLabelRow(
            new HtmlString('Supplier:'),
            new HtmlString(htmlspecialchars($supplierName))
        );
        $html .= $supplierRow->getHtml();
        
        // Bank account
        $bankAccountName = $this->transactionData['bank_account_name'] ?? 'Unknown Account';
        $bankRow = new HtmlLabelRow(
            new HtmlString('From bank account:'),
            new HtmlString(htmlspecialchars($bankAccountName))
        );
        $html .= $bankRow->getHtml();
        
        return $html;
    }

    /**
     * Render bank deposit details
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderBankDepositDetails(): string
    {
        $html = "";
        
        // Operation
        $operationRow = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Deposit')
        );
        $html .= $operationRow->getHtml();
        
        // Customer and branch
        $customerName = $this->transactionData['customer_name'] ?? 'Unknown Customer';
        $branchName = $this->transactionData['branch_name'] ?? 'Unknown Branch';
        $customerBranch = htmlspecialchars($customerName) . ' / ' . htmlspecialchars($branchName);
        
        $customerRow = new HtmlLabelRow(
            new HtmlString('Customer/Branch:'),
            new HtmlString($customerBranch)
        );
        $html .= $customerRow->getHtml();
        
        return $html;
    }

    /**
     * Render manual settlement details
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderManualSettlementDetails(): string
    {
        $row = new HtmlLabelRow(
            new HtmlString('Operation:'),
            new HtmlString('Manual settlement')
        );
        
        return $row->getHtml();
    }

    /**
     * Render unknown transaction type message
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderUnknownTransactionType(): string
    {
        $row = new HtmlLabelRow(
            new HtmlString('Status:'),
            new HtmlString('other transaction type; no info yet')
        );
        
        return $row->getHtml();
    }

    /**
     * Render unset transaction button
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderUnsetButton(): string
    {
        $lineItemId = $this->getLineItemId();
        $transNo = $this->getTransactionNumber();
        
        $buttonName = "UnsetTrans[{$lineItemId}]";
        $buttonText = "Unset Transaction {$transNo}";
        
        // Create submit button using HtmlSubmit
        $button = new HtmlSubmit(new HtmlString($buttonText));
        $button->setName($buttonName);
        $button->setClass('default'); // FA uses 'default' class
        
        $row = new HtmlLabelRow(
            new HtmlString('Unset Transaction Association'),
            new HtmlRaw($button->getHtml())
        );
        
        return $row->getHtml();
    }
}
