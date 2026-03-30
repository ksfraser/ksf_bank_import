# BankAccountMapping Migration Strategy

**Status:** Planning  
**Date:** 2026-03-30  
**Entity:** BankAccountMapping (newly created)  
**Legacy Sources:** bi_statements_model, bi_transactions, bi_counterparty_model, bi_bank_accounts_model  

---

## Overview

The bank account mapping data (OFX identifiers, account types, currencies) is currently scattered across multiple legacy classes:

- **bi_statements_model** → stores `acctid`, `bankid`, `intu_bid` at statement level
- **bi_transactions** → stores `acctid`, `bankid` at transaction level  
- **bi_counterparty_model** → stores `bank_id`, `account_id`
- **bi_bank_accounts_model** → legacy lookup table with FA bank account → OFX mapping

**Goal:** Consolidate all bank account mappings into a single `BankAccountMapping` entity with proper cross-references back to transactions and statements.

---

## Current Data Flow

```
OFX Import File
    ↓
bi_statements_model (acctid, bankid, intu_bid, curdef)
    ↓
import_statements.php (lines 305-312)
    ↓
bi_transactions (acctid, bankid cascaded from statement)
    ↓
bi_bank_accounts_model.upsert() (stores mapping by bankid+acctid+intu_bid)
```

## New Data Flow (Target)

```
OFX Import File
    ↓
bi_statements_model (acctid, bankid, intu_bid, curdef)
    ↓
BankAccountMappingRepository::extractFromStatement()
    ↓
BankAccountMapping entity (normalized, deduplicated)
    ↓
bi_transactions → references BankAccountMapping via acctid+bankid
bi_statements → references BankAccountMapping via acctid+bankid
bi_counterparty_model → references BankAccountMapping via bank_id+account_id
```

---

## Migration Steps

### Phase 1: Create Repository & Factory (✅ DONE)

**Files to create:**
1. `app/Shared/Repositories/BankAccountMappingRepository.php`
   - CRUD operations for BankAccountMapping
   - Lookup by OFX identifiers (bankid+acctid)
   - Lookup by Intuit BID
   - Lookup by FA bank account ID
   - Upsert logic (extract from statement metadata)

2. `app/Shared/Factories/BankAccountMappingFactory.php`
   - Create BankAccountMapping from statement data
   - Create BankAccountMapping from transaction data
   - Normalize OFX identifiers

### Phase 2: Update Legacy Models (🔄 IN PROGRESS)

**bi_statements_model changes:**
```php
// Before: stores raw OFX identifiers
public $acctid;    // OFX account ID
public $bankid;    // OFX bank ID
public $intu_bid;  // Intuit BID

// After: still stores raw data, but also exposes BankAccountMapping via getter
public function getBankAccountMapping(): ?BankAccountMapping {
    // Extract and return mapped entity
}
```

**bi_transactions changes:**
```php
// Before: stores raw OFX identifiers cascaded from statement
public $acctid;
public $bankid;

// After: still stores raw data for legacy compatibility, but adds reference
public function getBankAccountMapping(): ?BankAccountMapping {
    // Lookup or create mapping from acctid+bankid
}
```

### Phase 3: Update Import Pipeline

**import_statements.php changes:**
```php
// Line 302-320: When processing statement → transaction cascade

// Before:
$bit->set('acctid', $smt->acctid);
$bit->set('bankid', $smt->bankid);

// After:
$bit->set('acctid', $smt->acctid);
$bit->set('bankid', $smt->bankid);

// NEW: Also extract BankAccountMapping
$mapping = BankAccountMappingFactory::createFromStatement($smt);
if ($mapping) {
    BankAccountMappingRepository::upsert($mapping);
    // Optionally: $bit->setBankAccountMappingId($mapping->id);
}
```

### Phase 4: Update Service Layer

**Services that use bank account mappings:**

