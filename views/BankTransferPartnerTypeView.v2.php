<?php

/**
 * Bank Transfer Partner Type View - v2.1 (Option B - Return Objects)
 * 
 * Refactoring complete:
 * - Step 0: Added BankAccountDataProvider injection ✅
 * - Step 1: Replaced label_row() with HtmlLabelRow ✅
 * - Step 2: No hidden fields needed (simpler) ✅
 * - Option B: Changed getHtml() to return HtmlFragment ✅
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
 * @version    2.1.0
 */

namespace KsfBankImport\Views;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlString;

require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/BankAccountDataProvider.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlFragment.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Composites/HtmlLabelRow.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlSelect.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlOption.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlString.php');

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
     * @return HtmlFragment HTML fragment containing bank account selection
     */
    public function getHtml(): HtmlFragment
    {
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
        
        // Build bank account select
        $bankSelect = $this->buildBankAccountSelect();
        
        // Create label row with bank account dropdown
        $label = new HtmlString(_($rowLabel));
        $labelRow = new HtmlLabelRow($label, $bankSelect);
        
        // Update partnerId after building select
        $this->partnerId = $this->formData->getPartnerId();
        
        // Return fragment containing the label row
        $fragment = new HtmlFragment();
        $fragment->addChild($labelRow);
        return $fragment;
    }
    
    /**
     * Build bank account select dropdown
     * 
     * @return HtmlSelect Bank account selection dropdown
     */
    private function buildBankAccountSelect(): HtmlSelect
    {
        $selectName = "partnerId_{$this->lineItemId}";
        $select = new HtmlSelect($selectName);
        
        // Get bank accounts from data provider
        $bankAccounts = $this->dataProvider->getBankAccounts();
        $selectedId = $this->formData->getRawPartnerId();
        
        // Build options
        foreach ($bankAccounts as $account) {
            $label = $account['name'] ?? ($account['bank_account_name'] ?? '');
            $option = new HtmlOption(
                $account['id'], 
                $label
            );
            
            if ($account['id'] == $selectedId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     */
    public function display(): void
    {
        echo $this->getHtml()->toHtml();
    }
}
