# COMPREHENSIVE NAMESPACE AND IMPORT AUDIT REPORT

**Date**: April 1, 2026  
**Status**: Critical Issues Identified  
**Scope**: src/ and app/ directories  

---

## EXECUTIVE SUMMARY

This audit identified **multiple critical architectural issues** preventing proper autoloading and namespace consistency:

1. **Composer.json has broken autoload mappings** - Conflicting PSR4 entries  
2. **Namespace/Directory Mismatch** - Files in wrong directories with mismatched namespaces  
3. **Duplicate Directories** - model/ vs models/, view/ vs views/, Service/ vs services/  
4. **Missing Autoload Mapping** - app/Shared/ files not registered in composer.json  
5. **Undefined Parent Classes** - 100+ classes extending non-existent parents  
6. **Wrong Namespace Prefixes** - Views, KsfBankImport instead of Ksfraser\FaBankImport  

---

## ISSUE CATEGORY 1: AUTOLOAD MAPPING PROBLEMS IN COMPOSER.JSON

### CRITICAL: Missing/Broken PSR4 Mappings

**File**: composer.json, "autoload" section

**Issues**:

```
Conflicting Mappings:
- "Ksfraser\\FaBankImport\\Repository\\" -> "src/Ksfraser/FaBankImport/Repository/"
- "Ksfraser\\FaBankImport\\Repositories\\" -> "src/Ksfraser/FaBankImport/repositories/"
  → BOTH exist but different namespaces (singular vs plural)
```

**Case Sensitivity Mismatches** (PascalCase namespace, lowercase directory):
```
- "Ksfraser\\FaBankImport\\Models\\" -> "src/Ksfraser/FaBankImport/models/" ✓ exists
- "Ksfraser\\FaBankImport\\Services\\" -> "src/Ksfraser/FaBankImport/services/" ✓ exists  
- "Ksfraser\\FaBankImport\\Views\\" -> "src/Ksfraser/FaBankImport/views/" ✓ exists

BUT ALSO (not mapped!):
- "Ksfraser\\FaBankImport\\Service\\" -> src/Ksfraser/FaBankImport/Service/ ❌ NOT MAPPED
- "Ksfraser\\FaBankImport\\Model\\" -> src/Ksfraser/FaBankImport/model/ ❌ NOT MAPPED
- "Ksfraser\\FaBankImport\\View\\" -> src/Ksfraser/FaBankImport/view/ ❌ NOT MAPPED
```

### CRITICAL: app/Shared/ Not Mapped

**Issue**: Phase 0 Shared Kernel files ARE NOT in composer.json autoload

