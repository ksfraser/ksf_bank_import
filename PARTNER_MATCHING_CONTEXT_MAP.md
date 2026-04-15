# Partner Matching Architecture - Context Map

## Task
Understand and fix the partner matching system. The current implementation incorrectly matches by partner type (Supplier/Customer/Bank Transfer). The correct approach: Match by TRANSACTION PATTERN against a single centralized table, learning from past matches.

## Current Understanding from User
- Single table (`bi_partners_data`) should be primary scoring source
- Key insight: Match patterns, not partner types
- Examples of patterns:
  - "Pre-Auth Debit;Group benefit" → Quick Entry #2
  - "Internet Transfer" → Bank Transfer account match
  - "E-Transfer" → Could be send/receive; receiving often has customer name
- Learning: Once processed, occurrence_count improves future matches
- Challenge: Customer name from bank ≠ how we store it, but pattern should learn

## Files to Examine

### Core Matching Logic
| File | Purpose | Current Implementation |
|------|---------|------------------------|
| `pdata.inc` | Partner data CRUD | Simple LIKE search on `data` column - WRONG |
| `includes/search_partner_keywords.inc` | Keyword scoring | Has clustering bonus, occurrence weighting - GOOD |
| `PROD/class.ViewBiLineItems.php` | UI display | Calls `displaySupplierPartnerType()`, `displayCustomerPartnerType()`, `displayBankTransferPartnerType()` separately - WRONG APPROACH |
| `class.bi_lineitem.php` | Line item model | Contains `setPartnerType()` logic |
| `class.bi_partners_data.php` | Data model/schema | Defines table structure |

### Database Tables
| Table | Purpose | Columns |
|-------|---------|---------|
| `bi_partners_data` | **PRIMARY**: Pattern → Partner mapping | partner_id, partner_detail_id, partner_type, **data** (PATTERN!), occurrence_count, updated_ts |
| `bi_lineitem` | Imported transactions | otherBankAccount, otherBankAccountName, transactionTitle, memo, transactionDC, amount, matching_trans[] (GLs) |
| `gl_trans` | GL journal entries | Used for matching_trans scoring |
| Bank/Supplier/Customer/QE master tables | Partner master data | Provides list of valid partner IDs |

### GL Matching Integration
| File | Purpose | Relevance |
|------|---------|-----------|
| `src/Ksfraser/FaBankImport/models/MatchingJEs.php` | Finds matching GL entries | Returns array with scores; should integrate with pattern matching |

## Current (Wrong) Architecture

```
Transaction arrives
    ↓
setPartnerType() determines DC (Debit/Credit)
    ↓
displayPartnerType() switches on type
    ├─ Case 'SP' (Supplier) → search_partner_by_bank_account(PT_SUPPLIER, account)
    ├─ Case 'CU' (Customer) → search_partner_by_bank_account(PT_CUSTOMER, account)
    ├─ Case 'BT' (Bank Transfer) → search_partner_by_bank_account(ST_BANKTRANSFER, account)
    └─ Case 'QE' (Quick Entry) → custom logic
    ↓
Each search does: SELECT * FROM bi_partners_data 
                   WHERE partner_type = X 
                   AND data LIKE '%account%'
                   LIMIT 1
    ↓
Returns first match (no scoring)
    ↓
User must correct ❌ 80% of the time
```

**Problem**: Assumes partner type FIRST, searches within that type. But the data pattern should determine type!

## Correct Architecture (What User Describes)

```
Transaction Pattern Available:
├── otherBankAccount (IBAN)
├── otherBankAccountName (Counterparty)
├── transactionTitle (Memo)
├── transactionDC (D/C)
├── amount
├── valueTimestamp
└── matching_trans[] (GL matches with scores)

Step 1: Pattern Extraction
└─ Combine: [otherBankAccountName + transactionTitle + memo]
└─ Extract keywords

Step 2: Search bi_partners_data (NO TYPE FILTER YET!)
└─ SELECT * FROM bi_partners_data 
   WHERE data LIKE '%pattern%'
   ORDER BY occurrence_count DESC
   (All types searched together!)

Step 3: Score Results (Multi-Factor)
├─ Base score = occurrence_count (learned frequency)
├─ Keyword bonus = clustering factor
├─ GL score integration
├─ Type-specific adjustments

Step 4: Return Best Match Across ANY Type
└─ Result: { partner_id, partner_type, confidence_score }
   (Could be QE, Bank Transfer, Customer, Supplier - doesn't matter!)

Step 5: Auto-Select if Confidence ≥ Threshold
└─ Set partner type accordingly in UI

Step 6: Learn from User Selection
└─ On save: UPDATE bi_partners_data
   SET occurrence_count = occurrence_count + 1
   WHERE partner_id = X AND partner_type = Y
   (Improves next time!)
```

