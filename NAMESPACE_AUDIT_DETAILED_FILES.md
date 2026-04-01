# DETAILED NAMESPACE AUDIT - FILE MAPPINGS AND FIXES

## PART 1: FILES NOT AUTOLOADING DUE TO NAMESPACE/MAPPING MISMATCHES

### A. Service/ Directory Files (15 files) - Namespace: Ksfraser\FaBankImport\Service

**Location**: src/Ksfraser/FaBankImport/Service/  
**Expected Autoload**: Maps `Services` (plural) -> services/ (lowercase) in composer.json  
**Actual Namespace in Files**: `Ksfraser\FaBankImport\Service` (singular)  
**Result**: ❌ **WILL NOT AUTOLOAD**

**Files in this directory**:
1. AccountMappingResolver.php - Line 2: `namespace Ksfraser\FaBankImport\Service;`
2. BankImportLogger.php - `Ksfraser\FaBankImport\Service;`
3. BankImportPathResolver.php - `Ksfraser\FaBankImport\Service;`
4. BaseLogger.php - `Ksfraser\FaBankImport\Service;`
5. DetectedAccountAssociationKey.php - `Ksfraser\FaBankImport\Service;`
6. DuplicateDetector.php - `Ksfraser\FaBankImport\Service;`
7. FileStorageService.php - `Ksfraser\FaBankImport\Service;`
8. FileStorageServiceInterface.php - `Ksfraser\FaBankImport\Service;`
9. FileUploadService.php - `Ksfraser\FaBankImport\Service;`
10. ImportRunLogger.php - `Ksfraser\FaBankImport\Service;`
11. LegacyBankAccountsMigrator.php - `Ksfraser\FaBankImport\Service;`
12. SquareTransactionActions.php - `Ksfraser\FaBankImport\Service;`
13. StatementAccountMappingService.php - `Ksfraser\FaBankImport\Service;`
14. ThirdPartyTransactionActionsInterface.php - `Ksfraser\FaBankImport\Service;`
15. Schema/BankImportModuleSchemaService.php - `Ksfraser\FaBankImport\Service\Schema;`

**FIX**: Either (A) Move to services/ OR (B) Update composer.json mapping

---

### B. model/ Directory Files (1 file) - Namespace: Ksfraser\FaBankImport\Model

**Location**: src/Ksfraser/FaBankImport/model/  
**Expected Autoload**: Maps `Models` (plural) -> models/ (lowercase) in composer.json  
**Actual Namespace**: `Ksfraser\FaBankImport\Model` (singular, PascalCase)  
**Result**: ❌ **WILL NOT AUTOLOAD**

**File**:
- BiLineItemModel.php - Line 14: `namespace Ksfraser\FaBankImport\Model;`

**FIX**: Move to models/ OR update composer.json

---

### C. view/ Directory Files (1 file) - Namespace: Ksfraser\FaBankImport\View

**Location**: src/Ksfraser/FaBankImport/view/  
**Expected Autoload**: Maps `Views` (plural) -> views/ (lowercase) in composer.json  
**Actual Namespace**: `Ksfraser\FaBankImport\View` (singular, PascalCase)  
**Result**: ❌ **WILL NOT AUTOLOAD**

**File**:
- BiLineItemView.php - Line 14: `namespace Ksfraser\FaBankImport\View;`

**FIX**: Move to views/ OR update composer.json

---

## PART 2: APPLICATION SHARED KERNEL FILES NOT MAPPED

### D. app/Shared/ Files (17 files) - Namespace: Ksfraser\FaBankImport\Shared

**Location**: app/Shared/  
**Expected Autoload**: NONE - NO PSR4 MAPPING FOR app/ ROOT  
**Actual Namespace**: `Ksfraser\FaBankImport\Shared\*`  
**Result**: ❌ **WILL NOT AUTOLOAD** (CRITICAL FOR PHASE 0)

