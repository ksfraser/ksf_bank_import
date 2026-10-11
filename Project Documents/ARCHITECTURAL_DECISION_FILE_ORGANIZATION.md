# Architectural Decision: File Organization & DRY Resolution
**Date:** 2025-11-16  
**Status:** APPROVED BY USER  
**Decision:** Organize by reusability, not duplication  

---

## Guiding Principle

> "Any code that could be re-usable for other projects should be under src/Ksfraser/* directories to become git repos. Anything that is very specific to this module should be under the root area."

---

## Classification Rules

### Library Code → `src/Ksfraser/*` (Extractable to separate repos)

**Criteria:** Could this be used in other projects (Paypal imports, Stripe, Wordpress, other FA modules)?

1. **Parsers** (`src/Ksfraser/FaBankImport/`)
   - QFX parsers (banks are reusable)
   - CSV parsers (payment processors are reusable)
   - File upload handlers (generic)
   - ✅ class.AbstractQfxParser.php
   - ✅ class.CibcQfxParser.php
   - ✅ class.ManuQfxParser.php
   - ✅ class.PcmcQfxParser.php
   - ✅ class.QfxParserFactory.php

2. **HTML Generation** (`src/Ksfraser/HTML/`)
   - All HTML element classes (generic)
   - All HTML composite classes (generic)
   - Table, form, input builders (generic)
   - ✅ Already properly located in src/Ksfraser/HTML/

3. **Business Logic Libraries** (`src/Ksfraser/FaBankImport/`)
   - Transaction matching algorithms (reusable)
   - Customer/Vendor creation (reusable for any FA import)
   - Bank account management (reusable)
   - ✅ Transaction matching logic
   - ✅ Customer/Vendor CRUD operations

4. **Data Models** (`src/Ksfraser/Model/`)
   - BiLineItemModel (transaction line items - reusable)
   - ✅ Already in src/Ksfraser/Model/

5. **Utilities** (`src/Ksfraser/Application/` or `src/Ksfraser/FaBankImport/`)
   - POST request handlers (generic)
   - Configuration management (generic)
   - Repository pattern classes (generic)
   - Error handlers (generic)
   - ✅ handlers/ErrorHandler.php
   - ✅ config/Config.php
   - ✅ repositories/TransactionRepository.php

### Module-Specific Code → Root directory (This FA module only)

**Criteria:** Specific to THIS bank import module's UI/workflow, not reusable

1. **View/Screen Files** (Root)
   - ❌ process_statements.php - Bank import workflow screen (FA-specific UI)
   - ❌ import_statements.php - Bank import screen (FA-specific UI)
   - ❌ view_statements.php - Statement viewing screen (FA-specific UI)
   - ❌ class.transactions_table.php - Transaction table display (FA-specific layout)
   - ❌ class.ViewBiLineItems.php - Line item view (FA-specific display)
   - ❌ class.bi_lineitem.php - Line item display/forms (FA-specific UI)
   - ❌ validate_gl_entries.php - Validation tool (FA GL-specific)
   - ❌ module_config.php - Module config screen (FA module-specific)
   - ❌ manage_uploaded_files.php - File management screen (module-specific)

2. **Module Integration** (Root)
   - ❌ hooks.php - FA module hooks (FA-specific)
   - ❌ class.bank_import_controller.php - Module controller (FA integration)

3. **Data Access** (Root OR src - decision needed)
   - 🤔 class.bi_transactions.php - Transaction table access (could be library?)
   - 🤔 class.bi_statements.php - Statements table access (could be library?)
   - 🤔 class.bi_counterparty_model.php - Counterparty access (could be library?)
   - 🤔 class.bi_transactionTitle_model.php - Title model (could be library?)
   - 🤔 class.bi_partners_data.php - Partner data access (could be library?)

**Decision for Data Access classes:** Keep in ROOT because they're tightly coupled to this module's database schema (bi_transactions, bi_statements tables specific to this module).

---

## Resolution Plan for Duplicate Files

### Phase 1: Delete Root Duplicates (Library Code)

