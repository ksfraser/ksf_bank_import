<?php

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
 * @package KsfBankImport\Views
 * @author Kevin Fraser / GitHub Copilot
 * @since 2025-10-25
 * @see Martin Fowler's "Refactoring: Improving the Design of Existing Code"
 */

require_once( __DIR__ . '/ViewFactory.php' );
use KsfBankImport\Views\ViewFactory;

// Legacy Views (V1) - kept for backward compatibility
require_once( __DIR__ . '/SupplierPartnerTypeView.php' );
require_once( __DIR__ . '/CustomerPartnerTypeView.php' );
require_once( __DIR__ . '/BankTransferPartnerTypeView.php' );
require_once( __DIR__ . '/QuickEntryPartnerTypeView.php' );

// Define USE_V2_PARTNER_VIEWS if not already defined
if (!defined('USE_V2_PARTNER_VIEWS')) {
    define('USE_V2_PARTNER_VIEWS', true);
}

class PartnerTypeDisplayStrategy
{
    /**
     * @var array The data array containing all necessary fields
     */
    private $data;
    
    /**
     * @var array Strategy map: partner type code => display method name
     */
    private $strategies = [
        'SP' => 'displaySupplier',
        'CU' => 'displayCustomer',
        'BT' => 'displayBankTransfer',
        'QE' => 'displayQuickEntry',
        'MA' => 'displayMatched',
        'ZZ' => 'displayMatchedExisting'
    ];
    
    /**
     * Constructor
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
    }
    
    /**
     * Display the appropriate partner type view based on partner type code
     * 
     * This replaces the switch statement with a table-driven approach.
     * Uses the Strategy pattern to delegate to the appropriate display method.
     * 
     * @param string $partnerType The partner type code to display
     * @return void
     * @throws Exception If partner type is unknown
     */
    public function display(string $partnerType): void
    {
        // Validate partner type exists in strategy map
        if (!isset($this->strategies[$partnerType])) {
            throw new Exception("Unknown partner type: $partnerType");
        }
        
        // Get the strategy method name
        $method = $this->strategies[$partnerType];
        
        // Execute the strategy
        $this->$method();
    }
    
    /**
     * Display Supplier partner type (SP)
     * 
     * Uses ViewFactory with dependency injection (V2) or direct instantiation (V1)
     * 
     * @return void
     */
    private function displaySupplier(): void
    {
        if (USE_V2_PARTNER_VIEWS) {
            // V2: Use ViewFactory with dependency injection
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_SUPPLIER,
                $this->data['id'],
                [
                    'otherBankAccount' => $this->data['otherBankAccount'] ?? '',
                    'partnerId' => $this->data['partnerId'] ?? null
                ]
            );
        } else {
            // V1: Direct instantiation (legacy)
            $view = new SupplierPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['partnerId'] ?? null
            );
        }
        $view->display();
    }
    
    /**
     * Display Customer partner type (CU)
     * 
     * @return void
     */
    private function displayCustomer(): void
    {
        if (USE_V2_PARTNER_VIEWS) {
            // V2: Use ViewFactory with dependency injection
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
        } else {
            // V1: Direct instantiation (legacy)
            $view = new CustomerPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['valueTimestamp'] ?? '',
                $this->data['partnerId'] ?? null,
                $this->data['partnerDetailId'] ?? null
            );
        }
        $view->display();
    }
    
    /**
     * Display Bank Transfer partner type (BT)
     * 
     * @return void
     */
    private function displayBankTransfer(): void
    {
        if (USE_V2_PARTNER_VIEWS) {
            // V2: Use ViewFactory with dependency injection
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
        } else {
            // V1: Direct instantiation (legacy)
            $view = new BankTransferPartnerTypeView(
                $this->data['id'],
                $this->data['otherBankAccount'] ?? '',
                $this->data['transactionDC'] ?? '',
                $this->data['partnerId'] ?? null,
                $this->data['partnerDetailId'] ?? null
            );
        }
        $view->display();
    }
    
    /**
     * Display Quick Entry partner type (QE)
     * 
     * @return void
     */
    private function displayQuickEntry(): void
    {
        if (USE_V2_PARTNER_VIEWS) {
            // V2: Use ViewFactory with dependency injection
            $view = ViewFactory::createPartnerTypeView(
                ViewFactory::PARTNER_TYPE_QUICK_ENTRY,
                $this->data['id'],
                [
                    'transactionDC' => $this->data['transactionDC'] ?? ''
                ]
            );
        } else {
            // V1: Direct instantiation (legacy)
            $view = new QuickEntryPartnerTypeView(
                $this->data['id'],
                $this->data['transactionDC'] ?? ''
            );
        }
        $view->display();
    }
    
    /**
     * Display Matched partner type (MA)
     * 
     * For manually matched transactions
     * 
     * @return void
     */
    private function displayMatched(): void
    {
        $id = $this->data['id'];
        
        // Use function_exists to handle test environments
        if (function_exists('hidden')) {
            hidden("partnerId_$id", 'manual');
        }
        
        // Display transaction type selector
        $opts_arr = array(
            ST_JOURNAL => "Journal Entry",
            ST_BANKPAYMENT => "Bank Payment",
            ST_BANKDEPOSIT => "Bank Deposit",
            ST_BANKTRANSFER => "Bank Transfer",
            ST_CUSTCREDIT => "Customer Credit",
            ST_CUSTPAYMENT => "Customer Payment",
            ST_SUPPCREDIT => "Supplier Credit",
            ST_SUPPAYMENT => "Supplier Payment",
        );
        
        if (function_exists('label_row') && function_exists('array_selector')) {
            $name = "Existing_Type";
            label_row(_("Existing Entry Type:"), array_selector($name, 0, $opts_arr));
            
            label_row(
                _("Existing Entry:"),
                text_input("Existing_Entry", 0, 6, '', _("Existing Entry:"))
            );
        }
    }
    
    /**
     * Display Matched Existing transaction partner type (ZZ)
     * 
     * Handles the special case where a transaction was automatically
     * matched to an existing GL entry.
     * 
     * @return void
     */
    private function displayMatchedExisting(): void
    {
        // Handle matched existing item with special logic
        $matchingTrans = $this->data['matching_trans'] ?? [];
        
        if (isset($matchingTrans[0]) && function_exists('hidden')) {
            $matchTrans = $matchingTrans[0];
            $id = $this->data['id'];
            $memo = $this->data['memo'] ?? '';
            $title = $this->data['transactionTitle'] ?? '';
            
            hidden("partnerId_$id", $matchTrans['type']);
            hidden("partnerDetailId_$id", $matchTrans['type_no']);
            hidden("trans_no_$id", $matchTrans['type_no']);
            hidden("trans_type_$id", $matchTrans['type']);
            hidden("memo_$id", $memo);
            hidden("title_$id", $title);
        }
    }
    
    /**
     * Get available partner type codes
     * 
     * Useful for validation and documentation.
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
