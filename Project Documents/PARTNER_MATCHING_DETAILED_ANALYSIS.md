# Partner Matching Architecture - Detailed Analysis

## Executive Summary
The partner matching system consists of (3) separate subsystems with significant coupling and SRP violations. This analysis covers all current implementations to inform Phase 0 refactoring into a clean domain-driven architecture.

---

## 1. VIEW LAYER: class.ViewBiLineItems.php

**Location**: 
- Root: `class.ViewBiLineItems.php` (legacy, deprecated as of 20251106)
- PROD: `PROD/class.ViewBiLineItems.php`
- Src: `src/Ksfraser/FaBankImport/class.ViewBiLineItems.php`
- Also in: `src/Ksfraser/View/BiLineItemView.php` (refactored version)

### Main Methods

#### `displayPartnerType()`
**Purpose**: Dispatcher that switches on `$_POST['partnerType'][$this->id]` to display partner selection form

```php
function displayPartnerType()
{
    switch($_POST['partnerType'][$this->id]) {
        case 'SP':  // Supplier
            $this->displaySupplierPartnerType();
            break;
        case 'CU':  // Customer
            $this->displayCustomerPartnerType();
            break;
        case 'BT':  // Bank Transfer
            $this->displayBankTransferPartnerType();
            break;
        case 'QE':  // Quick Entry
            $this->displayQuickEntryPartnerType();
            break;
        case 'MA':  // Matched
            $this->displayMatchedPartnerType();
            break;
        case 'ZZ':  // Existing matched item
            // Handle hidden fields for pre-matched transaction
            break;
    }
}
```

**Responsibilities**:
- Accept form input
- Determine partner type
- Route to correct display method
- Handle hidden field output

#### `displaySupplierPartnerType()`
**Purpose**: Display supplier selection dropdown with bank account matching

```php
function displaySupplierPartnerType()
{
    $matched_supplier = [];
    if (empty($this->partnerId)) {
        $matched_supplier = search_partner_by_bank_account(PT_SUPPLIER, $this->otherBankAccount);
        if (!empty($matched_supplier)) {
            $this->partnerId = $_POST["partnerId_$this->id"] = $matched_supplier['partner_id'];
        }
    }
    label_row(_("Payment To:"), supplier_list("partnerId_$this->id", $matched_supplier, false, false));
}
```

**Responsibilities**:
- Call `search_partner_by_bank_account()` to find matching supplier
- Set `$this->partnerId` and `$_POST` from match
- Render FrontAccounting dropdown helper

#### `displayCustomerPartnerType()`
**Purpose**: Display customer selection with optional branch dropdown

```php
function displayCustomerPartnerType()
{
    if (empty($this->partnerId)) {
        $match = search_partner_by_bank_account(PT_CUSTOMER, $this->otherBankAccount);
        if (!empty($match)) {
            $this->partnerId = $_POST["partnerId_$this->id"] = $match['partner_id'];
            $this->partnerDetailId = $_POST["partnerDetailId_$this->id"] = $match['partner_detail_id'];
        }
    }
    
    $cust_text = customer_list("partnerId_$this->id", null, false, true);
    if (db_customer_has_branches($this->partnerId)) {
        $cust_text .= customer_branches_list($this->partnerId, "partnerDetailId_$this->id", ...);
    } else {
        hidden("partnerDetailId_$this->id", ANY_NUMERIC);
        $_POST["partnerDetailId_$this->id"] = ANY_NUMERIC;
    }
    label_row(_("From Customer/Branch:"), $cust_text);
}
```

