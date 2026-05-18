<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Views\DataProviders\PartnerDataProviderInterface;
use Ksfraser\FaBankImport\Views\DataProviders\QuickEntryDataProvider;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\PartnerFormData;

/**
 * QuickEntryPartnerTypeView - Display quick entry selection UI
 * 
 * Single Responsibility: Render quick entry selection dropdown based on transaction direction.
 * 
 * Responsibilities:
 * - Build quick entry dropdown from injected data provider
 * - Display base description of selected entry
 * 
 * Dependencies (via DI):
 * - QuickEntryDataProvider: Provides quick entry list (deposit or payment based on transactionDC)
 * 
 * @package Ksfraser\FaBankImport\Views
 * @since 20251019
 */
class QuickEntryPartnerTypeView implements PartnerTypeViewInterface
{
    /**
     * @var int
     */
    private $lineItemId;
    
    /**
     * @var string 'C' for credit/deposit, 'D' for debit/payment
     */
    private $transactionDC;
    
    /**
     * @var QuickEntryDataProvider
     */
    private $dataProvider;
    
    /**
     * @var PartnerFormData
     */
    private $formData;
    
    /**
     * Constructor
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $transactionDC Transaction type ('C' for credit/deposit, 'D' for debit/payment)
     * @param QuickEntryDataProvider $dataProvider Quick entry data provider
     */
    public function __construct(
        int $lineItemId,
        string $transactionDC,
        QuickEntryDataProvider $dataProvider
    ) {
        $this->lineItemId = $lineItemId;
        $this->transactionDC = $transactionDC;
        $this->dataProvider = $dataProvider;
        $this->formData = new PartnerFormData($lineItemId);
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
        return $this->formData->hasPartnerId() ? $this->formData->getPartnerId() : null;
    }
    
    /**
     * Get the selected partner detail ID
     * 
     * @return int|null
     */
    public function getSelectedPartnerDetailId(): ?int
    {
        return null;
    }
    
    /**
     * Get the HTML for this view
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $select = $this->buildQuickEntrySelect();
        $description = $this->getQuickEntryDescription();
        
        if ($description !== '') {
            $content = new HtmlFragment();
            $content->addChild($select);
            $content->addChild(new HtmlString(' ' . $description));
        } else {
            $content = $select;
        }
        
        $label = new HtmlString(_('Quick Entry:'));
        $labelRow = new HtmlLabelRow($label, $content);
        
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
     * Build quick entry select dropdown
     * 
     * @return HtmlSelect
     */
    private function buildQuickEntrySelect(): HtmlSelect
    {
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        $select->addOption(new HtmlOption('', _('Select Quick Entry')));
        
        $entries = $this->dataProvider->getEntries();
        
        foreach ($entries as $entry) {
            $entryId = $entry['id'] ?? null;
            $entryDescription = $entry['description'] ?? '';
            
            if ($entryId === null) {
                continue;
            }
            
            $option = new HtmlOption((string)$entryId, $entryDescription);
            
            if ($this->formData->hasPartnerId() && $this->formData->getPartnerId() == $entryId) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Get base description of selected quick entry
     * 
     * @return string Description text (empty string if none)
     */
    private function getQuickEntryDescription(): string
    {
        if (!$this->formData->hasPartnerId()) {
            return '';
        }
        
        $selectedId = $this->formData->getPartnerId();
        
        if (!$selectedId) {
            return '';
        }
        
        $entry = $this->dataProvider->getEntry($selectedId);
        
        if (!$entry) {
            return '';
        }
        
        return $entry['base_desc'] ?? '';
    }
}