<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlHidden;
use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;
use Ksfraser\FaBankImport\OperationTypes\OperationTypesRegistry;

/**
 * Partner Type Display Strategy
 * 
 * Replaces switch statement in displayPartnerType() with Strategy pattern.
 * Follows Martin Fowler's "Replace Conditional with Polymorphism" refactoring.
 * 
 * This class encapsulates the logic for selecting and displaying the appropriate
 * partner type view based on the partner type code. Instead of a procedural
 * switch statement, we use a lookup table (associative array) to map partner
 * type codes to their corresponding display methods.
 * 
 * REFACTORED (TDD): Strategy now contains the display logic directly instead of
 * calling back to bi_lineitem. This eliminates circular dependency and makes
 * Strategy truly standalone and testable.
 * 
 * Benefits:
 * - Open/Closed Principle: Easy to add new partner types without modifying existing code
 * - Single Responsibility: Each partner type display is in its own method
 * - Testability: Can test strategy independently with mock data
 * - Maintainability: Clear mapping of codes to behaviors
 * - No circular dependencies: Strategy uses ViewFactory directly
 * 
 * @package Ksfraser\FaBankImport\Views
 * @author Kevin Fraser / GitHub Copilot
 * @since 2025-10-25
 * @see Martin Fowler's "Refactoring: Improving the Design of Existing Code"
 */
class PartnerTypeDisplayStrategy
{
    /**
     * @var array The data array containing all necessary fields
     */
    private $data;
    
    /**
     * @var array Strategy map: partner type code => display method name
     * Dynamically loaded from PartnerTypeRegistry
     */
    private $strategies = [];
    
    /**
     * Constructor
     * 
     * Dynamically builds strategy map from PartnerTypeRegistry instead of
     * hardcoding partner type codes. This follows Open/Closed Principle -
     * adding new partner types doesn't require modifying this class.
     * 
     * @param array $data Associative array containing:
     *   - id: int (required) - Line item ID
     *   - otherBankAccount: string - Other bank account name
     *   - valueTimestamp: string - Transaction date
     *   - transactionDC: string - Debit/Credit indicator
     *   - partnerId: int - Partner ID
     *   - partnerDetailId: int - Partner detail ID (for customers)
     *   - memo: string - Transaction memo
     *   - transactionTitle: string - Transaction title
     *   - matching_trans: array - Array of matching GL transactions
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->buildStrategyMap();
    }
    
    /**
     * Build strategy map dynamically from PartnerTypeRegistry
     * 
     * Instead of hardcoding the strategies array, we discover partner types
     * at runtime using the PartnerTypeRegistry. Each partner type provides
     * its own display method name via getStrategyMethodName().
     * 
     * @return void
     */
    private function buildStrategyMap(): void
    {
        $registry = PartnerTypeRegistry::getInstance();
        $partnerTypes = $registry->getAll();
        
        foreach ($partnerTypes as $code => $partnerType) {
            $methodName = $partnerType->getStrategyMethodName();
            $this->strategies[$code] = $methodName;
        }
    }
    
    /**
     * Display the appropriate partner type view based on partner type code
     * 
     * DEPRECATED: Use render() instead. This method directly echoes output,
     * violating separation of concerns. Kept for backward compatibility.
     * 
     * @param string $partnerType The partner type code to display
     * @return void
     * @throws \Exception If partner type is unknown
     * @deprecated Use render() instead
     */
    public function display(string $partnerType): void
    {
        $html = $this->render($partnerType);
        $html->toHtml();
    }
    
    /**
     * Render the appropriate partner type view based on partner type code
     * 
     * Returns HtmlFragment containing the rendered HTML. Caller decides
     * whether to echo immediately (toHtml()) or compose into larger structure.
     * 
     * @param string $partnerType The partner type code to render
     * @return HtmlFragment Rendered HTML elements in fragment
     * @throws \Exception If partner type is unknown
     */
    public function render(string $partnerType): HtmlFragment
    {
        if (!isset($this->strategies[$partnerType])) {
            throw new \Exception("Unknown partner type: $partnerType");
        }
        
        $method = $this->strategies[$partnerType];
        
        return $this->$method();
    }
    
    /**
     * Display Supplier partner type (SP)
     * 
     * @return HtmlFragment Rendered HTML
     */
    private function displaySupplier(): HtmlFragment
    {
        if (USE_V2_PARTNER_VIEWS) {
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_SUPPLIER,
                $this->data['id'],
                [
                    'otherBankAccount' => $this->data['otherBankAccount'] ?? '',
                    'partnerId' => $this->data['partnerId'] ?? null
                ]
            );
            
            $labelRow = $view->getHtml();
            $fragment = new HtmlFragment();
            $fragment->addChild($labelRow);
            return $fragment;
        } else {
            $view = new SupplierPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['partnerId'] ?? null
            );
            $view->display();
            
