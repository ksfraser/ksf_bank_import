---
name: QE Configurable Transaction Reference Account
about: Make Quick Entry transaction reference logging configurable
title: '[Enhancement] Configurable Transaction Reference Account for Quick Entry Handler'
labels: enhancement, quick-entry, configuration
assignees: ''
---

## Summary
Currently, the Quick Entry handler logs transaction references to hardcoded GL account `0000`. This should be configurable via module settings to allow:
1. Enabling/disabling transaction reference logging
2. Specifying which GL account to use

## Background

### Current Implementation
**File**: `src/Ksfraser/FaBankImport/Handlers/QuickEntryTransactionHandler.php`  
**Lines**: 193-194

```php
// Add transaction reference tracking entries (offset to 0)
$transCode = $transaction['transactionCode'] ?? 'N/A';
$cart->add_gl_item('0000', 0, 0, 0.01, 'TransRef::' . $transCode, "Trans Ref");
$cart->add_gl_item('0000', 0, 0, -0.01, 'TransRef::' . $transCode, "Trans Ref");
```

### Original TODO
From `process_statements.php` (old switch statement, lines 366-368):
```php
//TODO:
//    Config which account to log these in
//    Conig whether to log these.
```

## Requirements

### Functional Requirements
1. **Module Configuration Options**:
   - `bank_import_trans_ref_logging` - Boolean to enable/disable (default: true)
   - `bank_import_trans_ref_account` - String for GL account code (default: '0000')

2. **Configuration UI**:
   - Add settings page or section in Bank Import module
   - Account selector with validation (must exist in GL accounts)
   - Enable/disable checkbox

3. **Handler Update**:
   - Read configuration on each transaction
   - Skip logging if disabled
   - Use configured account code if enabled

### Technical Requirements
1. Use FrontAccounting's company preferences system (`get_company_pref()`, `set_company_pref()`)
2. Validate GL account exists before using
3. Maintain backward compatibility (default to current behavior)
4. Add unit tests for configuration scenarios

## Proposed Implementation

### Step 1: Create Configuration Class
**File**: `src/Ksfraser/FaBankImport/Config/BankImportConfig.php`

```php
<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Config;

class BankImportConfig
{
    /**
     * Check if transaction reference logging is enabled
     */
    public static function getTransRefLoggingEnabled(): bool
    {
        $value = get_company_pref('bank_import_trans_ref_logging');
        return $value !== null ? (bool)$value : true; // Default: enabled
    }

    /**
     * Get GL account code for transaction reference logging
     */
    public static function getTransRefAccount(): string
    {
        return get_company_pref('bank_import_trans_ref_account') ?? '0000';
    }

    /**
     * Set transaction reference logging enabled/disabled
     */
    public static function setTransRefLoggingEnabled(bool $enabled): void
    {
        set_company_pref('bank_import_trans_ref_logging', $enabled ? 1 : 0);
    }

    /**
     * Set GL account code for transaction reference logging
     * 
     * @throws InvalidArgumentException if account doesn't exist
     */
    public static function setTransRefAccount(string $accountCode): void
    {
        // Validate account exists
        if (!self::glAccountExists($accountCode)) {
            throw new \InvalidArgumentException(
                "GL account '{$accountCode}' does not exist"
            );
        }
        set_company_pref('bank_import_trans_ref_account', $accountCode);
    }

    /**
     * Check if a GL account exists
     */
    private static function glAccountExists(string $accountCode): bool
    {
        $sql = "SELECT COUNT(*) as count FROM " . TB_PREF . "chart_master 
                WHERE account_code = " . db_escape($accountCode);
        $result = db_query($sql, "Failed to check GL account");
        $row = db_fetch($result);
        return $row['count'] > 0;
    }
}
```

### Step 2: Update QuickEntryTransactionHandler
**File**: `src/Ksfraser/FaBankImport/Handlers/QuickEntryTransactionHandler.php`  
**Lines**: 193-194

**BEFORE**:
```php
$cart->add_gl_item('0000', 0, 0, 0.01, 'TransRef::' . $transCode, "Trans Ref");
$cart->add_gl_item('0000', 0, 0, -0.01, 'TransRef::' . $transCode, "Trans Ref");
```

**AFTER**:
```php
use Ksfraser\FaBankImport\Config\BankImportConfig;

// ... in processQuickEntry() method ...

// Add transaction reference tracking if enabled
if (BankImportConfig::getTransRefLoggingEnabled()) {
    $refAccount = BankImportConfig::getTransRefAccount();
    $cart->add_gl_item(
        $refAccount, 0, 0, 0.01, 
        'TransRef::' . $transCode, 
        "Trans Ref"
    );
    $cart->add_gl_item(
        $refAccount, 0, 0, -0.01, 
        'TransRef::' . $transCode, 
        "Trans Ref"
    );
}
```

