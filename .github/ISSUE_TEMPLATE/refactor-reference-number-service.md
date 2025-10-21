---
name: Extract Reference Number Generation to Service
about: Refactor duplicated reference generation code following SRP
title: '[Refactoring] Extract ReferenceNumberService following Martin Fowler SRP'
labels: refactoring, code-quality, DRY, SRP
assignees: ''
---

## Summary
Reference number generation logic is duplicated across all 6 transaction handlers. Extract to a dedicated service class following Single Responsibility Principle (Martin Fowler).

## Background

### Current State: Code Duplication
Every handler has this same 4-line pattern:

**CustomerTransactionHandler.php** (lines 128-131):
```php
global $Refs;
$reference = $Refs->get_next($trans_type);
while (!is_new_reference($reference, $trans_type)) {
    $reference = $Refs->get_next($trans_type);
}
```

**Also duplicated in**:
- SupplierTransactionHandler.php
- QuickEntryTransactionHandler.php
- BankTransferTransactionHandler.php
- ManualSettlementHandler.php (if applicable)
- MatchedTransactionHandler.php (if applicable)
- `bank_import_controller.php` (lines 290-298 - already has `getNewRef()` method!)

### Existing Partial Solution
`class.bank_import_controller.php` already has a refactored version:

```php
function getNewRef( $transType )
{
    global $Refs;
    do {
        $reference = $Refs->get_next($transType);
    } while(!is_new_reference($reference, $transType));
    return $reference;
}
```

**Problem**: This is in the controller class, not a dedicated service, and handlers aren't using it.

## Requirements

### Functional Requirements
1. **Single source of truth** for reference number generation
2. **Guaranteed unique** reference for each transaction type
3. **Testable** without touching database or global state (mockable)
4. **Type-safe** with proper PHP type hints

### Non-Functional Requirements
1. Maintain backward compatibility with existing handlers
2. Follow PSR-4 autoloading standards
3. Include comprehensive unit tests
4. Update all handlers to use new service

## Proposed Implementation

### Step 1: Create Service Class
**File**: `src/Ksfraser/FaBankImport/Services/ReferenceNumberService.php`

```php
<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * Reference Number Service
 * 
 * Single Responsibility: Generate guaranteed unique reference numbers for transactions.
 * 
 * Follows Martin Fowler's SRP pattern - this class does ONE thing and does it well.
 * Extracted from duplicated code in all 6 transaction handlers.
 * 
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */
class ReferenceNumberService
{
    /**
     * Reference generator (FrontAccounting's $Refs global)
     * 
     * @var object|null
     */
    private $referenceGenerator;

    /**
     * Constructor
     * 
     * @param object|null $referenceGenerator Optional reference generator for testing
     */
    public function __construct($referenceGenerator = null)
    {
        // Allow dependency injection for testing, otherwise use FA global
        $this->referenceGenerator = $referenceGenerator;
    }

    /**
     * Get guaranteed unique reference number for transaction type
     * 
     * Continuously generates references until a unique one is found.
     * This ensures no duplicate references are created in FrontAccounting.
     * 
     * @param int $transType Transaction type constant (ST_CUSTPAYMENT, ST_SUPPAYMENT, etc.)
     * @return string Unique reference number
     * 
     * @example
     * $service = new ReferenceNumberService();
     * $reference = $service->getUniqueReference(ST_CUSTPAYMENT);
     * // Returns: "12345" or similar unique ref
     */
    public function getUniqueReference(int $transType): string
    {
        // Use injected generator or FA global
        $generator = $this->referenceGenerator ?? $this->getGlobalRefsObject();

        do {
            $reference = $generator->get_next($transType);
        } while (!is_new_reference($reference, $transType));

        return $reference;
    }

    /**
     * Get FrontAccounting's global $Refs object
     * 
     * Separated for testability - can be mocked in tests
     * 
     * @return object FA References object
     */
    protected function getGlobalRefsObject()
    {
        global $Refs;
        return $Refs;
    }
}
```

### Step 2: Update AbstractTransactionHandler
**File**: `src/Ksfraser/FaBankImport/Handlers/AbstractTransactionHandler.php`

Add service as protected property:

```php
abstract class AbstractTransactionHandler implements TransactionHandlerInterface
{
    protected ReferenceNumberService $referenceService;
    
    public function __construct(
        ReferenceNumberService $referenceService = null
    ) {
        // Allow injection for testing, create default otherwise
        $this->referenceService = $referenceService ?? new ReferenceNumberService();
    }
    
    // ... existing methods ...
}
```

### Step 3: Update CustomerTransactionHandler
**File**: `src/Ksfraser/FaBankImport/Handlers/CustomerTransactionHandler.php`

**BEFORE** (lines 128-131):
```php
global $Refs;
$reference = $Refs->get_next($trans_type);
while (!is_new_reference($reference, $trans_type)) {
    $reference = $Refs->get_next($trans_type);
}
```

**AFTER**:
```php
$reference = $this->referenceService->getUniqueReference($trans_type);
```

### Step 4: Update Remaining Handlers
Apply same change to:
- SupplierTransactionHandler.php
- QuickEntryTransactionHandler.php
- BankTransferTransactionHandler.php
- ManualSettlementHandler.php (if applicable)
- MatchedTransactionHandler.php (if applicable)

**Reduction**: 4 lines → 1 line per handler = **3 lines removed × 6 handlers = 18 lines eliminated**

