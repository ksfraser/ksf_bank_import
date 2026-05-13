<?php

namespace Ksfraser\FaBankImport\Views;

/**
 * View class for rendering the main process statements page.
 *
 * This class encapsulates all HTML generation for the bank import process statements page,
 * using proper Single Responsibility Principle with the Ksfraser\HTML component library.
 * 
 * Responsibilities:
 * - Coordinate pagination and transaction display
 * - Delegate HTML rendering to HTML library components (SubmitButton, HtmlInput, etc.)
 
 * Architecture Notes:
 * - Uses library components: SubmitButton for pagination buttons, HtmlInput for page selector
 * - Delegates table HTML to bi_lineitem->getHtml() 
 * - Maintains form-compatible HTML output for FrontAccounting integration
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
     * @var int Counter to track how many pagination instances have been rendered (for debugging)
     */
    private $paginationData = null;

    /**
     * @var int Count of calls to pagination render
     */
    private $paginationRenderCount;

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
        $this->paginationRenderCount = 0;
    }

    /**
     * Set pagination data from transaction query result
     *
     * @since 20260405
     * @param array $paginationData Pagination metadata array
     * @return void
     */
    public function setPaginationData(array $paginationData): void
    {
        $this->paginationData = $paginationData;
    }

    /**
     * Render the complete process statements page HTML.
     *
     * @return string Complete HTML for the page
     */
    public function render(): string
    {
//echo __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . "<br />";
        $html = $this->renderDocumentTableDiv();
        return $html;
    }

    /**
     * Render the document table div with filter and transaction tables.
     *
     * @return string HTML for the document table div
     */
    private function renderDocumentTableDiv(): string
    {
//echo __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . "<br />";
        $html = '<div id="doc_tbl">';

        // Add filter table
        $html .= $this->renderFilterTable();

        // Add pagination controls (top)
        $html .= $this->renderPaginationControls();

        // Add transaction table
        $html .= $this->renderTransactionTable();

        // Add pagination controls (bottom)
        $html .= $this->renderPaginationControls( 2 );

        $html .= '</div>';

        return $html;
    }

    /**
     * Render the filter table using the existing header_table.php class.
	*
	* Currently being rendered in process_statements
     *
     * @return string HTML string for the filter table
     */
    private function renderFilterTable(): string
    {
//echo __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . "<br />";
        // Use the new string-returning method instead of output buffering
        //require_once( __DIR__ . '/../header_table.php');
	//$headertable = new ksf_modules_table_filter_by_date();
        //$headertable->bank_import_header();
   //     return $headertable->getBankImportHeaderHtml();
	return "";
    }

    /**
     * Render the transaction display table.
     *
     * @return string HTML string for the transaction table
     */
    private function renderTransactionTable(): string
    {
	$html = "";
//echo __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . "<br />";
	// DEBUG: Show what we received
        $debug = '<div style="background: #ffe6e6; padding: 10px; margin: 10px 0; border: 2px solid red;">';
        $debug .= '<strong>DEBUG - Transactions received:</strong><br>';
        $debug .= 'Count: ' . count($this->transactions) . '<br>';
        $debug .= 'Keys: ' . json_encode(array_keys($this->transactions)) . '<br>';
        $debug .= 'Raw: ' . json_encode($this->transactions) . '';
        $debug .= '</div>';
        
        if (count($this->transactions) === 0) {
            $debug .= '<div style="background: #fff3cd; padding: 10px; border: 2px solid orange;">';
            $debug .= '<strong>WARNING: No transactions to display!</strong>';
            $debug .= '</div>';
        }
        //$html .= $debug;

        $html .= '<table class="TABLESTYLE" width="100%">';
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
        //table_header(array("Transaction Details", "Operation/Status"));
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
	//NEED TO CONVERT TO A USE!
        //require_once( 'class.bi_lineitem.php');
        require_once( __DIR__ . '/../../../../class.bi_lineitem.php');
        // Process all line items in the transaction
        $biLineitem = null;
        foreach ($transactionData as $index => $transaction) {
		//echo __FILE__ . "::" . __LINE__ . "<br />";
            $biLineitem = new \bi_lineitem($transaction, $this->vendorList, $this->operationTypes);
        }
	//var_dump( $biLineitem );

        if ($biLineitem !== null) {
            // Get the HTML for the line item (returns complete <tr>...</tr>)

		//echo __FILE__ . "::" . __LINE__ . "<br />";
		ob_start();
	        $biLineitem->display();
	        $html = ob_get_clean();
	        return $html;
        }
	//echo __FILE__ . "::" . __LINE__ . "<br />";

        // Return empty row if no line item
        return '<tr><td colspan="2">No transaction data</td></tr>';
    }


    /**
     * Render pagination navigation controls
     *
     * Displays page information and Previous/Next buttons for navigating through
     * paginated results. Returns empty string if pagination data not available.
	*
	*@since 20260405
	* Tested produces the control table.  
	* Untested (at time of writing) whether it actually works
	*
     * Form Submission Note:
     * - Each pagination instance has unique field names with instance number suffix
     * - Top pagination: instance=1, uses page_nav_action_1, goto_page_1, current_page_display_1
     * - Bottom pagination: instance=2, uses page_nav_action_2, goto_page_2, current_page_display_2
     * - This prevents form field conflicts when rendering pagination twice
     * - process_statements.php detects which instance submitted via field presence
     *
     * @param int $instance Instance number (1=top, 2=bottom) for unique field names
     * @return string HTML for pagination controls
     */
    private function renderPaginationControls( int $instance = 1 ): string
    {
        if (!$this->paginationData) {
            return '';
        }

	$this->paginationRenderCount++;
//        echo 'DEBUG: renderPaginationControls called - Instance: ' . $instance . ', Total renders so far: ' . $this->paginationRenderCount . '<br>';


        $current_page = $this->paginationData['current_page'];
        $total_pages = $this->paginationData['total_pages'];
        $total_count = $this->paginationData['total_count'];
        $limit = $this->paginationData['limit'];
	$page_size = $this->paginationData['page_size'] ?? 10;
        
//        echo 'DEBUG: Page size in pagination data: ' . htmlspecialchars($page_size) . '<br>';

	 // Create unique field name suffix based on instance
        $suffix = '_' . $instance;

        $html = '<div class="pagination-controls" style="text-align: center; margin: 10px 0; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
        
        // Page info
        $html .= '<span style="font-weight: bold;">Page ' . htmlspecialchars($current_page) . ' of ' . htmlspecialchars($total_pages) . ' (Total: ' . htmlspecialchars($total_count) . ' rows)</span><br><br>';
       
        // Hidden field to preserve page_size
//        $html .= '<input type="hidden" name="page_size" value="' . htmlspecialchars($page_size) . '">';
        
        // Navigation buttons
        $html .= '<div>';
        
        // Previous button - uses hidden field to communicate action
        if ($current_page > 1) {
		$html .= '<button type="submit" name="page_nav_action' . $suffix . '" value="previous" style="margin: 0 5px; cursor: pointer;">← Previous</button>';
        } else {
            $html .= '<button type="submit" disabled style="margin: 0 5px; opacity: 0.5; cursor: not-allowed;">← Previous</button>';
        }
        
	// Page number display and input fields
	// Hidden field tracks current page for Previous/Next buttons
	$html .= '<input type="hidden" name="current_page_display' . $suffix . '" value="' . htmlspecialchars($current_page) . '">';

        // Text input for user to type page number to Go to
	$html .= '<input type="text" name="goto_page' . $suffix . '" value="' . htmlspecialchars($current_page) . '" style="width: 50px; padding: 4px; margin: 0 5px;"> ';
        $html .= '<button type="submit" name="page_nav_action' . $suffix . '" value="go" style="margin: 0 5px; padding: 4px 12px; cursor: pointer;">Go</button>';

	// Next button - uses hidden field to communicate action
        if ($current_page < $total_pages) {
		$html .= '<button type="submit" name="page_nav_action' . $suffix . '" value="next" style="margin: 0 5px; cursor: pointer;">Next →</button>';

        } else {
            $html .= '<button type="submit" disabled style="margin: 0 5px; opacity: 0.5; cursor: not-allowed;">Next →</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
/**/

    /**
     * Render the form end tag.
     *
     * @return string HTML for form end
     */
    private function renderFormEnd(): string
    {
//echo __FILE__ . "::" . __LINE__ . "::" . __METHOD__ . "<br />";
        return '</form>';
    }
}
