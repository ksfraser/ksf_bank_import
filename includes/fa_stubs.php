<?php
/**
 * FrontAccounting Function Stubs for IDE Support
 * 
 * This file provides stub declarations for FrontAccounting core functions
 * to eliminate IDE lint errors during development. These functions are
 * defined in the actual FrontAccounting installation but not available
 * during standalone development/testing.
 * 
 * All stubs are wrapped in function_exists() checks so they won't
 * interfere with the real FrontAccounting functions in production.
 * 
 * @author Kevin Fraser
 * @since 20250119
 */

// =============================================================================
// Generic FA model fallback (for standalone tests)
// =============================================================================

if (!class_exists('generic_fa_interface_model')) {
    class generic_fa_interface_model
    {
        public function __construct(...$args) {}

        public function set($field, $value = null, $enforce = true)
        {
            $this->$field = $value;
            return true;
        }

        public function get($field)
        {
            return $this->$field ?? null;
        }
    }
}

if (!function_exists('shorten_bankAccount_Names')) {
    function shorten_bankAccount_Names($name)
    {
        return (string)$name;
    }
}

// =============================================================================
// Display Functions
// =============================================================================

if (!function_exists('display_notification')) {
    /**
     * Display a success/info notification message
     * @param string $msg Message to display
     * @param int $type Message type (0=success, 1=info)
     * @return void
     */
    function display_notification(string $msg, int $type = 0): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('display_error')) {
    /**
     * Display an error message
     * @param string $msg Error message to display
     * @return void
     */
    function display_error(string $msg): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('display_warning')) {
    /**
     * Display a warning message
     * @param string $msg Warning message to display
     * @return void
     */
    function display_warning(string $msg): void {
        // Stub - actual implementation in FrontAccounting
    }
}

// =============================================================================
// Table Functions
// =============================================================================

if (!function_exists('start_table')) {
    /**
     * Start an HTML table
     * @param string $class CSS class for table
     * @param mixed ...$args Additional arguments
     * @return void
     */
    function start_table($class = '', ...$args): void {
        // Minimal HTML output for standalone testing
        $classAttr = '';
        $classStr = (string)$class;
        if ($classStr !== '') {
            // FA often passes numeric TABLESTYLE constants like 2
            if (ctype_digit($classStr)) {
                $classAttr = 'tablestyle' . $classStr;
            } else {
                $classAttr = $classStr;
            }
        }

        $attrs = '';
        foreach ($args as $arg) {
            if (is_string($arg) && trim($arg) !== '') {
                $attrs .= ' ' . trim($arg);
            }
        }

        echo '<table';
        if ($classAttr !== '') {
            echo " class='" . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . "'";
        }
        if ($attrs !== '') {
            echo $attrs;
        }
        echo ">\n";
    }
}

if (!function_exists('end_table')) {
    /**
     * End an HTML table
     * @param int $breaks Number of line breaks after table
     * @return void
     */
    function end_table(int $breaks = 0): void {
        echo "</table>\n";
        if ($breaks > 0) {
            echo str_repeat("<br />\n", $breaks);
        }
    }
}

if (!function_exists('start_row')) {
    /**
     * Start a table row
     * @param string $class CSS class for row
     * @return void
     */
    function start_row(string $class = ''): void {
        if ($class !== '') {
            echo "<tr class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'>";
        } else {
            echo '<tr>';
        }
    }
}

if (!function_exists('end_row')) {
    /**
     * End a table row
     * @return void
     */
    function end_row(): void {
        echo "</tr>\n";
    }
}

if (!function_exists('table_header')) {
    /**
     * Display table header row
     * @param array $labels Header labels
     * @return void
     */
    function table_header(array $labels): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('label_row')) {
    /**
     * Display a label and value row
     * @param string $label Label text
     * @param mixed $value Value to display
     * @param string $params Additional parameters
     * @return void
     */
    function label_row(string $label, $value, string $params = ''): void {
        // Stub - output basic HTML structure
        echo "<tr><td class='label'>$label</td><td>$value</td></tr>";
    }
}