### Step 3: Create Configuration UI
**File**: `modules/bank_import/bank_import_settings.php` (new)

```php
<?php
$path_to_root = "../..";
$page_security = 'SA_BANKTRANSVIEW';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui/ui_input.inc");

use Ksfraser\FaBankImport\Config\BankImportConfig;

page(_("Bank Import Settings"));

if (isset($_POST['save_settings'])) {
    try {
        BankImportConfig::setTransRefLoggingEnabled(
            isset($_POST['trans_ref_logging'])
        );
        
        if (!empty($_POST['trans_ref_account'])) {
            BankImportConfig::setTransRefAccount($_POST['trans_ref_account']);
        }
        
        display_notification(_("Settings saved successfully"));
    } catch (Exception $e) {
        display_error($e->getMessage());
    }
}

start_form();

start_table(TABLESTYLE2);

// Section: Transaction Reference Logging
table_section_title(_("Transaction Reference Logging"));

// Enable/Disable
check_row(
    _("Enable Transaction Reference Logging:"),
    'trans_ref_logging',
    BankImportConfig::getTransRefLoggingEnabled()
);

// GL Account
gl_all_accounts_list_row(
    _("Transaction Reference GL Account:"),
    'trans_ref_account',
    BankImportConfig::getTransRefAccount(),
    false, // skip bank accounts
    false, // skip groups
    true   // allow selection
);

end_table(1);

submit_center('save_settings', _("Save Settings"), true, '', 'default');

end_form();
end_page();
?>
```

### Step 4: Add Menu Item
**File**: `modules/bank_import/hooks.php`

Add to module menu:
```php
function bank_import_install_tabs($app) {
    $app->add_application(
        new application(_("Banking"), "GL", _("General Ledger"))
    );
    
    $app->add_module(
        _("Bank Import"),
        _("Transactions"),
        $path . "/bank_import/"
    );
    
    $app->add_lappfunction(1, _("Import Statements"), 
        $path . "/bank_import/import_statements.php");
    $app->add_lappfunction(1, _("Process Statements"), 
        $path . "/bank_import/process_statements.php");
    $app->add_lappfunction(1, _("Settings"), 
        $path . "/bank_import/bank_import_settings.php"); // ← NEW
}
```

## Testing Plan

### Unit Tests
**File**: `tests/unit/Config/BankImportConfigTest.php`

```php
<?php
namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Config\BankImportConfig;

class BankImportConfigTest extends TestCase
{
    public function test_default_trans_ref_logging_is_enabled()
    {
        $this->assertTrue(BankImportConfig::getTransRefLoggingEnabled());
    }

    public function test_default_trans_ref_account_is_0000()
    {
        $this->assertEquals('0000', BankImportConfig::getTransRefAccount());
    }

    public function test_can_set_trans_ref_logging_disabled()
    {
        BankImportConfig::setTransRefLoggingEnabled(false);
        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
    }

    public function test_throws_exception_for_invalid_account()
    {
        $this->expectException(\InvalidArgumentException::class);
        BankImportConfig::setTransRefAccount('INVALID_ACCOUNT_999999');
    }
}
```

### Integration Tests
1. **Test with logging enabled** (default):
   - Process QE transaction
   - Verify two GL entries to account 0000 (or configured account)
   - Verify entries offset to $0.00

2. **Test with logging disabled**:
   - Disable in settings
   - Process QE transaction
   - Verify NO entries to reference account

3. **Test with custom account**:
   - Set reference account to '1060' (or other valid account)
   - Process QE transaction
   - Verify entries logged to '1060' instead of '0000'

4. **Test account validation**:
   - Attempt to set invalid account code
   - Verify error message displayed
   - Verify setting not saved

## Acceptance Criteria
- [ ] Configuration class created and tested
- [ ] QuickEntryTransactionHandler updated to use config
- [ ] Settings UI page created and accessible
- [ ] Menu item added to module
- [ ] Unit tests written (8+ tests)
- [ ] Integration tests pass
- [ ] Backward compatibility maintained (defaults match current behavior)
- [ ] Documentation updated (HANDLER_VERIFICATION.md)
- [ ] User manual updated with screenshots

## Effort Estimate
**2-4 hours** (1 developer)

## Priority
**Medium** - Enhancement to existing functionality, not blocking

## Related Issues
- Original TODO from switch statement refactoring
- See `HANDLER_VERIFICATION.md` section on Quick Entry

## Notes
- Maintain offset entries (0.01 and -0.01) to avoid affecting cart total
- Consider adding option to customize the memo text ("Trans Ref")
- Future: Could extend to other transaction types if needed
