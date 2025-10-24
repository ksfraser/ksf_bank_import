<?php

/**
 * Quick Entry Partner Type View
 * 
 * Single Responsibility: Display quick entry selection UI for a bank transaction line item.
 * 
 * Displays:
 * - "Quick Entry:" label
 * - Quick entry dropdown list (filtered by deposit/payment type)
 * - Base description of selected quick entry
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */

class QuickEntryPartnerTypeView
{
    private $lineItemId;
    private $transactionDC;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $transactionDC Transaction type ('C' for credit/deposit, 'D' for debit/payment)
     */
    public function __construct(int $lineItemId, string $transactionDC)
    {
        $this->lineItemId = $lineItemId;
        $this->transactionDC = $transactionDC;
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        ob_start();
        
        // Build quick entry selector
        // quick_entries_list($name, $selected_id=null, $type=null, $submit_on_change=false)
        // Filter by QE_DEPOSIT for credits, QE_PAYMENT for debits
        $qe_text = quick_entries_list(
            "partnerId_{$this->lineItemId}", 
            null, 
            (($this->transactionDC == 'C') ? QE_DEPOSIT : QE_PAYMENT), 
            true
        );
        
        // Add base description of selected quick entry
        $qe = get_quick_entry(get_post("partnerId_{$this->lineItemId}"));
        $qe_text .= " " . $qe['base_desc'];
        
        label_row("Quick Entry:", $qe_text);
        
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
