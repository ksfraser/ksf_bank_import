# FrontAccounting Function Stubs

## Purpose

This file provides stub declarations for FrontAccounting core functions to eliminate IDE lint errors during development.

## What Problem Does This Solve?

When developing FrontAccounting modules in an IDE (like VS Code), you get lint errors for FrontAccounting functions that aren't available outside the main FA installation:

```
❌ Call to unknown function: 'display_notification'
❌ Call to unknown function: 'start_table'
❌ Use of undefined constant 'TABLESTYLE'
```

These errors don't affect production (where FA functions are available), but they clutter your IDE and hide real errors.

## How It Works

`fa_stubs.php` provides no-op stub implementations wrapped in `function_exists()` checks:

```php
if (!function_exists('display_notification')) {
    function display_notification(string $msg, int $type = 0): void {
        // Stub - actual implementation in FrontAccounting
    }
}
```

**In Development**: Stub is used, IDE is happy, no lint errors ✅  
**In Production**: Real FA function exists, stub is skipped ✅

## What's Included

### Display Functions
- `display_notification()` - Success/info messages
- `display_error()` - Error messages
- `display_warning()` - Warning messages

### Table/Form Functions
- `start_table()`, `end_table()` - Table containers
- `start_row()`, `end_row()` - Table rows
- `table_header()` - Header row
- `label_row()`, `label_cell()` - Label displays
- `submit_cells()`, `submit_center_first()` - Submit buttons
- `hidden()` - Hidden form fields
- `array_selector()` - Dropdown selectors
- `bank_accounts_list_row()` - Bank account selector

### Container Functions
- `div_start()`, `div_end()` - Div containers

### Database Functions
- `db_insert_id()` - Last insert ID
- `db_query()` - Execute query
- `db_fetch()` - Fetch row

### Path Functions
- `company_path()` - Company file directory

### Translation Functions
- `_()` - Translation function

### Constants
- `TABLESTYLE` - Default table CSS class
- `TABLESTYLE2` - Alternative table CSS class
- `TB_PREF` - Table prefix (`0_`)

### Custom Functions
- `getParsers()` - Available file parsers

## Usage in Development

### VS Code Configuration

The `.vscode/settings.json` file is configured to include this stub file:

```json
{
    "intelephense.environment.includePaths": [
        "${workspaceFolder}/includes"
    ]
}
```

This tells the PHP language server to scan `includes/` for function definitions.

### Manual Include (Optional)

If your IDE doesn't pick up the stubs automatically, you can temporarily include it at the top of files:

```php
<?php
// For IDE support only - DO NOT COMMIT THIS LINE
require_once __DIR__ . '/../includes/fa_stubs.php';

// Your code...
display_notification("This now has autocomplete!");
```

**⚠️ IMPORTANT**: Remove the `require_once` before committing! The stubs are for IDE support only.

## Usage in Testing

For unit tests that need FA functions, you can include the stubs:

```php
<?php
namespace Ksfraser\FaBankImport\Tests;

require_once __DIR__ . '/../../includes/fa_stubs.php';

class MyTest extends TestCase {
    public function testSomething() {
        // Now FA functions won't cause errors
        display_notification("Test message");
    }
}
```

## What NOT To Do

❌ **Don't require this file in production code**
```php
// BAD - Don't do this!
require_once __DIR__ . '/includes/fa_stubs.php';
```

❌ **Don't rely on stub implementations**
```php
// BAD - Stub implementations are no-ops
$id = db_insert_id(); // Returns 0 in stubs, real ID in production
```

❌ **Don't commit files with explicit stub requires**
```php
// BAD - This should only be temporary for IDE support
require_once 'fa_stubs.php';
```

## What TO Do

✅ **Use IDE configuration** (`.vscode/settings.json`)  
✅ **Keep stubs updated** as you discover new FA functions  
✅ **Use stubs for lint error elimination only**  
✅ **Test in real FA environment** before deployment  

## Adding New Stubs

If you encounter a new FA function causing lint errors:

1. Open `includes/fa_stubs.php`
2. Add a new stub with `function_exists()` guard:
   ```php
   if (!function_exists('new_fa_function')) {
       /**
        * Description of what this function does
        * @param mixed $param Parameter description
        * @return mixed Return value description
        */
       function new_fa_function($param): mixed {
           // Stub - actual implementation in FrontAccounting
           return null; // or appropriate default
       }
   }
   ```
3. Save and reload VS Code window

## Maintenance

### When to Update
- New FrontAccounting version adds functions
- You discover undocumented FA functions
- Linter complains about missing functions

### How to Update
1. Find the function signature in FA source code
2. Add stub with proper type hints and PHPDoc
3. Use appropriate default return value
4. Wrap in `function_exists()` check

## Production Safety

All stubs use `function_exists()` guards, so they're safe even if accidentally included in production:

```php
if (!function_exists('display_notification')) {
    // This stub is ONLY defined if the real function doesn't exist
    function display_notification(...) { ... }
}
```

In production:
- Real FA functions are loaded first
- `function_exists()` returns `true`
- Stub is skipped
- No conflicts! ✅

## Benefits

1. **Clean IDE** - No false lint errors
2. **Autocomplete** - IDE suggests FA functions
3. **Type Safety** - Parameter hints in IDE
4. **Documentation** - PHPDoc comments for FA functions
5. **Development Speed** - Focus on real errors
6. **Zero Risk** - Guards prevent production conflicts

## Files Using FA Functions

These files reference FA functions and benefit from stubs:

- `import_statements.php` - Main upload interface
- `view_statements.php` - Statement viewing
- `process_statements.php` - Transaction processing
- `manage_partners_data.php` - Partner management

All these files work in production because FA provides the real functions.

## Testing

The stubs are tested to ensure:
- No syntax errors
- All guards work correctly
- Return types are appropriate
- No conflicts with real FA functions

Run: `php -l includes/fa_stubs.php` to check syntax.

## Summary

**Purpose**: IDE support for FrontAccounting functions  
**Method**: No-op stubs with `function_exists()` guards  
**Usage**: Automatic via `.vscode/settings.json`  
**Safety**: Guards prevent production conflicts  
**Benefit**: Clean development experience  

**Result**: Happy IDE, happy developer! 😊
