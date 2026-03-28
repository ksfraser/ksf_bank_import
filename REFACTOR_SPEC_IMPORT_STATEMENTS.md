# import_statements.php Refactor Specification
## Variable Hygiene Audit, Transaction Boundaries & SRP Class Architecture

**File:** `import_statements.php` (primary function: `importStatement()` at lines 243-553)  
**Environment:** FrontAccounting 2.4.19, PHP 7.3+  
**Priority:** CRITICAL (data corruption risk)  
**Date:** March 25, 2026

---

## Part 1: Variable Hygiene Audit & Issues

### 1.1 Unset Variables / Scope Bleed

#### Issue 1.1.1: `$bit` object created and unset in loop (Line 368)
**Current Code (lines 360-410):**
```php
foreach($statements[$id]->transactions as $tid => $transaction) {
    $bit = new transaction();  // Created fresh... but not always
    if(empty($transaction->account) || $transaction->account == '') {
        // ... error handling
        return false;
    }
    // ... populate $bit from $transaction
    $bit->contact_id = $contact_id;  // Set after helper
    // ... other assignments ...
    
    db_insert_statement_transaction(..., $bit, ...);
    
    unset($bit);  // *Unset here, but object could persist if exception thrown*
}
```

**Problem:** If `db_insert_statement_transaction()` throws exception BEFORE `unset()` executes, `$bit` persists in scope. Next iteration gets contaminated object. Additionally, exception never caught/rethrown, so caller doesn't know insert failed.

**Fix Pattern:**
```php
// FIXED: Use try/finally to ensure cleanup
foreach($statements[$id]->transactions as $tid => $transaction) {
    $bit = null;  // Initialize fresh
    try {
        $bit = new transaction();
        // ... populate $bit ...
        db_insert_statement_transaction(..., $bit, ...);
    } finally {
        $bit = null;  // Guaranteed cleanup even on exception
    }
}
```

---

#### Issue 1.1.2: Statement-level variables carry between iterations (Lines 245-360)
**Current Code (line 245+):**
```php
function importStatement($smt = null, $file_id = null) {
    // ... setup ...
    $amount = 0;
    $newinserted = 0;
    $dupecount = 0;
    $contact_id = null;
    // ... statements loop at line 285 ...
    foreach($statements as $id => $smtObject) {
        // All above variables reused across ALL statements
        // No reset per statement!
        // ...
    }
}
```

**Problem:** Counters `$newinserted`, `$dupecount`, `$amount` accumulate across ALL statements. If you import 3 statements in one batch, counters bleed.

**Fix Pattern:**
```php
function importStatement($smt = null, $file_id = null) {
    // ... setup ...
    
    foreach($statements as $id => $smtObject) {
        // FIXED: Fresh per statement
        $amountThisStatement = 0;
        $newinsertedThisStatement = 0;
        $dupecountThisStatement = 0;
        $contactIdThisStatement = null;
        
        foreach($smtObject->transactions as $tid => $transaction) {
            // These are fresh per loop too
            // ...
        }
        // Report $newinsertedThisStatement, etc. (isolated)
    }
}
```

---

#### Issue 1.1.3: Logger passed via `func_get_arg(2)` (Line 249)
**Current Code (line 249):**
```php
$logger = func_get_arg(2);  // Get 3rd argument (no type checking)
```

**Problem:** No type checking. If 3rd arg not passed, `func_get_arg()` throws. Implicit dependency = hard to refactor/test.

**Fix Pattern:**
```php
function importStatement($smt = null, $file_id = null, ?ImportRunLogger $logger = null) {
    // FIXED: Explicit parameter with type hint
    if ($logger === null) {
        $logger = new ImportRunLogger();  // Default
    }
    // ... use $logger ...
}
```

---

### 1.2 GLOBALS / SESSION Access

#### Issue 1.2.1: $_SESSION used without guarding (Lines 96, 285)
**Current Code (line 96, line 285):**
```php
// Line 96:
$statements = unserialize($_SESSION['statements']);

// Line 285 (inside importStatement):
foreach($statements as $id => $smt) {  // $statements from $_SESSION
```

**Problem:** 
1. `$_SESSION['statements']` could be corrupted or missing (no isset check).
2. Deserialized object attack risk (unserialize() without validation).
3. Hard to test (requires SESSION manipulation).

**Fix Pattern:**
```php
function importStatement($smt = null, $file_id = null, ?ImportRunLogger $logger = null) {
    // FIXED: Accept statements as parameter, not from $_SESSION
    // If called from web form, pass them explicitly from caller
}

// At call site (import_statements() function, line 96):
// FIXED: Explicit guard + validation
$statementsData = $_SESSION['statements'] ?? null;
if (empty($statementsData)) {
    display_error('No statements loaded in session.');
    return;
}

// Validate: optionally use a StatementValidator class
$statements = [];
try {
    $statements = $this->statementValidator->validateAndDeserialize($statementsData);
} catch (ValidationException $e) {
    display_error('Invalid statement data: ' . $e->getMessage());
    return;
}

foreach ($statements as $smt) {
    importStatement($smt, $file_id, $logger);  // Pass explicitly
}
```

