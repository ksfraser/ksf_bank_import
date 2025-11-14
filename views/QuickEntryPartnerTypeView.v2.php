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
 * Uses HTML library for composable objects:
 * - HtmlSelect for dropdowns with HtmlOption children
 * - HtmlLabelRow for labeled form rows
 * - Returns objects for composition, not strings
 * 
 * @package    KsfBankImport\Views
 * @author     Kevin Fraser / ChatGPT
 * @copyright  2025 KSF
 * @license    MIT
 * @version    2.1.0
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
 * │ + getHtml(): HtmlLabelRow                   │
 * │ + display(): void                           │
 * │ - buildQuickEntrySelect(): HtmlSelect       │
 * │ - getQuickEntryDescription(): string        │
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
require_once(__DIR__ . '/../src/Ksfraser/HTML/Composites/HtmlLabelRow.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/HtmlFragment.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlSelect.php');
require_once(__DIR__ . '/../src/Ksfraser/HTML/Elements/HtmlOption.php');

use KsfBankImport\Views\DataProviders\QuickEntryDataProvider;
use Ksfraser\PartnerFormData;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;

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
 * @since 2.1.0 Refactored to return HtmlLabelRow object instead of string
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
     * Renders quick entry selection UI as HtmlLabelRow object.
     * 
     * @return HtmlLabelRow Composable HTML object
     * 
     * @since 2.1.0 Returns object instead of string
     */
    public function getHtml(): HtmlLabelRow
    {
        // Build quick entry selector using HTML objects
        $select = $this->buildQuickEntrySelect();
        
        // Add base description of selected entry as text (if any)
        $description = $this->getQuickEntryDescription();
        
        // Create content: select + description
        if ($description) {
            $fragment = new HtmlFragment();
            $fragment->addChild($select);
            $fragment->addChild(new HtmlString(' ' . $description));
            $content = $fragment;
        } else {
            $content = $select;
        }
        
        // Create label
        $label = new HtmlString(_("Quick Entry:"));
        
        // Return composable object
        return new HtmlLabelRow($label, $content);
    }
    
    /**
     * Build quick entry dropdown selector as HtmlSelect object
     * 
     * Uses QuickEntryDataProvider to get entries and builds HtmlSelect with HtmlOption children.
     * Provider ensures entries are loaded only once per page.
     * 
     * @return HtmlSelect Dropdown selector object
     * 
     * @since 2.1.0 Returns HtmlSelect object instead of string
     */
    private function buildQuickEntrySelect(): HtmlSelect
    {
        // Create select element
        $select = new HtmlSelect("partnerId_{$this->lineItemId}");
        $select->setClass('combo');
        $select->setAttribute('onchange', 'this.form.submit()');
        
        // Add blank option
        $select->addOption(new HtmlOption('', _('Select Quick Entry')));
        
        // Get entries from data provider
        $entries = $this->dataProvider->getEntries();
        
        // Build options from provider data
        foreach ($entries as $entry) {
            $option = new HtmlOption($entry['id'], $entry['description']);
            
            // Mark selected if this is the current partner
            if ($this->formData->hasPartnerId() && $this->formData->getPartnerId() == $entry['id']) {
                $option->setSelected(true);
            }
            
            $select->addOption($option);
        }
        
        return $select;
    }
    
    /**
     * Get base description of selected quick entry
     * 
     * Retrieves the base description of currently selected entry.
     * Uses data provider to avoid additional database queries.
     * 
     * @return string Description text (empty string if none)
     * 
     * @since 2.1.0 Returns plain string without HTML
     */
    private function getQuickEntryDescription(): string
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
        
        // Return base description
        return $entry['base_desc'] ?? '';
    }
    
    /**
     * Output HTML directly (for legacy compatibility)
     * 
     * @return void
     * 
     * @since 2.1.0 Calls toHtml() on returned object
     */
    public function display(): void
    {
        $this->getHtml()->toHtml();
    }
}