**Responsibilities**:
- Call `search_partner_by_bank_account()` 
- Handle branch logic (customers have branches, suppliers don't)
- Render customer dropdown + optional branch dropdown
- Show allocatable invoices if available

#### `displayBankTransferPartnerType()`
**Purpose**: Display bank account selection for transfers

```php
function displayBankTransferPartnerType()
{
    if (empty($_POST["partnerId_$this->id"])) {
        $match = search_partner_by_bank_account(ST_BANKTRANSFER, $this->otherBankAccount);
        if (!empty($match)) {
            $_POST["partnerId_$this->id"] = $match['partner_id'];
            $_POST["partnerDetailId_$this->id"] = $match['partner_detail_id'];
        }
    }
    
    label_row(_($rowlabel), bank_accounts_list("partnerId_$this->id", $_POST["partnerId_$this->id"], ...));
    $this->partnerId = $_POST["partnerId_$this->id"];
}
```

**Responsibilities**:
- Call `search_partner_by_bank_account()` for bank transfer
- Context-aware label (to/from based on debit/credit)
- Render bank accounts dropdown

### Violations in ViewBiLineItems

| Category | Issue | Impact |
|----------|-------|--------|
| **SRP** | Knows about all partner types (SP, CU, BT, QE, MA, ZZ) | Hard to add new partner types |
| **SRP** | Mixes form presentation with business logic (form submission, $_POST, echoing HTML) | Can't reuse without output buffering |
| **Coupling** | Directly calls `search_partner_by_bank_account()` 3+ times | Tightly coupled to search implementation |
| **Coupling** | Depends on FrontAccounting UI helpers (`supplier_list()`, `customer_list()`, `bank_accounts_list()`) | Can't unit test without mocking FA |
| **Testability** | No return values (uses echo) | Must capture output for testing |
| **Testability** | Uses global `$_POST` directly | Must manipulate superglobals in tests |
| **Maintainability** | Repetitive code pattern: check emptiness, search, set $_POST | Duplicated logic across methods |

---

## 2. DATA ACCESS LAYER: pdata.inc

**Location**: `includes/pdata.inc`

### Main Functions

#### `get_partner_data($partner_id, $partner_type, $partner_detail_id)`
**Purpose**: Fetch partner data record by composite key

```php
function get_partner_data($partner_id, $partner_type, $partner_detail_id) {
    $sql = "SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE partner_id=" . db_escape($partner_id) . " 
            AND partner_type=" . db_escape($partner_type);
    
    if ($partner_type == PT_CUSTOMER OR $partner_type == ST_BANKTRANSFER)
        $sql .= " AND partner_detail_id=" . db_escape($partner_detail_id);
    
    $result = db_query($sql, "could not get partner data");
    return db_fetch($result);
}
```

**Responsibilities**:
- Build SQL query with conditional WHERE
- Escape parameters
- Execute query
- Fetch and return single row

#### `set_partner_data($partner_id, $partner_type, $partner_detail_id, $data)`
**Purpose**: Insert or update partner data with deduplication

```php
function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    $arr = get_partner_data($partner_id, $partner_type, $partner_detail_id);
    
    if (count($arr) > 0) {
        if ($arr['data'] == $data) {
            return;  // No update needed
        } else {
            $match = search_partner_by_bank_account($partner_type, $data);
            if ($match['partner_id'] == $partner_id) {
                return;  // Already there
            }
        }
    }
    
    $sql = "INSERT INTO " . TB_PREF . "bi_partners_data
            (partner_id, partner_type, partner_detail_id, data) 
            VALUES(...) 
            ON DUPLICATE KEY UPDATE data=" . db_escape($data);
    
    db_query($sql, 'Could not update partner');
}
```

**Responsibilities**:
- Check if record exists
- Check if data matches (avoid duplicates)
- Perform cross-search validation
- Insert or update via upsert pattern

#### `search_partner_by_bank_account($partner_type, $needle)`
**Purpose**: Find partner by bank account (LIKE search)

```php
function search_partner_by_bank_account($partner_type, $needle) {
    if (empty($needle))
        return array();
    
    $sql = "SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE partner_type=" . db_escape($partner_type) . " 
            AND data LIKE '%" . $needle . "%' LIMIT 1";
    
    $result = db_query($sql, "could not get search partner");
    return db_fetch($result);
}
```

**Responsibilities**:
- Validate input
- Build % LIKE search query
- Return single best match
- **NO SQL PARAMETER ESCAPING FOR $needle** ⚠️

#### `search_partner_data_by_needle($needle)`
**Purpose**: Find all partner data records matching needle

```php
function search_partner_data_by_needle($needle) {
    if (empty($needle))
        return array();
    
    $sql = "SELECT * FROM " . TB_PREF . "bi_partners_data
            WHERE data LIKE '%".$needle."%'";
    
    $result = db_query($sql, "could not get search partner");
    
    $arr = array();
    while ($row = db_fetch($result)) {
        $arr[] = $row;
    }
    return $arr;
}
```

**Responsibilities**:
- Search across all partner types (no type filtering)
- Return all matches as array
- **NO SQL PARAMETER ESCAPING** ⚠️

### Violations in pdata.inc

| Category | Issue | Impact |
|----------|-------|--------|
| **SRP** | Multiple search strategies (bank account, needle, eventual keywords) | Confusing API |
| **SRP** | Deduplication logic mixed with data persistence | Hard to test separately |
| **Security** | SQL injection vulnerability in `search_partner_data_by_needle()` | **CRITICAL** |
| **Security** | Unescaped LIKE operator in `search_partner_by_bank_account()` | **HIGH** |
| **Consistency** | `search_partner_data_by_needle()` has no type filtering | Inconsistent with `search_partner_by_bank_account()` |
| **Error Handling** | Returns empty array on error (indistinguishable from no matches) | Silent failures |
| **Testability** | Direct database calls with no interface | Can't unit test without DB |
| **Design** | Procedural functions in include file | Not namespace-aware, no OOP |

---

## 3. KEYWORD SEARCH LAYER: search_partner_keywords.inc

**Location**: `includes/search_partner_keywords.inc`

### Main Functions

#### `search_partner_by_keywords($partner_type, $search_text, $limit = 10)`
**Purpose**: Intelligent partner matching with occurrence-count scoring and co-occurrence clustering bonus

```php
function search_partner_by_keywords($partner_type, $search_text, $limit = 10) {
    if (empty($search_text)) return array();
    
    $keywords = extract_keywords_for_search($search_text);
    if (empty($keywords)) return array();
    
    // Build query for any matching keywords
    $keyword_conditions = array();
    foreach ($keywords as $keyword) {
        $keyword_conditions[] = "data = " . db_escape($keyword);
    }
    $where_clause = "(" . implode(" OR ", $keyword_conditions) . ")";
    
    if ($partner_type) {
        $where_clause = "partner_type = " . db_escape($partner_type) . " AND " . $where_clause;
    }
    
    $sql = "SELECT partner_id, partner_detail_id, partner_type, data as keyword, occurrence_count
            FROM " . TB_PREF . "bi_partners_data
            WHERE " . $where_clause . " 
            ORDER BY occurrence_count DESC";
    
    $result = db_query($sql, "Could not search partner data");
    
    // Aggregate results by partner
    $partner_scores = array();
    while ($row = db_fetch($result)) {
        $partner_key = sprintf("%d-%d-%d", $row['partner_id'], $row['partner_detail_id'], $row['partner_type']);
        
        if (!isset($partner_scores[$partner_key])) {
            $partner_scores[$partner_key] = array(
                'partner_id' => $row['partner_id'],
                'partner_detail_id' => $row['partner_detail_id'],
                'partner_type' => $row['partner_type'],
                'score' => 0,
                'matched_keywords' => array(),
                'keyword_match_count' => 0
            );
        }
        
        $partner_scores[$partner_key]['score'] += $row['occurrence_count'];
        $partner_scores[$partner_key]['matched_keywords'][] = $row['keyword'];
        $partner_scores[$partner_key]['keyword_match_count'] = count($partner_scores[$partner_key]['matched_keywords']);
    }
    
    // Apply co-occurrence clustering bonus
    // Formula: base_score * (1 + (keyword_match_count - 1) * KEYWORD_CLUSTERING_FACTOR)
    foreach ($partner_scores as &$partner) {
        $base_score = $partner['score'];
        $clustering_multiplier = 1 + (($partner['keyword_match_count'] - 1) * KEYWORD_CLUSTERING_FACTOR);
        $partner['score'] = round($base_score * $clustering_multiplier);
        $partner['clustering_bonus'] = round($base_score * ($clustering_multiplier - 1));
    }
    
    // Sort by: 1) keyword count, 2) total score
    usort($partner_scores, function($a, $b) {
        if ($a['keyword_match_count'] != $b['keyword_match_count']) {
            return $b['keyword_match_count'] - $a['keyword_match_count'];
        }
        return $b['score'] - $a['score'];
    });
    
    // Calculate multi-factor confidence scores
    if (!empty($partner_scores)) {
        $top_score = $partner_scores[0]['score'];
        $search_keyword_count = count($keywords);
        
        foreach ($partner_scores as &$partner) {
            $keyword_coverage = $search_keyword_count > 0
                ? ($partner['keyword_match_count'] / $search_keyword_count) * 100
                : 0;
            
            $score_strength = $top_score > 0 
                ? ($partner['score'] / $top_score) * 100
                : 0;
            
            // Weighted: 60% keyword coverage, 40% score strength
            $partner['confidence'] = round(
                ($keyword_coverage * 0.6) + ($score_strength * 0.4),
                1
            );
        }
    }
    
    return array_slice($partner_scores, 0, $limit);
}
```

**Responsibilities**:
- Extract keywords from search text
- Query database for matching keywords
- Aggregate and score by partner
- Apply clustering bonus based on co-occurrence
- Calculate multi-factor confidence
- Sort and limit results

**Algorithm Details**:
1. **Keyword Extraction**: Tokenize, lowercase, remove stopwords, filter min length 3
2. **Database Lookup**: Match ANY keyword (OR logic)
3. **Aggregation**: Group by partner, sum occurrence counts
4. **Clustering Bonus**: Reward partners with multiple matching keywords
   - Formula: `score * (1 + (count-1) * factor)`
   - Factor = 0.2 (20% bonus per additional keyword)
5. **Confidence**: Multi-factor scoring
   - 60% keyword coverage (how many search keywords matched)
   - 40% score strength (relative to top score)

#### `extract_keywords_for_search($text)`
**Purpose**: Tokenize search text into searchable keywords

```php
function extract_keywords_for_search($text) {
    if (empty($text)) return array();
    
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    $stopwords = array(
        'the', 'and', 'or', 'for', 'to', 'from', 'in', 'on', 'at', 'by',
        'with', 'of', 'as', 'is', 'was', 'be', 'are', 'were', 'been',
        'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'this', 'that', 'these', 'those', 'it', 'its', 'an', 'a',
        'payment', 'transaction', 'transfer', 'deposit', 'withdrawal'
    );
    
    $keywords = array();
    foreach ($words as $word) {
        if (strlen($word) >= 3 && !in_array($word, $stopwords)) {
            $keywords[$word] = true;  // Dedup via array key
        }
    }
    
    return array_keys($keywords);
}
```

#### `get_suggested_partner($partner_type, $search_text)`
**Purpose**: Convenience wrapper returning top match

```php
function get_suggested_partner($partner_type, $search_text) {
    $results = search_partner_by_keywords($partner_type, $search_text, 1);
    return !empty($results) ? $results[0] : null;
}
```

### Violations in search_partner_keywords.inc

| Category | Issue | Impact |
|----------|-------|--------|
| **SRP** | Combines keyword extraction, database query, aggregation, scoring, sorting, confidence calculation | One function does too much |
| **Testability** | Direct database calls | Can't unit test without DB |
| **Flexibility** | Hardcoded stopwords list | Can't customize filtering per use case |
| **Flexibility** | Hardcoded clustering factor loading | Config lookup logic mixed with search |
| **Performance** | No index hints or query optimization | Likely slow on large dataset |
| **Maintainability** | Complex aggregation logic in loop | Hard to debug scoring |
| **Design** | Procedural, include-file based | Not namespace-aware |

---

## 4. DATA BUILDER LAYER: build_partner_keyword_data.php

**Location**: `build_partner_keyword_data.php`

### Main Functions

#### `extract_keywords($text)`
**Purpose**: Extract keywords from transaction text (same as search_partner_keywords but separate implementation)

```php
function extract_keywords($text) {
    if (empty($text)) return array();
    
    $min_keyword_length = 3;  // From config or default
    if (class_exists('\Ksfraser\FaBankImport\Config\ConfigService')) {
        try {
            $configService = \Ksfraser\FaBankImport\Config\ConfigService::getInstance();
            $min_keyword_length = (int)$configService->get('pattern_matching.min_keyword_length', 3);
        } catch (\Exception $e) {
            // Fallback to default
        }
    }
    
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    $stopwords = array(
        'the', 'and', 'or', 'for', 'to', 'from', 'in', 'on', 'at', 'by',
        'with', 'of', 'as', 'is', 'was', 'be', 'are', 'were', 'been',
        'has', 'have', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'this', 'that', 'these', 'those', 'it', 'its', 'an', 'a',
        'payment', 'transaction', 'transfer', 'deposit', 'withdrawal'
    );
    
    $keywords = array();
    foreach ($words as $word) {
        if (strlen($word) >= $min_keyword_length && !in_array($word, $stopwords)) {
            $keywords[$word] = true;
        }
    }
    
    return array_keys($keywords);
}
```

**Responsibilities**:
- Load config for min keyword length
- Normalize text to lowercase
- Remove special characters
- Tokenize on whitespace
- Filter stopwords and short words
- Return unique keywords

#### `get_partner_type_from_transaction($row)`
**Purpose**: Map FA transaction type to partner type constant

```php
function get_partner_type_from_transaction($row) {
    $type_map = array(
        ST_SUPPAYMENT => PT_SUPPLIER,      // 22
        ST_SUPPCREDIT => PT_SUPPLIER,      // 21
        ST_BANKDEPOSIT => PT_SUPPLIER,     // 2 (supplier refund)
        ST_CUSTPAYMENT => PT_CUSTOMER,     // 12
        ST_CUSTCREDIT => PT_CUSTOMER,      // 11
        ST_BANKPAYMENT => ST_BANKPAYMENT,  // 1 (Quick Entry)
        ST_BANKTRANSFER => ST_BANKTRANSFER // 4
    );
    
    $fa_trans_type = $row['fa_trans_type'];
    return isset($type_map[$fa_trans_type]) ? $type_map[$fa_trans_type] : null;
}
```

**Responsibilities**:
- Translate between FA transaction types (1, 2, 4, 11, 12, 21, 22, etc.)
- Map to partner type constants (PT_SUPPLIER, PT_CUSTOMER, ST_BANKTRANSFER, ST_BANKPAYMENT)

#### `get_partner_from_fa_trans($trans_no, $trans_type)`
**Purpose**: Look up actual partner ID from FA transaction

```php
function get_partner_from_fa_trans($trans_no, $trans_type) {
    $counterparty = get_trans_counterparty($trans_no, $trans_type);
    
    if (empty($counterparty)) {
        return array('partner_id' => 0, 'partner_detail_id' => 0, 'partner_type' => 0);
    }
    
    $partner_id = 0;
    $partner_detail_id = 0;
    $partner_type = 0;
    
    // Check for supplier
    if (isset($counterparty['supplier_id']) && $counterparty['supplier_id']) {
        $partner_id = $counterparty['supplier_id'];
        $partner_detail_id = -1;  // Suppliers have no branches
        $partner_type = PT_SUPPLIER;
    }
    // Check for customer with branch
    elseif (isset($counterparty['debtor_no']) && $counterparty['debtor_no']) {
        $partner_id = $counterparty['debtor_no'];
        $partner_detail_id = $counterparty['branch_code'] ?? 0;
        $partner_type = PT_CUSTOMER;
    }
    // Check for quick entry
    elseif (isset($counterparty['account_code']) && $counterparty['account_code']) {
        $partner_id = $counterparty['account_code'];
        $partner_detail_id = 0;
        $partner_type = ST_BANKPAYMENT;
    }
    // Check for bank transfer
    elseif ($trans_type == ST_BANKTRANSFER) {
        if (!empty($counterparty) && isset($counterparty[0])) {
            $partner_id = $counterparty[0]['account'] ?? 0;
            $partner_detail_id = 0;
            $partner_type = ST_BANKTRANSFER;
        }
    }
    
    return array(
        'partner_id' => $partner_id,
        'partner_detail_id' => $partner_detail_id,
        'partner_type' => $partner_type
    );
}
```

**Responsibilities**:
- Call `get_trans_counterparty()` (FrontAccounting function)
- Extract partner ID based on transaction type
- Handle branch logic (customer) vs simple ID (supplier)
- Return normalized partner info tuple

#### `increment_keyword_occurrence($partner_id, $partner_detail_id, $partner_type, $keyword)`
**Purpose**: Insert or update keyword occurrence count

```php
function increment_keyword_occurrence($partner_id, $partner_detail_id, $partner_type, $keyword) {
    if (empty($keyword) || !$partner_id || !$partner_type) {
        return false;
    }
    
    $sql = "INSERT INTO " . TB_PREF . "bi_partners_data 
            (partner_id, partner_detail_id, partner_type, data, occurrence_count)
            VALUES (...)
            ON DUPLICATE KEY UPDATE
                occurrence_count = occurrence_count + 1";
    
    return db_query($sql, "Could not update keyword occurrence");
}
```

**Responsibilities**:
- Validate inputs
- Insert new keyword record or increment existing count
- Use upsert pattern (ON DUPLICATE KEY UPDATE)

#### `process_all_transactions($dry_run = false)`
**Purpose**: Bulk process all settled transactions to build keyword database

```php
function process_all_transactions($dry_run = false) {
    $stats = array(
        'transactions_processed' => 0,
        'keywords_added' => 0,
        'keywords_updated' => 0,
        'errors' => 0
    );
    
    // Get settled transactions with FA match
    $sql = "SELECT * FROM " . TB_PREF . "bi_transactions 
            WHERE fa_trans_no > 0 
            AND fa_trans_type > 0
            AND status = 1
            ORDER BY id";
    
    $result = db_query($sql, "Could not get transactions");
    
    while ($row = db_fetch($result)) {
        $stats['transactions_processed']++;
        
        // Get partner from FA transaction
        $partner_info = get_partner_from_fa_trans($row['fa_trans_no'], $row['fa_trans_type']);
        
        if (!$partner_info['partner_id'] || !$partner_info['partner_type']) {
            display_notification("Skipping transaction {$row['id']}: No partner info found");
            continue;
        }
        
        // Collect text from multiple fields
        $text_fields = array(
            $row['account'],
            $row['accountName'],
            $row['transactionTitle'],
            $row['memo'],
            $row['merchant'],
            $row['category']
        );
        
        $all_text = implode(' ', array_filter($text_fields));
        
        // Extract keywords
        $keywords = extract_keywords($all_text);
        
        if (empty($keywords)) {
            continue;
        }
        
        // Add/update each keyword
        if (!$dry_run) {
            foreach ($keywords as $keyword) {
                increment_keyword_occurrence(
                    $partner_info['partner_id'],
                    $partner_info['partner_detail_id'],
                    $partner_info['partner_type'],
                    $keyword
                );
            }
        }
    }
    
    return $stats;
}
```

**Responsibilities**:
- Query all settled transactions
- For each transaction: extract partner info and keywords
- Call `increment_keyword_occurrence()` for each keyword
- Support dry-run mode for preview
- Return statistics

### Violations in build_partner_keyword_data.php

| Category | Issue | Impact |
|----------|-------|--------|
| **SRP** | Multiple concerns: extraction, transaction lookup, partner mapping, occurrence updates | Monolithic script |
| **DRY** | `extract_keywords()` duplicates same logic as in `search_partner_keywords.inc` | Inconsistent behavior if changed |
| **Design** | Procedural page script, not reusable | Hard to call from tests or other code |
| **Testability** | Direct DB calls, global FA function calls | Can't unit test |
| **Error Handling** | `display_notification()` for dry-run, silent failure for actual run | Inconsistent feedback |
| **Configuration** | Multiple hardcoded lists (stopwords, type mappings) | Not configurable |

---

## 5. CROSS-FUNCTIONAL DEPENDENCIES

### Call Graph

```
ViewBiLineItems.displayPartnerType()
├─ displaySupplierPartnerType()
│  └─ search_partner_by_bank_account(PT_SUPPLIER, ...)
├─ displayCustomerPartnerType()
│  └─ search_partner_by_bank_account(PT_CUSTOMER, ...)
├─ displayBankTransferPartnerType()
│  └─ search_partner_by_bank_account(ST_BANKTRANSFER, ...)
└─ displayQuickEntryPartnerType()
   └─ search_partner_by_bank_account(PT_QE, ...)

pdata.inc:set_partner_data()
├─ get_partner_data()
└─ search_partner_by_bank_account()

search_partner_keywords.inc:search_partner_by_keywords()
├─ extract_keywords_for_search()
└─ [direct SQL query]

build_partner_keyword_data.php:process_all_transactions()
├─ get_partner_from_fa_trans()
│  └─ get_trans_counterparty() [FA function]
├─ get_partner_type_from_transaction()
├─ extract_keywords()
└─ increment_keyword_occurrence()
```

### Data Flow

```
Transaction (bank import)
    ↓
ViewBiLineItems.displayPartnerType()
    ↓ (calls)
search_partner_by_bank_account() 
    ↓ (queries)
bi_partners_data table
    ↓ (returns)
$matched_supplier / $matched_customer / $matched_bank
    ↓ (sets)
$_POST['partnerId_$this->id']
$_POST['partnerDetailId_$this->id']
    ↓ (submitted)
Partner selection form
```

### Historical Build Flow

```
FrontAccounting Transaction
    ↓
bi_transactions (imported with fa_trans_no, fa_trans_type)
    ↓ (processed by build_partner_keyword_data.php)
extract_keywords()
get_partner_from_fa_trans()
    ↓
increment_keyword_occurrence()
    ↓
bi_partners_data 
  (partner_id, partner_detail_id, partner_type, data=keyword, occurrence_count)
    ↓ (queried by search_partner_by_keywords on new transaction)
Scored ranking of potential partners
```

---

## 6. CURRENT TEST COVERAGE

### Existing Test Files

1. **BiPartnersDataTest.php** - Tests for `bi_partners_data` class
   - Basic instantiation and table definition
   - `get_partner_data()`, `set_bank_partner_data()`

2. **PartnerDataServiceTest.php** - Unit tests for `PartnerDataService` (refactored version)
   - Mocks `PartnerDataRepositoryInterface`
   - Tests service layer methods

3. **PartnerTypeSelectorViewTest.php** - Views testing partner type selection

4. **BankTransferPartnerTypeViewFinalTest.php** - Specific test for bank transfer view

5. **SupplierPartnerTypeViewFinalTest.php** - Specific test for supplier view

6. **CustomerPartnerTypeViewV2Test.php** - Specific test for customer view

### Test Coverage Gaps

| Layer | Tests Present | Gap |
|-------|---|---|
| View (HTML rendering) | Yes, 3+ files | HTML output format not fully tested |
| Search algorithms | No direct tests | `search_partner_by_keywords()` untested |
| Keyword extraction | No direct tests | Two separate implementations never validated |
| Partner type mapping | Yes (ConcretePartnerTypesTest) | Transaction type → Partner type mapping untested |
| Data persistence | Partial | Upsert logic not tested |
| Security | No | SQL injection vulnerabilities not tested |

---

## 7. IDENTIFIED ARCHITECTURAL PROBLEMS

### 1. **Inconsistent Search Strategies**

Current implementation has THREE SEPARATE search mechanisms:
- `search_partner_by_bank_account()` - Simple LIKE search (System A/Legacy)
- `search_partner_by_keywords()` - Scored keyword search (System B/New)
- `search_partner_data_by_needle()` - Unfiltered search (System C/? status)

**Problem**: Unclear which to use when, possible duplicate data, conflicting scoring

### 2. **Duplicate Keyword Extraction Logic**

Two separate implementations:
- `extract_keywords()` in `build_partner_keyword_data.php`
- `extract_keywords_for_search()` in `search_partner_keywords.inc`

**Problem**: If one changes, the other is not updated, creating data inconsistency

### 3. **Coupling Between Presentation and Data Access**

ViewBiLineItems directly calls `search_partner_by_bank_account()`:
- Can't swap implementations without changing view code
- Can't test view without database
- View code tangled with business logic

**Problem**: Violates dependency inversion principle

### 4. **No Exception Handling**

All functions return empty arrays on failure:
- Can't distinguish "no match found" from "database error"
- Silent failures make debugging hard

**Problem**: No recovery strategies or user feedback

### 5. **Hardcoded Constants and Configuration**

- Stopwords list hardcoded in TWO locations
- Clustering factor defaults in TWO locations
- Min keyword length hardcoded

**Problem**: Configuration scattered, hard to maintain

### 6. **SQL Injection Vulnerabilities**

`search_partner_data_by_needle()` and similar functions have unescaped LIKE clauses:
```php
WHERE data LIKE '%".$needle."%'  // ⚠️ VULNERABLE
```

**Problem**: **SECURITY RISK**

### 7. **No Abstraction Layer**

Direct SQL queries in multiple locations (pdata.inc, search_partner_keywords.inc, build_partner_keyword_data.php)

**Problem**: Can't refactor database schema without changing all three

### 8. **Testability Nightmare**

All functions use:
- Global `$_POST` / `$_GET`
- Direct `db_query()` calls
- FrontAccounting UI helper functions
- Direct echo for output

**Problem**: Can't unit test anything

### 9. **No Domain Model**

There's data (arrays) but no domain concept:
- No `PartnerMatch` entity
- No `SearchResult` value object
- No `PartnerRepository`

**Problem**: Business logic is scattered across functions

### 10. **Inconsistent Return Types**

- `search_partner_by_bank_account()` returns single array or empty array
- `search_partner_by_keywords()` returns array of arrays with different structure
- `search_partner_data_by_needle()` returns array of arrays
- `get_partner_data()` returns single array or empty

**Problem**: Caller must check multiple conditions, no type safety

---

## 8. PHASE 0 REFACTORING TARGETS

### High Priority (Breaking Changes)

1. **Create Domain Model**
   - `PartnerEntity` (immutable)
   - `PartnerMatchResult` (value object with score, confidence)
   - `Keyword` (value object)
   - Partner data DTOs

2. **Create Repository Interface**
   - `PartnerRepositoryInterface`
   - `PartnerSearchRepositoryInterface`
   - Decouple from SQL

3. **Implement Search Service**
   - `PartnerSearchService` - entry point
   - `BankAccountSearchStrategy`
   - `KeywordSearchStrategy`
   - Pluggable strategies

4. **Extract Keyword Logic**
   - `KeywordExtractor` - single implementation
   - `KeywordOccurrenceService`
   - Shared stopwords/config

5. **Create Custom Exceptions**
   - `PartnerNotFoundException`
   - `MultiplePartnersFoundException`
   - `InvalidSearchCriteriaException`

6. **Fix Security**
   - Use parameterized queries everywhere
   - Fix `search_partner_data_by_needle()` SQL injection

### Medium Priority (Testability)

7. **Extract Search Algorithms**
   - Move scoring logic to testable classes
   - Test hypothesis: clustering bonus improves matches

8. **Add Comprehensive Tests**
   - Keyword extraction (edge cases, stopwords)
   - Scoring algorithm (matches expected behavior)
   - Confidence calculation
   - Partner type mapping
   - Ranking algorithm

### Low Priority (Maintainability)

9. **Consolidate Search Strategies**
   - Deprecate unused search methods
   - Mark legacy bank account search for removal

10. **Configuration Management**
   - Store stopwords in database/config
   - Make clustering factor configurable
   - Make min keyword length configurable

---

## Summary Table

| File | Type | Main Purpose | SRP Issues | Coupling | Testing |
|------|------|---|---|---|---|
| `class.ViewBiLineItems.php` | View | Display partner selection forms | ✗ (6 responsibilities) | High (calls search directly) | Hard (echo, $_POST) |
| `pdata.inc` | Data Access | Query/insert partner data | ✗ (search + CRUD + dedup) | High (direct SQL) | Hard (DB calls) |
| `search_partner_keywords.inc` | Business Logic | Score and rank keywords | ✗ (extraction + scoring + sorting) | Medium (direct SQL) | Hard (DB calls) |
| `build_partner_keyword_data.php` | Script | Build keyword database | ✗ (extraction + mapping + building) | High (FA calls + direct SQL) | Hard (script-based) |

---

## Recommendations for Phase 0

1. **Start with Domain Model**: Create entities and value objects in Shared/
2. **Add Interfaces**: Define repository and service contracts
3. **Implement Repositories**: Decouple from SQL with Repository pattern
4. **Extract Services**: Create keyword service, search service
5. **Add Custom Exceptions**: Improve error handling
6. **Fix Security**: Parameterize all queries
7. **Add Tests**: Unit test all new services with mocks
8. **Refactor Views**: Use services instead of direct calls
9. **Retire Legacy**: Mark old search functions deprecated
10. **Document Migration**: Create guides for updating call sites
