# Partner Matching Architecture Analysis

## Executive Summary

The partner matching system is fragmented across multiple layers with mixed concerns. The current architecture uses a **basic search layer** (CRUD), a **skill-based matching layer** (keyword search), and a **legacy view layer** (display forms). The system needs decomposition into cleanly separated bounded contexts.

---

## 1. `includes/pdata.inc` - Partner Data CRUD Layer

### Current State: **Mixed Concerns**
This file contains basic partner data operations with intertwined logic.

### Functions Analyzed

#### 1.1 `get_partner_data($partner_id, $partner_type, $partner_detail_id)`
```php
function get_partner_data($partner_id, $partner_type, $partner_detail_id)
```

**Signature:**
- Input: `int $partner_id`, `int $partner_type`, `int $partner_detail_id`
- Output: `array` (single row or empty array)

**What it does (step by step):**
1. Constructs SQL WHERE clause: `partner_id = X AND partner_type = Y`
2. If partner_type is PT_CUSTOMER or ST_BANKTRANSFER, adds: `AND partner_detail_id = Z`
3. Executes SELECT query
4. Returns single row or empty array

**Mixed Concerns:**
- ✗ Data access (raw SQL/db_query)
- ✗ Type-specific logic (PT_CUSTOMER vs ST_BANKTRANSFER branching)
- ✗ Domain logic (when to include partner_detail_id)

**Dependencies:**
- Global: `TB_PREF` (table prefix constant)
- Functions: `db_escape()`, `db_query()`, `db_fetch()`

---

#### 1.2 `set_partner_data($partner_id, $partner_type, $partner_detail_id, $data)`
```php
function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data)
```

**Signature:**
- Input: `int $partner_id`, `int $partner_type`, `int $partner_detail_id`, `string $data`
- Output: `void` (modifies DB)

**What it does (step by step):**
1. **Fetch existing:** `get_partner_data()` to check if record exists
2. **Check if update needed:**
   - If exists and data matches → return early (no-op)
   - If exists but different → Call `search_partner_by_bank_account()` for duplicate check
   - If same partner already exists → return early
   - Otherwise → proceed with insert/update
3. **Execute upsert:** INSERT ... ON DUPLICATE KEY UPDATE (MySQL syntax)
4. Inserts/updates `partner_id`, `partner_type`, `partner_detail_id`, `data` fields

**Mixed Concerns:**
- ✗ Data validation (empty data allowed, but should check)
- ✗ Duplicate prevention logic (calls another search function)
- ✗ Data persistence (raw SQL INSERT/UPDATE)

**Dependencies:**
- Calls: `get_partner_data()`, `search_partner_by_bank_account()`
- Global: `TB_PREF`
- Functions: `db_escape()`, `db_query()`

---

#### 1.3 `set_bank_partner_data($from_bank_id, $partner_type = ST_BANKTRANSFER, $to_bank_id, $data)`
```php
function set_bank_partner_data($from_bank_id, $partner_type = ST_BANKTRANSFER, $to_bank_id, $data)
```

**Signature:**
- Input: `int $from_bank_id`, `int $partner_type`, `int $to_bank_id`, `string $data`
- Output: `void`

**What it does:**
1. Simple wrapper that calls `set_partner_data()` with bank-specific parameters
2. Maps `from_bank_id` → `partner_id`, `to_bank_id` → `partner_detail_id`

**Mixed Concerns:**
- ✗ Thin wrapper but bank-specific (policy)
- ✗ Parameter mapping logic

**Dependencies:**
- Calls: `set_partner_data()`

---

#### 1.4 `search_partner_data_by_needle($needle)`
```php
function search_partner_data_by_needle($needle)
```

**Signature:**
- Input: `string $needle`
- Output: `array` (array of matching rows)

**What it does (step by step):**
1. Return empty array if needle is empty
2. Execute SQL query: `SELECT * FROM bi_partners_data WHERE data LIKE '%needle%'`
3. Fetch all rows into array
4. Return array

**Mixed Concerns:**
- ✗ Substring search (inefficient LIKE pattern)
- ✗ No type filtering
- ✗ Returns ALL fields without aggregation

**Dependencies:**
- Global: `TB_PREF`
- Functions: `db_query()`, `db_fetch()`

---

#### 1.5 `search_partner_by_bank_account($partner_type, $needle)`
```php
function search_partner_by_bank_account($partner_type, $needle)
```

**Signature:**
- Input: `int $partner_type`, `string $needle`
- Output: `array` (single row or empty)

**What it does:**
1. Return empty array if needle is empty
2. Execute SQL: `SELECT * WHERE partner_type = X AND data LIKE '%needle%' LIMIT 1`
3. Return single row

**Mixed Concerns:**
- ✗ Type filtering (partner_type branching)
- ✗ Substring search (same limitation as above)
- ✗ Ordering not specified (LIMIT 1 is unpredictable)

**Dependencies:**
- Global: `TB_PREF`
- Functions: `db_escape()`, `db_query()`, `db_fetch()`

---

