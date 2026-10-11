<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Views\DataProviders\CustomerDataProvider;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlHidden;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\FaBankImport\Services\PartnerMatcher;

/**
 * CustomerPartnerTypeView - Display customer/branch selection UI
 * 
 * Single Responsibility: Render customer and branch selection dropdowns with auto-matching.
 * 
 * Responsibilities:
 * - Build customer dropdown from injected data provider
 * - Build branch dropdown when customer has branches
 * - Auto-match customer by bank account via PartnerMatcher
 * - Display allocatable invoices table when available
 * 
 * Dependencies (via DI):
 * - CustomerDataProvider: Provides customer and branch data
 * - PartnerMatcher: Static service for bank account matching
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
class CustomerPartnerTypeView implements PartnerTypeViewInterface
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
     * @var string
     */
    private $valueTimestamp;
    
    /**
     * @var int|null
     */
    private $partnerId;
    
    /**
     * @var int|null
     */
    private $partnerDetailId;
    
    /**
     * @var CustomerDataProvider
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
     * @param string $valueTimestamp Transaction date for invoice allocation
     * @param CustomerDataProvider $dataProvider Customer data provider
     * @param int|null $partnerId Existing partner ID if already set
     * @param int|null $partnerDetailId Existing partner detail ID if already set
     * @param PartnerMatcher|null $partnerMatcher Partner matching service (for testing)
     */
    public function __construct(
        int $lineItemId,
        string $otherBankAccount,
        string $valueTimestamp,
        CustomerDataProvider $dataProvider,
        ?int $partnerId = null,
        ?int $partnerDetailId = null,
        ?PartnerMatcher $partnerMatcher = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->valueTimestamp = $valueTimestamp;
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
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $this->autoMatchCustomer();
        
        $fragment = new HtmlFragment();
        
        $content = new HtmlFragment();
        $content->addChild($this->buildCustomerSelect());
        $content->addChild($this->buildBranchContent());
        
        $label = new HtmlString(_('From Customer/Branch:'));
        $labelRow = new HtmlLabelRow($label, $content);
        $fragment->addChild($labelRow);
        
        $fragment->addChild(new HtmlHidden("customer_{$this->lineItemId}", (string)($this->partnerId ?? '')));
        $fragment->addChild(new HtmlHidden("customer_branch_{$this->lineItemId}", (string)($this->partnerDetailId ?? '')));
        
        $invoicesFragment = $this->buildAllocatableInvoicesFragment();
        if ($invoicesFragment) {
            $fragment->addChild($invoicesFragment);
        }
        
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
     * Auto-match customer by bank account if no partner ID is set
     * 
     * @return void
     */
    private function autoMatchCustomer(): void
    {
        if ($this->partnerId !== null) {
            return;
        }
        
        $match = $this->partnerMatcher->searchByBankAccount(PT_CUSTOMER, $this->otherBankAccount);
        
        if ($this->partnerMatcher->hasMatch($match)) {
            $this->partnerId = $this->partnerMatcher->getPartnerId($match);
            $this->partnerDetailId = $this->partnerMatcher->getPartnerDetailId($match);
        }
    }
    
    /**
     * Build customer select dropdown
     * 
     * @return HtmlSelect
     */
    private function buildCustomerSelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        $select->addOption(new HtmlOption('', _('Select Customer')));
        
        $customers = $this->dataProvider->getCustomers();
        
        foreach ($customers as $customer) {
            $customerId = $customer['debtor_no'] ?? null;
            $customerName = $customer['name'] ?? '';
            
            if ($customerId === null) {
                continue;
            }
            
            $option = new HtmlOption((string)$customerId, $customerName);
            
            if ($this->partnerId !== null && (int)$customerId === $this->partnerId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Build branch dropdown or hidden field
     * 
     * @return HtmlSelect|HtmlHidden
     */
    private function buildBranchContent()
    {
        if ($this->partnerId !== null && $this->dataProvider->hasBranches($this->partnerId)) {
            return $this->buildBranchSelect();
        }
        
        $anyNumeric = defined('ANY_NUMERIC') ? ANY_NUMERIC : -1;
        return new HtmlHidden("partnerDetailId_{$this->lineItemId}", (string)$anyNumeric);
    }
    
    /**
     * Build branch select dropdown
     * 
     * @return HtmlSelect
     */
    private function buildBranchSelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerDetailId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        $branches = $this->dataProvider->getBranches($this->partnerId);
        
        foreach ($branches as $branch) {
            $branchCode = $branch['branch_code'] ?? null;
            $branchName = $branch['br_name'] ?? '';
            
            if ($branchCode === null) {
                continue;
            }
            
            $option = new HtmlOption((string)$branchCode, $branchName);
            
            if ($this->partnerDetailId !== null && (int)$branchCode === $this->partnerDetailId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Build allocatable invoices fragment
     * 
     * Returns HtmlFragment with invoice allocation UI, or null if not available.
     * 
     * @return HtmlFragment|null
     */
    private function buildAllocatableInvoicesFragment(): ?HtmlFragment
    {
        if (!class_exists('fa_customer_payment', false)) {
            return null;
        }
        
        $fragment = new HtmlFragment();
        
        $_GET['customer_id'] = $this->partnerId;
        
        try {
            $fcp = new \fa_customer_payment();
            $fcp->set('trans_date', $this->valueTimestamp);
            
            $res = $fcp->get_alloc_details();
            
            if (count($res) > 0) {
                $allocTable = new AllocatableInvoicesTable($res);
                $invoicesLabel = new HtmlString(_('Invoices to Pay'));
                $invoicesRow = new HtmlLabelRow($invoicesLabel, $allocTable);
                $fragment->addChild($invoicesRow);
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return $fragment->isEmpty() ? null : $fragment;
    }
}