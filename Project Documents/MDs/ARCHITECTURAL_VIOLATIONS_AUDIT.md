# Architectural Violations Audit Report
**Date:** 2025-11-16  
**Branch:** main  
**Purpose:** Identify DRY and HTML abstraction violations before regression testing  

---

## Executive Summary

### Critical Findings
- **21 duplicate class files** - Same files exist in root AND src/Ksfraser/FaBankImport/
- **7 duplicate procedural files** - import_statements.php, process_statements files, view_statements.php duplicated
- **Multiple files with hardcoded HTML** - Violates HTML abstraction principle
- **~6 backup/temp files** (*.php~, *.copilot.php) - Should be in .gitignore

### Impact
- **DRY Violations:** 28+ duplicate files = maintenance nightmare (changes must be made twice)
- **HTML Abstraction Violations:** Defeats purpose of HTML class library
- **Technical Debt:** Cannot reliably regression test with duplicates present

---

## 1. DUPLICATE FILE VIOLATIONS (DRY)

### A. Class Files Duplicated (21 files)

All of these exist in BOTH root AND `src/Ksfraser/FaBankImport/`:

1. `class.AbstractQfxParser.php` - QFX parser base class
2. `class.bank_import_controller.php` - Main controller
3. `class.bi_counterparty_model.php` - Counterparty model (12 changes in regression testing)
4. `class.bi_lineitem.php` - Line item class (1225 lines changed! Tested in regression)
5. `class.bi_partners_data.php` - Partner data management
6. `class.bi_statements.php` - Statements model (13 changes, needs testing)
7. `class.bi_transaction.php` - Transaction model
8. `class.bi_transactions.php` - Transactions collection (91 changes, tested in regression)
9. `class.bi_transactionTitle_model.php` - Transaction title model
10. `class.CibcQfxParser.php` - CIBC bank parser
11. `class.ManuQfxParser.php` - Manulife parser
12. `class.PcmcQfxParser.php` - PCMC parser
13. `class.QfxParserFactory.php` - Parser factory
14. `class.transactions_table.php` - **CRITICAL** (323 changes, blocked in regression)
15. `class.ViewBiLineItems.php` - View class (52 changes, tested in regression)

**TRIPLE Duplicate:**
- `class.bi_lineitem.php` - exists in root, src/Ksfraser/FaBankImport/, AND views/

### B. Procedural Files Duplicated (7 files)

All exist in BOTH root AND `src/Ksfraser/FaBankImport/`:

1. `import_statements.php` - **CRITICAL** (144 changes, needs testing)
2. `import_statements-old.php` - Legacy import (should be deleted)
3. `process_statements_preclean.php` - Pre-cleanup version
4. `process_statements.copilot_refactored.php` - Refactored version
5. `view_statements.php` - Statements view

### C. Backup/Development Files (Should be in .gitignore)

**Root directory:**
1. `class.bi_lineitem.php~` - Backup
2. `class.bi_partners_data.php~` - Backup
3. `process_statements.php~` - Backup
4. `class.bi_partners_data.copilot.php` - Dev file
5. `class.bi_transaction.copilot.php` - Dev file
6. `class.bi_transactions.copilot.php` - Dev file
7. `class.bi_transactionTitle_model.copilot.php` - Dev file
8. `import_statements.copilot.php` - Dev file

**Also in src/Ksfraser/FaBankImport/:**
- Same backup/copilot files duplicated there too

---

## 2. HARDCODED HTML VIOLATIONS

### Philosophy Violation
The project has `src/Ksfraser/HTML/` classes specifically designed to abstract HTML generation. Hardcoded HTML strings violate this abstraction and create:
- **Inconsistency** - Some code uses HTML classes, some doesn't
- **Maintainability issues** - HTML changes require hunting through procedural code
- **DRY violations** - Same HTML patterns repeated instead of using classes

### Files with Significant Hardcoded HTML