---

#### Issue 1.2.2: `$_POST` accessed for file_id (Line 79)
**Current Code (line 79):**
```php
if (isset($_POST['import_file_id'])) {
    $file_id = $_POST['import_file_id'];
    // ... pass to import_statements() ...
}
```

**Problem:** Direct $_POST access. Should go through validated FormSubmission object.

**Fix Pattern:**
```php
// FIXED: Use FormSubmission already available (line 42)
global $formSubmission;
if ($formSubmission->hasParameter('import_file_id')) {
    $file_id = $formSubmission->getInteger('import_file_id');  // With type coercion
} else {
    display_error('Missing import_file_id');
    return;
}
```

---

### 1.3 Silent Failures & Error Handling

#### Issue 1.3.1: ContactImportHelper failures swallowed (Lines 379-386)
**Current Code:**
```php
try {
    ContactImportHelper::attachContactIdFromParserTransaction($db, $smt, $t);
} catch (\Exception $e) {
    // Silently logs (no user-visible error message)
    // $smt->contact_id may still be NULL
    // Import continues with bad data
}
```

**Problem:** Transaction proceeds even if contact attachment failed. Creates orphaned GL entries or wrong contact assignments.

**Fix Pattern:**
```php
// FIXED: Explicit behavior on failure
try {
    ContactImportHelper::attachContactIdFromParserTransaction($db, $smt, $t);
} catch (InvalidContactException $e) {
    $logger->warn("Could not deduplicate contact for transaction " . $t->fitid . ": " . $e->getMessage());
    // Option A: Fail hard (strict mode)
    throw new TransactionProcessingException("Contact attachment failed for transaction " . $t->fitid, 0, $e);
    
    // Option B: Continue with NULL (warn mode)
    // $smt->contact_id = null;
    // $logger->warn(...);
} catch (\Exception $e) {
    // Unexpected error, always fail hard
    throw new TransactionProcessingException("Unexpected error attaching contact", 0, $e);
}
```

---

## Part 2: SQL Transaction Boundaries

### 2.1 Current Transaction Behavior: NONE (auto-commit)

**Identified Query Points:**

| Line | Operation | Type | Risk |
|------|-----------|------|------|
| 333 | `UPDATE bi_statements SET smtDate=...` | UPDATE | Split transaction: if next fails, orphans updated statement |
| 360 | `db_insert_statement_transaction(...)` | INSERT | Per-transaction auto-commit; if loop has 5 txns and one fails, 4+ already committed |
| 386+ | `contactDedup / ContactImportHelper` | SELECT/INSERT | Service may auto-commit; can orphan contact records |
| 410+ | `bi_uploaded_files_statements INSERT` | INSERT | Marks as processed even if prior step failed |

**Current Execution Model:**
```
BEGIN import_statements
  ↓
BEGIN per-statement
  ├─ UPDATE bi_statements         ← COMMIT (atomic)
  ├─ BEGIN per-transaction
  │  ├─ CALL ContactImportHelper  ← COMMIT (atomic) *inside service*
  │  ├─ INSERT bi_transactions    ← COMMIT (atomic)
  │  └─ INSERT bank_trans?        ← COMMIT (atomic)
  ├─ INSERT bi_uploaded_files_statements  ← COMMIT (atomic)
END per-statement (may have PARTIAL succeeded)
END import_statements
  ↓
Return to caller (partial success OK!)
```

**Problem:** Each operation auto-commits. If operation N+1 fails, N already committed. On error, no rollback possible.

---

### 2.2 Proposed Transaction Model: Atomic per Statement

**New Execution Model:**
```
BEGIN import_statements
  ↓
FOREACH statement
  ├─ BEGIN TRANSACTION (SAVEPOINT s1)
  │   ├─ UPDATE bi_statements
  │   │   ON ERROR → ROLLBACK s1, CONTINUE with next statement (or fail hard)
  │   ├─ FOREACH transaction
  │   │   ├─ BEGIN TRANSACTION (SAVEPOINT t1)
  │   │   │   ├─ Call ContactImportHelper (wrapped in transaction)
  │   │   │   │   ON ERROR → ROLLBACK t1, LOG, DECIDE (fail hard or skip this txn)
  │   │   │   ├─ INSERT bi_transactions
  │   │   │   ├─ Bank-specific logic (match/create bank_trans, etc.)
  │   │   │   └─ COMMIT t1  ← Per-transaction commit (atomic)
  │   │   │   ON ERROR → ROLLBACK t1, log, continue or fail
  │   ├─ INSERT bi_uploaded_files_statements
  │   └─ COMMIT TRANSACTION (s1)  ← Per-statement commit
  │   ON ERROR → ROLLBACK s1 (full statement undone, try next)
END FOREACH
END import_statements
  ↓
Return summary: "Successfully imported N statements, Y transactions; Failed M statements"
```

