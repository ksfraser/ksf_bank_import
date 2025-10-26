<?php

/**
 * Supplier Partner Type View (Refactored with Dependency Injection - Step-by-step TDD approach)
 * 
 * REFACTORING PLAN (one step at a time with tests):
 * ✅ Step 0: Start with original working code + DI
 * ✅ Step 1: Replace label_row() with HTML_ROW_LABEL  
 * ✅ Step 2: No hidden fields needed for SupplierPartnerTypeView
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only responsible for rendering supplier selection UI
 * - Dependency Inversion: Depends on SupplierDataProvider abstraction
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.1.0
 * @since      20250422
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/SupplierDataProvider.php');
require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Composites/HtmlLabelRow.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlSelect.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlOption.php');

use KsfBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;

/**
 * View for supplier partner type selection
 * 
 * Refactored to return HtmlLabelRow object instead of string.
 * Builds HtmlSelect with HtmlOption from SupplierDataProvider.
 * 
 * @since 2.1.0 Refactored to return HTML objects
 */
class SupplierPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $dataProvider;
    private $formData;
    
    /**
     * Constructor with dependency injection
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account
     * @param int|null $partnerId Existing partner ID if already set
     * @param SupplierDataProvider $dataProvider Data provider (injected dependency)
     */
    public function __construct(
        int $lineItemId,
        string $otherBankAccount,
        ?int $partnerId,
        SupplierDataProvider $dataProvider
    ) {
        $this->lineItemId = $lineItemId;
        $this->otherBankAccount = $otherBankAccount;
        $this->partnerId = $partnerId;
        $this->dataProvider = $dataProvider;
        $this->formData = new PartnerFormData($lineItemId);
    }
    
    /**
     * Get the HTML for this view
     * 
     * Builds supplier selection dropdown as HtmlLabelRow object.
     * 
     * @return HtmlLabelRow Composable HTML object
     * 
     * @since 2.1.0 Returns object instead of string
     */
    public function getHtml(): HtmlLabelRow
    {
        // If no partner ID is set, try to match by bank account
        $matched_supplier = [];
        if (empty($this->partnerId)) {
            $matched_supplier = \PartnerMatcher::searchByBankAccount(\PT_SUPPLIER, $this->otherBankAccount);
            
            if (\PartnerMatcher::hasMatch($matched_supplier)) {
                $this->partnerId = \PartnerMatcher::getPartnerId($matched_supplier);
                $this->formData->setPartnerId($this->partnerId);
            }
        }
        
        // Build supplier selection dropdown using HTML objects
        $select = $this->buildSupplierSelect($matched_supplier);
        
        // Create label
        $label = new HtmlString(_("Payment To:"));
        
        // Return composable object
        return new HtmlLabelRow($label, $select);
    }
    
    /**
     * Build supplier dropdown selector as HtmlSelect object
     * 
     * Uses SupplierDataProvider to get suppliers and builds HtmlSelect with HtmlOption children.
     * 
     * @param array $matchedSupplier Matched supplier data from PartnerMatcher
     * @return HtmlSelect Dropdown selector object
     * 
     * @since 2.1.0
     */
    private function buildSupplierSelect(array $matchedSupplier): HtmlSelect
    {
        // Create select element
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        // Add blank option
        $select->addOption(new HtmlOption('', _('Select Supplier')));
        
        // Get suppliers from data provider
        $suppliers = $this->dataProvider->getSuppliers();
        
        // Determine selected value: matched supplier or existing partnerId
        $selectedId = null;
        if (\PartnerMatcher::hasMatch($matchedSupplier)) {
            $selectedId = \PartnerMatcher::getPartnerId($matchedSupplier);
        } elseif ($this->partnerId) {
            $selectedId = $this->partnerId;
        }
        
        // Build options from provider data
        foreach ($suppliers as $supplier) {
            $option = new HtmlOption($supplier['supplier_id'], $supplier['supp_name']);
            
            // Mark selected if this is the current/matched supplier
            if ($selectedId && $selectedId == $supplier['supplier_id']) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     * 
     * @since 2.1.0 Calls toHtml() on returned object
     */
    public function display(): void
    {
        $this->getHtml()->toHtml();
    }
}
