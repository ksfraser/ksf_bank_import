# Partner Matching Analysis & Improvement Plan

## Current State

### 1. **Current Matching Implementation**

**Location**: `pdata.inc` → `search_partner_by_bank_account()`

```php
function search_partner_by_bank_account($partner_type, $needle) {
    $sql = "SELECT * FROM ".TB_PREF."bi_partners_data
        WHERE partner_type=".db_escape($partner_type)." AND data LIKE '%".$needle."%' LIMIT 1";
    return db_fetch($sql);
}
```

**Issues**:
- ✗ Simple LIKE search on bank account only
- ✗ No scoring algorithm
- ✗ Returns first match (LIMIT 1)
- ✗ Can't handle multiple keywords
- ✗ Doesn't consider transaction context (title, memo, GL matches)
- ✗ No occurrence frequency weighting

### 2. **Current Usage Locations**

In `PROD/class.ViewBiLineItems.php`:

```php
// Line 453 - Supplier matching
displaySupplierPartnerType() {
    $matched_supplier = search_partner_by_bank_account(PT_SUPPLIER, $this->otherBankAccount);
}

// Line 469 - Customer matching  
displayCustomerPartnerType() {
    $match = search_partner_by_bank_account(PT_CUSTOMER, $this->otherBankAccount);
}

// Line 514 - Bank Transfer matching
displayBankTransferPartnerType() {
    $match = search_partner_by_bank_account(ST_BANKTRANSFER, $this->otherBankAccount);
}
```

### 3. **Available Enhanced Matching Functions** (Not Currently Used)

#### A. Keyword-Based Scoring: `search_partner_by_keywords()`

**Location**: `includes/search_partner_keywords.inc`

**Features**:
- ✓ Multi-keyword extraction from transaction text
- ✓ Occurrence count weighting
- ✓ Co-occurrence clustering bonus (configurable, default 0.2)
- ✓ Keyword coverage & score strength metrics
- ✓ Confidence scoring (0-100 scale)
- ✓ Returns sorted array of matches with scores

**Scoring Algorithm**:
```
Base Score = Sum of occurrence_count for each matching keyword

Clustering Bonus = base_score × (keyword_match_count - 1) × CLUSTERING_FACTOR
Final Score = base_score + clustering_bonus

Confidence = (keyword_coverage × 0.6) + (score_strength × 0.4)
```

**Example**:
```
Search: "Internet Domain Registration"
Keywords: [internet, domain, registration]

Match A (QE-Internet): internet(50) + domain(45) + registration(12) = 107 points
Match B (QE-Transfer): internet(30) + transfer(25) = 55 points  

Result: Match A wins (107 > 55, plus clustering bonus for 3 keywords)
```

#### B. OOP Implementation: `PartnerMatcher` Class

**Location**: `views/PartnerMatcher.php`

Methods:
- `searchByBankAccount(partnerType, bankAccount): array`
- `hasMatch(match): bool`
- `getPartnerId(match): int`
- `getPartnerDetailId(match): int`

## Problems to Solve

### 1. **Supplier Matching (Debits)**
- **Status**: Sometimes works ✓ / Sometimes doesn't ✗
- **Root Cause**: Only searching by bank account number
- **Solution**: Use transaction title + bank account with keyword scoring

### 2. **Customer Matching (Credits)**
- **Status**: Never identifies customer ✗
- **Root Cause**: Searching by bank account only; customers often don't match by account
- **Solution**: Search by transaction title/memo; add confidence threshold check

### 3. **Bank Transfer Matching**
- **Status**: Sets partner type but doesn't identify bank accounts ✗
- **Root Cause**: No account lookup against `gl_trans` or bank account master table
- **Solution**: Match transfer partner by account number with type validation

### 4. **Interest Transactions**
- **Status**: Not automatically matched ✗
- **Current**: Defaults to generic customer/supplier
- **Solution**: Check transaction title/memo for "Interest" keyword → auto-match to:
  - Credits: `QE Interest Earned`
  - Debits: `QE Interest Paid`

### 5. **GL Matching Integration**
- **Status**: Matching GLs calculated but not used for partner selection ✗
- **Current**: Displayed to user but no auto-selection logic
- **Solution**: When GL match score > threshold (e.g., 80), auto-set to Partner Match type

