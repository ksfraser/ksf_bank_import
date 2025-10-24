<?php

/**
 * Customer Partner Type View (Refactored with Dependency Injection - Step-by-step TDD approach)
 * 
 * REFACTORING PLAN (one step at a time with tests):
 * ✅ Step 0: Start with original working code + DI
 * ✅ Step 1: Replace label_row() with HTML_ROW_LABEL
 * 🔄 Step 2: Replace hidden() FA calls with HtmlInput (CURRENT)
 * □  Step 3: Wrap in HtmlTable as appropriate
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only responsible for rendering customer/branch selection UI
 * - Dependency Inversion: Depends on CustomerDataProvider abstraction
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.0.0
 * @since      20250422
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/CustomerDataProvider.php');
require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HTML_ROW_LABEL.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlString.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlRaw.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlInput.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlHidden.php');

use KsfBankImport\Views\DataProviders\CustomerDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlString;
use Ksfraser\HTML\HtmlRaw;
use Ksfraser\HTML\HtmlInput;
use Ksfraser\HTML\HtmlHidden;

/**
 * View for customer/branch partner type selection
 * 
 * Step 0: Original implementation with dependency injection added.
 * This ensures we maintain functionality while adding testability.
 * 
 * @since 2.0.0
 */
class CustomerPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $partnerDetailId;
    private $valueTimestamp;
    private $dataProvider;
    private $formData;
    
    /**
     * Constructor with dependency injection
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param string $valueTimestamp Transaction date for invoice allocation
     * @param int|null $partnerId Existing partner (customer) ID if already set
     * @param int|null $partnerDetailId Existing partner detail (branch) ID if already set
     * @param CustomerDataProvider $dataProvider Data provider (injected dependency)
     */
    public function __construct(
        int $lineItemId, 
        string $otherBankAccount, 
        string $valueTimestamp,
        ?int $partnerId, 
        ?int $partnerDetailId,
        CustomerDataProvider $dataProvider
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->valueTimestamp = $valueTimestamp;
        $this->partnerId = $partnerId;
        $this->partnerDetailId = $partnerDetailId;
        $this->dataProvider = $dataProvider;
        $this->formData = new PartnerFormData($lineItemId);
    }
    
    /**
     * Get the HTML for this view
     * 
     * Step 2: Replace hidden() FA calls with HtmlInput
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $html = '';
        
        // If no partner ID is set, try to match by bank account
        if (empty($this->partnerId)) {
            $match = \PartnerMatcher::searchByBankAccount(\PT_CUSTOMER, $this->otherBankAccount);
            
            if (\PartnerMatcher::hasMatch($match)) {
                $this->partnerId = \PartnerMatcher::getPartnerId($match);
                $this->partnerDetailId = \PartnerMatcher::getPartnerDetailId($match);
                $this->formData->setPartnerId($this->partnerId);
                $this->formData->setPartnerDetailId($this->partnerDetailId);
            }
        }
        
        // Build customer selection UI
        $cust_text = \customer_list("partnerId_{$this->lineItemId}", null, false, true);
        
        // Add branch selection if customer has branches
        // Use injected dataProvider instead of db_customer_has_branches()
        if ($this->partnerId && $this->dataProvider->hasBranches($this->partnerId)) {
            $cust_text .= \customer_branches_list(
                $this->partnerId, 
                "partnerDetailId_{$this->lineItemId}", 
                null, 
                false, 
                true, 
                true
            );
        } else {
            if (!defined('ANY_NUMERIC')) {
                define('ANY_NUMERIC', -1);
            }
            // Step 2: Use HtmlHidden for hidden field instead of FA hidden()
            $hiddenBranch = new HtmlHidden("partnerDetailId_{$this->lineItemId}", (string)ANY_NUMERIC);
            $cust_text .= $hiddenBranch->getHtml();
            $this->formData->setPartnerDetailId(null);  // Sets to ANY_NUMERIC
        }
        
        // Step 1: Use HTML_ROW_LABEL instead of label_row()
        // Note: HTML from customer_list/customer_branches_list is trusted, use HtmlRaw
        $custTextHtml = new HtmlRaw($cust_text);
        $labelRow = new HTML_ROW_LABEL($custTextHtml, _("From Customer/Branch:"));
        $html .= $labelRow->getHtml();
        
        // Step 2: Replace hidden() FA calls with HtmlHidden for hidden fields
        $hiddenCustomer = new HtmlHidden("customer_{$this->lineItemId}", (string)($this->partnerId ?? ''));
        $html .= $hiddenCustomer->getHtml();
        
        $hiddenCustomerBranch = new HtmlHidden("customer_branch_{$this->lineItemId}", (string)($this->partnerDetailId ?? ''));
        $html .= $hiddenCustomerBranch->getHtml();
        
        // Display allocatable invoices if fa_customer_payment class is available
        $html .= $this->displayAllocatableInvoices();
        
        return $html;
    }
    
    /**
     * Display allocatable invoices for the customer (Mantis 3018)
     * 
     * Step 1: Replace label_row() with HTML_ROW_LABEL
     * 
     * @return string HTML output
     */
    private function displayAllocatableInvoices(): string
    {
        $html = '';
        $_GET['customer_id'] = $this->partnerId;
        
        if (@include_once('../ksf_modules_common/class.fa_customer_payment.php')) {
            $tr = 0;
            $fcp = new \fa_customer_payment();
            $fcp->set("trans_date", $this->valueTimestamp);
            
            // Step 1: Use HTML_ROW_LABEL for "Invoices to Pay"
            $allocatableHtml = new HtmlRaw($fcp->show_allocatable());
            $invoicesRow = new HTML_ROW_LABEL($allocatableHtml, "Invoices to Pay");
            $html .= $invoicesRow->getHtml();
            
            $res = $fcp->get_alloc_details();
            
            // Extract the invoice number from allocation details
            foreach ($res as $row) {
                $tr = $row['type_no'];
            }
            
            // Step 1: Use HTML_ROW_LABEL for invoice allocation input
            $textInputHtml = new HtmlRaw(
                \text_input(
                    "Invoice_{$this->lineItemId}", 
                    $tr, 
                    6, 
                    '', 
                    \_("Invoice to Allocate Payment:")
                )
            );
            $allocationRow = new HTML_ROW_LABEL(
                $textInputHtml,
                _("Allocate Payment to (1) Invoice")
            );
            $html .= $allocationRow->getHtml();
        }
        
        return $html;
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     */
    public function display(): void
    {
        echo $this->getHtml();
    }
}