            return new HtmlFragment();
        }
    }
    
    /**
     * Display Customer partner type (CU)
     * 
     * @return HtmlFragment Rendered HTML
     */
    private function displayCustomer(): HtmlFragment
    {
        if (USE_V2_PARTNER_VIEWS) {
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_CUSTOMER,
                $this->data['id'],
                [
                    'otherBankAccount' => $this->data['otherBankAccount'] ?? '',
                    'valueTimestamp' => $this->data['valueTimestamp'] ?? '',
                    'partnerId' => $this->data['partnerId'] ?? null,
                    'partnerDetailId' => $this->data['partnerDetailId'] ?? null
                ]
            );
            
            return $view->getHtml();
        } else {
            $view = new CustomerPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['valueTimestamp'] ?? '',
                $this->data['partnerId'] ?? null,
                $this->data['partnerDetailId'] ?? null
            );
            $view->display();
            
            return new HtmlFragment();
        }
    }
    
    /**
     * Display Bank Transfer partner type (BT)
     * 
     * @return HtmlFragment Rendered HTML
     */
    private function displayBankTransfer(): HtmlFragment
    {
        if (USE_V2_PARTNER_VIEWS) {
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_BANK_TRANSFER,
                $this->data['id'],
                [
                    'otherBankAccount' => $this->data['otherBankAccount'] ?? '',
                    'transactionDC' => $this->data['transactionDC'] ?? '',
                    'partnerId' => $this->data['partnerId'] ?? null,
                    'partnerDetailId' => $this->data['partnerDetailId'] ?? null
                ]
            );
            
            return $view->getHtml();
        } else {
            $view = new BankTransferPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['transactionDC'] ?? '',
                $this->data['partnerId'] ?? null,
                $this->data['partnerDetailId'] ?? null
            );
            $view->display();
            
            return new HtmlFragment();
        }
    }
    
    /**
     * Display Quick Entry partner type (QE)
     * 
     * @return HtmlFragment Rendered HTML
     */
    private function displayQuickEntry(): HtmlFragment
    {
        if (USE_V2_PARTNER_VIEWS) {
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_QUICK_ENTRY,
                $this->data['id'],
                [
                    'transactionDC' => $this->data['transactionDC'] ?? ''
                ]
            );
            
            $labelRow = $view->getHtml();
            $fragment = new HtmlFragment();
            $fragment->addChild($labelRow);
            return $fragment;
        } else {
            $view = new QuickEntryPartnerTypeView(
                $this->data['id'],
                $this->data['transactionDC'] ?? ''
            );
            $view->display();
            
            return new HtmlFragment();
        }
    }
    
    /**
     * Display Manual Settlement partner type (MA)
     * 
     * @return HtmlFragment Rendered HTML elements
     */
    private function displayManualSettlement(): HtmlFragment
    {
        $id = $this->data['id'];
        $fragment = new HtmlFragment();
        
        $fragment->addChild(new HtmlHidden("partnerId_$id", 'manual'));
        
        $registry = OperationTypesRegistry::getInstance();
        $opts_arr = $registry->getTypes();
        
        if (!empty($opts_arr)) {
            $selectName = "Existing_Type";
            $select = new HtmlSelect($selectName);
            $select->addOptionsFromArray($opts_arr, '0');
            
            $typeLabel = new HtmlString(_("Existing Entry Type:"));
            $typeRow = new HtmlLabelRow($typeLabel, $select);
            $fragment->addChild($typeRow);
            
            $input = new HtmlInput('text');
            $input->setName("Existing_Entry")
                  ->setValue('0')
                  ->addAttribute(new \Ksfraser\HTML\HtmlAttribute('size', '6'));
            
            $entryLabel = new HtmlString(_("Existing Entry:"));
            $entryRow = new HtmlLabelRow($entryLabel, $input);
            $fragment->addChild($entryRow);
        }
        
        return $fragment;
    }
    
    /**
     * Display Matched Existing transaction partner type (ZZ)
     * 
     * @return HtmlFragment Rendered HTML elements (hidden fields)
     */
    private function displayMatchedExisting(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        $matchingTrans = $this->data['matching_trans'] ?? [];
        
        if (isset($matchingTrans[0])) {
            $matchTrans = $matchingTrans[0];
            $id = $this->data['id'];
            $memo = $this->data['memo'] ?? '';
            $title = $this->data['transactionTitle'] ?? '';
            
            $fragment
                ->addChild(new HtmlHidden("partnerId_$id", (string)$matchTrans['type']))
                ->addChild(new HtmlHidden("partnerDetailId_$id", (string)$matchTrans['type_no']))
                ->addChild(new HtmlHidden("trans_no_$id", (string)$matchTrans['type_no']))
                ->addChild(new HtmlHidden("trans_type_$id", (string)$matchTrans['type']))
                ->addChild(new HtmlHidden("memo_$id", $memo))
                ->addChild(new HtmlHidden("title_$id", $title));
        }
        
        return $fragment;
    }
    
    /**
     * Get available partner type codes
     * 
     * @return array List of valid partner type codes
     */
    public function getAvailablePartnerTypes(): array
    {
        return array_keys($this->strategies);
    }
    
    /**
     * Check if a partner type code is valid
     * 
     * @param string $partnerType Partner type code to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidPartnerType(string $partnerType): bool
    {
        return isset($this->strategies[$partnerType]);
    }
}