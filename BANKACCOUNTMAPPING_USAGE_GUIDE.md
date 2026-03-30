# BankAccountMapping Usage Guide

**Created:** 2026-03-30  
**Version:** 1.0  
**Status:** Ready for Implementation

---

## Quick Start

### Import the Namespace
```php
use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;
use Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory;
```

### Basic Operations

#### 1. Create a Mapping from Statement Data
```php
// Extract mapping from an imported statement
$statement = new bi_statements_model();
$statement->bankid = '021000021';
$statement->acctid = '123456789';
$statement->intu_bid = 'my_bank_123';
$statement->currency = 'USD';

// Create entity
$mapping = BankAccountMappingFactory::createFromStatement($statement, $fa_bank_account_id);

// Store it
if ($mapping) {
    $mappingId = BankAccountMappingRepository::upsert($mapping, $fa_bank_account_id);
}
```

#### 2. Find a Mapping by OFX Identifiers
```php
// Later, when processing transactions, find the mapping
$mapping = BankAccountMappingRepository::findByOFXIdentifiers(
    bankid: '021000021',
    acctid: '123456789'
);

if ($mapping) {
    $faAccountId = $mapping->bank_account_id;
    echo "This transaction belongs to FA account: " . $faAccountId;
}
```

#### 3. Find All Mappings for a FA Account
```php
$mappings = BankAccountMappingRepository::findByFABankAccountId($fa_account_id);

foreach ($mappings as $mapping) {
    echo "OFX ID: " . $mapping->getPrimaryExternalId() . "\n";
    echo "Type: " . $mapping->accttype . "\n";
    echo "Currency: " . $mapping->curdef . "\n";
}
```

#### 4. Get Display Information
```php
$mapping = BankAccountMappingRepository::findById($mapping_id);

echo $mapping->getDisplayName();  // "021000021:123456789 [CHECKING]"
echo $mapping->getPrimaryExternalId();  // "021000021:123456789"
echo $mapping->toArray();  // Array representation for JSON
```

---

## Common Usage Patterns

### Pattern 1: Extract Mapping During Import

**File:** `import_statements.php` (around line 302-320)

**Before:**
```php
// Cascade OFX identifiers from statement to transaction
$bit->set('acctid', $smt->acctid);
$bit->set('bankid', $smt->bankid);
$bit->set('intu_bid', $smt->intu_bid);
```

**After:**
```php
use Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory;
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;

// Cascade OFX identifiers from statement to transaction (as before)
$bit->set('acctid', $smt->acctid);
$bit->set('bankid', $smt->bankid);
$bit->set('intu_bid', $smt->intu_bid);

// NEW: Also extract and store the BankAccountMapping
$mapping = BankAccountMappingFactory::createFromStatement($smt);
if ($mapping) {
    $mappingId = BankAccountMappingRepository::upsert($mapping);
    // Optional: Store mapping ID in transaction for later reference
    // $bit->set('bank_account_mapping_id', $mappingId);
}
```

### Pattern 2: Cross-Reference from Transaction

**Goal:** Get transaction's associated FA bank account

**Code:**
```php
// Load transaction
$transaction = new bi_transactions_model();
$transaction->get_transaction($transaction_id);

// Get the bank account mapping
$mapping = BankAccountMappingRepository::findByOFXIdentifiers(
    $transaction->bankid,
    $transaction->acctid,
    $transaction->intu_bid
);

if ($mapping && $mapping->isLinkedToFAAccount()) {
    $faAccountId = $mapping->bank_account_id;
    // Use $faAccountId for subsequent processing
}
```

### Pattern 3: Cross-Reference from Statement

**Goal:** Get all accounts referenced in a statement

**Code:**
```php
// Load statement
$statement = new bi_statements_model();
$stmtData = $statement->get_statement($statement_id);

// Get the bank account mapping for this statement
$mapping = BankAccountMappingRepository::findByOFXIdentifiers(
    $stmtData->bankid,
    $stmtData->acctid,
    $stmtData->intu_bid
);

if ($mapping) {
    $faAccountId = $mapping->bank_account_id;
    $accountType = $mapping->accttype;  // CHECKING, SAVINGS, etc
    $currency = $mapping->curdef;       // USD, EUR, etc
}
```

### Pattern 4: Lookup by FA Account ID

**Goal:** Get all OFX mappings for a specific FA bank account

**Code:**
```php
$faAccountId = 15;  // Some FA bank account ID

// Get all OFX mappings for this account
$mappings = BankAccountMappingRepository::findByFABankAccountId($faAccountId);

foreach ($mappings as $mapping) {
    echo "This FA account also receives from: " . $mapping->getPrimaryExternalId() . "\n";
}
```