**Files**: 
- app/Shared/DTOs/*.php
- app/Shared/Entities/*.php
- app/Shared/Repositories/*.php
- app/Shared/Factories/*.php
- app/Shared/ValueObjects/*.php

**Namespace**: `Ksfraser\FaBankImport\Shared\*`  
**Current Status**: Will NOT autoload (no mapping for app/)

**Recommended Fix**:
```json
"Ksfraser\\FaBankImport\\Shared\\": "app/Shared/"
```

---

## ISSUE CATEGORY 2: NAMESPACE/DIRECTORY MISMATCHES

### HIGH: Files in Singular Directories with Mapped Plural Namespaces

#### Service → Services mismatch

| File Path | Namespace Used | Autoload Maps | Status |
|-----------|----------------|---------------|--------|
| src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php | `Ksfraser\FaBankImport\Service\Schema` | ❌ No, only `Services` | **WON'T LOAD** |
| src/Ksfraser/FaBankImport/Service/AccountMappingResolver.php | `Ksfraser\FaBankImport\Service` | ❌ No, only `Services` | **WON'T LOAD** |
| (+ 13 more files in Service/) | `Ksfraser\FaBankImport\Service\*` | ❌ No mapping | **WON'T LOAD** |

**Impact**: ~15 files in Service/ directory won't load

#### Model → Models mismatch

| File Path | Namespace | Autoload Maps | Status |
|-----------|-----------|---------------|--------|
| src/Ksfraser/FaBankImport/model/BiLineItemModel.php | `Ksfraser\FaBankImport\Model` | ❌ No, only `Models` | **WON'T LOAD** |

**Impact**: 1 file won't load

#### View → Views mismatch  

| File Path | Namespace | Autoload Maps | Status |
|-----------|-----------|---------------|--------|
| src/Ksfraser/FaBankImport/view/BiLineItemView.php | `Ksfraser\FaBankImport\View` | ❌ No, only `Views` | **WON'T LOAD** |

**Impact**: 1 file won't load

---

## ISSUE CATEGORY 3: WRONG NAMESPACE PREFIXES

### CRITICAL: Views without Ksfraser prefix

**File**: src/Views/TransactionView.php  
**Line**: 5  
**Current Namespace**: `namespace Views;`  
**Problem**: Missing full namespace prefix  
**Should Be**: `namespace Ksfraser\FaBankImport\Views;`

**Autoload maps**: `"Views\\"` -> `"src/Views/"` (bad pattern!)

**Impact**: This creates a toplevel namespace that could conflict

---

## ISSUE CATEGORY 4: UNDEFINED PARENT CLASSES

### CRITICAL: 100+ files have undefined parent classes

Many files claim to extend classes that don't exist or aren't imported:

#### Ksfraser\HTML Classes
Files in `src/Ksfraser/HTML/` extend parents not found:

| File | Parent Class | Status |
|------|--------------|--------|
| HtmlElement.php | `HtmlElement` | ❌ Not defined |
| HtmlSup.php | `HtmlFormatting` | ❌ Not defined |
| HtmlEmptyElement.php | `HtmlElement` | ❌ Not defined |
| HtmlScriptLanguage.php | `HtmlString` | ❌ Not defined |
| Javascript/HtmlJSString.php | `HtmlString` | ❌ Not defined |
| Json/HtmlJsonString.php | `HtmlScriptLanguage` | ❌ Not defined |
| Rows/InterestFreqTableRow.php | `BaseTableRow` | ❌ Not defined |
| Rows/LoanSummaryTableRow.php | `BaseTableRow` | ❌ Not defined |
| ScriptHandlers/*.php (multiple) | `BaseScriptHandler` | ❌ Not defined |

#### Ksfraser\PartnerTypes Classes
All PartnerType classes fail:

| File | Parent Class | Status |
|------|--------------|--------|
| AbstractPartnerType.php | `AbstractPartnerType` (self-extending!) | ❌ Circular / Undefined |
| BankTransferPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| CustomerPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| ManualSettlementPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| MatchedPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| QuickEntryPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| SupplierPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |
| UnknownPartnerType.php | `AbstractPartnerType` | ❌ Not imported/found |

#### Ksfraser\FaBankImport Model/View Classes
| File | Parent Class | Status |
|------|--------------|--------|
| model/BiLineItemModel.php | `GenericFaInterfaceModel` | ❌ Not imported/found |
| view/BiLineItemView.php | `Origin` | ❌ Not imported/found |

#### Application Controllers
| File | Parent Class | Status |
|------|--------------|--------|
| Application/controllers/AdminController.php | `AbstractController` | ❌ Not imported/found |

#### Test Files (Different issue - using TestCase)
| File | Parent Class | Status |
|------|--------------|--------|
| Application/tests/unit/AlertServiceTest.php | `TestCase` (PHPUnit) | ⚠️ Need import |
| Application/tests/integration/DatabaseTestCase.php | `TestCase` (PHPUnit) | ⚠️ Need import |
| Application/tests/unit/ContainerTest.php | `TestCase` (PHPUnit) | ⚠️ Need import |
| Application/tests/unit/MetricsAggregatorTest.php | `TestCase` (PHPUnit) | ⚠️ Need import |

---

## ISSUE CATEGORY 5: DTO IMPORT INCONSISTENCIES

### Expected Pattern
DTOs should be imported from: `Ksfraser\FaBankImport\Shared\DTOs\`

### Problem
Multiple autoload paths for DTOs cause confusion:
```
"Ksfraser\\FaBankImport\\DTO\\" => "src/Ksfraser/FaBankImport/DTO/"
"Ksfraser\\FaBankImport\\Shared\\DTOs\\" => (NOT MAPPED - should be app/Shared/DTOs/)
```

This creates TWO different DTO namespaces that can import from different locations!

### Verification Needed
- Check all files with DTO references to ensure they import from ONE consistent location
- Prefer: `Ksfraser\FaBankImport\Shared\DTOs\`
- Flag: Any files importing from `Ksfraser\FaBankImport\DTO\` (old pattern)

---

## ISSUE CATEGORY 6: NAMESPACE PATTERN VIOLATIONS

### Pattern Violations Detected

1. **KsfBankImport instead of Ksfraser\FaBankImport**
   ```
   Mapped: "KsfBankImport\\Services\\" -> "Services/"
   Mapped: "KsfBankImport\\OperationTypes\\" -> "OperationTypes/"
   ```
   These should NOT exist - wrong case pattern

2. **Ksfraser\Application (correct) but lowercase directories**
   ```
   "Ksfraser\\Application\\Models\\" -> "src/Ksfraser/Application/models/"
   "Ksfraser\\Application\\Interfaces\\" -> "src/Ksfraser/Application/interfaces/"
   ```
   Causes confusion with case-sensitive systems

3. **Invalid top-level Ksfraser\ namespace**
   ```
   "Ksfraser\\" -> "src/Ksfraser/"
   "Ksfraser\\FaBankImport\\" -> "src/Ksfraser/FaBankImport/"
   ```
   These overlap - overlapping PSR4 prefixes can cause resolution issues

---

## ISSUE CATEGORY 7: CIRCULAR AND SELF-REFERENTIAL NAMESPACES

### AbstractPartnerType Self-Extension

**File**: src/Ksfraser/PartnerTypes/AbstractPartnerType.php  
**Line**: 19  
**Code**: `class AbstractPartnerType extends AbstractPartnerType`  
**Problem**: Class extends itself (circular)

**Impact**: Cannot be instantiated, will cause fatal errors

---

## CRITICAL PATHS TO FIX (IN ORDER)

### 1. IMMEDIATE: Fix app/Shared/ autoload mapping

Add to composer.json:
```json
"Ksfraser\\FaBankImport\\Shared\\": "app/Shared/",
"Ksfraser\\FaBankImport\\Shared\\DTOs\\": "app/Shared/DTOs/",
"Ksfraser\\FaBankImport\\Shared\\Entities\\": "app/Shared/Entities/",
```

### 2. IMMEDIATE: Consolidate Service / Services directories

**Choose one**: Either keep Service/ OR services/ but not both
- Rename Service/ → services/ (to match autoload expectation)
- Update all namespace declarations to use `Services` (plural)

OR

- Update composer.json to map `Ksfraser\FaBankImport\Service\` → `src/Ksfraser/FaBankImport/Service/`
- Update all files in services/ to use Service (singular)

**Recommendation**: Use Service (singular, PascalCase) consistently

### 3. IMMEDIATE: Fix model and view directories

- Rename model/ → models/ OR model/ → Model/
- Rename view/ → views/ OR view/ → View/
- Ensure namespace and directory structure match exactly

### 4. HIGH PRIORITY: Fix undefined parent classes

- Locate and create missing parent classes (HtmlString, BaseTableRow, BaseScriptHandler, etc.)
- Add proper `use` statements to import parent classes
- Fix AbstractPartnerType self-extension

### 5. HIGH PRIORITY: Remove wrong namespace prefixes

- Update Views/ namespace in src/Views/TransactionView.php
- Remove KsfBankImport namespace paths from composer.json
- Use only Ksfraser\FaBankImport pattern

### 6. MEDIUM PRIORITY: Consolidate DTO locations

- Decide: Use Ksfraser\FaBankImport\Shared\DTOs\ ONLY
- Remove duplicate DTO namespace mappings
- Migrate any old DTO files if they exist

### 7. MEDIUM PRIORITY: Clean PSR4 mapping

Remove duplicate/overlapping PSR4 entries:
- Carefully review all entries for conflicts
- Test autoload with: `composer dump-autoload -v`

---

## VALIDATION STEPS

After fixing issues, run:

```bash
# Verify autoload config
composer validate

# Dump and check autoload
composer dump-autoload -v

# Run tests to validate no regressions
vendor/bin/phpunit

# Check for undefined classes
grep -r "extends\|implements" src/ | grep -v "use " | head -20
```

---

## FILES REQUIRING ATTENTION

### DO NOT AUTOLOAD (Immediate Risk)
- src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php
- src/Ksfraser/FaBankImport/Service/*.php (14 files)
- src/Ksfraser/FaBankImport/model/BiLineItemModel.php
- src/Ksfraser/FaBankImport/view/BiLineItemView.php

### NOT AUTOLOADING  
- app/Shared/DTOs/*.php (6 files)
- app/Shared/Entities/*.php (8 files)
- app/Shared/Repositories/*.php (1 file)
- app/Shared/Factories/*.php (1 file)
- app/Shared/ValueObjects/*.php (1 file)

### TOTAL FILES NOT PROPERLY LOADED
**37 files** cannot be autoloaded due to namespace/mapping mismatches

---

## RECOMMENDATIONS

### Short-term (This sprint)
1. Create new composer.json autoload mapping for app/Shared
2. Consolidate duplicate directories (Service/services, model/models, view/views)
3. Fix AbstractPartnerType self-extension
4. Run full test suite to identify runtime errors

### Medium-term (Next sprint)  
1. Locate and create all missing parent classes
2. Add proper use statements throughout codebase
3. Remove KsfBankImport namespace references
4. Consolidate DTO locations to single namespace

### Long-term (Architectural)
1. Consider moving from src/Ksfraser/ structure to standard PSR-4 structure
2. Evaluate whether Ksfraser\HTML should be a separate package
3. Clean up overlapping PSR4 mappings
4. Implement namespace-aware code generation for new code

