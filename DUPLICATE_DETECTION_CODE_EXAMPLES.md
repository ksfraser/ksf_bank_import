# Duplicate Detection - Code Implementation Examples

This document shows concrete PHP code for the dynamic chain pattern refactoring.

---

## CURRENT CODE (How It Works Now)

### DuplicateDetectionService.php (Current - Hardcoded)

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

class DuplicateDetectionService
{
    private $directMatcher;
    private $fuzzyMatcher;
    private $rulesProvider;
    
    public function __construct(
        DirectCodeMatcher $directMatcher = null,
        FuzzyMatcher $fuzzyMatcher = null,
        DuplicateRulesProvider $rulesProvider = null
    ) {
        // Hardcoded instantiation
        $this->directMatcher = $directMatcher ?? new DirectCodeMatcher();
        $this->fuzzyMatcher = $fuzzyMatcher ?? new FuzzyMatcher();
        $this->rulesProvider = $rulesProvider ?? new DuplicateRulesProvider();
    }
    
    /**
     * HARDCODED THREE-LEVEL ORCHESTRATION
     */
    public function detect(array $transaction): DuplicateCheckResult
    {
        // ========== LEVEL 1 (HARDCODED) ==========
        $codeMatchResult = $this->directMatcher->findAndCompare($transaction);
        if ($codeMatchResult) {
            return DuplicateCheckResult::exactMatch(
                $codeMatchResult['match'],
                $codeMatchResult['fields_that_differ']
            );
        }
        
        // ========== LEVEL 2 (HARDCODED) ==========
        $fuzzy = $this->fuzzyMatcher->find($transaction);
        if (empty($fuzzy)) {
            return DuplicateCheckResult::notDuplicate();
        }
        
        // ========== LEVEL 3 (HARDCODED) ==========
        $rule = $this->rulesProvider->findMatchingRule($transaction);
        if ($rule && $this->ruleAllowsDuplicates($rule)) {
            return DuplicateCheckResult::fuzzyMatchAllowed($fuzzy[0], $rule);
        }
        
        return DuplicateCheckResult::fuzzyMatchNeedsReview($fuzzy);
    }
    
    private function ruleAllowsDuplicates(array $rule): bool
    {
        return (int)($rule['allow_duplicates'] ?? 0) === 1;
    }
}
```

### DirectCodeMatcher.php (Current - No Interface)

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

class DirectCodeMatcher
{
    private const FIELDS_TO_COMPARE = [
        'valueTimestamp',
        'transactionAmount',
        'merchant',
        'memo',
        'reference'
    ];
    
    // Different method signature than FuzzyMatcher!
    public function findAndCompare(array $newTransaction): ?array
    {
        $existingTransaction = $this->find($newTransaction);
        if (!$existingTransaction) {
            return null;
        }
        
        $fieldsThatDiffer = $this->getFieldsThatDiffer($newTransaction, $existingTransaction);
        
        return [
            'match' => $existingTransaction,
            'fields_that_differ' => $fieldsThatDiffer,
            'all_fields_match' => empty($fieldsThatDiffer),
            'is_code_match' => true
        ];
    }
    
    // Implementation details...
}
```

### FuzzyMatcher.php (Current - No Interface)

```php
<?php
namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

class FuzzyMatcher
{
    // Different method signature than DirectCodeMatcher!
    public function find(array $transaction): array
    {
        // Implementation...
        return $results;  // Returns array of matches
    }
}
```

**Problems with current code:**
- ✗ No common interface
- ✗ Different method names (findAndCompare vs find)
- ✗ Different return types (array|null vs array)
- ✗ Hardcoded orchestration in service
- ✗ Can't dynamically add/remove matchers
- ✗ Can't easily reorder matchers
- ✗ Difficult to test in isolation

---

## PROPOSED CODE (Dynamic Chain Pattern)

