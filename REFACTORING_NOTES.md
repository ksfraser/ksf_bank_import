# Refactoring Notes - Quick Entry Matching

## Problem Identified

### DRY Violation
The same logic for matching transactions and determining partner types was duplicated across multiple files:
- `views/class.bi_lineitem.php`
- `src/Ksfraser/View/BiLineItemView.php`
- `class.bi_lineitem.php` (root)
- `src/Ksfraser/FaBankImport/class.bi_lineitem.php`

### SRP Violation
Business logic (determining partner type based on matched transactions) was located in View layer files instead of the Model layer.

## Solution Implemented

### Centralized Business Logic in Model
Created a new method in `BiLineItemModel`:
```php
protected function determinePartnerTypeFromMatches(): void
```

This method:
1. Examines matched transactions
2. Applies business rules to determine partner type:
   - **Invoice match** → SP (Supplier Payment)
   - **Bank Payment/Deposit match** → QE (Quick Entry) - *NEW FEATURE*
   - **Other match** → ZZ (Generic matched transaction)
3. Sets the appropriate `partnerType` and `oplabel`

### Simplified View Layer
View classes now simply call `$model->findMatchingExistingJE()` which:
1. Finds matching transactions
2. Automatically determines partner type (via `determinePartnerTypeFromMatches()`)
3. Returns the matches

The View layer is responsible only for:
- Displaying the results
- Setting hidden form fields for matched transactions

## Benefits

1. **Single Source of Truth**: Business logic exists in one place (Model)
2. **Easier Maintenance**: Changes to matching logic only need to be made once
3. **Better Testing**: Can test business logic independently of views
4. **Proper Separation of Concerns**: Model handles business logic, View handles presentation

## New Feature: Quick Entry Matching

Transactions that match existing Quick Entry transactions (recurring expenses like groceries, insurance, utilities) are now automatically suggested as 'QE' partner type instead of generic 'ZZ'.

This improves workflow for recurring transactions by:
- Auto-selecting the correct Quick Entry type
- Pre-populating the Quick Entry dropdown
- Applying the same GL account coding as the original

## Files Modified

1. `src/Ksfraser/Model/BiLineItemModel.php`
   - Added `determinePartnerTypeFromMatches()` method
   - Enhanced `findMatchingExistingJE()` to call determination logic

2. `src/Ksfraser/View/BiLineItemView.php`
   - Simplified `getDisplayMatchingTrans()` to delegate to Model
   - Removed duplicated business logic

3. `views/class.bi_lineitem.php`
   - Added Quick Entry detection in matching logic
   - (Should be refactored to use Model method in future)

## TODO: Further Refactoring

The following files still contain duplicate logic and should be updated to use the centralized Model method:
- `views/class.bi_lineitem.php` - Update to use BiLineItemModel
- `class.bi_lineitem.php` (root) - Likely deprecated, consider removing
- `src/Ksfraser/FaBankImport/class.bi_lineitem.php` - Update or remove if duplicate

Consider creating a migration plan to consolidate all line item handling to the new MVC structure.