**Key Design:**
- **Per-statement transaction isolation** (SAVEPOINT `statement_<id>`).
- **Per-transaction nested isolation** (SAVEPOINT `transaction_<id>`).
- **Error recovery:** Log failure, attempt next statement (resilient) or abort all (strict).
- **Audit trail:** Correction log captures what was attempted + result.

---

## Part 3: SQL Transaction Boundaries (Detailed)

### 3.1 Statement-Level Transaction Class: `StatementImportTransaction`

**Responsibility:** Wraps all DB operations for a single statement into an atomic unit.

**Interface:**
```php
class StatementImportTransaction {
    public function __construct(
        private ImportRunLogger $logger,
        private Database $db,
        private StatementValidator $validator
    ) {}
    
    /**
     * Execute atomic import for one statement.
     * 
     * @param Statement $stmt
     * @param int $fileId
     * @param array [
     *   'strict_contact' => bool,  // Fail if contact can't be resolved?
     *   'match_strategy' => string, // 'auto' | 'manual' | 'skip'
     *   ...
     * ] $options
     * @return StatementImportResult
     * @throws StatementImportException on non-recoverable error
     */
    public function execute(Statement $stmt, int $fileId, array $options = []): StatementImportResult;
}
```

**Pseudo-code (structure, not actual code):**
```php
public function execute(Statement $stmt, int $fileId, array $options = []): StatementImportResult {
    $this->db->beginTransaction("statement_{$stmt->statementId}");
    try {
        // Step 1: Normalize/validate statement
        $normalizedStmt = $this->validateAndNormalize($stmt);
        
        // Step 2: Update bi_statements metadata
        $this->updateStatementMetadata($normalizedStmt, $fileId);
        
        // Step 3: Process transactions inside statement
        $transactionResults = $this->processTransactionsInStatement(
            $normalizedStmt,
            $options
        );
        
        // Step 4: Mark as imported
        $this->markStatementAsProcessed($normalizedStmt->statementId, $fileId);
        
        // Commit statement transaction
        $this->db->commit("statement_{$stmt->statementId}");
        
        return new StatementImportResult(
            success: true,
            statementId: $stmt->statementId,
            transactionResults: $transactionResults,
            ...
        );
    } catch (RecoverableException $e) {
        // Rollback this statement, log, return failure (continue next)
        $this->db->rollback("statement_{$stmt->statementId}");
        $this->logger->warn("Statement import failed (recoverable): " . $e->getMessage());
        return new StatementImportResult(
            success: false,
            statementId: $stmt->statementId,
            error: $e->getMessage(),
            recoverable: true,
            ...
        );
    } catch (Exception $e) {
        // Non-recoverable error, rollback and rethrow
        $this->db->rollback("statement_{$stmt->statementId}");
        $this->logger->error("Statement import failed (non-recoverable): " . $e->getMessage());
        throw $e;
    }
}
```

**Methods to Extract:**
1. `validateAndNormalize(Statement)` → Extract into `StatementValidator` class
2. `updateStatementMetadata(Statement, int)` → Extract into `StatementMetadataUpdater` class
3. `processTransactionsInStatement(Statement, array)` → Extract into `StatementTransactionProcessor` class
4. `markStatementAsProcessed(int, int)` → Extract into query class

---

### 3.2 Transaction-Level Transaction Class: `TransactionImportTransaction`

**Responsibility:** Wraps DB operations for a single transaction (within a statement) into an atomic unit.

**Interface:**
```php
class TransactionImportTransaction {
    public function __construct(
        private ImportRunLogger $logger,
        private Database $db,
        private TransactionValidator $validator,
        private ContactImporter $contactImporter,
        private BankTransactionMatcher $bankMatcher
    ) {}
    
    /**
     * Execute atomic import for one transaction within a statement.
     * 
     * @param Transaction $txn
     * @param Statement $parentStatement
     * @param array $options
     * @return TransactionImportResult
     * @throws TransactionImportException
     */
    public function execute(
        Transaction $txn,
        Statement $parentStatement,
        array $options = []
    ): TransactionImportResult;
}
```

**Pseudo-code:**
```php
public function execute(
    Transaction $txn,
    Statement $parentStatement,
    array $options = []
): TransactionImportResult {
    $savepoint = "txn_{$txn->fitid}_{uniqid()}";
    $this->db->beginTransaction($savepoint);
    
    try {
        // Step 1: Validate transaction
        $normalizedTxn = $this->validator->validate($txn);
        
        // Step 2: Resolve contact (if parser provided one)
        $contactResult = $this->contactImporter->importContactIfProvided(
            $txn,
            $options['contact_strategy'] ?? 'auto'
        );
        if (!$contactResult->success && ($options['strict_contact'] ?? false)) {
            throw new ContactResolutionException($contactResult->error);
        }
        $txn->contact_id = $contactResult->contactId;
        
        // Step 3: Insert bi_transactions record
        $transactionId = $this->insertTransaction($normalizedTxn, $parentStatement->statementId);
        
        // Step 4: Match/create bank_trans if applicable
        $bankTransResult = $this->bankMatcher->matchOrCreateBankTrans(
            $txn,
            $transactionId,
            $options['match_strategy'] ?? 'auto'
        );
        
        // Commit transaction
        $this->db->commit($savepoint);
        
        return new TransactionImportResult(
            success: true,
            transactionId: $transactionId,
            fitid: $txn->fitid,
            contactId: $txn->contact_id,
            bankTransId: $bankTransResult->bankTransId ?? null,
            ...
        );
    } catch (ValidationException $e) {
        // Validation failure: recoverable, skip this transaction
        $this->db->rollback($savepoint);
        $this->logger->warn("Transaction validation failed: " . $e->getMessage());
        return new TransactionImportResult(
            success: false,
            fitid: $txn->fitid,
            error: $e->getMessage(),
            recoverable: true,
            ...
        );
    } catch (Exception $e) {
        // Other errors: rollback and rethrow
        $this->db->rollback($savepoint);
        throw new TransactionImportException($e->getMessage(), 0, $e);
    }
}
```