**DELETE from root (keep in src/):**
```powershell
# Parsers (already in src/Ksfraser/FaBankImport/)
Remove-Item class.AbstractQfxParser.php
Remove-Item class.CibcQfxParser.php
Remove-Item class.ManuQfxParser.php
Remove-Item class.PcmcQfxParser.php
Remove-Item class.QfxParserFactory.php
```

### Phase 2: Delete src/ Duplicates (Module-Specific Code)

**DELETE from src/ (keep in root):**
```powershell
# View/screen files (module-specific UI)
Remove-Item src/Ksfraser/FaBankImport/process_statements.php  # Keep root version
Remove-Item src/Ksfraser/FaBankImport/import_statements.php   # Keep root version
Remove-Item src/Ksfraser/FaBankImport/view_statements.php     # Keep root version
Remove-Item src/Ksfraser/FaBankImport/class.transactions_table.php  # Keep root version
Remove-Item src/Ksfraser/FaBankImport/class.ViewBiLineItems.php     # Keep root version

# Module integration (FA-specific)
Remove-Item src/Ksfraser/FaBankImport/class.bank_import_controller.php  # Keep root version

# Data access classes (module DB schema)
Remove-Item src/Ksfraser/FaBankImport/class.bi_transactions.php
Remove-Item src/Ksfraser/FaBankImport/class.bi_statements.php
Remove-Item src/Ksfraser/FaBankImport/class.bi_counterparty_model.php
Remove-Item src/Ksfraser/FaBankImport/class.bi_transactionTitle_model.php
Remove-Item src/Ksfraser/FaBankImport/class.bi_partners_data.php
```

### Phase 3: Special Case - class.bi_lineitem.php (TRIPLE duplicate)

**Three locations:**
1. Root: `class.bi_lineitem.php`
2. Src: `src/Ksfraser/FaBankImport/class.bi_lineitem.php`
3. Views: `views/class.bi_lineitem.php`

**Analysis:**
- Contains matching algorithm (reusable) + display code (not reusable)
- Currently 1225 lines changed (massive file)
- Needs refactoring into separate concerns

**Decision:**
1. **SHORT TERM:** Keep ROOT version (canonical), delete src/ and views/ versions
2. **LONG TERM:** Split into:
   - `src/Ksfraser/FaBankImport/TransactionMatcher.php` (library - matching algorithm)
   - `class.bi_lineitem.php` (root - display logic)

```powershell
# For now - keep root, delete duplicates
Remove-Item src/Ksfraser/FaBankImport/class.bi_lineitem.php
Remove-Item views/class.bi_lineitem.php
```

### Phase 4: Delete Backup/Dev Files

```powershell
# Add to .gitignore first
Add-Content .gitignore "`n# Backup and development files"
Add-Content .gitignore "*.php~"
Add-Content .gitignore "*.copilot.php"
Add-Content .gitignore "*-old.php"