### Pattern 5: Update FA Account Reference

**Goal:** Link a mapping to a different FA bank account

**Code:**
```php
// Find or create mapping
$mapping = BankAccountMappingFactory::createFromArray([
    'bankid' => '021000021',
    'acctid' => '123456789',
    'intu_bid' => null,
    'accttype' => 'CHECKING',
    'curdef' => 'USD',
]);

// Link to FA bank account
$faAccountId = 42;
$mappingId = BankAccountMappingRepository::upsert($mapping, $faAccountId);

// Later, update the link
$updatedMapping = BankAccountMappingRepository::findById($mappingId);
$updatedMapping->bank_account_id = 99;  // Different FA account
BankAccountMappingRepository::upsert($updatedMapping);
```

---

## Factory Methods

### Create from Different Sources

```php
// From bi_statements_model
$mapping = BankAccountMappingFactory::createFromStatement($statement, $fa_account_id);

// From bi_transactions_model
$mapping = BankAccountMappingFactory::createFromTransaction($transaction, $fa_account_id);

// From bi_counterparty_model
$mapping = BankAccountMappingFactory::createFromCounterparty($counterparty, $fa_account_id);

// From array (import metadata, API response, etc)
$mapping = BankAccountMappingFactory::createFromArray([
    'bankid' => '021000021',
    'acctid' => '123456789',
    'accttype' => 'CHECKING',
    'curdef' => 'USD',
], $fa_account_id);
```

### Utility Methods

```php
// Compare two OFX identifier sets
$match = BankAccountMappingFactory::areIdentifiersEqual(
    '021000021', '123456789', null,  // First set
    '021000021', '123456789', null   // Second set
);

// Generate a display key
$key = BankAccountMappingFactory::generateIdentifierKey(
    '021000021',
    '123456789',
    'my_bank_123'
);
// Result: "021000021|123456789|intu:my_bank_123"
```

---

## Repository Methods Reference

### Lookup Methods

| Method | Purpose | Returns |
|--------|---------|---------|
| `findById($id)` | Get mapping by ID | `BankAccountMapping\|null` |
| `findByOFXIdentifiers($bankid, $acctid, $intu_bid)` | Find by OFX IDs | `BankAccountMapping\|null` |
| `findByFABankAccountId($bankAccountId)` | Get all mappings for FA account | `BankAccountMapping[]` |
| `getAllMappings($limit, $offset)` | Paginated list | `BankAccountMapping[]` |

### Write Methods

| Method | Purpose | Returns |
|--------|---------|---------|
| `upsert($mapping, $bankAccountId)` | Insert or update | `int` (ID) |
| `delete($id)` | Delete by ID | `bool` |
| `deleteByFABankAccountId($bankAccountId)` | Delete all for FA account | `int` (count) |

### Utility Methods

| Method | Purpose | Returns |
|--------|---------|---------|
| `tableExists()` | Check if table exists | `bool` |
| `countAll()` | Total mappings | `int` |
| `countByFABankAccountId($bankAccountId)` | Mappings for FA account | `int` |

---

## Entity Properties & Methods

### Properties
```php
$mapping->bank_account_id;  // int: FA bank account ID
$mapping->intu_bid;         // string: Intuit Business ID
$mapping->bankid;           // string: OFX BANKID
$mapping->acctid;           // string: OFX ACCTID
$mapping->accttype;         // string: Account type (CHECKING, SAVINGS, etc)
$mapping->curdef;           // string: Currency code (USD, EUR, etc)
```

### Methods
```php
// Display methods
$mapping->getDisplayName();        // "021000021:123456789 [CHECKING]"
$mapping->getPrimaryExternalId();  // "021000021:123456789" or intu_bid
$mapping->toArray();               // Array for JSON serialization

// Status checks
$mapping->hasValidIdentifiers();   // bool: Has at least one OFX ID
$mapping->isLinkedToFAAccount();   // bool: Has valid FA account link

// Lookup key
$mapping->getCompositeKey();       // String for deduplication
```

---

## Integration Checklist

### For import_statements.php
- [ ] Add factory import at top
- [ ] Add repository import at top
- [ ] After line 312 (cascading to transaction), extract mapping
- [ ] Call `BankAccountMappingRepository::upsert()`
- [ ] Test with sample import

### For process_statements.php
- [ ] Check if processing line items
- [ ] Extract mapping for each line item
- [ ] Store mapping reference

### For TransferMatchService.php
- [ ] Replace `bi_bank_accounts_model` calls with repository
- [ ] Use `findByOFXIdentifiers()` instead of static methods
- [ ] Update error handling