#### 1.6 `update_partner_data($partner_id, $partner_type, $partner_detail_id, $data)`
```php
function update_partner_data($partner_id, $partner_type, $partner_detail_id, $data)
```

**Signature:**
- Input: `int $partner_id`, `int $partner_type`, `int $partner_detail_id`, `string $data`
- Output: `void`

**What it does:**
1. Builds INSERT/UPDATE SQL with CONCAT operator to append data
2. Adds newline separator: `data=CONCAT(data, "\n")`
3. Executes upsert

**Mixed Concerns:**
- ✗ Accumulation logic (appends data with newlines)
- ✗ Raw SQL formatting
- ✗ No truncation limit (could grow unbounded)

**Dependencies:**
- Global: `TB_PREF`
- Functions: `db_escape()`, `db_query()`

---

### 1.7 Key Issues in pdata.inc

| Issue | Impact | Severity |
|-------|--------|----------|
| Type-specific branching in generic functions | Hard to add new partner types | High |
| Raw SQL mixedwith business logic | Hard to test, SQL injection risk | High |
| Duplicate search logic spread across functions | Inconsistency | Medium |
| LIKE searches inefficient | Performance degradation | Medium |
| No validation/exceptions | Silent failures | High |
| Accumulation in `update_partner_data()` unbounded | Data bloat | Medium |

---

## 2. `includes/search_partner_keywords.inc` - Intelligent Pattern Matching

### Current State: **Advanced, Well-Designed (but isolated)**
This is sophisticated keyword-based matching with scoring, but exists independently from other systems.

### Functions Analyzed

#### 2.1 `extract_keywords_for_search($text)`
```php
function extract_keywords_for_search($text)
```

**Signature:**
- Input: `string $text`
- Output: `array` (array of unique keywords)

**What it does (step by step):**
1. Normalize: convert to lowercase
2. Remove special characters: `preg_replace('/[^a-z0-9\s]/', ' ', $text)`
3. Split on whitespace
4. Filter by:
   - Minimum length ≥ 3 characters
   - Not in stopwords list (22 common words)
5. Return unique keywords array

**Mixed Concerns:**
- ✗ Text processing (normalization, tokenization)
- ✗ Filtering logic (stopwords hardcoded)

**Dependencies:**
- None (pure function)

**Stopwords (Hardcoded):**
```
'the', 'and', 'or', 'for', 'to', 'from', 'in', 'on', 'at', 'by',
'with', 'of', 'as', 'is', 'was', 'be', 'are', 'were', 'been',
'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
'this', 'that', 'these', 'those', 'it', 'its', 'an', 'a',
'payment', 'transaction', 'transfer', 'deposit', 'withdrawal'
```

---

#### 2.2 `search_partner_by_keywords($partner_type, $search_text, $limit = 10)`
```php
function search_partner_by_keywords($partner_type, $search_text, $limit = 10)
```

**Signature:**
- Input: `int $partner_type`, `string $search_text`, `int $limit`
- Output: `array` (scored results sorted by confidence)

**What it does (step by step):**

1. **Extract Keywords:**
   - Call `extract_keywords_for_search()` to tokenize $search_text
   - Return empty if no keywords found

2. **Query Phase:**
   - Build OR conditions: `data = 'keyword1' OR data = 'keyword2' ...`
   - Add partner type filter if provided
   - SELECT from bi_partners_data with `occurrence_count` ordering

3. **Scoring Aggregation:**
   - Group results by partner (partner_id-partner_detail_id-partner_type)
   - For each partner, aggregate:
     - Sum occurrence counts → base_score
     - Collect matched keywords
     - Count keyword matches

4. **Co-occurrence Clustering Bonus:**
   - Formula: `score = base_score * (1 + (keyword_match_count - 1) * KEYWORD_CLUSTERING_FACTOR)`
   - Default CLUSTERING_FACTOR = 0.2 (configurable via constant)
   - Examples:
     - 1 keyword: score × 1.0 (no bonus)
     - 2 keywords: score × 1.2 (20% boost)
     - 3 keywords: score × 1.4 (40% boost)

5. **Sorting:**
   - Primary: keyword_match_count (descending)
   - Secondary: final score (descending)

6. **Confidence Calculation:**
   - Keyword coverage: `(matched_keywords / search_keywords) * 100`
   - Score strength: `(partner_score / top_score) * 100`
   - Confidence: `(coverage * 0.6) + (strength * 0.4)` (weighted average)

7. **Return:**
   - Slice to limit and return

**Mixed Concerns:**
- ✓ Well separated: tokenization, querying, scoring, confidence
- ✓ Configurable via constants
- ✗ Still uses raw SQL with `db_query()` and `db_fetch()`
- ✗ Assumes `bi_partners_data` table structure

**Dependencies:**
- Calls: `extract_keywords_for_search()`
- Global: `TB_PREF`, `KEYWORD_CLUSTERING_FACTOR` (constant)
- Functions: `db_escape()`, `db_query()`, `db_fetch()`
- Config: Attempts to load from `ConfigService::getInstance()` if available

