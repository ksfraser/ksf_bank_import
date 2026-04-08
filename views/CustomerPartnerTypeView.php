<?php

/**
 * Customer Partner Type View
 * 
 * Single Responsibility: Display customer/branch selection UI for a bank transaction line item.
 * 
 * Displays:
 * - "From Customer/Branch:" label
 * - Customer dropdown list
 * - Customer branch dropdown (if customer has branches)
 * - Allocatable invoices (if customer is selected)
 * - Invoice allocation input field
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */

require_once(__DIR__ . '/PartnerMatcher.php');

class CustomerPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $partnerDetailId;
    private $valueTimestamp;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param string $valueTimestamp Transaction date for invoice allocation
     * @param int|null $partnerId Existing partner (customer) ID if already set
     * @param int|null $partnerDetailId Existing partner detail (branch) ID if already set
     */
    public function __construct(
        int $lineItemId, 
        string $otherBankAccount, 
        string $valueTimestamp,
        ?int $partnerId = null, 
        ?int $partnerDetailId = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->valueTimestamp = $valueTimestamp;
        $this->partnerId = $partnerId;
        $this->partnerDetailId = $partnerDetailId;
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        ob_start();
        
        // If no partner ID is set, try to match by bank account
        if (empty($this->partnerId)) {
            $match = PartnerMatcher::searchByBankAccount(PT_CUSTOMER, $this->otherBankAccount);
            
            if (PartnerMatcher::hasMatch($match)) {
                $this->partnerId = $_POST["partnerId_{$this->lineItemId}"] = PartnerMatcher::getPartnerId($match);
                $this->partnerDetailId = $_POST["partnerDetailId_{$this->lineItemId}"] = PartnerMatcher::getPartnerDetailId($match);
            }
        }
        
        // Build customer selection UI
        $cust_text = customer_list("partnerId_{$this->lineItemId}", null, false, true);
        
        // Add branch selection if customer has branches
        if (db_customer_has_branches($this->partnerId)) {
            $cust_text .= customer_branches_list(
                $this->partnerId, 
                "partnerDetailId_{$this->lineItemId}", 
                null, 
                false, 
                true, 
                true
            );
        } else {
            hidden("partnerDetailId_{$this->lineItemId}", ANY_NUMERIC);
            $_POST["partnerDetailId_{$this->lineItemId}"] = ANY_NUMERIC;
        }
        
        label_row(_("From Customer/Branch:"), $cust_text);
        
        // Hidden fields for downstream processing
        hidden("customer_{$this->lineItemId}", $this->partnerId);
        hidden("customer_branch_{$this->lineItemId}", $this->partnerDetailId);
        
        // Display allocatable invoices if fa_customer_payment class is available
        $this->displayAllocatableInvoices();
        
        return ob_get_clean();
    }
    
    /**
     * Display allocatable invoices for the customer (Mantis 3018)
     */
    private function displayAllocatableInvoices(): void
    {
        $_GET['customer_id'] = $this->partnerId;
        
        if (@include_once('../ksf_modules_common/class.fa_customer_payment.php')) {
            $tr = 0;
            $fcp = new fa_customer_payment();
            $fcp->set("trans_date", $this->valueTimestamp);
            
            label_row("Invoices to Pay", $fcp->show_allocatable());
            
            $res = $fcp->get_alloc_details();
            
            // Extract the invoice number from allocation details
            foreach ($res as $row) {
                $tr = $row['type_no'];
            }
            
            label_row(
                _("Allocate Payment to (1) Invoice"), 
                text_input(
                    "Invoice_{$this->lineItemId}", 
                    $tr, 
                    6, 
                    '', 
                    _("Invoice to Allocate Payment:")
                )
            );
        }
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     */
    public function display(): void
    {
        echo $this->getHtml();
    }
}
