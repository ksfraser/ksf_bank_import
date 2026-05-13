# Page-Level Data Loading Strategy

**Date:** October 19, 2025  
**Issue:** Multiple redundant database queries per page load  
**Impact:** Performance degradation with multiple line items

## Problem Analysis

### Current Architecture Issues

When displaying transaction line items in `process_statements.php`, the code loads some data at page level but not all:

#### ✅ Currently Optimized (Loaded Once)

1. **`$optypes`** (Partner Types) - Line 55
   ```php
   $optypes = OperationTypesRegistry::getInstance()->getTypes();
   ```
   - Now with PartnerSelectionPanel v1.1.0 static caching: ✅ Fully optimized

2. **`$vendor_list`** (Suppliers) - Line 628
   ```php
   $vendor_list = \KsfBankImport\VendorListManager::getInstance()->getVendorList();
   ```
   - Uses singleton pattern with caching: ✅ Already optimized
   - Passed to each `bi_lineitem` constructor

#### ❌ Currently NOT Optimized (Loaded Per Line Item)

Each time a line item displays a partner type form, these FA helper functions are called:

1. **`supplier_list()`** - Called in `displaySupplierPartnerType()`
   ```php
   label_row(_("Payment To:"), supplier_list("partnerId_$this->id", $matched_supplier, false, false));
   ```
   - Queries database: `SELECT * FROM suppliers`
   - Called once per line item with partner type 'SP'

2. **`customer_list()`** - Called in `displayCustomerPartnerType()`
   ```php
   $cust_text = customer_list("partnerId_$this->id", null, false, true);
   ```
   - Queries database: `SELECT * FROM customers`
   - Called once per line item with partner type 'CU'

3. **`customer_branches_list()`** - Called in `displayCustomerPartnerType()`
   ```php
   $cust_text .= customer_branches_list($this->partnerId, "partnerDetailId_$this->id", null, false, true, true);
   ```
   - Queries database: `SELECT * FROM cust_branches WHERE debtor_no = ?`
   - Called once per customer line item (if customer has branches)

4. **`bank_accounts_list()`** - Called in `displayBankTransferPartnerType()`
   ```php
   label_row(_($rowlabel), bank_accounts_list("partnerId_$this->id", $_POST["partnerId_$this->id"], null, false));
   ```
   - Queries database: `SELECT * FROM bank_accounts`
   - Called once per line item with partner type 'BT'

5. **`quick_entries_list()`** - Called in `displayQuickEntryPartnerType()`
   ```php
   $qe_text = quick_entries_list("partnerId_$this->id", null, (($this->transactionDC=='C') ? QE_DEPOSIT : QE_PAYMENT), true);
   ```
   - Queries database: `SELECT * FROM quick_entries WHERE type = ?`
   - Called once per line item with partner type 'QE'

### Performance Impact

**Scenario:** Page displays 20 line items with mixed partner types:
- 8 suppliers (SP)
- 5 customers (CU) - 3 with branches
- 4 bank transfers (BT)
- 2 quick entries (QE)
- 1 matched (MA)

**Current Database Queries:**
```
supplier_list() calls:       8 × "SELECT * FROM suppliers"           = 8 queries
customer_list() calls:       5 × "SELECT * FROM customers"           = 5 queries
customer_branches_list():    3 × "SELECT * FROM cust_branches"       = 3 queries
bank_accounts_list() calls:  4 × "SELECT * FROM bank_accounts"       = 4 queries
quick_entries_list() calls:  2 × "SELECT * FROM quick_entries"       = 2 queries
                                                            TOTAL: 22 queries
```

**Optimized Approach:**
```
Load at page level (once):
- supplier_list() data:      1 query
- customer_list() data:      1 query
- bank_accounts_list() data: 1 query
- quick_entries_list() data: 2 queries (one per QE_DEPOSIT, one per QE_PAYMENT)
                             TOTAL: 5 queries
```

**Improvement:** 22 queries → 5 queries = **77% reduction** 🚀

## Architectural Constraints

### FA Helper Functions Design

The FA helper functions (e.g., `supplier_list()`, `customer_list()`) are designed as **view helpers** that:

1. Query the database
2. Generate HTML `<select>` elements
3. Return HTML string directly

**Example:**
```php
function supplier_list($name, $selected_id=null, $spec_option=false, $submit_on_change=false) {
    $sql = "SELECT supplier_id, supp_name FROM suppliers";
    $result = db_query($sql);
    
    $html = "<select name='$name'>";
    while ($row = db_fetch($result)) {
        $html .= "<option value='{$row['supplier_id']}'>{$row['supp_name']}</option>";
    }
    $html .= "</select>";
    
    return $html;
}
```