### Step 1: Create Matcher Interface

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/Contracts/DuplicateMatcherInterface.php`**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts;

/**
 * Contract for duplicate detection matchers.
 * 
 * Matchers are chained in order of priority:
 * - Lower priority numbers run first (0 = highest priority)
 * - Each matcher can return a match or null
 * - Chain continues until a matcher returns a match
 * 
 * Implementations should be stateless and thread-safe.
 */
interface DuplicateMatcherInterface
{
    /**
     * Attempt to match a transaction.
     * 
     * @param array $transaction Transaction data
     * @return DuplicateMatchResult|null Match result if transaction is a duplicate, null otherwise
     */
    public function match(array $transaction): ?DuplicateMatchResult;
    
    /**
     * Get the execution priority of this matcher.
     * 
     * Lower numbers run first (0 = highest priority, 100 = lowest).
     * Matchers with same priority run in registration order.
     * 
     * Recommended values:
     * - 0:  Direct/exact matches (highest priority)
     * - 10: Fuzzy/candidate matches
     * - 20: Policy/rule matches
     * - 30+: Custom/external matchers
     * 
     * @return int Priority (0-100)
     */
    public function getPriority(): int;
    
    /**
     * Get a unique identifier for this matcher.
     * 
     * Used for logging, debugging, and result tracking.
     * Should be lowercase with underscores: "direct_code", "fuzzy_match", etc.
     * 
     * @return string Matcher identifier
     */
    public function getId(): string;
}
```

### Step 2: Create Unified Match Result Value Object

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateMatchResult.php`**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

/**
 * Result of a duplicate matcher.
 * 
 * Unified value object returned by all matchers in the chain.
 * Replaces the mixed return types (array, null) currently used.
 * 
 * Immutable - create once, pass around.
 */
final class DuplicateMatchResult
{
    /**
     * @param string $matcherId Matcher that produced this result ("direct_code", "fuzzy", etc)
     * @param int $confidence Confidence level (0-100, where 100 = certain)
     * @param array|null $matchedRecord The existing transaction record that matches
     * @param array $metadata Additional matcher-specific data
     */
    private function __construct(
        private string $matcherId,
        private int $confidence,
        private ?array $matchedRecord,
        private array $metadata = []
    ) {
        if ($confidence < 0 || $confidence > 100) {
            throw new \InvalidArgumentException('Confidence must be 0-100');
        }
    }
    
    /**
     * Create a match result from an exact code match.
     * 
     * @param array $existingTransaction The matching transaction from DB
     * @param string $fieldsThatDiffer CSV string of fields that differ
     * @return self
     */
    public static function exactCodeMatch(
        array $existingTransaction,
        string $fieldsThatDiffer = ''
    ): self {
        $confidence = empty($fieldsThatDiffer) ? 100 : 90;  // Lower if fields differ
        
        return new self(
            matcherId: 'direct_code',
            confidence: $confidence,
            matchedRecord: $existingTransaction,
            metadata: [
                'fieldsThatDiffer' => $fieldsThatDiffer,
                'allFieldsMatch' => empty($fieldsThatDiffer),
            ]
        );
    }
    
    /**
     * Create a match result from a fuzzy match.
     * 
     * @param array $matchedTransaction Best matching transaction
     * @param array $allMatches All candidates that matched
     * @return self
     */
    public static function fuzzyMatch(
        array $matchedTransaction,
        array $allMatches = []
    ): self {
        return new self(
            matcherId: 'fuzzy',
            confidence: 60,  // Fuzzy matches are less certain
            matchedRecord: $matchedTransaction,
            metadata: [
                'allMatches' => $allMatches,
                'matchCount' => count($allMatches),
            ]
        );
    }
    
    /**
     * Create a match result from rule matching.
     * 
     * @param array $matchedTransaction Transaction matching the rule
     * @param array $appliedRule The whitelist rule that matched
     * @return self
     */
    public static function ruleMatch(
        array $matchedTransaction,
        array $appliedRule
    ): self {
        return new self(
            matcherId: 'rules',
            confidence: 80,  // Rules are fairly certain
            matchedRecord: $matchedTransaction,
            metadata: [
                'appliedRule' => $appliedRule,
                'ruleName' => $appliedRule['rule_name'] ?? 'UNKNOWN',
                'allowDuplicates' => (bool)($appliedRule['allow_duplicates'] ?? false),
            ]
        );
    }
    
    // ========== Getters (Immutable) ==========
    
    public function getMatcherId(): string
    {
        return $this->matcherId;
    }
    
    public function getConfidence(): int
    {
        return $this->confidence;
    }
    
    public function getMatchedRecord(): ?array
    {
        return $this->matchedRecord;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
    
    /**
     * Check if this is a certain match (confidence >= threshold).
     * 
     * @param int $threshold Confidence threshold (0-100)
     * @return bool
     */
    public function isCertain(int $threshold = 90): bool
    {
        return $this->confidence >= $threshold;
    }
}
```

