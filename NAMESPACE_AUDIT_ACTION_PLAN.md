# NAMESPACE AND IMPORT AUDIT - ACTION PLAN

**Prepared**: April 1, 2026  
**Severity**: CRITICAL - 37 files not autoloading, Phase 0 Shared Kernel broken  
**Estimated Fix Time**: 2-3 hours  

---

## EXECUTIVE ACTION ITEMS

### IMMEDIATE (Do First - Unblocks Everything)

**ACTION 1: Add app/Shared/ to composer.json** ⏱️ 5 minutes

**Impact**: Enables all Phase 0 Shared Kernel files (17 files)

**Steps**:
1. Open `composer.json`
2. Find the `"autoload"` -> `"psr-4"` section
3. Add these entries (in alphabetical order with other Ksfraser entries):

**Insert after `"Ksfraser\\FaBankImport\\Request\\"`:**
```json
"Ksfraser\\FaBankImport\\Shared\\": "app/Shared/",
"Ksfraser\\FaBankImport\\Shared\\DTOs\\": "app/Shared/DTOs/",
"Ksfraser\\FaBankImport\\Shared\\Entities\\": "app/Shared/Entities/",
"Ksfraser\\FaBankImport\\Shared\\Factories\\": "app/Shared/Factories/",
"Ksfraser\\FaBankImport\\Shared\\Repositories\\": "app/Shared/Repositories/",
"Ksfraser\\FaBankImport\\Shared\\ValueObjects\\": "app/Shared/ValueObjects/",
```

4. Run: `composer dump-autoload`
5. Verify: `composer validate`

**Verify**: Files in app/Shared/ should now be autoloadable

---

**ACTION 2: Choose Directory Strategy for Service/model/view** ⏱️ 5 minutes decision

**Decision Point**: For each, choose strategy A or B

#### Strategy A (Recommended): Move to plural lowercase
- Service/ → services/
- model/ → models/ 
- view/ → views/
- Update namespaces to use plural
- *Advantage*: Aligns with composer.json existing mappings

#### Strategy B: Update composer.json
- Create new mappings for singular names
- Keep directory names as-is
- *Advantage*: Minimal file moves, but adds complexity

**DECISION**: ✅ **Use Strategy A** (Move directories, update namespaces)

---

### STEP-BY-STEP FIX IMPLEMENTATION

## PHASE 1: Directory Consolidation (30 minutes)

### Step 1.1: Consolidate Service/ → services/ (15 files)

**Command** (in terminal at repository root):
```bash
# Move Service/ directory to services/ with backup
mkdir -p ".backups/$(date +%Y%m%d_%H%M%S)"
cp -r src/Ksfraser/FaBankImport/Service ".backups/$(date +%Y%m%d_%H%M%S)/Service_BACKUP"

# Move the directory
mv src/Ksfraser/FaBankImport/Service/* src/Ksfraser/FaBankImport/services/
rmdir src/Ksfraser/FaBankImport/Service
```

**Update Namespaces** (15 files need changes):

Open each file and change:
```php
// OLD (BEFORE):
namespace Ksfraser\FaBankImport\Service;
namespace Ksfraser\FaBankImport\Service\Schema;

// NEW (AFTER):
namespace Ksfraser\FaBankImport\Services;
namespace Ksfraser\FaBankImport\Services\Schema;
```

**Files to update**:
```
src/Ksfraser/FaBankImport/services/AccountMappingResolver.php
src/Ksfraser/FaBankImport/services/BankImportLogger.php
src/Ksfraser/FaBankImport/services/BankImportPathResolver.php
src/Ksfraser/FaBankImport/services/BaseLogger.php
src/Ksfraser/FaBankImport/services/DetectedAccountAssociationKey.php
src/Ksfraser/FaBankImport/services/DuplicateDetector.php
src/Ksfraser/FaBankImport/services/FileStorageService.php
src/Ksfraser/FaBankImport/services/FileStorageServiceInterface.php
src/Ksfraser/FaBankImport/services/FileUploadService.php
src/Ksfraser/FaBankImport/services/ImportRunLogger.php
src/Ksfraser/FaBankImport/services/LegacyBankAccountsMigrator.php
src/Ksfraser/FaBankImport/services/SquareTransactionActions.php
src/Ksfraser/FaBankImport/services/StatementAccountMappingService.php
src/Ksfraser/FaBankImport/services/ThirdPartyTransactionActionsInterface.php
src/Ksfraser/FaBankImport/services/Schema/BankImportModuleSchemaService.php
```

---

### Step 1.2: Consolidate model/ → models/ (1 file)

**Commands**:
```bash
# Move the file
mv src/Ksfraser/FaBankImport/model/BiLineItemModel.php src/Ksfraser/FaBankImport/models/BiLineItemModel.php
rmdir src/Ksfraser/FaBankImport/model
```