## Key Differences

| Aspect | Current (Wrong) | Correct (Proposed) |
|--------|-----------------|-------------------|
| **Search Scope** | One partner type at a time | ALL partner types simultaneously |
| **Primary Key** | Partner type (assumed first) | Transaction pattern (data column) |
| **Scoring** | None (LIMIT 1) | Multi-factor (occurrence, keywords, GL, context) |
| **Learning** | No learning | Updates occurrence_count on successful match |
| **Example** | "Internet Transfer" → Search only in ST_BANKTRANSFER | "Internet Transfer" → Searches all types, finds it learned from before |

## Implementation Files That Need Changes

### Phase 1: Refactor Search Logic
- [ ] Create `PatternMatcher.php` - unified pattern-based search (replaces type-specific searches)
- [ ] Update `pdata.inc` - wrap search_partner_by_keywords() for pattern matching
- [ ] Deprecate `search_partner_by_bank_account()` - was type-specific

### Phase 2: Refactor Display Logic
- [ ] Update `PROD/class.ViewBiLineItems.php::displayPartnerType()`
  - Don't switch on type first
  - Call unified matcher before display_right() completes
  - Auto-select if confidence >= threshold
  - Show suggestions if confidence < threshold

### Phase 3: Add Learning
- [ ] Track user selections in `process_statements.php`
- [ ] On save: Call `update_partner_occurrence_count()`
- [ ] Update `bi_partners_data.occurrence_count`

### Phase 4: Integrate Special Cases
- [ ] Detect Interest patterns → QE Interest (Earned/Paid)
- [ ] Detect "E-Transfer" patterns → Bank Transfer with customer context
- [ ] GL match integration → Auto-suggest if score >= 80

## Critical Questions to Verify

1. **Is `bi_partners_data.data` field intended to store transaction PATTERNS?**
   - What format? Full strings? Keywords? Regex patterns?
   - How are patterns entered? Manual? Auto-learned?

2. **When matching, should we search ALL types or filter by DC (Debit/Credit)?**
   - Debit typically: Supplier or Bank Transfer or Interest Paid
   - Credit typically: Customer or Bank Transfer or Interest Earned
   - But not a hard constraint?

3. **GL Matching Integration:**
   - Current: `matching_trans[]` calculated but unused for partner selection
   - Proposed: Use GL score to auto-suggest "Partner Match" type
   - Correct threshold? 80? 70?

4. **E-Transfer Handling:**
   - User said: "E-Transfer can be send or receive; receiving often has customer name"
   - Logic: If Credit + E-Transfer + has name in pattern → Try Customer first, then QE
   - If Debit + E-Transfer → Try Bank Transfer?

5. **Learning Mechanism:**
   - Should UI show confidence score to user?
   - Should user be able to override auto-selection?
   - When does occurrence_count increment? On user confirm? Auto-save only?

## Dependencies & Impact Analysis

### Files That Call Partner Search
- `PROD/class.ViewBiLineItems.php` - 3 calls (line 453, 469, 514)
- `class.transactions_table.php` - 3 calls
- Tests (if any) - need to verify

### Files That Build Partner Data
- `build_partner_keyword_data.php` - how does this populate `data` column?
- Manual UI entry? - where?

### Breaking Changes Risk
- ⚠️ `search_partner_by_bank_account()` function signature may need to change
- ⚠️ UI logic in `displayPartnerType()` needs refactoring
- ⚠️ Data in `bi_partners_data` may need migration (if format of `data` changes)

## Recommended Next Steps

1. **Clarify Pattern Format**: What exactly goes in `bi_partners_data.data`?
2. **Examine build_partner_keyword_data.php**: How are patterns currently populated?
3. **Trace a real transaction**: Walk through matching logic to see actual data
4. **Verify learning mechanism**: Should occurrence_count be incremented? When?
5. **Test GL integration**: Does GL score data exist for all transactions?