1. **TransferMatchService.php**
   - Use `BankAccountMappingRepository::findByOFXIdentifiers()`
   - Instead of direct `bi_bank_accounts_model` calls

2. **BankImportModuleSchemaService.php**
   - Aggregate bank account mappings for schema
   - Use new repository methods

3. **ContactService.php**
   - Look up bank account info via BankAccountMapping
   - Instead of scattered lookups

---

## Database Schema (bi_bank_accounts table)

```sql
CREATE TABLE `fa_bi_bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` int(11) NOT NULL,        -- FK to FA bank accounts
  `intu_bid` varchar(64) NULL,               -- Intuit BID
  `bankid` varchar(64) NULL,                 -- OFX BANKID
  `acctid` varchar(64) NULL,                 -- OFX ACCTID
  `accttype` varchar(32) NULL,               -- Account type (CHECKING, SAVINGS, etc)
  `curdef` varchar(3) NULL,                  -- Currency code
  `updated_ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping` (`acctid`, `bankid`, `intu_bid`)
);
```

---

## Cross-Reference Strategy

### Querying from bi_transactions:
```php
// Get bank account mapping for a transaction
$transaction = new bi_transactions_model();
$mapping = BankAccountMappingRepository::findByOFXIdentifiers(
    $transaction->acctid,
    $transaction->bankid
);
$faAccountId = $mapping->bank_account_id;
```

### Querying from bi_statements:
```php
// Get bank account mapping for a statement
$statement = new bi_statements_model();
$mapping = BankAccountMappingRepository::findByOFXIdentifiers(
    $statement->acctid,
    $statement->bankid
);
$faAccountId = $mapping->bank_account_id;
```

### Bidirectional lookup:
```php
// Get all statements with a specific bank account mapping
$statements = bi_statements_model::get_statements_for_mapping($mappingId);

// Get all transactions with a specific bank account mapping
$transactions = bi_transactions_model::get_transactions_for_mapping($mappingId);
```

---

## Implementation Checklist

### Step 1: Create Repository & Factory
- [ ] Create `BankAccountMappingRepository.php`
  - [ ] `create($data): BankAccountMapping`
  - [ ] `findById($id): ?BankAccountMapping`
  - [ ] `findByOFXIdentifiers($bankid, $acctid, $intu_bid): ?BankAccountMapping`
  - [ ] `findByFA_AccountId($bankAccountId): ?BankAccountMapping[]`
  - [ ] `upsert($mapping): void`
  - [ ] `delete($id): void`
  - [ ] `getAllMappings(): BankAccountMapping[]`

- [ ] Create `BankAccountMappingFactory.php`
  - [ ] `createFromStatement($statement): ?BankAccountMapping`
  - [ ] `createFromTransaction($transaction): ?BankAccountMapping`
  - [ ] `normalizeOFXIdentifiers($bankid, $acctid, $intu_bid): array`

### Step 2: Add Getters to Entity
- [ ] Add `BankAccountMapping::getPrimaryExternalId()` method ✅ DONE
- [ ] Add `BankAccountMapping::getDisplayName()` method
- [ ] Add `BankAccountMapping::toArray()` method

### Step 3: Update Import Pipeline
- [ ] Update `import_statements.php` lines 302-320
  - [ ] Extract mapping after cascading to transaction
  - [ ] Store mapping via repository

- [ ] Update `process_statements.php` lines 278, 325
  - [ ] Extract mappings when processing statements/line items

### Step 4: Update Services
- [ ] Update `TransferMatchService.php`
- [ ] Update `BankImportModuleSchemaService.php`
- [ ] Update `ContactService.php`

### Step 5: Backward Compatibility Layer
- [ ] Update `bi_bank_accounts_model` to wrap repository
- [ ] Ensure all old queries still work
- [ ] Add deprecation notices

### Step 6: Cross-Reference Methods
- [ ] Add `bi_statements_model::findByBankAccountMappingId()`
- [ ] Add `bi_transactions_model::findByBankAccountMappingId()`
- [ ] Add `BankAccountMapping::getRelatedStatements()`
- [ ] Add `BankAccountMapping::getRelatedTransactions()`

