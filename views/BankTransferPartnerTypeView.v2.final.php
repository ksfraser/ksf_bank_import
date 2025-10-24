<?php

/**
 * Bank Transfer Partner Type View - v2 Final (Steps 0-2 complete)
 * 
 * Refactoring complete:
 * - Step 0: Added BankAccountDataProvider injection ✅
 * - Step 1: Replaced label_row() with HTML_ROW_LABEL ✅
 * - Step 2: No hidden fields needed (simpler) ✅
 * 
 * Single Responsibility: Display bank account selection UI for bank transfer transactions.
 * 
 * Displays:
 * - Direction-aware label (To/From based on transaction type)
 * - Bank account dropdown list
 * - Auto-matches destination bank account if available
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @since      2025-01-07
 * @version    2.0.0-final
 */

namespace KsfBankImport\Views;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlRaw;

require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/BankAccountDataProvider.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HTML_ROW_LABEL.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlRaw.php');

class BankTransferPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $partnerDetailId;
    private $transactionDC;
    private BankAccountDataProvider $dataProvider;
    private PartnerFormData $formData;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param string $transactionDC Transaction type ('C' for credit/deposit, 'D' for debit/payment)
     * @param BankAccountDataProvider $dataProvider Bank account data provider (injected)
     * @param int|null $partnerId Existing partner (bank account) ID if already set
     * @param int|null $partnerDetailId Existing partner detail ID if already set
     */
    public function __construct(
        int $lineItemId, 
        string $otherBankAccount, 
        string $transactionDC,
        BankAccountDataProvider $dataProvider,
        ?int $partnerId = null, 
        ?int $partnerDetailId = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->transactionDC = $transactionDC;
        $this->partnerId = $partnerId;
        $this->partnerDetailId = $partnerDetailId;
        $this->dataProvider = $dataProvider;
        $this->formData = new PartnerFormData($lineItemId);
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $html = '';
        
        // If no partner ID is set, try to match by bank account
        if (!$this->formData->hasPartnerId()) {
            $match = \PartnerMatcher::searchByBankAccount(ST_BANKTRANSFER, $this->otherBankAccount);
            
            if (\PartnerMatcher::hasMatch($match)) {
                $this->formData->setPartnerId(\PartnerMatcher::getPartnerId($match));
                $this->formData->setPartnerDetailId(\PartnerMatcher::getPartnerDetailId($match));
            } else {
                $this->formData->setPartnerId(null);  // Sets to ANY_NUMERIC
            }
        }
        
        // Determine label based on transaction direction (Mantis 2963)
        if ($this->transactionDC == 'C') {
            $rowLabel = "Transfer to <i>Our Bank Account</i> <b>from (OTHER ACCOUNT</b>):";
        } else {
            $rowLabel = "Transfer from <i>Our Bank Account</i> <b>To (OTHER ACCOUNT</b>):";
        }
        
        // Generate bank account select list
        // bank_accounts_list($name, $selected_id=null, $submit_on_change=false, $spec_option=false)
        $bankListHtml = \bank_accounts_list(
            "partnerId_{$this->lineItemId}", 
            $this->formData->getRawPartnerId(), 
            null, 
            false
        );
        
        // Build HTML using HTML_ROW_LABEL
        $bankSelectHtml = new HtmlRaw($bankListHtml);
        $labelRow = new HTML_ROW_LABEL($bankSelectHtml, _($rowLabel));
        $html .= $labelRow->getHtml();
        
        $this->partnerId = $this->formData->getPartnerId();
        
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
