# Directory Consolidation Analysis

**Generated**: November 6, 2025  
**Analysis**: Canonical Source Identification  
**Decision**: src/Ksfraser/HTML/ is AUTHORITATIVE

---

## Key Findings

### File Counts
- **src/Ksfraser/HTML/**: 99 files (CANONICAL - most recent)
- **views/HTML/**: 129 files (30 extra files - needs review)
- **src/Ksfraser/FaBankImport/views/HTML/**: 140 files (41 extra files - legacy copy)

### Most Recent File Dates

**src/Ksfraser/HTML/ (WINNER ✅)**
```
HtmlInputButton.php     Oct 25, 2025  11:02 PM  ⭐ MOST RECENT
HtmlSelect.php          Oct 25, 2025   8:45 PM
HtmlOption.php          Oct 25, 2025   8:44 PM  
HtmlFragment.php        Oct 25, 2025   8:15 PM
HtmlInput.php           Oct 25, 2025   3:33 PM
```

**views/HTML/**
```
HtmlInput.php                   Oct 25, 2025   3:33 PM
HTML_ROW_LABELDecorator.php     Oct 24, 2025   9:27 PM
HtmlUnorderedList.php           Oct 24, 2025   9:27 PM
```

**src/Ksfraser/FaBankImport/views/HTML/**
```
HTML_ROW_LABELDecorator.php     Oct 24, 2025   9:27 PM
HtmlUnorderedList.php           Oct 24, 2025   9:27 PM
```

### Analysis

1. **src/Ksfraser/HTML/** is clearly the **CANONICAL** source
   - Most recent modifications (Oct 25, 2025)
   - Active development happening here
   - Proper namespace structure (`Ksfraser\HTML\`)
   - Intended git submodule location

2. **views/HTML/** has 30 extra files
   - Some files are unique (not in src/)
   - Some are older versions/backups (files with `~` suffix)
   - Need to review each unique file for value

3. **src/Ksfraser/FaBankImport/views/HTML/** appears to be legacy copy
   - 41 extra files
   - Older dates (Oct 24 vs Oct 25)
   - Wrong namespace structure
   - Should be deleted after migration

---

## Decision Matrix

| Criterion | src/Ksfraser/HTML/ | views/HTML/ | src/Ksfraser/FaBankImport/views/HTML/ |
|-----------|-------------------|-------------|----------------------------------------|
| Most Recent | ✅ Oct 25, 2025 | ⚠️ Oct 24, 2025 | ⚠️ Oct 24, 2025 |
| Proper Namespace | ✅ `Ksfraser\HTML\` | ❌ No namespace | ❌ Wrong namespace |
| Intended Location | ✅ Git submodule path | ❌ Temporary | ❌ Legacy |
| Active Development | ✅ Yes | ⚠️ Some | ❌ No |
| File Count | 99 (clean) | 129 (bloated) | 140 (bloated) |
| **DECISION** | **KEEP AS CANONICAL** | **MIGRATE UNIQUE → DELETE** | **DELETE AFTER REVIEW** |

---

## Migration Strategy

### Phase 1: Identify Unique Files in views/HTML/

Files in `views/HTML/` that DON'T exist in `src/Ksfraser/HTML/`:

**Need to check these 30 files:**
1. Backup files (`~` suffix) - DELETE
2. Unique valuable classes - MIGRATE
3. Deprecated/obsolete - DELETE

**Command to find unique files:**
```powershell
$src = Get-ChildItem -Path "src\Ksfraser\HTML" -Recurse -Filter "Html*.php" | 
    Select-Object -ExpandProperty Name
$views = Get-ChildItem -Path "views\HTML" -Filter "Html*.php" | 
    Select-Object -ExpandProperty Name
Compare-Object $src $views | Where-Object {$_.SideIndicator -eq '=>'}
```

### Phase 2: Identify Unique Files in src/Ksfraser/FaBankImport/views/HTML/

**Need to check these 41 files:**
- Likely all duplicates or obsolete
- Review for any unique functionality
- Delete directory after verification

### Phase 3: Update require_once Statements

**Search for:**
```regex
require_once.*views/HTML/Html.*\.php
require_once.*FaBankImport/views/HTML/Html.*\.php
```

**Replace with:**
```php
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlXxx.php' );
// OR use composer autoloading:
use Ksfraser\HTML\Elements\HtmlXxx;
```

**Files likely need updates:**
- class.bi_lineitem.php
- class.ViewBiLineItems.php
- class.transactions_table.php
- All View classes in Views/ directory
- Any test files

### Phase 4: Verify No Breakage

**Before deletion:**
1. ✅ All tests pass
2. ✅ All require_once updated
3. ✅ grep search shows no references to old paths
4. ✅ Manual testing of 5+ key pages
5. ✅ Git branch created for rollback

**Test command:**
```powershell
vendor\bin\phpunit --testdox
```

**Grep verification:**
```powershell
# Should return NO results:
grep -r "views/HTML/Html" --include="*.php" .
grep -r "FaBankImport/views/HTML/Html" --include="*.php" .
```

### Phase 5: Delete Duplicate Directories

**Safety first:**
```powershell
# Create backup branch
git checkout -b consolidate-html-pre-delete
git add -A
git commit -m "Before deleting duplicate HTML directories"

# Delete duplicates
Remove-Item -Path "views\HTML" -Recurse -Force
Remove-Item -Path "src\Ksfraser\FaBankImport\views\HTML" -Recurse -Force

# Test everything
vendor\bin\phpunit --testdox

# If all good:
git add -A
git commit -m "Consolidated HTML classes into src/Ksfraser/HTML/

- Deleted views/HTML/ (129 files)
- Deleted src/Ksfraser/FaBankImport/views/HTML/ (140 files)  
- Updated all require_once paths
- All tests passing
- Single source of truth: src/Ksfraser/HTML/"
```

---

## Risk Assessment

### LOW RISK ✅
- **src/Ksfraser/HTML/** is clearly the canonical source
- Most recent files
- Active development
- Proper structure

### MEDIUM RISK ⚠️
- **views/HTML/** may have 30 unique files worth keeping
- Need careful review before deletion
- Some files might be in use

### HIGH RISK IF NOT CAREFUL ⚠️
- Breaking existing functionality
- **Mitigation**: Git branch for rollback
- **Mitigation**: Comprehensive testing
- **Mitigation**: Grep verification

---

## Timeline

**Total Estimate**: 6-8 hours

1. **Identify unique files** (1 hour)
   - Run comparison scripts
   - Review each unique file
   - Decide keep/migrate/delete

2. **Migrate valuable files** (1-2 hours)
   - Copy to src/Ksfraser/HTML/
   - Update namespaces
   - Create/update tests

3. **Update all require_once** (2-3 hours)
   - Search all files
   - Update paths
   - Test each file

4. **Comprehensive testing** (1-2 hours)
   - Run full test suite
   - Manual testing
   - Verify no breakage

5. **Delete and commit** (0.5 hours)
   - Create backup branch
   - Delete directories
   - Final test
   - Commit

---

## Next Steps

1. ✅ Decision made: src/Ksfraser/HTML/ is canonical
2. ⏳ Run script to identify 30 unique files in views/HTML/
3. ⏳ Review unique files for value
4. ⏳ Migrate worthy files to src/Ksfraser/HTML/
5. ⏳ Update all require_once statements
6. ⏳ Run full test suite
7. ⏳ Delete duplicate directories
8. ⏳ Commit changes

**Status**: ✅ DECISION COMPLETE - Ready for Phase 2 (Migration)