### Step 7: Testing
- [ ] Unit tests for repository
- [ ] Unit tests for factory
- [ ] Integration tests for import pipeline
- [ ] Tests for cross-references
- [ ] Tests for backward compatibility

### Step 8: Validation
- [ ] Run existing PHPUnit tests
- [ ] Verify no new errors/warnings
- [ ] Confirm data integrity (no missing mappings)
- [ ] Check cascade behavior

---

## Key Design Decisions

1. **Denormalized Approach**
   - KEEP `acctid`, `bankid`, `intu_bid` in bi_transactions & bi_statements
   - ADD optional FK reference to BankAccountMapping
   - Benefit: Avoids JOIN overhead, maintains backward compatibility

2. **Lazy Loading**
   - `getBankAccountMapping()` methods load on-demand
   - Not automatically loaded with statement/transaction
   - Benefit: Performance, don't load unused data

3. **Unique Key Strategy**
   - Primary key: `(acctid, bankid, intu_bid)` combination
   - Ensures no duplicate mappings for same external ID
   - Allows NULL values (one or more can be empty)

4. **Factory Pattern**
   - Extract mapping logic from services
   - Normalize OFX identifiers (trim, uppercase, etc)
   - Make it testable and reusable

5. **Repository Pattern**
   - Abstract database access
   - Single source of truth for mapping queries
   - Easy to replace with different storage later

---

## Performance Considerations

### Query Optimization
```php
// Use indexed lookups (acctid, bankid, intu_bid)
SELECT * FROM bi_bank_accounts 
WHERE acctid = ? AND bankid = ? AND intu_bid = ?
-- Index: UNIQUE KEY (acctid, bankid, intu_bid)

// Or batch look up multiple mappings
SELECT * FROM bi_bank_accounts 
WHERE (acctid, bankid) IN ((?, ?), (?, ?), ...)
-- Index: KEY (bankid, acctid)
```

### Caching Strategy
- Cache BankAccountMapping by OFX identifiers in memory
- Invalidate cache when upsert occurs
- Consider Redis for multi-request caching

### Lazy Loading
- Don't join BankAccountMapping on every query
- Load only when accessed via `getBankAccountMapping()`

---

## Rollback Plan

If migration causes issues:

1. **Keep bi_bank_accounts table** ← data is still stored here
2. **Keep acctid/bankid in bi_transactions, bi_statements** ← old fields still exist
3. **Disable BankAccountMapping usage** ← comment out repository calls
4. **Revert to bi_bank_accounts_model** ← fallback to original implementation

No data loss, clean rollback possible.

---

## Related Files

- [BankAccountMapping.php](app/Shared/Entities/BankAccountMapping.php) - New entity ✅
- [class.bi_bank_accounts.php](class.bi_bank_accounts.php) - Legacy model (keep as-is for now)
- [class.bi_statements.php](class.bi_statements.php) - Will add getter
- [class.bi_transactions.php](class.bi_transactions.php) - Will add getter
- [import_statements.php](import_statements.php) - Update pipeline
- [Services/TransferMatchService.php](Services/TransferMatchService.php) - Update to use repository

---

## Success Metrics

✅ **Phase 1 Complete** (✅ BankAccountMapping entity created)  
⏳ **Phase 2 In Progress** (Creating repository/factory next)  
⏳ **Phase 3 Pending** (Update import pipeline)  
⏳ **Phase 4 Pending** (Update services)  
⏳ **Phase 5 Pending** (Add cross-references)  
⏳ **Testing** (Validate everything works)  

After all phases:
- [ ] All bank account mappings centralized in one entity
- [ ] Services use repository instead of scattered lookups
- [ ] Cross-references work bidirectionally
- [ ] Backward compatibility maintained
- [ ] All tests pass
- [ ] No performance regression