### Step 3: Refactor DirectCodeMatcher to Implement Interface

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DirectCodeMatcher.php` (REFACTORED)**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts\DuplicateMatcherInterface;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Level 1: Direct Code Matcher
 * 
 * Performs authoritative duplicate detection based on:
 * - transactionCode (bank-assigned unique code)
 * - acctid (our account identifier)
 * 
 * If both match an existing transaction, it IS a duplicate (100% certainty).
 * 
 * Priority: 0 (runs first in the chain)
 */
class DirectCodeMatcher implements DuplicateMatcherInterface
{
    private const FIELDS_TO_COMPARE = [
        'valueTimestamp',
        'transactionAmount',
        'merchant',
        'memo',
        'reference'
    ];
    
    /**
     * Implementation of DuplicateMatcherInterface.
     * 
     * Unified method signature for all matchers.
     */
    public function match(array $transaction): ?DuplicateMatchResult
    {
        $codeMatch = $this->find($transaction);
        
        if (!$codeMatch) {
            return null;  // No match found
        }
        
        // Code matches - compare all fields
        $fieldsThatDiffer = $this->getFieldsThatDiffer($transaction, $codeMatch);
        
        // Return unified result
        return DuplicateMatchResult::exactCodeMatch($codeMatch, $fieldsThatDiffer);
    }
    
    public function getPriority(): int
    {
        return 0;  // Highest priority - check first
    }
    
    public function getId(): string
    {
        return 'direct_code';
    }
    
    // ========== Original methods (unchanged) ==========
    
    /**
     * Find exact duplicate by transactionCode + acctid.
     */
    public function find(array $transaction): ?array
    {
        $transactionCode = $transaction['transactionCode'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        
        if (!$transactionCode || !$acctid) {
            return null;
        }
        
        try {
            $query = sprintf(
                "SELECT * FROM %s bi_transactions 
                 WHERE transactionCode = %s 
                 AND acctid = %s
                 LIMIT 1",
                TB_PREF,
                db_escape($transactionCode),
                db_escape($acctid)
            );
            
            $result = db_query($query, 'Could not query for exact duplicate');
            return db_fetch_assoc($result);
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions WHERE transactionCode=? AND acctid=?",
                $e->getMessage()
            );
        }
    }
    
    /**
     * Compare all relevant fields between two transactions.
     */
    private function getFieldsThatDiffer(array $newTransaction, array $existingTransaction): string
    {
        $differences = [];
        
        foreach (self::FIELDS_TO_COMPARE as $field) {
            $newValue = $this->normalizeValue($newTransaction[$field] ?? null);
            $existingValue = $this->normalizeValue($existingTransaction[$field] ?? null);
            
            if ($newValue !== $existingValue) {
                $differences[] = $field;
            }
        }
        
        return implode(',', $differences);
    }
    
    /**
     * Normalize values for field comparison.
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_string($value)) {
            return trim($value);
        }
        
        if (is_numeric($value)) {
            return round((float)$value, 2);
        }
        
        return $value;
    }
}
```

