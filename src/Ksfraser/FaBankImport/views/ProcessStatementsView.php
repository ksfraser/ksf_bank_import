<?php

namespace Ksfraser\FaBankImport\Views;

/**
 * View class for rendering the main process statements page.
 *
 * This class encapsulates all HTML generation for the bank import process statements page,
 * replacing inline HTML construction with proper SRP using the Ksfraser\HTML component library.
 */
class ProcessStatementsView
{
    /**
     * @var array Transaction data to display
     */
    private $transactions;

    /**
     * @var array Operation types for partner matching
     */
    private $operationTypes;

    /**
     * @var array Vendor list for display
     */
    private $vendorList;

    /**
     * Constructor.
     *
     * @param array $transactions Array of transaction data
     * @param array $operationTypes Operation types configuration
     * @param array $vendorList Vendor list for display
     */
    public function __construct(array $transactions, array $operationTypes, array $vendorList)
    {
        $this->transactions = $transactions;
        $this->operationTypes = $operationTypes;
        $this->vendorList = $vendorList;
    }

    /**
     * Render the complete process statements page HTML.
     *
     * @return string Complete HTML for the page
     */
    public function render(): string
    {
        $html = '';

        // Start the main form
        $html .= $this->renderFormStart();

        // Document table div
        $html .= $this->renderDocumentTableDiv();

        // End the main form
        $html .= $this->renderFormEnd();

        return $html;
    }

    /**
     * Render the form start tag.
     *
     * @return string HTML for form start
     */
    private function renderFormStart(): string
    {
        return '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
    }

    /**
     * Render the document table div with filter and transaction tables.
     *
     * @return string HTML for the document table div
     */
    private function renderDocumentTableDiv(): string
    {
        $html = '<div id="doc_tbl">';

        // Add filter table
        $html .= $this->renderFilterTable();

        // Add transaction table
        $html .= $this->renderTransactionTable();

        $html .= '</div>';

        return $html;
    }

    /**
     * Render the filter table using the existing header_table.php class.
     *
     * @return string HTML string for the filter table
     */
    private function renderFilterTable(): string
    {
        // Use the new string-returning method instead of output buffering
        require_once('header_table.php');
        $headertable = new ksf_modules_table_filter_by_date();
        return $headertable->getBankImportHeaderHtml();
    }

    /**
     * Render the transaction display table.
     *
     * @return string HTML string for the transaction table
     */
    private function renderTransactionTable(): string
    {
        $html = '<table class="TABLESTYLE" width="100%">';

        // Add table header
        $html .= $this->renderTransactionTableHeader();

        // Add table body with transactions
        $html .= $this->renderTransactionTableBody();

        $html .= '</table>';

        return $html;
    }

    /**
     * Render the transaction table header.
     *
     * @return string HTML string for table header
     */
    private function renderTransactionTableHeader(): string
    {
        return '<thead><tr><th>Transaction Details</th><th>Operation/Status</th></tr></thead>';
    }

    /**
     * Render the transaction table body with all transactions.
     *
     * @return string HTML string for table body
     */
    private function renderTransactionTableBody(): string
    {
        $html = '<tbody>';

        foreach ($this->transactions as $transactionCode => $transactionData) {
            $html .= $this->renderTransactionRow($transactionCode, $transactionData);
        }

        $html .= '</tbody>';
        return $html;
    }

    /**
     * Render a single transaction row.
     *
     * @param string $transactionCode Transaction identifier
     * @param array $transactionData Transaction data
     * @return string HTML string for the transaction row
     */
    private function renderTransactionRow(string $transactionCode, array $transactionData): string
    {
        // Create bi_lineitem for this transaction
        require_once('class.bi_lineitem.php');

        // Process all line items in the transaction
        $biLineitem = null;
        foreach ($transactionData as $index => $transaction) {
            $biLineitem = new bi_lineitem($transaction, $this->vendorList, $this->operationTypes);
        }

        if ($biLineitem !== null) {
            // Get the HTML for the line item (returns complete <tr>...</tr>)
            return $biLineitem->getHtml();
        }

        // Return empty row if no line item
        return '<tr><td colspan="2">No transaction data</td></tr>';
    }

    /**
     * Render the form end tag.
     *
     * @return string HTML for form end
     */
    private function renderFormEnd(): string
    {
        return '</form>';
    }
}