**Problem:** These functions tightly couple data retrieval with presentation, making it impossible to:
- Cache the data separately
- Reuse the data across multiple selectors
- Test the logic in isolation

### Why We Can't Simply Cache HTML

**Option 1: Cache the HTML?** ❌
```php
// Won't work - each line item needs different field names
$cached_html = supplier_list("partnerId_123", ...);
// Can't reuse for partnerId_124, partnerId_125, etc.
```

**Option 2: Extract Data Layer?** ✅ (But complex)
```php
// Would need to refactor FA core functions
$suppliers = get_all_suppliers(); // New function - data only
$html = generate_supplier_select("partnerId_$id", $suppliers); // Reusable
```

This requires modifying FA core or creating our own data access layer.

## Proposed Solution: Hybrid Approach

### Phase 1: Document the Issue (Current Phase)

Create awareness and provide recommendations for future optimization.

### Phase 2: Create Data Provider Classes (Future)

Create lightweight wrappers that separate data from presentation:

```php
namespace Ksfraser\DataProviders;

/**
 * SupplierDataProvider
 * 
 * Provides cached access to supplier data for page-level initialization
 */
class SupplierDataProvider
{
    private static ?array $cachedSuppliers = null;
    
    public static function getAll(): array
    {
        if (self::$cachedSuppliers !== null) {
            return self::$cachedSuppliers;
        }
        
        // Query once
        $sql = "SELECT supplier_id, supp_name, supp_ref FROM suppliers ORDER BY supp_name";
        $result = db_query($sql);
        
        $suppliers = [];
        while ($row = db_fetch($result)) {
            $suppliers[$row['supplier_id']] = $row;
        }
        
        self::$cachedSuppliers = $suppliers;
        return $suppliers;
    }
    
    public static function generateSelectHtml(string $fieldName, $selectedId = null): string
    {
        $suppliers = self::getAll();
        
        $html = "<select name='$fieldName'>";
        foreach ($suppliers as $id => $supplier) {
            $selected = ($id == $selectedId) ? " selected" : "";
            $html .= "<option value='$id'$selected>{$supplier['supp_name']}</option>";
        }
        $html .= "</select>";
        
        return $html;
    }
    
    public static function clearCache(): void
    {
        self::$cachedSuppliers = null;
    }
}
```

**Usage:**
```php
// At page level (once):
SupplierDataProvider::getAll(); // Loads and caches

// In each line item:
$html = SupplierDataProvider::generateSelectHtml("partnerId_$this->id", $selectedId);
// Uses cached data - no query!
```

### Phase 3: Integrate with PartnerFormFactory (Next Component)

When we extract `PartnerFormFactory`, we can:

1. Accept data providers as dependencies
2. Generate forms using cached data
3. Maintain backward compatibility with FA helper functions

```php
class PartnerFormFactory
{
    private SupplierDataProvider $supplierProvider;
    private CustomerDataProvider $customerProvider;
    private BankAccountDataProvider $bankAccountProvider;
    
    public function __construct(
        ?SupplierDataProvider $supplierProvider = null,
        ?CustomerDataProvider $customerProvider = null,
        ?BankAccountDataProvider $bankAccountProvider = null
    ) {
        // Use provided or create defaults
        $this->supplierProvider = $supplierProvider ?? new SupplierDataProvider();
        $this->customerProvider = $customerProvider ?? new CustomerDataProvider();
        $this->bankAccountProvider = $bankAccountProvider ?? new BankAccountDataProvider();
    }
    
    public function renderSupplierForm(int $lineItemId, $selectedId): string
    {
        // Use cached data
        return $this->supplierProvider->generateSelectHtml(
            "partnerId_$lineItemId", 
            $selectedId
        );
    }
}
```

## Implementation Strategy

### Immediate Actions (This Session)

1. ✅ Document this architectural issue
2. ✅ Provide performance analysis
3. ⏳ Note in PartnerFormFactory requirements

### Short-term Actions (Next 1-2 Weeks)

1. Create `DataProviders` namespace
2. Implement `SupplierDataProvider`
3. Implement `CustomerDataProvider`
4. Implement `BankAccountDataProvider`
5. Implement `QuickEntryDataProvider`
6. Add comprehensive tests (with database mocking)

### Medium-term Actions (1-2 Months)

1. Integrate data providers into `PartnerFormFactory`
2. Refactor `ViewBILineItems` to use providers
3. Add performance monitoring/metrics
4. Create migration guide for other pages

### Long-term Actions (3-6 Months)

1. Consider upstreaming improvements to FA core
2. Create generic `DataProvider` interface
3. Implement repository pattern for all FA entities
4. Create ORM-like abstraction layer

## Backward Compatibility Strategy

### During Transition

