# import_statements.php - Detailed Code Analysis

> **Data Corruption Detection:** See related reports in separate repositories:
> - [FA_REP_OrphanedTrans](https://github.com/org/FA_REP_OrphanedTrans) — Orphaned GL entries
> - [FA_REP_ContaminatedTransactions](https://github.com/org/FA_REP_ContaminatedTransactions) — Cross-contaminated GL lines
> - [FA_REP_AmountMismatches](https://github.com/org/FA_REP_AmountMismatches) — GL vs bank amount discrepancies

**Note**: The file does not contain a `process_statement()` function. The primary statement processing function is **`importStatement()`** (lines 243-553).

---

## 1. FUNCTION SIGNATURE & DOCUMENTATION

```php
function importStatement($smt, $file_id = null) 
{
    // $logger = func_num_args() >= 3 ? func_get_arg(2) : null;
}
```

**Parameters:**
- `$smt` (object): Statement object with properties like `account`, `currency`, `statementId`, `transactions`, `smtDate`, `acctid`, `bankid`, `intu_bid`
- `$file_id` (int|null): Optional uploaded file ID to link to statement

**Returns:** 
- string `$message`: Summary string like "new, imported" or "existing, updated" with transaction count

**Docstring:** Missing/incomplete at lines 232-242. Only mentions basic parameters and summary return.

---

## 2. FULL PSEUDOCODE & LINE-BY-LINE VARIABLE TRACKING

### Phase 1: Currency & Statement Date Validation (Lines 245-280)

```
INITIALIZE:
  $message = ''
  $logger = null

CURRENCY HANDLING (Lines 251-280):
  TRY:
    $faAccountNumber = trim($smt->account) ?? ''
    $faBankAccountId = fa_get_bank_account_id_by_number($faAccountNumber)
    
    IF $faBankAccountId != null:
      $ba = get_bank_account($faBankAccountId)  // FA function call
      $faCurrency = $ba['bank_curr_code'] ?? ''
      $ofxCurrency = trim($smt->currency) ?? ''
      
      IF $ofxCurrency == '' AND $faCurrency != '':
        SET $smt->currency = $faCurrency
        FOR EACH $t in $smt->transactions:
          IF $t->currency is missing/empty:
            SET $t->currency = $faCurrency
        CALL bank_import_log_event(...)  // Logging: statement.currency_fallback_applied
      
      ELSE IF $faCurrency != '' AND $ofxCurrency != '' AND $faCurrency != $ofxCurrency:
        CALL bank_import_log_event(...)  // Logging: statement.currency_mismatch
  
  CATCH Throwable: 
    // Silent - never blocks import flow

STATEMENT DATE PATCHING (Lines 282-304):
  IF $smt->smtDate is missing/invalid:
    $lastDate = null
    FOR EACH $t in $smt->transactions:
      IF $t->date is valid:
        IF $lastDate is null OR $date > $lastDate:
          SET $lastDate = $date
    
    IF $lastDate != null:
      SET $smt->smtDate = $lastDate
```

### Phase 2: Statement UPSERt (Lines 305-337)

```
INCLUDE FILE: class.bi_statements.php
CREATE: $bis = new bi_statements_model()

POPULATE OBJECT:
  $bis->set("bank", $smt->bank)
  $bis->set("statementId", $smt->statementId)
  $exists = $bis->statement_exists()  // DB query: SELECT
  $bis->obj2obj($smt)  // Copy all stmt properties

IF NOT $exists:
  SQL: $bis->hand_insert_sql()  // Generates INSERT statement
  EXECUTE: db_query($sql, "could not insert transaction")
  $smt_id = db_insert_id()
  $bis->set("id", $smt_id)
  SET $message .= "new, imported"

ELSE:
  $storedDate = $bis->get('smtDate')
  $newDate = $smt->smtDate ?? null
  
  IF $storedDate == '0000-00-00' AND $newDate != '0000-00-00':
    SQL: UPDATE bi_statements SET smtDate = ? WHERE id = ?
    EXECUTE: db_query($updateSql)
    DISPLAY: "Statement date updated..."
  
  CALL $bis->update_statement()  // DB query: UPDATE
  DISPLAY: "Updated Statement..."
  SET $message .= "existing, updated"

EXTRACT: $smt_id = $bis->get("id")

LOG: bank_import_log_event(...)  // statement.upserted
  - statement_id, statement_identifier, existed, file_id
```

### Phase 3: File Linking (Lines 339-356)

```
IF $file_id != null:
  TRY:
    $uploadService = FileUploadService::create()
    CALL $uploadService->linkToStatements($file_id, [$smt_id])  // DB: INSERT link
    LOG: bank_import_log_event(..., 'statement.file_linked', ...)
  CATCH Exception:
    DISPLAY ERROR: "Failed to link file..."
    LOG: bank_import_log_event(..., 'statement.file_link_failed', ...)
```

### Phase 4: Transaction Processing Loop (Lines 358-539)

```
INITIALIZE COUNTERS:
  $newinserted = 0
  $dupecount = 0
  $dupeupdated = 0

INCLUDE FILE: class.bi_transactions.php

FOR EACH $id, $t IN $smt->transactions:
  DISPLAY: "Processing transaction"
  SET: set_time_limit(0)  // PHP time limit reset
  
  TRY:
    unset($bit)  // Clear previous transaction object
    
    TRY:
      CREATE: $bit = new bi_transactions_model()
    CATCH Exception:
      DISPLAY ERROR + file/line
  
  CATCH Exception:
    DISPLAY NOTIFICATION + file/line
  
  CALL: $bit->trz2obj($t)  // Populate transaction object
  
  SET: $bit->set("smt_id", $smt_id)
  
  // Account metadata injection:
  IF $smt->acctid exists AND $bit->acctid is empty:
    SET: $bit->set('acctid', $smt->acctid)
  
  IF $smt->bankid exists:
    SET: $bit->set('bankid', $smt->bankid)
  
  IF $smt->intu_bid exists:
    SET: $bit->set('intu_bid', $smt->intu_bid)
  
  // EXTERNAL SERVICE CALL:
  TRY:
    IF isset($GLOBALS['db']):
      CALL ContactImportHelper::attachContactIdFromParserTransaction(
        $GLOBALS['db'], $smt, $t
      )
  CATCH Throwable:
    @error_log("ImportStatements: contact helper failed: ...")
  
  // DUPLICATE CHECK:
  CHECK: $dupe = $bit->trans_exists()  // DB query: SELECT
  
  IF $dupe:
    INCREMENT: $dupecount++
    // Note: trans_exists() loads object from DB
    // NO insert (Mantis #2948)
  
  ELSE:
    SQL: $sql = $bit->hand_insert_sql()  // Generate INSERT
    EXECUTE: db_query($sql, "could not insert transaction")
    $t_id = db_insert_id()
    INCREMENT: $newinserted++

END FOR

// TRANSACTION SUMMARY:
SET: $message .= ' ' . count($smt->transactions) . ' transactions'
DISPLAY: "Inserted transactions: $newinserted"
DISPLAY: "Duplicates Total: $dupecount"
DISPLAY: "Updated Duplicates: $dupeupdated"

LOG: bank_import_log_event(...)  // statement.transactions_summary
  - statement_id, transactions_total, inserted, duplicates, duplicates_updated

RETURN: $message
```

---

## 3. SQL QUERIES - COMPLETE LIST

| # | Query Type | Approximate Line | Context | Function | Notes |
|---|-----------|-----------------|---------|----------|-------|
| 1 | SELECT | 296 | `$bis->statement_exists()` | Wrapped in model | Checks if statement already in DB |
| 2 | INSERT | 326 | `$bis->hand_insert_sql()` → `db_query()` | New statement insert | Creates new `bi_statements` record |
| 3 | UPDATE | 333 | `UPDATE bi_statements SET smtDate = ? WHERE id = ?` | Direct inline SQL | Updates statement date from 0000-00-00 |
| 4 | UPDATE | 337 | `$bis->update_statement()` | Model method | Updates existing statement record |
| 5 | SELECT/INSERT | 343 | `$uploadService->linkToStatements()` | Service method | Links file to statement record |
| 6 | SELECT | 380 | `$bit->trans_exists()` | Model method | Checks if transaction already in DB |
| 7 | INSERT | 406 | `$bit->hand_insert_sql()` → `db_query()` | New transaction insert | Creates new `bi_transactions` record |

**Database impacts:**
- **2 writes to `bi_statements`** (1 INSERT or 1 UPDATE on statement date, 1 UPDATE via model)
- **1-2 writes to `bi_uploaded_files_statements`** (if file_id provided)
- **Multiple writes to `bi_transactions`** (one INSERT per non-duplicate transaction)
- **No transaction wrapping** (no BEGIN/COMMIT seen) - **SQL commits immediately**

---

## 4. GLOBALS, $_SESSION, $_POST ACCESSES

### GLOBALS

| Line(s) | Variable | Context | Read/Write | Purpose |
|---------|----------|---------|-----------|---------|
| 382 | `$GLOBALS['db']` | Logic check | Read | Passed to `ContactImportHelper::attachContactIdFromParserTransaction()` |
| N/A | N/A | File-level | Read (implicit) | FA functions: `get_bank_account()`, `fa_get_bank_account_id_by_number()`, etc. |

### $_SESSION

| Line(s) | Key | Context | Read/Write | Purpose |
|---------|-----|---------|-----------|---------|
| (Implicit) | Entry point | `$formSubmission` object reads state | Read | Determines which state/function to call |
| (Implicit) | Entry point | `$_SESSION['bank_import_state']` | Write | Stored for next request in state machine (line 1232-1234) |
| 343 | `$_SESSION['bank_import_run_log_path']` | Optional in file linking | Write (indirect via service) | Imported run log path |

### $_POST

| Line(s) | Key | Context | Read/Write | Purpose |
|-----------|-----|---------|-----------|---------|
| N/A | N/A | Not accessed directly in `importStatement()` | - | Post data handling is in wrapper functions (`parse_uploaded_files()`, `resolve_account_mappings()`, etc.) |

**Note**: `importStatement()` is a worker function called from `import_statements()` (line 95-96) which retrieves statements from `$_SESSION['statements']` (line 93), not `$_POST`.

---

## 5. EXTERNAL SERVICE CALLS & SIDE EFFECTS

### A. `ContactImportHelper::attachContactIdFromParserTransaction()` (Lines 379-386)

```php
\Ksfraser\FaBankImport\Import\ContactImportHelper::attachContactIdFromParserTransaction(
    $GLOBALS['db'], $smt, $t
);
```

**Side Effects:**
- May modify `$t` object (adds/updates contact-related properties)
- Non-blocking: errors logged but don't halt import
- Executes in its own try-catch

**Contact Data Pollution Risk:**
- Modifies transaction object in-place
- No validation that modified properties are safe for subsequent DB insert

---

### B. `FileUploadService::linkToStatements()` (Lines 343-344)

```php
$uploadService = FileUploadService::create();
$uploadService->linkToStatements($file_id, array($smt_id));
```

**Side Effects:**
- Inserts record into `bi_uploaded_files_statements` junction table
- Establishes bidirectional link: file ↔ statement
- May create directory/log files

**Error Handling:**
- Caught and logged, error displayed to user
- Doesn't block statement insert

---

### C. `bank_import_log_event()` (Called multiple times)

**Lines:** 278, 281, 318, 349, 364, 376, 482, 486, etc.

```php
bank_import_log_event($logger, $eventName, $context_array);
```

**Side Effects:**
- Writes to JSON/structured log file
- Non-blocking: errors silently caught

**Log Events:**
- `statement.currency_fallback_applied`
- `statement.currency_mismatch`
- `statement.upserted`
- `statement.file_linked` / `statement.file_link_failed`
- `statement.transactions_summary`

---

## 6. CONTROL FLOW DECISION POINTS & NESTING DEPTH

```
importStatement()
│
├─ TRY [Depth 1] Currency/date logic (Lines 250-280)
│  ├─ IF accounted matched [Depth 2]
│  │  ├─ IF currency fallback needed [Depth 3]
│  │  │  └─ FOR EACH transaction [Depth 4]
│  │  │     └─ IF currency missing [Depth 5] ← MAX DEPTH HERE
│  │  ├─ ELSE IF currency mismatch [Depth 3]
│
├─ IF statement date invalid [Depth 1]
│  ├─ FOR EACH transaction [Depth 2]
│  │  ├─ IF valid date [Depth 3]
│  │  │  └─ IF first or greater [Depth 4]
│
├─ CREATE object & check exists [Depth 1]
│  ├─ IF NOT exists [Depth 2]
│  │  ├─ Execute INSERT [Depth 3]
│  ├─ ELSE [Depth 2]
│  │  ├─ IF date update needed [Depth 3]
│  │  │  └─ Execute UPDATE [Depth 4]
│  │  ├─ Execute UPDATE (model method) [Depth 3]
│
├─ IF file_id provided [Depth 1]
│  ├─ TRY [Depth 2]
│  │  ├─ Call FileUploadService [Depth 3]
│  ├─ CATCH [Depth 2]
│
├─ FOR EACH transaction [Depth 1] ← LONGEST LOOP
│  ├─ TRY [Depth 2]
│  │  ├─ TRY create object [Depth 3]
│  │  ├─ CATCH [Depth 3]
│  ├─ CATCH [Depth 2]
│  ├─ IF metadata injection [Depth 2]
│  ├─ TRY ContactImportHelper [Depth 2]
│  │  ├─ IF $GLOBALS['db'] exists [Depth 3]
│  │  ├─ CATCH [Depth 2]
│  ├─ IF duplicate exists [Depth 2]
│  ├─ ELSE [Depth 2]
│  │  ├─ Execute INSERT [Depth 3]
```

**Nesting Complexity:**
- **Max nesting depth**: 5 levels (in currency fallback loop)
- **Transaction loop**: Longest-running branch, iterates hundreds/thousands of times
- **Multiple independent decision branches**: ~10 major fork points

---

## 7. ERROR HANDLING & TRANSACTION PATTERNS

### Current Error Handling

| Layer | Strategy | Lines | Coverage |
|-------|----------|-------|----------|
| Currency validation | Try-catch, silent fail | 250-280 | Non-blocking |
| Statement UPSERT | No error handling | 326-337 | Direct `db_query()` calls |
| File linking | Try-catch, error displayed | 342-356 | Service exception caught |
| Transaction creation | Try-catch (nested), error displayed | 368-379 | Partial coverage |
| Transaction check | No error handling | 389 | Direct `db_query()` via model |
| Transaction insert | No error handling | 406-408 | Direct `db_query()` via model |
| Contact helper | Try-catch, error logged | 379-386 | Non-blocking |
| Log events | Silent error handling | All log calls | Never blocks import |

### Transaction Wrapping

**CRITICAL FINDING**: ❌ **NO DATABASE TRANSACTIONS**

- No `BEGIN TRANSACTION` issued
- No explicit `COMMIT` / `ROLLBACK` logic
- Each `db_query()` auto-commits immediately
- **If error occurs mid-transaction-loop**, statement record exists but transactions are partially inserted

Example failure scenario:
```
1. INSERT statement record → COMMITS
2. INSERT transaction 1 → COMMITS
3. INSERT transaction 2 → COMMITS
4. INSERT transaction 3 → FAILS (validation error)
5. Statement exists with only 2/3 transactions
   (No rollback to clean up statement record)
```

---

## 8. IDENTIFIED ISSUES & RISKS

### ⚠️ CRITICAL ISSUES

#### Issue 1: No Transaction Wrapping
**Impact**: Statement/transaction data integrity compromised on partial failure
**Lines**: 326-408
**Symptom**: Orphaned statement records with incomplete transactions

**Fix**: Wrap entire UPSERT + transaction loop in `BEGIN TRANSACTION`:
```php
db_query("BEGIN", "Transaction start failed");
try {
    // ... statement insert/update ...
    foreach ($smt->transactions as $t) {
        // ... transaction insert ...
    }
    db_query("COMMIT", "Commit failed");
} catch (Exception $e) {
    db_query("ROLLBACK", "Rollback failed");
    throw $e;
}
```

---

#### Issue 2: Inline SQL Injection Risk
**Lines**: 333
**Code**: 
```php
$updateSql = "UPDATE ... SET smtDate=" . db_escape($newDate) . " WHERE id=" . db_escape($bis->get('id'));
```
**Risk**: While `db_escape()` is used, this is non-standard (most FA code uses parameterized queries)
**Mitigation**: Use model method instead

---

#### Issue 3: Silent Contact Helper Failure
**Lines**: 379-386
**Code**:
```php
try {
    \Ksfraser\FaBankImport\Import\ContactImportHelper::attachContactIdFromParserTransaction(
        $GLOBALS['db'], $smt, $t
    );
} catch (\Throwable $e) {
    @error_log('ImportStatements: contact helper failed: ' . $e->getMessage());
}
```
**Issues**:
- Error only goes to PHP error log, not displayed to user
- Transaction object may be partially modified before error
- No logging what field was/wasn't set
- Silently continues with potentially corrupted transaction object

---

#### Issue 4: Unset Variable in Loop
**Line**: 368
**Code**: 
```php
foreach($smt->transactions as $id => $t) 
{
    try {
        unset( $bit );  // ← Unnecessary, loop iteration creates new scope
```
**Issues**:
- `$bit` declared outside try block but scope carries across iterations
- If trans_exists() fails mid-loop, `$bit` from previous iteration may be partial
- After loop, `$bit` retains last transaction's data (pollutes function scope)

**Fix**: Declare `$bit` inside try block or use meaningful object cleaning

---

#### Issue 5: Counter Variable Pollution
**Lines**: 358-410
**Code**:
```php
$newinserted=0;
$dupecount=0;
$dupeupdated=0;

foreach($smt->transactions as $id => $t) {
    // ... MODIFIES $t (ContactImportHelper) ...
}
```
**Issues**:
- `$dupeupdated` incremented nowhere in visible code (Mantis #2948 comment suggests old logic removed)
- Counters persist after function (function-level vars survive serialize/unserialize if called again)
- `$message` accumulation at line 362/515 is fragile (no clear end-of-loop detection)

---

#### Issue 6: Statement Date Patch Logic Flawed
**Lines**: 282-304
**Code**:
```php
if (!isset($smt->smtDate) || $smt->smtDate === '' || $smt->smtDate === '0000-00-00' || $smt->smtDate === null) {
    $lastDate = null;
    // ... loop to find latest transaction date ...
}
```
**Issues**:
- `$lastDate` initialized only inside if block
- If `$smt->smtDate` is already valid, no date validation
- What if ALL transactions have invalid dates? `$lastDate` stays null, `$smt->smtDate` never updated

---

#### Issue 7: Logger Parameter Not Used
**Lines**: 245-246
**Code**:
```php
$logger = func_num_args() >= 3 ? func_get_arg(2) : null;
```
**Issues**:
- Function signature shows only 2 params (`$smt`, `$file_id`)
- Third param accessed via `func_get_arg(2)` but **never explicitly in signature**
- Confusing for IDE/type checking
- Should be: `function importStatement($smt, $file_id = null, ?ImportRunLogger $logger = null)`

---

### ⚠️ MODERATE ISSUES

#### Issue 8: ContactImportHelper Modifies Shared Object
**Lines**: 379-386, 406
**Sequence**:
1. `ContactImportHelper::attachContactIdFromParserTransaction()` modifies `$t`
2. `$bit->trz2obj($t)` copies (potentially corrupted) data to transaction object
3. Inserted into DB with modified fields

**Risk**: If contact helper sets invalid contact_id, no validation occurs before DB insert

---

#### Issue 9: Silent Model Method Failures
**Lines**: 296, 389, 407
**Code**:
```php
$exists = $bis->statement_exists();  // No error handling
$dupe = $bit->trans_exists();        // No error handling
$res = db_query($sql, "could not insert transaction");  // db_query() might log and continue
```
**Issues**:
- Model methods may throw exceptions not caught
- `db_query()` error handling is FA framework-dependent
- Return values not validated

---

#### Issue 10: File Linking Loosely Coupled
**Lines**: 342-356
**Issues**:
- File linkage is optional (if `$file_id` is null, silently skipped)
- No indication to caller whether file was linked successfully
- Business logic depends on file link existing later (audit trail broken)

---

#### Issue 11: Incomplete Docstring
**Lines**: 232-242
**Issues**:
- Missing `@param ImportRunLogger|null $logger` documentation
- No `@throws` documentation (though exceptions not expected to propagate)
- No `@deprecated` comment despite note in line 232

---

### 🟡 MINOR ISSUES

#### Issue 12: Magic Numbers
**Lines**: 333
**Code**:
```php
$storedDate = $bis->get('smtDate');
$newDate = $smt->smtDate ?? null;
if ($storedDate === '0000-00-00' && $newDate && $newDate !== '0000-00-00') {
```
**Issues**:
- Hard-coded `'0000-00-00'` (date sentinel) appears 5 times
- Should be class constant: `const INVALID_DATE = '0000-00-00'`

#### Issue 13: Inconsistent Error Display
**Lines**: 373, 344, 485
**Code**:
```php
display_error( __FILE__ . "::" . __LINE__ . print_r( $e, true ) );  // Line 373
display_error("Failed to link file to statement: " . $e->getMessage());  // Line 344
display_error("Upload failed: " . $result->getMessage());  // Not in importStatement()
```
**Issues**:
- Some errors include file/line (verbose), others don't (inconsistent UX)
- `print_r()` on exception pollutes output

#### Issue 14: Variables Reused Without Clearing
**Line**: 515
**Code**:
```php
$message .= ' ' . count($smt->transactions) . ' transactions';
```
**Issues**:
- `$message` already contains statement status ("new, imported" or "existing, updated")
- Appending transaction count directly (no separator/formatting)
- Example output: "new, imported 47 transactions" (looks like typo)

---

## 9. VARIABLE SCOPE ANALYSIS

### File-Level Variables (Persist Across Function Calls)

Declared at file level (lines 1-79):
```php
$page_security = 'SA_BANKACCOUNT'
$path_to_root = "../.."
$configRepo = new DatabaseConfigRepository()
$parserRegistry = new ParserRegistry($configRepo)
$parameterProvider = new PostParameterProvider()
$formSubmission = new FormSubmission($parameterProvider)
$parserSelector = new ParserSelector(...)
```

**Used in `importStatement()`?** NO - Not directly accessed.

---

### Session Variables Modified

| Variable | Scope | Created By | Used In | Risk |
|----------|-------|-----------|---------|------|
| `$_SESSION['bank_import_run_log_path']` | Session-level | File linking (line 343) | Logger creation (line 1213) | Persists across requests; if import fails, stale path could be used |
| `$_SESSION['statements']` | Session (set elsewhere) | `parse_uploaded_files()` line 1028 | `import_statements()` line 93 | Serialized arrays; if importStatement() called multiple times, state could differ |
| `$_SESSION['multistatements']` | Session (set elsewhere) | `parse_uploaded_files()` line 1029 | Account resolution | Large serialized structure; memory impact |

---

### Function-Level Variables

Declared inside `importStatement()`:

| Variable | Type | Initialized | Used | Scope Issues |
|----------|------|-------------|------|----------------|
| `$message` | string | Line 245 | Accumulated lines 312/515 + returned 517 | Carries state across operations (fragile string building) |
| `$logger` | object/null | Line 246 | Passed to `bank_import_log_event()` | Accessed throughout, safe |
| `$faAccountNumber` | string | Line 251 | Line 252 + 273 | Scoped correctly |
| `$faBankAccountId` | int/null | Line 252 | Line 254+ | Safe |
| `$faCurrency`, `$ofxCurrency` | string | Lines 255-256 | Local comparison | Safe |
| `$lastDate` | string/null | Line 285 | Loop line 290+ | Reset each function call, safe |
| `$bis` | object | Line 310 | Throughout statement phase | Scoped to statement, safe |
| `$exists` | bool | Line 312 | Line 314/329 | Determines logic branch, safe |
| `$smt_id` | int | Line 318 | Rest of function | Critical: used in file linking + transaction loop |
| `$uploadService` | object | Line 342 | Line 344 | Scoped, safe |
| `$newinserted`, `$dupecount`, `$dupeupdated` | int | Lines 358-360 | Lines 400/404/486 | **RISK**: Persist after function in case of reruns; should be local |
| `$bit` | object | Line 365+ (inside loop, but unset line 368) | Throughout transact loop | **RISK**: Unset but then recreated; scope carries across iterations |
| `$t` | object | Loop iterator (line 370) | Throughout loop | Safe (iterator) |
| `$dupe` | bool | Line 389 | Line 391/402 | Safe |
| `$sql`, `$res`, `$t_id` | mixed | Lines 407/408/409 | Limited scope | Safe |

---

### ⚠️ Variable Reuse Risk Summary

**High Risk:**
1. `$message` - fragile string accumulation, returned as summary
2. `$newinserted`, `$dupecount`, `$dupeupdated` - loop counters, not reset between function calls
3. `$bit` - unset/recreated in loop, but scope persists
4. `$smt_id` - single value tracks statement across entire function; if failed mid-loop, still used

**Mitigation**: All these are function-scoped and new function call creates clean state. Risk is primarily if function is called interactively without proper request boundary (unlikely in web context).

---

## 10. SUMMARY TABLE: ARCHITECTURAL VIOLATIONS

| Violation | Severity | Risk Level | Lines | Fix Priority |
|-----------|----------|-----------|-------|--------------|
| No transaction wrapping (atomicity broken) | CRITICAL | Data corruption | 326-408 | P1 |
| Inline SQL (moderate) | MODERATE | SQL injection | 333 | P2 |
| Contact Helper silent fail | MODERATE | Data corruption | 379-386 | P2 |
| Logger parameter via func_get_arg() | MODERATE | IDE/type checking issues | 245-246 | P3 |
| Unset $bit in loop | MODERATE | Logic confusion | 368 | P3 |
| Counter variables not reset | LOW | Potential state leak | 358-360 | P4 |
| Device logger not formatted | LOW | Maintenance debt | 373 | P4 |

---

## 11. CALL CHAIN & EXTERNAL DEPENDENCIES

```
importStatement($smt, $file_id, $logger)
│
├─ fa_get_bank_account_id_by_number($faAccountNumber)
│  └─ bi_bank_accounts_model::fa_get_bank_account_id_by_number()  [external model]
│
├─ get_bank_account($faBankAccountId)
│  └─ FrontAccounting API
│
├─ bank_import_log_event($logger, $eventName, $context)
│  └─ ImportRunLogger::event()  [custom logging service]
│
├─ new bi_statements_model()
│  ├─ statement_exists()
│  ├─ obj2obj($smt)
│  ├─ hand_insert_sql()
│  └─ update_statement()
│
├─ db_query($sql)  [FrontAccounting API]
│  └─ Executes SQL, returns resource/true/false
│
├─ db_insert_id()  [FrontAccounting API]
│  └─ Returns last insert ID
│
├─ FileUploadService::create()  [custom service]
│  └─ linkToStatements($file_id, $smt_ids)
│
├─ new bi_transactions_model()
│  ├─ trz2obj($t)
│  ├─ trans_exists()
│  └─ hand_insert_sql()
│
├─ ContactImportHelper::attachContactIdFromParserTransaction()  [custom helper]
│  └─ May modify $t object in-place
│
└─ display_notification/display_error()  [FrontAccounting UI]
```

---

## 12. DATA FLOW DIAGRAM

```
INPUT OBJECT: $smt (Statement)
├─ Properties: account, currency, statementId, transactions[], smtDate, 
│              acctid, bankid, intu_bid, bank, ...
│
PROCESSING:
├─ VALIDATE currency (fallback to FA account currency)
├─ VALIDATE/PATCH smtDate (use latest transaction date if missing)
│
├─ UPSERT bi_statements TABLE:
│  ├─ Check if exists (SELECT)
│  ├─ If NOT: INSERT new record
│  ├─ If YES: UPDATE existing (with optional date patch)
│  └─ Extract: $smt_id
│
├─ LINK FILE (if $file_id provided):
│  └─ INSERT into bi_uploaded_files_statements junction
│
├─ FOR EACH transaction $t in $smt->transactions:
│  ├─ TRANSFORM: ContactImportHelper enriches $t with contact_id
│  ├─ CHECK for duplicate (SELECT bi_transactions)
│  ├─ If NOT duplicate: INSERT new transaction record
│  └─ Track: $newinserted++, $dupecount++
│
OUTPUT:
└─ $message: String summary ("new, imported 47 transactions")
```

---

## APPENDIX: CRITICAL CODE SECTIONS

### Section A: Currency Fallback (Lines 251-280)
**Purpose**: Ensures statement has valid currency code
**Risk**: May silently override user intent if statement currency missing

### Section B: Statement Existence Check (Lines 312-337)
**Purpose**: INSERT or UPDATE statement record
**Risk**: No atomic wrapping with transaction inserts below

### Section C: Transaction Loop (Lines 370-410)
**Purpose**: Process all transactions in statement
**Risk**: Partial success possible (statement exists, some transactions don't)
**Complexity**: ~40 lines, nested try-catch, external service calls

### Section D: Contact Helper Integration (Lines 379-386)
**Purpose**: Attach contact records to transactions
**Risk**: Silent failure, modifies shared object, no validation

---

## SUMMARY OF FINDINGS

✅ **Strengths:**
- Logging coverage (all major steps logged)
- Non-blocking error handling (currency, file linking not critical)
- Clear structure (phases: currency, statement, file, transactions)

❌ **Weaknesses:**
- **No transaction atomicity** (critical data integrity risk)
- **Silent external service failure** (ContactImportHelper)
- **Incomplete error recovery** (partial inserts possible)
- **Weak type hints** (parameters via func_get_arg)
- **String-based state machine** (fragile)

🔴 **Immediate Action Required:**
1. Implement BEGIN/COMMIT/ROLLBACK wrapping
2. Fix inline SQL injection risk
3. Improve ContactImportHelper error handling
4. Add explicit type hints to function signature

