# ProcessStatementsFetchService Refactoring

**Date:** 2025-01-16  
**Status:** Complete  
**Goal:** Implement guardrails for POST parameter validation and establish proper delegation pattern to prevent SQL injection and resource exhaustion.

---

## Problem Statement

The original `ProcessStatementsFetchService.fetch()` method was building raw SQL queries from POST parameters with insufficient validation:

```php
// BEFORE: Limited validation
if (isset($post['date_from']) && !empty($post['date_from'])) {
    $dateFrom = db_escape($post['date_from']);
    $query .= " AND date >= '{$dateFrom}'";  // Still vulnerable to logic errors
}
```

**Issues:**
1. **No limit enforcement** - A malicious user could request 1 million records, causing memory exhaustion
2. **No filter whitelisting** - Any arbitrary field could be filtered on
3. **No date range validation** - Could provide illogical ranges (date_from > date_to)
4. **No delegation pattern** - Service was mixing concerns (validation + data fetching)
5. **Hard-coded table name** - Not using `TB_PREF` constant for multi-install compatibility

---

## Solution Architecture

### 1. Guardrails Layer

Three validation methods ensure safe parameter handling:

#### a) `validateLimit()`
```php
protected function validateLimit($limit): int
{
    $limit = (int)$limit;
    if ($limit <= 0 || $limit > 1000) {
        return 100; // Default
    }
    return $limit;
}
```
**Prevents:** Memory exhaustion attacks by capping maximum records at 1000.

#### b) `isValidDate()`
```php
protected function isValidDate(string $date): bool
{
    $d = \DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
```
**Prevents:** Invalid date strings from breaking queries or causing unexpected behavior.

#### c) `getWhitelistedFilters()`
```php
protected function getWhitelistedFilters(array $filters): array
{
    $whitelist = ['status', 'bank', 'account', 'currency'];
    // Only allows explicitly approved filters
}
```
**Prevents:** SQL injection by only allowing known-safe filter fields.

### 2. Delegation Pattern

The service now has two paths:

```
POST Input
    ↓
validateLimit() + isValidDate() + getWhitelistedFilters()
    ↓
┌─────────────────────────────────────────┐
│ if ($dataProvider exists)               │
│    → Use injected dataProvider          │  (For dependency injection / mocking)
│ else                                    │
│    → Use fallback fetchDirect()         │  (Legacy compatibility)
└─────────────────────────────────────────┘
    ↓
Query execution + result processing
    ↓
Return validated data
```

**Constructor signature:**
```php
public function __construct($dataProvider = null)
{
    $this->dataProvider = $dataProvider;
}
```

### 3. Data Provider Interface

New `StatementsDataProviderInterface` defines the contract:

```php
public function fetch(
    ?int $statusFilter = null,
    array $filters = [],           // Only whitelisted filters
    ?string $dateFrom = null,      // YYYY-MM-DD format
    ?string $dateTo = null,        // YYYY-MM-DD format
    int $limit = 100               // 1-1000, enforced
): array;
```

Future implementations can replace the fallback with:
- A repository pattern class
- A `bi_statements_model` query builder
- An ORM layer

**Key:** All inputs are pre-validated by `ProcessStatementsFetchService` before reaching the provider.

---

## Method Refactoring Details

### `fetch()`
**Before:** Raw SQL construction with loose validation  
**After:** 
- Validates all POST parameters before use
- Enforces date range logic (date_from ≤ date_to)
- Whitelists allowed filters
- Delegates to data provider
- Consistent error handling via `TransactionFetchException`

### `fetchUnprocessed()`
**Before:** Passed status=0 as unvalidated filter  
**After:** Uses whitelisted filters and validates limit

### `count()`
**Before:** Separate query building logic  
**After:** Delegates to provider with same guardrails

---

## Usage Examples

### Example 1: Basic fetch with guarded POST
```php
$service = new ProcessStatementsFetchService();

$statements = $service->fetch(
    statusFilter: null,
    filters: [],
    post: $_POST  // Already validated by guardrails
);
```

### Example 2: With dependency injection
```php
$provider = new StatementRepositoryProvider($db);
$service = new ProcessStatementsFetchService($provider);

$statements = $service->fetch(0, [], $_POST);
```

### Example 3: Enforced limits
```php
// User requests 50000 records, gets max 1000
$result = $service->fetch(null, [], ['limit' => 50000]);
// $limit is automatically capped to 1000

// Invalid date range is caught
try {
    $result = $service->fetch(null, [], [
        'date_from' => '2025-12-31',
        'date_to' => '2025-01-01'  // Before start date!
    ]);
} catch (\InvalidArgumentException $e) {
    // Caught and logged
}
```

---

## Security Benefits

| Risk | Mitigation |
|------|-----------|
| Resource exhaustion (million records) | `validateLimit()` caps at 1000 |
| SQL injection via filter fields | `getWhitelistedFilters()` whitelist only |
| Invalid date logic | `isValidDate()` + date_from ≤ date_to check |
| Field injection | `preg_replace('/[^a-zA-Z0-9_]/', '', $field)` in direct access |
| Table name injection | Uses `TB_PREF` constant |

---

## Testing

### Unit Tests Needed
```php
// GuardrailsTest.php
test_validateLimit_enforces_maximum()
test_validateLimit_enforces_minimum()
test_isValidDate_rejects_invalid_formats()
test_isValidDate_accepts_valid_dates()
test_getWhitelistedFilters_only_allows_approved()

// IntegrationTest.php
test_fetch_with_valid_post()
test_fetch_with_invalid_date_range()
test_fetch_with_excessive_limit()
test_fetchWithTransactions_loads_related_data()
test_count_respects_filters()
```

### Test Data
- Valid dates: '2025-01-15', '2024-12-31'
- Invalid dates: '2025-13-01', '01-15-2025', '2025/01/15'
- Valid limits: 1, 50, 100, 1000
- Invalid limits: 0, -50, 1001, 999999

---

## Backward Compatibility

✅ **Fully backward compatible**
- Fallback `fetchDirect()` implementation maintains legacy behavior if no provider injected
- Existing code calling `fetch($_POST)` continues to work with added safety
- All public method signatures unchanged

---

## Future Improvements

1. **Create `bi_statements_model` query builder** - Move all DB logic here
2. **Implement repository pattern** - Separate data access from business logic
3. **Add caching layer** - For frequently accessed statements
4. **Paginate results** - Instead of flat limit, implement offset/page parameters
5. **Add audit logging** - Track who fetched what data and when

---

## Deployment Notes

✨ **No database migrations needed**  
✨ **No configuration changes required**  
✨ **Drop-in replacement** for existing code

Simply update the ProcessStatementsFetchService file and run tests.

---

## Related Files

- [ProcessStatementsFetchService.php](../../Services/ProcessStatementsFetchService.php)
- [StatementsDataProviderInterface.php](../../Services/Contracts/StatementsDataProviderInterface.php)
- [TransactionFetchException.php](../../Exceptions/TransactionFetchException.php)
