<?php

/**
 * Supplier Partner Type View (Refactored with Dependency Injection - Step-by-step TDD approach)
 * 
 * REFACTORING PLAN (one step at a time with tests):
 * 🔄 Step 0: Start with original working code + DI (CURRENT)
 * □  Step 1: Replace label_row() with HTML_ROW_LABEL
 * □  Step 2: Replace hidden() FA calls with HtmlHidden
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

use KsfBankImport\Views\DataProviders\SupplierDataProvider;

/**
 * View for supplier partner type selection
 * 
 * Step 0: Original implementation with dependency injection added.
 * This ensures we maintain functionality while adding testability.
 * 
 * @since 2.0.0
 */
class SupplierPartnerTypeView
{
    private $lineItemId;
    private $otherBankAccount;
    private $partnerId;
    private $dataProvider;
    
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
    }
    
    /**
     * Get the HTML for this view
     * 
     * Step 0: Uses original implementation with ob_start/ob_get_clean
     * 
     * @return string HTML output
     */
    public function getHtml(): string
    {
        ob_start();
        
        // If no partner ID is set, try to match by bank account
        $matched_supplier = [];
        if (empty($this->partnerId)) {
            $matched_supplier = \PartnerMatcher::searchByBankAccount(\PT_SUPPLIER, $this->otherBankAccount);
            
            if (\PartnerMatcher::hasMatch($matched_supplier)) {
                $this->partnerId = $_POST["partnerId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerId($matched_supplier);
            }
        }
        
        // Display supplier selection dropdown
        // supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
        \label_row(\_("Payment To:"), \supplier_list("partnerId_{$this->lineItemId}", $matched_supplier, false, false));
        
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