### Step 5: Create Unit Tests
**File**: `tests/unit/Services/ReferenceNumberServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;

class ReferenceNumberServiceTest extends TestCase
{
    public function test_returns_unique_reference_on_first_try()
    {
        // Mock reference generator
        $mockRefs = $this->createMock(\stdClass::class);
        $mockRefs->method('get_next')->willReturn('REF-001');
        
        // Mock is_new_reference to return true (unique)
        $service = new ReferenceNumberService($mockRefs);
        
        // Note: Would need to mock global is_new_reference function
        // This demonstrates the testing approach
        
        $reference = $service->getUniqueReference(ST_CUSTPAYMENT);
        $this->assertEquals('REF-001', $reference);
    }

    public function test_retries_until_unique_reference_found()
    {
        // Mock reference generator that returns non-unique then unique
        $mockRefs = $this->createMock(\stdClass::class);
        $mockRefs->method('get_next')
            ->willReturnOnConsecutiveCalls('REF-001', 'REF-002', 'REF-003');
        
        // Mock is_new_reference to fail twice, succeed third time
        // (Would need global function mocking library)
        
        $service = new ReferenceNumberService($mockRefs);
        $reference = $service->getUniqueReference(ST_CUSTPAYMENT);
        
        // Should return third attempt
        $this->assertEquals('REF-003', $reference);
    }

    public function test_handles_different_transaction_types()
    {
        $mockRefs = $this->createMock(\stdClass::class);
        $mockRefs->method('get_next')
            ->willReturnCallback(function($type) {
                return "REF-{$type}-001";
            });
        
        $service = new ReferenceNumberService($mockRefs);
        
        $custRef = $service->getUniqueReference(ST_CUSTPAYMENT);
        $suppRef = $service->getUniqueReference(ST_SUPPAYMENT);
        
        $this->assertStringContainsString('CUSTPAYMENT', $custRef);
        $this->assertStringContainsString('SUPPAYMENT', $suppRef);
    }
}
```

### Step 6: Update TransactionProcessor Auto-Discovery
**File**: `src/Ksfraser/FaBankImport/TransactionProcessor.php`

Update handler instantiation to inject service:

```php
private function discoverAndRegisterHandlers(): void
{
    $referenceService = new ReferenceNumberService();
    
    $handlerClasses = [
        'SupplierTransactionHandler',
        'CustomerTransactionHandler',
        // ... rest ...
    ];

    foreach ($handlerClasses as $className) {
        $fqcn = "Ksfraser\\FaBankImport\\Handlers\\{$className}";
        
        if (class_exists($fqcn)) {
            // Inject reference service
            $handler = new $fqcn($referenceService);
            
            if ($handler instanceof TransactionHandlerInterface) {
                $this->registerHandler($handler);
            }
        }
    }
}
```

## Benefits

### Code Quality
- ✅ **DRY** - One place for reference generation logic (was 7 places)
- ✅ **SRP** - Service has single responsibility: generate unique references
- ✅ **Testable** - Can inject mock for unit testing
- ✅ **Type Safe** - Proper type hints and return types
- ✅ **Discoverable** - Clear name and location

### Maintainability
- ✅ **Single Point of Change** - Update algorithm once, affects all handlers
- ✅ **Consistent** - All handlers use same implementation
- ✅ **Future-Proof** - Easy to add caching, logging, metrics

### Statistics
- **Lines Reduced**: 18 lines (4 → 1 per handler × 6 handlers)
- **Files Improved**: 7 files (6 handlers + AbstractTransactionHandler)
- **Duplication Eliminated**: 100% (7 copies → 1 service)

## Testing Plan

### Unit Tests (8 tests minimum)
1. ✅ Returns reference on first attempt when unique
2. ✅ Retries until unique reference found
3. ✅ Handles different transaction types
4. ✅ Constructor accepts injected generator
5. ✅ Constructor creates default generator if none provided
6. ✅ Throws exception if max retries exceeded (optional safety)
7. ✅ Respects transaction type parameter
8. ✅ Returns string type

### Integration Tests (2 tests)
1. ✅ Handler uses service and creates FA transaction
2. ✅ Multiple handlers don't create duplicate refs

### Regression Tests
1. ✅ All existing handler tests still pass
2. ✅ TransactionProcessor tests still pass (14 tests, 50 assertions)

## Acceptance Criteria
- [ ] ReferenceNumberService class created with proper namespace
- [ ] AbstractTransactionHandler updated to inject service
- [ ] All 6 handlers updated to use service
- [ ] Reference generation code removed from handlers (18 lines eliminated)
- [ ] Unit tests created (8+ tests)
- [ ] Integration tests pass
- [ ] All existing handler tests pass (70 tests)
- [ ] TransactionProcessor tests pass (14 tests, 50 assertions)
- [ ] HANDLER_VERIFICATION.md updated
- [ ] No regressions in transaction processing

## Effort Estimate
**1-2 hours** (1 developer)

## Priority
**High** - Code quality improvement, eliminates duplication, improves testability

## Related Issues
- See HANDLER_VERIFICATION.md - "Outstanding TODOs" section
- Related to original switch statement refactoring (STEP 3-9)
- Mentioned in STEP4_SRP_ANALYSIS.md as planned refactoring

## Migration Path
1. ✅ Create ReferenceNumberService class
2. ✅ Update AbstractTransactionHandler with service
3. ✅ Update TransactionProcessor auto-discovery
4. ✅ Update handlers one by one (can test each independently)
5. ✅ Run tests after each handler update
6. ✅ Remove `bank_import_controller::getNewRef()` once handlers migrated

## Notes
- This follows Martin Fowler's "Extract Class" refactoring
- Service is stateless (no instance variables except injected dependency)
- Could extend in future to add metrics, caching, or custom reference formats
- Maintains backward compatibility - handlers work same way from outside
