# Duplicate Detection Architecture Diagrams

## CURRENT ARCHITECTURE (Hardcoded Levels)

```
┌─────────────────────────────────────────────────────────────────┐
│  DuplicateDetectionService                                      │
│  ─────────────────────────────────────────────────────────────  │
│  public detect(array $transaction): DuplicateCheckResult       │
│                                                                  │
│  Orchestration (HARDCODED):                                     │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ if ($codeMatchResult = directMatcher->findAndCompare()) │  │
│  │    return exactMatch()                    ← LEVEL 1     │  │
│  │                                                          │  │
│  │ if ($fuzzyMatches = fuzzyMatcher->find())              │  │
│  │    if ($rule = rulesProvider->findMatchingRule())      │  │
│  │        if (ruleAllowsDuplicates)                       │  │
│  │            return fuzzyMatchAllowed()   ← LEVEL 2+3   │  │
│  │        else                                             │  │
│  │            return fuzzyMatchNeedsReview()              │  │
│  │    else                                                 │  │
│  │        return fuzzyMatchNeedsReview()                  │  │
│  │                                                          │  │
│  │ return notDuplicate()                                  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  private $directMatcher        ← Injected or new DirectCodeMatcher()
│  private $fuzzyMatcher         ← Injected or new FuzzyMatcher()
│  private $rulesProvider        ← Injected or new DuplicateRulesProvider()
└─────────────────────────────────────────────────────────────────┘
       ↓             ↓                      ↓
       │             │                      │
    HARD            HARD                  HARD
   CODED           CODED                  CODED
       │             │                      │
       ↓             ↓                      ↓
   ┌────────┐   ┌───────────┐   ┌──────────────────┐
   │ Level1 │   │  Level 2  │   │     Level 3      │
   ├────────┤   ├───────────┤   ├──────────────────┤
   │Direct  │   │  Fuzzy    │   │ Whitelist Rules  │
   │Code    │   │  Match    │   │ Provider         │
   │Matcher │   │           │   │                  │
   │        │   │           │   │ findMatchingRule()
   │find()  │   │ find()    │   │                  │
   │Compare │   │           │   │                  │
   └────────┘   └───────────┘   └──────────────────┘
        ↓             ↓                      ↓
        │ Query       │ Query                │ Cache +
        │ Code+       │ Date+Amount          │ Query
        │ AcctID      │ +Merchant            │ Rules
        │             │                      │
        ↓             ↓                      ↓
    ┌─────────────────────────────────┐
    │      Database (FA Legacy)        │
    ├─────────────────────────────────┤
    │ bi_transactions                 │
    │   - transactionCode             │
    │   - acctid                      │
    │   - valueTimestamp              │
    │   - transactionAmount           │
    │   - merchant                    │
    │   - memo                        │
    │   - reference                   │
    │                                 │
    │ bi_duplicate_rules              │
    │   - merchant_pattern (LIKE)     │
    │   - category                    │
    │   - allow_duplicates (0|1)      │
    └─────────────────────────────────┘


SEQUENCE FLOW:
═════════════

detect(transaction)
│
├─ LEVEL 1: DirectCodeMatcher.findAndCompare()
│  │ Finding: transactionCode + acctid match?
│  │
│  ├─ YES + fields match
│  │   → exactMatch("") 
│  │   → recommendedAction: SKIP ✓
│  │
│  ├─ YES + fields differ (data corruption)
│  │   → exactMatch("memo,amount")
│  │   → recommendedAction: REVIEW ✓
│  │
│  └─ NO → Continue to Level 2
│
├─ LEVEL 2: FuzzyMatcher.find()
│  │ Finding: date + amount±$0.01 + (merchant|memo|accountName) match?
│  │
│  ├─ NO matches found
│  │  → notDuplicate()
│  │  → recommendedAction: IMPORT ✓
│  │
│  └─ YES → Continue to Level 3
│
└─ LEVEL 3: DuplicateRulesProvider.findMatchingRule()
   │ Finding: Does whitelist rule match?
   │
   ├─ Rule found + allow_duplicates=1
   │  → fuzzyMatchAllowed(rule)
   │  → recommendedAction: ALLOWED_REPEAT ✓
   │
   └─ No rule or allow_duplicates=0
      → fuzzyMatchNeedsReview()
      → recommendedAction: REVIEW ✓

RETURN: DuplicateCheckResult
│
├─ isDuplicate: bool
├─ level: EXACT|FUZZY|NONE
├─ recommendedAction: IMPORT|SKIP|REVIEW|ALLOWED_REPEAT
├─ exactMatch: ?array
├─ fuzzyMatches: array
├─ whitelistRule: ?array
├─ fieldsThatDiffer: string (PHASE 2)
└─ message: string
```