**Output Format:**
```php
[
    'partner_id' => int,
    'partner_detail_id' => int,
    'partner_type' => int,
    'score' => int,  // Final scored (with clustering bonus)
    'base_score' => int,  // Before clustering bonus
    'clustering_bonus' => int,
    'matched_keywords' => array,  // e.g. ['internet', 'domain', 'registration']
    'total_occurrences' => int,
    'keyword_match_count' => int,
    'confidence' => float  // 0-100
]
```

---

#### 2.3 `get_suggested_partner($partner_type, $search_text)`
```php
function get_suggested_partner($partner_type, $search_text)
```

**Signature:**
- Input: `int $partner_type`, `string $search_text`
- Output: `array|null` (top match or null)

**What it does:**
1. Calls `search_partner_by_keywords()` with limit=1
2. Returns first result if exists, null otherwise

**Mixed Concerns:**
- ✓ Thin wrapper, good semantic

---

### 2.4 Key Strengths in search_partner_keywords.inc

| Strength | Benefit |
|----------|---------|
| Multi-factor scoring | Robust matching even with partial data |
| Co-occurrence bonus | Rewards multiple keyword matches |
| Configurable clustering factor | Tunable for different business rules |
| Confidence scoring | Allows UI to show match quality |
| Occurrence counting | Learns from historical transactions |
| Sortable results | Top N matches for user review |

---

## 3. `build_partner_keyword_data.php` - Keyword Data Population

### Current State: **Data Processing Script**
One-off/maintenance script that processes historical transactions to build keyword training data.

### Functions Analyzed

#### 3.1 `extract_keywords($text)`
```php
function extract_keywords($text)
```

**Signature:**
- Input: `string $text`
- Output: `array` (array of keywords)

**What it does:**
1. Similar to `extract_keywords_for_search()` but with config lookup
2. Attempts to get `pattern_matching.min_keyword_length` from ConfigService
3. Falls back to 3 if config unavailable
4. Normalizes, removes special chars, splits, filters

**Mixed Concerns:**
- ✗ Text processing + configuration lookups
- ✗ Same stopwords list hardcoded

**Dependencies:**
- Config: `ConfigService::getInstance()`
- Constants: None (loads from config)

---

#### 3.2 `get_partner_type_from_transaction($row)`
```php
function get_partner_type_from_transaction($row)
```

**Signature:**
- Input: `array $row` (transaction record with fa_trans_type)
- Output: `string|null` (partner type or null)

**What it does (step by step):**
1. Maps FA transaction types to bi_partners_data types:
   - ST_SUPPAYMENT, ST_SUPPCREDIT → PT_SUPPLIER
   - ST_BANKDEPOSIT → PT_SUPPLIER (when supplier refund)
   - ST_CUSTPAYMENT, ST_CUSTCREDIT → PT_CUSTOMER
   - ST_BANKPAYMENT → ST_BANKPAYMENT (Quick Entry)
   - ST_BANKTRANSFER → ST_BANKTRANSFER
2. Returns mapped type or null if not in map

**Mixed Concerns:**
- ✗ Type mapping hardcoded
- ✓ Encapsulates mapping logic

**Dependencies:**
- Constants: Transaction type constants (ST_*)

---

#### 3.3 `get_partner_from_fa_trans($trans_no, $trans_type)`
```php
function get_partner_from_fa_trans($trans_no, $trans_type)
```

**Signature:**
- Input: `int $trans_no`, `int $trans_type`
- Output: `array` with keys `partner_id`, `partner_detail_id`, `partner_type`

**What it does (step by step):**
1. **Get counterparty:** Call `get_trans_counterparty()` to look up FA transaction
2. **Type-specific extraction:**
   - Supplier: partner_id = supplier_id, partner_detail_id = -1
   - Customer: partner_id = debtor_no, partner_detail_id = branch_code
   - Quick Entry: partner_id = account_code, partner_detail_id = 0
   - Bank Transfer: partner_id = account, partner_detail_id = 0
3. Return structured array

**Mixed Concerns:**
- ✗ External system integration (FA transaction lookup)
- ✗ Type-specific branching
- ✗ Null and empty checking scattered

**Dependencies:**
- Calls: `get_trans_counterparty()` (external FA function)
- Transaction constants: ST_*

---

#### 3.4 `increment_keyword_occurrence($partner_id, $partner_detail_id, $partner_type, $keyword)`
```php
function increment_keyword_occurrence($partner_id, $partner_detail_id, $partner_type, $keyword)
```

**Signature:**
- Input: `int $partner_id`, `int $partner_detail_id`, `int $partner_type`, `string $keyword`
- Output: `bool` (success)

**What it does:**
1. Validation: return false if keyword empty or IDs empty
2. INSERT with occurrence_count = 1
3. ON DUPLICATE KEY UPDATE: occurrence_count = occurrence_count + 1
4. Execute and return query result

**Mixed Concerns:**
- ✓ Good validation
- ✗ Raw SQL

