<?php

/**
 * Supplier Partner Type View
 * 
 * Single Responsibility: Display supplier selection UI for a bank transaction line item.
 * 
 * Displays:
 * - "Payment To:" label
 * - Supplier dropdown list
 * - Auto-matches supplier based on bank account if available
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */

require_once(__DIR__ . '/PartnerMatcher.php');

class SupplierPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param int|null $partnerId Existing partner ID if already set
     */
    public function __construct(int $lineItemId, string $otherBankAccount, ?int $partnerId = null)
    {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->partnerId = $partnerId;
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
        $matched_supplier = [];
        if (empty($this->partnerId)) {
            $matched_supplier = PartnerMatcher::searchByBankAccount(PT_SUPPLIER, $this->otherBankAccount);
            
            if (PartnerMatcher::hasMatch($matched_supplier)) {
                $this->partnerId = $_POST["partnerId_{$this->lineItemId}"] = PartnerMatcher::getPartnerId($matched_supplier);
            }
        }
        
        // Display supplier selection dropdown
        // supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
        label_row(_("Payment To:"), supplier_list("partnerId_{$this->lineItemId}", $matched_supplier, false, false));
        
        return ob_get_clean();
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     */
    public function display(): void
    {
        echo $this->getHtml();
    }
}
