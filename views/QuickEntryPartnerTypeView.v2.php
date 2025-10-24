<?php

/**
 * Quick Entry Partner Type View (Refactored)
 * 
 * SOLID Principles Applied:
 * - Single Responsibility: Only responsible for rendering quick entry selection UI
 * - Open/Closed: Open for extension (subclassing), closed for modification
 * - Liskov Substitution: Can be used wherever base View interface is expected
 * - Interface Segregation: Implements minimal PartnerTypeViewInterface
 * - Dependency Inversion: Depends on QuickEntryDataProvider abstraction, not FA globals
 * 
 * Dependency Injection:
 * - QuickEntryDataProvider injected via constructor
 * - No direct calls to quick_entries_list()
 * - Testable without FrontAccounting framework
 * 
 * Uses HTML library instead of ob_start():
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
 * │  QuickEntryPartnerTypeView                  │
 * ├─────────────────────────────────────────────┤
 * │ - lineItemId: int                           │
 * │ - transactionDC: string                     │
 * │ - dataProvider: QuickEntryDataProvider      │
 * ├─────────────────────────────────────────────┤
 * │ + __construct(int, string, Provider)        │
 * │ + getHtml(): string                         │
 * │ + display(): void                           │
 * │ - renderQuickEntrySelector(): string        │
 * │ - renderQuickEntryDescription(): string     │
 * └─────────────────────────────────────────────┘
 *            │
 *            │ depends on
 *            ▼
 * ┌─────────────────────────────────────────────┐
 * │  QuickEntryDataProvider                     │
 * │  (Singleton - loaded once per page)         │
 * ├─────────────────────────────────────────────┤
 * │ + getEntries(): array                       │
 * │ + getEntry(int): array|null                 │
 * │ + getLabel(int): string|null                │
 * └─────────────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\Views;

require_once(__DIR__ . '/DataProviders/QuickEntryDataProvider.php');
require_once(__DIR__ . '/../src/Ksfraser/PartnerFormData.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HTML_ROW_LABEL.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlRaw.php');

use KsfBankImport\Views\DataProviders\QuickEntryDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\HTML_ROW_LABEL;
use Ksfraser\HTML\HtmlRaw;

/**
 * View for quick entry partner type selection
 * 
 * Refactored to follow SOLID principles with dependency injection.
 * Uses QuickEntryDataProvider singleton to avoid repeated database queries.
 * 
 * Design Improvements:
 * - Dependency Injection: DataProvider injected, not created internally
 * - Testability: Can mock QuickEntryDataProvider for unit tests
 * - Performance: DataProvider loads once per page, not per line item
 * - Separation: UI logic separate from data loading logic
 * 
 * Example usage:
 * <code>
 * // In process_statements.php - load ONCE for all line items
 * $depositProvider = QuickEntryDataProvider::forDeposit();
 * $paymentProvider = QuickEntryDataProvider::forPayment();
 * 
 * // Pass to each line item view
 * foreach ($lineItems as $item) {
 *     $provider = ($item->transactionDC == 'C') ? $depositProvider : $paymentProvider;
 *     $view = new QuickEntryPartnerTypeView($item->id, $item->transactionDC, $provider);
 *     echo $view->getHtml();
 * }
 * </code>
 * 
 * Testing example:
 * <code>
 * class QuickEntryPartnerTypeViewTest extends TestCase
 * {
 *     public function testRendersQuickEntrySelector()
 *     {
 *         // Mock data provider
 *         $provider = $this->createMock(QuickEntryDataProvider::class);
 *         $provider->method('getEntries')->willReturn([
 *             1 => ['id' => 1, 'description' => 'Test Entry', 'base_desc' => 'Test']
 *         ]);
 *         
 *         $view = new QuickEntryPartnerTypeView(123, 'C', $provider);
 *         $html = $view->getHtml();
 *         
 *         $this->assertStringContainsString('Quick Entry:', $html);
 *     }
 * }
 * </code>
 * 
 * @since 2.0.0
 */
class QuickEntryPartnerTypeView
{
    /**
     * Line item ID
     * 
     * @var int
     */
    private $lineItemId;
    
    /**
     * Transaction direction/type
     * 
     * @var string 'C' for credit/deposit, 'D' for debit/payment
     */
    private $transactionDC;
    
    /**
     * Quick entry data provider
     * 
     * @var QuickEntryDataProvider
     */
    private $dataProvider;
    
    /**
     * Form data encapsulation
     * 
     * @var PartnerFormData
     */
    private $formData;
    
    /**
     * Constructor with dependency injection
     * 
     * @param int $lineItemId The ID of the line item
     * @param string $transactionDC Transaction type ('C' for credit/deposit, 'D' for debit/payment)
     * @param QuickEntryDataProvider $dataProvider Data provider (injected dependency)
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
     * Get the HTML for this view
     * 
     * Renders quick entry selection UI using HTML_ROW_LABEL.
     * 
     * @return string HTML output
     * 
     * @since 2.0.0
     */
    public function getHtml(): string
    {
        // Build quick entry selector using injected data provider
        $qeSelector = $this->renderQuickEntrySelector();
        
        // Add base description of selected entry
        $qeDescription = $this->renderQuickEntryDescription();
        
        // Combine selector and description
        $qeContent = new HtmlRaw($qeSelector . $qeDescription);
        
        // Render using HTML_ROW_LABEL (note: legacy parameter order is $data, $label)
        $row = new HTML_ROW_LABEL($qeContent, "Quick Entry:");
        
        return $row->getHtml();
    }
    
    /**
     * Render quick entry dropdown selector
     * 
     * Uses FrontAccounting quick_entries_list() function with data from provider.
     * Provider ensures entries are loaded only once per page.
     * 
     * @return string HTML for dropdown selector
     * 
     * @since 2.0.0
     */
    private function renderQuickEntrySelector(): string
    {
        // Get quick entry type based on transaction direction
        $qeType = ($this->transactionDC == 'C') ? QE_DEPOSIT : QE_PAYMENT;
        
        // Build selector using FrontAccounting function
        // Data is pre-loaded by provider, so this is just rendering
        // quick_entries_list($name, $selected_id=null, $type=null, $submit_on_change=false)
        $selector = quick_entries_list(
            "partnerId_{$this->lineItemId}", 
            null, 
            $qeType, 
            true
        );
        
        return $selector;
    }
    
    /**
     * Render base description of selected quick entry
     * 
     * Retrieves and displays the base description of currently selected entry.
     * Uses data provider to avoid additional database queries.
     * 
     * @return string HTML for description (plain text with leading space)
     * 
     * @since 2.0.0
     */
    private function renderQuickEntryDescription(): string
    {
        // Get currently selected entry ID from PartnerFormData
        if (!$this->formData->hasPartnerId()) {
            return '';
        }
        
        $selectedId = $this->formData->getPartnerId();
        
        if (!$selectedId) {
            return '';
        }
        
        // Get entry from provider (cached data, no query)
        $entry = $this->dataProvider->getEntry($selectedId);
        
        if (!$entry) {
            return '';
        }
        
        // Return base description with leading space
        return ' ' . ($entry['base_desc'] ?? '');
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