### Step 4: Refactor FuzzyMatcher to Implement Interface

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/FuzzyMatcher.php` (REFACTORED)**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts\DuplicateMatcherInterface;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionFetchException;

/**
 * Level 2: Fuzzy Matcher
 * 
 * Finds potential duplicates when Level 1 (exact code match) fails.
 * Matches on: exact date + amount (±$0.01) + merchant/memo
 * 
 * Priority: 10 (runs second in the chain)
 */
class FuzzyMatcher implements DuplicateMatcherInterface
{
    /**
     * Implementation of DuplicateMatcherInterface.
     * 
     * Unified method signature for all matchers.
     */
    public function match(array $transaction): ?DuplicateMatchResult
    {
        $matches = $this->find($transaction);
        
        if (empty($matches)) {
            return null;  // No matches found
        }
        
        // Return unified result with best match
        return DuplicateMatchResult::fuzzyMatch($matches[0], $matches);
    }
    
    public function getPriority(): int
    {
        return 10;  // Second priority
    }
    
    public function getId(): string
    {
        return 'fuzzy';
    }
    
    // ========== Original methods (unchanged) ==========
    
    /**
     * Find fuzzy matches on date + amount + merchant/memo.
     */
    public function find(array $transaction): array
    {
        $date = $transaction['valueTimestamp'] ?? null;
        $amount = $transaction['transactionAmount'] ?? 0;
        $merchant = $transaction['merchant'] ?? null;
        $memo = $transaction['memo'] ?? null;
        $acctid = $transaction['acctid'] ?? null;
        
        if (!$date || !$acctid) {
            return [];
        }
        
        try {
            $matchCriteria = "(";
            $criteria = [];
            
            if ($merchant) {
                $criteria[] = "merchant = " . db_escape($merchant);
            }
            if ($memo) {
                $criteria[] = "memo = " . db_escape($memo);
            }
            if (isset($transaction['accountName'])) {
                $criteria[] = "accountName = " . db_escape($transaction['accountName']);
            }
            
            if (empty($criteria)) {
                return [];
            }
            
            $matchCriteria .= implode(" OR ", $criteria) . ")";
            
            $query = sprintf(
                "SELECT * FROM %s bi_transactions
                 WHERE acctid = %s
                 AND valueTimestamp = %s
                 AND ABS(transactionAmount - %f) < 0.01
                 AND %s
                 ORDER BY id DESC",
                TB_PREF,
                db_escape($acctid),
                db_escape($date),
                (float)$amount,
                $matchCriteria
            );
            
            $results = [];
            $res = db_query($query, 'Could not query for fuzzy duplicates');
            
            while ($row = db_fetch_assoc($res)) {
                $results[] = $row;
            }
            
            return $results;
        } catch (\Throwable $e) {
            throw TransactionFetchException::queryFailed(
                "SELECT * FROM bi_transactions WHERE date=? AND amount=? AND merchant|memo=?",
                $e->getMessage()
            );
        }
    }
}
```

