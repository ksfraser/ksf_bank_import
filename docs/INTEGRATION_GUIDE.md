# Integration Guide: process_statements.php

## Quick Start (Option A: Include Bootstrap)

This is the **easiest** method. Simply include the bootstrap file in process_statements.php.

### Step 1: Add Include Statement

Find line ~30 in `process_statements.php` (after includes, before HTML) and add:

```php
// Include Command Pattern bootstrap
require_once(__DIR__ . '/../../src/Ksfraser/FaBankImport/command_bootstrap.php');
```

### Step 2: Remove Old POST Handling

**Comment out or delete lines 100-130** (the old procedural POST handling):

```php
/*
// OLD CODE - COMMENTED OUT
if (isset($_POST['UnsetTrans'])) {
    $bi_controller->unsetTrans();
    $Ajax->activate('doc_tbl');
}

if (isset($_POST['AddCustomer'])) {
    $bi_controller->addCustomer();
    $Ajax->activate('doc_tbl');
}

if (isset($_POST['AddVendor'])) {
    $bi_controller->addVendor();
    $Ajax->activate('doc_tbl');
}

if (isset($_POST['ToggleTransaction'])) {
    $bi_controller->toggleDebitCredit();
    $Ajax->activate('doc_tbl');
}
*/
```

### Step 3: Test

The bootstrap file handles everything automatically! Test each button:

- [x] Click "Unset Transaction" - should work
- [x] Click "Add Customer" - should work
- [x] Click "Add Vendor" - should work  
- [x] Click "Toggle D/C" - should work

---

## Advanced Integration (Option B: Manual Setup)

For more control, set up manually:

### Step 1: Add Autoload (Top of file, line ~10)

```php
// Enable autoloading for Command Pattern classes
require_once(__DIR__ . '/../../vendor/autoload.php');
```

### Step 2: Initialize Container (line ~30, after includes)

```php
use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Commands\CommandDispatcher;

// Initialize DI Container
$container = new SimpleContainer();
$container->instance('TransactionRepository', $bi_transactions_model);

// Initialize Command Dispatcher
$commandDispatcher = new CommandDispatcher($container);
```

### Step 3: Replace POST Handling (lines 100-130)

**Replace the old code with:**

```php
// Handle POST actions using Command Pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
    
    foreach ($actions as $action) {
        if (isset($_POST[$action])) {
            $result = $commandDispatcher->dispatch($action, $_POST);
            $result->display();
            
            if ($Ajax) {
                $Ajax->activate('doc_tbl');
            }
            break; // Only one action per request
        }
    }
}
```

---

## Backward Compatibility (Option C: Feature Flag)

Keep both old and new code, toggle with feature flag:

### Step 1: Define Feature Flag (config file or top of process_statements.php)

```php
// Feature flag - set to false to revert to old code
define('USE_COMMAND_PATTERN', true);
```

### Step 2: Include Bootstrap (it checks the flag automatically)

```php
require_once(__DIR__ . '/../../src/Ksfraser/FaBankImport/command_bootstrap.php');
```

### Step 3: Keep Old Code (for safety)

Don't delete the old code initially - the bootstrap file will choose which to use based on the flag.

### Rollback Plan

If something goes wrong:

1. Set `USE_COMMAND_PATTERN` to `false`
2. System reverts to old code immediately
3. No data loss, no downtime

---

## Testing Checklist

After integration, test all POST actions:

### Unset Transaction
- [ ] Click "Unset" on a single transaction
- [ ] Verify green notification: "Disassociated 1 transaction"
- [ ] Verify transaction is unset in database
- [ ] Select multiple transactions, click "Unset"
- [ ] Verify notification: "Disassociated X transactions"

### Add Customer
- [ ] Select a transaction without customer
- [ ] Click "Add Customer"
- [ ] Verify green notification: "Created 1 customer"
- [ ] Verify customer appears in FrontAccounting
- [ ] Try with multiple transactions
- [ ] Try with transaction that already has customer (should error)

