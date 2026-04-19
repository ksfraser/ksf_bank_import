# Unified Transaction Partner Matching Architecture

## Problem Solved

**Before**: Separate `displaySupplier()`, `displayCustomer()`, `displayBankTransfer()` methods with duplicated matching logic in each, forcing transaction classification BEFORE matching.

**After**: Single unified matcher that scores transactions against ALL partner types regardless of classification, then recommends partner type based on best match.

## Architecture Overview

```
Transaction Data (amount, memo, account, etc.)
         │
         ▼
┌─────────────────────────────────────┐
│ TransactionPartnerMatcher           │
│ ┌──────────────────────────────────┐│
│ │ Load All Partners:               ││
│ │ • Suppliers                      ││
│ │ • Customers                      ││
│ │ • Bank Accounts                  ││
│ └──────────────────────────────────┘│
│         │                            │
│         ▼                            │
│ ┌──────────────────────────────────┐│
│ │ ScoringRuleEngine                ││
│ │ (score against each partner)     ││
│ └──────────────────────────────────┘│
│         │                            │
│         ▼                            │
│ ┌──────────────────────────────────┐│
│ │ Return Results Grouped By Type:  ││
│ │ • supplier: [matches...]         ││
│ │ • customer: [matches...]         ││
│ │ • bank_transfer: [matches...]    ││
│ │ • best_match: highest score      ││
│ └──────────────────────────────────┘│
└─────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│ bi_lineitem / Display Method         │
│ ┌──────────────────────────────────┐│
│ │ IF best_match:                   ││
│ │  • Pre-select partner type       ││
│ │  • Pre-select partner ID         ││
│ │  • Show confidence score         ││
│ │  • Allow user override           ││
│ │                                  ││
│ │ ELSE:                            ││
│ │  • Show partner type selector    ││
│ │  • Show all matches for each type││
│ │  • Let user choose               ││
│ └──────────────────────────────────┘│
│         │                            │
│         ▼                            │
│    Display Form                      │
└─────────────────────────────────────┘
```

## Key Classes

### TransactionPartnerMatcher
**Orchestrates unified matching across all partner types**

```php
$matcher = new TransactionPartnerMatcher($engine, $config);

$results = $matcher->matchTransaction(
    $transaction,      // Transaction data
    $suppliers,        // Array of suppliers
    $customers,        // Array of customers
    $bankAccounts      // Array of bank accounts
);

// Returns:
$results = [
    'supplier' => [TransactionMatchResult, ...],      // Ranked by score
    'customer' => [TransactionMatchResult, ...],      // Ranked by score
    'bank_transfer' => [TransactionMatchResult, ...], // Ranked by score
    'best_match' => TransactionMatchResult or null    // Highest overall
];
```

### TransactionMatchResult
**Immutable value object for single match with confidence score**

```php
$result = $results['best_match'];

$partnerId = $result->getPartnerId();        // Which partner
$partnerName = $result->getPartnerName();    // Partner name
$score = $result->getScore();                // Confidence 0-100
$type = $result->getPartnerType();           // 'supplier', 'customer', 'bank_transfer'
```

## Integration Pattern: Minimal Refactoring

### Option 1: Hook Into Existing displayPartnerType()

```php
// In bi_lineitem::displayPartnerType()

// BEFORE THIS METHOD IS CALLED: Run the matcher
$matchResults = $this->getTransactionMatches();  // New helper

if ($matchResults['best_match'] !== null) {
    // Pre-populate based on best match
    $this->formData->setPartnerType($matchResults['best_match']->getPartnerType());
    $this->formData->setPartnerId($matchResults['best_match']->getPartnerId());
    
    // Display the confidence
    echo "Recommended: " . $matchResults['best_match']->getPartnerName() 
         . " (Confidence: " . $matchResults['best_match']->getScore() . "%)";
}

// Then call existing display method
switch($_POST['partnerType'][$this->id]) {
    case 'SP': $this->displaySupplierPartnerType(); break;
    case 'CU': $this->displayCustomerPartnerType(); break;
    case 'BT': $this->displayBankTransferPartnerType(); break;
    // ...
}
```

### Option 2: Create New displayPartnerTypeWithMatching()

```php
// New method in bi_lineitem
function displayPartnerTypeWithMatching()
{
    // Get matches for ALL partner types
    $matchResults = $this->getTransactionMatches();
    
    // Show best match if confident enough
   if ($matchResults['best_match'] !== null) {
        $this->showBestMatchOption($matchResults['best_match']);
    }
    
    // Show other options
    $this->showAlternativeMatches($matchResults);
    
    // Standard partner type selector with matches pre-populated
    $this->showPartnerTypeSelector($matchResults);
}

private function getTransactionMatches()
{
    $matcher = $this->createMatcher();
    
    $suppliers = $this->loadAllSuppliers();
    $customers = $this->loadAllCustomers();
    $bankAccounts = $this->loadAllBankAccounts();
    
    $transaction = [
        'account' => $this->our_account,
        'partner_account' => $this->otherBankAccount,
        'amount' => $this->amount,
        'memo' => $this->memo,
        'is_invoice' => $this->transactionTypeLabel === 'Invoice',
        'type' => $this->transactionTypeCode,
    ];
    
    return $matcher->matchTransaction(
        $transaction,
        $suppliers,
        $customers,
        $bankAccounts
    );
}
```

##  Benefits Over Current Approach

| Aspect | Current | With Unified Matcher |
|--------|---------|----------------------|
| **Matching Logic** | Duplicated in 3 methods | Single engine |
| **Partner Type Selection** | Before matching | After matching (data-driven) |
| **User UX** | Manual entry | Pre-filled with confidence |
| **Extensibility** | Add new type = add method | Add new matches automatically |
| **Testing** | 3 separate test suites | 1 unified test suite |
| **Configuration** | 3 configurations | 1 configuration |

## Zero Breaking Changes

✅ Existing `class.bi_lineitem.php` - **No changes required**  
✅ Existing `class.ViewBiLineItems.php` - **No changes required**  
✅ Backward compatibility - **Fully maintained**  
✅ Opt-in integration - **Feature flag can control behavior**  

## Feature Flag Approach

```php
// In class.bi_lineitem.php
define('USE_UNIFIED_PARTNER_MATCHING', true);

function displayPartnerType()
{
    if (USE_UNIFIED_PARTNER_MATCHING) {
        $this->displayPartnerTypeWithMatching();  // New approach
    } else {
        $this->displayPartnerTypeWithoutMatching(); // Existing approach
    }
}
```

## Next Steps

1. **Test the matcher independently** - Verify scoring works correctly
2. **Add feature flag to bi_lineitem** - Control new behavior
3. **Implement displayPartnerTypeWithMatching()** - New method that uses results
4. **Update tests** - Add integration tests for new flow
5. **Measure performance** - Ensure single pass is faster than 3 separate calls
6. **Gradually rollout** - Start with feature flag, monitor results

## Summary

This unified transaction matcher provides a **cleaner architecture** where:
- Matching decisions are data-driven (score-based)
- Partner type is recommended, not required upfront  
- No duplicated matching logic across partner types
- Minimal refactoring needed (integration layer only)
- Zero breaking changes to existing code
- Better user experience (confidence scores, auto-prefill)
