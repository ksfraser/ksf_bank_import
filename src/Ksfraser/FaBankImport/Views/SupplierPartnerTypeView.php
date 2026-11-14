<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Views\DataProviders\PartnerDataProviderInterface;
use Ksfraser\FaBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\FaBankImport\Services\PartnerMatcher;

/**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001

 * SupplierPartnerTypeView - Display supplier selection UI
 * 
 * Single Responsibility: Render supplier selection dropdown with auto-matching.
 * 
 * Responsibilities:
 * - Build supplier dropdown from injected data provider
 * - Auto-match supplier by bank account via PartnerMatcher
 * 
 * Dependencies (via DI):
 * - PartnerDataProviderInterface (SupplierDataProvider): Provides supplier list
 * - PartnerMatcher: Static service for bank account matching
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
class SupplierPartnerTypeView implements PartnerTypeViewInterface
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
     * @var int|null
     */
    private $partnerId;
    
    /**
     * @var PartnerDataProviderInterface
     */
    private $dataProvider;
    
    /**
     * @var PartnerMatcher
     */
    private $partnerMatcher;
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param PartnerDataProviderInterface $dataProvider Supplier data provider
     * @param int|null $partnerId Existing partner ID if already set
     * @param PartnerMatcher|null $partnerMatcher Partner matching service (for testing)
     */
    public function __construct(
        int $lineItemId,
        string $otherBankAccount,
        PartnerDataProviderInterface $dataProvider,
        ?int $partnerId = null,
        ?PartnerMatcher $partnerMatcher = null
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->partnerId = $partnerId;
        $this->dataProvider = $dataProvider;
        $this->partnerMatcher = $partnerMatcher ?? new PartnerMatcher();
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Get the line item ID
     * 
     * @return int
     */
    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Get the selected partner ID
     * 
     * @return int|null
     */
    public function getSelectedPartnerId(): ?int
    {
        return $this->partnerId;
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Get the selected partner detail ID
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int
    {
        return null;
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $this->autoMatchSupplier();
        
        $select = $this->buildSupplierSelect();
        $label = new HtmlString(_('Payment To:'));
        $labelRow = new HtmlLabelRow($label, $select);
        
        $fragment = new HtmlFragment();
        $fragment->addChild($labelRow);
        
        return $fragment->getHtml();
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Output HTML directly
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Auto-match supplier by bank account if no partner ID is set
     * 
     * @return void
     */
    private function autoMatchSupplier(): void
    {
        if ($this->partnerId !== null) {
            return;
        }
        
        $match = $this->partnerMatcher->searchByBankAccount(PT_SUPPLIER, $this->otherBankAccount);
        
        if ($this->partnerMatcher->hasMatch($match)) {
            $this->partnerId = $this->partnerMatcher->getPartnerId($match);
        }
    }
    
    /**
 * @BABOK FR-PROC-002, FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
     * Build supplier select dropdown
     * 
     * @return HtmlSelect
     */
    private function buildSupplierSelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        $select->addOption(new HtmlOption('', _('Select Supplier')));
        
        $suppliers = $this->dataProvider->getPartners();
        
        foreach ($suppliers as $supplier) {
            $supplierId = $supplier['supplier_id'] ?? $supplier['id'] ?? null;
            $supplierName = $supplier['supp_name'] ?? $supplier['name'] ?? '';
            
            if ($supplierId === null) {
                continue;
            }
            
            $option = new HtmlOption((string)$supplierId, $supplierName);
            
            if ($this->partnerId !== null && (int)$supplierId === $this->partnerId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
}
