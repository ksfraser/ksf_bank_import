# Menu Integration for Validate GL Entries

**Date:** October 18, 2025  
**Feature:** Mantis #2713 - Validate GL Entries  
**Status:** ✅ Complete

## Changes Made

### 1. hooks.php - Added to FrontAccounting Menu System

**File:** `hooks.php`  
**Line:** 37 (after "Bank Statements Inquiry")

**Code Added:**
```php
$app->add_lapp_function(3, _("Validate GL Entries"),
    $path_to_root."/modules/".$this->module_name."/validate_gl_entries.php", 'SA_BANKTRANSVIEW', MENU_INQUIRY);
```

**Details:**
- **Menu Level:** 3 (same as other bank import functions)
- **Label:** "Validate GL Entries"
- **Path:** `/modules/bank_import/validate_gl_entries.php`
- **Security:** `SA_BANKTRANSVIEW` (Bank Transaction View permission)
- **Menu Section:** `MENU_INQUIRY` (appears under GL → Inquiry menu)

**Result:**
The validation page now appears in the FrontAccounting main menu under:
```
GL → Inquiry → Validate GL Entries
```

---

### 2. views/module_menu_view.php - Added to Module Navigation

**File:** `views/module_menu_view.php`  
**Line:** 12 (added as 5th menu item)

**Code Added:**
```php
echo '<li><a href="validate_gl_entries.php">Validate GL Entries</a></li>';
```

**Result:**
The module navigation menu now shows:
- Process Statements
- Import Statements
- Manage Partners Data
- View Statements
- **Validate GL Entries** ✨ NEW

This menu appears at the top of all bank import module pages.

---

### 3. src/Ksfraser/FaBankImport/views/module_menu_view.php - Consistency Update

**File:** `src/Ksfraser/FaBankImport/views/module_menu_view.php`  
**Line:** 12 (added as 5th menu item)

**Code Added:**
```php
echo '<li><a href="validate_gl_entries.php">Validate GL Entries</a></li>';
```

**Reason:**
Keep the namespaced version in sync with the root version for consistency.

---

### 4. validate_gl_entries.php - Added Module Menu

**File:** `validate_gl_entries.php`  
**Lines:** 26-29 (after page() call)

**Code Added:**
```php
// Display module menu
include_once "views/module_menu_view.php";
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();
```

**Result:**
The validation page now displays the module navigation menu at the top, allowing users to easily switch between different bank import functions.

---

## Menu Hierarchy

### FrontAccounting Main Menu
```
General Ledger (GL)
├── Transactions
│   └── Process Bank Statements
├── Maintenance
│   ├── Manage Partners Bank Accounts
│   └── Import Bank Statements
└── Inquiry
    ├── Bank Statements Inquiry
    └── Validate GL Entries ✨ NEW
```

### Module Navigation Menu
```
┌─────────────────────────────────────────────────────────┐
│ [Process Statements] [Import Statements]                │
│ [Manage Partners Data] [View Statements]                │
│ [Validate GL Entries] ✨ NEW                            │
└─────────────────────────────────────────────────────────┘
```

---

## Security Permissions

**Permission Required:** `SA_BANKTRANSVIEW`

**Why this permission?**
- Same as viewing bank transactions
- Users who can view transactions should be able to validate them
- More restrictive than `SA_BANKACCOUNT` (which allows full management)
- Appropriate for inquiry/validation function

**Access Granted To:**
- Bank reconciliation staff
- Accounting managers
- Auditors with bank transaction view rights

---

## Testing the Menu Integration

### Test 1: Main Menu Navigation
1. Log into FrontAccounting
2. Click on **GL** (General Ledger)
3. Navigate to **Inquiry** submenu
4. Click **Validate GL Entries**
5. ✅ Page should load

### Test 2: Module Menu Navigation
1. Visit any bank import page (e.g., `import_statements.php`)
2. Look for the module navigation menu at the top
3. Click **Validate GL Entries**
4. ✅ Should navigate to validation page

### Test 3: Direct Access
1. Navigate directly to `/modules/bank_import/validate_gl_entries.php`
2. ✅ Page should load with module menu visible

### Test 4: Security Check
1. Log in as user WITHOUT `SA_BANKTRANSVIEW` permission
2. Try to access validation page
3. ✅ Should be denied access with security message

---

## Files Modified Summary

| File | Lines Changed | Purpose |
|------|---------------|---------|
| `hooks.php` | +2 | Add to FA main menu |
| `views/module_menu_view.php` | +1 | Add to module navigation |
| `src/Ksfraser/FaBankImport/views/module_menu_view.php` | +1 | Keep src/ in sync |
| `validate_gl_entries.php` | +4 | Display module menu |

**Total:** 4 files modified, 8 lines added

---

## Reverting Changes (If Needed)

If you need to remove the menu items:

```bash
# Revert hooks.php
git checkout hooks.php

# Revert module menu views
git checkout views/module_menu_view.php
git checkout src/Ksfraser/FaBankImport/views/module_menu_view.php

# Remove menu from validation page
# Edit validate_gl_entries.php and remove lines 26-29
```

Or manually comment out the lines:

**In hooks.php:**
```php
// $app->add_lapp_function(3, _("Validate GL Entries"),
//     $path_to_root."/modules/".$this->module_name."/validate_gl_entries.php", 'SA_BANKTRANSVIEW', MENU_INQUIRY);
```

**In module_menu_view.php:**
```php
// echo '<li><a href="validate_gl_entries.php">Validate GL Entries</a></li>';
```

---

## Next Steps

1. ✅ **Commit Changes**
   ```bash
   git add hooks.php views/module_menu_view.php src/Ksfraser/FaBankImport/views/module_menu_view.php validate_gl_entries.php
   git commit -m "Add Validate GL Entries to menu system (Mantis #2713)"
   ```

2. ✅ **Deploy to Server**
   - Upload modified files
   - Clear FrontAccounting cache if needed
   - Test menu access

3. ✅ **User Training**
   - Inform users of new menu item
   - Provide quick reference guide
   - Schedule training session if needed

4. ✅ **Documentation**
   - Update user manual
   - Add to module documentation
   - Create video tutorial (optional)

---

## Related Documentation

- **Feature Documentation:** `docs/MANTIS_2713_VALIDATION.md`
- **Quick Summary:** `MANTIS_2713_SUMMARY.md`
- **Test Script:** `tests/test_validation.php`

---

## Summary

✅ **Menu integration complete!**

The "Validate GL Entries" page is now accessible from:
1. **Main FrontAccounting menu:** GL → Inquiry → Validate GL Entries
2. **Module navigation menu:** Available on all bank import pages
3. **Direct URL:** `/modules/bank_import/validate_gl_entries.php`

All changes maintain consistency with existing menu structure and security model.