**Methods to Extract:**
1. `insertTransaction(Transaction, int)` → Extract into `BiTransactionInserter` class
2. `matchOrCreateBankTrans(...)` → Extract into `BankTransactionMatcher` class

---

## Part 4: SRP Class Architecture & Split Points

### 4.1 Class Hierarchy (Top-Level Flow)

**NEW CLASS: `BankImportOrchestrator`**  
*Responsibility:* Coordinate high-level import flow; no direct DB access

```
public function importBatch(
    array $statements,
    int $fileId,
    array $options = [],
    ImportRunLogger $logger = null
): BankImportBatchResult {
    
    $results = [];
    foreach ($statements as $stmt) {
        try {
            $result = $this->statementImporter->import($stmt, $fileId, $options);
            $results[] = $result;
        } catch (NonRecoverableException $e) {
            // Log, optionally abort batch
            $logger->error(...);
            throw $e;
        }
    }
    return new BankImportBatchResult($results);
}
```

---

### 4.2 Transaction Classes (SQL Boundary Wrappers)

```
StatementImportTransaction
    ├─ SQL: BEGIN TRANSACTION
    ├─ Delegates: StatementValidator, StatementMetadataUpdater, StatementTransactionProcessor
    └─ SQL: COMMIT / ROLLBACK

TransactionImportTransaction
    ├─ SQL: BEGIN TRANSACTION (SAVEPOINT)
    ├─ Delegates: TransactionValidator, ContactImporter, BankTransactionMatcher
    └─ SQL: COMMIT / ROLLBACK (SAVEPOINT)
```

---

### 4.3 Query & Mutation Classes (Single Queries)

**Shared Query Classes (Reusable across transactions):**

```php
// Query Classes (Read-only):
class FetchStatementsByFileId {
    public function fetch(int $fileId): array { /* SELECT */ }
}

class FetchTransactionsByStatementId {
    public function fetch(int $stmtId): array { /* SELECT */ }
}

class CheckDuplicateTransactionByFitid {
    public function exists(string $fitid, int $accountId): bool { /* SELECT */ }
}

// Mutation Classes:
class InsertBiStatement {
    public function insert(Statement $stmt, int $fileId): int { /* INSERT, return ID */ }
}

class UpdateBiStatementMetadata {
    public function update(int $stmtId, array $updates): void { /* UPDATE */ }
}

class InsertBiTransaction {
    public function insert(Transaction $txn, int $stmtId): int { /* INSERT, return ID */ }
}

class InsertBiBankTransaction {
    public function insert(BankTransaction $bt): int { /* INSERT, return ID */ }
}

class MarkFileAsProcessed {
    public function mark(int $fileId, int $stmtId): void { /* INSERT into bi_uploaded_files_statements */ }
}
```

**Injection Points:** Each transaction class receives these as dependencies:
```php
class StatementImportTransaction {
    public function __construct(
        private InsertBiStatement $insertStmt,
        private UpdateBiStatementMetadata $updateStmtMetadata,
        private MarkFileAsProcessed $markProcessed,
        private FetchTransactionsByStatementId $fetchTxns,
        // ... and transaction processor
        private StatementTransactionProcessor $txnProcessor,
        ...
    ) {}
}
```

---

### 4.4 Logic & Control Flow Classes (if/switch Extraction)

**Issue: Nested control flow in transaction loop (lines 360+)**  
Currently: Nested if/foreach with mixed concerns (business logic + DB ops + error handling)

**Extract: Strategy Classes**

