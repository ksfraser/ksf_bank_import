# Transaction Data Field Sources

**Reference:** [DUPLICATE_DETECTION_REFINED_SIMPLIFIED.md](DUPLICATE_DETECTION_REFINED_SIMPLIFIED.md)

---

## Key Fields for Duplicate Detection

All fields for fuzzy matching come **directly from parser output** - NO concatenation:

| Field | Source | Type | Example | Used in |
|-------|--------|------|---------|----------|
| **valueTimestamp** | Bank CSV date field | DATE | 2025-01-15 | Level 2 (exact match) |
| **transactionAmount** | Bank CSV amount field | DOUBLE | 45.99 | Level 2 (±$0.01) |
| **merchant** | Bank CSV description → Parser extraction | VARCHAR | SHOPPERS DRUG MART | Level 2 + Whitelist |
| **memo** | Bank CSV memo field or parsed from description | VARCHAR | Pharmacy purchase | Level 2 |
| **accountName** | Bank CSV account name | VARCHAR | Chequing Account | Alternative to merchant |
| **transactionCode** | Bank CSV transaction/reference ID | VARCHAR | RBC-001 | Level 1 (authoritative) |
| **acctid** | Bank account identifier | VARCHAR | ACC123 | Level 1 + Level 2 key |

---

## Parser Flow: Where Merchant Originates

### Step 1: Bank CSV Format

```
Date,Description,Amount,Balance
2025-01-15,"SHOPPERS DRUG MART 1234 TORONTO ON","45.99",1000.00
```

### Step 2: Parser Extracts

```php
// In parser.parse() method
$csv_fields = [
    'date' => '2025-01-15',
    'description' => 'SHOPPERS DRUG MART 1234 TORONTO ON',  // ← Raw
    'amount' => 45.99
];

// Parser populates transaction object:
$transaction->valueTimestamp = '2025-01-15';
$transaction->transactionAmount = 45.99;
$transaction->merchant = 'SHOPPERS DRUG MART 1234 TORONTO ON';  // ← From description field
$transaction->memo = 'Drug store purchase';  // ← May be derived or from separate field
$transaction->acctid = 'ACC123';             // ← Bank account ID
```

### Step 3: Stored in DB

```
INSERT INTO bi_transactions (
    valueTimestamp,
    transactionAmount,
    merchant,
    memo,
    acctid
) VALUES (
    '2025-01-15',
    45.99,
    'SHOPPERS DRUG MART 1234 TORONTO ON',  ← Single source: parser extraction
    'Drug store purchase',
    'ACC123'
);
```

---

## NO Concatenation

**Important:** `merchant` field is:
✅ **Direct extraction from parser** (not concatenated)  
❌ **NOT** constructed from multiple fields  
❌ **NOT** a combination of description + category  

---

## Parser Examples by Bank

### RBC CSV Parser

```php
// File: includes/ro_bcr_csv_parser.php
// Extracts: description field becomes merchant

$trz->transactionTitle1 = $f[12];  // Description from field 12
// Later stored as merchant in bi_transactions
```

### BMO / TD CSV Parser

```php
// Similar pattern
$description = $row['Description'];  // "LOBLAW'S #1234 TORONTO"
$trz->merchant = $description;
```

### Bank-Specific Variations

Some banks provide separate fields:

```php
// CIBC Example (hypothetical)
$trz->merchant = $row['Merchant'];        // "SHOPPERS"
$trz->accountName = $row['Description'];  // "Store #1234"

// For duplicate detection:
// - Use: $transaction['merchant'] = "SHOPPERS"
// - Also check: $transaction['accountName'] in fuzzy query
```

---

## Fuzzy Match Query: Using Actual Parser Fields

```php
// Query uses exact fields as stored by parser, no concatenation:
SELECT * FROM bi_transactions
WHERE acctid = :acctid                    // From bank record
  AND valueTimestamp = :date              // Exact date (no window)
  AND ABS(transactionAmount - :amount) < 0.01
  AND (
      merchant = :merchant                // Direct from parser
      OR memo = :memo                     // Direct from parser
      OR accountName = :accountName       // Alternative merchant source
  )
```

---

## Important: ±Amount Tolerance

**Why ±$0.01?**

```
Scenario: Fee charged separately

CSV Entry 1:
  Transaction: $100.00 (purchase)
  Merchant: SHOPPERS

CSV Entry 2 (hours later):
  Fee: $0.50 (bank fee on purchase)
  Merchant: BANK CHARGE

These should NOT match as duplicates (different merchants, separated entries).

Tolerance of $0.01 only handles:
- Rounding differences (floating point)
- Currency conversion micro-adjustments
- NOT intended to absorb fees
```

---

## No Date Window Justification

### Scenario 1: Re-Download (Same Statement)

```
Download 1 @ 2025-01-15:
  TX: date=2025-01-15, code=RBC-001

Download 2 @ 2025-01-16 (re-download, same file):
  TX: date=2025-01-15, code=RBC-002  ← Code changed, but DATE is identical

Level 2 Query:
  WHERE valueTimestamp = 2025-01-15  ← Exact match, no window needed
```

**Result:** Fuzzy match finds original (RBC-001) because dates are identical ✓

### Scenario 2: Transaction Timing Differences

```
Bank posts transaction with:
  Processing date: 2025-01-15
  Value date: 2025-01-15

Statement Download 1:
  Shows date: 2025-01-15

Statement Download 2 (different statement, same TX):
  Shows date: 2025-01-15 (bank doesn't change it)
```

**Result:** Exact date match, no window needed ✓

### Why NOT a Date Window

**Counterargument:** "What if processing delays shift the date?"

**Answer:**
1. Banks typically show VALUE DATE (consistent)
2. If date shifts, it's a DIFFERENT transaction line item
3. For cross-statement reconciliation = different flow (not this dedup)
4. Window would create false positives (legitimate same-day repeats blocked)

---

## Data Validation

Before Level 2 fuzzy match, validate:

```php
// In DuplicateDetectionService::detect()

if (empty($transaction['valueTimestamp'])) {
    return DuplicateCheckResult::notDuplicate();  // Can't match without date
}

if (empty($transaction['acctid'])) {
    return DuplicateCheckResult::notDuplicate();  // Can't match without account
}

// At least one of: merchant, memo, accountName must exist
if (empty($transaction['merchant']) 
    && empty($transaction['memo'])
    && empty($transaction['accountName'])) {
    return DuplicateCheckResult::notDuplicate();  // Can't fuzzy match
}
```

---

## Summary

✅ **Merchant field** = Direct parser extraction (single source)  
✅ **Date matching** = Exact (no window needed, re-downloads have identical dates)  
✅ **Amount tolerance** = ±$0.01 (rounding only, not fees)  
✅ **Fallback options** = memo or accountName if merchant missing  
✅ **Whitelist rules** = Only apply at Level 2 (fuzzy detection)  
✅ **Level 1** = Absolute (transactionCode + acctid, no override)  

---

## Next: Implementation

See [ROBUST_DUPLICATE_DETECTION_DESIGN.md](ROBUST_DUPLICATE_DETECTION_DESIGN.md) for:
- Class implementation
- Database schema
- Integration with TransactionValidator
- Unit test cases