Support both patterns simultaneously:

```php
class PartnerFormFactory
{
    private $useDataProviders = true; // Feature flag
    
    public function renderSupplierForm(int $lineItemId, $selectedId): string
    {
        if ($this->useDataProviders && class_exists('SupplierDataProvider')) {
            // New optimized way
            return SupplierDataProvider::generateSelectHtml("partnerId_$lineItemId", $selectedId);
        } else {
            // Old FA way (fallback)
            return supplier_list("partnerId_$lineItemId", $selectedId, false, false);
        }
    }
}
```

### Testing Strategy

1. **Performance Tests**: Measure query counts before/after
2. **Regression Tests**: Ensure HTML output is identical
3. **Integration Tests**: Test with real FA database
4. **Load Tests**: Simulate pages with 50+ line items

## Comparison with PartnerSelectionPanel Optimization

### Similarities

| Aspect | PartnerSelectionPanel | Data Providers |
|--------|----------------------|----------------|
| Pattern | Static caching | Static caching |
| Trigger | First access | First access |
| Scope | Per request | Per request |
| Benefit | Avoid registry lookups | Avoid DB queries |
| Cost | ~200 bytes memory | ~5-50 KB per provider |

### Differences

| Aspect | PartnerSelectionPanel | Data Providers |
|--------|----------------------|----------------|
| Data source | PartnerTypeRegistry (file system) | Database queries |
| Complexity | Low (6 types, hardcoded) | High (variable records) |
| FA dependency | None (standalone) | High (uses FA db functions) |
| Testing | Easy (no external deps) | Complex (needs DB or mocks) |

### Why PartnerSelectionPanel Was Easier

1. **Fixed data size**: Always 6 partner types
2. **No external dependencies**: Self-contained
3. **Already abstracted**: Used PartnerTypeRegistry
4. **Small memory footprint**: ~200 bytes

### Why Data Providers Are Harder

1. **Variable data size**: 10-1000+ suppliers/customers/accounts
2. **FA dependencies**: Requires `db_query()`, `db_fetch()`
3. **Tightly coupled**: FA helpers mix data + presentation
4. **Larger memory footprint**: 5-50 KB per provider
5. **Cache invalidation**: Need to handle data changes

## Memory Footprint Estimates

### PartnerTypeRegistry Cache
```
6 types × ~30 bytes = ~180 bytes + array overhead = ~200 bytes
```

### Data Provider Caches (estimates)

**SupplierDataProvider:**
```
100 suppliers × ~100 bytes each = ~10 KB
```

**CustomerDataProvider:**
```
500 customers × ~80 bytes each = ~40 KB
```

**BankAccountDataProvider:**
```
10 bank accounts × ~150 bytes each = ~1.5 KB
```

**QuickEntryDataProvider:**
```
20 quick entries × ~200 bytes each = ~4 KB
```

**Total additional memory: ~55 KB**

**Trade-off Analysis:**
- Cost: 55 KB RAM
- Benefit: 15-20 database queries eliminated
- Typical query time: 5-20ms each
- Total time saved: **75-400ms per page load** ⚡
- Memory is cheap, time is expensive ✅

## Recommendations

### For Current Session (PartnerFormFactory)

When extracting PartnerFormFactory:

1. **Accept FA helper function results** as parameters (maintains compatibility)
2. **Document** that these should be cached at page level
3. **Add PHPDoc** recommending DataProvider usage in future
4. **Keep backward compatible** with existing FA helper calls

### For Next Session (Data Providers)

1. Start with **SupplierDataProvider** (simplest, most common)
2. Add comprehensive tests with **database mocking**
3. Measure actual performance improvement
4. Create **opt-in mechanism** (feature flag)

### For Future Refactoring

1. Consider **FA core contribution**: Propose data/view separation
2. Implement **cache warming**: Pre-load on page init
3. Add **cache statistics**: Monitor hit rates
4. Create **cache invalidation strategy**: Handle CRUD operations

## Conclusion

**Current State:**
- ✅ PartnerTypeRegistry: Optimized (static caching)
- ✅ VendorListManager: Already optimized (singleton with caching)
- ❌ Suppliers, Customers, Bank Accounts, Quick Entries: **Not optimized**

**Impact:**
- Displaying 20 mixed line items: **22 redundant DB queries**
- Potential time savings: **75-400ms per page load**
- Memory cost: **~55 KB** (negligible)

**Next Steps:**
1. Document in PartnerFormFactory extraction
2. Plan DataProvider implementation for next session
3. Maintain backward compatibility throughout
4. Measure and monitor performance improvements

**Architecture Principle:**
> "Load once at page level, reuse for all line items"  
> — Applied to partner types ✅, needs applying to entity lists ⏳
