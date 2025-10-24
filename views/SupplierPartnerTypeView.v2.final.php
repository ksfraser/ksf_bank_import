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
 * @version    2.0.0
 * @since      20250422
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/SupplierDataProvider.php');
require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HTML_ROW_LABEL.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlRaw.php');

use KsfBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlRaw;

/**
 * View for supplier partner type selection
 * 
 * Steps 0-2: Fully refactored with HTML library classes
 * 
 * @since 2.0.0
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
     * Steps 1-2: Uses HTML_ROW_LABEL instead of label_row()
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        $html = '';
        
        // If no partner ID is set, try to match by bank account
        $matched_supplier = [];
        if (empty($this->partnerId)) {
            $matched_supplier = \PartnerMatcher::searchByBankAccount(\PT_SUPPLIER, $this->otherBankAccount);
            
            if (\PartnerMatcher::hasMatch($matched_supplier)) {
                $this->partnerId = \PartnerMatcher::getPartnerId($matched_supplier);
                $this->formData->setPartnerId($this->partnerId);
            }
        }
        
        // Display supplier selection dropdown
        // supplier_list returns HTML, so we use HtmlRaw
        $supplierListHtml = \supplier_list("partnerId_{$this->lineItemId}", $matched_supplier, false, false);
        $supplierHtml = new HtmlRaw($supplierListHtml);
        
        // Step 1: Use HTML_ROW_LABEL instead of label_row()
        $labelRow = new HTML_ROW_LABEL($supplierHtml, _("Payment To:"));
        $html .= $labelRow->getHtml();
        
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