### Step 5: Create New Chain Orchestrator

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateDetectionChain.php`**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts\DuplicateMatcherInterface;

/**
 * Dynamic Duplicate Detection Chain
 * 
 * Implements Chain of Responsibility pattern for duplicate detection.
 * Matchers are executed in priority order until one returns a match.
 * 
 * Usage:
 *   $chain = new DuplicateDetectionChain();
 *   $chain->addMatcher(new DirectCodeMatcher());
 *   $chain->addMatcher(new FuzzyMatcher());
 *   $chain->addMatcher(new RulesMatcher());
 *   
 *   $result = $chain->detect($transaction);
 */
class DuplicateDetectionChain
{
    /** @var DuplicateMatcherInterface[] Registered matchers, sorted by priority */
    private array $matchers = [];
    
    /**
     * Register a matcher in the chain.
     * 
     * Matchers are automatically sorted by priority after registration.
     * 
     * @param DuplicateMatcherInterface $matcher
     * @return self Fluent interface for chaining
     */
    public function addMatcher(DuplicateMatcherInterface $matcher): self
    {
        $this->matchers[] = $matcher;
        $this->sortByPriority();
        return $this;
    }
    
    /**
     * Detect if transaction is a duplicate using the registered matchers.
     * 
     * Executes matchers in priority order, stopping at first match.
     * 
     * @param array $transaction Transaction data
     * @return DuplicateCheckResult Result with action and matching records
     */
    public function detect(array $transaction): DuplicateCheckResult
    {
        // Execute matchers in priority order
        foreach ($this->matchers as $matcher) {
            $matchResult = $matcher->match($transaction);
            
            if ($matchResult !== null) {
                // Convert matcher result to detection result
                return $this->buildCheckResult($matchResult);
            }
        }
        
        // No matcher found a duplicate
        return DuplicateCheckResult::notDuplicate();
    }
    
    /**
     * Convert DuplicateMatchResult to DuplicateCheckResult.
     * 
     * Business logic for handling different matcher types.
     * 
     * @param DuplicateMatchResult $matchResult Result from a matcher
     * @return DuplicateCheckResult
     */
    private function buildCheckResult(DuplicateMatchResult $matchResult): DuplicateCheckResult
    {
        $matcherId = $matchResult->getMatcherId();
        $matchedRecord = $matchResult->getMatchedRecord();
        
        switch ($matcherId) {
            case 'direct_code':
                // Code match found
                $fieldsThatDiffer = $matchResult->getMetadata('fieldsThatDiffer', '');
                
                // If fields differ, flag for review (data corruption)
                // If fields match, it's a true duplicate (skip)
                return DuplicateCheckResult::exactMatch($matchedRecord, $fieldsThatDiffer);
            
            case 'fuzzy':
                // Fuzzy match found - need to check rules
                $allMatches = $matchResult->getMetadata('allMatches', [$matchedRecord]);
                
                // Check if there's a whitelist rule
                $rulesProvider = new DuplicateRulesProvider();
                $rule = $rulesProvider->findMatchingRule(/* need transaction context */);
                
                if ($rule && (int)($rule['allow_duplicates'] ?? 0) === 1) {
                    return DuplicateCheckResult::fuzzyMatchAllowed($matchedRecord, $rule);
                }
                
                // No rule or rule doesn't allow - needs review
                return DuplicateCheckResult::fuzzyMatchNeedsReview($allMatches);
            
            case 'rules':
                // Rule matcher returned a match
                $rule = $matchResult->getMetadata('appliedRule', []);
                $allowDuplicates = (bool)$matchResult->getMetadata('allowDuplicates', false);
                
                if ($allowDuplicates) {
                    return DuplicateCheckResult::fuzzyMatchAllowed($matchedRecord, $rule);
                }
                
                return DuplicateCheckResult::fuzzyMatchNeedsReview([$matchedRecord]);
            
            default:
                // Unknown matcher type - treat as potential duplicate
                return DuplicateCheckResult::fuzzyMatchNeedsReview([$matchedRecord]);
        }
    }
    
    /**
     * Sort matchers by priority (lower numbers first).
     */
    private function sortByPriority(): void
    {
        usort($this->matchers, function (DuplicateMatcherInterface $a, DuplicateMatcherInterface $b) {
            $priorityDiff = $a->getPriority() <=> $b->getPriority();
            
            // If priorities are equal, maintain registration order
            return $priorityDiff;
        });
    }
    
    /**
     * Get registered matchers (for debugging/testing).
     * 
     * @return DuplicateMatcherInterface[]
     */
    public function getMatchers(): array
    {
        return $this->matchers;
    }
}
```

