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
 * @version    2.1.0
 * @since      20250422
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/CustomerDataProvider.php');
require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlFragment.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Composites/HtmlLabelRow.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlSelect.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlOption.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlHidden.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlRaw.php');

use KsfBankImport\Views\DataProviders\CustomerDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlHidden;
use Ksfraser\HTML\Elements\HtmlRaw;

/**
 * View for customer/branch partner type selection
 * 
 * Refactored to return HtmlFragment with multiple HtmlLabelRow objects.
 * Builds HtmlSelect for customers and branches from CustomerDataProvider.
 * 
 * @since 2.1.0 Returns HtmlFragment instead of string
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
     * Returns HtmlFragment containing customer/branch selection UI.
     * 
     * @return HtmlFragment Composable HTML object
     * 
     * @since 2.1.0 Returns HtmlFragment instead of string
     */
    public function getHtml(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
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
        
        // Build customer selection dropdown
        $customerSelect = $this->buildCustomerSelect();
        
        // Build branch selection dropdown or hidden field
        $branchContent = $this->buildBranchContent();
        
        // Combine customer and branch into one content fragment
        $combinedContent = new HtmlFragment();
        $combinedContent->addChild($customerSelect);
        $combinedContent->addChild($branchContent);
        
        // Create label row for customer/branch
        $label = new HtmlString(_("From Customer/Branch:"));
        $labelRow = new HtmlLabelRow($label, $combinedContent);
        $fragment->addChild($labelRow);
        
        // Add hidden fields for form submission
        $hiddenCustomer = new HtmlHidden("customer_{$this->lineItemId}", (string)($this->partnerId ?? ''));
        $fragment->addChild($hiddenCustomer);
        
        $hiddenCustomerBranch = new HtmlHidden("customer_branch_{$this->lineItemId}", (string)($this->partnerDetailId ?? ''));
        $fragment->addChild($hiddenCustomerBranch);
        
        // Display allocatable invoices if fa_customer_payment class is available
        $invoicesFragment = $this->displayAllocatableInvoices();
        if ($invoicesFragment) {
            $fragment->addChild($invoicesFragment);
        }
        
        return $fragment;
    }
    
    /**
     * Build customer dropdown selector
     * 
     * @return HtmlSelect Customer dropdown
     */
    private function buildCustomerSelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        // Add blank option
        $select->addOption(new HtmlOption('', _('Select Customer')));
        
        // Get customers from data provider
        $customers = $this->dataProvider->getCustomers();
        
        // Build options
        foreach ($customers as $customer) {
            $option = new HtmlOption($customer['debtor_no'], $customer['name']);
            
            // Mark selected if this is the current customer
            if ($this->partnerId && $this->partnerId == $customer['debtor_no']) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Build branch dropdown or hidden field
     * 
     * @return HtmlSelect|HtmlHidden Branch dropdown if customer has branches, hidden field otherwise
     */
    private function buildBranchContent()
    {
        // If customer has branches, show branch dropdown
        if ($this->partnerId && $this->dataProvider->hasBranches($this->partnerId)) {
            $select = new HtmlSelect("partnerDetailId_{$this->lineItemId}");
            $select->setClass('combo');
            $select->setAttribute('onchange', 'this.form.submit()');
            
            // Get branches for customer
            $branches = $this->dataProvider->getBranches($this->partnerId);
            
            // Build options
            foreach ($branches as $branch) {
                $option = new HtmlOption($branch['branch_code'], $branch['br_name']);
                
                // Mark selected if this is the current branch
                if ($this->partnerDetailId && $this->partnerDetailId == $branch['branch_code']) {
                    $option->setSelected(true);
                }
                
                $select->addOption($option);
            }
            
            return $select;
        } else {
            // No branches - use hidden field
            if (!defined('ANY_NUMERIC')) {
                define('ANY_NUMERIC', -1);
            }
            $this->formData->setPartnerDetailId(null);  // Sets to ANY_NUMERIC
            return new HtmlHidden("partnerDetailId_{$this->lineItemId}", (string)ANY_NUMERIC);
        }
    }
    
    /**
     * Display allocatable invoices for the customer (Mantis 3018)
     * 
     * Returns HtmlFragment with invoice allocation UI, or null if not available.
     * 
     * @return HtmlFragment|null Fragment with invoice rows, or null
     * 
     * @since 2.1.0 Returns HtmlFragment instead of string
     */
    private function displayAllocatableInvoices(): ?HtmlFragment
    {
        $_GET['customer_id'] = $this->partnerId;
        
        if (@include_once('../ksf_modules_common/class.fa_customer_payment.php')) {
            $fragment = new HtmlFragment();
            $tr = 0;
            $fcp = new \fa_customer_payment();
            $fcp->set("trans_date", $this->valueTimestamp);
            
            // Show allocatable invoices
            $allocatableHtml = new HtmlRaw($fcp->show_allocatable());
            $invoicesLabel = new HtmlString("Invoices to Pay");
            $invoicesRow = new HtmlLabelRow($invoicesLabel, $allocatableHtml);
            $fragment->addChild($invoicesRow);
            
            $res = $fcp->get_alloc_details();
            
            // Extract the invoice number from allocation details
            foreach ($res as $row) {
                $tr = $row['type_no'];
            }
            
            // Invoice allocation input (using FA function for now - could be refactored)
            $textInputHtml = new HtmlRaw(
                \text_input(
                    "Invoice_{$this->lineItemId}", 
                    $tr, 
                    6, 
                    '', 
                    \_("Invoice to Allocate Payment:")
                )
            );
            $allocationLabel = new HtmlString(_("Allocate Payment to (1) Invoice"));
            $allocationRow = new HtmlLabelRow($allocationLabel, $textInputHtml);
            $fragment->addChild($allocationRow);
            
            return $fragment;
        }
        
        return null;
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     * 
     * @since 2.1.0 Calls toHtml() on returned object
     */
    public function display(): void
    {
        $this->getHtml()->toHtml();
    }
}