**Dependencies:**
- Global: `TB_PREF`
- Functions: `db_escape()`, `db_query()`

---

#### 3.5 `process_all_transactions($dry_run = false)`
```php
function process_all_transactions($dry_run = false)
```

**Signature:**
- Input: `bool $dry_run` (preview mode)
- Output: `array` (statistics: transactions_processed, keywords_added, keywords_updated, errors)

**What it does (step by step):**

1. **Query Phase:**
   - SELECT all bi_transactions WHERE status = 1 (settled)
   - AND fa_trans_no > 0 AND fa_trans_type > 0

2. **For each transaction:**
   - Get partner info via `get_partner_from_fa_trans()`
   - Skip if no partner found
   - Collect text from: account, accountName, transactionTitle, memo, merchant, category
   - Extract keywords via `extract_keywords()`
   - Skip if no keywords

3. **Keyword Processing (unless dry_run):**
   - For each keyword:
     - Check if already exists for this partner
     - Call `increment_keyword_occurrence()`
     - Track stats (added vs updated vs errors)

4. **Return statistics**

**Mixed Concerns:**
- ✗ Transaction processing
- ✗ Statistics tracking
- ✗ Dry run mode
- ✗ Transaction boundaries

**Dependencies:**
- Calls: `get_partner_from_fa_trans()`, `extract_keywords()`, `get_partner_data()`, `increment_keyword_occurrence()`
- Functions: `db_query()`, `db_fetch()`, `display_notification()`
- Transaction control: `begin_transaction()`, `commit_transaction()`, `rollback_transaction()`

---

### 3.6 Main Execution (UI/Form Handling)

The script handles POST actions:
- `'dry_run'` → Preview mode (transaction rolled back)
- `'process'` → Execute actual processing

Shows database state before processing.

**Mixed Concerns:**
- ✗ Business logic mixed with UI
- ✗ FrontAccounting form handling hardcoded

---

### 3.7 Key Issues in build_partner_keyword_data.php

| Issue | Impact |
|-------|--------|
| Type mapping hardcoded | Brittle, non-extensible |
| Text field collection ad-hoc | Missing fields, inconsistent |
| Transaction lookup external | Coupling to FA system |
| Dry run mode via parameter | Unclear semantics |
| Statistics object loose | Type-unsafe, error-prone |

---

## 4. `class.ViewBiLineItems.php` - View & Display Layer

### Current State: **Legacy View Class (Deprecated)**
Encapsulates display logic for line items with partner type routing.

### Methods Analyzed

#### 4.1 `displayPartnerType()`
```php
function displayPartnerType()
```

**Signature:**
- Input: Uses `$_POST['partnerType'][$this->id]`
- Output: Void (outputs HTML directly via echo)

**What it does (step by step):**

1. **Type Routing:** Switch on `$_POST['partnerType'][$this->id]`:
   - `'SP'` → call `displaySupplierPartnerType()`
   - `'CU'` → call `displayCustomerPartnerType()`
   - `'BT'` → call `displayBankTransferPartnerType()`
   - `'QE'` → call `displayQuickEntryPartnerType()`
   - `'MA'` → call `displayMatchedPartnerType()`
   - `'ZZ'` → (Generic/Already matched) output hidden fields from `$this->matching_trans[0]`

2. **Common Footer:**
   - Output comment label row with text_input
   - Output submit button via ProcessTransactionButtonRow

**Mixed Concerns:**
- ✗ Display logic (HTML generation)
- ✗ Routing logic (switch statement)
- ✗ Form handling (POST data)
- ✗ Hidden field management

**Dependencies:**
- Global POST data: `$_POST['partnerType'][$this->id]`
- Member fields: `$this->matching_trans`, `$this->id`, `$this->memo`
- Functions: `hidden()`, `label_row()`, `text_input()`
- Classes: `ProcessTransactionButtonRow`

**Output:** HTML for partner type-specific form controls

---

#### 4.2 `displaySupplierPartnerType()`
```php
function displaySupplierPartnerType()
```

**What it does:**
1. **Propose supplier:** If $this->partnerId empty:
   - Call `search_partner_by_bank_account(PT_SUPPLIER, $this->otherBankAccount)`
   - If match found, set $this->partnerId and $_POST value
2. **Display form:** Render supplier_list dropdown

**Mixed Concerns:**
- ✗ Business logic (partner search)
- ✗ Form rendering
- ✗ State mutation ($_POST modification)

**Dependencies:**
- Calls: `search_partner_by_bank_account()` from pdata.inc
- Member fields: `$this->partnerId`, `$this->otherBankAccount`, `$this->id`
- Functions: `supplier_list()`, `label_row()`

---

#### 4.3 `displayCustomerPartnerType()`
```php
function displayCustomerPartnerType()
```

**What it does:**
1. **Propose customer:** If empty, search via `search_partner_by_bank_account(PT_CUSTOMER, ...)`
   - Set partner_id and partner_detail_id (branch)
