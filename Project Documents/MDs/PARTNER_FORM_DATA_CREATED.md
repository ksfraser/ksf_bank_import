# PartnerFormData Class Created ✅

**Date**: 2025-01-07  
**Status**: ✅ Complete - 17 tests passing  
**Integration**: ✅ BankTransferPartnerTypeView updated

## Summary

Created `PartnerFormData` class to encapsulate `$_POST` access for partner form fields.
This completes the Single Responsibility Principle separation for form field handling:

1. **FormFieldNameGenerator** - Generates field names ✅
2. **PartnerFormData** - Encapsulates $_POST access ✅ (NEW!)

## Problem Identified

User spotted direct `$_POST` manipulation in Views at line 80 of BankTransferPartnerTypeView:

```php
// ❌ BAD: Direct $_POST manipulation
if (empty($_POST["partnerId_{$this->lineItemId}"])) {
    $_POST["partnerId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerId($match);
    $_POST["partnerDetailId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerDetailId($match);
}
```

This violates:
- **Single Responsibility Principle** - Views shouldn't know about $_POST structure
- **Tell Don't Ask** - Views are asking about internal state
- **Encapsulation** - Direct access to global state

## Solution: PartnerFormData Class

**File**: `src/Ksfraser/PartnerFormData.php` (287 lines)

### Key Features

1. **Type-Safe Access**
   ```php
   $formData = new PartnerFormData($lineItemId);
   
   $partnerId = $formData->getPartnerId();      // Returns int|null
   $detailId = $formData->getPartnerDetailId(); // Returns int|null
   ```

2. **Existence Checking**
   ```php
   if ($formData->hasPartnerId()) {
       // Partner ID is set and not ANY_NUMERIC
   }
   ```

3. **Raw Value Access** (for FA functions)
   ```php
   $rawValue = $formData->getRawPartnerId();  // Includes ANY_NUMERIC
   ```

4. **Method Chaining**
   ```php
   $formData
       ->setPartnerId(456)
       ->setPartnerDetailId(789);
   ```

5. **Clear Methods**
   ```php
   $formData->clearPartnerId();
   $formData->clearPartnerDetailId();
   ```

### API Methods

**Getters**:
- `getPartnerId()` - Returns int|null (excludes ANY_NUMERIC)
- `getPartnerDetailId()` - Returns int|null (excludes ANY_NUMERIC)
- `getRawPartnerId()` - Returns mixed (includes ANY_NUMERIC)
- `getRawPartnerDetailId()` - Returns mixed (includes ANY_NUMERIC)

**Setters**:
- `setPartnerId(int|null)` - Set partner ID (null → ANY_NUMERIC)
- `setPartnerDetailId(int|null)` - Set partner detail ID (null → ANY_NUMERIC)

**Existence Checks**:
- `hasPartnerId()` - Returns bool (false for ANY_NUMERIC)
- `hasPartnerDetailId()` - Returns bool (false for ANY_NUMERIC)

**Clear Methods**:
- `clearPartnerId()` - Unset from $_POST
- `clearPartnerDetailId()` - Unset from $_POST

**Utility**:
- `getLineItemId()` - Returns int
- `getFieldGenerator()` - Returns FormFieldNameGenerator

### ANY_NUMERIC Handling

Special handling for FA's `ANY_NUMERIC` constant:

```php
// Setting to null → ANY_NUMERIC in $_POST
$formData->setPartnerId(null);
// $_POST['partnerId_123'] === ANY_NUMERIC

// hasPartnerId() treats ANY_NUMERIC as "not set"
$formData->hasPartnerId();  // false

// getPartnerId() converts ANY_NUMERIC to null
$formData->getPartnerId();  // null

// getRawPartnerId() preserves ANY_NUMERIC
$formData->getRawPartnerId();  // ANY_NUMERIC
```

## Testing

**File**: `tests/unit/PartnerFormDataTest.php`

**Tests**: 17  
**Assertions**: 32  
**Status**: ALL PASSING ✅

Test Coverage:
- ✅ Constructor with default generator
- ✅ Constructor with custom generator
- ✅ Set/get partner ID
- ✅ Set/get partner detail ID
- ✅ Null value handling
- ✅ ANY_NUMERIC constant handling
- ✅ Existence checking
- ✅ Raw value access
- ✅ Clear methods
- ✅ Method chaining
- ✅ Getters for line item ID and generator

## Integration Example

### Before (Direct $_POST Access)

```php
// ❌ BAD: Direct $_POST manipulation
if (empty($_POST["partnerId_{$this->lineItemId}"])) {
    $match = \PartnerMatcher::searchByBankAccount(...);
    
    if (\PartnerMatcher::hasMatch($match)) {
        $_POST["partnerId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerId($match);
        $_POST["partnerDetailId_{$this->lineItemId}"] = \PartnerMatcher::getPartnerDetailId($match);
    } else {
        $_POST["partnerId_{$this->lineItemId}"] = ANY_NUMERIC;
    }
}

$bankListHtml = \bank_accounts_list(
    "partnerId_{$this->lineItemId}", 
    $_POST["partnerId_{$this->lineItemId}"],
    null, 
    false
);
```

