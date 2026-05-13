# HtmlHidden Class Added ✅

**Date**: October 23, 2025  
**File**: `src/Ksfraser/HTML/HtmlHidden.php`  
**Status**: ✅ **COMPLETE**

## Summary

Created a new `HtmlHidden` convenience class for generating hidden form fields, replacing the verbose `new HtmlInput("hidden")` pattern with a cleaner, more semantic API.

## Motivation

**Before (Using HtmlInput)**:
```php
// Verbose - 3 lines for a simple hidden field
$hiddenBranch = (new HtmlInput("hidden"))
    ->setName("partnerDetailId_{$this->lineItemId}")
    ->setValue((string)ANY_NUMERIC);
$cust_text .= $hiddenBranch->getHtml();
```

**After (Using HtmlHidden)**:
```php
// Concise - 1 line for a simple hidden field
$hiddenBranch = new HtmlHidden("partnerDetailId_{$this->lineItemId}", (string)ANY_NUMERIC);
$cust_text .= $hiddenBranch->getHtml();
```

**Improvement**: 66% less code, clearer intent!

## Implementation

### Class Design

```php
class HtmlHidden extends HtmlInput
{
    public function __construct(?string $name = null, ?string $value = null)
    {
        parent::__construct("hidden");  // Pre-configured type
        
        if ($name !== null) {
            $this->setName($name);
        }
        
        if ($value !== null) {
            $this->setValue($value);
        }
    }
}
```

### Key Features

1. **Convenience Constructor**: Accepts name and value directly
2. **Fluent Interface**: Inherits from HtmlInput, supports chaining
3. **Type Pre-configured**: Always creates `type="hidden"` inputs
4. **Security**: Inherits XSS protection from HtmlInput (auto-escaping)
5. **Flexibility**: Can use empty constructor + fluent methods

### Usage Examples

```php
// Simple usage
$hidden = new HtmlHidden("user_id", "12345");
echo $hidden->getHtml();
// Output: <input type="hidden" name="user_id" value="12345">

// Fluent interface
$hidden = (new HtmlHidden())
    ->setName("customer_id")
    ->setValue("42");

// Name only (value set later)
$hidden = new HtmlHidden("field_name");
$hidden->setValue("some_value");

// Empty string value
$hidden = new HtmlHidden("empty_field", "");
```

## Test Coverage

Created comprehensive test suite: `tests/unit/HTML/HtmlHiddenTest.php`

```
Tests: 8, Assertions: 20, ALL PASSING ✅

✅ Basic hidden field
✅ Hidden field with name only
✅ Hidden field with fluent interface
✅ Hidden field escapes special characters
✅ Hidden field with empty value
✅ To html outputs correctly
✅ Hidden field is self closing
✅ Constructor with null values
```

## Integration

Updated `CustomerPartnerTypeView.v2.php` to use HtmlHidden:

### Changes Made

**3 locations updated**:

1. **Branch hidden field** (line ~121):
```php
// BEFORE:
$hiddenBranch = (new HtmlInput("hidden"))
    ->setName("partnerDetailId_{$this->lineItemId}")
    ->setValue((string)ANY_NUMERIC);

// AFTER:
$hiddenBranch = new HtmlHidden("partnerDetailId_{$this->lineItemId}", (string)ANY_NUMERIC);
```

2. **Customer hidden field** (line ~136):
```php
// BEFORE:
$hiddenCustomer = (new HtmlInput("hidden"))
    ->setName("customer_{$this->lineItemId}")
    ->setValue((string)($this->partnerId ?? ''));

// AFTER:
$hiddenCustomer = new HtmlHidden("customer_{$this->lineItemId}", (string)($this->partnerId ?? ''));
```

3. **Customer branch hidden field** (line ~139):
```php
// BEFORE:
$hiddenCustomerBranch = (new HtmlInput("hidden"))
    ->setName("customer_branch_{$this->lineItemId}")
    ->setValue((string)($this->partnerDetailId ?? ''));

// AFTER:
$hiddenCustomerBranch = new HtmlHidden("customer_branch_{$this->lineItemId}", (string)($this->partnerDetailId ?? ''));
```

