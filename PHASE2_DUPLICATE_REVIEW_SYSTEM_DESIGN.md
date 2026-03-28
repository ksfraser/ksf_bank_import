# Phase 2: Duplicate Review System - Architecture & Implementation Plan

## Overview
Phase 2 builds the user-facing duplicate review workflow and storage layer. It complements Phase 1's detection service by providing a staging area for fuzzy matches and a UI for user confirmation/rejection.

## Key Design Decisions

### 1. Refined Level 1 Matching: Exact Code + All Fields
**Change from Phase 1**: Level 1 now does a **full field comparison**

**Phase 1 Behavior** (Just code match):
- `(transactionCode + acctid)` match → Auto SKIP

**Phase 2 Behavior** (Code match + validate all fields):
- `(transactionCode + acctid)` match → Check all transaction fields
  - If **all fields identical** (date, amount, merchant, memo, reference, etc.) → True duplicate → **SKIP**
  - If **any field differs** → Data anomaly → **Move to `bi_transactions_dupe` for review**

**Rationale**:
- Same code + account + everything else = 100% duplicate, safe to skip
- Same code + account but different amount/merchant/memo = suspicious mismatch → User review needed
- Catches data corruption, re-transmission with updates, or system errors

**Field Comparison** (comparing flagged transaction to existing record):
- `valueTimestamp` (exact)
- `transactionAmount` (exact)
- `merchant` (exact)
- `memo` (exact)
- `reference` (exact)
- Other metadata: `accountName`, `transactionType` (logged if different)

### 2. Duplicate Review Storage
**Table**: `bi_transactions_dupe` (Staging area for fuzzy matches)
- **Full clone** of bi_transactions row structure
- Plus metadata: `matching_bi_transaction_id`, `fuzzy_score`, `rule_applied`, `status`, `reviewed_by`, `reviewed_at`
- Not moved into `bi_transactions` until user confirms

**Workflow**:
1. Import runs → Fuzzy match detected → INSERT into `bi_transactions_dupe` (stays in memory during import)
2. User reviews duplicates → Either:
   - `CONFIRMED_DUPE`: Mark as duplicate (stays in review table, not imported)
   - `REJECTED`: Not a duplicate → INSERT into `bi_transactions` + DELETE from `bi_transactions_dupe`
   - `PENDING`: Still reviewing (stays in `bi_transactions_dupe`)

### 3. UI Architecture

#### Screen 1: `review_duplicates.php` (Admin Dashboard)
- **Purpose**: Ongoing review of all flagged duplicates across all imports
- **Features**:
  - Status filter: Pending / Confirmed Dupes / All
  - Bank filter dropdown
  - Date range filter
  - Inline grid showing:
    - Flagged transaction (date, amount, merchant, reference)
    - Matched transaction (date, amount, merchant, reference)
    - Confidence score / matching rule applied
    - Buttons: "This is a dupe", "Not a dupe (Import)", "Review Later"
  - Bulk actions: "Confirm All", "Import All"
  - Whitelist suggestions: "Create rule to auto-allow SHOPPERS%"

#### Screen 2: Import Post-Review Tab (on import_statements.php)
- **Purpose**: Review flagged duplicates immediately after import completes
- **Features**: Same as above, but filtered to this import only
- **Flow**: Upload → Parse → Import (with fuzzy flags) → Show this tab → User confirms/rejects → Redirects to full review_duplicates.php

### 4. Status Workflow

| Status | Meaning | In _dupe Table? | In bi_transactions? | User Action |
|--------|---------|-----------------|-------------------|-------------|
| PENDING | Awaiting review | ✅ Yes | ❌ No | Review Later |
| CONFIRMED_DUPE | Is a duplicate | ✅ Yes | ❌ No | Marked as dupe |
| MOVED_TO_STATEMENT | Not a dupe, imported | ❌ No (deleted) | ✅ Yes | Clicked "Import" |
| REJECTED | Rejected by user | ❌ No (deleted) | ❌ No (discarded) | Clicked "Discard" |

---

## Database Schema

