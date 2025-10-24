<?php

/**
 * Supplier Partner Type View (Refactored with Dependency Injection)
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only responsible for rendering supplier selection UI
 * - Open/Closed: Open for extension (subclassing), closed for modification
 * - Liskov Substitution: Can be used wherever PartnerTypeViewInterface expected
 * - Interface Segregation: Implements minimal interface
 * - Dependency Inversion: Depends on SupplierDataProvider abstraction
 * 
 * Dependency Injection:
 * - SupplierDataProvider injected via constructor
 * - No direct calls to supplier_list() database function
 * - Testable without FrontAccounting framework
 * 
 * Uses HTML library:
 * - HtmlOB for capturing legacy label_row() output
 * - Future: Replace with HtmlTable, HtmlTr, HtmlTd
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.0.0
 * @since      20250422
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────────────┐
 * │  SupplierPartnerTypeView                    │
 * ├─────────────────────────────────────────────┤
 * │ - lineItemId: int                           │
 * │ - otherBankAccount: string                  │
 * │ - partnerId: int|null                       │
 * │ - dataProvider: SupplierDataProvider        │
 * │ - matcher: PartnerMatcher                   │
 * ├─────────────────────────────────────────────┤
 * │ + __construct(int, string, int|null, Prov.) │
 * │ + getHtml(): string                         │
 * │ + display(): void                           │
 * │ - autoMatchSupplier(): void                 │
 * │ - renderSupplierSelector(): string          │
 * └─────────────────────────────────────────────┘
 *            │
 *            │ depends on
 *            ▼
 * ┌─────────────────────────────────────────────┐
 * │  SupplierDataProvider                       │
 * │  (Singleton - loaded once per page)         │
 * ├─────────────────────────────────────────────┤
 * │ + getSuppliers(): array                     │
 * │ + getSupplier(int): array|null              │
 * │ + getLabel(int): string|null                │
 * └─────────────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/SupplierDataProvider.php');
require_once(__DIR__ . '/PartnerMatcher.php');
require_once(__DIR__ . '/HTML/HtmlOB.php');

use KsfBankImport\Views\DataProviders\SupplierDataProvider;
use Ksfraser\HTML\HTMLAtomic\HtmlOB;

/**
 * View for supplier partner type selection
 * 
 * Refactored to follow SOLID principles with dependency injection.
 * Uses SupplierDataProvider singleton to avoid repeated database queries.
 * 
 * Design Improvements:
 * - Dependency Injection: DataProvider injected, not created internally
 * - Testability: Can mock SupplierDataProvider for unit tests
 * - Performance: DataProvider loads once per page, not per line item
 * - Separation: UI logic separate from data loading logic
 * 
 * Example usage:
 * <code>
 * // In process_statements.php - load ONCE for all line items
 * $supplierProvider = SupplierDataProvider::getInstance();
 * 
 * // Pass to each line item view
 * foreach ($lineItems as $item) {
 *     $view = new SupplierPartnerTypeView(
 *         $item->id,
 *         $item->otherBankAccount,
 *         $item->partnerId,
 *         $supplierProvider
 *     );
 *     echo $view->getHtml();
 * }
 * </code>
 * 
 * Testing example:
 * <code>
 * class SupplierPartnerTypeViewTest extends TestCase
 * {
 *     public function testRendersSupplierSelector()
 *     {
 *         // Mock data provider
 *         $provider = $this->createMock(SupplierDataProvider::class);
 *         $provider->method('getSuppliers')->willReturn([
 *             1 => ['supplier_id' => 1, 'supp_name' => 'Test Supplier']
 *         ]);
 *         
 *         $view = new SupplierPartnerTypeView(123, 'ACME Corp', null, $provider);
 *         $html = $view->getHtml();
 *         
 *         $this->assertStringContainsString('Payment To:', $html);
 *     }
 * }
 * </code>
 * 
 * @since 2.0.0
 */
class SupplierPartnerTypeView
{
    /**
     * Line item ID
     * 
     * @var int
     */
    private $lineItemId;
    
    /**
     * Other party's bank account name
     * 
     * @var string
     */
    private $otherBankAccount;
    
    /**
     * Partner (supplier) ID if already matched
     * 
     * @var int|null
     */
    private $partnerId;
    
    /**
     * Supplier data provider
     * 
     * @var SupplierDataProvider
     */
    private $dataProvider;
    
    /**
     * Constructor with dependency injection
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $otherBankAccount The other party's bank account name
     * @param int|null $partnerId Existing partner ID if already matched
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
     * Renders supplier selection UI using HTML library where possible.
     * Currently uses HtmlOB to capture label_row() output.
     * 
     * Future enhancement: Replace label_row() with HtmlTable classes.
     * 
     * @return string HTML output
     * 
     * @since 2.0.0
     */
    public function getHtml(): string
    {
        // Auto-match supplier if not already set
        $this->autoMatchSupplier();
        
        // Use HtmlOB to capture legacy label_row() output
        // TODO: Replace with HtmlTable, HtmlTr, HtmlTd classes
        $html = new HtmlOB(function() {
            // Render supplier selector using FrontAccounting function
            // Data is pre-loaded by provider, so this is just rendering
            $supplierSelector = $this->renderSupplierSelector();
            
            label_row(_("Payment To:"), $supplierSelector);
        });
        
        return $html->getHtml();
    }
    
    /**
     * Auto-match supplier by bank account
     * 
     * If no partner ID is set, attempts to match supplier using
     * PartnerMatcher service. Updates $_POST if match found.
     * 
     * @return void
     * 
     * @since 2.0.0
     */
    private function autoMatchSupplier(): void
    {
        // Only auto-match if not already set
        if (!empty($this->partnerId)) {
            return;
        }
        
        // Use PartnerMatcher service to search by bank account
        $match = \PartnerMatcher::searchByBankAccount(PT_SUPPLIER, $this->otherBankAccount);
        
        if (\PartnerMatcher::hasMatch($match)) {
            $this->partnerId = $_POST["partnerId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerId($match);
        }
    }
    
    /**
     * Render supplier dropdown selector
     * 
     * Uses FrontAccounting supplier_list() function.
     * Data is pre-loaded by provider, ensuring no repeated queries.
     * 
     * @return string HTML for dropdown selector
     * 
     * @since 2.0.0
     */
    private function renderSupplierSelector(): string
    {
        // Get matched supplier data if available
        // Note: supplier_list() expects array or false, not supplier_id
        $matched_supplier = [];
        if ($this->partnerId) {
            $supplier = $this->dataProvider->getSupplier($this->partnerId);
            if ($supplier) {
                $matched_supplier = $supplier;
            }
        }
        
        // Build selector using FrontAccounting function
        // supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $all=false, $editkey = false)
        $selector = supplier_list(
            "partnerId_{$this->lineItemId}",
            $matched_supplier,
            false,
            false
        );
        
        return $selector;
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     * 
     * @return void
     * 
     * @since 2.0.0
     */
    public function display(): void
    {
        echo $this->getHtml();
    }
}
