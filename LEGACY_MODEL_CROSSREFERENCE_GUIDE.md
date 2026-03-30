# Legacy Model Cross-Reference Implementation Guide

**Created:** 2026-03-30  
**Status:** Implementation Ready  
**Priority:** Medium (After Repository/Factory created)

---

## Overview

This document describes how to add cross-reference methods to the legacy `bi_statements_model` and `bi_transactions_model` classes to enable lookups of associated `BankAccountMapping` entities.

This approach maintains backward compatibility while gradually transitioning to the new architecture.

---

## Goals

1. ✅ Keep legacy models unchanged (they still work as before)
2. ✅ Add new methods for cross-reference access
3. ✅ Enable easy transition to new architecture
4. ✅ Avoid breaking existing code
5. ✅ Make it obvious which code to migrate

---

## Changes to bi_statements_model

### Add Import & New Methods

**Location:** `class.bi_statements.php`

**Add near top of class:**
```php
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;
use Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory;
use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;
```

### Method 1: Get Associated BankAccountMapping

**Add to class.bi_statements_model:**
```php
/**
 * Get the BankAccountMapping for this statement's OFX identifiers
 * 
 * This cross-references the statement's bankid/acctid/intu_bid to find
 * the associated BankAccountMapping entity.
 * 
 * @return BankAccountMapping|null The mapping or null if not found
 */
public function getBankAccountMapping(): ?BankAccountMapping
{
    if (!BankAccountMappingRepository::tableExists()) {
        return null;
    }

    return BankAccountMappingRepository::findByOFXIdentifiers(
        $this->bankid ?? null,
        $this->acctid ?? null,
        $this->intu_bid ?? null
    );
}

/**
 * Get the FA bank account ID for this statement's OFX identifiers
 * 
 * Shorthand for: getBankAccountMapping()->bank_account_id
 * 
 * @return int|null The FA bank account ID or null if not mapped
 */
public function getFABankAccountId(): ?int
{
    $mapping = $this->getBankAccountMapping();
    return $mapping ? $mapping->bank_account_id : null;
}

/**
 * Check if this statement has an associated FA bank account
 * 
 * @return bool True if statement is linked to a FA bank account
 */
public function hasFABankAccount(): bool
{
    $mapping = $this->getBankAccountMapping();
    return $mapping && $mapping->isLinkedToFAAccount();
}
```

### Method 2: Static Query by Mapping

**Add to class.bi_statements_model:**
```php
/**
 * Find all statements associated with a BankAccountMapping
 * 
 * @param int $mappingId The BankAccountMapping ID
 * @param int $limit Maximum results
 * @return array<bi_statements_model> Array of statements
 */
public static function findByBankAccountMappingId(int $mappingId, int $limit = 1000): array
{
    // Get the mapping to extract OFX identifiers
    $mapping = BankAccountMappingRepository::findById($mappingId);
    if (!$mapping) {
        return [];
    }

    // Query statements with matching identifiers
    $table = TB_PREF . 'bi_statements';
    
    $conditions = [];
    if ($mapping->acctid) {
        $conditions[] = "IFNULL(acctid,'')=" . db_escape($mapping->acctid);
    }
    if ($mapping->bankid) {
        $conditions[] = "IFNULL(bankid,'')=" . db_escape($mapping->bankid);
    }
    if ($mapping->intu_bid) {
        $conditions[] = "IFNULL(intu_bid,'')=" . db_escape($mapping->intu_bid);
    }

    if (empty($conditions)) {
        return [];
    }

    $whereClause = implode(' AND ', $conditions);
    $sql = "SELECT * FROM `{$table}`
            WHERE {$whereClause}
            LIMIT " . (int)$limit;

    $res = @db_query($sql, 'Could not find statements for mapping');
    if (!is_object($res)) {
        return [];
    }

    $statements = [];
    while ($row = db_fetch($res)) {
        if (is_array($row)) {
            $stmt = new self();
            // Populate statement object from $row
            foreach ($row as $key => $value) {
                if (property_exists($stmt, $key)) {
                    $stmt->$key = $value;
                }
            }
            $statements[] = $stmt;
        }
    }

    return $statements;
}
```

### Method 3: Extract & Create Mapping

