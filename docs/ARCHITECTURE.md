# KSF Bank Import - Architecture Documentation

## Overview

The KSF Bank Import module follows a **service-oriented architecture** based on SOLID principles, separating concerns into specialized services that handle different aspects of paired bank transfer processing.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     process_statements.php                          │
│                    (Main Controller / UI)                           │
└─────────────────┬───────────────────────────────────────────────────┘
                  │
                  │ uses
                  ▼
    ┌─────────────────────────────────────────────────────┐
    │        PairedTransferProcessor                      │
    │          (Orchestration Service)                    │
    │                                                     │
    │  + processTransfer(trz1, trz2, acc1, acc2)         │
    │  + matchTransactions(transactions)                 │
    └───┬─────────────────┬─────────────────┬────────────┘
        │                 │                 │
        │ uses            │ uses            │ uses
        ▼                 ▼                 ▼
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│ Transfer      │  │  Bank         │  │ Transaction   │
│ Direction     │  │  Transfer     │  │ Updater       │
│ Analyzer      │  │  Factory      │  │               │
│               │  │               │  │               │
│ + analyze()   │  │ + validate()  │  │ + updateDB()  │
│               │  │ + create()    │  │               │
└───────────────┘  └───────────────┘  └───────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      Supporting Singletons                          │
├──────────────────────────────────┬──────────────────────────────────┤
│  VendorListManager               │  OperationTypesRegistry          │
│  (Session-cached vendor list)    │  (Pluggable operation types)     │
│                                  │                                  │
│  + getInstance()                 │  + getInstance()                 │
│  + getVendorList()               │  + getTypes()                    │
│  + clearCache()                  │  + getType(code)                 │
└──────────────────────────────────┴──────────────────────────────────┘
```

## Core Services

### 1. PairedTransferProcessor
**Responsibility:** Orchestrates the entire paired transfer workflow

**Key Methods:**
- `processTransfer($trz1, $trz2, $account1, $account2)` - Processes a pair of transactions as a bank transfer
- `matchTransactions($transactions)` - Finds matching transaction pairs within ±2 day window

**Dependencies:**
- TransferDirectionAnalyzer
- BankTransferFactory
- TransactionUpdater

**Location:** `Services/PairedTransferProcessor.php`

### 2. TransferDirectionAnalyzer
**Responsibility:** Determines which account is FROM and which is TO based on DC (Debit/Credit) indicators

**Key Logic:**
- Debit (D) = Money leaving account → FROM account
- Credit (C) = Money arriving to account → TO account

**Key Methods:**
- `analyze($trz1, $trz2, $account1, $account2)` - Returns transfer data with correct direction
- `buildTransferData()` - Constructs the transfer array

**Validation:**
- Ensures both transactions have DC indicators
- Validates amounts exist
- Checks account IDs are present

**Location:** `Services/TransferDirectionAnalyzer.php`

**Tests:** 11 unit tests, all passing ✓

### 3. BankTransferFactory
**Responsibility:** Validates transfer data and creates FrontAccounting bank transfers

**Key Methods:**
- `validate($transferData)` - Ensures all required fields present and valid
- `create($transferData)` - Calls FA's `add_bank_transfer()` function

**Validation Rules:**
- FROM and TO accounts must exist
- Amount must be positive and non-zero
- Date must be valid
- Memo is required
- FROM and TO accounts must be different

**Location:** `Services/BankTransferFactory.php`

### 4. TransactionUpdater
**Responsibility:** Updates transaction records in the database after processing

**Key Methods:**
- `updateTransactionPair($fromTransId, $toTransId)` - Marks both transactions as processed
- `setProcessed($transactionId)` - Marks single transaction as processed
- `linkTransactions($trans1Id, $trans2Id, $transferId)` - Creates linkage in database

**Location:** `Services/TransactionUpdater.php`

## Supporting Singletons

### VendorListManager
**Purpose:** Provides session-cached vendor list for improved performance

**Performance:** ~95% improvement (from multiple DB queries to single cached instance)

**Key Features:**
- Session-based caching
- Automatic cache invalidation
- Lazy loading

**Methods:**
```php
VendorListManager::getInstance()  // Get singleton instance
->getVendorList()                 // Get cached vendor list
->clearCache()                    // Force reload
```

**Location:** `Services/VendorListManager.php`

### OperationTypesRegistry
**Purpose:** Manages operation types with plugin architecture

**Default Types:**
- **SP** - Spending
- **CU** - Customer Payment
- **QE** - QuickEntry
- **BT** - Bank Transfer
- **MA** - Manual Adjustment
- **ZZ** - Ignore

**Plugin System:**
- Auto-discovers plugins in `OperationTypes/` directory
- Each plugin implements `OperationTypeInterface`
- Session-cached for performance

**Methods:**
```php
OperationTypesRegistry::getInstance()  // Get singleton instance
->getTypes()                           // Get all operation types
->getType('BT')                        // Get specific type
```

**Location:** `OperationTypes/OperationTypesRegistry.php`

## Data Flow Sequence

### Processing a Paired Transfer

```
1. User selects ProcessBothSides in UI (process_statements.php)
   ↓