2. **Display form:**
   - customer_list dropdown
   - If customer has branches: customer_branches_list
   - Else: hidden ANY_NUMERIC
3. **Include external:** Attempt to include `../ksf_modules_common/class.fa_customer_payment.php`
   - If available, show invoice allocation interface
4. **Hidden fields:**
   - customer_id, customer_branch_id, invoice allocation

**Mixed Concerns:**
- ✗ Partner search logic
- ✗ Branch detection and rendering
- ✗ External system integration (customer payment module)
- ✗ Invoice allocation (belongs to accounting, not partner selection)

**Dependencies:**
- Calls: `search_partner_by_bank_account()`, `db_customer_has_branches()`, `customer_branches_list()`
- External include: `class.fa_customer_payment.php`
- Member fields: `$this->partnerId`, `$this->partnerDetailId`, `$this->otherBankAccount`, `$this->valueTimestamp`, `$this->id`

---

#### 4.4 `displayBankTransferPartnerType()`
```php
function displayBankTransferPartnerType()
```

**What it does:**
1. **Propose bank:** If POST value empty:
   - Call `search_partner_by_bank_account(ST_BANKTRANSFER, $this->otherBankAccount)`
   - If found: set partner_id and partner_detail_id
   - If not: default to ANY_NUMERIC
2. **Display form:** bank_accounts_list dropdown

**Mixed Concerns:**
- ✗ Partner search
- ✗ Form rendering
- ✗ Default value logic (ANY_NUMERIC)

**Dependencies:**
- Calls: `search_partner_by_bank_account()`
- Member fields: `$this->otherBankAccount`, `$this->id`
- Functions: `bank_accounts_list()`

---

#### 4.5 `getDisplayMatchingTrans()` Orchestration

In [class.bi_lineitem.php](class.bi_lineitem.php), the matching orchestration:

```php
function getDisplayMatchingTrans()
{
    $this->findMatchingExistingJE();  // Populates $this->matching_trans
    
    if(count($this->matching_trans) > 0) {
        if(count($this->matching_trans) < 3) {
            if(50 <= $this->matching_trans[0]['score']) {
                // High confidence match
                if($this->matching_trans[0]['is_invoice']) {
                    $this->formData->setPartnerType('SP');  // Supplier
                } else {
                    $this->formData->setPartnerType('ZZ');  // Generic match
                }
                $this->oplabel = "MATCH";
            }
        }
        // 3+ matches: need to sort by score and take highest
    }
}
```

**Logic:**
1. Call MatchingJEs to find GL matches
2. If 1-2 matches AND score ≥ 50 → suggest match
3. If invoice → route to Supplier partner type
4. If not invoice → route to Generic (ZZ) partner type

**Mixed Concerns:**
- ✗ Matching algorithm (score thresholds)
- ✗ Partner type selection
- ✗ Form data mutation

---

### 4.6 Key Issues in ViewBiLineItems

| Issue | Impact | Severity |
|-------|--------|----------|
| Partner search logic in views | Scattered, not reusable | High |
| $_POST mutation in views | State inconsistencies | High |
| Business logic in display methods | Hard to test | High |
| Type-specific branching in views | Not extensible | Medium |
| External module coupling | Tight coupling to payment logic | Medium |
| Magic strings ('SP', 'CU', 'BT', 'QE', 'MA', 'ZZ') | Unmaintainable | Medium |

---

## 5. Orchestration Services - Transaction Matching

### 5.1 `MatchingJEs` Class
**Location:** [src/Ksfraser/FaBankImport/models/MatchingJEs.php](src/Ksfraser/FaBankImport/models/MatchingJEs.php)

**Purpose:** Find matching Journal Entry (GL) transactions for imported bank transactions

**Core Method:**
```php
$match = new MatchingJEs($lineItem);
$results = $match->getMatchArr();  // Returns array of matching GL entries with scores
```

**Output Format:**
```php
[
    [
        'type' => int,           // FA transaction type
        'type_no' => int,        // FA transaction number
        'tran_date' => string,   // Transaction date
        'account' => string,     // GL account code
        'memo_' => string,       // Memo
        'amount' => float,       // Amount
        'person_type_id' => int, // Supplier/Customer type
        'person_id' => int,      // Supplier/Customer ID
        'account_name' => string,// GL account name
        'reference' => string,   // Reference text
        'score' => int,          // Match score (0-100+)
        'is_invoice' => bool     // Whether this is an invoice
    ],
    // ... more results sorted by score
]
```

**Integration Point:**
- Called by `BiLineItem::findMatchingExistingJE()`
- Results populate `$lineItem->matching_trans`
- Used by `getDisplayMatchingTrans()` to route partner types

---

### 5.2 Partner Type Registry & Strategy Pattern
**Location:** [src/Ksfraser/PartnerTypes/](src/Ksfraser/PartnerTypes/)

**Architecture:**
- `PartnerTypeInterface` - Contract for partner types
- `AbstractPartnerType` - Base implementation with conventions
- Concrete types: `SupplierPartnerType`, `CustomerPartnerType`, `BankTransferPartnerType`, `QuickEntryPartnerType`, `MatchedPartnerType`, `ManualSettlementPartnerType`
- `PartnerTypeRegistry` - Registry for dynamic type discovery