**Add to class.bi_statements_model:**
```php
/**
 * Extract this statement's OFX identifiers into a BankAccountMapping
 * 
 * Useful during import to consolidate mapping data.
 * 
 * @param int|null $bankAccountId Optional FA bank account ID to link
 * @return BankAccountMapping|null The extracted mapping or null if no OFX IDs
 */
public function extractBankAccountMapping(?int $bankAccountId = null): ?BankAccountMapping
{
    return BankAccountMappingFactory::createFromStatement($this, $bankAccountId);
}

/**
 * Persist this statement's BankAccountMapping
 * 
 * Extracts OFX identifiers and stores in BankAccountMapping repository.
 * 
 * @param int|null $bankAccountId Optional FA bank account ID to link
 * @return int The BankAccountMapping ID (0 if unable to store)
 */
public function storeBankAccountMapping(?int $bankAccountId = null): int
{
    $mapping = $this->extractBankAccountMapping($bankAccountId);
    if (!$mapping) {
        return 0;
    }

    return BankAccountMappingRepository::upsert($mapping, $bankAccountId);
}
```

---

## Changes to bi_transactions_model

### Add Import & New Methods

**Location:** `class.bi_transactions.php`

**Add near top of class:**
```php
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;
use Ksfraser\FaBankImport\Shared\Factories\BankAccountMappingFactory;
use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;
```

### Method 1: Get Associated BankAccountMapping

**Add to class.bi_transactions_model (or bi_transaction):**
```php
/**
 * Get the BankAccountMapping for this transaction's OFX identifiers
 * 
 * Cross-references the transaction's bankid/acctid to find the
 * associated BankAccountMapping entity.
 * 
 * @return BankAccountMapping|null The mapping or null if not found
 */
public function getBankAccountMapping(): ?BankAccountMapping
{
    if (!BankAccountMappingRepository::tableExists()) {
        return null;
    }

    return BankAccountMappingRepository::findByOFXIdentifiers(
        $this->bankid ?? null,
        $this->acctid ?? null,
        $this->intu_bid ?? null
    );
}

/**
 * Get the FA bank account ID for this transaction's OFX identifiers
 * 
 * Shorthand for: getBankAccountMapping()->bank_account_id
 * 
 * @return int|null The FA bank account ID or null if not mapped
 */
public function getFABankAccountId(): ?int
{
    $mapping = $this->getBankAccountMapping();
    return $mapping ? $mapping->bank_account_id : null;
}

/**
 * Check if this transaction has an associated FA bank account
 * 
 * @return bool True if transaction is linked to a FA bank account
 */
public function hasFABankAccount(): bool
{
    $mapping = $this->getBankAccountMapping();
    return $mapping && $mapping->isLinkedToFAAccount();
}

/**
 * Get account type for this transaction (CHECKING, SAVINGS, etc)
 * 
 * @return string|null The account type or null if not found
 */
public function getAccountType(): ?string
{
    $mapping = $this->getBankAccountMapping();
    return $mapping ? $mapping->accttype : null;
}

/**
 * Get currency code for this transaction
 * 
 * @return string|null The currency code or null if not found
 */
public function getCurrencyCode(): ?string
{
    $mapping = $this->getBankAccountMapping();
    return $mapping ? $mapping->curdef : null;
}
```

### Method 2: Static Query by Mapping