```php
// Strategy: How to handle contact resolution
interface ContactResolutionStrategy {
    public function resolve(Transaction $txn, ParserContact $parserContact): ?int;
}

class AutoContactResolution implements ContactResolutionStrategy {
    public function resolve(Transaction $txn, ParserContact $parserContact): ?int {
        // Attempt auto-dedup; return contact_id or null
    }
}

class ManualContactResolution implements ContactResolutionStrategy {
    public function resolve(Transaction $txn, ParserContact $parserContact): ?int {
        // Require manual approval; return null (skip transaction)
    }
}

class SkipContactResolution implements ContactResolutionStrategy {
    public function resolve(Transaction $txn, ParserContact $parserContact): ?int {
        // Skip contact attachment; return null
    }
}

// Usage in TransactionImportTransaction:
private ContactResolutionStrategy $contactStrategy;

public function __construct(array $options = []) {
    $strategy = $options['contact_strategy'] ?? 'auto';
    $this->contactStrategy = match($strategy) {
        'auto' => new AutoContactResolution(...),
        'manual' => new ManualContactResolution(...),
        'skip' => new SkipContactResolution(...),
        default => throw new InvalidArgumentException("Unknown strategy: $strategy"),
    };
}

// Execute:
public function execute(...): TransactionImportResult {
    $contactId = $this->contactStrategy->resolve($txn, $txn->contact);
    $txn->contact_id = $contactId;
    // ... continue ...
}
```

**Extract: Bank Transaction Matcher (if/switch for matching)**  
Currently: Lines 410+ have logic deciding whether to create bank_trans, match existing, etc.

```php
interface BankTransactionMatcher {
    public function match(Transaction $parsedTxn, int $transactionRecordId): BankTransactionMatchResult;
}

class BankTransactionAutoMatcher implements BankTransactionMatcher {
    public function match(Transaction $parsedTxn, int $transactionRecordId): BankTransactionMatchResult {
        // Auto-match logic: check fitid, amount, date against existing bank_trans
        // Return match or CREATE decision
    }
}

class BankTransactionSkipMatcher implements BankTransactionMatcher {
    public function match(Transaction $parsedTxn, int $transactionRecordId): BankTransactionMatchResult {
        // Skip: don't create/match bank_trans
        return new BankTransactionMatchResult(matched: false, newId: null);
    }
}

// Usage in TransactionImportTransaction:
private BankTransactionMatcher $bankMatcher;

public function execute(...): TransactionImportResult {
    $matchResult = $this->bankMatcher->match($txn, $transactionId);
    $bankTransId = $matchResult->matched ? $matchResult->bankTransId : null;
    // ... continue ...
}
```

---

### 4.5 Validator Classes (Pre-condition Checks)

**Extract validation logic from main flow:**

```php
class StatementValidator {
    public function validate(Statement $stmt): Statement {
        if (empty($stmt->account)) {
            throw new ValidationException("Statement account is empty");
        }
        if (empty($stmt->statementId)) {
            throw new ValidationException("Statement ID is empty");
        }
        // ... more checks ...
        return $stmt;  // Return validated (possibly normalized)
    }
}

class TransactionValidator {
    public function validate(Transaction $txn): Transaction {
        if (empty($txn->fitid)) {
            throw new ValidationException("Transaction FITID is empty");
        }
        if (empty($txn->transactionAmount) || !is_numeric($txn->transactionAmount)) {
            throw new ValidationException("Transaction amount is invalid");
        }
        // ... more checks ...
        return $txn;
    }
}
```

---

### 4.6 Contact Import Classes (Extracted from Inline Logic)

**Currently:** Lines 379-386 in-place contact helper call  
**Extract:** `ContactImporter` class that wraps helper + error handling

```php
class ContactImporter {
    public function __construct(
        private Database $db,
        private ImportRunLogger $logger,
        private ContactImportHelper $helper
    ) {}
    
    /**
     * Import contact from transaction if provided; handle errors gracefully.
     * 
     * @param Transaction $txn
     * @param string $strategy ('strict' = throw on failure, 'lenient' = log only)
     * @return ContactImportResult { contactId: ?int, success: bool, error?: string }
     */
    public function importContactIfProvided(Transaction $txn, string $strategy = 'lenient'): ContactImportResult {
        if (!isset($txn->contact) || $txn->contact === null) {
            return new ContactImportResult(success: true, contactId: null);
        }
        
        try {
            $this->helper->attachContactIdFromParserTransaction($this->db, /* statement */, $txn);
            return new ContactImportResult(
                success: true,
                contactId: $txn->contact_id ?? null
            );
        } catch (ContactResolutionException $e) {
            if ($strategy === 'strict') {
                throw $e;
            }
            $this->logger->warn("Contact import failed: " . $e->getMessage());
            return new ContactImportResult(
                success: false,
                contactId: null,
                error: $e->getMessage()
            );
        }
    }
}
```

---

## Part 5: Refactor Plan (Step-by-Step)

### Phase 1: Foundation (Week 1)
- [ ] Create Result/Exception classes (StatementImportResult, TransactionImportResult, etc.)
- [ ] Create Validator classes (StatementValidator, TransactionValidator)
- [ ] Create Query/Mutation classes (InsertBiStatement, UpdateBiStatementMetadata, etc.)
- [ ] Unit tests for validators + query classes
- [ ] **No changes to import_statements.php yet**

