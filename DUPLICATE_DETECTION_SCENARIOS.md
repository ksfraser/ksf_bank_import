# Duplicate Detection: Scenario Analysis

**Reference:** [ROBUST_DUPLICATE_DETECTION_DESIGN.md](ROBUST_DUPLICATE_DETECTION_DESIGN.md)

---

## Scenario 1: RBC Statement Re-Download (Different Code)

### Situation
User downloads statement on Jan 15 → gets transaction:
```
transactionCode: "RBC-001"
acctid: "ACC123"
date: "2025-01-15"
amount: $500.00
merchant: "PAYROLL ADVANCE"
memo: "Direct deposit"
```

User re-downloads same statement on Jan 16 (bank updated file) → same transaction BUT:
```
transactionCode: "RBC-002"  ← NEW CODE!
acctid: "ACC123"
date: "2025-01-15"
amount: $500.00
merchant: "PAYROLL ADVANCE"
memo: "Direct deposit"
```

### Current Behavior ❌
```
Level 1: transactionCode (RBC-002) + acctid (ACC123)
Result: NOT FOUND (different code)
→ Imported TWICE ❌ Data corruption!
```

### With New Design ✅
```
Level 1: transactionCode (RBC-002) + acctid (ACC123)
Result: NOT FOUND (code changed)

Level 2: Fuzzy match
- Window: Jan 13-17 (±3 days)
- Amount: $500 (exact)
- Merchant: "PAYROLL ADVANCE" (exact)
- Found: 1 existing transaction ✓

Level 3: Whitelist rules
- Rule: PAYROLL_RECURRING (action: EXACT_DUPLICATE_ONLY)
- Decision: Block (must be exact code match for payroll)
  
RESULT: ⚠️ AMBIGUOUS → Show user UI
"Possible duplicate payroll found on 2025-01-15 ($500) - approve or skip?"
User confirms: Skip (it's the same deposit)
→ Imported ONCE ✅ Data integrity maintained!
```

**Action Taken:** User reviews and decides, system logs decision

---

## Scenario 2: Shoppers Multiple Same-Day Purchases

### Situation
Same day shopping trip with multiple purchases:
```
Transaction 1:
  date: "2025-01-15"
  amount: $45.99
  merchant: "SHOPPERS"
  memo: "Pharmacy"

Transaction 2 (later same day):
  date: "2025-01-15"
  amount: $45.99
  merchant: "SHOPPERS"
  memo: "Pharmacy"

Transaction 3 (different amount, same day):
  date: "2025-01-15"
  amount: $23.50
  merchant: "SHOPPERS"
  memo: "Cosmetics"
```

### Current Behavior ⚠️
```
Transaction 1: No match → IMPORT ✓
Transaction 2: transactionCode + acctid check
  May or may not match (bank-dependent)
  Depends on if bank gives unique codes
→ Possibly blocked as duplicate ❌
```

### With New Design ✅
```
Transaction 1:
  Level 1: No exact code match → proceed to Level 2
  Level 2: Check for same date/amount/merchant
  Result: No prior match → IMPORT ✓

Transaction 2:
  Level 1: Different transactionCode → proceed to Level 2
  Level 2: Fuzzy match = Transaction 1
  Level 3: Whitelist check
    - Merchant: SHOPPERS
    - Rule: SHOPPERS_RETAIL (action: ALLOW_DUPLICATES)
    - Decision: ✅ ALLOWED REPEAT
  → IMPORT ✓ Logged as "allowed repeat per rule"

Transaction 3:
  Level 1: Different code → proceed
  Level 2: Different amount ($23.50 vs $45.99)
    - No fuzzy match
  → IMPORT ✓
```

**Result:** ✅ All 3 purchases imported, no false duplicates!

**Audit Trail:**
```
TX-001: IMPORT (new)
TX-002: IMPORT (allowed repeat, rule: SHOPPERS_RETAIL)
TX-003: IMPORT (new, different amount)
```

---

## Scenario 3: Manulife Cross-Account Same Code

### Situation
Same transactionCode used in different accounts:
```
Account 1 (Chequing):
  transactionCode: "INT-DEPOSIT-001"
  acctid: "CHQ123"
  amount: $25.00
  date: "2025-01-15"

Account 2 (Savings):
  transactionCode: "INT-DEPOSIT-001"  ← SAME CODE!
  acctid: "SAV456"
  amount: $45.00
  date: "2025-01-15"
```

### Current Behavior ✅
```
Account 1: Level 1 check
  WHERE transactionCode = "INT-DEPOSIT-001" AND acctid = "CHQ123"
  → No match, IMPORT ✓

Account 2: Level 1 check
  WHERE transactionCode = "INT-DEPOSIT-001" AND acctid = "SAV456"
  → No match, IMPORT ✓
```
Works correctly (acctid in key prevents false positive)

### With New Design ✅
```
Same as current - acctid is part of Level 1 key
→ Works perfectly
```

**Result:** ✅ Correctly imports both, no false duplicates

---

## Scenario 4: Unusual Repeat - Same Amount, Date, Merchant, but Different Code

### Situation
```
Transaction A:
  transactionCode: "ABC-123"
  date: "2025-01-15"
  amount: $75.00
  merchant: "CANADIAN TIRE"
  memo: "Gas + oil change"

Transaction B (duplicate, but different code):
  transactionCode: "ABC-124"  ← Different!
  date: "2025-01-15"
  amount: $75.00
  merchant: "CANADIAN TIRE"
  memo: "Gas + oil change"
```