if (!function_exists('label_cell')) {
    /**
     * Display a label cell
     * @param string $label Label text
     * @param string $params Additional parameters
     * @return void
     */
    function label_cell(string $label, string $params = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('submit_cells')) {
    /**
     * Display submit button cells
     * @param string $name Button name
     * @param string $value Button value
     * @param string $params Additional parameters
     * @return void
     */
    function submit_cells(string $name, string $value, string $params = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('submit_center_first')) {
    /**
     * Display centered submit button
     * @param string $name Button name
     * @param string $value Button value
     * @param string $params Additional parameters
     * @return void
     */
    function submit_center_first(string $name, string $value, string $params = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('submit_center_last')) {
    /**
     * Display centered submit button (last column)
     * @param string $name Button name
     * @param string $value Button value
     * @param string $params Additional parameters
     * @return void
     */
    function submit_center_last(string $name, string $value, string $params = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

// =============================================================================
// Form Functions
// =============================================================================

if (!function_exists('hidden')) {
    /**
     * Create hidden form field
     * @param string $name Field name
     * @param mixed $value Field value
     * @return void
     */
    function hidden(string $name, $value): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('text_input')) {
    /**
     * Create text input field
     * @param string $name Field name
     * @param mixed $value Field value
     * @param int $size Input size
     * @param string $max Max length
     * @param string $title Input title/placeholder
     * @return string HTML input element
     */
    function text_input(string $name, $value = '', int $size = 0, string $max = '', string $title = ''): string {
        // Stub - returns empty input for development
        return "<input type='text' name='$name' value='$value' />";
    }
}

if (!function_exists('array_selector')) {
    /**
     * Create a select dropdown from array
     * @param string $name Field name
     * @param mixed $selected Selected value
     * @param array $items Items for dropdown
     * @param array $options Additional options
     * @return string HTML output
     */
    function array_selector(string $name, $selected, array $items, array $options = []): string {
        // Stub - actual implementation in FrontAccounting
        return '';
    }
}

if (!function_exists('bank_accounts_list_row')) {
    /**
     * Display bank accounts dropdown row
     * @param string $label Row label
     * @param string $name Field name
     * @param mixed $selected_id Selected account ID
     * @param bool $submit_on_change Whether to submit on change
     * @return void
     */
    function bank_accounts_list_row(string $label, string $name, $selected_id = null, bool $submit_on_change = false): void {
        // Stub - actual implementation in FrontAccounting
    }
}

// =============================================================================
// Div/Container Functions
// =============================================================================

if (!function_exists('div_start')) {
    /**
     * Start a div container
     * @param string $id Div ID
     * @param string $class Div class
     * @return void
     */
    function div_start(string $id = '', string $class = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('div_end')) {
    /**
     * End a div container
     * @return void
     */
    function div_end(): void {
        // Stub - actual implementation in FrontAccounting
    }
}

// =============================================================================
// Database Functions
// =============================================================================

if (!function_exists('db_insert_id')) {
    /**
     * Get last inserted database ID
     * @return int Last insert ID
     */
    function db_insert_id(): int {
        // Stub - actual implementation in FrontAccounting
        return 0;
    }
}

if (!function_exists('db_query')) {
    /**
     * Execute a database query
     * @param string $sql SQL query
     * @param string $err_msg Error message if query fails
     * @return mixed Query result
     */
    function db_query(string $sql, string $err_msg = ''): mixed {
        // Stub - actual implementation in FrontAccounting
        return null;
    }
}

if (!function_exists('db_fetch')) {
    /**
     * Fetch a row from query result
     * @param mixed $result Query result
     * @return array|false Row data or false
     */
    function db_fetch($result): array|false {
        // Stub - actual implementation in FrontAccounting
        return false;
    }
}

// =============================================================================
// Path Functions
// =============================================================================

if (!function_exists('company_path')) {
    /**
     * Get the current company's file path
     * @param int $id Company ID (optional)
     * @return string Company path
     */
    function company_path(int $id = 0): string {
        // Stub - returns a temp directory for development
        return sys_get_temp_dir() . '/fa_company';
    }
}

// =============================================================================
// Translation Functions
// =============================================================================

if (!function_exists('_')) {
    /**
     * Translate a string
     * @param string $text Text to translate
     * @return string Translated text
     */
    function _(string $text): string {
        // Stub - just returns the text as-is
        return $text;
    }
}

// =============================================================================
// Constants
// =============================================================================

if (!defined('TABLESTYLE')) {
    /**
     * Default table CSS class
     */
    define('TABLESTYLE', 'tablestyle');
}

if (!defined('TABLESTYLE2')) {
    /**
     * Alternative table CSS class
     */
    define('TABLESTYLE2', 'tablestyle2');
}

if (!defined('TB_PREF')) {
    /**
     * Table prefix for database tables
     */
    define('TB_PREF', '0_');
}

if (!defined('PT_SUPPLIER')) {
    /**
     * Partner type constant for suppliers
     */
    define('PT_SUPPLIER', 'supplier');
}

if (!defined('PT_CUSTOMER')) {
    /**
     * Partner type constant for customers
     */
    define('PT_CUSTOMER', 'customer');
}

if (!defined('ANY_NUMERIC')) {
    /**
     * Constant representing any numeric value
     */
    define('ANY_NUMERIC', -1);
}

// =============================================================================
// Custom Module Functions
// =============================================================================

if (!function_exists('getParsers')) {
    /**
     * Get available file format parsers
     * @return array Array of parser configurations
     */
    function getParsers(): array {
        // Stub - returns empty array for development
        // Real implementation in parsers.inc
        return [];
    }
}

if (!function_exists('search_partner_by_bank_account')) {
    /**
     * Search for a partner (customer/supplier) by bank account number
     * @param string $partner_type Partner type (PT_SUPPLIER or PT_CUSTOMER)
     * @param string $bank_account Bank account number to search
     * @return array|null Partner data or null if not found
     */
    function search_partner_by_bank_account(string $partner_type, string $bank_account): ?array {
        // Stub - returns null for development
        return null;
    }
}

// =============================================================================
// Customer/Supplier Functions
// =============================================================================

if (!function_exists('supplier_list')) {
    /**
     * Generate supplier dropdown list
     * @param string $name Field name
     * @param mixed $selected_id Selected supplier ID
     * @param bool $spec_option Whether to show special option
     * @param bool $submit_on_change Submit form on change
     * @return string HTML select element
     */
    function supplier_list(string $name, $selected_id = null, bool $spec_option = false, bool $submit_on_change = false): string {
        // Stub - returns empty select for development
        return "<select name='$name'></select>";
    }
}

if (!function_exists('customer_list')) {
    /**
     * Generate customer dropdown list
     * @param string $name Field name
     * @param mixed $selected_id Selected customer ID
     * @param bool $spec_option Whether to show special option
     * @param bool $submit_on_change Submit form on change
     * @return string HTML select element
     */
    function customer_list(string $name, $selected_id = null, bool $spec_option = false, bool $submit_on_change = false): string {
        // Stub - returns empty select for development
        return "<select name='$name'></select>";
    }
}

if (!function_exists('db_customer_has_branches')) {
    /**
     * Check if a customer has branches
     * @param mixed $customer_id Customer ID
     * @return bool True if customer has branches
     */
    function db_customer_has_branches($customer_id): bool {
        // Stub - returns false for development
        return false;
    }
}

if (!function_exists('customer_branches_list')) {
    /**
     * Generate customer branches dropdown list
     * @param mixed $customer_id Customer ID
     * @param string $name Field name
     * @param mixed $selected_id Selected branch ID
     * @param bool $spec_option Whether to show special option
     * @param bool $enabled Whether field is enabled
     * @param bool $submit_on_change Submit form on change
     * @return string HTML select element
     */
    function customer_branches_list($customer_id, string $name, $selected_id = null, bool $spec_option = false, bool $enabled = true, bool $submit_on_change = false): string {
        // Stub - returns empty select for development
        return "<select name='$name'></select>";
    }
}

if (!function_exists('get_customer_details_from_trans')) {
    /**
     * Get customer details from a transaction
     * @param int $trans_type Transaction type
     * @param int $trans_no Transaction number
     * @return array|null Customer details or null
     */
    function get_customer_details_from_trans(int $trans_type, int $trans_no): ?array {
        // Stub - returns null for development
        return null;
    }
}

// =============================================================================
// Session/Security Functions
// =============================================================================

if (!function_exists('check_csrf_token')) {
    /**
     * Check CSRF token for security
     * @return bool True if valid
     */
    function check_csrf_token(): bool {
        // Stub - always returns true for development
        return true;
    }
}

if (!function_exists('get_user')) {
    /**
     * Get current logged-in user ID
     * @return int User ID
     */
    function get_user(): int {
        // Stub - returns dummy user ID
        return 1;
    }
}

if (!function_exists('get_post')) {
    /**
     * Get POST variable safely
     * @param string $name Variable name
     * @param mixed $default Default value if not set
     * @return mixed POST value or default
     */
    function get_post(string $name, $default = null) {
        // Stub - checks $_POST
        return $_POST[$name] ?? $default;
    }
}

// =============================================================================
// Page/Form Functions
// =============================================================================

if (!function_exists('page')) {
    /**
     * Start a page with title
     * @param string $title Page title
     * @param bool $no_menu Whether to hide menu
     * @param bool $is_index Whether this is index page
     * @param string $onload JavaScript onload
     * @param string $js JavaScript to include
     * @return void
     */
    function page(string $title, bool $no_menu = false, bool $is_index = false, string $onload = '', string $js = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('end_page')) {
    /**
     * End a page
     * @param bool $no_menu Whether menu was hidden
     * @param bool $is_index Whether this is index page
     * @return void
     */
    function end_page(bool $no_menu = false, bool $is_index = false): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('start_form')) {
    /**
     * Start an HTML form
     * @param bool $multi Whether this is multipart form
     * @param string $dummy Unused parameter
     * @param mixed $action Form action
     * @param string $name Form name
     * @return void
     */
    function start_form(bool $multi = false, string $dummy = '', $action = '', string $name = ''): void {
        // Stub - actual implementation in FrontAccounting
    }
}

if (!function_exists('end_form')) {
    /**
     * End an HTML form
     * @param int $breaks Number of line breaks after form
     * @return void
     */
    function end_form(int $breaks = 0): void {
        // Stub - actual implementation in FrontAccounting
    }
}

// =============================================================================
// Transaction Type Constants
// =============================================================================

if (!defined('ST_JOURNAL')) {
    define('ST_JOURNAL', 0); // Journal Entry
}
if (!defined('ST_BANKPAYMENT')) {
    define('ST_BANKPAYMENT', 1); // Bank Payment
}
if (!defined('ST_BANKDEPOSIT')) {
    define('ST_BANKDEPOSIT', 2); // Bank Deposit
}
if (!defined('ST_BANKTRANSFER')) {
    define('ST_BANKTRANSFER', 4); // Bank Transfer
}
if (!defined('ST_SALESINVOICE')) {
    define('ST_SALESINVOICE', 10); // Sales Invoice
}
if (!defined('ST_CUSTCREDIT')) {
    define('ST_CUSTCREDIT', 11); // Customer Credit Note
}
if (!defined('ST_CUSTPAYMENT')) {
    define('ST_CUSTPAYMENT', 12); // Customer Payment
}
if (!defined('ST_CUSTDELIVERY')) {
    define('ST_CUSTDELIVERY', 13); // Customer Delivery
}
if (!defined('ST_LOCTRANSFER')) {
    define('ST_LOCTRANSFER', 16); // Location Transfer
}
if (!defined('ST_INVADJUST')) {
    define('ST_INVADJUST', 17); // Inventory Adjustment
}
if (!defined('ST_PURCHORDER')) {
    define('ST_PURCHORDER', 18); // Purchase Order
}
if (!defined('ST_SUPPINVOICE')) {
    define('ST_SUPPINVOICE', 20); // Supplier Invoice
}
if (!defined('ST_SUPPCREDIT')) {
    define('ST_SUPPCREDIT', 21); // Supplier Credit Note
}
if (!defined('ST_SUPPAYMENT')) {
    define('ST_SUPPAYMENT', 22); // Supplier Payment
}
if (!defined('ST_SUPPRECEIVE')) {
    define('ST_SUPPRECEIVE', 25); // Supplier Receive
}

// =============================================================================
// Quick Entry Constants
// =============================================================================

if (!defined('QE_DEPOSIT')) {
    define('QE_DEPOSIT', 1); // Quick Entry Deposit
}
if (!defined('QE_PAYMENT')) {
    define('QE_PAYMENT', 2); // Quick Entry Payment
}
if (!defined('QE_JOURNAL')) {
    define('QE_JOURNAL', 3); // Quick Entry Journal
}

// =============================================================================
// Bank & Quick Entry Functions
// =============================================================================

if (!function_exists('bank_accounts_list')) {
    /**
     * Display bank account dropdown list
     * @param string $name Input name
     * @param mixed $selected_id Selected account ID
     * @param mixed $submit_on_change Whether to submit on change
     * @param bool $spec_option Show special option
     * @return string HTML select element
     */
    function bank_accounts_list(string $name, $selected_id = null, $submit_on_change = false, bool $spec_option = false): string {
        return "<select name='$name'></select>";
    }
}

if (!function_exists('quick_entries_list')) {
    /**
     * Display quick entries dropdown list
     * @param string $name Input name
     * @param mixed $selected_id Selected entry ID
     * @param int $type Quick entry type (QE_DEPOSIT, QE_PAYMENT, QE_JOURNAL)
     * @param bool $submit_on_change Whether to submit on change
     * @return string HTML select element
     */
    function quick_entries_list(string $name, $selected_id = null, int $type = 0, bool $submit_on_change = false): string {
        return "<select name='$name'></select>";
    }
}

if (!function_exists('get_quick_entry')) {
    /**
     * Get quick entry details
     * @param int $id Quick entry ID
     * @return array Quick entry details with keys: id, type, description, base_amount, base_desc
     */
    function get_quick_entry(int $id): array {
        return [
            'id' => $id,
            'type' => 0,
            'description' => '',
            'base_amount' => 0,
            'base_desc' => ''
        ];
    }
}

if (!function_exists('submit')) {
    /**
     * Create a submit button
     * @param string $name Button name
     * @param string $value Button value/label
     * @param bool $echo Whether to echo or return
     * @param string $title Button title attribute
     * @param string $atype Button style type
     * @return string HTML button element
     */
    function submit(string $name, string $value, bool $echo = true, string $title = '', string $atype = ''): string {
        $html = "<input type='submit' name='$name' value='$value' title='$title' />";
        if ($echo) {
            echo $html;
            return '';
        }
        return $html;
    }
}

if (!function_exists('get_customer_trans')) {
    /**
     * Get customer transaction details
     * @param int $trans_no Transaction number
     * @param int $type Transaction type
     * @return array Transaction details with keys: trans_no, type, debtor_no, branch_code, etc.
     */
    function get_customer_trans(int $trans_no, int $type): array {
        return [
            'trans_no' => $trans_no,
            'type' => $type,
            'debtor_no' => 0,
            'branch_code' => 0
        ];
    }
}

if (!function_exists('get_customer_name')) {
    /**
     * Get customer name by ID
     * @param int $customer_id Customer ID
     * @return string Customer name
     */
    function get_customer_name(int $customer_id): string {
        return "Customer #$customer_id";
    }
}

if (!function_exists('get_branch_name')) {
    /**
     * Get branch name by code
     * @param int $branch_code Branch code
     * @return string Branch name
     */
    function get_branch_name(int $branch_code): string {
        return "Branch #$branch_code";
    }
}

if (!function_exists('add_days')) {
    /**
     * Add days to a date
     * @param string $date Date in YYYY-MM-DD or other format
     * @param int $days Number of days to add (can be negative)
     * @return string Date with days added
     */
    function add_days(string $date, int $days): string {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }
        return date('Y-m-d', strtotime("+$days days", $timestamp));
    }
}

// =============================================================================
// Development Note
// =============================================================================

/*
 * IMPORTANT: This file should NEVER be included in production!
 * 
 * These stubs are for IDE support only. In production, FrontAccounting
 * provides all these functions. The function_exists() checks ensure
 * these stubs won't override the real functions.
 * 
 * Usage in development:
 * - Include at the top of files for IDE autocomplete
 * - All functions use function_exists() guards
 * - No-op implementations to prevent runtime errors
 * 
 * Do NOT commit files that require() this stub file!
 * Use it only for IDE configuration or testing scaffolds.
 */
