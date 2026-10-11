# Refactor class.bi_lineitem.php to Use PartnerFormData

**Date**: 2025-01-24  
**Status**: 📋 PROPOSED

## Problem

`class.bi_lineitem.php` directly accesses `$_POST` superglobal in **20+ locations**, violating:
- **Separation of Concerns** - MODEL class should not access HTTP layer
- **Testability** - Hard to unit test without modifying $_POST
- **Maintainability** - Field name logic duplicated across file
- **Type Safety** - No type checking on POST values

## Current $_POST Usage in bi_lineitem

### Constructor (Lines 219-222)
```php
if( isset( $_POST["partnerId_" . $this->id] ) )
{
    $this->partnerId = $_POST["partnerId_" . $this->id];
}
```

### setPartnerType() (Lines 427-430)
```php
if( !isset( $_POST['partnerType'][$this->id] ) )
{
    $_POST['partnerType'][$this->id] = $this->partnerType;
}
```

### getDisplayMatchingTrans() (Lines 659, 663)
```php
$_POST['partnerType'][$this->id] = 'SP';  // or 'ZZ'
```

### displayBankTransferPartnerType() (Line 776)
```php
$this->partnerId = $_POST["partnerId_$this->id"];
```

### displayPartnerType() (Line 845)
```php
switch( $_POST['partnerType'][$this->id] ) 
```

### display_right() (Lines 944, 947-949)
```php
label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], ...));

if ( !isset( $_POST["partnerId_$this->id"] ) )
{
    $_POST["partnerId_$this->id"] = '';
}
```

## Solution: Use PartnerFormData

`PartnerFormData` class already exists at `src/Ksfraser/PartnerFormData.php` with:

### ✅ Existing Methods
- `getPartnerId(): ?int`
- `setPartnerId(?int $id): self`
- `hasPartnerId(): bool`
- `getPartnerDetailId(): ?int`
- `setPartnerDetailId(?int $id): self`
- `hasPartnerDetailId(): bool`
- `getRawPartnerId()` - for FA compatibility
- `getRawPartnerDetailId()` - for FA compatibility

### ❌ Missing Methods (Need to Add)
- `getPartnerType(): ?string`
- `setPartnerType(?string $type): self`
- `hasPartnerType(): bool`

## Proposed Refactoring

### Step 1: Extend PartnerFormData with partnerType support

Add to `src/Ksfraser/PartnerFormData.php`:

```php
/**
 * Get partner type from POST data
 *
 * @return string|null The partner type (SP, CU, BT, QE, etc.) or null if not set
 */
public function getPartnerType(): ?string
{
    $fieldName = "partnerType[{$this->lineItemId}]";
    
    if (!isset($_POST[$fieldName])) {
        return null;
    }
    
    $value = $_POST[$fieldName];
    
    if ($value === '') {
        return null;
    }
    
    return (string)$value;
}

/**
 * Set partner type in POST data
 *
 * @param string|null $partnerType The partner type to set (SP, CU, BT, QE, etc.)
 * @return self For method chaining
 */
public function setPartnerType(?string $partnerType): self
{
    $fieldName = "partnerType[{$this->lineItemId}]";
    
    if ($partnerType === null) {
        unset($_POST[$fieldName]);
    } else {
        $_POST[$fieldName] = $partnerType;
    }
    
    return $this;
}

/**
 * Check if partner type exists in POST data
 *
 * @return bool True if partner type is set and not empty
 */
public function hasPartnerType(): bool
{
    $fieldName = "partnerType[{$this->lineItemId}]";
    
    return isset($_POST[$fieldName]) && $_POST[$fieldName] !== '';
}
```

### Step 2: Add PartnerFormData to bi_lineitem constructor