**Each partner type defines:**
- `getShortCode()` - 2-char code (SP, CU, BT, QE, MA, MS)
- `getLabel()` - Human-readable name
- `getConstantName()` - Constant suffix (SUPPLIER, CUSTOMER, etc.)
- `getStrategyMethodName()` - Display method name (displaySupplier, etc.)
- `getViewClassName()` - View class name
- `getPriority()` - Sort order

---

### 5.3 PartnerFormFactory
**Location:** [src/Ksfraser/PartnerFormFactory.php](src/Ksfraser/PartnerFormFactory.php)

**Purpose:** Generate partner-type-specific forms with query optimization

**Key Features:**
- Accepts `DataProvider` dependencies (Supplier, Customer, BankAccount, QuickEntry)
- Eliminates redundant database queries (73% reduction: 22→6 queries)
- Method: `renderForm($partnerTypeCode, $lineItemData)`

**Mixed Concerns:**
- ✗ Form generation (rendering)
- ✗ Data provider injection
- ✓ Query optimization abstracted

---

## 6. Dependencies & Coupling Analysis

### 6.1 Cross-Layer Dependencies

```
┌─────────────────────────────────────────────────────┐
│  View Layer (DisplayPartnerType, ViewBiLineItems)  │
│  - Calls pdata functions directly                    │
│  - Uses $_POST global state                         │
│  - Routes to MatchingJEs for scoring                 │
└─────────────────────────────────────────────────────┘
           ↓↓↓
┌─────────────────────────────────────────────────────┐
│  Business Logic (Mixed across layers)               │
│  - Partner search in views (pdata.inc functions)    │
│  - Matching orchestration (MatchingJEs)             │
│  - Keyword building (build_partner_keyword_data)    │
│  - Type mapping (scattered constants)               │
└─────────────────────────────────────────────────────┘
           ↓↓↓
┌─────────────────────────────────────────────────────┐
│  Data Access Layer (pdata.inc, search_partner_...)  │
│  - Raw SQL queries                                  │
│  - Direct db_query/db_fetch calls                   │
│  - No repository abstraction                        │
└─────────────────────────────────────────────────────┘
           ↓↓↓
┌─────────────────────────────────────────────────────┐
│  Database (bi_partners_data, bi_transactions, etc.) │
└─────────────────────────────────────────────────────┘
```

### 6.2 Global State Dependencies

| Global | Used In | Issues |
|--------|---------|--------|
| `$_POST['partnerType'][$id]` | ViewBiLineItems | State mutation in views |
| `$_POST["partnerId_$id"]` | DisplayPartnerType methods | Scattered form state |
| `TB_PREF` | All data functions | Hardcoded table prefix |
| Transaction constants ST_* | Type mapping | Scattered throughout |
| Partner type constants PT_* | Type checks | Implicit contracts |
| `KEYWORD_CLUSTERING_FACTOR` | search_partner_keywords | Config via constant |

### 6.3 Hidden Dependencies

| Service | Dependency | Type | Issue |
|---------|-----------|------|-------|
| displayCustomerPartnerType | `class.fa_customer_payment.php` | External module | Tight coupling |
| getDisplayMatchingTrans | FA API (get_trans_counterparty) | External | Business logic depends on FA |
| search_partner_by_keywords | ConfigService | Optional, degradable | Config loading fragile |
| build_partner_keyword_data | FA system integration | External | Type mapping to FA types |

---

## 7. Data Flow Summary

### 7.1 Partner Search Flow

```
User selects partner type (SP/CU/BT/QE/MA/ZZ)
  ↓
displayPartnerType() routes based on type
  ↓
Type-specific display method called:
  - displaySupplierPartnerType()
  - displayCustomerPartnerType()
  - displayBankTransferPartnerType()
  - etc.
  ↓
Each method calls search_partner_by_bank_account():
  - Searches bi_partners_data table
  - Uses LIKE on 'data' field
  - Returns first match or empty
  ↓
If match found:
  - Populate $_POST with partner ID
  - Pre-select in form dropdown
  ↓
User reviews and can change selection
  ↓
Form submitted with selected partner_id
```

### 7.2 Keyword Building Flow

```
build_partner_keyword_data.php loads
  ↓
Get all settled transactions (fa_trans_no > 0)
  ↓
For each transaction:
  - Extract text from account, title, memo, etc.
  - Extract keywords via extract_keywords()
  - Look up partner via get_partner_from_fa_trans()
  ↓
For each keyword:
  - Increment occurrence in bi_partners_data
  - CREATE if not exists, UPDATE occurrence_count if exists
  ↓
Result: Knowledge base for keyword searching
```

### 7.3 Keyword Search Flow

