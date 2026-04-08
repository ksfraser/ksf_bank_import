# Duplicate Detection Service - Architecture Analysis

## 1. CURRENT IMPLEMENTATION OVERVIEW

### 1.1 Component Summary

The duplicate detection system consists of 5 main components working together in a **hardcoded 3-level strategy**:

| Component | Responsibility | Pattern |
|-----------|-----------------|---------|
| **DuplicateDetectionService** | Orchestrates all levels, applies business logic | Service (Coordinator) |
| **DirectCodeMatcher** | Level 1: Fast authoritative matching | Data Access Layer |
| **FuzzyMatcher** | Level 2: Fallback candidate finding | Data Access Layer |
| **DuplicateRulesProvider** | Level 3: Whitelist rule application | Data Access Layer |
| **DuplicateCheckResult** | Result encapsulation | Value Object |

---

## 2. COMPONENT DETAILS

### 2.1 DirectCodeMatcher

**Location:** `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DirectCodeMatcher.php`

**Purpose:** Level 1 - Definitive duplicate detection

```php
class DirectCodeMatcher {
    private const FIELDS_TO_COMPARE = [
        'valueTimestamp',
        'transactionAmount',
        'merchant',
        'memo',
        'reference'
    ];
}
```

**Public Methods:**

| Method | Returns | Purpose |
|--------|---------|---------|
| `find(array $transaction)` | `?array` | Direct DB lookup by `transactionCode` + `acctid` |
| `findAndCompare(array $newTransaction)` | `?array` | Find + compare all fields, return field differences |

**Database Query Logic:**
```sql
SELECT * FROM bi_transactions 
WHERE transactionCode = ? 
  AND acctid = ?
LIMIT 1
```

**Key Features:**
- No interface/contract defined (hardcoded)
- Compares 5 specific fields when code matches
- Returns CSV string of differing fields: `"memo,amount"`
- Phase 2: Detects data corruption (code match but field mismatch)

**Hardcoded Aspect:**
```php
// Constructor
public function __construct(
    DirectCodeMatcher $directMatcher = null,
    FuzzyMatcher $fuzzyMatcher = null,
    DuplicateRulesProvider $rulesProvider = null
) {
    $this->directMatcher = $directMatcher ?? new DirectCodeMatcher();
    $this->fuzzyMatcher = $fuzzyMatcher ?? new FuzzyMatcher();
    $this->rulesProvider = $rulesProvider ?? new DuplicateRulesProvider();
}
```

---

### 2.2 FuzzyMatcher

**Location:** `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/FuzzyMatcher.php`

**Purpose:** Level 2 - Fallback candidate matching when Level 1 fails

**Match Criteria:**
```
- Exact date match (valueTimestamp)
- Amount ±$0.01 (ABS(amount - ?) < 0.01)
- Merchant OR Memo OR Account Name match
- Same account (acctid)
```

**Database Query:**
```sql
SELECT * FROM bi_transactions
WHERE acctid = ?
  AND valueTimestamp = ?
  AND ABS(transactionAmount - ?) < 0.01
  AND (merchant = ? OR memo = ? OR accountName = ?)
ORDER BY id DESC
```

**Key Features:**
- No date window (re-downloads have identical dates)
- Multiple candidates possible (returns array)
- No whitelist logic here (passed to Level 3)
- Returns all fuzzy matches for rule evaluation

**Hardcoded Aspect:**
- Amount tolerance: `< 0.01` (hardcoded)
- Match fields: merchant, memo, accountName (hardcoded)
- No window/threshold configuration

---

### 2.3 DuplicateRulesProvider

**Location:** `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateRulesProvider.php`

**Purpose:** Level 3 - Apply whitelist rules to fuzzy matches

**Rule Matching Logic:**

```php
private function ruleMatches(array $rule, string $merchant, string $category): bool
{
    // Rule contains: merchant_pattern, category, allow_duplicates flag
    if (isset($rule['merchant_pattern']) && !empty($rule['merchant_pattern'])) {
        if (!$this->patternMatches($rule['merchant_pattern'], $merchant)) {
            return false;
        }
    }
    
    if (isset($rule['category']) && !empty($rule['category'])) {
        if ($category !== $rule['category']) {
            return false;
        }
    }
    
    return true;
}
```

**Pattern Matching:**
- Supports SQL LIKE syntax: `SHOPPERS%` matches "SHOPPERS DRUG MART"
- Multiple patterns: pipe-separated `pattern1|pattern2|pattern3`
- Case-insensitive

