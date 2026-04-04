# FAMock Library Exploration Summary

## 1. FAMock Library Directory Structure

### Location
- **Primary**: `c:\Users\prote\Documents\software-devel\FAMock/` (local development repo)
- **Vendor**: `ksf_bank_import\vendor\ksfraser\famock\php\` (installed via Composer)

### Directory Contents

#### FAMock/php/ (Local Development)
```
FAMock/
├── FaDbStubs.php              # Database function stubs
├── FaUpdateOnlyStubs.php      # Company preference update stubs
└── MockDatabase.php           # DatabaseInterface implementation for testing
```

#### vendor/ksfraser/famock/php/ (Installed Library - More Complete)
```
famock/php/
├── FAMock.php                 # MAIN ENTRY POINT - loads all stubs
├── FaConstantStubs.php        # FA constants
├── FaDbStubs.php              # Database functions
├── FaDateStubs.php            # Date/time functions
├── FaBusinessStubs.php        # Business logic (suppliers, customers)
├── FaHookStubs.php            # Hook/filter system
├── FaSecurityStubs.php        # Security/access control
├── FaSessionStubs.php         # Session management
├── FaUIStubs.php              # UI functions (forms, tables, buttons)
├── FaUpdateOnlyStubs.php      # Company preference updates
├── MockDatabase.php           # DatabaseInterface mock
└── FAMockTest.php             # Test file
```

---

## 2. Available Test Helpers & Mocks

### Database Functions (FaDbStubs.php)
- **`db_escape(string $value): string`** - Stubs addslashes()
- **`db_query(string $sql)`** - Handles INSERT, UPDATE, DELETE, SELECT queries with in-memory storage
- **`db_fetch($result)`** - Fetch next row from result set

#### Behavior:
- Stores data in global `$GLOBALS['__fa_table']` array
- Tracks `__fa_last_sql`, `__fa_result_set`, `__fa_result_pos` for cursor management
- Supports basic WHERE, LIKE, and result set iteration

### Company Preferences (FaUpdateOnlyStubs.php)
- **`get_company_prefs(): array`** - Returns global company preferences
- **`update_company_prefs(array $prefs): void`** - Updates preferences

#### Storage:
- Store in global `$GLOBALS['__fa_prefs']`

### Date Functions (FaDateStubs.php)
- **`begin_month(string $date): string`** - Returns first day of month
- **`end_month(string $date): string`** - Returns last day of month
- **`Today(): string`** - Returns current date (Y-m-d)
- **`new_doc_date(): string`** - Returns current date for document
- **`sql2date(string $date): string`** - Converts SQL date format (pass-through in stub)
- **`add_days(string $date, int $days): string`** - Adds days to date
- **`is_new_reference(string $reference, int $transType): bool`** - Always returns true (mock)

### Business Functions (FaBusinessStubs.php)
- **`get_supplier_details_all()`** - Returns mock suppliers as ArrayIterator
- **`get_customer_details_all()`** - Returns mock customers as ArrayIterator

### Security Functions (FaSecurityStubs.php)
- **`user_check_access(string $page)`** - Mock access check
- **`add_access_extensions(array $extensions)`** - Stub for extensions
- **`has_access(string $page): bool`** - Always returns true (mock)
- **`get_user(): array`** - Returns mock user info

### UI Functions (FaUIStubs.php) - 20+ Functions
Form/Table Building:
- `start_form()`, `end_form()`
- `start_table()`, `end_table()`, `table_section_title()`, `table_header()`
- `start_row()`, `end_row()`

Cell Content:
- `label_cell()`, `text_row()`, `small_amount_row()`, `check_row()`

Buttons/Controls:
- `hidden()`, `submit()`, `submit_center()`
- `edit_button_cell()`, `delete_button_cell()`

Messages:
- `display_notification()`, `display_error()`, `_()`

#### Behavior:
- All return empty strings or void (no-op implementations for testing)

### Hook/Filter Functions (FaHookStubs.php)
- **`fa_hooks(): array`** - Returns empty hook array (mock)
- **`add_filter(string $hook, callable $callback)`** - Stub (no-op)
- **`apply_filters(string $hook, mixed $value)`** - Returns value unchanged

### Session Functions (FaSessionStubs.php)
- Session-related stubs for testing isolation

### Constants (FaConstantStubs.php)
- Various FA constants for table prefixes, transaction types, etc.

---

## 3. Bootstrap Setup (tests/bootstrap.php)

### Current Setup
```php
<?php
// Start output buffering to suppress HTML
if (!ob_get_level()) {
    ob_start();
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load FAMock (conditionally, with output suppression)
$famock_path = __DIR__ . '/../vendor/ksfraser/famock/php/FAMock.php';
if (file_exists($famock_path)) {
    ob_start();
    require_once $famock_path;
    ob_end_clean(); // Suppress any HTML output from FAMock
}

// Load project-specific FA function stubs/helpers
require_once __DIR__ . '/helpers/fa_functions.php';

// Configure test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Define constants
define('FA_ROOT', dirname(__DIR__));
if (!defined('TB_PREF')) {
    define('TB_PREF', '0_'); // FrontAccounting table prefix
}

// Pre-load test base class (if needed)
if (class_exists('Tests\Integration\DatabaseTestCase') === false) {
    require_once __DIR__ . '/integration/DatabaseTestCase.php';
}
```

### Key Points:
- Uses output buffering to prevent HTML output during FAMock loading
- Loads both vendor FAMock and project helpers
- Defines `FA_ROOT` and `TB_PREF` constants for DB tests
- Pre-loads DatabaseTestCase (integration testing base)

---

## 4. Database Mocking Classes

### TestDbResult (tests/helpers/fa_functions.php)
Lightweight in-memory result set wrapper:
```php
class TestDbResult {
    private array $rows;
    private int $index = 0;

    public function __construct(array $rows)
    public function fetch(): array|false
    public function rowCount(): int
}
```

**Use Case**: Simulating query results for repository/integration tests

### MockDatabase (vendor/ksfraser/famock/php/MockDatabase.php)
Implements `Ksfraser\Frontaccounting\GenCat\DatabaseInterface`:
```php
class MockDatabase implements DatabaseInterface {
    private string $tablePrefix;
    private array $rows;

    public function __construct(array $rows = [], string $tablePrefix = '0_')
    public function query($query, $error_message = 'Database query failed') // Returns ArrayIterator
    public function fetch($result) // Fetches from Iterator
    public function getTablePrefix() // Returns table prefix
}
```

**Use Case**: 
- Dependency injection for services that accept DatabaseInterface
- Fixed result sets for unit tests
- Avoids real database calls entirely

### Global Storage (FaDbStubs.php)
```php
$GLOBALS['__fa_table']         // In-memory table data
$GLOBALS['__fa_result_set']    // Result sets by SQL
$GLOBALS['__fa_result_pos']    // Current position in result
$GLOBALS['__fa_last_sql']      // Last executed SQL
$GLOBALS['__fa_last_update_matched']  // UPDATE match flag
```

---

## 5. FA Functions Mocked (Complete List)

### Database Operations (7 functions)
```
db_escape()
db_query()
db_fetch()
get_company_prefs()
update_company_prefs()
(+ FaConstantStubs for constants)
```

### Date/Time (7 functions)
```
begin_month()
end_month()
Today()
new_doc_date()
sql2date()
add_days()
is_new_reference()
```

### Business Data (2 functions)
```
get_supplier_details_all()
get_customer_details_all()
```

### Security (4 functions)
```
user_check_access()
add_access_extensions()
has_access()
get_user()
```

### UI (20+ functions)
```
start_form() / end_form()
start_table() / end_table()
table_section_title()
table_header()
start_row() / end_row()
label_cell()
text_row()
small_amount_row()
check_row()
hidden()
submit()
submit_center()
edit_button_cell()
delete_button_cell()
display_notification()
display_error()
_() [translation function]
```

### Hooks (3 functions)
```
fa_hooks()
add_filter()
apply_filters()
```

### Session (5+ functions)
```
[See FaSessionStubs.php for details]
```

---

## 6. Project-Specific Test Helpers (tests/helpers/fa_functions.php)

Beyond FAMock, ksf_bank_import provides additional test helpers:

### Helper Functions
- **`_test_extract_int_list_from_in_clause(string $sql): array`** - Parse IN clauses
- **`_test_normalize_sql_string_literal(string $value): string`** - Unescape SQL values  
- **`_test_join_statement_data(array $row): array`** - Join transaction with statement data
- **`_test_db_rows_for_sql(string $sql): array`** - Generate fake rows based on SQL pattern

### Test Data Sets
Global arrays for in-memory testing:
```php
$_test_company_prefs = [];        // Company preferences
$_test_db_affected_rows = 0;      // Track affected rows
$_test_bi_statements = [...]      // Mock bank statements
$_test_bi_transactions = [...]    // Mock bank transactions (500+ lines of test data)
```

### Advanced SQL Mocking
`_test_db_rows_for_sql()` handles:
- **bi_transactions queries**: 
  - GROUP BY account, g_option, g_partner (pairing aggregation)
  - WHERE id = N (by ID)
  - Date range filters (valuetimestamp >= X, < Y)
  - Amount range filters (abs(transactionAmount) >= X, <= Y)
  - Title search (transactionTitle LIKE X)
  - Account filter via joined statements
  - SORT and LIMIT
- **debtors_master queries**: Mock customer data
- **[Additional table support as needed]**

---

## 7. Integration Points

### How Bootstrap Works
1. **Composer autoload** loads all namespaced classes
2. **FAMock.php** loads 10 stub files (db, ui, business, security, etc.)
3. **Project helpers** (`fa_functions.php`) provide ksf-specific mocks and test data
4. **Constants defined** (`FA_ROOT`, `TB_PREF`)
5. **Tests run** with isolated, in-memory mocking

### Testing Patterns

#### Option 1: Direct db_* functions (legacy)
```php
$res = db_query("SELECT * FROM bi_transactions WHERE id = 1");
$row = db_fetch($res);
```

#### Option 2: MockDatabase (recommended for new code)
```php
$mockDb = new MockDatabase($rows);
$service = new MyService($mockDb);
```

#### Option 3: TestDbResult (integration tests)
```php
$result = new TestDbResult($rows);
while ($row = $result->fetch()) { ... }
```

---

## Summary

**FAMock Structure:**
- 12 stub files covering database, UI, business, security, dates, hooks, sessions
- 2 database mock classes for in-memory testing
- Global storage for simulation state
- Output buffering to suppress HTML

**Project Helpers:**
- Advanced SQL pattern matching for bank transfer/transaction simulation
- 3 test data sets (statements, transactions, preferences)
- Utility functions for SQL parsing and data joining

**Key Innovation:**
- Stubs use global `$GLOBALS['__fa_*']` for state (simple, no setup needed)
- MockDatabase uses dependency injection (cleaner, testable)
- SQL pattern matching allows realistic repository testing without DB setup
