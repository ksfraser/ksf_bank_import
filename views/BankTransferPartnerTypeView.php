<?php

/**
 * Bank Transfer Partner Type View
 * 
 * Single Responsibility: Display bank account selection UI for bank transfer transactions.
 * 
 * Displays:
 * - Direction-aware label (To/From based on transaction type)
 * - Bank account dropdown list
 * - Auto-matches destination bank account if available
 * 
 * @author Kevin Fraser / ChatGPT
 * @since 20250422
 */

require_once(__DIR__ . '/PartnerMatcher.php');

class BankTransferPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $partnerDetailId;
    private $transactionDC;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param string $transactionDC Transaction type ('C' for credit/deposit, 'D' for debit/payment)
     * @param int|null $partnerId Existing partner (bank account) ID if already set
     * @param int|null $partnerDetailId Existing partner detail ID if already set
     */
    public function __construct(
        int $lineItemId, 
        string $otherBankAccount, 
        string $transactionDC,
        ?int $partnerId = null, 
        ?int $partnerDetailId = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->transactionDC = $transactionDC;
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
        if (empty($_POST["partnerId_{$this->lineItemId}"])) {
            $match = PartnerMatcher::searchByBankAccount(ST_BANKTRANSFER, $this->otherBankAccount);
            
            if (PartnerMatcher::hasMatch($match)) {
                $_POST["partnerId_{$this->lineItemId}"] = PartnerMatcher::getPartnerId($match);
                $_POST["partnerDetailId_{$this->lineItemId}"] = PartnerMatcher::getPartnerDetailId($match);
            } else {
                $_POST["partnerId_{$this->lineItemId}"] = ANY_NUMERIC;
            }
        }
        
        // Determine label based on transaction direction (Mantis 2963)
        if ($this->transactionDC == 'C') {
            $rowLabel = "Transfer to <i>Our Bank Account</i> <b>from (OTHER ACCOUNT</b>):";
        } else {
            $rowLabel = "Transfer from <i>Our Bank Account</i> <b>To (OTHER ACCOUNT</b>):";
        }
        
        // Display bank account selection
        // bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
        label_row(
            _($rowLabel),
            bank_accounts_list("partnerId_{$this->lineItemId}", $_POST["partnerId_{$this->lineItemId}"], null, false)
        );
        
        $this->partnerId = $_POST["partnerId_{$this->lineItemId}"];
        
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