### Step 6: Create Chain Factory (Optional - For Configuration)

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/DuplicateDetectionChainFactory.php`**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts\DuplicateMatcherInterface;

/**
 * Factory for building DuplicateDetectionChain from configuration.
 * 
 * Allows dynamic chain construction from arrays, databases, or service containers.
 */
class DuplicateDetectionChainFactory
{
    /**
     * Create chain from array configuration.
     * 
     * Example configuration:
     * ```php
     * $config = [
     *     [
     *         'class' => DirectCodeMatcher::class,
     *         'priority' => 0,
     *         'enabled' => true,
     *         'params' => []
     *     ],
     *     [
     *         'class' => FuzzyMatcher::class,
     *         'priority' => 10,
     *         'enabled' => true,
     *         'params' => ['amountTolerance' => 0.01]
     *     ]
     * ];
     * ```
     * 
     * @param array $config Matcher configurations
     * @return DuplicateDetectionChain
     * @throws \InvalidArgumentException If config is invalid
     */
    public static function fromArray(array $config): DuplicateDetectionChain
    {
        $chain = new DuplicateDetectionChain();
        
        foreach ($config as $matcherConfig) {
            if (!isset($matcherConfig['class'])) {
                throw new \InvalidArgumentException('Matcher config missing "class" key');
            }
            
            // Skip disabled matchers
            if (isset($matcherConfig['enabled']) && !$matcherConfig['enabled']) {
                continue;
            }
            
            $class = $matcherConfig['class'];
            $params = $matcherConfig['params'] ?? [];
            
            // Instantiate matcher
            $matcher = self::instantiateMatcher($class, $params);
            
            if (!$matcher instanceof DuplicateMatcherInterface) {
                throw new \InvalidArgumentException(
                    "Matcher {$class} must implement DuplicateMatcherInterface"
                );
            }
            
            $chain->addMatcher($matcher);
        }
        
        return $chain;
    }
    
    /**
     * Create default chain with standard matchers.
     * 
     * @return DuplicateDetectionChain
     */
    public static function createDefault(): DuplicateDetectionChain
    {
        return (new DuplicateDetectionChain())
            ->addMatcher(new DirectCodeMatcher())
            ->addMatcher(new FuzzyMatcher());
            // Note: RulesMatcher would be added here once created
    }
    
    /**
     * Instantiate a matcher from class name and parameters.
     * 
     * @param string $class Fully qualified class name
     * @param array $params Constructor parameters
     * @return object
     * @throws \InvalidArgumentException If class doesn't exist
     */
    private static function instantiateMatcher(string $class, array $params): object
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Matcher class not found: {$class}");
        }
        
        // For now, only use positional constructor parameters
        return new $class(...$params);
    }
}
```

### Step 7: Create RulesMatcher (New Matcher for Level 3)

**File: `src/Ksfraser/FaBankImport/Import/Services/DuplicateDetection/RulesMatcher.php`**

```php
<?php

namespace Ksfraser\FaBankImport\Import\Services\DuplicateDetection;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\Contracts\DuplicateMatcherInterface;

/**
 * Level 3: Rules Matcher
 * 
 * Applies whitelist rules to fuzzy matches.
 * 
 * NOTE: This matcher operates differently than others.
 * It doesn't detect duplicates directly, but rather evaluates
 * whether a previously detected duplicate is whitelisted.
 * 
 * Priority: 20 (runs last in the chain)
 */
class RulesMatcher implements DuplicateMatcherInterface
{
    private $rulesProvider;
    
    public function __construct(
        DuplicateRulesProvider $rulesProvider = null
    ) {
        $this->rulesProvider = $rulesProvider ?? new DuplicateRulesProvider();
    }
    
    /**
     * Implementation of DuplicateMatcherInterface.
     * 
     * This matcher doesn't find duplicates directly.
     * It's a policy evaluation matcher for fuzzy results.
     */
    public function match(array $transaction): ?DuplicateMatchResult
    {
        // This matcher would need context from previous matchers
        // For now, returns null (doesn't match on its own)
        return null;
    }
    
    public function getPriority(): int
    {
        return 20;  // Lowest priority - evaluates after other matchers
    }
    
    public function getId(): string
    {
        return 'rules';
    }
    
    /**
     * Check if a rule allows this duplicate.
     * 
     * This is called by the chain after a fuzzy match is found.
     * 
     * @param array $transaction Transaction being checked
     * @param array $matchedTransaction Existing transaction that matches
     * @return DuplicateMatchResult|null Match if rule allows, null otherwise
     */
    public function evaluateRule(
        array $transaction,
        array $matchedTransaction
    ): ?DuplicateMatchResult {
        $rule = $this->rulesProvider->findMatchingRule($transaction);
        
        if ($rule && (int)($rule['allow_duplicates'] ?? 0) === 1) {
            return DuplicateMatchResult::ruleMatch($matchedTransaction, $rule);
        }
        
        return null;
    }
}
```

