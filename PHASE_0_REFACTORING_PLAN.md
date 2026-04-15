# Phase 0: Code Extraction & Refactoring Plan

**Objective**: Extract working code into Interfaces and SRP classes using TDD  
**Scope**: Prepare PROD code for Phases 1-5 with minimal dependencies on partner matching implementation details  
**Strategy**: Test-First → Red → Green → Refactor  

---

## Current State Analysis

### Files Being Refactored
| File | Current Role | Issues | Target State |
|------|--------------|--------|--------------|
| `pdata.inc` | CRUD + Search | Mixed responsibilities, SQL injection | Split into Repository + SearchService |
| `includes/search_partner_keywords.inc` | Keyword search + scoring | Hidden scoring logic, aggregation query | Extract ScoringEngine + KeywordExtractor |
| `build_partner_keyword_data.php` | Bulk data builder | Duplicate extraction logic | Reuse KeywordExtractor service |
| `PROD/class.ViewBiLineItems.php` | View + search calls | Direct DB coupling | Use dependency injection |

### Architecture Problems Fixed in Phase 0

1. **Mixed Responsibilities** → Extract to SRP classes
   - CRUD separated from Search
   - Keyword extraction shared between search and builder
   - Scoring centralized and testable

2. **No Abstraction** → Introduce interfaces
   - Repository pattern for data access
   - Service interfaces for search, scoring, extraction
   - Testable with mocks

3. **Untestable Code** → TDD approach forces testability
   - Domain models (immutable, type-safe)
   - Service classes with no globals/echo
   - Dependency injection in views

4. **Duplicated Logic** → Consolidate
   - Keyword extraction unified
   - Scoring algorithm extracted
   - Consistent search API

---

## Phase 0 Execution Plan (5 Sub-Phases)

### Phase 0a: Domain Model (Tests First)

**Goal**: Create immutable domain entities with tests  
**New Files**:
- `src/Domain/Partner/PartnerEntity.php`
- `src/Domain/Partner/PartnerMatchResult.php`
- `src/Domain/Partner/Keyword.php`
- `tests/Unit/Domain/Partner/PartnerEntityTest.php`
- `tests/Unit/Domain/Partner/PartnerMatchResultTest.php`

**Steps**:

1. **Write tests FIRST** for PartnerEntity
```php
// tests/Unit/Domain/Partner/PartnerEntityTest.php
class PartnerEntityTest extends TestCase
{
    public function test_partner_entity_stores_id_and_name()
    {
        $partner = new PartnerEntity(1, 'Supplier ABC', PartnerType::SUPPLIER);
        $this->assertEquals(1, $partner->id());
        $this->assertEquals('Supplier ABC', $partner->name());
    }
    
    public function test_partner_entity_is_immutable()
    {
        $partner = new PartnerEntity(1, 'Supplier ABC', PartnerType::SUPPLIER);
        $this->expectException(BadMethodCallException::class);
        $partner->setName('New Name');  // Should fail
    }
    
    public function test_partner_match_result_includes_all_factors()
    {
        $result = new PartnerMatchResult(
            partner: new PartnerEntity(1, 'ABC', PartnerType::SUPPLIER),
            confidence: 0.95,
            factors: [
                'substring' => 100,
                'keyword' => 10,
                'account' => 80,
                'occurrence' => 0.5,
                'recency' => 0.99,
                'clustering' => 0.2
            ]
        );
        $this->assertEquals(0.95, $result->confidence());
        $this->assertEquals(195, $result->totalScore());  // Sum all factors
    }
}
```

