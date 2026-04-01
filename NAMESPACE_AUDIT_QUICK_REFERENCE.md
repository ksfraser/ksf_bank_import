# NAMESPACE AUDIT - QUICK REFERENCE SUMMARY

**Audit Date**: April 1, 2026  
**Total Issues Found**: 37 files not autoloading + 100+ classes with undefined parents  
**Critical Severity**: YES - Phase 0 Shared Kernel broken  

---

## 🔴 CRITICAL ISSUES AT A GLANCE

| Issue | Count | Files | Impact | Fix Time |
|-------|-------|-------|--------|----------|
| Service/ uses Service namespace, composer maps Services | 15 | Won't autoload | HIGH | 10 min |
| model/ not mapped in composer | 1 | Won't autoload | HIGH | 3 min |
| view/ not mapped in composer | 1 | Won't autoload | HIGH | 3 min |
| app/Shared/ not mapped in composer | 17 | **PHASE 0 BROKEN** | **CRITICAL** | 5 min |
| Undefined parent classes | 100+ | Runtime errors | HIGH | 30 min |
| AbstractPartnerType self-extends | 1 | Circular reference | CRITICAL | 5 min |
| Wrong namespace prefixes | 1 | Conflicts possible | MEDIUM | 5 min |

---

## 📋 FILES NOT AUTOLOADING (37 TOTAL)

### Service Directory (15 files)
```
src/Ksfraser/FaBankImport/Service/                    ← Wrong location/namespace
  - accountMappingResolver.php
  - BankImportLogger.php
  - BankImportPathResolver.php
  - etc... (12 more)
  - Schema/BankImportModuleSchemaService.php
```
**Fix**: Move to `services/` and update namespace from `Service` to `Services`

### Model Directory (1 file)
```
src/Ksfraser/FaBankImport/model/BiLineItemModel.php
```
**Fix**: Move to `models/` and update namespace from `Model` to `Models`

### View Directory (1 file)
```
src/Ksfraser/FaBankImport/view/BiLineItemView.php
```
**Fix**: Move to `views/` and update namespace from `View` to `Views`

### app/Shared Directory (17 files) ⚠️ PHASE 0 KERNEL BROKEN
```
app/Shared/DTOs/                 (6 files) - Not accessible
app/Shared/Entities/             (8 files) - Not accessible
app/Shared/Repositories/         (1 file)  - Not accessible
app/Shared/Factories/            (1 file)  - Not accessible
app/Shared/ValueObjects/         (1 file)  - Not accessible
```
**Fix**: Add PSR4 mapping: `"Ksfraser\\FaBankImport\\Shared\\"` → `"app/Shared/"`

---

## 🚨 UNDEFINED PARENT CLASSES

### Self-Extending (Circular)
- `AbstractPartnerType extends AbstractPartnerType` ← **FIX IMMEDIATELY**

### Missing Base Classes
- HtmlString (referenced by 7 files)
- BaseTableRow (referenced by 3 files)
- BaseScriptHandler (referenced by 4 files)
- HtmlElement (referenced by 2 files)
- HtmlFormatting (referenced by 1 file)

### Missing Imports
- AbstractPartnerType (8 files in PartnerTypes/)
- TestCase (4 test files) - Missing `use PHPUnit\Framework\TestCase;`
- GenericFaInterfaceModel (1 file)
- Origin (1 file)
- AbstractController (1 file)

---

## ✅ QUICK FIX CHECKLIST

### 5-Minute Fixes
- [ ] Add app/Shared/ to composer.json PSR4 mapping
  ```bash
  # Add these lines to composer.json under psr-4:
  "Ksfraser\\FaBankImport\\Shared\\": "app/Shared/",
  ```
- [ ] Run `composer dump-autoload`

### 20-Minute Fixes  
- [ ] Move Service/ files to services/ directory
- [ ] Move model/BiLineItemModel.php to models/
- [ ] Move view/BiLineItemView.php to views/
- [ ] Update all moved file namespaces
- [ ] Search/replace old namespace imports

### 40-Minute Fixes
- [ ] Fix AbstractPartnerType self-reference
- [ ] Add use statements to all PartnerType files
- [ ] Add TestCase import to 4 test files
- [ ] Create/import missing HTML base classes
- [ ] Fix ApplicationController import

### 30-Minute Verification
- [ ] Run `composer validate`
- [ ] Run `composer dump-autoload -v`
- [ ] Run `vendor/bin/phpunit`
- [ ] Verify no remaining undefined classes

---

## 📄 DETAILED AUDIT DOCUMENTS

| Document | Purpose | Audience |
|----------|---------|----------|
| NAMESPACE_AND_IMPORT_AUDIT.md | Executive summary, all issue categories | Project leads, architects |
| NAMESPACE_AUDIT_DETAILED_FILES.md | Line-by-line file mappings with specific fixes | Developers doing the work |
| NAMESPACE_AUDIT_ACTION_PLAN.md | Step-by-step implementation with commands | Developers, DevOps |
| namespace-audit-results.json | Full JSON output from PHP audit script | Automation, CI/CD |
| audit-namespaces.php | Reusable PHP audit script | Future audits |