**Database Schema (inferred):**
```sql
bi_duplicate_rules (
    id,
    merchant_pattern (e.g., 'SHOPPERS%'),
    category,
    allow_duplicates ENUM(0|1),
    active ENUM(0|1),
    rule_name
)
```

**Key Features:**
- Rules cached per request (`$rulesCache`)
- Loaded once at startup
- Pattern matching via LIKE to regex conversion
- `allow_duplicates` flag determines action

**Hardcoded Aspect:**
- Rule matching criteria fixed (merchant_pattern, category, active status)
- No priority/weighting system
- First matching rule wins

---

### 2.4 DuplicateCheckResult

**Location:** `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateCheckResult.php`

**Purpose:** Encapsulate detection result with recommended action

**State Properties:**
```php
private $isDuplicate = false;
private $level = 'NONE';                // EXACT|FUZZY|NONE
private $exactMatch = null;             // Level 1 match
private $fuzzyMatches = [];             // Level 2 matches
private $recommendedAction = 'IMPORT';  // IMPORT|SKIP|REVIEW|ALLOWED_REPEAT
private $whitelistRule = null;          // Applied rule
private $fieldsThatDiffer = '';         // PHASE 2: CSV fields
private $mustReviewBeforeMerge = false; // PHASE 2: Force review flag
```

**Factory Methods (Hardcoded Levels):**

| Method | When Used | Action | Level |
|--------|-----------|--------|-------|
| `exactMatch(existingTxn, fieldsDiffer)` | Code match found | SKIP or REVIEW | EXACT (1) |
| `fuzzyMatchAllowed(txn, rule)` | Fuzzy + whitelisted | ALLOWED_REPEAT | FUZZY (2) |
| `fuzzyMatchNeedsReview(txns)` | Fuzzy + not whitelisted | REVIEW | FUZZY (2) |
| `notDuplicate()` | No matches | IMPORT | NONE |

**Phase 2 Enhancement:**
```php
public static function exactMatch(array $existingTransaction, string $fieldsThatDiffer = ''): self
{
    // If fields differ → recommendedAction = 'REVIEW' (data corruption detected)
    // If fields match → recommendedAction = 'SKIP' (true duplicate)
}
```

---

### 2.5 DuplicateReviewHandler

**Location:** `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateReviewHandler.php`

**Purpose:** Store flagged duplicates for user review (Phase 2)

**Workflow:**
1. Import detects duplicate (code match + field mismatch OR fuzzy)
2. Calls `storeForReview()` method
3. Transaction stored in `bi_transactions_dupe` staging table
4. User sees dashboard with side-by-side comparison
5. User confirms or rejects
6. On confirm: move to statement, create journal
7. On reject: skip import

**Staging Table Fields:**
```php
$dupeRecord = [
    // All fields from bi_transactions (copied)
    'id', 'statement_id', 'valueTimestamp', 'transactionAmount', ...
    
    // Phase 2 metadata
    'matching_bi_transaction_id',  // ID of existing transaction
    'fields_that_differ',           // CSV: "memo,amount"
    'match_type',                   // EXACT_CODE_MISMATCH|FUZZY_MATCH
    'status',                       // PENDING|CONFIRMED|REJECTED
    'reviewed_by',                  // User ID (null until reviewed)
    'reviewed_at',                  // Timestamp of review
    'notes',                        // User notes
    'created_at', 'updated_at'
]
```

**Integration Point:**
- Used by DuplicateDetectionService when result action is 'REVIEW'
- Not directly part of the chain yet (external handler)

---

## 3. CURRENT ORCHESTRATION FLOW

### 3.1 DuplicateDetectionService Orchestration

```php
public function detect(array $transaction): DuplicateCheckResult
{
    // ========== LEVEL 1: Direct Code Match (Fast + Authoritative) ==========
    $codeMatchResult = $this->directMatcher->findAndCompare($transaction);
    
    if ($codeMatchResult) {
        $existingTransaction = $codeMatchResult['match'];
        $fieldsThatDiffer = $codeMatchResult['fields_that_differ'];
        
        // Phase 2: Fields differ → REVIEW, else → SKIP
        return DuplicateCheckResult::exactMatch($existingTransaction, $fieldsThatDiffer);
    }
    
    // ========== LEVEL 2: Fuzzy Match (Slower, Fallback) ==========
    $fuzzy = $this->fuzzyMatcher->find($transaction);
    
    if (empty($fuzzy)) {
        // No matches found
        return DuplicateCheckResult::notDuplicate();
    }
    
    // ========== LEVEL 3: Whitelist Rules (User Policy) ==========
    $rule = $this->rulesProvider->findMatchingRule($transaction);
    
    if ($rule && $this->ruleAllowsDuplicates($rule)) {
        // Whitelisted - allow the import
        return DuplicateCheckResult::fuzzyMatchAllowed($fuzzy[0], $rule);
    }
    
    // Not whitelisted - show user
    return DuplicateCheckResult::fuzzyMatchNeedsReview($fuzzy);
}
```