```php
protected $formData;  // Add property

function __construct( $trz, $vendor_list = array(), $optypes = array() )
{
    // ... existing code ...
    
    $this->id = $trz['id'];
    
    // Initialize form data handler
    $this->formData = new \Ksfraser\PartnerFormData($this->id);
    
    // BEFORE:
    // if( isset( $_POST["partnerId_" . $this->id] ) )
    // {
    //     $this->partnerId = $_POST["partnerId_" . $this->id];
    // }
    
    // AFTER:
    if( $this->formData->hasPartnerId() )
    {
        $this->partnerId = $this->formData->getPartnerId();
    }
}
```

### Step 3: Refactor setPartnerType()

```php
function setPartnerType()
{
    switch( $this->transactionDC )
    {
        case 'C':
            $this->partnerType = 'CU';
            $this->oplabel = "Depost";
        break;
        case 'D':
            $this->partnerType = 'SP';
            $this->oplabel = "Payment";
        break;
        // ... etc
    }
    
    // BEFORE:
    // if( !isset( $_POST['partnerType'][$this->id] ) )
    // {
    //     $_POST['partnerType'][$this->id] = $this->partnerType;
    // }
    
    // AFTER:
    if( !$this->formData->hasPartnerType() )
    {
        $this->formData->setPartnerType($this->partnerType);
    }
    
    return $this->oplabel;
}
```

### Step 4: Refactor getDisplayMatchingTrans()

```php
// BEFORE:
// $_POST['partnerType'][$this->id] = 'SP';

// AFTER:
$this->formData->setPartnerType('SP');
```

### Step 5: Refactor displayBankTransferPartnerType()

```php
// BEFORE:
// $this->partnerId = $_POST["partnerId_$this->id"];

// AFTER:
$this->partnerId = $this->formData->getPartnerId();
```

### Step 6: Refactor displayPartnerType()

```php
// BEFORE:
// switch( $_POST['partnerType'][$this->id] )

// AFTER:
switch( $this->formData->getPartnerType() )
```

### Step 7: Refactor display_right()

```php
// BEFORE:
// label_row("Partner:", array_selector("partnerType[$this->id]", $_POST['partnerType'][$this->id], ...));

// AFTER:
label_row("Partner:", array_selector("partnerType[$this->id]", $this->formData->getPartnerType(), ...));

// BEFORE:
// if ( !isset( $_POST["partnerId_$this->id"] ) )
// {
//     $_POST["partnerId_$this->id"] = '';
// }

// AFTER:
if ( !$this->formData->hasPartnerId() )
{
    $this->formData->setPartnerId(null);  // Sets to ANY_NUMERIC
}
```

## Benefits

1. ✅ **Testability** - Can inject mock FormData for testing
2. ✅ **Type Safety** - Type-hinted return values
3. ✅ **Maintainability** - Field name logic centralized
4. ✅ **Separation of Concerns** - MODEL doesn't touch $_POST
5. ✅ **Consistency** - All Views and Models use same API
6. ✅ **Documentation** - Self-documenting method names

## Estimated Impact

- **Files to Change**: 2
  - `src/Ksfraser/PartnerFormData.php` - Add 3 methods
  - `class.bi_lineitem.php` - Refactor ~20 $_POST accesses
  
- **Lines Changed**: ~40-50 lines

- **Risk**: Low
  - PartnerFormData already tested
  - Can refactor incrementally
  - Tests will catch regressions

## Testing Strategy

1. Add unit tests for new PartnerFormData methods
2. Run existing bi_lineitem tests
3. Run integration tests with process_statements.php
4. Verify all 4 partner types work correctly

## Related Work

- **Already Complete**: V2 Views use PartnerFormData (ViewFactory pattern)
- **In Progress**: Integration testing with v2 Views
- **This Proposal**: Extend to MODEL layer (bi_lineitem)

## Next Steps

1. ✅ Get approval for refactoring approach
2. Add `getPartnerType()` / `setPartnerType()` to PartnerFormData
3. Add unit tests for new methods
4. Refactor bi_lineitem constructor
5. Refactor remaining $_POST accesses
6. Run full test suite
7. Update documentation

---

**Recommendation**: ✅ **PROCEED** - This refactoring aligns with our existing architecture and improves code quality with minimal risk.