```
Transaction text provided (memo, account, title)
  ↓
search_partner_by_keywords() called
  ↓
Extract keywords from text
  ↓
Query bi_partners_data for any keyword matches
  ↓
Aggregate by partner, sum occurrence counts
  ↓
Apply co-occurrence clustering bonus
  ↓
Calculate confidence (keyword coverage + score strength)
  ↓
Sort by keyword count, then score
  ↓
Return top N results with confidence scores
```

### 7.4 Matching Orchestration Flow

```
BiLineItem::display() called
  ↓
getDisplayMatchingTrans() called
  ↓
MatchingJEs finds GL matches (journal entry scoring)
  ↓
If 1-2 matches with score ≥ 50:
  - Set partner type to 'SP' (supplier) or 'ZZ' (generic)
  - Store in formData
  ↓
displayPartnerType() routes to appropriate display
  ↓
Type-specific display renders form
```

---

## 8. Decomposition Mapping

### 8.1 Proposed Bounded Contexts

#### **Context 1: Partner Data Management**
**Responsibility:** CRUD operations on partner data

Files:
- `includes/pdata.inc` → Repository implementation
- New: `src/.../Repositories/PartnerDataRepository.php`

Operations:
- `getPartnerData($partnerId, $type, $detailId):PartnerData`
- `savePartnerData(PartnerData $entity):void`
- `findByBankAccount($type, $account):PartnerData`
- `findAllByKeyword($keyword):PartnerData[]`

---

#### **Context 2: Pattern Matching & Scoring**
**Responsibility:** Intelligent partner suggestion via keywords

Files:
- `includes/search_partner_keywords.inc` → Matching service
- New: `src/.../Services/PartnerMatchingService.php`

Operations:
- `searchByKeywords($partnerType, $text, $limit):ScoredMatch[]`
- `suggestPartner($partnerType, $text):ScoredMatch|null`
- `calculateScore($partners, $matchedKeywords):int`
- `calculateConfidence($scores):float`

---

#### **Context 3: Entity Matching (GL/FA Integration)**
**Responsibility:** Match bank transactions to GL entries

Files:
- `src/Ksfraser/FaBankImport/models/MatchingJEs.php` → Existing
- New: `src/.../Services/TransactionMatchingService.php`

Operations:
- `findMatches(BiLineItem $trans):(GLEntry)[]`
- `scoreMatch(GLEntry, BiLineItem):int`
- `getTopMatch(BiLineItem):GLEntry`

---

#### **Context 4: Partner Type Management**
**Responsibility:** Partner type selection and routing

Files:
- `src/Ksfraser/PartnerTypes/` → Existing (needs strengthening)
- `src/Ksfraser/PartnerFormFactory.php` → Existing

Operations:
- `getPartnerType(code):PartnerTypeInterface`
- `renderForm($type, $data):HtmlElement`
- `isValidType(code):bool`

---

#### **Context 5: Keyword Intelligence**
**Responsibility:** Build and maintain keyword training data

Files:
- `build_partner_keyword_data.php` → Refactor
- New: `src/.../Services/KeywordTrainingService.php`

Operations:
- `extractKeywords($text):string[]`
- `recordPartnerKeywords($partnerId, $text, $type):void`
- `buildTrainingData(bool $dryRun):Statistics`
- `getKeywordOccurrence($keyword, $partnerType):int`

---

### 8.2 Service Interfaces (Proposed)

```php
interface PartnerRepositoryInterface {
    public function findById(int $partnerId, int $type): ?PartnerData;
    public function save(PartnerData $partner): void;
    public function findByBankAccount(int $type, string $account): ?PartnerData;
    public function findByKeyword(string $keyword): PartnerData[];
}

interface PartnerMatchingServiceInterface {
    public function searchByKeywords(
        int $partnerType,
        string $searchText,
        int $limit = 10
    ): array;  // Returns ScoredMatch[]
    
    public function suggestPartner(
        int $partnerType,
        string $searchText
    ): ?ScoredMatch;
}

interface TransactionMatcherInterface {
    public function findMatches(BiLineItem $transaction): array;
    public function getTopMatch(BiLineItem $transaction): ?GLEntry;
    public function scoreMatch(GLEntry $entry, BiLineItem $transaction): int;
}

interface KeywordServiceInterface {
    public function extractKeywords(string $text): array;
    public function recordKeywords(
        int $partnerId,
        string $text,
        int $partnerType
    ): void;
    public function buildTrainingData(bool $dryRun = false): array;
}
```

---

## 9. Global Constants & Configuration

### 9.1 Partner Type Constants (Scattered)

```php
// From transaction types
ST_SUPPAYMENT = 22
ST_SUPPCREDIT = 21
ST_CUSTPAYMENT = 12
ST_CUSTCREDIT = 11
ST_BANKPAYMENT = 1
ST_BANKTRANSFER = 4
ST_BANKDEPOSIT = 2

// From partner types
PT_SUPPLIER = 11
PT_CUSTOMER = 21

// Partner type codes (2-char strings)
'SP' => Supplier
'CU' => Customer
'BT' => Bank Transfer
'QE' => Quick Entry
'MA' => Matched (existing transaction)
'MS' => Manual Settlement
'ZZ' => Generic (matched transaction)
```

