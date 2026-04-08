# Duplicate Detection Refactoring - Executive Summary

## Overview

The `DuplicateDetectionService` implements a **hardcoded 3-level duplicate detection strategy**. This analysis breaks down the current architecture and provides a roadmap for converting it to a **dynamic Chain of Responsibility pattern**.

---

## Quick Reference: Current vs. Proposed

| Aspect | Current | Proposed |
|--------|---------|----------|
| **Pattern** | Hardcoded Orchestrator | Chain of Responsibility |
| **Matchers** | 3 fixed (hardcoded) | N dynamic (registered) |
| **Interfaces** | None | DuplicateMatcherInterface |
| **Result Types** | Variable (mixed) | Unified DuplicateMatchResult |
| **Add Level 4?** | Modify service.detect() | Register new matcher |
| **Testing** | All dependencies required | Test matchers in isolation |
| **Configuration** | Constructor only | Config array or factory |

---

## Component Breakdown

### 1. DirectCodeMatcher (Level 1)
- **Current Method:** `findAndCompare()` + `find()`
- **Query:** `SELECT * FROM bi_transactions WHERE transactionCode=? AND acctid=?`
- **Returns:** Array with match + field differences
- **Confidence:** 100% (or 90% if fields differ)
- **Proposed Role:** Implement `DuplicateMatcherInterface`, priority=0

### 2. FuzzyMatcher (Level 2)
- **Current Method:** `find()`
- **Query:** Date exact + Amount ±$0.01 + (Merchant OR Memo OR AccountName)
- **Returns:** Array of candidates
- **Confidence:** 60% (fuzzy)
- **Proposed Role:** Implement `DuplicateMatcherInterface`, priority=10

### 3. DuplicateRulesProvider (Level 3)
- **Current Method:** `findMatchingRule()`, `ruleMatches()`, `patternMatches()`
- **Query:** `SELECT * FROM bi_duplicate_rules WHERE active=1`
- **Returns:** Single rule record
- **Function:** Policy adapter (whitelist rules)
- **Proposed Role:** Become `RulesMatcher`, priority=20

### 4. DuplicateCheckResult (Output)
- **Current:** Encapsulates final decision (SKIP|REVIEW|IMPORT|ALLOWED_REPEAT)
- **Proposed:** Unchanged (public API remains stable)

### 5. DuplicateReviewHandler (Phase 2)
- **Current:** Stores flagged duplicates for user review
- **Proposed:** Unchanged (external handler)

---

## Current Flow (Hardcoded Decision Tree)

```
detect($transaction)
  │
  ├─→ Level 1: directMatcher->findAndCompare()
  │   ├─ Found? → Return exactMatch() [SKIP or REVIEW]
  │   └─ Not found? → Continue
  │
  ├─→ Level 2: fuzzyMatcher->find()
  │   ├─ Found? → Continue to Level 3
  │   └─ Not found? → Return notDuplicate() [IMPORT]
  │
  └─→ Level 3: rulesProvider->findMatchingRule()
      ├─ Rule + allow? → Return fuzzyMatchAllowed() [ALLOWED_REPEAT]
      └─ No rule? → Return fuzzyMatchNeedsReview() [REVIEW]
```

**Problem:** Orchestration is embedded in service.detect(). Adding Level 4 requires modifying the service.

---

## Hardcoded Elements

### 1. Hardcoded Injections (Constructor)
```php
$this->directMatcher = $directMatcher ?? new DirectCodeMatcher();
$this->fuzzyMatcher = $fuzzyMatcher ?? new FuzzyMatcher();
$this->rulesProvider = $rulesProvider ?? new DuplicateRulesProvider();
```
**Issue:** Can't easily disable/reorder matchers.

### 2. Hardcoded Levels in detect()
```php
if ($codeMatchResult) {           // ← Level 1 exclusive
if (empty($fuzzy)) {               // ← Level 2 only if Level 1 fails
$rule = $this->rulesProvider->...  // ← Level 3 only if Level 2 succeeds
```
**Issue:** Can't swap/reorder levels or add new ones.

### 3. Hardcoded Query Criteria
- **DirectCodeMatcher:** Fields to compare = hardcoded array
- **FuzzyMatcher:** Amount tolerance = 0.01 (hardcoded), match fields = hardcoded
- **DuplicateRulesProvider:** Rule matching fields = hardcoded

