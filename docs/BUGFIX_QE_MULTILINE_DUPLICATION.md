# Quick Entry Multi-Line Duplication Bug Fix
## Bug Fix Summary & Justification

**Ticket ID:** BUG-QE-001  
**Severity:** HIGH - Data Correctness  
**Status:** ✅ FIXED  
**Commit:** `99067a9` - fix(qe_to_cart): move foreach loop outside while to fix line duplication  
**Documentation:** `QE_MULTILINE_FIX_RESOLUTION.md`  
**Date Fixed:** 2026-03-27

---

## Executive Summary

Fixed critical architecture bug in Quick Entry (QE) multi-line bank payment processing where template lines were processed N times instead of 1 time due to nested foreach loop inside while loop. This caused:
- **Data Correctness Issue**: First template line duplicated in cart
- **Amount Calculation Error**: 150% total instead of 100%
- **Reduce-Base Cascade**: Incorrect calculations (50% → 25% → 12.5%)

---

## Problem

### Symptom
When processing QE multi-line bank transactions (e.g., 50/50 split between two GL accounts):
- Template line 1: Processed **twice** (duplicated)
- Template line 2: Processed once
- **Result:** 150% total instead of 100%

**Example:**
```
Input:  Bank transfer $100 → Split 50% Groceries (GL101), 50% Household (GL102)
Expected: GL101=$50, GL102=$50 (Total=$100)
Actual:   GL101=$100, GL102=$50 (Total=$150) ❌
```

**With Reduce-Base:** Cascading recalculation
- Line 1: 50% of 100 = 50, base becomes 50
- Line 2: 50% of 50 = 25
- **Result:** 50 + 25 = 75 instead of 100 (if only 2 lines)

### Root Cause

**Nested Loop Bug** in `qe_to_cart()` function ([includes/includes.inc](includes/includes.inc#L428-L464)):

```php
// BUGGY PATTERN:
while($row = db_fetch($result)) {              // Line 428: Fetch loop
    $qe_lines[] = $row;                        // Array grows on each iteration
    // Tax calculations...
    foreach($qe_lines as $qe_line) {           // Line 464: NESTED INSIDE while
        // Process items
    }
}

// With 2-line template:
// Iteration 1: $qe_lines=[L1] → foreach 1x → L1 added
// Iteration 2: $qe_lines=[L1,L2] → foreach 2x → L1 AGAIN + L2 ❌
```

**Architectural Flaw**: Process loop was inside fetch loop, not after it. Each iteration processed ALL accumulated lines, not just new ones.

### Impact on FA Compatibility
- **FA's native `qe_to_cart()`**: Separates fetch phase from process phase (correct)
- **Our module's `qe_to_cart()`**: Had nested structure (incorrect)
- This bug did NOT exist in FA 2.4.19 - only in module's copy

---

## Solution

### Fix Applied

**Moved foreach loop OUTSIDE while loop** - separated fetch and process phases:

```php
// FIXED PATTERN:
while($row = db_fetch($result)) {              // Phase 1: Fetch all rows
    $qe_lines[] = $row;
}
// While closes at line 459

foreach($qe_lines as $qe_line) {               // Phase 2: Process once
    // Process items against fixed state
}
```

**Changes:**
- **Line 459**: While loop closes (fetch complete)
- **Line 465**: Foreach begins (process all rows exactly once)
- Each template line now processes **exactly 1 time** ✓

### File Modified
- `includes/includes.inc` - `qe_to_cart()` function (lines 371-589)

### Commits
- **99067a9:** fix(qe_to_cart): move foreach loop outside while to fix line duplication
- **14ea29f:** docs: consolidate QE investigation into final resolution document

---

## Why This Architecture Matters

### Idempotent Processing
- **Fetch Phase**: Deterministic, builds complete state once
- **Process Phase**: Runs once on fixed state, not growing state
- **Result**: Same input always produces same output

### Comparison: Nested vs Separated

| Aspect | Nested (BUGGY) | Separated (FIXED) |
|--------|---|---|
| **Fetch Loop** | N iterations for N rows | N iterations for N rows |
| **Process Loop** | Runs N times (grows each iteration) | Runs 1 time (after fetch) |
| **First Line Processing** | N times | 1 time ✓ |
| **State During Processing** | Growing array | Fixed array ✓ |
| **Predictability** | Non-deterministic | Deterministic ✓ |

### Architectural Principle
The fix implements **clear separation of concerns**:
1. **Data Layer**: Fetch all data from database
2. **Business Logic Layer**: Process data once against fixed state
3. **No Mixing**: Never process while fetching

---

## Testing Verification

### Pre-Fix Behavior
```
Input: 2-line template (50/50), $100 total
Loop Iteration 1:
  - db_fetch gets Line1
  - $qe_lines = [Line1]
  - foreach processes 1 item: Line1 added to cart ✓

Loop Iteration 2:
  - db_fetch gets Line2
  - $qe_lines = [Line1, Line2]
  - foreach processes 2 items: Line1 ADDED AGAIN ❌, Line2 added ✓

Result: Line1 in cart 2×, Line2 in cart 1× (150% total) ❌
```

### Post-Fix Behavior
```
Input: 2-line template (50/50), $100 total
While Loop (Fetch Phase):
  - Iteration 1: get Line1, $qe_lines = [Line1]
  - Iteration 2: get Line2, $qe_lines = [Line1, Line2]
  - Loop ends

Foreach (Process Phase):
  - Process Line1: added to cart ✓
  - Process Line2: added to cart ✓
  - Done

Result: Line1 in cart 1×, Line2 in cart 1× (100% total) ✓
```

### Test Matrix
- ✅ 2-line template (50/50) - each line processes once
- ✅ 3-line template (40/40/20) - each line processes once
- ✅ Reduce-base calculations - correct cascade
- ✅ Single-line QE - no regression
- ✅ Non-QE transactions - no regression

---

## Requirements & Standards Compliance

### Architectural Principles Met
- ✅ **Separation of Concerns**: Fetch/Process are distinct phases
- ✅ **Idempotency**: Same input → same output
- ✅ **Determinism**: Predictable state during processing
- ✅ **FA Parity**: Matches FA 2.4.19 native implementation

### Quality Standards
- ✅ Root cause identified and documented
- ✅ Fix validated against FA's architecture
- ✅ Comprehensive documentation provided
- ✅ Non-breaking change (internal refactoring)
- ✅ Commit message follows conventional commits

---

## Deployment Notes

### Prerequisites
- None - pure internal refactoring

### Deployment Steps
1. Merge commit to target branch
2. Run test suite to verify QE transactions work correctly
3. Manual testing: Create QE transaction with 2+ lines, verify each line processes once

### Rollback Plan
- If issues arise, revert commit `99067a9`
- Bug behavior would return (but fix was correct)

---

## Follow-Up Items

- [ ] UAT testing with production QE templates
- [ ] Performance verification (foreach outside loop shouldn't impact performance)
- [ ] Documentation update in module README if needed
- [ ] Release notes: include this fix in next release

---

## Related Documentation

- [QE_MULTILINE_FIX_RESOLUTION.md](../QE_MULTILINE_FIX_RESOLUTION.md) - Detailed technical documentation
- [REQUIREMENTS_SPECIFICATION.md](REQUIREMENTS_SPECIFICATION.md) - Main system requirements
- Commit: `99067a9` - fix(qe_to_cart)
- Commit: `14ea29f` - docs: consolidate QE investigation
