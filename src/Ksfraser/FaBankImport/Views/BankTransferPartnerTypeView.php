<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\FaBankImport\Services\PartnerMatcher;

/**
 * BankTransferPartnerTypeView - Display bank account selection UI for bank transfer transactions
 * 
 * Single Responsibility: Render bank account selection dropdown with direction-aware labels.
 * 
 * Responsibilities:
 * - Display direction-aware label (To/From based on transaction type)
 * - Build bank account dropdown from injected data provider
 * - Auto-match destination bank account via PartnerMatcher
 * 
 * Dependencies (via DI):
 * - BankAccountDataProvider: Provides bank account list
 * - PartnerMatcher: Static service for bank account matching
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
class BankTransferPartnerTypeView implements PartnerTypeViewInterface
{
    /**
     * @var int
     */
    private $lineItemId;
    
    /**
     * @var string
     */
    private $otherBankAccount;
    
    /**
     * @var string 'C' for credit, 'D' for debit
     */
    private $transactionDC;
    
    /**
     * @var int|null
     */
    private $partnerId;
    
    /**
     * @var int|null
     */
    private $partnerDetailId;
    
    /**
     * @var BankAccountDataProvider
     */
    private $dataProvider;
    
    /**
     * @var PartnerMatcher
     */
    private $partnerMatcher;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param string $transactionDC Transaction type ('C' for credit, 'D' for debit)
     * @param BankAccountDataProvider $dataProvider Bank account data provider
     * @param int|null $partnerId Existing partner ID if already set
     * @param int|null $partnerDetailId Existing partner detail ID if already set
     * @param PartnerMatcher|null $partnerMatcher Partner matching service (for testing)
     */
    public function __construct(
        int $lineItemId,
        string $otherBankAccount,
        string $transactionDC,
        BankAccountDataProvider $dataProvider,
        ?int $partnerId = null,
        ?int $partnerDetailId = null,
        ?PartnerMatcher $partnerMatcher = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->transactionDC = $transactionDC;
        $this->partnerId = $partnerId;
        $this->partnerDetailId = $partnerDetailId;
        $this->dataProvider = $dataProvider;
        $this->partnerMatcher = $partnerMatcher ?? new PartnerMatcher();
    }
    
    /**
     * Get the line item ID
     * 
     * @return int
     */
    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }
    
    /**
     * Get the selected partner ID
     * 
     * @return int|null
     */
    public function getSelectedPartnerId(): ?int
    {
        return $this->partnerId;
    }
    
    /**
     * Get the selected partner detail ID
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int
    {
        return $this->partnerDetailId;
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return HtmlFragment HTML fragment containing bank account selection
     */
    public function getHtml(): string
    {
        $this->autoMatchBankAccount();
        
        $label = $this->buildDirectionLabel();
        $select = $this->buildBankAccountSelect();
        $labelRow = new HtmlLabelRow(new HtmlString($label), $select);
        
        $fragment = new HtmlFragment();
        $fragment->addChild($labelRow);
        
        return $fragment->getHtml();
    }
    
    /**
     * Output HTML directly
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
    
    /**
     * Auto-match bank account if no partner ID is set
     * 
     * @return void
     */
    private function autoMatchBankAccount(): void
    {
        if ($this->partnerId !== null) {
            return;
        }
        
        $match = $this->partnerMatcher->searchByBankAccount(ST_BANKTRANSFER, $this->otherBankAccount);
        
        if ($this->partnerMatcher->hasMatch($match)) {
            $this->partnerId = $this->partnerMatcher->getPartnerId($match);
            $this->partnerDetailId = $this->partnerMatcher->getPartnerDetailId($match);
        }
    }
    
    /**
     * Build direction-aware label
     * 
     * @return string The label text
     */
    private function buildDirectionLabel(): string
    {
        if ($this->transactionDC === 'C') {
            return 'Transfer to <i>Our Bank Account</i> <b>from (OTHER ACCOUNT)</b>:';
        }
        
        return 'Transfer from <i>Our Bank Account</i> <b>To (OTHER ACCOUNT)</b>:';
    }
    
    /**
     * Build bank account select dropdown
     * 
     * @return HtmlSelect
     */
    private function buildBankAccountSelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        $bankAccounts = $this->dataProvider->getBankAccounts();
        
        foreach ($bankAccounts as $account) {
            $accountId = $account['id'];
            $accountName = $account['bank_account_name'] ?? $accountId;
            
            $option = new HtmlOption($accountId, $accountName);
            
            if ($this->partnerId !== null && (string)$accountId === (string)$this->partnerId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
}