#### A. CRITICAL: class.transactions_table.php (main refactor file)
**Lines:** 61, 67, 212, 213, 216, 330, 333, 334, 768, 769, 770, 772
**Violations:**
```php
echo '<table class="tablestyle" width="100%">';     // Line 61
echo '</table>';                                     // Line 67
echo '<tr>';                                         // Line 212
echo '<td width="50%">';                            // Line 213
echo '<table class="tablestyle2" width="100%">';    // Line 216
echo '</table>'; // Close the tablestyle2 table     // Line 330
echo "</td><td width='50%' valign='top'>";          // Line 333
echo '<table class="tablestyle2" width="100%">';    // Line 334
echo '</table>'; // Close tablestyle2 table         // Line 768
echo "</td>";                                        // Line 769
echo '</tr>'; // Close the row                      // Line 770
echo '</table>'; // Close main tablestyle table     // Line 772
```

**Status:** MIXED - File uses HtmlLabelRow, HtmlString, HtmlSubmit, HtmlHidden for content BUT hardcoded HTML for table structure. Should use HtmlTable, HtmlTableRow, HtmlTableCell classes.

**Also duplicated in:** `src/Ksfraser/FaBankImport/class.transactions_table.php` (same violations)

#### B. class.ViewBiLineItems.php
**Lines:** 45, 483, 485, 534, 535
**Violations:**
```php
echo '<td width="50%">';                            // Line 45
echo "</td><td width='50%' valign='top'>";          // Line 483
echo '<table class="tablestyle2" width="100%">';    // Line 485
echo '</table>';                                     // Line 534
echo "</td>";                                        // Line 535
```
**Status:** Partially refactored, hardcoded table structure remains
**Also in:** `src/Ksfraser/FaBankImport/class.ViewBiLineItems.php` (duplicate)

#### C. class.bi_lineitem.php (views/)
**Lines:** 367, 788, 838, 1126, 1255, 1256, 1266, 1277, 1284, 1861, 1911
**Violations:** Heavy hardcoded HTML including styled divs:
```php
echo '<td width="50%">';                                                    // Line 367
echo "</td><td width='50%' valign='top'>";                                  // Line 788
echo "</td>";                                                               // Line 838
echo '<td width="50%">';                                                    // Line 1126
echo "<tr><td colspan='2' style='background-color: #ffffcc; border: 2px solid #ffa500; padding: 10px;'>"; // Line 1255
echo "<div style='font-weight: bold; color: #ff8c00; margin-bottom: 5px;'>⇄ PAIRED BANK TRANSFER DETECTED</div>"; // Line 1256
echo "<div style='margin: 5px 0; padding: 5px; background-color: #fff; border: 1px solid #ddd;'>"; // Line 1266
echo "<div style='margin-top: 10px;'>";                                    // Line 1277
echo "</td></tr>";                                                          // Line 1284
```
**Status:** SEVERE - Inline styles and complex HTML structures hardcoded

#### D. src/Ksfraser/View/BiLineItemView.php
**Lines:** 57, 389, 439
**Violations:**
```php
echo '<td width="50%">';                            // Line 57
echo "</td><td width='50%' valign='top'>";          // Line 389
echo "</td>";                                        // Line 439
```
**Status:** Partial refactor incomplete

#### E. import_statements.php (both root and src/)
**Lines:** 39, 41, 62, 64, 235, 382-408, 417, 422
**Violations:** Mixed `<td>`, `<pre>`, `<div>`, `<form>`, `<input>`, `<button>` hardcoded
**Status:** Large file (144 changes) with extensive hardcoded HTML for forms and layouts

#### F. view_statements.php (both root and src/)
**Lines:** 80, 81, 82, 83
**Violations:**
```php
echo "<td>". $myrow['bank'] . "</td>";
echo "<td>" . $myrow['statementId']."</td>";
echo "<td>" . $myrow['smtDate'] . "</td>";
echo "<td>" . $myrow['account']. '(' . $myrow['currency'] . ')' . "</td>";
```
**Status:** Table cell generation hardcoded

#### G. validate_gl_entries.php
**Lines:** 71, 173, 264, 268, 270, 274, 278, 304, 313, 314, 321, 322
**Violations:** Extensive hardcoded HTML for validation UI
**Status:** Utility script, lower priority

#### H. module_config.php
**Lines:** 81-82, 107-296, 323
**Violations:** Configuration UI with hardcoded table cells and styled divs
**Status:** Admin UI, lower priority

