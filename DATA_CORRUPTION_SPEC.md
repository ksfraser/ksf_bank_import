# Data Corruption Prevention, Detection, and Remediation Specification

**Environment:** FrontAccounting 2.4.19  
**Priority:** CRITICAL — Reported orphaned GL entries and cross-contaminated line items  
**Status:** Specification Phase

---

## 1. Problem Summary

### Observed Issues

1. **Orphaned GL Entries**: Bank Payment/Deposit creates `gl_trans` entries but no matching `bank_trans` row.
2. **Cross-Contaminated Line Items**: Single GL transaction contains line items from multiple, unrelated transactions (including 3-year-old transactions).
3. **Query Inconsistency**: Bank View query uses 5 LEFT JOINs and produces different grouping than GL Journal view of the same transaction.
4. **Variable Pollution**: Suspected SESSION or variable reuse causing line item bleed across iterations.

### Root Cause Hypotheses

- **Unset Variables**: `process_statement()` reuses variables across statement/transaction iterations without cleanup.
- **SESSION Persistence**: Cached line items or derived data in `$_SESSION` not cleared between imports.
- **Incomplete Transactions**: Import fails mid-way through pair-wise GL and bank_trans inserts (no rollback).
- **Query Design Debt**: Bank View query grew organically; lacks normalizer principle or canonical FK pattern.

---

## 2. Prevention Strategy

### 2.1 SQL Transaction Wrapping

**Goal:** Ensure atomicity — either both GL and bank_trans entries succeed, or both rollback.

**Implementation Points:**

1. **Bank Payment/Deposit/Transfer Operations** (FA modules):
   ```
   BEGIN TRANSACTION
     - Insert/update gl_trans (with line details)
     - Insert/update bank_trans
     - Update related journal/GL accounts
   COMMIT or ROLLBACK on exception
   ```

2. **Import Statement Processing** (our `import_statements.php`):
   ```
   BEGIN TRANSACTION
     - Per-statement: validate and transform
     - Per-transaction: upsert statement + transaction + contact-dedup
     - Insert/pair with bank_trans if applicable
   COMMIT or ROLLBACK on any error
   ```

3. **Contact Deduplication** (`ContactImportHelper`, contact services):
   ```
   BEGIN TRANSACTION
     - Lookup or insert contact
     - Deduplicate candidates
     - Return contact_id
   COMMIT or ROLLBACK
   ```

**Key Rules:**
- Wrap at **logical operation** boundaries (not at every INSERT).
- Use nested transactions (SAVEPOINTs) for retry logic.
- Log transaction start/end and rollback events for audit trail.

---

### 2.2 Variable Hygiene Audit: `process_statement()`

**Scope:** `import_statements.php::process_statement()` or equivalent.

**Checklist:**

1. **Loop Initialization**: Each iteration over statements/transactions must declare fresh local variables:
   ```php
   foreach ($statements as $stmt) {
       $line_items = [];      // Fresh, not reused
       $gl_lines = [];        // Fresh
       $amount_total = 0;     // Reset, not += from prior
       // ... process $stmt
   }
   ```

2. **GLOBALS Audit**: Document and minimize use of `$_SESSION`, `$GLOBALS`, `$_POST`, etc.:
   - If accessed, log the access with context.
   - Use defensive checks: `isset()`, `!empty()` with explicit defaults.
   - Avoid `extract($_POST)` or similar auto-population.

3. **External Service Calls**: Verify contact services, validation, and dedup **do not store state** in static/global properties.

4. **Array & Object Handling**:
   - Avoid pass-by-reference unless intentional.
   - Clone objects if later mutations could corrupt originals.
   - Use immutable value objects for parsed data (DTOs).

**Deliverable:** A `VARIABLE_HYGIENE_AUDIT.md` report with findings and fixes applied.

---

### 2.3 Query Standardization

**Problem:** Bank View query differs from GL Journal; inconsistent grouping = inconsistent reporting.

**Solution:**

