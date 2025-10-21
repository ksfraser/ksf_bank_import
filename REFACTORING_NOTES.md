# Refactoring Notes - KSF Bank Import Module

## Table of Contents
1. [Quick Entry Matching Refactoring](#quick-entry-matching-refactoring)
2. [Process Statements Switch Statement Refactoring](#process-statements-switch-statement-refactoring)

---

## Process Statements Switch Statement Refactoring

### Date Completed
2025-01-XX (Current Session)

### Problem Identified

#### Violation of SOLID Principles
The `process_statements.php` file contained a massive **420-line switch statement** (lines 193-613) that violated multiple principles:

1. **Single Responsibility Principle (SRP)**: One file handled 6 different transaction types with completely different business logic
2. **Open/Closed Principle (OCP)**: Adding new partner types required modifying the switch statement
3. **Dependency Inversion**: Concrete implementations were tightly coupled to the procedural script

#### Maintainability Issues
- **757 total lines** in a single procedural file
- Switch statement cases ranging from 8 lines (SP) to 150+ lines (CU)
- Inline business logic mixed with display code
- Difficult to test individual transaction types
- High cyclomatic complexity

### Solution Implemented

#### Strategy Pattern with Handler Classes
Replaced the monolithic switch statement with a **Strategy Pattern** using:

1. **TransactionProcessor** - Orchestrator class that:
   - Registers handler classes for each partner type
   - Delegates processing to appropriate handler
   - Returns standardized `TransactionResult` objects

2. **Six Handler Classes** implementing `TransactionHandlerInterface`:
   - `SupplierTransactionHandler` (SP) - Supplier payments
   - `CustomerTransactionHandler` (CU) - Customer receipts/deposits
   - `QuickEntryTransactionHandler` (QE) - Recurring expenses
   - `BankTransferTransactionHandler` (BT) - Inter-account transfers
   - `ManualSettlementHandler` (MA) - Manual transaction linking
   - `MatchedTransactionHandler` (ZZ) - Auto-matched transactions

3. **TransactionResult** - Immutable value object for outcomes:
   - Success/Error/Warning states
   - Transaction number and type
   - Display integration with FrontAccounting

#### Code Changes

**Before** (420 lines):
```php
switch(true) {
    case ($_POST['partnerType'][$k] == 'SP'):
        // 8 lines of supplier payment logic
        break;
    
    case ($_POST['partnerType'][$k] == 'CU' && ...):
        // 150+ lines of customer payment logic
        break;
    
    case ($_POST['partnerType'][$k] == 'QE'):
        // 98 lines of quick entry logic
        break;
    
    // ... 3 more cases ...
}
```

**After** (35 lines - AUTO-DISCOVERY):
```php
// Initialize processor (once at startup)
// Auto-discovers and loads all handlers from Handlers/ directory
$transactionProcessor = new TransactionProcessor();

// Process transaction (replaces 420-line switch)
try {
    $partnerType = $_POST['partnerType'][$k];
    $collectionIds = implode(',', array_filter(explode(',', $_POST['cids'][$tid] ?? '')));
    
    $result = $transactionProcessor->process(
        $partnerType,
        $trz,              // Database transaction data
        $_POST,            // Form POST data
        $tid,              // Transaction ID
        $collectionIds,    // Charge transaction IDs
        $our_account       // Our bank account
    );
    
    $result->display();  // Handles success/error notifications
    
    // Display transaction links
    if ($result->isSuccess() && $result->getTransNo() > 0) {
        display_notification("<a href='...'>View GL Entry</a>");
    }
    
} catch (\InvalidArgumentException $e) {
    display_error("No handler registered for partner type: {$partnerType}");
} catch (\Exception $e) {
    display_error("Error processing transaction: " . $e->getMessage());
}
```

### Metrics

#### File Size Reduction
- **Before**: 757 lines
- **After (Switch Replacement)**: 332 lines
- **After (Auto-Discovery)**: 319 lines
- **Total Reduction**: 438 lines (58% smaller) 🎉

#### Code Complexity Reduction
- **Before**: Single file with 6 different responsibilities
- **After**: 1 orchestrator + 6 specialized handlers
- **Switch Statement**: 420 lines → 35 lines (92% reduction)
- **Handler Registration**: 13 lines → 1 line (zero-config)
- **Lines per handler**: Average ~150 lines (focused, testable)

#### Test Coverage
- **Handler Tests**: 70 tests across 6 handler test files
- **Processor Tests**: 14 tests, 50 assertions (TransactionProcessorTest.php)
- **Status**: All tests passing ✅

### Benefits

1. **Testability**: Each handler can be unit tested independently
2. **Maintainability**: Changes to one transaction type don't affect others
3. **Extensibility**: New partner types = add new handler class (no file modification)
4. **Readability**: Clear separation of concerns, easy to understand flow
5. **SOLID Compliance**:
   - ✅ Single Responsibility: Each handler does one thing
   - ✅ Open/Closed: Add handlers without modifying processor
   - ✅ Liskov Substitution: All handlers implement same interface
   - ✅ Interface Segregation: Clean, focused interface
   - ✅ Dependency Inversion: Depends on abstractions (interface)

### Architecture Diagram

```
process_statements.php
    ↓
TransactionProcessor (Orchestrator) 🆕 AUTO-DISCOVERY
    ├── __construct() - Auto-discovers handlers from Handlers/ directory
    ├── discoverAndRegisterHandlers() - Scans and loads handler classes
    ├── registerHandler() - Manual registration (also available)
    ├── process() - Delegates to handler
    └── extractTransactionPostData() - Separates concerns
    
TransactionHandlerInterface
    ├── process() - Main processing method
    ├── getPartnerType() - Returns type code
    └── canProcess() - Type validation
    
Handlers (Auto-discovered)
    ├── SupplierTransactionHandler (SP)
    ├── CustomerTransactionHandler (CU)
    ├── QuickEntryTransactionHandler (QE)
    ├── BankTransferTransactionHandler (BT)
    ├── ManualSettlementHandler (MA)
    └── MatchedTransactionHandler (ZZ)
    
TransactionResult (Return Value)
    ├── success() - Factory for success
    ├── error() - Factory for errors
    ├── display() - FA notification integration
    └── toArray() - Backward compatibility
```

### Key Improvement: Auto-Discovery Pattern

**Problem**: Manual handler registration was a code smell - client code (`process_statements.php`) had to know about all handler classes.

**Solution**: TransactionProcessor now uses **Auto-Discovery Pattern**:
- Constructor scans `Handlers/` directory on instantiation
- Automatically loads and registers all handlers implementing `TransactionHandlerInterface`
- Eliminates 13 lines of manual registration code
- New handlers are discovered automatically when added to directory

**Benefits**:
- ✅ **Zero Configuration**: Just instantiate `new TransactionProcessor()`
- ✅ **Better Encapsulation**: Handler details hidden from client code
- ✅ **Easier Testing**: Pass custom handlers array for isolated tests
- ✅ **Plugin Architecture**: Drop new handler file = instant registration

### Files Modified

1. **process_statements.php** - Main refactoring target
   - Removed manual handler registration (13 lines)
   - Single line: `$transactionProcessor = new TransactionProcessor();`
   - Reduced from 757 to **319 lines** (438 lines removed, 58% smaller)

2. **TransactionProcessor.php** - Added auto-discovery
   - Added constructor with auto-discovery logic
   - Added `discoverAndRegisterHandlers()` private method
   - Accepts optional handler array for testing
   - Maintains backward compatibility with `registerHandler()`

3. **TransactionProcessorTest.php** - Updated tests
   - Added test for auto-discovery behavior
   - Pass empty array `[]` to constructor for manual registration tests
   - Added 2 new test cases (now 14 tests, 50 assertions)

### Files Already Existing (Infrastructure)

1. **TransactionProcessor.php** - Strategy pattern orchestrator with auto-discovery
2. **TransactionHandlerInterface.php** - Handler contract
3. **AbstractTransactionHandler.php** - Base implementation
4. **TransactionResult.php** - Immutable result object
5. **Handlers/** - Six handler implementations (auto-discovered)
6. **Tests/** - Comprehensive test coverage

### Migration Guide

For other large switch statements in the codebase:

1. **Identify Cases**: Document each case and its responsibility
2. **Create Interface**: Define handler contract
3. **Extract Handlers**: One handler per case
4. **Create Processor**: Build orchestrator with registration
5. **Replace Switch**: Call processor.process() instead
6. **Test**: Verify each handler independently
7. **Document**: Update notes and architecture docs

### TODO: Further Refactoring

Next steps for `process_statements.php`:

1. **Extract View Layer** - Create `ProcessStatementsView` class
   - Move HTML rendering (lines 250+)
   - Move header table generation
   - Separate presentation from business logic

2. **Extract Search/Filter Logic** - Create service class
   - Centralize search functionality
   - Improve query building
   - Add pagination support

3. **Dependency Injection** - Consider DI container
   - Inject TransactionProcessor
   - Inject View dependencies
   - Improve testability further

---

## Quick Entry Matching Refactoring

### Problem Identified

#### DRY Violation
The same logic for matching transactions and determining partner types was duplicated across multiple files:
- `views/class.bi_lineitem.php`
- `src/Ksfraser/View/BiLineItemView.php`
- `class.bi_lineitem.php` (root)
- `src/Ksfraser/FaBankImport/class.bi_lineitem.php`

#### SRP Violation
Business logic (determining partner type based on matched transactions) was located in View layer files instead of the Model layer.

### Solution Implemented

#### Centralized Business Logic in Model
Created a new method in `BiLineItemModel`:
```php
protected function determinePartnerTypeFromMatches(): void
```

This method:
1. Examines matched transactions
2. Applies business rules to determine partner type:
   - **Invoice match** → SP (Supplier Payment)
   - **Bank Payment/Deposit match** → QE (Quick Entry) - *NEW FEATURE*
   - **Other match** → ZZ (Generic matched transaction)
3. Sets the appropriate `partnerType` and `oplabel`

#### Simplified View Layer
View classes now simply call `$model->findMatchingExistingJE()` which:
1. Finds matching transactions
2. Automatically determines partner type (via `determinePartnerTypeFromMatches()`)
3. Returns the matches

The View layer is responsible only for:
- Displaying the results
- Setting hidden form fields for matched transactions

### Benefits

1. **Single Source of Truth**: Business logic exists in one place (Model)
2. **Easier Maintenance**: Changes to matching logic only need to be made once
3. **Better Testing**: Can test business logic independently of views
4. **Proper Separation of Concerns**: Model handles business logic, View handles presentation

### New Feature: Quick Entry Matching

Transactions that match existing Quick Entry transactions (recurring expenses like groceries, insurance, utilities) are now automatically suggested as 'QE' partner type instead of generic 'ZZ'.

This improves workflow for recurring transactions by:
- Auto-selecting the correct Quick Entry type
- Pre-populating the Quick Entry dropdown
- Applying the same GL account coding as the original

### Files Modified

1. `src/Ksfraser/Model/BiLineItemModel.php`
   - Added `determinePartnerTypeFromMatches()` method
   - Enhanced `findMatchingExistingJE()` to call determination logic

2. `src/Ksfraser/View/BiLineItemView.php`
   - Simplified `getDisplayMatchingTrans()` to delegate to Model
   - Removed duplicated business logic

3. `views/class.bi_lineitem.php`
   - Added Quick Entry detection in matching logic
   - (Should be refactored to use Model method in future)

### TODO: Further Refactoring

The following files still contain duplicate logic and should be updated to use the centralized Model method:
- `views/class.bi_lineitem.php` - Update to use BiLineItemModel
- `class.bi_lineitem.php` (root) - Likely deprecated, consider removing
- `src/Ksfraser/FaBankImport/class.bi_lineitem.php` - Update or remove if duplicate

Consider creating a migration plan to consolidate all line item handling to the new MVC structure.