### 3.2 Decision Tree Diagram

```
START: detect(transaction)
│
├─→ LEVEL 1: DirectCodeMatcher.findAndCompare()
│   ├─ Code+acctid match?
│   │  ├─ YES + fields match → DuplicateCheckResult::exactMatch("", fieldsMatch)
│   │  │                        Action: SKIP ✓ (true duplicate)
│   │  │
│   │  └─ YES + fields differ → DuplicateCheckResult::exactMatch(txn, fieldsDiffer)
│   │                            Action: REVIEW ✓ (data corruption)
│   │
│   └─ NO → Continue
│
├─→ LEVEL 2: FuzzyMatcher.find()
│   ├─ Fuzzy match found?
│   │  ├─ YES → Continue to Level 3
│   │  │
│   │  └─ NO → DuplicateCheckResult::notDuplicate()
│   │           Action: IMPORT ✓
│
├─→ LEVEL 3: DuplicateRulesProvider.findMatchingRule()
│   ├─ Rule matches?
│   │  ├─ YES (allow_duplicates = 1) → DuplicateCheckResult::fuzzyMatchAllowed()
│   │  │                               Action: ALLOWED_REPEAT ✓
│   │  │
│   │  └─ NO or rule missing → DuplicateCheckResult::fuzzyMatchNeedsReview()
│   │                           Action: REVIEW ✓ (user decides)
│
END: return DuplicateCheckResult
```

---

## 4. HARDCODED LEVELS & ASPECTS

### 4.1 Level Hardcoding

**Level 1 - Direct Code Match**
- ✗ Constructor parameter allows injection but instantiation is hardcoded
- ✗ Methods are called in fixed sequence
- ✗ Level result dictates early exit (Level 2 only runs if Level 1 fails)
- ✗ Result factory method name encodes level: `exactMatch()` → implies level

**Level 2 - Fuzzy Match**
- ✗ Only runs if Level 1 returns null
- ✗ Query criteria hardcoded: amount tolerance = 0.01, fields = merchant|memo|accountName
- ✗ No weighting/scoring of candidates (returns all equally)
- ✗ Passes to Level 3 automatically if found

**Level 3 - Whitelist Rules**
- ✗ Hardcoded dependency injection in constructor
- ✗ Only runs if Level 2 returns results
- ✗ Rule matching logic embedded in class
- ✗ No chain concept (Level 3 is an adapter, not a matcher)

**Level Orchestration Hardcoding:**
```php
// In detect() method:
if ($codeMatchResult) {              // ← Level 1 exclusive
    return DuplicateCheckResult::exactMatch(...);
}

$fuzzy = $this->fuzzyMatcher->find(...); // ← Level 2 only if Level 1 fails
if (empty($fuzzy)) {
    return DuplicateCheckResult::notDuplicate();
}

$rule = $this->rulesProvider->findMatchingRule(...); // ← Level 3 only if Level 2 succeeds
```

### 4.2 Other Hardcoded Aspects

**DirectCodeMatcher:**
- `FIELDS_TO_COMPARE` constant (5 fixed fields)
- Match criteria: transactionCode + acctid (no flexibility)
- No threshold/tolerance configuration

**FuzzyMatcher:**
- Amount tolerance: `0.01` hardcoded in SQL
- Match field criteria: merchant, memo, accountName (fixed)
- No date window (hardcoded design decision)

**DuplicateRulesProvider:**
- Database cache key: none (full reload each time)
- Rule matching fields: merchant_pattern, category (fixed)
- Pattern format: SQL LIKE only

**DuplicateCheckResult:**
- Factory method names encode levels: `exactMatch`, `fuzzyMatch*`, `notDuplicate`
- Recommended actions hardcoded: IMPORT, SKIP, REVIEW, ALLOWED_REPEAT
- Phase 2 logic embedded in `exactMatch()` factory

---

## 5. DYNAMIC CHAIN PATTERN REQUIREMENTS

### 5.1 What Would Need to Change

To implement a **Chain of Responsibility pattern** with dynamic matcher configuration:

#### A. Matcher Interface/Contract

**Currently Missing:**
```php
// NO INTERFACE EXISTS - matchers have different method signatures
```