1. **Canonical Transaction View** (new query):
   ```sql
   SELECT
     trans.trans_id,
     trans.type,
     trans.trans_date,
     trans.reference,
     SUM(gl.amount) as gl_total,
     bt.amount as bank_trans_amount,
     bt.trans_date as bank_trans_date,
     CASE 
       WHEN bt.trans_id IS NULL THEN 'ORPHANED_GL'
       WHEN ABS(SUM(gl.amount) - bt.amount) > 0.01 THEN 'AMOUNT_MISMATCH'
       ELSE 'OK'
     END as integrity_status
   FROM gl_trans trans
   LEFT JOIN gl_trans gl ON gl.trans_id = trans.trans_id
   LEFT JOIN bank_trans bt ON bt.trans_id = trans.trans_id
   GROUP BY trans.trans_id, trans.type, ...
   ```

2. **Refactor Bank View**: Replace 5-JOIN ad-hoc query with:
   - Canonical view above
   - Or a join to `bank_trans` first, then LEFT JOIN to `gl_trans`
   - Document grouping logic (e.g., "one row per bank_trans, with GL summary").

3. **Align GL Journal View**: Same canonical logic for consistency.

---

## 3. Detection Strategy

### 3.1 Diagnostic Queries

#### Query 1: Orphaned GL Entries (GL with no bank_trans, or GL touching bank accounts)
```sql
SELECT
  gt.type_no,
  gt.type,
  gt.tran_date,
  gt.reference,
  COUNT(*) as gl_line_count,
  SUM(gt.amount) as total_amount,
  bt.id as has_bank_trans,
  CASE 
    WHEN bt.id IS NULL THEN 'NO_BANK_TRANS'
    ELSE 'HAS_BANK_TRANS'
  END as orphan_status
FROM {TB_PREF}gl_trans gt
LEFT JOIN {TB_PREF}bank_trans bt ON bt.trans_no = gt.type_no
LEFT JOIN {TB_PREF}chart_master cm ON cm.account_code = gt.account
WHERE (
  -- Bank Payment/Deposit types
  gt.type IN (BT_BANK_PAYMENT, BT_BANK_DEPOSIT, BT_BANK_TRANSFER)
  OR
  -- Or journal entries that touch GL accounts designated for bank use
  (gt.type = BT_JOURNAL AND cm.account_type LIKE 'BANK%')
)
AND bt.id IS NULL
GROUP BY gt.type_no, gt.type, gt.tran_date
ORDER BY gt.tran_date DESC;
```

#### Query 2: Suspicious Line-Item Overlap (GL lines with wildly different dates)
```sql
SELECT
  gt.counter,
  gt.type_no,
  gt.type,
  MIN(gt.tran_date) as earliest_date,
  MAX(gt.tran_date) as latest_date,
  DATEDIFF(MAX(gt.tran_date), MIN(gt.tran_date)) as day_span,
  COUNT(*) as line_count
FROM {TB_PREF}gl_trans gt
WHERE gt.type IN (BT_BANK_PAYMENT, BT_BANK_DEPOSIT, BT_BANK_TRANSFER)
GROUP BY gt.type_no, gt.type
HAVING DATEDIFF(MAX(gt.tran_date), MIN(gt.tran_date)) > 1
ORDER BY day_span DESC;
```

#### Query 3: Amount Mismatch (GL total ≠ bank_trans amount)
```sql
SELECT
  bt.id,
  bt.type,
  bt.amount as bank_amount,
  COALESCE(SUM(gt.amount), 0) as gl_total,
  ABS(bt.amount - COALESCE(SUM(gt.amount), 0)) as discrepancy,
  ROUND((ABS(bt.amount - COALESCE(SUM(gt.amount), 0)) / NULLIF(ABS(bt.amount), 0)) * 100, 2) as variance_pct
FROM {TB_PREF}bank_trans bt
LEFT JOIN {TB_PREF}gl_trans gt ON gt.type_no = bt.trans_no
WHERE bt.type IN (BT_BANK_PAYMENT, BT_BANK_DEPOSIT, BT_BANK_TRANSFER)
GROUP BY bt.id, bt.type, bt.amount
HAVING ABS(bt.amount - COALESCE(SUM(gt.amount), 0)) > 0.01
ORDER BY discrepancy DESC;
```