### For ContactService.php
- [ ] Look up mappings for account type/currency info
- [ ] Use repository instead of scattered lookups

### For existing code
- [ ] Keep `bi_bank_accounts_model` for backward compatibility
- [ ] It now wraps the repository internally
- [ ] All old code continues to work

---

## Backward Compatibility

### Legacy Code Still Works
```php
// Old code - still works
$mapping = bi_bank_accounts_model::get_row($bank_account_id);

// Is now wrapped by:
$mapping_entity = BankAccountMappingRepository::findByFABankAccountId($bank_account_id);
```

### Transition Strategy
1. Start using new repository in new code
2. Legacy controllers/services continue using old model
3. Gradually refactor files to use repository
4. Old model becomes thin wrapper around repository
5. Eventually deprecate and remove old model

---

## Testing Examples

### Unit Test: Create from Statement
```php
public function testCreateFromStatement()
{
    $statement = new \stdClass();
    $statement->bankid = '021000021';
    $statement->acctid = '123456789';
    $statement->intu_bid = 'test_bid';
    $statement->currency = 'USD';

    $mapping = BankAccountMappingFactory::createFromStatement($statement, 42);

    $this->assertEquals('021000021', $mapping->bankid);
    $this->assertEquals('123456789', $mapping->acctid);
    $this->assertEquals(42, $mapping->bank_account_id);
}
```

### Unit Test: Find by OFX Identifiers
```php
public function testFindByOFXIdentifiers()
{
    // Pre-populate with test data
    $mapping = BankAccountMappingFactory::createFromArray([
        'bankid' => '021000021',
        'acctid' => '123456789',
    ]);
    $id = BankAccountMappingRepository::upsert($mapping, 42);

    // Now find it
    $found = BankAccountMappingRepository::findByOFXIdentifiers('021000021', '123456789');
    $this->assertNotNull($found);
    $this->assertEquals(42, $found->bank_account_id);
}
```

### Integration Test: Import Statement
```php
public function testImportStatementWithMapping()
{
    $statement = $this->createTestStatement();
    
    // Extract mapping
    $mapping = BankAccountMappingFactory::createFromStatement($statement, 42);
    $this->assertNotNull($mapping);
    
    // Store it
    $id = BankAccountMappingRepository::upsert($mapping, 42);
    $this->assertGreater(0, $id);
    
    // Verify retrieval
    $found = BankAccountMappingRepository::findById($id);
    $this->assertNotNull($found);
}
```

---

## Troubleshooting

### Problem: Mapping Not Found
```php
$mapping = BankAccountMappingRepository::findByOFXIdentifiers($bankid, $acctid);
if ($mapping === null) {
    // Check if identifiers are being normalized correctly
    $normalizedBankid = BankAccountMappingFactory::normalizeOFXBankId($bankid);
    $normalizedAcctid = BankAccountMappingFactory::normalizeOFXAccountId($acctid);
    
    // Debug: Log the normalized values
    error_log("Looking for: $normalizedBankid:$normalizedAcctid");
}
```

### Problem: Multiple Mappings Returned
```php
// If multiple statements have same OFX identifiers but different FA accounts
$mappings = BankAccountMappingRepository::findByFABankAccountId($fa_account_id);
if (count($mappings) > 1) {
    // This FA account receives from multiple OFX sources
    // Or there are duplicate mappings needing deduplication
}
```

### Problem: Null Reference in Entity
```php
if ($mapping && $mapping->hasValidIdentifiers()) {
    // Ensure at least one OFX identifier is present
    // Safe to use $mapping->getPrimaryExternalId()
}
```

---

## Performance Tips

1. **Cache Mappings**: For frequently accessed mappings, implement in-memory cache
2. **Batch Queries**: When processing many transactions, batch lookup by OFX IDs
3. **Indexes**: The table has UNIQUE KEY on (acctid, bankid, intu_bid) for fast lookup
4. **Pagination**: Use `getAllMappings($limit, $offset)` for large result sets

---

## Next Steps

1. ✅ **Repository & Factory Created**
2. ⏳ **Integrate with import_statements.php**
3. ⏳ **Update Services to use Repository**
4. ⏳ **Add Cross-Reference Methods to Legacy Models**
5. ⏳ **Write Unit Tests**
6. ⏳ **Run Integration Tests**
7. ⏳ **Document via llms.txt**

---

**Questions?** See [BANKACCOUNTMAPPING_MIGRATION.md](BANKACCOUNTMAPPING_MIGRATION.md) for detailed architecture.