2. PairedTransferProcessor::processTransfer() called
   ↓
3. TransferDirectionAnalyzer::analyze()
   - Examines DC indicators
   - Determines FROM/TO direction
   - Returns structured transfer data
   ↓
4. BankTransferFactory::validate()
   - Checks all required fields
   - Validates amounts, dates, accounts
   - Throws exception if invalid
   ↓
5. BankTransferFactory::create()
   - Calls FA's add_bank_transfer()
   - Returns transfer ID
   ↓
6. TransactionUpdater::updateTransactionPair()
   - Marks both transactions processed
   - Links transactions to transfer ID
   - Updates database
   ↓
7. Return success to UI
```

## Matching Algorithm

### Finding Paired Transfers

The system automatically identifies matching transaction pairs using:

**Criteria:**
1. **Amount Match:** Absolute values must match (within $0.01 tolerance)
2. **Opposite DC:** One must be Debit (D), one must be Credit (C)
3. **Date Window:** Transactions within **±2 days** of each other
4. **Different Accounts:** Cannot be same account

**Example:**
```
Account A: 2025-01-15, $100.00, D (Debit) → Money leaving Account A
Account B: 2025-01-16, $100.00, C (Credit) → Money arriving to Account B
Result: MATCH - Transfer from Account A to Account B
```

## Database Schema

### Key Tables

**`bi_transactions`** - Imported transactions
```sql
- id (INT) - Transaction ID
- account_id (INT) - Bank account
- transactionTitle (VARCHAR) - Description
- transactionDC (CHAR) - D=Debit, C=Credit
- transactionAmount (DECIMAL) - Amount
- valueTimestamp (DATE) - Transaction date
- processed (BOOL) - Processing status
- linked_transfer_id (INT) - FK to bank_transfers
```

**`bank_transfers`** (FrontAccounting)
```sql
- id (INT) - Transfer ID
- from_account (INT) - Source account
- to_account (INT) - Destination account
- amount (DECIMAL) - Transfer amount
- trans_date (DATE) - Transfer date
- memo (TEXT) - Description
```

## Testing

### Unit Tests
- **TransferDirectionAnalyzerTest** - 11 tests, all passing ✓
- Tests cover:
  - Debit/Credit direction logic
  - Real-world scenarios (Manulife, CIBC)
  - Validation rules
  - Amount handling
  - Memo generation

### Test Command:
```bash
composer test
# or
vendor/bin/phpunit tests/unit/TransferDirectionAnalyzerTest.php
```

## PSR Compliance

The codebase follows these PSR standards:

- **PSR-1** - Basic Coding Standard
- **PSR-2** - Coding Style Guide  
- **PSR-4** - Autoloading Standard
- **PSR-5** - PHPDoc Standard
- **PSR-12** - Extended Coding Style

**Naming Conventions:**
- Classes: `PascalCase`
- Methods: `camelCase()`
- Properties: `camelCase`
- Constants: `SCREAMING_SNAKE_CASE`

## File Organization

```
ksf_bank_import/
├── Services/
│   ├── PairedTransferProcessor.php
│   ├── TransferDirectionAnalyzer.php
│   ├── BankTransferFactory.php
│   ├── TransactionUpdater.php
│   └── VendorListManager.php
├── OperationTypes/
│   ├── OperationTypesRegistry.php
│   └── types/
│       └── (plugin directory)
├── tests/
│   └── unit/
│       ├── TransferDirectionAnalyzerTest.php
│       └── (other test files)
├── docs/
│   └── ARCHITECTURE.md (this file)
└── process_statements.php (main controller)
```

## Configuration

### Matching Window
The ±2 day matching window is configurable in `PairedTransferProcessor.php`:

```php
private const MATCH_WINDOW_DAYS = 2;
```

### Cache Duration
Session cache duration in `VendorListManager.php`:

```php
private $cacheDuration = 3600; // 1 hour in seconds
```

## Extension Points

### Adding New Operation Types

1. Create new class in `OperationTypes/types/`
2. Implement `OperationTypeInterface`
3. Define required methods:
   - `getCode()` - Return type code (e.g., 'NT')
   - `getName()` - Return display name
   - `getHandler()` - Return processing callback

Example:
```php
class NewTransferType implements OperationTypeInterface {
    public function getCode(): string {
        return 'NT';
    }
    