### 4. No Common Interface
- DirectCodeMatcher has `find()` and `findAndCompare()`
- FuzzyMatcher has `find()`
- DuplicateRulesProvider has `findMatchingRule()`
- **Issue:** Can't swap implementations easily.

---

## What Needs to Change

### Phase 1: Create Interfaces & Unify Results

**Create DuplicateMatcherInterface:**
```php
interface DuplicateMatcherInterface {
    public function match(array $transaction): ?DuplicateMatchResult;
    public function getPriority(): int;
    public function getId(): string;
}
```

**Create DuplicateMatchResult (unified):**
```php
class DuplicateMatchResult {
    - matcherId: string
    - confidence: 0-100
    - matchedRecord: ?array
    - metadata: array
}
```

**Costs:**
- 2 new files (interface + result VO)
- ~100 lines total
- **Time:** 1-2 hours

### Phase 2: Refactor Matchers to Implement Interface

**DirectCodeMatcher:**
```php
public function match(array $transaction): ?DuplicateMatchResult {
    // Keeps find() and getFieldsThatDiffer() private
    // Returns DuplicateMatchResult::exactCodeMatch()
}
```

**FuzzyMatcher:**
```php
public function match(array $transaction): ?DuplicateMatchResult {
    // Keeps find() private
    // Returns DuplicateMatchResult::fuzzyMatch()
}
```

**Costs:**
- ~30 lines per matcher
- ~1 hour

### Phase 3: Build Chain Orchestrator

**Create DuplicateDetectionChain:**
```php
class DuplicateDetectionChain {
    private array $matchers = [];
    
    public function addMatcher(DuplicateMatcherInterface $matcher): self
    public function detect(array $transaction): DuplicateCheckResult
}
```

**Costs:**
- ~200 lines
- **Time:** 1-2 hours

### Phase 4: Create Factory (Optional)

**Create DuplicateDetectionChainFactory:**
```php
class DuplicateDetectionChainFactory {
    public static function fromArray(array $config): DuplicateDetectionChain
    public static function createDefault(): DuplicateDetectionChain
}
```

**Costs:**
- ~100 lines
- **Time:** 1 hour

### Phase 5: Backward Compatibility

**Refactor DuplicateDetectionService to delegate to chain:**
```php
class DuplicateDetectionService {
    private $chain;
    
    public function detect(array $transaction): DuplicateCheckResult {
        return $this->chain->detect($transaction);  // ← Delegates
    }
}
```

**Costs:**
- ~20 lines changed
- **Time:** 30 minutes

---

## Total Implementation Effort

| Phase | Component | Files | LOC | Hours |
|-------|-----------|-------|-----|-------|
| 1 | Interfaces & Result VO | 2 | 100 | 1.5 |
| 2 | Refactor Matchers | 2 | 60 | 1 |
| 3 | Chain Orchestrator | 1 | 200 | 2 |
| 4 | Factory (optional) | 1 | 100 | 1 |
| 5 | Backward Compatibility | 1 | 20 | 0.5 |
| **Total** | | **7** | **480** | **6** |

**Testing:** Add ~200 LOC of unit tests (+2 hours)

**Total Time:** ~8 hours (including testing)

---

## Key Benefits

### 1. Extensibility
```php
// Current: Modify service.detect() to add Level 4
// Proposed: Just register a new matcher
$chain->addMatcher(new MyCustomMatcher(priority: 5));
```

### 2. Configurability
```php
// Load from database or config file
$config = $configService->loadDuplicateDetectionConfig();
$chain = DuplicateDetectionChainFactory::fromArray($config);
```

### 3. Testability
```php
// Test matcher in isolation (no mocks)
$matcher = new DirectCodeMatcher();
$result = $matcher->match($transaction);

// vs. currently: Must mock all dependencies
```

### 4. Flexibility
```php
// Reorder matchers by changing priority
$chain->addMatcher(new FuzzyMatcher(priority: 1));     // Check first
$chain->addMatcher(new DirectCodeMatcher(priority: 2)); // Check second
```

### 5. Feature Flags
```php
// Enable/disable matchers dynamically
if ($featureFlags->isEnabled('new_rules_matcher')) {
    $chain->addMatcher(new RulesMatcher(priority: 20));
}
```

---

## Migration Path (Zero Breaking Changes)

### Day 1: Build New Pattern In Parallel
```php
// Old code keeps working
$service = new DuplicateDetectionService();
$service->detect($transaction);

// New code available for testing
$chain = new DuplicateDetectionChain();
$chain->detect($transaction);
```