**Add to bi_transactions_model:**
```php
/**
 * Find all transactions associated with a BankAccountMapping
 * 
 * @param int $mappingId The BankAccountMapping ID
 * @param int $limit Maximum results
 * @return array<bi_transactions_model> Array of transactions
 */
public static function findByBankAccountMappingId(int $mappingId, int $limit = 10000): array
{
    // Get the mapping to extract OFX identifiers
    $mapping = BankAccountMappingRepository::findById($mappingId);
    if (!$mapping) {
        return [];
    }

    // Query transactions with matching identifiers
    $table = TB_PREF . 'bi_transactions';
    
    $conditions = [];
    if ($mapping->acctid) {
        $conditions[] = "IFNULL(acctid,'')=" . db_escape($mapping->acctid);
    }
    if ($mapping->bankid) {
        $conditions[] = "IFNULL(bankid,'')=" . db_escape($mapping->bankid);
    }
    if ($mapping->intu_bid) {
        $conditions[] = "IFNULL(intu_bid,'')=" . db_escape($mapping->intu_bid);
    }

    if (empty($conditions)) {
        return [];
    }

    $whereClause = implode(' AND ', $conditions);
    $sql = "SELECT * FROM `{$table}`
            WHERE {$whereClause}
            ORDER BY valueTimestamp DESC
            LIMIT " . (int)$limit;

    $res = @db_query($sql, 'Could not find transactions for mapping');
    if (!is_object($res)) {
        return [];
    }

    $transactions = [];
    while ($row = db_fetch($res)) {
        if (is_array($row)) {
            $trans = new self();
            // Populate transaction object from $row
            foreach ($row as $key => $value) {
                if (property_exists($trans, $key)) {
                    $trans->$key = $value;
                }
            }
            $transactions[] = $trans;
        }
    }

    return $transactions;
}

/**
 * Find all transactions for a FA bank account
 * 
 * This queries all BankAccountMappings linked to the FA account,
 * then finds all transactions matching those OFX identifiers.
 * 
 * @param int $bankAccountId FA bank account ID
 * @param int $limit Maximum results
 * @return array<bi_transactions_model> Array of transactions
 */
public static function findByFABankAccountId(int $bankAccountId, int $limit = 10000): array
{
    if ($bankAccountId <= 0) {
        return [];
    }

    // Get all mappings for this FA account
    $mappings = BankAccountMappingRepository::findByFABankAccountId($bankAccountId);
    if (empty($mappings)) {
        return [];
    }

    // Build conditions for all OFX identifiers
    $table = TB_PREF . 'bi_transactions';
    $conditions = [];

    foreach ($mappings as $mapping) {
        $mapConditions = [];
        if ($mapping->acctid) {
            $mapConditions[] = "IFNULL(acctid,'')=" . db_escape($mapping->acctid);
        }
        if ($mapping->bankid) {
            $mapConditions[] = "IFNULL(bankid,'')=" . db_escape($mapping->bankid);
        }
        if ($mapping->intu_bid) {
            $mapConditions[] = "IFNULL(intu_bid,'')=" . db_escape($mapping->intu_bid);
        }

        if (!empty($mapConditions)) {
            $conditions[] = "(" . implode(' AND ', $mapConditions) . ")";
        }
    }

    if (empty($conditions)) {
        return [];
    }

    $whereClause = implode(' OR ', $conditions);
    $sql = "SELECT * FROM `{$table}`
            WHERE {$whereClause}
            ORDER BY valueTimestamp DESC
            LIMIT " . (int)$limit;

    $res = @db_query($sql, 'Could not find transactions for FA account');
    if (!is_object($res)) {
        return [];
    }

    $transactions = [];
    while ($row = db_fetch($res)) {
        if (is_array($row)) {
            $trans = new self();
            foreach ($row as $key => $value) {
                if (property_exists($trans, $key)) {
                    $trans->$key = $value;
                }
            }
            $transactions[] = $trans;
        }
    }

    return $transactions;
}
```

### Method 3: Extract & Create Mapping

**Add to bi_transactions_model:**
```php
/**
 * Extract this transaction's OFX identifiers into a BankAccountMapping
 * 
 * @param int|null $bankAccountId Optional FA bank account ID to link
 * @return BankAccountMapping|null The extracted mapping or null if no OFX IDs
 */
public function extractBankAccountMapping(?int $bankAccountId = null): ?BankAccountMapping
{
    return BankAccountMappingFactory::createFromTransaction($this, $bankAccountId);
}

/**
 * Persist this transaction's BankAccountMapping
 * 
 * Extracts OFX identifiers and stores in BankAccountMapping repository.
 * 
 * @param int|null $bankAccountId Optional FA bank account ID to link
 * @return int The BankAccountMapping ID (0 if unable to store)
 */
public function storeBankAccountMapping(?int $bankAccountId = null): int
{
    $mapping = $this->extractBankAccountMapping($bankAccountId);
    if (!$mapping) {
        return 0;
    }

    return BankAccountMappingRepository::upsert($mapping, $bankAccountId);
}
```

---

## Usage Examples After Adding Methods

### Example 1: Get FA Account from Statement
```php
$statement = new bi_statements_model();
$statement->get_statement($statement_id);

// NEW: Simple getter
$faAccountId = $statement->getFABankAccountId();
```