**Subdirectories and Files**:

#### DTOs (6 files)
- app/Shared/DTOs/AccountResolutionDTO.php - Line 2: `namespace Ksfraser\FaBankImport\Shared\DTOs;`
- app/Shared/DTOs/DuplicateResolutionDTO.php - `Ksfraser\FaBankImport\Shared\DTOs;`
- app/Shared/DTOs/ImportSummaryDTO.php - `Ksfraser\FaBankImport\Shared\DTOs;`
- app/Shared/DTOs/MappingConfirmationDTO.php - `Ksfraser\FaBankImport\Shared\DTOs;`
- app/Shared/DTOs/ParseFilesDTO.php - `Ksfraser\FaBankImport\Shared\DTOs;`
- app/Shared/DTOs/UploadFormDTO.php - `Ksfraser\FaBankImport\Shared\DTOs;`

#### Entities (8 files)
- app/Shared/Entities/BankAccountMapping.php - Line 2: `namespace Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/BankStatement.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/Counterparty.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/LineItem.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/PartnerKeyword.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/Transaction.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/TransactionTitle.php - `Ksfraser\FaBankImport\Shared\Entities;`
- app/Shared/Entities/TransferMatch.php - `Ksfraser\FaBankImport\Shared\Entities;`

#### Repositories (1 file)
- app/Shared/Repositories/BankAccountMappingRepository.php - Line 2: `namespace Ksfraser\FaBankImport\Shared\Repositories;`

#### Factories (1 file)
- app/Shared/Factories/BankAccountMappingFactory.php - Line 2: `namespace Ksfraser\FaBankImport\Shared\Factories;`

#### ValueObjects (1 file)
- app/Shared/ValueObjects/PartnerData.php - Line 2: `namespace Ksfraser\FaBankImport\Shared\ValueObjects;`

**FIX**: Add to composer.json:
```json
"Ksfraser\\FaBankImport\\Shared\\": "app/Shared/"
```

---

## PART 3: WRONG NAMESPACE PREFIX FILES

### E. Views without Ksfraser prefix

**File**: src/Views/TransactionView.php  
**Line 5**: `namespace Views;`  
**Problem**: Missing full namespace context, just "Views"  
**Result**: ⚠️ Can create global namespace conflicts

**Current Autoload**: `"Views\\"` -> `"src/Views/"`

**FIX**: Change namespace to `Ksfraser\FaBankImport\Views` and update composer.json

---

## PART 4: UNDEFINED PARENT CLASS ISSUES

### F. Ksfraser\PartnerTypes - AbstractPartnerType Problem

**Files**: src/Ksfraser/PartnerTypes/*.php

**Critical Issue**: AbstractPartnerType.php, Line 19
```php
class AbstractPartnerType extends AbstractPartnerType
```
**Problem**: Self-extending class (circular)

**All files extending AbstractPartnerType**:
1. BankTransferPartnerType.php - Line 18: `extends AbstractPartnerType`
2. CustomerPartnerType.php - Line 18: `extends AbstractPartnerType`
3. ManualSettlementPartnerType.php - Line 18: `extends AbstractPartnerType`
4. MatchedPartnerType.php - Line 18: `extends AbstractPartnerType`
5. QuickEntryPartnerType.php - Line 18: `extends AbstractPartnerType`
6. SupplierPartnerType.php - Line 18: `extends AbstractPartnerType`
7. UnknownPartnerType.php - Line 5: `extends AbstractPartnerType`

**Missing**: No use statement importing AbstractPartnerType!

**FIX**: 
```php
// Add to each file:
use Ksfraser\PartnerTypes\AbstractPartnerType;