### Day 2-3: Service Delegates to Chain
```php
class DuplicateDetectionService {
    public function __construct(...) {
        $this->chain = new DuplicateDetectionChain();
        // Register matchers in order
    }
    
    public function detect(array $transaction): DuplicateCheckResult {
        return $this->chain->detect($transaction);  // ← Delegates
    }
}
```

### Week 2+: Migrate Callers Gradually
```php
// Old callers still work (backward compatible)
$service->detect($transaction);

// Refactored callers use chain directly
$chain->detect($transaction);
```

### End of Cycle: Deprecate Service (Optional)
```php
/**
 * @deprecated Use DuplicateDetectionChain instead
 * @see DuplicateDetectionChain
 */
class DuplicateDetectionService { ... }
```

---

## Files Affected

### New Files (Create)
1. `Contracts/DuplicateMatcherInterface.php`
2. `DuplicateMatchResult.php`
3. `DuplicateDetectionChain.php`
4. `DuplicateDetectionChainFactory.php`
5. `RulesMatcher.php` (extract rules logic)

### Modified Files (Refactor)
1. `DirectCodeMatcher.php` - Add interface implementation
2. `FuzzyMatcher.php` - Add interface implementation
3. `DuplicateDetectionService.php` - Delegate to chain (optional)

### Unchanged Files
1. `DuplicateCheckResult.php` - Still the public API
2. `DuplicateRulesProvider.php` - Helper stays as is
3. `DuplicateReviewHandler.php` - External handler
4. Tests - Will improve (not changed, only enhanced)

---

## Testing Strategy

### Unit Tests (Isolated Matchers)
```php
// Test each matcher independently
public function testDirectCodeMatcherFindsExactDuplicate() { ... }
public function testDirectCodeMatcherDetectsFieldMismatch() { ... }
public function testFuzzyMatcherFindsCandidate() { ... }
```

### Integration Tests (Chain)
```php
// Test chain with multiple matchers
public function testChainStopsAtFirstMatch() { ... }
public function testChainSkipsDisabledMatchers() { ... }
public function testChainRespectsPriority() { ... }
```

### Backward Compatibility Tests
```php
// Old service still works
public function testServiceDelegatesAndProducesCorrectResult() { ... }
```

---

## Recommendations

### Immediate (Next Sprint)
1. ✅ Create interfaces and unified result VO (Phase 1)
2. ✅ Refactor matchers to implement interface (Phase 2)
3. ✅ Build chain orchestrator (Phase 3)
4. ✅ Add comprehensive unit tests

### Short-term (Following Sprint)
1. Create factory for configuration (Phase 4)
2. Migrate service to delegate (Phase 5)
3. Update all callers to use chain directly

### Medium-term (Next Quarter)
1. Add feature flags for experimental matchers
2. Build matcher configuration UI/dashboard
3. Add matcher performance metrics
4. Extract matcher strategy to database

### Long-term (Roadmap)
1. Support custom matchers (plugin architecture)
2. Machine learning matcher (optional)
3. Real-time configuration updates (no restart)
4. A/B testing framework for matchers

---

## Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| Breaking changes | Low | High | Use adapter pattern in Service |
| Performance degradation | Low | Medium | Benchmark before/after |
| Integration issues | Medium | Medium | Comprehensive testing |
| Configuration complexity | Medium | Low | Provide factory defaults |

---

## Success Criteria

✅ **All tests passing** - No regression  
✅ **New matchers added easily** - Less than 30 minutes  
✅ **Matchers testable in isolation** - No mock dependencies  
✅ **Old API still works** - Backward compatible  
✅ **Configuration externalized** - Database or config file  
✅ **Matchers reorderable** - Priority system working  

---

## Related Documents

1. **DUPLICATE_DETECTION_ANALYSIS.md** - Detailed component analysis
2. **DUPLICATE_DETECTION_ARCHITECTURE_DIAGRAMS.md** - Visual comparisons
3. **DUPLICATE_DETECTION_CODE_EXAMPLES.md** - Concrete PHP code

---

## Next Steps

1. **Review this analysis** with the team
2. **Prioritize the phases** (recommend doing all 5 in one cycle)
3. **Assign implementation** (1 developer, ~1 week)
4. **Create acceptance tests** for the new chain
5. **Plan migration** of callers
6. **Deploy with feature flag** (new chain behind flag)

---

**Document Version:** 1.0  
**Created:** 2026-04-04  
**Status:** Ready for Implementation  
**Estimated Timeline:** 1-2 sprints (with testing)