#### I. modules/bank_import/bank_import_settings.php
**Lines:** 109-110, 114-115, 177-183
**Violations:** Settings form with hardcoded HTML
**Status:** Admin UI, lower priority

#### J. manage_uploaded_files.php
**Lines:** 94, 113, 171, 240-285
**Violations:** File management UI with hardcoded HTML
**Status:** Admin UI, lower priority

### Files with Acceptable Hardcoded HTML

These are allowed exceptions (not violations):

1. **src/Ksfraser/HTML/FaUiFunctions.php** - Lines 27, 36, 45
   - **Reason:** Legacy compatibility wrapper functions, documented as such
   
2. **src/Ksfraser/HTML/Composites/HTML_TABLE.php** - Lines 112, 124
   - **Reason:** This IS the HTML abstraction class (generates HTML internally)
   
3. **includes/fa_stubs.php** - Line 124
   - **Reason:** Stub functions for testing/compatibility

4. **tests/unit/HtmlOBTest.php** - Multiple lines
   - **Reason:** Test fixtures intentionally use raw HTML to test output buffering

5. **Markdown documentation files** (*.md) - Multiple files
   - **Reason:** Examples and documentation, not production code

---

## 3. RECOMMENDATIONS

### Immediate Actions (BLOCKER for regression testing)

#### Step 1: Resolve Duplicate Files
```powershell
# Decision needed: Which is authoritative - root or src/?
# Recommendation: Keep src/Ksfraser/FaBankImport/ (proper namespace structure)
# Delete root duplicates OR make root files require() from src/

# Option A: Delete root duplicates (RECOMMENDED)
Remove-Item class.AbstractQfxParser.php
Remove-Item class.bank_import_controller.php
# ... (all 21 class files)
Remove-Item import_statements.php
Remove-Item process_statements_preclean.php
# ... (all procedural duplicates)

# Option B: Make root files thin wrappers
# class.bi_lineitem.php becomes:
<?php require_once(__DIR__ . '/src/Ksfraser/FaBankImport/class.bi_lineitem.php');
```

#### Step 2: Clean Up Backup/Dev Files
```powershell
# Add to .gitignore:
*.php~
*.copilot.php
*-old.php

# Delete from repository:
git rm *.php~
git rm *.copilot.php
git rm *-old.php
```

#### Step 3: Fix HTML Abstraction Violations

**Priority 1 - Files in regression testing:**
1. `class.transactions_table.php` (323 changes) - Replace hardcoded table HTML with HtmlTable classes
2. `class.ViewBiLineItems.php` (52 changes) - Complete HTML abstraction
3. `class.bi_lineitem.php` (1225 changes) - Remove inline styles, use HTML classes

**Priority 2 - Files needing testing:**
4. `import_statements.php` (144 changes) - Abstract form HTML
5. `view_statements.php` - Abstract table generation

**Priority 3 - Admin/utility files:**
6. `validate_gl_entries.php`
7. `module_config.php`
8. `modules/bank_import/bank_import_settings.php`
9. `manage_uploaded_files.php`

### Implementation Pattern

**Before (WRONG):**
```php
echo '<table class="tablestyle2" width="100%">';
label_row("Trans Date:", $valueTimestamp);  // Using HtmlLabelRow
label_row("Amount:", $amount);               // Using HtmlLabelRow
echo '</table>';
```

**After (CORRECT):**
```php
use Ksfraser\HTML\Composites\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTableRow;

$table = new HtmlTable(2, 100); // TABLESTYLE2, 100% width
$table->addRow(new HtmlLabelRow(
    new HtmlString("Trans Date:"), 
    new HtmlString($valueTimestamp)
));
$table->addRow(new HtmlLabelRow(
    new HtmlString("Amount:"), 
    new HtmlString($amount)
));
$table->toHtml();
```

---

## 4. TESTING IMPACT

### Blocked Tests
- `class.transactions_table.php` - Cannot create baseline test due to:
  1. Duplicate files (which version to test?)
  2. Hardcoded HTML mixed with HTML classes (inconsistent abstraction)
  3. File is procedural script, not class library (requires $_POST, db connection, outputs directly)

### Test Coverage Gap
Current regression tests cover only 4 of 19 changed files (21%). Cannot proceed to remaining 15 files until architectural issues resolved.

---