### Current Behavior ❌
```
Trans B Level 1: transactionCode (ABC-124) vs stored (ABC-123)
→ No match → IMPORT TWICE ❌
```

### With New Design ✅
```
Trans B Level 1: NOT FOUND (different code)

Trans B Level 2: Fuzzy match
  - Date: 2025-01-15 (match)
  - Amount: $75.00 (match)
  - Merchant: CANADIAN TIRE (match)
  - Memo: "Gas + oil change" (match)
  - Found: Trans A ✓

Trans B Level 3: Whitelist check
  - Merchant: CANADIAN TIRE
  - No specific rule for this merchant
  - Decision: ⚠️ AMBIGUOUS (1 fuzzy match, no rule)

RESULT: Show user UI
"Duplicate detected - Transaction B looks like Transaction A
 (Canada Tire, $75, same day). Skip or import?"
 
User confirms: "Skip, it's the same transaction"
→ Imported ONCE ✅
```

**Action:** User reviews, confirms, decision logged

---

## Scenario 5: Legitimate Different Transactions, Same Amount

### Situation
```
Transaction A:
  date: "2025-01-15"
  amount: $35.00
  merchant: "STARBUCKS"
  
Transaction B (legitimate different purchase):
  date: "2025-01-15"
  amount: $35.00
  merchant: "STARBUCKS"
```

### Current Behavior ⚠️
```
Depends on bank codes - could go either way
```

### With New Design ✅
```
Trans A:
  Level 1: No match → proceed
  Level 2: No prior transactions → IMPORT ✓

Trans B:
  Level 1: Different code (likely) → proceed
  Level 2: Fuzzy match = Trans A
    (same date, amount, merchant)
  Level 3: Whitelist check
    - Merchant: STARBUCKS
    - Rule: exists with action: ALLOW_DUPLICATES
    - Decision: ✅ ALLOWED REPEAT
  → IMPORT ✓ Logged: "allowed repeat per COFFEE_REPEATS rule"
```

**Result:** ✅ Both imported, legitimate purchases

---

## Summary Table

| Scenario | Current | New Design | Benefit |
|----------|---------|-----------|---------|
| **RBC re-download** | ❌ Duplicate | ✅ Fuzzy + User review | Catches code-change dupes |
| **Shoppers repeats** | ⚠️ Maybe blocked | ✅ Whitelist allows | False positives eliminated |
| **Manulife cross-account** | ✅ Works | ✅ Still works | No regression |
| **Unknown repeats** | ❌ Duplicate | ⚠️ User review | Manual oversight available |
| **Legitimate same-amount** | ⚠️ Depends | ✅ Whitelist allows | Predictable behavior |

---

## Configuration Examples

### For Users with RBC (High re-download risk)

```sql
INSERT INTO 0_bi_duplicate_rules VALUES
(NULL, 'RBC_FREQUENT_REDOWNLOAD', NULL, NULL, 'REQUIRE_REVIEW',
 'RBC updates files frequently - always show duplicates for manual review', 1, NOW(), 1);

-- This ensures ALL potential RBC duplicates go to user review, not auto-skipped
```

### For Users with Multiple Shoppers Locations

```sql
INSERT INTO 0_bi_duplicate_rules VALUES
(NULL, 'CANADIAN_RETAIL_CHAINS', 'SHOPPERS%|WALMART%|COSTCO%', 'RETAIL', 'ALLOW_DUPLICATES',
 'Multiple shopping locations and same-day runs are common', 1, NOW(), 1);
```

### For Subscriptions (Monthly, must be exact)

```sql
INSERT INTO 0_bi_duplicate_rules VALUES
(NULL, 'MONTHLY_SUBSCRIPTIONS', '%NETFLIX%|%SPOTIFY%|%ADOBE%', NULL, 'EXACT_DUPLICATE_ONLY',
 'Subscriptions occur monthly, but different transactionCodes indicate different charges', 1, NOW(), 1);
```

---

## Performance Implications

| Operation | Latency | Notes |
|-----------|---------|-------|
| Level 1 (Direct match) | ~2ms | Indexed query: `(transactionCode, acctid)` |
| Level 2 (Fuzzy match) | ~10-50ms | Depends on transaction volume |
| Level 3 (Whitelist rules) | ~1ms | In-memory cache, simple pattern matching |
| **Total Decision** | ~13-53ms | <100ms for nearly all cases |
| **Batch Processing** | N/A | 1000 tx = ~13-53 seconds ✅ Acceptable |

---

## Edge Cases Handled

✅ **Bank code changes mid-statement**  
✅ **Multiple legitimate purchases same merchant/amount**  
✅ **Cross-account identical codes**  
✅ **Subscriptions (monthly, should not be duped)**  
✅ **Wire transfers (one-time, should not be duped)**  
✅ **ATM withdrawals (often repeated)**  
✅ **Duplicate file uploads** (same statement, multiple times)  

---

## Next: Implementation

See [ROBUST_DUPLICATE_DETECTION_DESIGN.md](ROBUST_DUPLICATE_DETECTION_DESIGN.md) for:
- New class architecture
- Database schema
- Integration steps
- Testing strategy