### Example 2: Get FA Account from Transaction
```php
$transaction = new bi_transactions_model();
$transaction->get_transaction($transaction_id);

// NEW: Get account ID
$faAccountId = $transaction->getFABankAccountId();
echo "Account Type: " . $transaction->getAccountType();
echo "Currency: " . $transaction->getCurrencyCode();
```

### Example 3: Find All Statements for a Mapping
```php
$mappingId = 42;

// NEW: Static query
$statements = bi_statements_model::findByBankAccountMappingId($mappingId);
foreach ($statements as $stmt) {
    echo "Statement: " . $stmt->id . "\n";
}
```

### Example 4: Find All Transactions for FA Account
```php
$faAccountId = 15;

// NEW: Static query
$transactions = bi_transactions_model::findByFABankAccountId($faAccountId);
foreach ($transactions as $trans) {
    echo $trans->getId() . ": " . $trans->transactionAmount . "\n";
}
```

### Example 5: Store Mapping During Import
```php
// In import_statements.php
$statement = new bi_statements_model();
$statement->loadStatement($data);

// NEW: Store the mapping
$mappingId = $statement->storeBankAccountMapping($fa_bank_account_id);
```

---

## Implementation Checklist

### For bi_statements_model
- [ ] Add use statements for BankAccountMapping classes
- [ ] Add `getBankAccountMapping()` method
- [ ] Add `getFABankAccountId()` method
- [ ] Add `hasFABankAccount()` method
- [ ] Add `findByBankAccountMappingId()` static method
- [ ] Add `extractBankAccountMapping()` method
- [ ] Add `storeBankAccountMapping()` method
- [ ] Add doc comments to all methods
- [ ] Test with existing code

### For bi_transactions_model
- [ ] Add use statements for BankAccountMapping classes
- [ ] Add `getBankAccountMapping()` method
- [ ] Add `getFABankAccountId()` method
- [ ] Add `hasFABankAccount()` method
- [ ] Add `getAccountType()` method
- [ ] Add `getCurrencyCode()` method
- [ ] Add `findByBankAccountMappingId()` static method
- [ ] Add `findByFABankAccountId()` static method
- [ ] Add `extractBankAccountMapping()` method
- [ ] Add `storeBankAccountMapping()` method
- [ ] Add doc comments to all methods
- [ ] Test with existing code

---

## Testing Strategy

### Unit Tests
```php
public function testGetBankAccountMapping()
{
    // Setup: Create statement with OFX IDs
    $statement = new bi_statements_model();
    $statement->bankid = '021000021';
    $statement->acctid = '123456789';
    
    // Create mapping in DB
    $mapping = BankAccountMappingFactory::createFromStatement($statement, 42);
    BankAccountMappingRepository::upsert($mapping, 42);
    
    // Test: Get mapping from statement
    $retrieved = $statement->getBankAccountMapping();
    $this->assertNotNull($retrieved);
    $this->assertEquals(42, $retrieved->bank_account_id);
}

public function testFindStatementsByMapping()
{
    $mappingId = 42;
    
    // Create test statements
    $stmt = new bi_statements_model();
    // ... populate and save
    
    // Test: Find by mapping
    $found = bi_statements_model::findByBankAccountMappingId($mappingId);
    $this->assertNotEmpty($found);
}
```

### Integration Tests
- Test import pipeline with new methods
- Verify backward compatibility
- Check query performance

---

## Performance Considerations

1. **Lazy Loading**: Cross-reference methods only load when called
2. **Caching**: Consider caching BankAccountMapping in statement/transaction objects
3. **Batch Operations**: For processing many statements/transactions, batch the queries

---

## Rollback Strategy

If issues arise:
1. Comment out the new methods
2. Keep the imports but unused
3. Legacy code continues to work via old methods
4. Easy to remove or reactivate

---

## Next Steps

1. ✅ Repository & Factory created
2. ✅ Entity updated with helpers
3. ⏳ Add these cross-reference methods to legacy models
4. ⏳ Integrate into import pipeline
5. ⏳ Update services
6. ⏳ Write tests
7. ⏳ Validate performance

---

**Questions?** See [BANKACCOUNTMAPPING_USAGE_GUIDE.md](BANKACCOUNTMAPPING_USAGE_GUIDE.md) for usage patterns.