#### Query 4: Duplicate bank_trans for Same GL trans
```sql
SELECT
  gt.type_no,
  gt.type,
  COUNT(DISTINCT bt.id) as bank_trans_count,
  GROUP_CONCAT(DISTINCT bt.id) as duplicate_bank_ids,
  GROUP_CONCAT(DISTINCT bt.tran_date) as bank_tran_dates
FROM {TB_PREF}gl_trans gt
LEFT JOIN {TB_PREF}bank_trans bt ON bt.trans_no = gt.type_no
WHERE gt.type IN (BT_BANK_PAYMENT, BT_BANK_DEPOSIT, BT_BANK_TRANSFER)
GROUP BY gt.type_no, gt.type
HAVING COUNT(DISTINCT bt.id) > 1
ORDER BY bank_trans_count DESC;
```

---

### 3.2 Detection Reports (UI/Report Screens)

**Report 1: Orphaned Transactions**
- List: GL trans with no bank_trans match
- Columns: trans_id, type, date, reference, GL total, action buttons (inspect, correct)
- Filter: by date range, type, amount range

**Report 2: Cross-Contaminated Transactions**
- List: GL trans with line items from multiple dates (day_span > threshold)
- Columns: trans_id, date_range, line_count, dates involved, action buttons (split, inspect)
- Analysis: Show which days' items are mixed

**Report 3: Amount Mismatches**
- List: bank_trans vs GL total discrepancy
- Columns: trans_id, bank_amount, GL_total, difference, % variance, action buttons

**Report 4: Integrity Dashboard (Summary)**
- Count of orphaned GL entries
- Count of cross-contaminated trans
- Largest discrepancies
- Timeline anomalies (e.g., newest orphan, most costly mismatch)
- Drilldown links to detailed reports

---

### 3.3 UI Implementation Plan

**Technology:** FrontAccounting 2.4.19 (PHP/HTML)

**Module Structure:**

Corruption detection and remediation functionality has been organized into separate GitHub repositories for independent development and deployment:

**Report Modules (Separate Repositories):**
- [FA_REP_OrphanedTrans](https://github.com/org/FA_REP_OrphanedTrans) — Detects orphaned GL entries (GL without matching bank_trans)
- [FA_REP_ContaminatedTransactions](https://github.com/org/FA_REP_ContaminatedTransactions) — Detects cross-contaminated GL lines from multiple dates
- [FA_REP_AmountMismatches](https://github.com/org/FA_REP_AmountMismatches) — Identifies GL vs bank_trans amount discrepancies

**Legacy Integration (To Be Developed):**
```
banking_import/
  reports/
    integrity_dashboard.php      -- Summary dashboard + drilldowns to reports
  corrections/
    void_and_insert.php          -- Dual void+correction workflow
    inspect_transaction.php      -- Detailed transaction view
```

Each report module includes:
- Query classes for diagnostic SQL execution
- Screen classes for interactive HTML rendering
- PDF export classes (TCPDF)
- Corrections classes for audit-logged remediation
- Comprehensive ProjectDocs (USE_CASE.md, FUNCTIONAL_REQUIREMENTS.md, RTM.md, TEST_PLAN.md)

---

## 4. Remediation Strategy

### 4.1 Void Workflow (Standard FA)

- Execute FA's standard **void** operation (reverse GL entries, mark bank_trans as reversed).
- Log: trans_id, reason, who, when, before/after snapshots.

**Limitation:** Void removes data; does not fix it in place.

---

### 4.2 Correction Workflow (New)

**Goal:** Fix incomplete or wrong data without destroying audit trail.

**Steps:**

1. **Inspect Transaction**:
   - Show current GL entries, bank_trans state, contact info, contact_id.
   - Highlight discrepancies (missing bank_trans, amount mismatch, stale contacts).

2. **Correction Options**:
   a) **Insert Missing bank_trans**: If GL exists but bank_trans is missing:
      - Derive bank_trans fields from GL (amount, date, reference).
      - Insert with a correction flag/note (e.g., `correction_auto_ca_20260325`).
   
   b) **Uncontaminate Line Items**: If GL has mixed-date lines:
      - Show all lines grouped by original date.
      - Allow splitting into separate GL trans_ids or removing errant lines.
      - Insert corrective journal entries to balance.
   
   c) **Update Contact**: If contact_id is wrong or missing:
      - Show current contact, dedupe candidates.
      - Update transaction + bank_trans with new contact_id.
      - Log the change.