// OR use fully qualified:
class BankTransferPartnerType extends \Ksfraser\PartnerTypes\AbstractPartnerType
```

---

### G. Ksfraser\HTML Classes - Missing Base Classes

**Problem**: Many classes extend undefined base classes

**Missing Base Classes**:
- HtmlString - Referenced by 7 files
- BaseTableRow - Referenced by 3 files
- BaseScriptHandler - Referenced by 4 files
- HtmlElement - Referenced by 2 files
- HtmlFormatting - Referenced by 1 file

**Files Affected**:

HtmlString References (7):
- src/Ksfraser/HTML/HtmlScriptLanguage.php - Line 2: `extends HtmlString`
- src/Ksfraser/HTML/Javascript/HtmlJSString.php - Line 3: `extends HtmlString`
- src/Ksfraser/HTML/Typescript/HtmlTypeScriptString.php - Line 2: `extends HtmlString`
- src/Ksfraser/HTML/VBScript/HtmlVBScriptString.php - Line 2: `extends HtmlString`

BaseTableRow References (3):
- src/Ksfraser/HTML/Rows/InterestFreqTableRow.php - Line 2: `extends BaseTableRow`
- src/Ksfraser/HTML/Rows/LoanSummaryTableRow.php - Line 2: `extends BaseTableRow`
- src/Ksfraser/HTML/Rows/LoanTypeTableRow.php - Line 2: `extends BaseTableRow`

BaseScriptHandler References (4):
- src/Ksfraser/HTML/ScriptHandlers/InterestFreqScriptHandler.php - Line 2: `extends BaseScriptHandler`
- src/Ksfraser/HTML/ScriptHandlers/LoanScriptHandler.php - Line 2: `extends BaseScriptHandler`
- src/Ksfraser/HTML/ScriptHandlers/LoanTypeScriptHandler.php - Line 2: `extends BaseScriptHandler`
- src/Ksfraser/HTML/ScriptHandlers/ReportScriptHandler.php - Line 2: `extends BaseScriptHandler`

HtmlElement References (2):
- src/Ksfraser/HTML/HtmlEmptyElement.php - Line 3: `extends HtmlElement`
- src/Ksfraser/HTML/HtmlEmptyElement (2).php - Line 3: `extends HtmlElement`

HtmlFormatting References (1):
- src/Ksfraser/HTML/Formatting/HtmlSup.php - Line 3: `extends HtmlFormatting`

**FIX**: Either (A) Create missing base classes OR (B) Make classes abstract and not extend OR (C) Import/create interfaces

---

### H. Application & Model - Missing GenericFaInterfaceModel

**File**: src/Ksfraser/FaBankImport/model/BiLineItemModel.php  
**Line 2**: `extends GenericFaInterfaceModel`  
**Problem**: Class not found (no use statement, no definition)  

**FIX**: Add use statement:
```php
use Ksfraser\GenericInterface\GenericFaInterfaceTrait;
// then change extends to: 
class BiLineItemModel {
    use GenericFaInterfaceModel; // Convert to trait if possible
}
```

---

### I. Application Controllers - Missing AbstractController

**File**: src/Ksfraser/Application/controllers/AdminController.php  
**Line 3**: `extends AbstractController`  
**Problem**: AbstractController not imported or defined

**Files Affected**:
- src/Ksfraser/Application/controllers/AdminController.php - `extends AbstractController`

**FIX**: Create the AbstractController class or add use statement

---

### J. View Classes - Missing Origin Class

**File**: src/Ksfraser/FaBankImport/view/BiLineItemView.php  
**Line**: extends Origin  
**Problem**: Origin class not defined

**FIX**: Either create Origin class or remove inheritance

---

### K. Test Classes - Missing TestCase Import

**Files**:
- src/Ksfraser/Application/tests/unit/AlertServiceTest.php - `extends TestCase`
- src/Ksfraser/Application/tests/unit/ContainerTest.php - `extends TestCase`
- src/Ksfraser/Application/tests/unit/MetricsAggregatorTest.php - `extends TestCase`
- src/Ksfraser/Application/tests/integration/DatabaseTestCase.php - `extends TestCase`

**Problem**: No use statement importing TestCase from PHPUnit

**FIX**: Add to each test file:
```php
use PHPUnit\Framework\TestCase;
```

---

## PART 5: COMPOSER.JSON AUTOLOAD CURRENT STATE

### Current Invalid Mappings

```json
"autoload": {
    "psr-4": {
        "Controllers\\": "src/Controllers/",                    // ⚠️ Non-standard
        "Ksfraser\\": "src/Ksfraser/",                         // ⚠️ Too broad
        "Ksfraser\\FaBankImport\\": "src/Ksfraser/FaBankImport/",  // ✓ Good
        
        // Duplicate mappings - CONFLICT!
        "Ksfraser\\FaBankImport\\Repository\\": "src/Ksfraser/FaBankImport/Repository/",
        "Ksfraser\\FaBankImport\\Repositories\\": "src/Ksfraser/FaBankImport/repositories/",
        
        // Service mappings - case mismatch
        "Ksfraser\\FaBankImport\\Services\\": "src/Ksfraser/FaBankImport/services/",
        
        // MISSING Mappings
        // NO mapping for "Ksfraser\\FaBankImport\\Service\\"  <- Singular
        // NO mapping for "Ksfraser\\FaBankImport\\Model\\"    <- Singular  
        // NO mapping for "Ksfraser\\FaBankImport\\View\\"     <- Singular
        // NO mapping for "Ksfraser\\FaBankImport\\Shared\\"   <- app/Shared/ location
        
        // Old/Non-standard mappings
        "KsfBankImport\\Services\\": "Services/",               // ⚠️ Wrong case
        "KsfBankImport\\OperationTypes\\": "OperationTypes/",   // ⚠️ Wrong case
        "Views\\": "src/Views/",                               // ⚠️ No prefix context
    }
}
```

---

## PRIORITY FIXES SUMMARY

### CRITICAL (Must fix for functioning codebase)

1. **Add app/Shared/ mapping** - Phase 0 Shared Kernel
   ```json
   "Ksfraser\\FaBankImport\\Shared\\": "app/Shared/"
   ```
   
2. **Fix Service/ vs services/** - 15 files won't load
   - Move src/Ksfraser/FaBankImport/Service/ → src/Ksfraser/FaBankImport/services/
   - Update all namespace declarations to Services (plural)
   
3. **Fix model/ directory** - 1 file won't load
   - Move src/Ksfraser/FaBankImport/model/ → src/Ksfraser/FaBankImport/models/
   - Update namespace to Models (plural)
   
4. **Fix view/ directory** - 1 file won't load
   - Move src/Ksfraser/FaBankImport/view/ → src/Ksfraser/FaBankImport/views/
   - Update namespace to Views (plural)

### HIGH (Prevents runtime errors)

5. **Fix AbstractPartnerType** - Self-extending class
   - Add use statement to all PartnerType files
   - Fix circular inheritance
   
6. **Add missing parent classes or use statements** - 100+ classes
   - HtmlString, BaseTableRow, BaseScriptHandler, etc.
   - Or create these as actual base classes
   
7. **Fix test classes TestCase import** - 4 test files

### MEDIUM (Code quality)

8. **Remove wrong namespace prefixes** - Views, KsfBankImport
9. **Clean up duplicate PSR4 mappings** - Repository vs Repositories
10. **Remove overlapping PSR4 prefixes** - Ksfraser\ vs Ksfraser\FaBankImport\

---

## VERIFICATION CHECKLIST

After implementing fixes:

- [ ] `composer validate` passes
- [ ] `composer dump-autoload -v` shows no conflicts
- [ ] All 37 non-autoloading files now load correctly
- [ ] PHPUnit tests run without autoload errors
- [ ] grep for undefined parent classes returns 0 results
- [ ] All app/Shared/ files are accessible via their namespaces
- [ ] No "Class not found" errors in application

