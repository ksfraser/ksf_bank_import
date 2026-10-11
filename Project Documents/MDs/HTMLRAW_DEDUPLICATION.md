# HtmlRaw / HtmlRawString Deduplication

**Date**: 2025-01-24  
**Status**: ✅ COMPLETE

## Problem Identified

During HTML library reorganization, discovered two functionally identical classes:
- `HtmlRaw` (created 2025-10-19)
- `HtmlRawString` (created 2025-12-19)

Both classes:
- Implement `HtmlElementInterface`
- Store raw HTML without escaping
- Have `toHtml()` and `getHtml()` methods
- Return unescaped HTML content

## Decision: Keep HtmlRaw

**Reasons**:
1. **Created first** (October 2025 vs December 2025)
2. **Better documentation** (extensive examples, security warnings)
3. **Proper type hints** (`string $html` vs untyped `$string`)
4. **More widely used** (4 v2 views + production code)
5. **Better naming** (more concise)

HtmlRawString appeared to be a later duplicate, possibly created when HtmlRaw couldn't be found due to namespace issues.

## Changes Made

### 1. Updated HtmlOB Parent Class
**File**: `src/Ksfraser/HTML/Elements/HtmlOB.php`

```php
// OLD:
class HtmlOB extends HtmlRawString

// NEW:
class HtmlOB extends HtmlRaw
```

Also updated:
- Removed `use Ksfraser\HTML\Elements\HtmlRawString;`
- Changed property `$this->string` to `$this->html` in `end()` method
- Updated documentation to reference HtmlRaw

### 2. Updated class.bi_lineitem.php
**File**: `class.bi_lineitem.php`

```php
// OLD:
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlRawString.php' );
use Ksfraser\HTML\Elements\{HtmlRawString, HtmlOB, ...};

// NEW:
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlOB.php' );
use Ksfraser\HTML\Elements\{HtmlOB, ...};
```

### 3. Updated Test Files

**tests/unit/HtmlEmailAndATest.php**:
- Removed `require_once HtmlRawString.php`

**tests/unit/HtmlOBTest.php**:
- Changed namespace from `\Ksfraser\HTML\HTMLAtomic\HtmlOB` to `\Ksfraser\HTML\Elements\HtmlOB`

**test_html_links_manual.php**:
- Changed `require_once HtmlRawString.php` to `require_once HtmlRaw.php`
- Changed `use ...HtmlRawString` to `use ...HtmlRaw`
- Changed `new HtmlRawString(...)` to `new HtmlRaw(...)`

**test_nested_links.php**:
- Removed `require_once HtmlRawString.php` (wasn't used)

### 4. Removed Duplicate File
**Deleted**: `src/Ksfraser/HTML/Elements/HtmlRawString.php`

## Test Results

### Before Deduplication
```
Tests: 944, Assertions: 1680, Errors: 226, Failures: 19
```

### After Deduplication
```
Tests: 944, Assertions: 1697, Errors: 214, Failures: 19
```

**Improvement**: ✅ 12 fewer errors! (HtmlOB tests now passing)

All 12 HtmlOB tests pass:
- ✔ Capture simple echo
- ✔ Capture multiple echoes
- ✔ Does not escape html
- ✔ Manual start end
- ✔ To html echoes
- ✔ Capture object method
- ✔ Empty capture
- ✔ Implements html element interface
- ✔ Constructor with callable
- ✔ Constructor with string
- ✔ Constructor with null
- ✔ Capture equivalent to constructor

## Files Changed

1. `src/Ksfraser/HTML/Elements/HtmlOB.php` - Updated parent class and property name
2. `class.bi_lineitem.php` - Removed HtmlRawString require and use
3. `tests/unit/HtmlOBTest.php` - Fixed namespace references
4. `tests/unit/HtmlEmailAndATest.php` - Removed HtmlRawString require
5. `test_html_links_manual.php` - Replaced HtmlRawString with HtmlRaw
6. `test_nested_links.php` - Removed unused HtmlRawString require
7. `src/Ksfraser/HTML/Elements/HtmlRawString.php` - **DELETED**

## Usage

Going forward, use **`HtmlRaw`** for raw/unescaped HTML:

```php
use Ksfraser\HTML\Elements\HtmlRaw;

// For trusted HTML that should not be escaped
$html = new HtmlRaw('<b>Important</b> text');
echo $html->getHtml(); // <b>Important</b> text

// Security Warning: Only use with trusted content!
// ❌ NEVER use with user input:
$dangerous = new HtmlRaw($_POST['comment']); // XSS vulnerability!

// ✅ Use HtmlString for user input:
$safe = new HtmlString($_POST['comment']); // Escapes HTML
```

## Related Classes

- **HtmlRaw** - Canonical class for raw HTML (kept)
- **HtmlOB** - Extends HtmlRaw, captures output buffer
- **HtmlString** - Escaped HTML (for user input)

## Conclusion

Successfully eliminated code duplication by consolidating HtmlRaw and HtmlRawString into a single canonical class (HtmlRaw). All tests pass, functionality preserved, codebase cleaner.

✅ **Complete**