**Would Need:**
```php
namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts;

interface DuplicateMatcherInterface {
    /**
     * Attempt to match transaction.
     * 
     * @param array $transaction Transaction data
     * @return DuplicateMatchResult|null Match found, or null if no match
     */
    public function match(array $transaction): ?DuplicateMatchResult;
    
    /**
     * Get matcher priority (lower = checked first, 0-100).
     */
    public function getPriority(): int;
    
    /**
     * Get matcher identifier for logging/debugging.
     */
    public function getId(): string;
}
```

#### B. Unified Match Result

**Currently:** Each matcher returns different types
- `DirectCodeMatcher::findAndCompare()` → array with ['match', 'fields_that_differ']
- `FuzzyMatcher::find()` → array of matches
- `DuplicateRulesProvider` → array rule record (not a match result)

**Would Need:**
```php
namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts;

class DuplicateMatchResult {
    public function __construct(
        private string $matcherId,      // "direct_code" | "fuzzy" | "rules"
        private int $confidence,        // 0-100: certainty of match
        private ?array $matchedRecord,  // Existing transaction record
        private array $metadata = [],   // Additional data (fieldsDiffer, rule, etc)
    ) {}
    
    public function getMatcherId(): string;
    public function getConfidence(): int;
    public function getMatchedRecord(): ?array;
    public function getMetadata(): array;
}
```

#### C. Dynamic Chain Builder

**Currently:** Hardcoded in service constructor

**Would Need:**
```php
class DuplicateDetectionChain {
    private array $matchers = [];
    
    public function addMatcher(DuplicateMatcherInterface $matcher): self {
        $this->matchers[] = $matcher;
        // Sort by priority
        usort($this->matchers, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
        return $this;
    }
    
    public function detect(array $transaction): DuplicateCheckResult {
        foreach ($this->matchers as $matcher) {
            $result = $matcher->match($transaction);
            if ($result !== null) {
                // Handle based on matcher type and confidence
                return $this->buildCheckResult($result);
            }
        }
        return DuplicateCheckResult::notDuplicate();
    }
}
```

#### D. Refactored Matchers → Implement Interface

**DirectCodeMatcher:**
```php
class DirectCodeMatcher implements DuplicateMatcherInterface {
    public function match(array $transaction): ?DuplicateMatchResult {
        $codeMatch = $this->find($transaction);
        if (!$codeMatch) return null;
        
        $fieldsThatDiffer = $this->getFieldsThatDiffer(...);
        
        return new DuplicateMatchResult(
            matcherId: 'direct_code',
            confidence: empty($fieldsThatDiffer) ? 100 : 90,
            matchedRecord: $codeMatch,
            metadata: ['fieldsThatDiffer' => $fieldsThatDiffer]
        );
    }
    
    public function getPriority(): int { return 0; }  // Check first
    public function getId(): string { return 'direct_code'; }
}
```

**FuzzyMatcher:**
```php
class FuzzyMatcher implements DuplicateMatcherInterface {
    public function match(array $transaction): ?DuplicateMatchResult {
        $matches = $this->find($transaction);
        if (empty($matches)) return null;
        
        return new DuplicateMatchResult(
            matcherId: 'fuzzy',
            confidence: 60,  // Lower certainty for fuzzy matches
            matchedRecord: $matches[0],  // Return best match
            metadata: ['allMatches' => $matches, 'matchCount' => count($matches)]
        );
    }
    
    public function getPriority(): int { return 10; }  // Check second
    public function getId(): string { return 'fuzzy'; }
}
```

**RulesMatcher (NEW):**
```php
class RulesMatcher implements DuplicateMatcherInterface {
    public function match(array $transaction): ?DuplicateMatchResult {
        // Don't match directly - instead adapts fuzzy results
        // This would be handled at a higher level
    }
    
    public function getPriority(): int { return 20; }  // Check last
    public function getId(): string { return 'rules'; }
}
```

#### E. ResultantJSON/Configuration for Dynamic Setup

Instead of hardcoded orchestration, configuration:

```php
// Could be loaded from config, database, or service provider
$chainConfig = [
    [
        'class' => DirectCodeMatcher::class,
        'priority' => 0,
        'enabled' => true,
        'config' => [
            'fields_to_compare' => ['valueTimestamp', 'amount', 'merchant', 'memo', 'reference']
        ]
    ],
    [
        'class' => FuzzyMatcher::class,
        'priority' => 10,
        'enabled' => true,
        'config' => [
            'amount_tolerance' => 0.01,
            'match_fields' => ['merchant', 'memo', 'accountName']
        ]
    ],
    [
        'class' => RulesMatcher::class,
        'priority' => 20,
        'enabled' => true,
        'config' => []
    ]
];
```

---

## 6. IMPLEMENTATION PATH: FROM HARDCODED TO DYNAMIC

