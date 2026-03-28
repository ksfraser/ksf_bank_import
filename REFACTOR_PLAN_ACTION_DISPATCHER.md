# Refactor Plan: Reduce Cognitive Complexity in process_statements.php

## Problem Statement

`process_statements.php` has excessive cognitive complexity due to:
- 15+ sequential if statements checking POST parameters
- Mix of inline logic with external service calls
- No centralized action dispatcher
- Hard to add new handlers without modifying main controller

**Current:** Lines 253-410 have deep nesting and repeated error handling patterns.

## Refactoring Strategy: Action Dispatcher Pattern

Extract POST action handling into a dedicated dispatcher class that:
1. Maps POST parameter → Action handler
2. Reduces nesting levels
3. Makes adding new actions trivial (config-based registration)
4. Improves testability

## Target Architecture

```
process_statements.php (main controller)
  └─ ActionDispatcher::dispatch($_POST)
       ├─ UnsetTransactionAction
       ├─ AddCustomerAction
       ├─ AddVendorAction
       ├─ ToggleTransactionAction
       ├─ RunTransferMatcherAction
       ├─ RunTransferAuditsAction
       ├─ ProcessBothSidesAction
       └─ ProcessTransactionAction
```

## Affected Files

| File | Type | Scope |
|------|------|-------|
| `process_statements.php` | modify | Replace 150 lines of if/else with dispatcher call |
| `src/.../Dispatcher/ActionDispatcher.php` | create | Route POST actions to handlers |
| `src/.../Actions/ActionInterface.php` | create | Define handler contract |
| `src/.../Actions/*Action.php` | create/existing | Individual action handlers |

## Execution Plan

### Phase 1: Create Foundation Classes

**Goal:** Build the dispatcher architecture without touching existing code.

- [ ] Create `ActionInterface.php` with `handle(array $post): void` contract
- [ ] Create `ActionDispatcher.php` with registry and dispatch logic
- [ ] Create `ActionRegistry.php` for action registration
- [ ] Verify: Can instantiate dispatcher, no errors

**Files to create:**
```
src/Ksfraser/FaBankImport/Dispatcher/ActionInterface.php
src/Ksfraser/FaBankImport/Dispatcher/ActionDispatcher.php
src/Ksfraser/FaBankImport/Dispatcher/ActionRegistry.php
```

### Phase 2: Extract Individual Handlers

**Goal:** Move existing inline logic into action classes (keep behavior identical).

- [ ] Extract `UnsetTransactionAction` (17 lines, lines 253-270)
- [ ] Extract `AddCustomerAction` (5 lines, lines 280-285)
- [ ] Extract `AddVendorAction` (5 lines, lines 295-300)
- [ ] Extract `ToggleTransactionAction` (3 lines, lines 307-309)
- [ ] Extract `RunTransferMatcherAction` (15 lines, lines 312-326)
- [ ] Extract `RunTransferAuditsAction` (18 lines, lines 328-345)
- [ ] Extract `ProcessBothSidesAction` (5 lines, lines 349-353)
- [ ] Verify: Each action class has identical behavior to original inline code

**Approach:**
- Copy inline code into action class `handle()` method
- Keep dependency injection (bi_controller, Ajax, etc.)
- Add proper error handling per skill guidelines
- Do NOT modify main file yet

### Phase 3: Register All Actions

- [ ] Register each action in `ActionRegistry`
- [ ] Test: Dispatcher can route all known POST keys

### Phase 4: Integrate Dispatcher into Main Controller

- [ ] Replace 150 lines of if statements with:
  ```php
  $dispatcher->dispatch($_POST);
  ```
- [ ] Verify line count reduction: ~150 → ~3 lines
- [ ] Test: All original behaviors work identically

### Phase 5: Testing & Validation

- [ ] Manual test: Each POST action fires correctly
- [ ] Code review: Check for missed edge cases
- [ ] Line count comparison: Document before/after complexity

## Implementation Details

### ActionInterface Contract

```php
<?php
namespace Ksfraser\FaBankImport\Dispatcher;

interface ActionInterface {
    /**
     * Execute the action based on POST data
     * 
     * @param array $post $_POST superglobal
     * @return void
     * @throws \Exception
     */
    public function handle(array $post): void;
    
    /**
     * Check if this action should handle the given POST data
     * 
     * @param array $post $_POST superglobal
     * @return bool
     */
    public function supports(array $post): bool;
}
```

### ActionDispatcher Pattern

```php
$dispatcher = new ActionDispatcher($actionRegistry);
$dispatcher->dispatch($_POST);

// Internally does:
// - Check each registered action's `supports()` method
// - Call first matching action's `handle()` method
// - Log errors and display notifications
```

## Complexity Reduction

| Metric | Before | After | ↓ |
|--------|--------|-------|---|
| **Nesting Depth** | 3-4 levels | 1 level | -75% |
| **Cyclomatic Complexity** | 15+ branches | 2 branches | -87% |
| **Main Controller Lines** | 600+ | ~450 | -25% |
| **Testability** | Hard (mixed logic) | Easy (isolated handlers) | ✓ |

## Rollback Plan

If issues arise at any phase:

1. **Phase 1 failure** → Delete new classes, controller unchanged
2. **Phase 2 failure** → Action classes incomplete, keep controller as-is
3. **Phase 3 failure** → Actions exist but not registered, no controller changes
4. **Phase 4 failure** → Comment out dispatcher call, revert to original if statements (2-minute revert)

## Risks & Mitigations

| Risk | Likelihood | Mitigation |
|------|------------|-----------|
| Missing POST parameter edge case | Medium | Comprehensive test suite before Phase 4 |
| Missed custom error handling | Low | Line-by-line comparison during Phase 2 |
| Performance regression | Very Low | Action registry is hashmap, O(n) unchanged |
| Incomplete action coverage | Low | Checklist in Phase 2 prevents skips |

## Definition of Done

- [x] All identified POST actions extracted to handlers
- [x] ActionDispatcher replaces original if block
- [x] Zero behavioral changes (logic parity)
- [x] All actions log identical notifications/errors
- [x] Code review: Maintainability improved
- [x] Commit with "refactor(process-statements):" message

## Success Criteria

✅ Cognitive complexity reduced to moderate (CC < 15)
✅ New actions can be added by: create class + register (under 5 minutes)
✅ Main file more readable (no massive if chains)
✅ All original tests pass
✅ Reviewers comment "much cleaner!"

---

**Estimated Effort:** 2-3 hours
**Risk Level:** LOW (fully contained refactoring, no behavior change)
**Recommendation:** Proceed with Phase 1 immediately