### Add Vendor
- [ ] Select a transaction without vendor
- [ ] Click "Add Vendor"
- [ ] Verify green notification: "Created 1 vendor"
- [ ] Verify vendor appears in FrontAccounting
- [ ] Try with multiple transactions
- [ ] Try with transaction that already has vendor (should error)

### Toggle Debit/Credit
- [ ] Select a debit transaction
- [ ] Click "Toggle D/C"
- [ ] Verify it changes to credit
- [ ] Click again
- [ ] Verify it changes back to debit
- [ ] Try with multiple transactions

### Error Scenarios
- [ ] Try actions with no transactions selected (should show error)
- [ ] Try creating customer for non-existent transaction
- [ ] Verify all errors show red banners
- [ ] Verify partial success shows yellow warnings

---

## Troubleshooting

### Issue: "Class not found" errors

**Solution**: Ensure autoloader is loaded:
```php
require_once(__DIR__ . '/../../vendor/autoload.php');
```

Run: `composer dump-autoload`

### Issue: Container errors

**Solution**: Verify container is initialized before CommandDispatcher:
```php
$container = new SimpleContainer();
$container->instance('TransactionRepository', $bi_transactions_model);
$commandDispatcher = new CommandDispatcher($container);
```

### Issue: POST actions not working

**Solution**: 
1. Check `USE_COMMAND_PATTERN` is `true`
2. Verify bootstrap file is included
3. Check PHP error log for exceptions

### Issue: Performance problems

**Solution**: Container uses singletons by default, so no performance impact.
If issues persist, check database query log.

### Issue: Want to revert to old code

**Solution**: Set `USE_COMMAND_PATTERN = false` or remove bootstrap include.

---

## Migration Timeline

### Week 1: Parallel Testing
- ✅ Include bootstrap with `USE_COMMAND_PATTERN = true`
- ✅ Keep old code in place (commented)
- ✅ Test extensively

### Week 2: Monitoring
- ✅ Monitor error logs
- ✅ Gather user feedback
- ✅ Fix any issues

### Week 3: Commit
- ✅ Remove old POST handling code
- ✅ Remove feature flag
- ✅ Remove commented code

### Week 4: Cleanup
- ✅ Delete deprecated bi_controller methods
- ✅ Update documentation
- ✅ Close ticket

---

## Files Modified

When integrating, you'll modify:

### Must Modify
- `src/Ksfraser/FaBankImport/process_statements.php` - Add include, remove old POST handling

### May Modify (Optional)
- Configuration file - Add `USE_COMMAND_PATTERN` constant

### No Modifications Needed
- All Command classes - Already complete
- CommandDispatcher - Already complete
- SimpleContainer - Already complete
- UI/HTML - Buttons work as-is, no changes needed

---

## Success Criteria

✅ All POST actions work correctly  
✅ No PHP errors or warnings  
✅ UI buttons function identically to before  
✅ Notifications display correctly (green/red/yellow)  
✅ Ajax refresh works  
✅ Can toggle feature flag on/off  
✅ Performance is equal or better  

---

## Support

If you encounter issues:

1. Check `docs/POST_REFACTOR_SUMMARY.md` for details
2. Review `docs/REFACTORING_EXAMPLES.php` for code examples
3. Check unit tests for expected behavior
4. Enable PHP error logging and check logs

---

## Example: Complete Integration

Here's what the integrated code looks like in `process_statements.php`:

```php
<?php
// ... existing includes ...

// ============================================================================
// COMMAND PATTERN SETUP (NEW - Oct 21, 2025)
// ============================================================================

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../src/Ksfraser/FaBankImport/command_bootstrap.php');

// That's it! The bootstrap handles everything else.
// Old POST handling (lines 100-130) can be deleted or commented out.

// ... rest of file continues as normal ...
```

The bootstrap automatically:
- ✅ Creates DI container
- ✅ Initializes CommandDispatcher
- ✅ Registers all commands
- ✅ Handles POST requests
- ✅ Displays results
- ✅ Refreshes Ajax components

**No other changes needed!**

---

**Status**: Ready for integration  
**Recommended approach**: Option A (Include Bootstrap)  
**Estimated time**: 15 minutes  
**Risk level**: Low (feature flag provides instant rollback)