**Update Namespace** in: `src/Ksfraser/FaBankImport/models/BiLineItemModel.php`
```php
// OLD:
namespace Ksfraser\FaBankImport\Model;

// NEW:
namespace Ksfraser\FaBankImport\Models;
```

---

### Step 1.3: Consolidate view/ → views/ (1 file)

**Commands**:
```bash
# Move the file  
mv src/Ksfraser/FaBankImport/view/BiLineItemView.php src/Ksfraser/FaBankImport/views/BiLineItemView.php
rmdir src/Ksfraser/FaBankImport/view
```

**Update Namespace** in: `src/Ksfraser/FaBankImport/views/BiLineItemView.php`
```php
// OLD:
namespace Ksfraser\FaBankImport\View;

// NEW:
namespace Ksfraser\FaBankImport\Views;
```

---

### Step 1.4: Update any imports referencing moved namespaces

**Search** for all references to old namespaces:
```bash
# Find all files importing from old Service\ namespace
grep -r "use Ksfraser\\\\FaBankImport\\\\Service" src/ --include="*.php"

# Find all files importing from old Model\ namespace
grep -r "use Ksfraser\\\\FaBankImport\\\\Model" src/ --include="*.php"

# Find all files importing from old View\ namespace
grep -r "use Ksfraser\\\\FaBankImport\\\\View" src/ --include="*.php"
```

**Example changes**:
```php
// OLD:
use Ksfraser\FaBankImport\Service\AccountMappingResolver;
use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\FaBankImport\View\BiLineItemView;

// NEW:
use Ksfraser\FaBankImport\Services\AccountMappingResolver;
use Ksfraser\FaBankImport\Models\BiLineItemModel;
use Ksfraser\FaBankImport\Views\BiLineItemView;
```

---

## PHASE 2: Fix Undefined Parent Classes (45 minutes)

### Step 2.1: Fix AbstractPartnerType Self-Reference

**File**: `src/Ksfraser/PartnerTypes/AbstractPartnerType.php`

**Current**:
```php
class AbstractPartnerType extends AbstractPartnerType
```

**FIX** - Option A (Recommended): Make it a base class without extending
```php
// Remove the extends clause
class AbstractPartnerType
{
    // ... methods
}
```

**FIX** - Option B: If it should extend something, create proper base
```php
// Create a base class first, then have AbstractPartnerType extend it
```

**After fixing AbstractPartnerType**, add use statements to all PartnerType files:

**Files to update**:
```
src/Ksfraser/PartnerTypes/BankTransferPartnerType.php
src/Ksfraser/PartnerTypes/CustomerPartnerType.php
src/Ksfraser/PartnerTypes/ManualSettlementPartnerType.php
src/Ksfraser/PartnerTypes/MatchedPartnerType.php
src/Ksfraser/PartnerTypes/QuickEntryPartnerType.php
src/Ksfraser/PartnerTypes/SupplierPartnerType.php
src/Ksfraser/PartnerTypes/UnknownPartnerType.php
```

**Add to top of each**:
```php
use Ksfraser\PartnerTypes\AbstractPartnerType;
```

---

### Step 2.2: Fix Test Classes TestCase Import

**Files** (4 files to modify):
```
src/Ksfraser/Application/tests/unit/AlertServiceTest.php
src/Ksfraser/Application/tests/unit/ContainerTest.php
src/Ksfraser/Application/tests/unit/MetricsAggregatorTest.php
src/Ksfraser/Application/tests/integration/DatabaseTestCase.php
```

**Add to top** of each file (after `<?php`):
```php
use PHPUnit\Framework\TestCase;
```

---

### Step 2.3: Handle Missing HTML Base Classes

**Analysis needed**: 

Check if these classes should exist:
- HtmlString
- BaseTableRow  
- BaseScriptHandler
- HtmlElement
- HtmlFormatting

**Option A** (Recommended): Create base classes
1. Create `src/Ksfraser/HTML/HtmlString.php`
2. Create `src/Ksfraser/HTML/BaseTableRow.php`
3. Create `src/Ksfraser/HTML/BaseScriptHandler.php`
4. etc.

**Option B**: Make classes non-extendable
1. Remove `extends HtmlString` etc.
2. Implement interfaces instead

**Recommendation**: Use Option A - Create the base classes with abstract methods

---

### Step 2.4: Fix Application Controller

**File**: `src/Ksfraser/Application/controllers/AdminController.php`

**Option A**: Create AbstractController
```bash
# Create the base controller file
touch src/Ksfraser/Application/controllers/AbstractController.php
```

Then in AdminController.php, add:
```php
use Ksfraser\Application\Controllers\AbstractController;
```

**Option B**: Create the base class in different location and import

---

### Step 2.5: Fix Model and View Origin/Model Issues