### 6. **Partners Table Not Fully Utilized**
- **Status**: `bi_partners_data` has occurrence_count but not used for scoring ✗
- **Solution**: Implement multi-factor scoring:
  1. Bank account match (high confidence if exact)
  2. Keyword scoring (titles, account names, GL matches)
  3. GL match score integration
  4. Special case handling (Interest, etc.)

---

## Architecture: Enhanced Partner Matching Flow

```
Transaction Data Available:
├── otherBankAccount (IBAN/account number)
├── otherBankAccountName (counterparty name)
├── transactionTitle (transaction memo)
├── transactionDC (Debit/Credit)
├── amount
├── Matching GLs[] (with scores 0-100)
└── valueTimestamp (date)

Matching Priority:
1. Special Cases Check
   └─ Interest? → QE Interest (Earned/Paid)
   
2. GL Matching Integration  
   └─ GL Score > 80? → Partner Match type + GL details
   
3. Multi-Factor Keyword Scoring
   ├─ Search bi_partners_data by keyword (title + account + memo)
   ├─ Apply occurrence weighting
   ├─ Apply clustering bonus
   └─ Return top N matches with confidence
   
4. Context-Specific Matching
   ├─ Debit: Try Supplier + Bank Transfer
   ├─ Credit: Try Customer + Quick Entry
   └─ Weights based on transaction characteristics
   
5. Threshold-Based Auto-Selection
   └─ If confidence ≥ auto_threshold (e.g., 75%) → Auto-select
       Otherwise → Display candidates for user selection
```

---

## Implementation Strategy

### Phase 1: Enhanced Supplier Matching (Debit Transactions)

**Goal**: Improve "sometimes works" → "usually works"

**Changes**:
1. Extract search text: `[otherBankAccountName, transactionTitle, memo]`
2. Call `search_partner_by_keywords(PT_SUPPLIER, $search_text, 5)`
3. Filter matches: `confidence >= 60%`
4. Return top match OR display candidates

**Files to Modify**:
- `PROD/class.ViewBiLineItems.php::displaySupplierPartnerType()`
- Create new service: `MatchingService.php`

### Phase 2: Enhanced Customer Matching (Credit Transactions)

**Goal**: "Never identifies" → ~80% auto-match

**Changes**:
1. Extract search text: `[otherBankAccountName, transactionTitle, memo]`
2. Call `search_partner_by_keywords(PT_CUSTOMER, $search_text, 5)`
3. If confidence ≥ 70% → auto-select + show branches
4. If confidence < 70% → display top candidates

**Special Handling**:
- Check for branch matches in `customer_list()`
- Auto-populate `partnerDetailId` if single branch

**Files to Modify**:
- `PROD/class.ViewBiLineItems.php::displayCustomerPartnerType()`
- `MatchingService.php`

### Phase 3: Special Cases (Interest & Other Fixed Patterns)

**Goal**: Auto-match known transaction types

**Implementation**:
```php
function matchSpecialCases($transactionTitle, $transactionDC) {
    if (preg_match('/interest/i', $transactionTitle)) {
        $qe_type = ($transactionDC == 'C') 
            ? QE_INTEREST_EARNED 
            : QE_INTEREST_PAID;
        return search_partner_by_bank_account(PT_QE, $qe_type);
    }
    
    if (preg_match('/bank\s+charge|fee/i', $transactionTitle)) {
        return search_partner_by_bank_account(PT_QE, QE_BANK_CHARGES);
    }
    
    // Add more special cases as needed
    return null;
}
```

**Files to Create**:
- `SpecialCasesMatcher.php`

### Phase 4: Bank Transfer Account Matching

**Goal**: Identify bank accounts for transfers

**Implementation**:
```php
function matchBankTransferAccount($otherBankAccount, $otherBankAccountName) {
    // Try exact account number match
    $match = db_query("
        SELECT bank_account_number, id 
        FROM " . TB_PREF . "bank_accounts 
        WHERE bank_account_number = " . db_escape($otherBankAccount)
    );
    
    if ($match) return db_fetch($match);
    
    // Try keyword matching on bank account names
    return search_partner_by_keywords(ST_BANKTRANSFER, $otherBankAccountName, 1);
}
```

**Files to Modify**:
- `PROD/class.ViewBiLineItems.php::displayBankTransferPartnerType()`

### Phase 5: GL Matching Integration

**Goal**: When GL matches high-confidence, auto-suggest Partner Match