### Phase 2: Transaction Classes (Week 2)
- [ ] Create `TransactionImportTransaction` class with nested transaction logic
- [ ] Create `StatementImportTransaction` class with statement-level transaction
- [ ] Extract `ContactImporter` class
- [ ] Extract strategy classes (ContactResolutionStrategy, BankTransactionMatcher)
- [ ] Unit + integration tests (mock DB, test rollback behavior)
- [ ] **Still no changes to import_statements.php**

### Phase 3: Coordinator (Week 3)
- [ ] Create `BankImportOrchestrator` class
- [ ] Refactor `importStatement()` to delegate to orchestrator
- [ ] Update `import_statements()` to use FormSubmission (guards on $_POST/$_SESSION)
- [ ] Migrate logger to explicit parameter, not func_get_arg()
- [ ] Integration test: full import flow with new classes

### Phase 4: Migration (Week 4)
- [ ] Test against staging data
- [ ] Audit prod for existing corruption (run diagnostic queries)
- [ ] Deploy with feature flag (new code path is optional)
- [ ] Gradual rollout

---

## Part 6: Before/After Code Examples

### 6.1 BEFORE: Current Variable Bleed + Silent Failure

```php
// import_statements.php, lines 243-410
function importStatement($smt = null, $file_id = null) {
    $logger = func_get_arg(2);  // // ← Issue 1.2.1: Implicit dependency
    $statements = unserialize($_SESSION['statements']);  // ← Issue 1.2.1: Unguarded $_SESSION
    
    $amount = 0;       // ← Issue 1.1.2: Reused across statements
    $newinserted = 0;  // ← Issue 1.1.2
    $dupecount = 0;    // ← Issue 1.1.2
    
    foreach($statements as $id => $smtObject) {
        // Variables NOT reset per statement iteration!
        // ...each statement loop reuses $amount, $newinserted, $dupecount...
        
        foreach($smtObject->transactions as $tid => $transaction) {
            $bit = new transaction();  // ← Issue 1.1.1: Created fresh
            
            // ... populate $bit ...
            
            try {
                ContactImportHelper::attachContactIdFromParserTransaction($db, $smt, $t);
                // ← Issue 1.3.1: Silent failure (exception swallowed, no user message)
            } catch (\Exception $e) {
                // Silently logged, import continues with NULL contact_id
            }
            
            db_insert_statement_transaction(..., $bit, ...);  // Auto-commit, no transaction wrapper
            unset($bit);  // ← After insert which may throw; unset never executes
        }
        
        // Result: $amount, $newinserted, $dupecount accumulated across ALL statements
    }
}
```

**Problems Summary:**
1. ✗ No SQL transactions (auto-commit all queries)
2. ✗ Variables reused across iterations (counters bleed)
3. ✗ Unset logic fails on exceptions (scope pollution)
4. ✗ Contact failures silent (orphaned/wrong data)
5. ✗ $_SESSION unguarded (crash risk)
6. ✗ Logger implicit (func_get_arg; hard to test)

---

### 6.2 AFTER: Clean Transactions + Explicit Dependencies + SRP