3. **Commit Correction**:
   - Wrap in SQL transaction (BEGIN/COMMIT).
   - Log all changes: old values, new values, reason, user, timestamp.
   - Generate audit report (JSON).

4. **Verification**:
   - Re-run diagnostic queries.
   - Confirm GL + bank_trans now match in amount, date, and structure.
   - Mark transaction as "corrected" for future reference.

### 4.3 Correction Log Table

**Schema:**
```sql
CREATE TABLE fa_bank_import_corrections (
  id INT PRIMARY KEY AUTO_INCREMENT,
  trans_id INT,
  correction_type ENUM('insert_missing_bank_trans', 'uncontaminate_gl', 'update_contact', 'void'),
  before_snapshot JSON,  -- Snapshot before correction
  after_snapshot JSON,   -- Snapshot after correction
  reason VARCHAR(500),
  created_by VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (trans_id),
  INDEX (created_at)
);
```

---

## 5. Implementation Roadmap

### Phase 1: Prevention & Audit (Week 1-2)
- [ ] Audit `process_statement()` for variable reuse; fix identified issues.
- [ ] Add SQL transaction wrappers to bank payment/deposit/transfer logic.
- [ ] Write `VARIABLE_HYGIENE_AUDIT.md` with findings.
- [ ] Establish logging for transaction start/end/rollback.

### Phase 2: Detection (Week 2-3)
- [ ] Create diagnostic SQL queries (4 queries above).
- [ ] Build FA console/script to execute queries and export results.
- [ ] Implement Report screens 1-4 (orphaned, contaminated, mismatches, dashboard).

### Phase 3: Remediation (Week 3-4)
- [ ] Create `fa_bank_import_corrections` audit log table.
- [ ] Implement "inspect transaction" view.
- [ ] Implement "insert missing bank_trans" correction.
- [ ] Implement "uncontaminate GL lines" correction tool.
- [ ] Implement "update contact" correction.
- [ ] Test corrections with synthetic corrupt data.

### Phase 4: Integration & Deployment (Week 4-5)
- [ ] Integrate correction workflows into main import UI.
- [ ] Run full test suite; verify no new data corruption.
- [ ] Deploy to staging; run comprehensive audit against historical data.
- [ ] Document known corruption patterns and remediation steps.
- [ ] Deploy to production with phased rollout.

---

## 6. Known Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Correction actions introduce new bugs | Unit + integration tests for each correction type; staging rehearsal |
| SQL transaction overhead impacts performance | Use nested transactions; only wrap critical sections; add performance benchmarks |
| Query inconsistency persists | Canonicalize query design; align Bank View with GL Journal view; doc review |
| Legacy code still has hidden variable pollution | Static analysis + code review; gradually refactor high-impact functions |
| Audit log grows unbounded | Implement log archiving; set retention policy (e.g., 1 year) |

---

## 7. Deliverables

1. **VARIABLE_HYGIENE_AUDIT.md** — Findings and fixes in `process_statement()`.
2. **SQL Transaction Layer** — Wrapper functions + integration in payment/deposit/transfer logic.
3. **Diagnostic Query Suite** — 4+ queries ready to run via console/script.
4. **Detection Reports** — 4 FA report screens (orphaned, contaminated, mismatches, dashboard).
5. **Correction Tooling** — Inspect + void/insert/update workflows with audit logging.
6. **Test Coverage** — Unit and integration tests for transaction wrapping and corrections.
7. **Deployment Plan** — Staged rollout with preflightchecks, rollback procedures.

---

## 8. Success Criteria

- [ ] Zero new orphaned GL entries created after prevention deployed.
- [ ] All existing corrupted transactions identified and catalogued (detection reports operational).
- [ ] >= 90% of orphaned GL entries successfully corrected or voided (remediation working).
- [ ] `process_statement()` variable audit completed; documented fixes applied.
- [ ] Query inconsistency resolved; Bank View and GL Journal now aligned.
- [ ] Correction audit log captures all changes with before/after snapshots.
- [ ] Full integration test suite passes; historical data audit shows no regressions.

---

## References

- FrontAccounting 2.4.19 database schema (gl_trans, bank_trans, gl_trans table docs)
- FA payment/deposit/transfer modules
- Our `import_statements.php` and contact services
- Existing DTO/validation infrastructure (BankingTransaction, BankingStatement)