### Integration Tests

All existing tests pass with HtmlHidden:

```
CustomerPartnerTypeViewV2Test: 8/8 passing ✅
```

## Benefits

### 1. Cleaner Code
- **66% less boilerplate** for hidden fields
- Single line vs 3 lines per hidden field
- Clearer semantic intent

### 2. Better Readability
```php
// Crystal clear what this does
new HtmlHidden("field_name", "value")

// vs requires understanding of HtmlInput
(new HtmlInput("hidden"))->setName("field_name")->setValue("value")
```

### 3. Consistency
- Follows HTML class naming convention (`HtmlInput`, `HtmlSelect`, `HtmlHidden`)
- Matches pattern of other specialized input classes

### 4. Maintainability
- Single place to modify hidden field behavior
- Can add hidden-specific features later (e.g., validation, sanitization)

### 5. Type Safety
- Pre-configured `type="hidden"` - can't accidentally create wrong type
- Constructor signature makes it impossible to forget the type

## Security

### XSS Protection

Inherits automatic XSS protection from `HtmlInput::setValue()`:

```php
$hidden = new HtmlHidden("field", "<script>alert('xss')</script>");
echo $hidden->getHtml();
// Output: <input type="hidden" name="field" value="&lt;script&gt;alert('xss')&lt;/script&gt;">
```

**Test confirms**: Special characters are properly escaped (test passes ✅)

### Security Note in Documentation

Class includes warning:
> Hidden fields are visible in HTML source and can be modified by users.
> Never rely on hidden fields for security - always validate on server side.

## Location

**Proper namespace**: `src/Ksfraser/HTML/HtmlHidden.php`
- ✅ In source of truth directory
- ✅ Consistent with other HTML classes
- ✅ Follows naming convention

## Code Quality

### SOLID Principles

- **Single Responsibility**: Only creates hidden input fields
- **Open/Closed**: Extends HtmlInput (open for extension)
- **Liskov Substitution**: Can replace HtmlInput with type="hidden" anywhere
- **Interface Segregation**: Uses HtmlElementInterface appropriately
- **Dependency Inversion**: Depends on abstractions (HtmlInput base)

### Documentation

- ✅ Full PHPDoc comments
- ✅ Usage examples in docblock
- ✅ Security notes
- ✅ Package/author/version info
- ✅ @since tag

### Testing

- ✅ 8 unit tests
- ✅ 20 assertions
- ✅ Edge cases covered (null values, special characters, empty strings)
- ✅ 100% coverage of public methods

## Files Created/Modified

1. **src/Ksfraser/HTML/HtmlHidden.php** (NEW - 69 lines)
   - New convenience class
   
2. **tests/unit/HTML/HtmlHiddenTest.php** (NEW - 140 lines)
   - Comprehensive test suite
   
3. **Views/CustomerPartnerTypeView.v2.php** (MODIFIED)
   - Updated 3 hidden field usages
   - Added HtmlHidden import

## Comparison to Other Frameworks

### Laravel Blade
```php
{{ Form::hidden('field', 'value') }}  // Laravel syntax
new HtmlHidden('field', 'value')      // Our syntax - similar!
```

### Symfony Forms
```php
$builder->add('field', HiddenType::class);  // Symfony
new HtmlHidden('field', 'value')            // Our syntax - simpler!
```

Our implementation is competitive with major frameworks! ✅

## Future Enhancements (Optional)

1. **Array values**: Support `name[]` for arrays
2. **Validation**: Built-in value validation
3. **Sanitization**: Additional sanitization options
4. **ID auto-generation**: Auto-generate IDs from names
5. **CSS classes**: Support adding classes to hidden fields (rare but possible)

## Conclusion

The `HtmlHidden` class is a small but impactful addition that:
- ✅ Improves code readability
- ✅ Reduces boilerplate
- ✅ Follows best practices
- ✅ Fully tested
- ✅ Properly documented
- ✅ Zero breaking changes

**Recommendation**: Use `HtmlHidden` for all future hidden field implementations!

---

**Created**: October 23, 2025  
**Author**: Kevin Fraser / GitHub Copilot  
**Status**: ✅ Production Ready