### 9.2 Configuration Points

| Config | Current | Used In | Needed |
|--------|---------|---------|--------|
| KEYWORD_CLUSTERING_FACTOR | 0.2 | search_partner_keywords | Tunable |
| Min keyword length | 3 | build_partner_keyword_data | Configurable |
| Stopwords list | Hardcoded | Both keyword functions | Database/config |
| ML score threshold | 50 | getDisplayMatchingTrans | Configurable |
| Match count threshold | 3 | getDisplayMatchingTrans | Configurable |

---

## 10. SQL Patterns & Data Structure

### 10.1 bi_partners_data Table

```sql
CREATE TABLE `{TB_PREF}bi_partners_data` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `partner_id` int(11) NOT NULL,
  `partner_detail_id` int(11) NOT NULL,
  `partner_type` int(11) NOT NULL,
  `data` varchar(256) NOT NULL,
  `occurrence_count` int(11) DEFAULT 1,  -- Added for keyword scoring
  `updated_ts` timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_partner_data` (partner_id, partner_detail_id, partner_type, data)
);
```

### 10.2 Query Patterns

**Current (Inefficient):**
```sql
-- Substring search (slow)
SELECT * FROM bi_partners_data
WHERE data LIKE '%text%' AND partner_type = X

-- Returns all rows, no aggregation
SELECT * FROM bi_partners_data WHERE data LIKE '%keyword%'
```

**Proposed (Optimized):**
```sql
-- Exact keyword search with scoring
SELECT 
    partner_id,
    partner_detail_id,
    partner_type,
    SUM(occurrence_count) as score
FROM bi_partners_data
WHERE partner_type = ? 
  AND data IN (?, ?, ...)  -- Exact matches, not LIKE
GROUP BY partner_id, partner_detail_id, partner_type
ORDER BY score DESC
LIMIT 10

-- Index optimization
CREATE INDEX idx_type_data ON bi_partners_data(partner_type, data)
```

---

## 11. Testing Implications

### 11.1 What's Currently Hard to Test

| Issue | Root Cause | Impact |
|-------|-----------|--------|
| Partner search logic locked in views | Business logic in display | No unit tests possible |
| Global $_POST manipulation | State mutation | Integration tests only |
| Raw SQL in functions | No DAL abstraction | Can't mock queries |
| External FA coupling | Tight integration | Can't isolate matching |
| Keyword extraction hardcoded | Logic embedded in script | Hard to test variations |

### 11.2 Testable Units After Decomposition

```php
// Test keyword extraction independently
$keywords = $service->extractKeywords("Internet Transfer");
// Assert: ['internet', 'transfer']

// Test scoring algorithm without DB
$score = $scorer->calculateScore([
    ['occurrence_count' => 50],
    ['occurrence_count' => 45]
]);
// Assert: score with clustering bonus applied

// Test partner type routing
$type = $registry->getPartnerType('SP');
// Assert: returns SupplierPartnerType instance

// Test matching logic isolated from FA
$matches = $matcher->findMatches($lineItem);
// Assert: returns scored GL entries
```

---

## 12. Key Refactoring Priorities

### High Priority (Architectural)
1. Extract Partner Type Registry (already partial)
2. Create PartnerRepository interface
3. Separate matching orchestration from views
4. Move business logic out of display methods

### Medium Priority (Efficiency)
1. Optimize keyword search queries (exact match vs LIKE)
2. Implement query result caching
3. Batch keyword operations
4. Add database indexes on search fields

### Low Priority (Polish)
1. Standardize error handling/exceptions
2. Add comprehensive logging
3. Create configuration management
4. Build monitoring/instrumentation

---

## 13. Code Smells Summary

| Smell | Location | Severity |
|-------|----------|----------|
| God functions | set_partner_data, process_all_transactions | High |
| Mixed concerns | ViewBiLineItems display methods | High |
| Type branching everywhere | pdata.inc, views, builders | High |
| Magic strings | Partner type codes (SP, CU, etc.) | Medium |
| Raw SQL in functions | All pdata.inc functions | High |
| Global state mutation | $_POST in views | High |
| Hardcoded stopwords | Both keyword extractors | Low |
| Unbounded loops | update_partner_data accumulation | Medium |
| Tight coupling to FA | get_partner_from_fa_trans | High |
| Configuration duplication | Multiple copies of keyword extraction | Medium |

---

## Conclusion

The partner matching system requires **systematic decomposition** across five bounded contexts:
1. **Data Management** (CRUD)
2. **Pattern Matching** (Keywords/Scoring)
3. **Transaction Matching** (GL integration)
4. **Partner Types** (Routing/Strategy)
5. **Keyword Intelligence** (Learning/Training)

This separation will enable:
- ✓ Independent testing of business logic
- ✓ Extensible partner type system
- ✓ Database query optimization
- ✓ Configuration management
- ✓ Reusable services across the codebase
- ✓ Clear separation of concerns