---

## 🎯 PRIORITY ORDER TO FIX

### DO THIS FIRST (Unblocks Phase 0)
```bash
# 1. Add to composer.json - 5 minutes
"Ksfraser\\FaBankImport\\Shared\\": "app/Shared/",

# 2. Dump autoload
composer dump-autoload

# 3. Verify works
composer validate
```

### THEN DO THIS (Fix main issues - 20 minutes)
```bash
# 1. Move directories
mv src/Ksfraser/FaBankImport/Service/* src/Ksfraser/FaBankImport/services/
mv src/Ksfraser/FaBankImport/model/* src/Ksfraser/FaBankImport/models/
mv src/Ksfraser/FaBankImport/view/* src/Ksfraser/FaBankImport/views/

# 2. Update namespaces in 17 files
# Search: Ksfraser\FaBankImport\Service
# Replace: Ksfraser\FaBankImport\Services

# Search: Ksfraser\FaBankImport\Model
# Replace: Ksfraser\FaBankImport\Models

# Search: Ksfraser\FaBankImport\View
# Replace: Ksfraser\FaBankImport\Views
```

### THEN DO THIS (Fix parent classes - 40 minutes)
```bash
# 1. Fix AbstractPartnerType self-reference
# Edit: src/Ksfraser/PartnerTypes/AbstractPartnerType.php
# Change: class AbstractPartnerType extends AbstractPartnerType
# To: class AbstractPartnerType

# 2. Add to all 8 PartnerType files:
use Ksfraser\PartnerTypes\AbstractPartnerType;

# 3. Add to 4 test files:
use PHPUnit\Framework\TestCase;

# 4. Create missing base classes or add imports
```

### FINALLY DO THIS (Verify - 30 minutes)
```bash
composer validate
composer dump-autoload -v
vendor/bin/phpunit
```

---

## 🔍 HOW TO VERIFY FIX

```bash
# 1. Check Service files now use Services
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Services" src/ --include="*.php" | wc -l
# Should show: 15

# 2. Check app/Shared files accessible  
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Shared" app/ --include="*.php" | wc -l
# Should show: 17

# 3. Check no old Service namespace remains
grep -r "namespace Ksfraser\\\\FaBankImport\\\\Service" src/ --include="*.php" | wc -l
# Should show: 0

# 4. Check no self-extending classes
grep -r "extends AbstractPartnerType" src/Ksfraser/PartnerTypes/AbstractPartnerType.php
# Should show: nothing (no match)

# 5. Run tests
vendor/bin/phpunit
# Should pass or have same pass rate as before
```

---

## 💾 BACKUP BEFORE STARTING

```bash
# Create timestamped backup
mkdir -p "backups/$(date +%Y%m%d_%H%M%S)"
cp -r src/ "backups/$(date +%Y%m%d_%H%M%S)/"
cp composer.json "backups/$(date +%Y%m%d_%H%M%S)/"
git add -A && git commit -m "backup: before namespace audit fixes"
```

---

## 📞 IF SOMETHING BREAKS

```bash
# Restore from git
git checkout .

# Or restore from backup
cp -r "backups/BACKUP_DATE/src/" src/
cp "backups/BACKUP_DATE/composer.json" composer.json

# Rebuild autoload
composer dump-autoload
```

---

## 📊 IMPACT SUMMARY

| Metric | Before Fix | After Fix |
|--------|-----------|-----------|
| Files that won't autoload | 37 | 0 |
| Undefined parent classes | 100+ | 0 (or properly handled) |
| Phase 0 Shared Kernel accessible | ❌ NO | ✅ YES |
| Tests can run | ⚠️ May fail | ✅ Should pass |
| Codebase health | 🔴 Poor | 🟢 Good |

---

## 📝 NOTES FOR NEXT AUDIT

This audit was generated by PHP script `audit-namespaces.php` on April 1, 2026.

**To re-run audit**: `php audit-namespaces.php`

**To verify fixes worked,** run same script and check for:
- Fewer CRITICAL issues
- All app/Shared files accessible
- No undefined parent classes for main code

**Known limitations**:
- Script cannot detect imports at declaration time
- Some PHP classes (like TestCase) are external
- Script assumes standard PSR-4 patterns

---

## 🎓 KEY LESSONS

1. **Autoload mappings must match actual namespaces** - Case-sensitive on Linux!
2. **app/ root directory needs explicit mapping** - Not automatically included
3. **Duplicate directories with different case are dangerous** - Choose ONE
4. **Missing parent classes will only fail at runtime** - Need comprehensive audit
5. **Test files need explicit imports** - PHPUnit TestCase not auto-discovered

---