# Then remove from repo
git rm --cached *.php~
git rm --cached *.copilot.php
git rm --cached *-old.php
git rm --cached src/Ksfraser/FaBankImport/*.php~
git rm --cached src/Ksfraser/FaBankImport/*.copilot.php
```

### Phase 5: Clean up old/legacy files

```powershell
# These are clearly legacy/unused
Remove-Item import_statements-old.php
Remove-Item src/Ksfraser/FaBankImport/import_statements-old.php
Remove-Item process_statements_preclean.php
Remove-Item src/Ksfraser/FaBankImport/process_statements_preclean.php
Remove-Item process_statements.copilot_refactored.php
Remove-Item src/Ksfraser/FaBankImport/process_statements.copilot_refactored.php
```

---

## File Inventory After Cleanup

### Root Directory (Module-Specific)
```
├── process_statements.php         # Bank import workflow screen
├── import_statements.php          # Import screen
├── view_statements.php            # Statement viewing
├── class.transactions_table.php   # Transaction display
├── class.ViewBiLineItems.php      # Line item view
├── class.bi_lineitem.php          # Line item display/forms (needs refactoring)
├── class.bi_transactions.php      # Transaction table access
├── class.bi_statements.php        # Statements table access
├── class.bi_counterparty_model.php # Counterparty access
├── class.bi_transactionTitle_model.php # Title model
├── class.bi_partners_data.php     # Partner data access
├── class.bank_import_controller.php # Module controller
├── hooks.php                      # FA module hooks
├── validate_gl_entries.php        # Validation tool
├── module_config.php              # Module config screen
├── manage_uploaded_files.php      # File management
└── ... (other module-specific files)
```

### src/Ksfraser/ (Library Code - Extractable)
```
src/Ksfraser/
├── HTML/                          # HTML generation library (generic)
│   ├── Elements/                  # Button, Input, Hidden, etc.
│   ├── Composites/                # Table, Form, LabelRow, etc.
│   └── ...
│
├── FaBankImport/                  # Bank import library (reusable)
│   ├── class.AbstractQfxParser.php    # Parser base
│   ├── class.CibcQfxParser.php        # CIBC parser
│   ├── class.ManuQfxParser.php        # Manulife parser
│   ├── class.PcmcQfxParser.php        # PCMC parser
│   ├── class.QfxParserFactory.php     # Parser factory
│   ├── handlers/
│   │   ├── ErrorHandler.php           # Error handling (generic)
│   │   ├── SupplierTransactionHandler.php
│   │   ├── CustomerTransactionHandler.php
│   │   └── ... (transaction handlers)
│   ├── config/
│   │   └── Config.php                 # Config management (generic)
│   └── repositories/
│       └── TransactionRepository.php  # Repository pattern (generic)
│
├── Model/                         # Data models (generic)
│   └── BiLineItemModel.php        # Line item model
│
└── Application/                   # Application utilities (generic)
    └── handlers/
        └── ErrorHandler.php
```

---

## Migration Strategy

### Step 1: Analyze Each Duplicate
For each duplicate file, determine:
1. Is this library code (reusable) or module code (specific)?
2. Which version is most up-to-date?
3. Are there differences between versions?

### Step 2: Compare Duplicates
```powershell
# Example: Check if root and src versions differ
$files = @(
    "class.AbstractQfxParser.php",
    "class.bi_transactions.php",
    "import_statements.php"
    # ... etc
)

foreach ($file in $files) {
    $rootPath = ".\$file"
    $srcPath = ".\src\Ksfraser\FaBankImport\$file"
    
    if ((Test-Path $rootPath) -and (Test-Path $srcPath)) {
        $diff = Compare-Object (Get-Content $rootPath) (Get-Content $srcPath)
        if ($diff) {
            Write-Host "`n=== $file HAS DIFFERENCES ===" -ForegroundColor Red
            # Manual review needed
        } else {
            Write-Host "$file - IDENTICAL" -ForegroundColor Green
        }
    }
}
```

### Step 3: Execute Cleanup
Run deletion commands from phases 1-5 above

### Step 4: Update Includes/Requires
Search for any include/require statements that reference deleted files and update paths:
```powershell
# Find all require/include statements
Get-ChildItem -Recurse -Filter "*.php" | Select-String "require.*class\.(Abstract|Cibc|Manu|Pcmc|Qfx)" | Select-Object Path, LineNumber, Line
```

### Step 5: Test
1. Run existing regression tests
2. Manual smoke test of module
3. Check for missing file errors

---

## Long-Term Architectural Goals

### 1. Extract Libraries to Separate Repos
Once code is properly organized, extract reusable components:

**Potential libraries:**
- `ksfraser/html` - HTML generation classes
- `ksfraser/fa-bank-import` - Bank import library
- `ksfraser/fa-utilities` - FA integration utilities

### 2. Refactor Large Files
- Split class.bi_lineitem.php (1225 lines) into:
  - Matching algorithm library
  - Display/view code

### 3. Complete HTML Abstraction
- Remove all hardcoded HTML from module-specific files
- Use HTML library exclusively

### 4. Proper Namespacing
- Use PSR-4 autoloading
- Remove `class.` prefix from filenames in src/
- Use proper PHP namespaces throughout

---

## Regression Testing Impact

### Before Cleanup
❌ BLOCKED - Cannot test with 28 duplicate files

### After Cleanup
✅ Can proceed with regression testing:
1. No ambiguity about which file to test
2. Clear separation of concerns
3. Can test library code independently of FA integration
4. Module-specific code can be tested with FA context

---

## Implementation Commands

### Complete Cleanup Script
```powershell
# Phase 1: Delete root duplicates of library code
$libraryInSrc = @(
    "class.AbstractQfxParser.php",
    "class.CibcQfxParser.php",
    "class.ManuQfxParser.php",
    "class.PcmcQfxParser.php",
    "class.QfxParserFactory.php"
)

foreach ($file in $libraryInSrc) {
    if (Test-Path ".\$file") {
        Write-Host "Deleting root: $file (keeping src/ version)" -ForegroundColor Yellow
        Remove-Item ".\$file" -WhatIf  # Remove -WhatIf to execute
    }
}

# Phase 2: Delete src duplicates of module-specific code
$moduleInRoot = @(
    "src/Ksfraser/FaBankImport/process_statements.php",
    "src/Ksfraser/FaBankImport/import_statements.php",
    "src/Ksfraser/FaBankImport/view_statements.php",
    "src/Ksfraser/FaBankImport/class.transactions_table.php",
    "src/Ksfraser/FaBankImport/class.ViewBiLineItems.php",
    "src/Ksfraser/FaBankImport/class.bi_lineitem.php",
    "src/Ksfraser/FaBankImport/class.bank_import_controller.php",
    "src/Ksfraser/FaBankImport/class.bi_transactions.php",
    "src/Ksfraser/FaBankImport/class.bi_statements.php",
    "src/Ksfraser/FaBankImport/class.bi_counterparty_model.php",
    "src/Ksfraser/FaBankImport/class.bi_transactionTitle_model.php",
    "src/Ksfraser/FaBankImport/class.bi_partners_data.php"
)

foreach ($file in $moduleInRoot) {
    if (Test-Path $file) {
        Write-Host "Deleting src: $file (keeping root version)" -ForegroundColor Yellow
        Remove-Item $file -WhatIf  # Remove -WhatIf to execute
    }
}

# Phase 3: Delete views/ duplicate
if (Test-Path "views/class.bi_lineitem.php") {
    Write-Host "Deleting views/class.bi_lineitem.php (keeping root version)" -ForegroundColor Yellow
    Remove-Item "views/class.bi_lineitem.php" -WhatIf
}

# Phase 4: Backup/dev files
$patterns = @("*.php~", "*.copilot.php")
foreach ($pattern in $patterns) {
    Get-ChildItem -Recurse -Filter $pattern | Where-Object { $_.FullName -notmatch "vendor" } | ForEach-Object {
        Write-Host "Deleting backup/dev file: $($_.FullName)" -ForegroundColor Yellow
        Remove-Item $_.FullName -WhatIf
    }
}

# Phase 5: Legacy/old files
$legacyFiles = @(
    "import_statements-old.php",
    "src/Ksfraser/FaBankImport/import_statements-old.php",
    "process_statements_preclean.php",
    "src/Ksfraser/FaBankImport/process_statements_preclean.php",
    "process_statements.copilot_refactored.php",
    "src/Ksfraser/FaBankImport/process_statements.copilot_refactored.php"
)

foreach ($file in $legacyFiles) {
    if (Test-Path $file) {
        Write-Host "Deleting legacy: $file" -ForegroundColor Yellow
        Remove-Item $file -WhatIf
    }
}

Write-Host "`n✅ Cleanup complete! Remove -WhatIf flags to execute for real." -ForegroundColor Green
```

### Update .gitignore
```bash
# Add to .gitignore
echo "" >> .gitignore
echo "# Backup and development files" >> .gitignore
echo "*.php~" >> .gitignore
echo "*.copilot.php" >> .gitignore
echo "*-old.php" >> .gitignore
echo "*.bak" >> .gitignore
```

---

## Summary

**Principle:** Organize by reusability, not by duplication avoidance alone.

**Library code** (src/Ksfraser/*):
- Parsers, HTML classes, matching algorithms, utilities
- Can be extracted to separate repos
- Reusable across projects

**Module code** (root):
- Views, screens, FA integration, module-specific DB access
- Specific to this FA module
- Not intended for reuse

**Result:** 28 duplicates resolved, clear architecture, regression testing unblocked.