```php
// BankImportOrchestrator.php (NEW)
class BankImportOrchestrator {
    public function importBatch(
        array $statements,
        int $fileId,
        array $options = [],
        ImportRunLogger $logger = null
    ): BankImportBatchResult {
        $logger ??= new ImportRunLogger();
        $results = [];
        
        foreach ($statements as $stmt) {
            try {
                // Each statement gets its own transaction + isolated variables
                $result = $this->statementImporter->import($stmt, $fileId, $options, $logger);
                $results[] = $result;
            } catch (NonRecoverableException $e) {
                $logger->error("Batch aborted: " . $e->getMessage());
                throw $e;
            }
        }
        
        return new BankImportBatchResult($results);
    }
}

// StatementImportTransaction.php (NEW)
class StatementImportTransaction {
    public function __construct(
        private Database $db,
        private ImportRunLogger $logger,
        private InsertBiStatement $insertBiStmt,
        private UpdateBiStatementMetadata $updateStmtMetadata,
        private StatementTransactionProcessor $txnProcessor,
        private MarkFileAsProcessed $markProcessed
    ) {}
    
    /**
     * Execute atomic import for one statement.
     * Variables are LOCAL to this method, not shared across calls.
     * SQL operations wrapped in transaction.
     */
    public function execute(
        Statement $stmt,
        int $fileId,
        array $options = [],
        ImportRunLogger $logger = null
    ): StatementImportResult {
        $logger ??= $this->logger;
        $savepoint = "stmt_{$stmt->statementId}_" . uniqid();
        
        $this->db->beginTransaction($savepoint);  // ← SQL Transaction START
        
        try {
            // FIXED: All variables are LOCAL to this execution
            $amountThisStatement = 0;
            $newinsertedThisStatement = 0;
            $dupecountThisStatement = 0;
            
            // Validate statement (strict, fails early)
            $normalizedStmt = (new StatementValidator())->validate($stmt);
            
            // Update Statement metadata
            $this->updateStmtMetadata->update($normalizedStmt->statementId, [
                'smtDate' => $normalizedStmt->smtDate,
            ]);
            
            // Process transactions (each in nested transaction)
            $txnResults = $this->txnProcessor->process(
                $normalizedStmt,
                $options,
                $logger
            );
            
            // Update counters (isolated to this statement)
            foreach ($txnResults as $txnResult) {
                if ($txnResult->success) $newinsertedThisStatement++;
                else $dupecountThisStatement++;
            }
            
            // Mark file as processed
            $this->markProcessed->mark($fileId, $normalizedStmt->statementId);
            
            // FIXED: Explicit commit
            $this->db->commit($savepoint);  // ← SQL Transaction COMMIT
            
            return new StatementImportResult(
                success: true,
                statementId: $stmt->statementId,
                transactionsProcessed: count($txnResults),
                newInserted: $newinsertedThisStatement,
                dupes: $dupecountThisStatement,
                amount: $amountThisStatement
            );
            
        } catch (ValidationException $e) {
            // Validation: recoverable, skip this statement
            $this->db->rollback($savepoint);  // ← SQL Transaction ROLLBACK
            $logger->warn("Statement {$stmt->statementId} validation failed: " . $e->getMessage());
            return new StatementImportResult(
                success: false,
                statementId: $stmt->statementId,
                error: $e->getMessage(),
                recoverable: true
            );
        } catch (Exception $e) {
            // Other errors: non-recoverable
            $this->db->rollback($savepoint);  // ← SQL Transaction ROLLBACK
            $logger->error("Statement {$stmt->statementId} import failed: " . $e->getMessage());
            throw new StatementImportException($e->getMessage(), 0, $e);
        }
    }
}

// StatementTransactionProcessor.php (NEW)
class StatementTransactionProcessor {
    public function __construct(
        private TransactionImportTransaction $txnImporter,
        private ImportRunLogger $logger
    ) {}
    
    /**
     * Process all transactions in a statement.
     * Each transaction is atomic (nested transaction).
     */
    public function process(
        Statement $stmt,
        array $options = [],
        ImportRunLogger $logger = null
    ): array {
        $logger ??= $this->logger;
        $results = [];
        
        foreach ($stmt->transactions as $txn) {
            try {
                // Each transaction is independently wrapped
                $result = $this->txnImporter->execute($txn, $stmt, $options, $logger);
                $results[] = $result;
            } catch (ValidationException $e) {
                // Validation error: skip this transaction, continue
                $logger->warn("Transaction {$txn->fitid} skipped: " . $e->getMessage());
                $results[] = new TransactionImportResult(
                    success: false,
                    fitid: $txn->fitid,
                    error: $e->getMessage(),
                    recoverable: true
                );
            } catch (Exception $e) {
                // Non-recoverable: rethrow (will rollback statement)
                throw $e;
            }
        }
        
        return $results;
    }
}

// TransactionImportTransaction.php (NEW)
class TransactionImportTransaction {
    public function __construct(
        private Database $db,
        private InsertBiTransaction $insertBiTxn,
        private ContactImporter $contactImporter,
        private BankTransactionMatcher $bankMatcher,
        private ImportRunLogger $logger
    ) {}
    
    public function execute(
        Transaction $txn,
        Statement $parentStmt,
        array $options = [],
        ImportRunLogger $logger = null
    ): TransactionImportResult {
        $logger ??= $this->logger;
        $savepoint = "txn_{$txn->fitid}_" . uniqid();
        
        $this->db->beginTransaction($savepoint);  // ← Nested SQL Transaction START
        
        try {
            // FIXED: Validate transaction (strict)
            $normalizedTxn = (new TransactionValidator())->validate($txn);
            
            // FIXED: Import contact with explicit strategy + error handling
            $contactResult = $this->contactImporter->importContactIfProvided(
                $normalizedTxn,
                $options['contact_strategy'] ?? 'auto'
            );
            if (!$contactResult->success && ($options['strict_contact'] ?? false)) {
                throw new ContactResolutionException($contactResult->error);
            }
            $normalizedTxn->contact_id = $contactResult->contactId;
            
            // Insert transaction record
            $transactionId = $this->insertBiTxn->insert($normalizedTxn, $parentStmt->statementId);
            
            // Match/create bank_trans
            $bankResult = $this->bankMatcher->match($normalizedTxn, $transactionId);
            
            // FIXED: Explicit commit
            $this->db->commit($savepoint);  // ← Nested SQL Transaction COMMIT
            
            return new TransactionImportResult(
                success: true,
                transactionId: $transactionId,
                fitid: $txn->fitid,
                contactId: $normalizedTxn->contact_id,
                bankTransId: $bankResult->bankTransId ?? null
            );
            
        } catch (ValidationException $e) {
            // Validation failure: recoverable
            $this->db->rollback($savepoint);  // ← Nested SQL Transaction ROLLBACK
            $logger->warn("Transaction {$txn->fitid} validation failed: " . $e->getMessage());
            return new TransactionImportResult(
                success: false,
                fitid: $txn->fitid,
                error: $e->getMessage(),
                recoverable: true
            );
        } catch (Exception $e) {
            // Non-recoverable error
            $this->db->rollback($savepoint);  // ← Nested SQL Transaction ROLLBACK
            $logger->error("Transaction {$txn->fitid} import failed: " . $e->getMessage());
            throw new TransactionImportException($e->getMessage(), 0, $e);
        }
    }
}

// import_statements.php (REFACTORED - old code path preserved, new code path added)
function import_statements() {
    // FIXED: Explicit guards on $_SESSION
    global $db, $formSubmission, $logger;
    
    $statementsData = $_SESSION['statements'] ?? null;
    if (empty($statementsData)) {
        display_error(_('No statements loaded in session.'));
        return;
    }
    
    try {
        $statements = unserialize($statementsData);  // TODO: Use safer deserializer
    } catch (Exception $e) {
        display_error(_('Invalid statement data.'));
        return;
    }
    
    // FIXED: Explicit file_id parameter
    $fileId = $formSubmission->getInteger('import_file_id', null);
    if ($fileId === null) {
        display_error(_('Missing import_file_id.'));
        return;
    }
    
    try {
        // Use new orchestrator (explicit dependencies)
        $orchestrator = new BankImportOrchestrator(
            new StatementImportTransaction(...),
            new StatementTransactionProcessor(...),
            new TransactionImportTransaction(...),
            $logger
        );
        
        $result = $orchestrator->importBatch(
            $statements,
            $fileId,
            ['strict_contact' => false, 'contact_strategy' => 'auto'],
            $logger
        );
        
        // Display results
        display_notification(
            _("Import complete: {$result->successCount} statements, {$result->totalTransactions} transactions")
        );
        
    } catch (NonRecoverableException $e) {
        display_error(_("Import failed: ") . $e->getMessage());
        return;
    }
}
```