### Table: `bi_transactions_dupe` (Full clone + metadata)
```sql
CREATE TABLE `0_bi_transactions_dupe` (
  `id`                          INT(11)           NOT NULL AUTO_INCREMENT,
  -- Transaction clone (full bi_transactions row)
  `smt_id`                      INT(11)           NOT NULL,
  `transactionCode`             VARCHAR(64)       NOT NULL,
  `acctid`                      VARCHAR(32)       NULL,
  `bankid`                      VARCHAR(16)       NULL,
  `intu_bid`                    VARCHAR(16)       NULL,
  `valueTimestamp`              DATETIME          NULL,
  `transactionAmount`           DECIMAL(19,4)     NULL,
  `transactionType`             VARCHAR(32)       NULL,
  `merchant`                    VARCHAR(255)      NULL,
  `memo`                        VARCHAR(255)      NULL,
  `accountName`                 VARCHAR(100)      NULL,
  `reference`                   VARCHAR(100)      NULL,
  -- Duplicate detection metadata
  `matching_bi_transaction_id`  INT(11)           NOT NULL COMMENT 'ID of transaction in bi_transactions this matches',
  `match_type`                  ENUM('EXACT_CODE_MISMATCH', 'FUZZY_MATCH') NOT NULL DEFAULT 'FUZZY_MATCH' COMMENT 'Was this flagged due to code match with field diff, or fuzzy match?',
  `fields_that_differ`          VARCHAR(255)      NULL COMMENT 'Comma-separated list of fields that differ from original (e.g., "memo,amount") - calculated at INSERT time',
  `fuzzy_match_score`           DECIMAL(5,2)      NULL COMMENT 'Confidence score 0-100 (for FUZZY_MATCH only)',
  `fuzzy_match_rule`            VARCHAR(100)      NULL COMMENT 'Rule that matched (e.g., SHOPPERS%, PAYROLL%)',
  `fuzzy_match_fields`          VARCHAR(255)      NULL COMMENT 'JSON or CSV: which fields matched (date, amount, merchant)',
  -- Review tracking
  `status`                      ENUM('PENDING', 'CONFIRMED_DUPE', 'MOVED_TO_STATEMENT', 'REJECTED') NOT NULL DEFAULT 'PENDING',
  `reviewed_by`                 VARCHAR(100)      NULL COMMENT 'FA user who reviewed this',
  `reviewed_at`                 TIMESTAMP         NULL,
  `review_notes`                TEXT              NULL,
  `created_at`                  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_matching_bi_transaction_id` (`matching_bi_transaction_id`),
  KEY `idx_acctid` (`acctid`),
  KEY `idx_bankid` (`bankid`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_matching_bi_transaction` FOREIGN KEY (`matching_bi_transaction_id`) REFERENCES `0_bi_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Implementation Phases

### Phase 2.1: Schema & Detection Service Update
**Deliverables**:
- Create `bi_transactions_dupe` table with indexes
- Update `DirectCodeMatcher.php` to perform full field comparison on code match
- Update `DuplicateCheckResult.php` to track field differences
- Update `DuplicateDetectionService.php` to handle field mismatch as review-needed
- Write tests for field comparison logic

**Dependencies**: Phase 1 complete

### Phase 2.2: Import Integration
**Deliverables**:
- Update `DirectCodeMatcher.php` to perform full field comparison 
- Update `import_statements.php` to call detection service
- Handle 4 outcomes: exact match → skip | code match with field diff → dupe table | fuzzy match → dupe table | no match → import
- Add "flagged for review" counter to import summary
- Update `importStatement()` to return review statistics

**Key Changes**:
```php
// In importStatement() transaction loop:
$checkResult = $duplicateDetectionService->detect($transactionData);

if ($checkResult->shouldSkip()) {
    // Level 1: Code + acctid + ALL FIELDS match - exact duplicate
    $dupecount++;
} elseif ($checkResult->needsReview()) {
    // Two cases here:
    // A) Code + acctid match but fields differ (Level 1 field mismatch)
    // B) Fuzzy match (Level 2) - flagged for review
    // Either way: store in dupe table
    $this->storeFuzzyMatchForReview($transactionData, $checkResult->getMatches()[0]);
    $pendingReviewCount++;
} else {
    // Not a duplicate - import normally
    $bit->hand_insert_sql();
    $newinserted++;
}
```

### Phase 2.3: Review UI - Dashboard (`review_duplicates.php`)
**Deliverables**:
- View class: `DuplicateReviewView.php`
- Filter logic matching `process_statements.php` pattern (4 filters)
- Pagination implementation (X items per page with prev/next)
- Inline grid showing transaction pairs with field differences highlighted
- AJAX handlers for confirm/reject/import actions
- Bulk action support

**Filter Inputs** (Match process_statements.php):

| Filter | Field | Default | Values | Control |
|--------|-------|---------|--------|---------|
| **From Date** | `dupFromDate` | Start of month | Any date | Date picker |
| **To Date** | `dupToDate` | End of month | Any date | Date picker |
| **Status** | `dupStatusFilter` | 'PENDING' | PENDING / CONFIRMED_DUPE / ALL | Dropdown |
| **Bank Account** | `dupBankAccountFilter` | 'ALL' | List of accounts + ALL | Dropdown |
| **Match Type** | `dupMatchTypeFilter` | 'ALL' | EXACT_CODE_MISMATCH / FUZZY_MATCH / ALL | Dropdown |

**Pagination**:
- Page size: Configurable (default 20 items = 20 transaction pair sub-tables)
- Controls: [← Previous] [Page X of Y] [Next →]
- Query optimization: SQL LIMIT + OFFSET per page
- Stores filters in session so prev/next preserves user selections

**UI Structure** - Per-duplicate sub-table pair (field diffs highlighted at render time):
```
┌─────────────────────────────────────────────────────────────────┐
│ Duplicate Review Dashboard                                      │
├─────────────────────────────────────────────────────────────────┤
│ From: [Jan 15] To: [Jan 31] Status: [Pending ▼] Account: [All ▼]│
│ Match Type: [All ▼]                          [Clear Filters]    │
├─────────────────────────────────────────────────────────────────┤
│ Showing 1-20 of 847 duplicates    [← Previous] Page 1 [Next →]  │
├─────────────────────────────────────────────────────────────────┤
│ 
│ Dupe ID 1: RBC-0001 vs Flagged ID 523      Match Type: CODE_MISMATCH
├─────────────────────────────────────────────────────────────────┤
│ ORIGINAL (bi_transactions ID 1000)                              │
│ Date: 2025-01-15 | Amount: $50.00 | Merchant: SHOPPERS PHARM   │
│ Memo: Pharmacy purchase | Reference: RBC-0001                   │
├─────────────────────────────────────────────────────────────────┤
│ FLAGGED FOR REVIEW (bi_transactions_dupe ID 523)                │
│ Date: 2025-01-15 | Amount: $50.00 | Merchant: SHOPPERS PHARM   │
│ Memo: [HIGHLIGHT] Pharmacy - card | Reference: RBC-0001        │
│       ↑ Differs from original                                    │
├─────────────────────────────────────────────────────────────────┤
│ [✓ Confirm as Duplicate] [✕ Not a Dupe - Import] [Review Later] │
└─────────────────────────────────────────────────────────────────┘

│ Dupe ID 2: ...
│...
│
│ [← Previous] Page 1 of 43 [Next →]    Bulk: [✓ All on Page] [✕ All on Page]
└─────────────────────────────────────────────────────────────────┘
```

### Phase 2.4: Review UI - Post-Import Tab
**Deliverables**:
- Tab on `import_statements.php` showing post-import duplicates only
- Same grid as dashboard but filtered to current import
- Quick workflow: Review → Confirm → Redirect to dashboard

**Integration Point**:
```php
// In import_statements.php after import completes:
if ($pendingReviewCount > 0) {
    display_notification("$pendingReviewCount transactions flagged for review");
    include 'views/DuplicateReviewPostImportTab.php';  // Show review inline
}
```

### Phase 2.5: Action Handlers
**Deliverables**:
- AJAX endpoint: `confirm_duplicate.php` - Mark as CONFIRMED_DUPE
- AJAX endpoint: `move_to_statement.php` - INSERT to bi_transactions, DELETE from dupe
- AJAX endpoint: `discard_review.php` - DELETE without importing
- Bulk action handlers
- Audit logging for all actions

**Key Handlers**:
```php
// confirm_duplicate.php
$dupeId = (int)$_POST['dupe_id'];
$userId = $_SESSION['wa_current_user']->username;
$sql = "UPDATE 0_bi_transactions_dupe SET status='CONFIRMED_DUPE', 
        reviewed_by='$userId', reviewed_at=NOW() WHERE id=$dupeId";
db_query($sql);

// move_to_statement.php - More complex, copies row + deletes
$dupe = db_fetch_assoc(db_query("SELECT * FROM 0_bi_transactions_dupe WHERE id=$dupeId"));
$insert_sql = build_bi_transactions_insert($dupe);
db_query($insert_sql);  // Insert into bi_transactions
db_query("DELETE FROM 0_bi_transactions_dupe WHERE id=$dupeId");
```

### Phase 2.6: Whitelist Rules Integration
**Deliverables**:
- "Suggest rule" button for transactions matching a pattern
- Modal: "Create whitelist rule from this merchant?"
- Auto-populate next patterns that would match
- Test the pattern against historical transactions

---

## Service Class Updates

### Updated: `DirectCodeMatcher.php`
**Change**: Full field comparison on code match, return list of differing fields

```php
public function find(array $transaction): ?array
{
    $query = "SELECT * FROM " . TB_PREF . "bi_transactions 
              WHERE transactionCode = " . db_escape($transaction['transactionCode']) . "
              AND acctid = " . db_escape($transaction['acctid']);
    
    $result = db_query($query);
    $existing = db_fetch_assoc($result);
    
    if (!$existing) {
        return null;  // No code match
    }
    
    // Code match found - now validate all fields
    $fieldDifferences = $this->compareAllFields($transaction, $existing);
    
    if (empty($fieldDifferences)) {
        // All fields match - exact duplicate
        return $existing;  // Will trigger exactMatch() → SKIP
    } else {
        // Fields differ - return match with field list
        // Store field names (not values - those already in the row)
        $existing['_fields_that_differ'] = implode(',', array_keys($fieldDifferences));
        return $existing;  // Will trigger needsReview() → DUPE TABLE
    }
}

private function compareAllFields(array $new, array $existing): array
{
    $differences = [];  // Keyed by field name
    $fieldsToCompare = [
        'valueTimestamp',
        'transactionAmount', 
        'merchant',
        'memo',
        'reference'
    ];
    
    foreach ($fieldsToCompare as $field) {
        $newVal = $new[$field] ?? null;
        $existVal = $existing[$field] ?? null;
        
        if ($newVal !== $existVal) {
            $differences[$field] = true;  // Just mark that it differs
        }
    }
    
    return $differences;
}
```

**Why this approach**:
- Field differences calculated once at INSERT time (during import)
- Avoids recalculating on every page load during review
- Simpler schema: CSV string vs JSON with old/new values
- Review UI already has both transaction rows side-by-side, can highlight using field list

### Updated: `DuplicateCheckResult.php`
**Change**: Return field names that differ

```php
public function getFieldsThatDiffer(): ?array
{
    // Return exploded array: ['memo', 'amount', ...]
    if (!empty($this->match['_fields_that_differ'])) {
        return explode(',', $this->match['_fields_that_differ']);
    }
    return [];
}

public function hasFieldDifferences(): bool
{
    return !empty($this->match['_fields_that_differ'] ?? '');
}
```

### Import Flow: Calculate & Store at INSERT Time
**In `importStatement()` transaction loop - detecting duplicates**:
```php
$checkResult = $this->duplicateDetectionService->detect($transactionData);

if ($checkResult->shouldSkip()) {
    // Exact match - skip it
    $dupecount++;
} elseif ($checkResult->needsReview()) {
    // Store in dupe table with calculated field differences
    $this->storeFuzzyMatchForReview(
        $transactionData, 
        $checkResult->getMatches()[0],
        $checkResult->getFieldsThatDiffer()
    );
    $pendingReviewCount++;
} else {
    // Import normally
    $bit->hand_insert_sql();
    $newinserted++;
}
```

**The `storeFuzzyMatchForReview()` method**:
```php
private function storeFuzzyMatchForReview(
    array $transactionData, 
    array $matchedRecord, 
    array $fieldDifferences = [],
    $matchType = 'FUZZY_MATCH'
)
{
    $fields_that_differ = !empty($fieldDifferences) 
        ? implode(',', $fieldDifferences) 
        : null;
    
    // Insert full clone of transaction into dupe table
    $sql = "INSERT INTO " . TB_PREF . "bi_transactions_dupe 
            (smt_id, transactionCode, acctid, ..., 
             matching_bi_transaction_id, match_type, fields_that_differ, created_at)
            VALUES (" . db_escape($transactionData['smt_id']) . ", ...)
                   " . db_escape($matchedRecord['id']) . ",
                   '" . db_escape($matchType) . "',
                   " . ($fields_that_differ ? "'" . db_escape($fields_that_differ) . "'" : "NULL") . ",
                   NOW()
            )";
    
    db_query($sql);
}
```

### Review UI: Highlight Differences Using Field List
**In DuplicateReviewView.php - rendering transaction pair**:
```php
// Get the field names that differed
$fieldsDifferent = explode(',', $dupe['fields_that_differ']);

// Render original transaction
$this->renderTransactionRow($existingTx, null, 'original');

// Render flagged transaction with highlighting
$this->renderTransactionRow($dupe, $fieldsDifferent, 'flagged');

private function renderTransactionRow($tx, $highlightFields, $type) {
    $fields = ['date', 'amount', 'merchant', 'memo', 'reference'];
    
    foreach ($fields as $field) {
        $value = $tx[$field] ?? '';
        $isHighlighted = in_array($field, $highlightFields ?? []);
        $class = $isHighlighted ? 'field-differs' : '';
        echo "<span class='$class'>$value</span>";
    }
}
```

**CSS for highlighting**:
```css
.field-differs {
    background-color: #fff3cd;  /* Yellow highlight */
    border: 1px solid #ffc107;
    padding: 2px 4px;
    font-weight: bold;
}
```

```php
public function getFieldDifferences(): ?array
{
    // Return $this->match['_field_differences'] if present
    // Used by review UI to highlight what changed
}

public function hasFieldDifferences(): bool
{
    return !empty($this->match['_field_differences'] ?? []);
}
```

---

## Key Implementation Notes

### Field Difference Calculation & Storage
- **Calculated AT INSERT TIME** (during import processing, not during review)
- Stored as comma-separated field names: `"memo,amount"` (not full JSON)
- This avoids recalculating on every review page load
- Review UI uses the field list to highlight differences with CSS
- Since both transaction rows are displayed side-by-side, highlighting is instant

### Pagination Strategy (Mirrors `process_statements.php`)
- Page size: 20 transactions per page (configurable)
- Uses SQL `LIMIT X OFFSET (page-1)*X`
- Filters stored in session for prev/next navigation
- Counts total matches for pagination: "Showing 1-20 of 847 duplicates"
- AJAX-based page refreshes preserve all user filters

### Filter Implementation (Mirrors `process_statements.php`)
| Filter | Default | Behavior |
|--------|---------|----------|
| Date From | Start of month | Applied always |
| Date To | End of month | Applied always |
| Status | 'PENDING' | Applied only if not 'ALL' |
| Bank Account | 'ALL' | Applied only if not 'ALL' |
| Match Type | 'ALL' | Applied only if not 'ALL' |

---

## UI Components (PHP)

### 1. `DuplicateReviewView.php`
- Render filter controls (5 filters + Clear button)
- Render pagination controls
- Loop through paginated results
- Render each transaction pair with field highlighting
- Handle action buttons (confirm/import/defer)
- Render bulk actions on page

### 2. `DuplicatePairRowView.php`
- Display original transaction row (from bi_transactions)
- Display flagged transaction row (from bi_transactions_dupe)
- Highlight differing fields using `fields_that_differ` list
- Action buttons: [✓ Confirm as Dupe] [✕ Not a Dupe - Import] [Review Later]
- Show match type (EXACT_CODE_MISMATCH or FUZZY_MATCH)

### 3. `DuplicatePostImportTabView.php`
- Summary: "X transactions flagged for review"
- Quick review grid (first 20 or configurable)
- "Review all in dashboard" button
- Same filter/pagination as main dashboard

---

## Testing Strategy

### Unit Tests
- `BankStrategyServiceTest.php` - Test strategy loading, caching
- `DirectCodeMatcherWithStrategyTest.php` - Test account-scoped matching
- `FuzzyMatchStoreTest.php` - Test INSERT into bi_transactions_dupe

### Integration Tests
- Full import with RBC re-download scenario → All go to bi_transactions_dupe
- Manulife cross-account scenario → Account filter prevents false match
- Standard bank → Exact match still works

### UI Tests
- Filter: Date range filters correctly
- Filter: Status filter (Pending / Confirmed / All) shows correct counts
- Filter: Bank account filter shows correct accounts
- Filter: Match type filter (Exact vs Fuzzy) works
- Pagination: Page sizing (default 20 per page)
- Pagination: Prev/next buttons navigate correctly
- Pagination: Filters preserved across page navigation
- Pagination: Page indicator shows "1-20 of 847"
- Field highlighting: Differing fields show yellow background
- Confirm/Import/Defer: AJAX buttons update status immediately
- Bulk actions: Select all on page, confirm/import bulk

---

## Deployment Checklist

- [ ] Create `bi_transactions_dupe` table + indexes
- [ ] Update DirectCodeMatcher to perform full field comparison + return field names
- [ ] Create DuplicateCheckResult methods for field name tracking
- [ ] Integrate into import_statements.php with field difference calculation at INSERT
- [ ] Build review_duplicates.php dashboard with:
  - [ ] 5 filter inputs (date from/to, status, bank account, match type)
  - [ ] Pagination with LIMIT/OFFSET
  - [ ] Clear filters button
  - [ ] Page navigation: [← Previous] [Page X of Y] [Next →]
- [ ] Build DuplicateReviewView.php (render filters + pagination + transaction pairs)
- [ ] Build DuplicatePairRowView.php (side-by-side with field highlighting)
- [ ] Build post-import review tab
- [ ] Implement AJAX handlers for confirm/import/defer actions
- [ ] Bulk action handlers (select all on page)
- [ ] Add CSS for field highlighting (yellow background)
- [ ] Test code match with field difference → goes to _dupe for review with fields flagged
- [ ] Test code + all fields match → skipped (no _dupe)
- [ ] Test fuzzy match → goes to _dupe with fields_that_differ = null
- [ ] Test pagination: 20 items per page, navigation works
- [ ] Test filters: date, status, bank, match type all work correctly
- [ ] Test filter preservation across pagination
- [ ] Test field highlighting displays correctly
- [ ] Load test with 1000+ flagged transactions, pagination performance
- [ ] Verify review UI shows field differences using CSS (not stored JSON)

---

## Success Criteria

✅ Users can import statements with duplicates held for review  
✅ Code match + all fields identical → Skip immediately  
✅ Code match + field differences → Flag for review with fields highlighted  
✅ Fuzzy match → Flag for review  
✅ Differing fields highlighted in yellow during review (CSS-based, not JSON)
✅ Field differences calculated once at INSERT time (no recalculation)
✅ Review dashboard supports 5 filters (date, status, account, match type)
✅ Pagination works: 20 items per page with prev/next navigation
✅ Filters preserved when navigating between pages
✅ Page info shows: "Showing 1-20 of 847 duplicates"
✅ Users can view side-by-side diff with highlighting
✅ Users can confirm, reject, or defer flagged transactions
✅ Bulk actions available for multiple transactions
✅ Whitelist rules can be created from review UI  
✅ Audit trail shows who reviewed and when  
✅ No data corruption from false duplicates  
✅ Performance acceptable with 10,000+ pending reviews  

---

## Open Questions

1. **Page Size Configuration**: Make pagination configurable (default 20) or keep fixed?

2. **Whitelist Learning**: Should rejected "not a dupe" transactions help train rules? E.g., if user rejects 10 "SHOPPERS" transactions marked as dupe, auto-create whitelist rule?

3. **Auto-Retry on New Rules**: After creating whitelist rule, should we re-run detection on pending items?

4. **Audit Export**: Need export of review history for compliance/reconciliation?

5. **Statement Lifecycle**: Once all transactions reviewed, mark statement as "REVIEW_COMPLETE"?

6. **Batch Import Multiple Files**: If user uploads 5 files, show dupe review per-file or combined view of all?

7. **Additional Filters**: Should we add Amount Range filter (min/max) like process_statements has scaffolded?