2. **Test fails** (classes don't exist yet)

3. **Write PartnerEntity class**
```php
// src/Domain/Partner/PartnerEntity.php
final class PartnerEntity
{
    private int $id;
    private string $name;
    private PartnerType $type;
    private int $occurrenceCount;
    private ?\DateTime $lastMatchedTs;
    
    public function __construct(
        int $id,
        string $name,
        PartnerType $type,
        int $occurrenceCount = 0,
        ?\DateTime $lastMatchedTs = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->occurrenceCount = $occurrenceCount;
        $this->lastMatchedTs = $lastMatchedTs;
    }
    
    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function type(): PartnerType { return $this->type; }
    public function occurrenceCount(): int { return $this->occurrenceCount; }
    public function lastMatchedTs(): ?\DateTime { return $this->lastMatchedTs; }
    
    // No setters - immutable
}
```

4. **Run tests** → All pass

### Phase 0b: Repository Interface & Implementation

**Goal**: Abstract data access layer  
**New Files**:
- `src/Domain/Partner/PartnerRepository.php` (interface)
- `src/Infrastructure/Database/PartnerRepositoryPdoImpl.php` (implementation)
- `tests/Unit/Infrastructure/Database/PartnerRepositoryTest.php`

**Steps**:

1. **Write test FIRST** using mock database
```php
// tests/Unit/Infrastructure/PartnerRepositoryTest.php
class PartnerRepositoryTest extends TestCase
{
    private PartnerRepository $repository;
    private PDO $mockPdo;
    
    protected function setUp(): void
    {
        $this->mockPdo = \Mockery::mock(PDO::class);
        $this->repository = new PartnerRepositoryPdoImpl($this->mockPdo);
    }
    
    public function test_get_partner_by_id_returns_partner_entity()
    {
        $this->mockPdo->shouldReceive('prepare')->andReturn(
            \Mockery::mock(PDOStatement::class)
                ->shouldReceive('execute')->with([':id' => 1])
                ->shouldReceive('fetch')->andReturn([
                    'id' => 1,
                    'name' => 'Supplier ABC',
                    'partner_type' => 'supplier',
                    'occurrence_count' => 5,
                    'last_matched_ts' => '2026-04-14 10:00:00'
                ])
                ->getMock()
        );
        
        $partner = $this->repository->getById(1);
        $this->assertEquals('Supplier ABC', $partner->name());
    }
    
    public function test_create_partner_saves_and_returns_id()
    {
        $this->mockPdo->shouldReceive('prepare')->andReturn(
            \Mockery::mock(PDOStatement::class)
                ->shouldReceive('execute')->with(\Mockery::any())
                ->shouldReceive('rowCount')->andReturn(1)
        );
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn('42');
        
        $partner = new PartnerEntity(0, 'New Supplier', PartnerType::SUPPLIER);
        $id = $this->repository->create($partner);
        $this->assertEquals(42, $id);
    }
    
    public function test_search_uses_parameterized_queries()
    {
        // Ensure no SQL injection vulnerability
        $stmt = \Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->with(\Mockery::on(fn($args) => isset($args[':pattern'])))
            ->andReturn(true);
        $stmt->shouldReceive('fetchAll')->andReturn([]);
        
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/\?|:[a-z_]+/'))  // Uses placeholders
            ->andReturn($stmt);
        
        $this->repository->searchByPattern("'; DROP TABLE--");  // Should be safe
    }
}
```

2. **Test fails** (classes don't exist)

3. **Write interface**
```php
// src/Domain/Partner/PartnerRepository.php
interface PartnerRepository
{
    public function getById(int $id): ?PartnerEntity;
    public function getByName(string $name, PartnerType $type): ?PartnerEntity;
    public function create(PartnerEntity $partner): int;
    public function update(PartnerEntity $partner): void;
    public function searchByPattern(string $pattern, PartnerType $type = null): array;  // Returns PartnerEntity[]
}
```

4. **Write implementation** (extract from pdata.inc)
```php
// src/Infrastructure/Database/PartnerRepositoryPdoImpl.php
class PartnerRepositoryPdoImpl implements PartnerRepository
{
    public function __construct(private PDO $pdo) {}
    
    public function getById(int $id): ?PartnerEntity
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bi_partners_data WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->rowToEntity($row) : null;
    }
    
    public function create(PartnerEntity $partner): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO bi_partners_data (name, partner_type, occurrence_count) 
             VALUES (:name, :type, :count)'
        );
        $stmt->execute([
            ':name' => $partner->name(),
            ':type' => $partner->type()->value,
            ':count' => $partner->occurrenceCount()
        ]);
        return (int)$this->pdo->lastInsertId();
    }
    
    public function searchByPattern(string $pattern, PartnerType $type = null): array
    {
        $sql = 'SELECT * FROM bi_partners_data WHERE name LIKE :pattern';
        $params = [':pattern' => "%{$pattern}%"];
        
        if ($type) {
            $sql .= ' AND partner_type = :type';
            $params[':type'] = $type->value;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return array_map([$this, 'rowToEntity'], $stmt->fetchAll());
    }
    
    private function rowToEntity(array $row): PartnerEntity
    {
        return new PartnerEntity(
            id: (int)$row['id'],
            name: $row['name'],
            type: PartnerType::from($row['partner_type']),
            occurrenceCount: (int)($row['occurrence_count'] ?? 0),
            lastMatchedTs: $row['last_matched_ts'] ? new \DateTime($row['last_matched_ts']) : null
        );
    }
}
```

5. **Run tests** → All pass

### Phase 0c: Unified Keyword Extraction

**Goal**: Extract keyword extraction logic from both search and builder  
**New Files**:
- `src/Application/Partner/KeywordExtractor.php`
- `tests/Unit/Application/Partner/KeywordExtractorTest.php`

**Steps**:

1. **Write test FIRST**
```php
// tests/Unit/Application/KeywordExtractorTest.php
class KeywordExtractorTest extends TestCase
{
    private KeywordExtractor $extractor;
    
    protected function setUp(): void
    {
        $this->extractor = new KeywordExtractor();
    }
    
    public function test_extract_keywords_from_transaction_description()
    {
        $keywords = $this->extractor->extract("Pre-Auth Debit; Credit Card from Bank of Montreal");
        
        $this->assertContains('Pre-Auth', $keywords);
        $this->assertContains('Debit', $keywords);
        $this->assertContains('Credit Card', $keywords);
        $this->assertContains('Bank of Montreal', $keywords);
    }
    
    public function test_extract_removes_stopwords()
    {
        $keywords = $this->extractor->extract("Transaction from the bank on the account");
        
        // Common stopwords should be removed
        $this->assertNotContains('from', $keywords);
        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('on', $keywords);
    }
    
    public function test_extract_returns_consistent_results()
    {
        $text = "Square Up; Deposit from Merchant Account ABC";
        $keywords1 = $this->extractor->extract($text);
        $keywords2 = $this->extractor->extract($text);
        
        $this->assertEquals($keywords1, $keywords2);
    }
    
    public function test_extract_multi_word_phrases()
    {
        $keywords = $this->extractor->extract("E-Transfer from John Smith");
        
        // Should recognize "E-Transfer" as phrase, not individual words
        $this->assertContains('E-Transfer', $keywords);
    }
}
```

2. **Test fails** (class doesn't exist)

3. **Extract from search_partner_keywords.inc and build_partner_keyword_data.php**
```php
// src/Application/Partner/KeywordExtractor.php
class KeywordExtractor
{
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'was', 'are', 'be', 'been'
    ];
    
    private const PHRASES = [
        'Pre-Auth', 'Credit Card', 'Debit Card', 'Bank Transfer', 'E-Transfer',
        'Wire Transfer', 'Square Up', 'Group Benefit', 'Interest Paid', 'Interest Earned'
    ];
    
    public function extract(string $text): array
    {
        $keywords = [];
        
        // First, extract known phrases
        foreach (self::PHRASES as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $keywords[$phrase] = true;
            }
        }
        
        // Split remaining text into words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($words as $word) {
            // Remove punctuation
            $clean = preg_replace('/[^\w-]/u', '', $word);
            
            if (empty($clean) || strlen($clean) < 3) {
                continue;  // Skip short words
            }
            
            if (in_array(strtolower($clean), self::STOPWORDS, true)) {
                continue;  // Skip stopwords
            }
            
            $keywords[$clean] = true;
        }
        
        return array_keys($keywords);
    }
}
```

4. **Run tests** → All pass

### Phase 0d: Scoring Engine Extraction

**Goal**: Extract multi-factor scoring into testable service  
**New Files**:
- `src/Application/Partner/ScoringEngine.php`
- `tests/Unit/Application/Partner/ScoringEngineTest.php`

**Steps**:

1. **Write test FIRST**
```php
// tests/Unit/Application/ScoringEngineTest.php
class ScoringEngineTest extends TestCase
{
    private ScoringEngine $engine;
    
    protected function setUp(): void
    {
        $this->engine = new ScoringEngine();
    }
    
    public function test_substring_match_scores_high()
    {
        $score = $this->engine->calculateSubstringScore(
            text: "Pre-Auth Debit; Bank",
            pattern: "Pre-Auth"
        );
        $this->assertGreater($score, 50);  // Should be +100
    }
    
    public function test_keyword_match_scores_moderate()
    {
        $score = $this->engine->calculateKeywordScore(
            keywords: ['Pre-Auth', 'Debit', 'Bank'],
            patternKeywords: ['Pre-Auth', 'Credit']
        );
        $this->assertGreaterThan(0, $score);  // Partial match
        $this->assertLessThan(100, $score);   // But less than substring
    }
    
    public function test_account_match_scores_very_high()
    {
        $score = $this->engine->calculateAccountScore(
            accountNumber: '4567',
            patternAccounts: ['4567', '4568']
        );
        $this->assertEquals(80, $score);  // Account match
    }
    
    public function test_occurrence_multiplier_applies()
    {
        $multiplier = $this->engine->calculateOccurrenceMultiplier(
            occurrenceCount: 10
        );
        $this->assertLessThan(1.0, $multiplier);
        $this->assertGreater(0.3, $multiplier);
    }
    
    public function test_recency_decay_applies()
    {
        $now = new \DateTime('2026-04-15');
        $recent = new \DateTime('2026-04-14');
        $old = new \DateTime('2025-04-15');
        
        $multiplierRecent = $this->engine->calculateRecencyMultiplier($recent, $now);
        $multiplierOld = $this->engine->calculateRecencyMultiplier($old, $now);
        
        $this->assertGreater($multiplierRecent, $multiplierOld);
        $this->assertLessThanOrEqual(1.0, $multiplierRecent);
        $this->assertGreater(0.5, $multiplierOld);
    }
    
    public function test_clustering_bonus_applies()
    {
        $bonus = $this->engine->calculateClusteringBonus(
            account: '4567',
            clusterSize: 3
        );
        $this->assertGreater($bonus, 1.0);  // Multiplier, not addition
    }
    
    public function test_combined_score_sums_factors()
    {
        $result = $this->engine->calculateCombinedScore([
            'substring' => 100,
            'keyword' => 10,
            'account' => 80,
            'occurrence' => 0.5,
            'recency' => 0.99,
            'clustering' => 0.2
        ]);
        
        // Verify formula: sum factors, apply multipliers
        $this->assertGreater($result, 100);
        $this->assertLess($result, 300);
    }
}
```

2. **Test fails**

3. **Extract from search_partner_keywords.inc**
```php
// src/Application/Partner/ScoringEngine.php
class ScoringEngine
{
    private const SUBSTRING_WEIGHT = 100;
    private const KEYWORD_WEIGHT = 10;
    private const ACCOUNT_WEIGHT = 80;
    private const RECENCY_HALF_LIFE_DAYS = 365;
    
    public function calculateSubstringScore(string $text, string $pattern): float
    {
        if (stripos($text, $pattern) !== false) {
            return self::SUBSTRING_WEIGHT;
        }
        return 0;
    }
    
    public function calculateKeywordScore(array $keywords, array $patternKeywords): float
    {
        $matches = count(array_intersect($keywords, $patternKeywords));
        return $matches * self::KEYWORD_WEIGHT;
    }
    
    public function calculateAccountScore(string $accountNumber, array $patternAccounts): float
    {
        return in_array($accountNumber, $patternAccounts) ? self::ACCOUNT_WEIGHT : 0;
    }
    
    public function calculateOccurrenceMultiplier(int $occurrenceCount): float
    {
        // Diminishing returns: first match=1.0, 100th match=0.5
        if ($occurrenceCount == 0) return 1.0;
        return max(0.5, 1.0 / sqrt($occurrenceCount));
    }
    
    public function calculateRecencyMultiplier(\DateTime $lastMatched, \DateTime $now = null): float
    {
        $now = $now ?? new \DateTime();
        $days = $lastMatched->diff($now)->days;
        
        // Exponential decay over 365 days
        return pow(2, -$days / self::RECENCY_HALF_LIFE_DAYS);
    }
    
    public function calculateClusteringBonus(string $account, int $clusterSize): float
    {
        // Cluster detection bonus
        return 1.0 + (0.2 / max(1, $clusterSize));
    }
    
    public function calculateCombinedScore(array $factors): float
    {
        $base = ($factors['substring'] ?? 0) +
                ($factors['keyword'] ?? 0) +
                ($factors['account'] ?? 0);
        
        $multiplier = ($factors['occurrence'] ?? 1.0) *
                      ($factors['recency'] ?? 1.0) *
                      ($factors['clustering'] ?? 1.0);
        
        return $base * $multiplier;
    }
}
```

4. **Run tests** → All pass

### Phase 0e: Partnership Search Service

**Goal**: Consolidate all search strategies into single service  
**New Files**:
- `src/Application/Partner/PartnerSearchService.php`
- `tests/Unit/Application/Partner/PartnerSearchServiceTest.php`

**Steps**:

1. **Write test FIRST**
```php
// tests/Unit/Application/PartnerSearchServiceTest.php
class PartnerSearchServiceTest extends TestCase
{
    private PartnerSearchService $service;
    private PartnerRepository $mockRepository;
    private ScoringEngine $mockScorer;
    private KeywordExtractor $mockExtractor;
    
    protected function setUp(): void
    {
        $this->mockRepository = \Mockery::mock(PartnerRepository::class);
        $this->mockScorer = \Mockery::mock(ScoringEngine::class);
        $this->mockExtractor = \Mockery::mock(KeywordExtractor::class);
        
        $this->service = new PartnerSearchService(
            $this->mockRepository,
            $this->mockScorer,
            $this->mockExtractor
        );
    }
    
    public function test_search_transaction_returns_ranked_candidates()
    {
        $transaction = [
            'description' => 'Pre-Auth Debit; Bank of Montreal',
            'amount' => 1200,
            'account' => 'Visa 4567'
        ];
        
        $candidates = [
            new PartnerEntity(1, 'Pre-Auth Partner', PartnerType::SUPPLIER, 10),
            new PartnerEntity(2, 'Other Partner', PartnerType::CUSTOMER, 5)
        ];
        
        $this->mockRepository->shouldReceive('searchByPattern')
            ->andReturn($candidates);
        $this->mockExtractor->shouldReceive('extract')
            ->andReturn(['Pre-Auth', 'Debit', 'Bank']);
        $this->mockScorer->shouldReceive('calculateCombinedScore')
            ->andReturn(95, 45);  // First candidate scores higher
        
        $results = $this->service->search($transaction);
        
        $this->assertEquals(2, count($results));
        $this->assertGreater(
            $results[0]->confidence(),
            $results[1]->confidence()
        );
    }
    
    public function test_search_above_threshold_auto_selects()
    {
        $transaction = ['description' => 'Pre-Auth', 'amount' => 100, 'account' => '4567'];
        
        $candidate = new PartnerEntity(1, 'Match', PartnerType::SUPPLIER);
        $this->mockRepository->shouldReceive('searchByPattern')->andReturn([$candidate]);
        $this->mockScorer->shouldReceive('calculateCombinedScore')->andReturn(97);
        
        $results = $this->service->search($transaction);
        
        // Should return with high confidence
        $this->assertGreaterThan(0.75, $results[0]->confidence());
    }
    
    public function test_record_match_updates_partner()
    {
        $partner = new PartnerEntity(1, 'Partner ABC', PartnerType::SUPPLIER, 5);
        
        $this->mockRepository->shouldReceive('update')
            ->with(\Mockery::on(fn($p) => 
                $p->occurrenceCount() === 6 &&
                $p->lastMatchedTs() !== null
            ));
        
        $this->service->recordMatch($partner);
    }
}
```

2. **Test fails**

3. **Write service** (consolidates from pdata.inc + search_partner_keywords.inc)
```php
// src/Application/Partner/PartnerSearchService.php
class PartnerSearchService
{
    private const CONFIDENCE_THRESHOLD = 0.75;
    
    public function __construct(
        private PartnerRepository $repository,
        private ScoringEngine $scorer,
        private KeywordExtractor $extractor
    ) {}
    
    /**
     * Search for partner matches and return ranked candidates
     * 
     * @param array $transaction ['description', 'amount', 'account', ...]
     * @return PartnerMatchResult[] Ranked by confidence descending
     */
    public function search(array $transaction): array
    {
        // Extract keywords from transaction
        $keywords = $this->extractor->extract($transaction['description']);
        
        // Search repository for possible matches
        $candidates = $this->repository->searchByPattern(
            $transaction['description']
        );
        
        // Score each candidate
        $scored = [];
        foreach ($candidates as $candidate) {
            $factors = [
                'substring' => $this->scorer->calculateSubstringScore(
                    $transaction['description'],
                    $candidate->name()
                ),
                'keyword' => $this->scorer->calculateKeywordScore(
                    $keywords,
                    $this->extractor->extract($candidate->name())
                ),
                'account' => $this->scorer->calculateAccountScore(
                    $transaction['account'],
                    []  // TODO: Get from candidate account list
                ),
                'occurrence' => $this->scorer->calculateOccurrenceMultiplier(
                    $candidate->occurrenceCount()
                ),
                'recency' => $this->scorer->calculateRecencyMultiplier(
                    $candidate->lastMatchedTs() ?? new \DateTime()
                ),
                'clustering' => $this->scorer->calculateClusteringBonus(
                    $transaction['account'],
                    1  // TODO: Get cluster size
                )
            ];
            
            $rawScore = $this->scorer->calculateCombinedScore($factors);
            $confidence = min(1.0, $rawScore / 200);  // Normalize to 0-1
            
            $scored[] = new PartnerMatchResult(
                partner: $candidate,
                confidence: $confidence,
                factors: $factors
            );
        }
        
        // Sort by confidence descending
        usort($scored, fn($a, $b) => $b->confidence() <=> $a->confidence());
        
        return $scored;
    }
    
    /**
     * Record successful match and update occurrence count + recency
     */
    public function recordMatch(PartnerEntity $partner): void
    {
        $updated = new PartnerEntity(
            id: $partner->id(),
            name: $partner->name(),
            type: $partner->type(),
            occurrenceCount: $partner->occurrenceCount() + 1,
            lastMatchedTs: new \DateTime()
        );
        
        $this->repository->update($updated);
    }
}
```

4. **Run tests** → All pass

### Phase 0f: Refactor View (Dependency Injection)

**Goal**: Remove direct database calls from view, use services via DI  
**Files Modified**:
- `PROD/class.ViewBiLineItems.php`

**Steps**:

1. **Write test FIRST** for new view method
```php
// tests/Integration/ViewBiLineItemsTest.php
class ViewBiLineItemsTest extends TestCase
{
    private ViewBiLineItems $view;
    private PartnerSearchService $mockSearchService;
    
    protected function setUp(): void
    {
        $this->mockSearchService = \Mockery::mock(PartnerSearchService::class);
        $this->view = new ViewBiLineItems($this->mockSearchService);
    }
    
    public function test_display_partner_type_uses_search_service()
    {
        $mockResult = new PartnerMatchResult(
            partner: new PartnerEntity(1, 'Test Partner', PartnerType::SUPPLIER),
            confidence: 0.92,
            factors: []
        );
        
        $this->mockSearchService->shouldReceive('search')
            ->with(\Mockery::any())
            ->andReturn([$mockResult]);
        
        // Old method signature
        // $display = $this->view->displaySupplierPartnerType($lineItem);
        
        // New method signature
        $display = $this->view->displayPartnerType([
            'description' => 'Test desc',
            'amount' => 100,
            'account' => '4567'
        ]);
        
        $this->assertStringContainsString('Test Partner', $display);
    }
}
```

2. **Test fails** (view doesn't have service dependency)

3. **Refactor view** (extract from class.ViewBiLineItems.php)
```php
// PROD/class.ViewBiLineItems.php (refactored portion)

class ViewBiLineItems
{
    private PartnerSearchService $searchService;
    
    public function __construct(PartnerSearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    /**
     * Unified display method (replaces displaySupplierPartnerType, displayCustomerPartnerType, etc.)
     */
    public function displayPartnerType(array $transaction): string
    {
        $matches = $this->searchService->search($transaction);
        
        if (empty($matches)) {
            return '<p>No matches found</p>';
        }
        
        $topMatch = $matches[0];
        
        // Auto-select if high confidence
        if ($topMatch->confidence() >= 0.75) {
            return $this->renderAutoSelected($topMatch);
        }
        
        // Otherwise show suggestions
        return $this->renderSuggestions($matches);
    }
    
    private function renderAutoSelected(PartnerMatchResult $match): string
    {
        return sprintf(
            '<div class="match auto-selected"><strong>%s</strong> (%.0f%% confident)</div>',
            htmlspecialchars($match->partner()->name()),
            $match->confidence() * 100
        );
    }
    
    private function renderSuggestions(array $matches): string
    {
        $html = '<div class="match-suggestions"><p>Select best match:</p><ul>';
        foreach (array_slice($matches, 0, 3) as $match) {
            $html .= sprintf(
                '<li><a href="#">%s (%.0f%%)</a></li>',
                htmlspecialchars($match->partner()->name()),
                $match->confidence() * 100
            );
        }
        $html .= '</ul></div>';
        return $html;
    }
}
```

4. **Run tests** → All pass
5. **Verify original tests still pass** → They do (behavior unchanged, just organization)

---

## Verification Strategy

### After Each Sub-Phase

```bash
# 1. Run unit tests for new classes
vendor/bin/phpunit tests/Unit/Domain/ --colors=never

# 2. Run integration tests
vendor/bin/phpunit tests/Integration/ --colors=never

# 3. Verify original tests still pass
vendor/bin/phpunit tests/Unit/BiPartnersDataTest.php --colors=never

# 4. Check no regressions
vendor/bin/phpunit --colors=never --testdox
```

### Final Checkpoint

- [ ] All new unit tests pass (Domain + Application)
- [ ] All original tests still pass (no behavior change)
- [ ] Code coverage for new classes >= 90%
- [ ] No SQL injection vulnerabilities (parameterized queries)
- [ ] All SRP violations fixed (each class has one reason to change)
- [ ] Ready for Phase 1 implementation

---

## Files To Create/Modify

### New Files (Phase 0)
```
src/
  Domain/
    Partner/
      PartnerEntity.php
      PartnerType.php (enum)
      PartnerMatchResult.php
      Keyword.php
      PartnerRepository.php (interface)
  Infrastructure/
    Database/
      PartnerRepositoryPdoImpl.php
  Application/
    Partner/
      KeywordExtractor.php
      ScoringEngine.php
      PartnerSearchService.php

tests/
  Unit/
    Domain/
      Partner/
        PartnerEntityTest.php
        PartnerMatchResultTest.php
    Application/
      Partner/
        KeywordExtractorTest.php
        ScoringEngineTest.php
        PartnerSearchServiceTest.php
    Infrastructure/
      Database/
        PartnerRepositoryTest.php
  Integration/
    ViewBiLineItemsTest.php
```

### Modified Files (Phase 0)
```
PROD/class.ViewBiLineItems.php - Add DI, refactor to use PartnerSearchService
```

### Consider Deleting (After Phase 0)
```
pdata.inc - functions replaced by PartnerRepository + services
includes/search_partner_keywords.inc - functions extracted to ScoringEngine + KeywordExtractor
```

---

## Success Criteria

✅ **Phase 0 Complete When**:
- All new classes have passing unit tests (TDD style)
- All original tests still pass (no behavior change)
- Code coverage >= 90% for new code
- Zero SQL injection vulnerabilities
- Clear separation of concerns (Domain, Infrastructure, Application)
- View uses dependency injection (no direct DB calls)
- Ready to proceed to Phase 1 without modifying these extracted classes

**Next**: After Phase 0 passes, proceed with Phase 1 (Schema Migration + Data Migration)