### Phase 1: Extract Interfaces & Unify Results

```
Step 1: Create DuplicateMatcherInterface
Step 2: Create DuplicateMatchResult value object
Step 3: Implement interface on DirectCodeMatcher
Step 4: Implement interface on FuzzyMatcher
Step 5: Update DuplicateDetectionService to work with new interface
```

### Phase 2: Build Chain Builder

```
Step 6: Create DuplicateDetectionChain class
Step 7: Move orchestration logic from Service to Chain
Step 8: Support dynamic matcher registration
Step 9: Test with multiple matcher orderings
```

### Phase 3: Configuration & Setup

```
Step 10: Create configuration system (array-based or database)
Step 11: Build factory to instantiate chain from config
Step 12: Add rules matcher as chain member
Step 13: Support enable/disable per matcher
```

### Phase 4: Legacy Compatibility

```
Step 14: Keep DetectionService as facade for backward compatibility
Step 15: Migrate callers to Chain-based approach gradually
Step 16: Add feature flags to switch between old/new
```

---

## 7. CURRENT INTERFACE PATTERNS IN USE

### 7.1 Existing Patterns (Not Used Here)

The codebase has established patterns in other services:

**From PHASE_0 guidelines (user memory):**
- ✓ RepositoryInterface exists in pattern
- ✓ Entities with private constructors + factories
- ✓ EventInterface pattern for pub/sub
- ✓ ValueObject pattern for results

**But duplicate detection uses:**
- ✗ No matcher interface
- ✗ No contract-based matchers
- ✗ Hardcoded orchestration in Service
- ✗ Mixed result formats (array returns)

### 7.2 Common Interface Pattern (if adopted)

Following existing codebase patterns:

```php
namespace Ksfraser\FaBankImport\Shared\Contracts;

interface MatcherInterface {
    public function execute(array $subject): ?MatchResult;
    public function getName(): string;
}

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts;

interface DuplicateMatcherInterface extends \Ksfraser\FaBankImport\Shared\Contracts\MatcherInterface {
    public function getPriority(): int;
}
```

This follows the established Shared/Contracts/ pattern from Phase 0.

---

## 8. SUMMARY TABLE

| Aspect | Current State | For Dynamic Chain |
|--------|---------------|-------------------|
| **Matcher Contract** | None (methods differ) | DuplicateMatcherInterface |
| **Result Format** | Variable (array, null, array) | DuplicateMatchResult VO |
| **Matcher Ordering** | Hardcoded (1→2→3) | Priority-based via Chain |
| **Configuration** | Hardcoded in constructor | External config/builder |
| **Stopping Condition** | Hardcoded (first match wins) | Configurable (confidence threshold) |
| **Error Handling** | Try/catch per matcher | Unified in chain |
| **Rule Integration** | Adapter pattern (implicit) | Chain member (explicit) |
| **Testing** | Difficult (mock all deps) | Easy (mock individual matchers) |
| **Extensibility** | Add Level 4? Refactor service | Add new matcher to chain |

---

## 9. QUICK REFERENCE: Files to Modify

### High Impact Changes
1. **DuplicateDetectionService.php** - Refactor to use DuplicateDetectionChain
2. **DirectCodeMatcher.php** - Implement DuplicateMatcherInterface
3. **FuzzyMatcher.php** - Implement DuplicateMatcherInterface

### New Files to Create
1. **Contracts/DuplicateMatcherInterface.php** - Matcher contract
2. **DuplicateMatchResult.php** - Unified result VO
3. **DuplicateDetectionChain.php** - Chain orchestrator
4. **Matchers/RulesMatcher.php** - Rules adapter as chain member

### Unchanged
- DuplicateCheckResult.php (result VO for public API)
- DuplicateRulesProvider.php (implementation detail)
- DuplicateReviewHandler.php (external handler)

---

## 10. MIGRATION EXAMPLE

### Before (Hardcoded):
```php
$service = new DuplicateDetectionService();
$result = $service->detect($transaction);
```

### After (Dynamic Chain):
```php
$chain = new DuplicateDetectionChain();
$chain
    ->addMatcher(new DirectCodeMatcher(priority: 0))
    ->addMatcher(new FuzzyMatcher(priority: 10))
    ->addMatcher(new RulesMatcher(priority: 20));

$result = $chain->detect($transaction);
```

### With Configuration:
```php
$config = new DuplicateDetectionConfig($configArray);
$chain = DuplicateDetectionChainFactory::fromConfig($config);
$result = $chain->detect($transaction);
```

---

**Generated:** 2026-04-04
**Version:** 1.0 (Initial Analysis)