---

## PROPOSED ARCHITECTURE (Dynamic Chain Pattern)

```
┌─────────────────────────────────────────────────────────────────┐
│  DuplicateDetectionChain (NEW Orchestrator)                     │
│  ─────────────────────────────────────────────────────────────  │
│  public detect(array $transaction): DuplicateCheckResult        │
│                                                                  │
│  Orchestration (DYNAMIC):                                       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ foreach ($matchers as $matcher) {      ← Sorted by prio │  │
│  │     if ($result = $matcher->match()) { ← Same interface │  │
│  │         return handleResult($result)   ← Unified result │  │
│  │     }                                                     │  │
│  │ }                                                         │  │
│  │ return notDuplicate()                                   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  private array $matchers      ← DuplicateMatcherInterface[]    │
│                                                                  │
│  public addMatcher(DuplicateMatcherInterface): Chain           │
│  public setMatchingStrategy(Strategy): void                    │
└─────────────────────────────────────────────────────────────────┘
       ↓
       │ REGISTERED AT STARTUP (or config)
       │
       └──────→ NEW: Factory/ServiceProvider
                ┌─────────────────────────────────────────┐
                │ DuplicateDetectionChainFactory          │
                ├─────────────────────────────────────────┤
                │ fromArray(config[])                     │
                │ fromDatabase()                          │
                │ fromDependencyContainer()               │
                └─────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────────┐
│                    Matcher Interface Contract                      │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  interface DuplicateMatcherInterface {                            │
│      match(transaction): ?DuplicateMatchResult;                   │
│      getPriority(): int;    // 0=first, 100=last                 │
│      getId(): string;        // For logging                       │
│  }                                                                │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘


   ┌─────────────────────────────────────────────────────────┐
   │          Concrete Matcher Implementations               │
   ├─────────────────────────────────────────────────────────┤
   │                                                         │
   │  DirectCodeMatcher                                     │
   │  ├─ implements DuplicateMatcherInterface              │
   │  ├─ match(): ?DuplicateMatchResult                    │
   │  ├─ getPriority(): 0     ← Check FIRST               │
   │  └─ getId(): "direct_code"                           │
   │                                                        │
   │  FuzzyMatcher                                          │
   │  ├─ implements DuplicateMatcherInterface              │
   │  ├─ match(): ?DuplicateMatchResult                    │
   │  ├─ getPriority(): 10    ← Check SECOND              │
   │  └─ getId(): "fuzzy"                                 │
   │                                                        │
   │  RulesMatcher (NEW)                                    │
   │  ├─ implements DuplicateMatcherInterface              │
   │  ├─ match(): ?DuplicateMatchResult                    │
   │  ├─ getPriority(): 20    ← Check THIRD               │
   │  └─ getId(): "rules"                                 │
   │                                                        │
   │  → Easy to add Level 4, 5, ... N                      │
   │  → Each matcher is independently testable            │
   │  → Can swap matchers without changing chain           │
   │  → Can reorder matchers by changing priority          │
   │                                                        │
   └─────────────────────────────────────────────────────────┘
          ↓
          │ UNIFIED: DuplicateMatchResult {
          │     matcherId: string
          │     confidence: 0-100
          │     matchedRecord: ?array
          │     metadata: array
          │ }
          ↓


KEY IMPROVEMENTS:
═════════════════

✓ Matchers are interchangeable (implement same interface)
✓ Add new matcher = implement interface + register
✓ Remove matcher = don't register
✓ Reorder matchers = change priority values
✓ Each matcher independently testable
✓ Easy to feature-flag matchers on/off
✓ Easier to debug (forEach shows which matcher matched)

BEFORE:
  Hardcoded: detect() method calls exact sequence
  Add Level 4? Modify service.detect()
  Test Level 1 in isolation? Must mock levels 2&3

AFTER:
  Dynamic: foreach($matchers) runs registered sequence
  Add Level 4? Create new RulesMatcher, register with priority=5
  Test Level 1? Just instantiate DirectCodeMatcher, no mocks needed
```

---

## COMPARISON: Hardcoded vs. Dynamic

### Hardcoded (Current)
```php
// Adding a new matcher requires:
// 1. Create new matcher class
// 2. Modify DuplicateDetectionService constructor
// 3. Add logic to detect() method
// 4. Handle result in detect()
// 5. Update tests for service

$service = new DuplicateDetectionService(
    new DirectCodeMatcher(),    // ← Must provide all
    new FuzzyMatcher(),          // ← in correct order
    new DuplicateRulesProvider() // ← hardcoded
);
```