    public function getName(): string {
        return 'New Transfer Type';
    }
    
    public function getHandler(): callable {
        return function($transaction) {
            // Custom processing logic
        };
    }
}
```

### Custom Validation Rules

Add validation in `BankTransferFactory::validate()`:

```php
if ($transferData['amount'] > 1000000) {
    throw new \InvalidArgumentException(
        'Amount exceeds maximum limit'
    );
}
```

## Performance Optimizations

1. **Session Caching**
   - VendorListManager uses PHP sessions
   - ~95% performance improvement
   - Reduces redundant database queries

2. **Singleton Pattern**
   - Single instances of managers/registries
   - Memory efficient
   - State preserved across requests

3. **Lazy Loading**
   - Resources loaded only when needed
   - Reduced initial page load time

4. **Batch Processing**
   - Transaction matching done in batches
   - Minimizes database round-trips

## Error Handling

### Validation Exceptions
All services throw `\InvalidArgumentException` for validation failures:

```php
try {
    $processor->processTransfer($trz1, $trz2, $acc1, $acc2);
} catch (\InvalidArgumentException $e) {
    display_error($e->getMessage());
}
```

### Database Errors
Database operations should be wrapped in transactions:

```php
begin_transaction();
try {
    // Processing logic
    commit_transaction();
} catch (\Exception $e) {
    rollback_transaction();
    throw $e;
}
```

## Security Considerations

1. **Input Validation**
   - All user inputs validated before processing
   - Amount bounds checking
   - Account ID verification

2. **SQL Injection Protection**
   - Use prepared statements
   - Parameterized queries only

3. **Authorization**
   - Check user permissions before processing
   - Validate account access rights

## Migration Notes

### From Legacy Code
The old `ProcessBothSides` handler (lines 105-154) has been replaced with:

```php
require_once('Services/PairedTransferProcessor.php');
$processor = new \KsfBankImport\Services\PairedTransferProcessor(
    $bi_trans_model,
    $vendorList,
    $optypes
);
$processor->processTransfer($trz1, $trz2, $account1, $account2);
```

## Future Enhancements

1. **Fuzzy Matching** - Allow small amount discrepancies
2. **Multi-Transfer Chains** - Handle A→B→C scenarios  
3. **Confidence Scores** - Rate match quality
4. **ML-Based Matching** - Learn from historical patterns
5. **API Integration** - REST API for external systems
6. **Webhook Support** - Notifications for processed transfers

## References

- [PSR Standards](https://www.php-fig.org/psr/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [FrontAccounting API](https://frontaccounting.com/)

## Version History

- **1.0.0** - Initial service-oriented architecture implementation
  - Separated concerns into dedicated services
  - Added comprehensive unit tests
  - Implemented singleton patterns for performance
  - PSR compliance

---

**Last Updated:** 2025-01-18  
**Author:** Kevin Fraser  
**License:** MIT