### After (PartnerFormData Encapsulation)

```php
// ✅ GOOD: Encapsulated form data access
if (!$this->formData->hasPartnerId()) {
    $match = \PartnerMatcher::searchByBankAccount(...);
    
    if (\PartnerMatcher::hasMatch($match)) {
        $this->formData->setPartnerId(\PartnerMatcher::getPartnerId($match));
        $this->formData->setPartnerDetailId(\PartnerMatcher::getPartnerDetailId($match));
    } else {
        $this->formData->setPartnerId(null);  // Sets to ANY_NUMERIC
    }
}

$bankListHtml = \bank_accounts_list(
    "partnerId_{$this->lineItemId}", 
    $this->formData->getRawPartnerId(),  // For FA compatibility
    null, 
    false
);
```

**Benefits**:
- ✅ No direct `$_POST` access
- ✅ Clear intent with method names
- ✅ Type-safe
- ✅ Testable
- ✅ Single responsibility
- ✅ Easy to mock for testing

## Updated Files

### 1. BankTransferPartnerTypeView.v2.final.php ✅

**Changes**:
1. Added `use Ksfraser\PartnerFormData;`
2. Added `private PartnerFormData $formData;` property
3. Initialize `$this->formData = new PartnerFormData($lineItemId);` in constructor
4. Replaced all `$_POST["partnerId_{$this->lineItemId}"]` with `$this->formData->getPartnerId()`
5. Replaced `empty($_POST[...])` with `!$this->formData->hasPartnerId()`
6. Replaced `$_POST[...] = value` with `$this->formData->setPartnerId(value)`
7. Used `$this->formData->getRawPartnerId()` for FA function calls

**Test Results**: 7/7 tests passing ✅

## Next Steps

### Remaining Views to Update

1. **CustomerPartnerTypeView.v2.php** - Has 3 hidden fields with `$_POST` access
2. **SupplierPartnerTypeView.v2.final.php** - Has `$_POST` access
3. **QuickEntryPartnerTypeView** - Not yet refactored

### Integration Pattern

For each View:

```php
class SomePartnerTypeView
{
    private PartnerFormData $formData;
    
    public function __construct(
        int $lineItemId,
        DataProvider $dataProvider
    ) {
        $this->lineItemId = $lineItemId;
        $this->dataProvider = $dataProvider;
        $this->formData = new PartnerFormData($lineItemId);  // ← Add this
    }
    
    public function getHtml(): string
    {
        // Replace $_POST access with $this->formData methods
        if (!$this->formData->hasPartnerId()) {
            $this->formData->setPartnerId($someId);
        }
        
        $value = $this->formData->getPartnerId();
        $rawValue = $this->formData->getRawPartnerId();  // For FA functions
    }
}
```

## Benefits of This Refactoring

### Single Responsibility
- **Before**: Views responsible for form structure, HTML generation, AND $_POST manipulation
- **After**: Views focus on HTML generation, PartnerFormData handles $_POST

### Testability
- **Before**: Had to manipulate $_POST directly in tests
- **After**: Can inject mock PartnerFormData

### Type Safety
- **Before**: `$_POST` values are always mixed/string
- **After**: `getPartnerId()` returns `int|null`

### Maintainability
- **Before**: Field name format scattered across Views
- **After**: Centralized in FormFieldNameGenerator + PartnerFormData

### Clarity
- **Before**: `empty($_POST["partnerId_$id"])` - What does "empty" mean?
- **After**: `!$formData->hasPartnerId()` - Clear intent

## Architecture

```
FormFieldNameGenerator (generates field names)
         ↓
PartnerFormData (encapsulates $_POST access)
         ↓
PartnerTypeViews (use formData for all POST operations)
```

### Separation of Concerns

1. **FormFieldNameGenerator**: "What is the field called?"
   - `partnerIdField(123)` → `"partnerId_123"`

2. **PartnerFormData**: "What is the field's value?"
   - `getPartnerId()` → `456`
   - `setPartnerId(456)` → Updates $_POST

3. **PartnerTypeViews**: "How do we display this?"
   - Generates HTML using form data
   - No $_POST knowledge required

## Documentation

This refactoring was triggered by user observation at line 80 of BankTransferPartnerTypeView:

> "Didn't we create an SRP class to replace the passing of $_POST? line 80..."

The user was correct - we had FormFieldNameGenerator for generating names, but were still accessing `$_POST` directly. This new `PartnerFormData` class completes the encapsulation pattern.

## Files Created

1. `src/Ksfraser/PartnerFormData.php` (287 lines) - Production code
2. `tests/unit/PartnerFormDataTest.php` (318 lines) - Test suite

**Total**: 605 lines of code + tests

---

**Status**: Ready to integrate into remaining PartnerType views