**Implementation**:
```php
function getGLMatchScore($matching_trans_array) {
    if (empty($matching_trans_array)) return 0;
    
    // Sort by score DESC
    usort($matching_trans_array, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return $matching_trans_array[0]['score'] ?? 0;
}

// In display logic:
$gl_score = $this->getGLMatchScore($this->matching_trans);
if ($gl_score >= 80) {
    // Auto-suggest to Partner Match type
    // Set partnerType to 'MA' (Matched)
    $_POST['partnerType'][$this->id] = 'MA';
}
```

**Files to Modify**:
- `PROD/class.ViewBiLineItems.php::displayPartnerType()`

---

## Database Schema Support

### Current: `bi_partners_data` Table

```sql
CREATE TABLE bi_partners_data (
    partner_id INT(11) NOT NULL,
    partner_detail_id INT(11) NOT NULL,
    partner_type INT(11) NOT NULL,
    data VARCHAR(256),
    occurrence_count INTEGER,
    updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (partner_id, partner_detail_id, partner_type),
    KEY idx_partner_type_data (partner_type, data),
    KEY idx_occurrence_count (occurrence_count)
);
```

**Already Supports**:
- ✓ Occurrence count weighting
- ✓ Indexed by type for efficient filtering
- ✓ Multi-field unique constraint

**May Need**:
- New index on `data` for faster keyword searches
- Optional: statistics table for tuning clustering factor

---

## Configuration & Tuning Parameters

### Confidence Thresholds
```php
// Auto-select thresholds (adjust based on testing)
const CONFIDENCE_AUTO_SELECT_SUPPLIER = 75;     // % 
const CONFIDENCE_AUTO_SELECT_CUSTOMER = 70;     // %
const CONFIDENCE_AUTO_SELECT_BANKTRANSFER = 80; // %

// Minimum confidence to display (don't show low-scoring matches)
const CONFIDENCE_MIN_DISPLAY = 30; // %
```

### Keyword Clustering
```php
// Already configurable in search_partner_keywords.inc
const KEYWORD_CLUSTERING_FACTOR = 0.2; // 0.1-0.3 range
// Increase = more weight on multiple matching keywords
// Decrease = weight individual keyword strength more
```

### GL Match Score Threshold
```php
const GL_MATCH_THRESHOLD = 80; // 0-100 scale
// Above this → auto-suggest Partner Match type
```

---

## Testing Strategy

### Unit Tests Needed
1. `test_SpecialCasesMatcher_Interest()` - Interest keyword detection
2. `test_KeywordScoring_ClusteringBonus()` - Verify clustering math
3. `test_ConfidenceThreshold_AutoSelection()` - Threshold logic
4. `test_GLScoreIntegration_AutoSelect()` - GL match >= 80

### Integration Tests
1. Load test data with known partners
2. Run transactions through matching logic
3. Verify auto-selection rate ≥80% for customers
4. Verify GL match detection

### Manual Testing Checklist
- [ ] Supplier debit: Bank account + name match
- [ ] Customer credit: Name/memo match (no account)
- [ ] Interest credit: Auto-match to QE
- [ ] Interest debit: Auto-match to QE
- [ ] Bank transfer: Account number match
- [ ] High GL score: Show "Partner Match" option

---

## Files Affected

### To Create
- `MatchingService.php` - Unified matching logic
- `SpecialCasesMatcher.php` - Interest & special cases
- Tests in `tests/unit/` subdirectory

### To Modify
- `PROD/class.ViewBiLineItems.php` - displaySupplierPartnerType(), displayCustomerPartnerType(), displayBankTransferPartnerType()
- `PROD/process_statements.php` - Initialize matchers
- `pdata.inc` - May wrap search functions

### Existing (No Changes Needed)
- `includes/search_partner_keywords.inc` - Already has scoring ✓
- `bi_partners_data` table schema - Already supports occurrence_count ✓

---

## Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Supplier auto-match rate | ~40% | ≥80% |
| Customer auto-match rate | ~5% | ≥75% |
| Interest auto-detection | 0% | 100% |
| GL match integration | Not used | ≥50% of transactions |
| User manual selection needed | ~80% | ≤20% |

---

## Implementation Checklist

- [ ] Phase 1: Enhanced Supplier Matching
- [ ] Phase 2: Enhanced Customer Matching  
- [ ] Phase 3: Special Cases (Interest)
- [ ] Phase 4: Bank Transfer Account Matching
- [ ] Phase 5: GL Matching Integration
- [ ] Configuration tuning (thresholds)
- [ ] Comprehensive testing
- [ ] Documentation & deployment