---

## Part 7: Deliverables Checklist

### Classes to Create (27 classes total)

**Result Classes:**
- [ ] `StatementImportResult`
- [ ] `TransactionImportResult`
- [ ] `BankImportBatchResult`
- [ ] `ContactImportResult`
- [ ] `BankTransactionMatchResult`

**Exception Classes:**
- [ ] `StatementImportException`
- [ ] `TransactionImportException`
- [ ] `ContactResolutionException`
- [ ] `ValidationException`
- [ ] `NonRecoverableException`
- [ ] `RecoverableException`

**Transaction Classes (SQL Boundaries):**
- [ ] `StatementImportTransaction`
- [ ] `TransactionImportTransaction`

**Processor Classes:**
- [ ] `BankImportOrchestrator`
- [ ] `StatementTransactionProcessor`

**Validator Classes:**
- [ ] `StatementValidator`
- [ ] `TransactionValidator`

**Query/Mutation Classes:**
- [ ] `InsertBiStatement`
- [ ] `UpdateBiStatementMetadata`
- [ ] `InsertBiTransaction`
- [ ] `InsertBiBankTransaction`
- [ ] `MarkFileAsProcessed`
- [ ] `FetchTransactionsByStatementId`
- [ ] `CheckDuplicateTransactionByFitid`

**Strategy Classes:**
- [ ] `ContactResolutionStrategy` (interface)
- [ ] `AutoContactResolution`
- [ ] `ManualContactResolution`
- [ ] `SkipContactResolution`
- [ ] `BankTransactionMatcher` (interface)
- [ ] `BankTransactionAutoMatcher`
- [ ] `BankTransactionSkipMatcher`

**Utility Classes:**
- [ ] `ContactImporter`

### Tests to Write
- [ ] Unit: StatementValidator, TransactionValidator
- [ ] Unit: Query/Mutation classes
- [ ] Unit: Strategy classes
- [ ] Integration: StatementImportTransaction with mock DB (test rollback)
- [ ] Integration: TransactionImportTransaction with mock DB (test nested rollback)
- [ ] Integration: Full orchestrator flow (happy path + error paths)

---

## Part 8: Known Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Massive refactor breaks existing flow | Keep old code path; implement feature flag; gradual rollout |
| New classes introduce performance overhead | Use dependency injection (no reflection); cache DB connections |
| Backward compatibility with existing code | DTOs + result classes match old behavior; test thoroughly |
| Transaction nesting support varies by DB driver | Use SAVEPOINT syntax (MySQL 5.0+, covered by PDO) |

---

## Final Notes

This specification is **NOT production-code**, but a detailed architectural plan. Each phase has clear deliverables and testability checkpoints. The refactor is **additive** (no breaking changes to existing API), allowing phased rollout.

**Key Principles Enforced:**
1. ✓ SQL Atomicity: Explicit BEGIN/COMMIT/ROLLBACK with savepoints
2. ✓ Variable Hygiene: Local scope, no reuse across iterations
3. ✓ Dependency Injection: Explicit constructor parameters, no func_get_arg
4. ✓ Error Handling: Recoverable vs non-recoverable, explicit logging
5. ✓ SRP: Each class has single responsibility (transaction, validation, query, strategy)
6. ✓ Testability: All classes unit-testable with mock dependencies