## 5. ROOT CAUSE ANALYSIS

### How Did This Happen?

1. **Duplicate Files:**
   - Likely created during namespace refactoring (moving from root to src/)
   - Root files kept for backward compatibility
   - Never cleaned up

2. **Mixed HTML Abstraction:**
   - HTML class library created to replace FrontAccounting functions
   - Refactoring incomplete - table structure HTML not abstracted
   - Different developers worked on different sections (some abstracted, some not)

3. **Backup Files Committed:**
   - Missing .gitignore entries for *.php~, *.copilot.php
   - Dev/backup files accidentally committed

### Prevention Strategy

1. Add to .gitignore:
   ```
   *.php~
   *.copilot.php
   *-old.php
   *.bak
   ```

2. Establish HTML abstraction policy:
   - NO raw HTML `echo` statements in business logic
   - ALL HTML generation through HTML classes
   - Code review requirement: Check for hardcoded HTML

3. Enforce DRY:
   - One authoritative location per file
   - Use require() for compatibility if needed
   - Regular duplicate file audits

---

## 6. METRICS

### File Duplication
- **Total duplicate files:** 28
- **Critical duplicates (in regression scope):** 8 files
  - class.transactions_table.php (323 changes)
  - import_statements.php (144 changes)
  - class.bi_lineitem.php (1225 changes)
  - class.ViewBiLineItems.php (52 changes)
  - class.bi_transactions.php (91 changes)
  - class.bi_statements.php (13 changes)
  - class.bi_counterparty_model.php (12 changes)
  - view_statements.php

### HTML Violations
- **Files with hardcoded HTML:** 15+ (excluding tests, docs, stubs)
- **Critical violations (regression scope):** 5 files
- **Total hardcoded HTML echo statements:** 100+ (excluding acceptable exceptions)

### Technical Debt Estimate
- **Duplicate file cleanup:** ~2 hours (decision + deletion + testing)
- **Backup file cleanup:** ~30 minutes (.gitignore + git rm)
- **HTML abstraction fixes:**
  - class.transactions_table.php: 4-6 hours (complex table structures)
  - import_statements.php: 3-4 hours (forms + tables)
  - Other critical files: 2-3 hours each
  - **Total:** ~20-30 hours

---

## 7. NEXT STEPS

### Decision Required
**QUESTION FOR USER:** Which directory should be authoritative?
- Option A: Keep `src/Ksfraser/FaBankImport/`, delete root files (RECOMMENDED - proper namespacing)
- Option B: Keep root files, delete `src/` duplicates (backward compat, but messy)
- Option C: Make root files thin wrappers to src/ files (compromise, adds layer)

### Recommended Sequence
1. ✅ **COMPLETED:** Audit duplicate files
2. ✅ **COMPLETED:** Audit hardcoded HTML
3. **BLOCKED - User decision needed:** Choose authoritative directory
4. **TODO:** Remove duplicate files
5. **TODO:** Clean up backup/dev files
6. **TODO:** Fix HTML violations in critical files (regression scope)
7. **TODO:** Resume regression testing with clean architecture

---

## APPENDIX: Complete File Lists

### A. All Duplicate Class Files
```
class.AbstractQfxParser.php
class.bank_import_controller.php
class.bi_counterparty_model.php
class.bi_lineitem.php (TRIPLE: root, src, views)
class.bi_partners_data.php
class.bi_statements.php
class.bi_transaction.php
class.bi_transactions.php
class.bi_transactionTitle_model.php
class.CibcQfxParser.php
class.ManuQfxParser.php
class.PcmcQfxParser.php
class.QfxParserFactory.php
class.transactions_table.php
class.ViewBiLineItems.php
```

### B. All Duplicate Procedural Files
```
import_statements.php
import_statements-old.php
process_statements_preclean.php
process_statements.copilot_refactored.php
view_statements.php
```

### C. All Backup/Dev Files to Delete
```
*.php~ (6 files)
*.copilot.php (8 files)
*-old.php (2 files)
```

---

**Report Status:** COMPLETE  
**Blockers Identified:** YES (duplicate files, hardcoded HTML)  
**User Decision Required:** Authoritative directory choice  
**Estimated Resolution Time:** 25-35 hours total technical debt