---

## USAGE EXAMPLES

### Old Way (Current - Hardcoded)
```php
$service = new DuplicateDetectionService();
$result = $service->detect($transaction);

if ($result->getRecommendedAction() === 'SKIP') {
    // Skip this duplicate
}
```

### New Way (Dynamic Chain)
```php
// Build chain
$chain = new DuplicateDetectionChain();
$chain->addMatcher(new DirectCodeMatcher());
$chain->addMatcher(new FuzzyMatcher());

// Detect duplicates
$result = $chain->detect($transaction);

if ($result->getRecommendedAction() === 'SKIP') {
    // Skip this duplicate
}
```

### With Configuration
```php
$config = [
    [
        'class' => DirectCodeMatcher::class,
        'priority' => 0,
        'enabled' => true
    ],
    [
        'class' => FuzzyMatcher::class,
        'priority' => 10,
        'enabled' => true
    ]
];

$chain = DuplicateDetectionChainFactory::fromArray($config);
$result = $chain->detect($transaction);
```

### Adding New Matcher (Easy with Chain)
```php
// Current: Would need to modify DuplicateDetectionService constructor
// New: Just add it to the chain

$chain->addMatcher(new MyCustomMatcher(priority: 5));
```

---

## TESTING IMPROVEMENTS

### Before (Hardcoded - Difficult)
```php
public function testDirectCodeMatcher()
{
    // Must instantiate entire service
    $service = new DuplicateDetectionService(
        $directMatcher,
        $fuzzyMatcher,        // Not testing!
        $rulesProvider        // Not testing!
    );
    
    // Must mock internal calls
    $directMatcher->expects($this->once())
        ->method('findAndCompare')
        ->willReturn([...]);
    
    // Result depends on all components
    $result = $service->detect($transaction);
}
```

### After (Dynamic Chain - Easy)
```php
public function testDirectCodeMatcher()
{
    // Test just the matcher in isolation
    $matcher = new DirectCodeMatcher();
    $result = $matcher->match($transaction);
    
    // No dependencies, no mocks, clean test
    $this->assertNotNull($result);
    $this->assertEquals('direct_code', $result->getMatcherId());
}

public function testChainWithMultipleMatchers()
{
    $chain = new DuplicateDetectionChain();
    $chain->addMatcher(new DirectCodeMatcher());
    $chain->addMatcher(new FuzzyMatcher());
    
    $result = $chain->detect($transaction);
    
    $this->assertNotNull($result);
}
```

---

## MIGRATION STRATEGY

### Phase 1: Add New Code Alongside Old
```php
// Old API still works
$service = new DuplicateDetectionService();
$result = $service->detect($transaction);  // ← Still using hardcoded

// New API available
$chain = new DuplicateDetectionChain();
$result = $chain->detect($transaction);    // ← Using dynamic chain
```

### Phase 2: Service Uses Chain Internally
```php
class DuplicateDetectionService
{
    private $chain;
    
    public function __construct(...) {
        $this->chain = new DuplicateDetectionChain();
        $this->chain->addMatcher($direct ?? new DirectCodeMatcher());
        // ...
    }
    
    public function detect(array $transaction): DuplicateCheckResult
    {
        return $this->chain->detect($transaction);  // ← Delegates to chain
    }
}
```

### Phase 3: Migrate Callers Gradually
```php
// Old way still supported (backward compatible)
$service->detect($transaction);

// New way preferred
$chain->detect($transaction);
```

---

**Document Version:** 1.0  
**Created:** 2026-04-04  
**Focus:** Concrete PHP code examples for refactoring