### Dynamic (Proposed)
```php
// Adding a new matcher requires:
// 1. Create new matcher class (implement interface)
// 2. Register with chain
// That's it!

$chain = new DuplicateDetectionChain();
$chain
    ->addMatcher(new DirectCodeMatcher())
    ->addMatcher(new FuzzyMatcher())
    ->addMatcher(new RulesMatcher());

// Or from config:
$chain = DuplicateDetectionChainFactory::fromConfig($config);
```

---

## Configuration Example (For Dynamic Chain)

### Array-based Configuration:
```php
$config = [
    [
        'class' => DirectCodeMatcher::class,
        'priority' => 0,
        'enabled' => true,
        'params' => []
    ],
    [
        'class' => FuzzyMatcher::class,
        'priority' => 10,
        'enabled' => true,
        'params' => [
            'amountTolerance' => 0.01,
            'matchFields' => ['merchant', 'memo', 'accountName']
        ]
    ],
    [
        'class' => RulesMatcher::class,
        'priority' => 20,
        'enabled' => true,
        'params' => []
    ]
];

$chain = DuplicateDetectionChainFactory::fromArray($config);
```

### Feature Flag Example:
```php
// In production, disable experimental RulesMatcher
$config = [
    // ...
    [
        'class' => RulesMatcher::class,
        'priority' => 20,
        'enabled' => feature_flag('experimental.rules_matcher'),
        'params' => []
    ]
];
```

---

## Migration Path (No Breaking Changes)

### Phase 1: Build new chain in parallel
```php
// Old API still works
$service = new DuplicateDetectionService();
$result = $service->detect($transaction);  // Uses hardcoded

// New API starts working
$chain = new DuplicateDetectionChain();
$chain->addMatcher(new DirectCodeMatcher());
$result = $chain->detect($transaction);    // Uses chain
```

### Phase 2: Service delegates to chain (adapter pattern)
```php
class DuplicateDetectionService {
    private $chain;
    
    public function __construct(
        DirectCodeMatcher $direct = null,
        FuzzyMatcher $fuzzy = null,
        DuplicateRulesProvider $rules = null
    ) {
        $this->chain = new DuplicateDetectionChain();
        $this->chain->addMatcher($direct ?? new DirectCodeMatcher());
        // ...
    }
    
    public function detect(array $transaction): DuplicateCheckResult {
        return $this->chain->detect($transaction);  // ← Delegates
    }
}
```

### Phase 3: Migrate callers to chain directly
```php
// Old code still works (backward compatible)
$service->detect(...);

// New code uses chain directly
$chain->detect(...);
```

### Phase 4: Deprecate service (optional)
```php
/**
 * @deprecated Use DuplicateDetectionChain instead
 * @see DuplicateDetectionChain
 */
class DuplicateDetectionService {
    // ...
}
```

---

## Testing Improvements

### Current (Hardcoded)
```php
// Testing Level 1 matcher in isolation = HARD
public function testDirectCodeMatcherFindsExactDuplicate() {
    // Must instantiate service with all dependencies
    $service = new DuplicateDetectionService(
        $directMatcher,
        new FuzzyMatcher(),      // ← Not testing this
        new DuplicateRulesProvider() // ← Not testing this
    );
    
    // Must mock find() to return specific value
    $directMatcher->expects(...)
        ->method('find')
        ->willReturn(...);
    
    // Result depends on other components' behavior
    $result = $service->detect($transaction);
    // ← Test is fragile if Level 2 or 3 have bugs
}
```

### Proposed (Dynamic Chain)
```php
// Testing Level 1 matcher in isolation = EASY
public function testDirectCodeMatcherFindsExactDuplicate() {
    // Test just the matcher
    $matcher = new DirectCodeMatcher();
    $result = $matcher->match($transaction);
    
    // No mocking, no dependencies, clean test
    $this->assertNotNull($result);
    $this->assertEquals('direct_code', $result->getMatcherId());
}

// Testing chain = easy
public function testChainStopsAtFirstMatch() {
    $chain = new DuplicateDetectionChain();
    $chain->addMatcher($matcher1);  // Returns result with priority 0
    $chain->addMatcher($matcher2);  // Priority 10 (never called)
    
    $result = $chain->detect($transaction);
    $this->assertEquals('direct_code', $result->getMatcherId());
}
```

---

**Document Version:** 1.0
**Created:** 2026-04-04
**Architecture:** Current = Hardcoded 3-Level | Proposed = Dynamic Chain of Responsibility