**BiLineItemModel.php** - replace `extends GenericFaInterfaceModel` with:
```php
use Ksfraser\GenericInterface\GenericFaInterfaceTrait;

class BiLineItemModel {
    use GenericFaInterfaceTrait;
    // ... rest of class
}
```

**BiLineItemView.php** - Check if Origin class is needed or if it should use interface

---

## PHASE 3: Fix Namespace Prefixes (15 minutes)

### Step 3.1: Fix Views namespace prefix

**File**: `src/Views/TransactionView.php`

**Current** (line 5):
```php
namespace Views;
```

**Change to**:
```php
namespace Ksfraser\FaBankImport\Views;
```

Also update composer.json - remove or fix:
```json
// IN composer.json - REMOVE or UPDATE this:
"Views\\": "src/Views/",

// Option 1 - Remove it completely and rely on:
// "Ksfraser\\FaBankImport\\Views\\" -> "src/Ksfraser/FaBankImport/views/"

// Option 2 - If this is legacy, update to:
// No separate Views mapping needed
```

---

## PHASE 4: Verification & Testing (30 minutes)

### Step 4.1: Validate Composer Configuration

```bash
# Validate composer.json syntax
composer validate

# Dump and rebuild autoload
composer dump-autoload -v

# Check for conflicts/issues
composer dump-autoload --dry-run
```

**Expected output**: No errors, all namespaces loaded

---

### Step 4.2: Run Tests

```bash
# Run full test suite to catch runtime errors
vendor/bin/phpunit

# Or run tests with color output for better visibility
vendor/bin/phpunit --colors=always
```

**Expected**: All tests pass (or same number pass as before audit)

---

### Step 4.3: Manual Verification

```bash
# Check no more references to old namespaces
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Service" src/ --include="*.php"
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Model" src/ --include="*.php"
grep -r "namespace Ksfraser\\\\FaBankImport\\\\View" src/ --include="*.php"

# Check all Service files now use Services namespace
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Services" src/ --include="*.php" | wc -l
# Should show 15 files

# Verify app/Shared files accessible
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Shared" app/ --include="*.php" | wc -l
# Should show 17 files

# Check for remaining undefined extends
grep -r "extends [A-Z]" src/ --include="*.php" | grep -v "use " | grep -v "//" | head -20
```

---

### Step 4.4: Run Audit Script Again

```bash
# Re-run the namespace audit
php audit-namespaces.php 2>&1 | grep "CRITICAL\|HIGH\|MEDIUM" | wc -l

# Should show fewer issues or zero if all fixed
```

---

## ROLLBACK PROCEDURE (If needed)

```bash
# Restore from backup
cp -r ".backups/LATEST_BACKUP/Service_BACKUP" src/Ksfraser/FaBankImport/Service

# Then revert composer.json to previous version
git checkout composer.json

# Rebuild autoload
composer dump-autoload
```

---

## ESTIMATED TIMELINE

| Phase | Task | Time | Total |
|-------|------|------|-------|
| 1 | Move Service/ → services/ + update namespaces | 10 min | 10 min |
| 1 | Move model/ → models/ + update namespace | 3 min | 13 min |
| 1 | Move view/ → views/ + update namespace | 3 min | 16 min |
| 1 | Update imports referencing moved namespaces | 2-5 min | 18-21 min |
| **1 Subtotal** | Directory consolidation | | **~20 min** |
| 2 | Fix AbstractPartnerType + add use statements (7 files) | 10 min | 10 min |
| 2 | Fix test TestCase imports (4 files) | 3 min | 13 min |
| 2 | Investigate/create HTML base classes | 10-15 min | 23-28 min |
| 2 | Fix ApplicationController | 5 min | 28-33 min |
| 2 | Fix Model/View base classes | 5 min | 33-38 min |
| **2 Subtotal** | Parent class fixes | | **~40 min** |
| 3 | Fix Views namespace prefix | 5 min | 5 min |
| **3 Subtotal** | Namespace prefix fixes | | **~5 min** |
| 4 | Validate, test, verify | 30 min | 30 min |
| **TOTAL** | All fixes + verification | | **~95 min** |

**Realistic total with iteration: 2-2.5 hours**

---

## SUCCESS CRITERIA

After completing all phases:

✅ `composer validate` passes  
✅ `composer dump-autoload` shows no conflicts  
✅ All 37 previously non-loading files now autoload  
✅ PHPUnit runs without class-not-found errors  
✅ No files still in old `Ksfraser\FaBankImport\Service\` (singular) namespace  
✅ app/Shared/ files accessible via namespaces  
✅ No undefined parent class references  
✅ All tests pass  

---

## SIGN-OFF

When all actions are complete:
1. Delete/archive backup directories
2. Run full test suite one final time
3. Commit changes with message: "fix: consolidate namespaces and fix autoload mappings"
4. Update any documentation referencing old namespaces

