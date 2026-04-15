# Phase 0e: Partner Matching Refactoring Roadmap

## Executive Summary

This document provides a detailed refactoring roadmap for decomposing the existing partner matching system (currently scattered across `pdata.inc`, `search_partner_keywords.inc`, and views) into clean, testable services based on the Phase 0a-0e foundation.

**Key Principle**: Each PROD function contains mixed concerns. Refactoring involves:
1. Identifying all concerns within that function
2. Mapping each concern to a new SRP class (Phase 0a-0e)
3. Creating a new function/service that coordinates the new classes
4. Gradually replacing PROD code with calls to new services
5. Removing old PROD code once all callers migrated

---

## Phase 0a-0e Deliverables Summary

**New Classes Created (All Tested)**:
- `PartnerEntity` - Immutable domain model (8 tests)
- `PartnerMatchResult` - Immutable result with 6-factor scoring (7 tests)
- `PartnerRepository` interface + PDO implementation (12 tests)
- `KeywordExtractor` - Unified text processing (11 tests)
- `ScoringEngine` - 6-factor scoring algorithm (13 tests)
- `PartnerSearchService` - Orchestrator (10 tests)

**Total: 61 new tests, 0 regressions**

---

## PROD Code Decomposition Roadmap

### 1. `includes/pdata.inc` - CRUD Helper Functions

#### 1.1 `get_partner_data($partner_id, $partner_type, $partner_detail_id)`

**Current State**:
```php
function get_partner_data($partner_id, $partner_type, $partner_detail_id) {
    // WHERE partner_id = X AND partner_type = Y [AND partner_detail_id = Z]
    // Returns single row or empty array
}
```

**Mixed Concerns**:
- ✗ Data Access (raw SQL)
- ✗ Type-specific logic (PT_CUSTOMER vs ST_BANKTRANSFER branching)

**New Mapping**:
```
Concern                  → New Class/Method
─────────────────────────────────────────
Data Access (raw SQL)    → PartnerRepository::getById() or custom query
Type-specific logic      → Domain logic in PartnerEntity or Repository
```

**Refactoring Path**:
- **Phase 1**: Create wrapper function using `PartnerRepository->getById($id)`
- **Phase 2**: Update all callers to use wrapper
- **Phase 3**: Mark `get_partner_data()` as deprecated
- **Phase 4**: Remove once all callers migrated

**New Implementation Sketch**:
```php
function get_partner_data_refactored(int $partnerId, PartnerType $type, ?int $detailId = null) {
    $partner = $partnerRepository->getById($partnerId);
    // Additional filtering if needed
    return $partner ? [$partner->toArray()] : [];
}
```

---

#### 1.2 `set_partner_data($partner_id, $partner_type, $partner_detail_id, $data)`

**Current State**:
```php
function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    // 1. Fetch existing via get_partner_data()
    // 2. Duplicate prevention: call search_partner_by_bank_account()
    // 3. INSERT/UPDATE with MySQL-specific syntax
}
```

**Mixed Concerns**:
- ✗ Duplicate prevention (calls search function)
- ✗ Upsert logic (INSERT ... ON DUPLICATE)
- ✗ Validation (no checks on input)
- ✗ Data persistence

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Duplicate prevention           → PartnerRepository::exists() + verification logic
Upsert (INSERT/UPDATE)         → PartnerRepository::create() or ::update()
Data validation                → Domain validation in PartnerEntity constructor
Persistence                    → PartnerRepository interface
```

**Refactoring Path**:
- **Phase 1**: Create `PartnerDataService` that wraps PartnerRepository with duplicate checking
- **Phase 2**: Update all `set_partner_data()` callers to use new service
- **Phase 3**: Mark old function deprecated
- **Phase 4**: Remove

**New Implementation Sketch**:
```php
class PartnerDataService {
    public function setPartnerData(int $id, PartnerType $type, ?int $detailId, string $data): void {
        // Validation
        if (empty($data)) throw new InvalidArgumentException('Data cannot be empty');
        
        // Duplicate check
        $existing = $this->partnerRepository->getById($id);
        if ($existing && $existing->name() === $data) {
            return; // No-op if same
        }
        
        // Create or update
        if ($existing) {
            $updated = new PartnerEntity($id, $data, $type, $existing->occurrenceCount(), $existing->lastMatchedTs());
            $this->partnerRepository->update($updated);
        } else {
            $partner = new PartnerEntity(0, $data, $type);
            $this->partnerRepository->create($partner);
        }
    }
}
```

---

#### 1.3 `search_partner_data_by_needle($needle)` & 1.4 `search_partner_by_bank_account($partner_type, $needle)`

**Current State**:
```php
function search_partner_data_by_needle($needle) {
    // SELECT * FROM bi_partners_data WHERE data LIKE '%needle%'
    // Returns ALL fields, inefficient
}

function search_partner_by_bank_account($partner_type, $needle) {
    // SELECT * WHERE partner_type = X AND data LIKE '%needle%' LIMIT 1
    // Unpredictable ordering
}
```

**Mixed Concerns**:
- ✗ Substring search (LIKE pattern, inefficient)
- ✗ Type filtering (branching logic)
- ✗ No scoring/ranking
- ✗ Returns raw data without aggregation

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Substring search               → ScoringEngine::calculateSubstringScore()
Pattern matching               → PartnerSearchService::search()
Type filtering                 → PartnerRepository::getByType()
Ranking/scoring                → ScoringEngine with 6 factors
Result aggregation             → PartnerMatchResult
```

**Refactoring Path**:
- **Phase 1-3**: Replace all calls to these functions with `PartnerSearchService->search($text, $type)`
- **Phase 4**: Mark functions deprecated
- **Phase 5**: Remove

**New Implementation**:
```php
// Replace:
$results = search_partner_data_by_needle('Transaction Ref');

// With:
$results = $partnerSearchService->search('Transaction Ref', PartnerType::CUSTOMER);
// Returns: PartnerMatchResult[] sorted by confidence
```

---

#### 1.5 `update_partner_data($partner_id, $partner_type, $partner_detail_id, $data)`

**Current State**:
```php
function update_partner_data($partner_id, $partner_type, $partner_detail_id, $data) {
    // CONCAT(data, "\n") - appends with newline
    // No truncation limit - can grow unbounded!
}
```

**Mixed Concerns**:
- ✗ Data accumulation logic (append with newline)
- ✗ No size limits
- ✗ Raw SQL with CONCAT operator

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Data accumulation              → PartnerDataBuilder helper
Append with formatting         → String concatenation in service
Size limits                    → Validation in PartnerEntity
Persistence                    → PartnerRepository::update()
```

**Refactoring Path**:
- **Phase 1**: Create `PartnerDataBuilder` service that handles accumulation safely
- **Phase 2**: Update callers
- **Phase 3**: Deprecate
- **Phase 4**: Remove

**New Implementation Sketch**:
```php
class PartnerDataBuilder {
    private const MAX_DATA_SIZE = 2000; // Safety limit
    
    public function appendPartnerData(PartnerEntity $partner, string $newData): PartnerEntity {
        $existing = $partner->name();
        $combined = $existing . "\n" . $newData;
        
        if (strlen($combined) > self::MAX_DATA_SIZE) {
            throw new DomainException('Partner data exceeds maximum length');
        }
        
        return new PartnerEntity(
            $partner->id(),
            $combined,
            $partner->type(),
            $partner->occurrenceCount(),
            $partner->lastMatchedTs()
        );
    }
}
```

---

### 2. `includes/search_partner_keywords.inc` - Matching Engine

#### 2.1 `extract_keywords($text)` & `get_keyword_list($partner_type)`

**Current State**:
```php
function extract_keywords_for_search($text) {
    // Recognizes phrases, filters stopwords, removes short words
    // Sophisticated but isolated
}
```

**Mixed Concerns**:
- ✓ Good: Single responsibility (keyword extraction)
- ✓ Already testable
- ✗ Hardcoded stopwords and phrases (not configurable)

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Keyword extraction             → KeywordExtractor (Phase 0c)
Phrase recognition             → KeywordExtractor::extract()
Stopword filtering             → KeywordExtractor (hardcoded list)
```

**Refactoring Path**: 
- **Phase 1**: Use `KeywordExtractor->extract($text)` directly
- ✅ **Already Done** in Phase 0c

**Usage Change**:
```php
// Old:
$keywords = extract_keywords_for_search($text);

// New (Phase 0e):
$keywordExtractor = new KeywordExtractor();
$keywords = $keywordExtractor->extract($text);
```

---

#### 2.2 The Sophisticated Matching Algorithm (7 steps)

**Current State**:  
```php
// Unnamed function or embedded in search flow
// 1. Get keywords from transaction
// 2. Search for partners by type
// 3. For each partner:
//    - Calculate keyword overlap score
//    - Get occurrence count
//    - Calculate recency bonus
//    - Get clustering bonus from account
// 4. Multiply all factors
// 5. Threshold check
// 6. Return top result
```

**Mixed Concerns**:
- ✓ Pattern-first (no type assumptions) - good design
- ✗ Scattered across functions
- ✗ Not encapsulated in a service
- ✗ No testable API

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Keyword extraction             → KeywordExtractor (0c)
Partner retrieval              → PartnerRepository (0b)
Scoring calculation            → ScoringEngine (0d)
Orchestration                  → PartnerSearchService (0e)
Threshold checking             → autoSelect() threshold
```

**Refactoring Path**: 
- ✅ **Already Done** in Phase 0e: `PartnerSearchService->search()` and `->autoSelect()`

**Usage Change**:
```php
// Old scattered logic:
$keywords = extract_keywords_for_search($transactionText);
$partners = search_partner_keywords_by_type($type);
// ... manual scoring loop ...

// New (Phase 0e):
$results = $partnerSearchService->search($transactionText, $type);
$autoSelected = $partnerSearchService->autoSelect($transactionText, $type);
```

---

### 3. `build_partner_keyword_data.php` - Training Pipeline

#### 3.1 `extract_keywords()` (Duplicate)

**Current State**:
```php
// Duplicate of extraction logic from search_partner_keywords.inc
// Different implementation/parameters
```

**Issue**: Code duplication

**New Mapping**:
```
Shared concern                 → KeywordExtractor (Phase 0c)
```

**Refactoring Path**: 
- ✅ **Already Done** in Phase 0c: Created shared `KeywordExtractor` service

---

#### 3.2 `build_training_data()` & `learn_from_matches()`

**Current State**:
```php
// Iterates through transactions
// For each matched partner:
//   - Extract keywords from description
//   - Record occurrence_count++
//   - Update last_matched_ts
// Build training dataset
```

**Mixed Concerns**:
- ✗ Data iteration (transaction loop)
- ✗ Keyword extraction (use shared KeywordExtractor)
- ✗ Learning logic (occurrence tracking)
- ✗ Persistence (occurrence_count update)

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Keyword extraction             → KeywordExtractor (0c)
Partner update (occurrence)    → PartnerRepository::update()
Learning orchestration         → TrainingService (new)
Batch operations               → TrainingService::buildTrainingData()
```

**Refactoring Path**:
- **Phase 2**: Create `TrainingService` that:
  - Iterates transactions
  - Uses `KeywordExtractor->extract()` for each
  - Calls `PartnerRepository->update()` for occurrence tracking
  - Returns training statistics
- **Phase 3**: Update batch jobs to use TrainingService
- **Phase 4**: Remove old build scripts

**New Implementation Sketch**:
```php
class TrainingService {
    public function buildTrainingData(bool $dryRun = false): array {
        $stats = ['processed' => 0, 'learned' => 0, 'skipped' => 0];
        
        foreach ($this->getTransactions() as $transaction) {
            $keywords = $this->keywordExtractor->extract($transaction['reference']);
            
            $result = $this->partnerSearchService->autoSelect(
                $transaction['reference'],
                $transaction['partner_type']
            );
            
            if ($result && !$dryRun) {
                // autoSelect already updates occurrence_count and last_matched_ts
                $stats['learned']++;
            } else {
                $stats['skipped']++;
            }
            $stats['processed']++;
        }
        
        return $stats;
    }
}
```

---

### 4. `class.ViewBiLineItems.php` - Display Layer

#### 4.1 Display Methods with Embedded Business Logic

**Current State**:
```php
class ViewBiLineItems {
    public function getDisplayMatchingTrans() {
        // Get $_POST data
        // Call search functions
        // Filter results
        // Build HTML form
        // Handle $_POST mutation for updates
    }
}
```

**Mixed Concerns**:
- ✗ View logic (HTML rendering)
- ✗ Business logic (search/matching)
- ✗ State mutation ($_POST handling)
- ✗ No separation of concerns

**New Mapping**:
```
Concern                        → New Class/Method
──────────────────────────────────────────────────
Partner search                 → PartnerSearchService::search()
Result handling                → PartnerMatchPresenter (new)
HTML rendering                 → View template or presenter
State management               → Request handler service
```

**Refactoring Path**:
- **Phase 3**: Extract business logic from views into services
  - Create `PartnerMatchPresenter` to format results for display
  - Create `PartnerSelectionHandler` to process form submissions
  - Keep views for rendering only
- **Phase 4**: Wire new services into views
- **Phase 5**: Remove old mixed-concern methods

**New Implementation Pattern**:
```php
// OLD (mixed concerns):
public function getDisplayMatchingTrans() {
    $results = search_partner_keywords($_POST['reference'] ?? '');
    // ... 50 lines of filtering, mutation, rendering ...
}

// NEW (separated):
// Controller/Handler:
$results = $this->partnerSearchService->search($reference, $type);
$formatted = $this->partnerMatchPresenter->format($results);

// View:
foreach ($formatted as $match) {
    echo "<div class='match'>{$match['name']}</div>";
}
```

---

### 5. Orchestration & Configuration

#### 5.1 Partner Type Registry

**Current State**:
```php
class PartnerTypeRegistry {
    private array $types = [];
    
    public function register(PartnerType $type): void { ... }
    public function getPartnerType(int $code): PartnerType { ... }
}
```

**Status**: Already exists, used by new classes ✓

**Refactoring Path**: 
- ✅ Already integrated in Phase 0a

---

#### 5.2 Service Initialization & Dependency Injection

**Current State**: Scattered initialization across views and scripts

**New Mapping**:
```
Service                        Location
──────────────────────────────────────────
PartnerRepository              DI container / Factory
KeywordExtractor               DI container / Singleton
ScoringEngine                  DI container / Singleton
PartnerSearchService           DI container / Factory
TrainingService (Phase 2)      DI container / Factory
```

**Refactoring Path**:
- **Phase 1**: Create `PartnerServiceFactory` to initialize all services
- **Phase 2**: Register factory in DI container
- **Phase 3**: Update all callers to use factory instead of direct initialization

**Implementation Sketch**:
```php
class PartnerServiceFactory {
    public static function createSearchService(\PDO $pdo): PartnerSearchService {
        $repository = new PartnerRepositoryPdoImpl($pdo);
        $extractor = new KeywordExtractor();
        $scorer = new ScoringEngine();
        
        return new PartnerSearchService($repository, $extractor, $scorer);
    }
    
    public static function createTrainingService(\PDO $pdo): TrainingService {
        $searchService = self::createSearchService($pdo);
        $repository = new PartnerRepositoryPdoImpl($pdo);
        
        return new TrainingService($repository, $searchService);
    }
}
```

---

## Detailed Refactoring Timeline

### Phase 1: Schema & Service Migration
- Migrations: Add `occurrence_count`, `last_matched_ts` columns
- Create `PartnerSearchService` factory
- Create `PartnerDataService` wrapper for crud operations
- Update configuration points

### Phase 2: Data Migration & Learning
- Backfill `occurrence_count` from historical matches
- Create `TrainingService` for batch learning
- Migrate `build_partner_keyword_data.php` to use new service
- Update occurrence tracking on each successful match

### Phase 3: View Refactoring
- Extract business logic from `ViewBiLineItems`
- Create `PartnerMatchPresenter` for display formatting
- Create `PartnerSelectionHandler` for form processing
- Inject services into views via dependency injection

### Phase 4: Tier 2 ML Integration
- Integrate scikit-learn for learned weights
- Retrain model after every ~100 successful matches
- Add model versioning and rollback capability
- Create weight update service

### Phase 5: Tier 5 Ollama Integration
- Activate Ollama skeleton (created in Phase 0)
- Implement tie-breaking with LLM
- Add fallback to pattern-first if LLM unavailable
- Create Ollama client service

---

## Codebase Organization After Complete Refactoring

```
src/Ksfraser/FaBankImport/
├── Entity/
│   ├── PartnerEntity.php                    ✅ (0a)
│   ├── PartnerMatchResult.php               ✅ (0a)
│   ├── PartnerType.php                      ✅ (0a)
│
├── Application/Partner/
│   ├── KeywordExtractor.php                 ✅ (0c)
│   ├── ScoringEngine.php                    ✅ (0d)
│   ├── PartnerSearchService.php             ✅ (0e)
│   ├── PartnerDataService.php               📋 (Phase 1)
│   ├── TrainingService.php                  📋 (Phase 2)
│   ├── PartnerMatchPresenter.php            📋 (Phase 3)
│   ├── PartnerSelectionHandler.php          📋 (Phase 3)
│   ├── PartnerServiceFactory.php            📋 (Phase 1)
│
├── Infrastructure/Database/
│   ├── PartnerRepositoryPdoImpl.php          ✅ (0b)
│   ├── PartnerDataBuilder.php               📋 (Phase 1)
│
├── Contracts/
│   ├── PartnerRepository.php                ✅ (0b)
│   ├── PartnerService.php                   📋 (Phase 1)
│   ├── TrainingService.php                  📋 (Phase 2)
│
├── ML/ (New)
│   ├── WeightOptimizer.php                  📋 (Phase 4)
│   ├── ModelManager.php                     📋 (Phase 4)
│
├── LLM/ (New)
│   ├── OllamaClient.php                     📋 (Phase 5, skeleton ready)
│   ├── TiebreakerService.php                📋 (Phase 5)
```

---

## Success Criteria for Each Phase

### Phase 0e Completion ✅
- [x] 61 new tests created and passing
- [x] No regressions in existing tests
- [x] All 6 SRP classes created and tested
- [x] Independence verified (no PROD dependencies)
- [x] Decomposition mapping document created

### Phase 1 Completion
- [ ] Schema migrated (new columns added)
- [ ] All CRUD operations use PartnerRepository
- [ ] PartnerDataService in use
- [ ] Factory pattern initialized
- [ ] 70+ total tests passing

### Phase 2 Completion
- [ ] Backfill historical data
- [ ] TrainingService processing batch jobs
- [ ] Learned weights incorporated  
- [ ] 75+ total tests passing

### Phase 3 Completion
- [ ] Business logic extracted from views
- [ ] Presenter/Handler services in use
- [ ] No direct $_POST in views
- [ ] 80+ total tests passing

### Phase 4 Completion
- [ ] ML model training/inference working
- [ ] Weight updates after matched transactions
- [ ] Model versioning implemented
- [ ] 85+ total tests passing

### Phase 5 Completion
- [ ] Ollama container running
- [ ] Tie-breaking with LLM functional
- [ ] Graceful fallback to pattern-first
- [ ] 90+ total tests passing (new LLM tests)

---

## Migration Checklist for Phase 1-5

For each function to be refactored, follow this 5-step process:

**Step 1: Identify Concerns**
- List all responsibilities in current function
- Note any external dependencies (DB, config, globals)

**Step 2: Create New Services**
- One new service per bounded context/responsibility
- Use dependency injection
- Write tests first (TDD)

**Step 3: Implement Wrapper Function**
- New function calls new services
- Should be near 1-1 line replacement
- Returns same data structure as old function

**Step 4: Update Callers**
- Find all references to old function
- Update to call new wrapper
- Run tests to verify no regressions

**Step 5: Deprecate & Remove**
- Mark old function with @deprecated
- Wait 1-2 phases for user notice
- Remove once no calls remain

---

## Key Decisions & Trade-offs

### Pattern-First Scoring
- **Decision**: No type assumptions; all partners scored equally
- **Rationale**: Prevents misclassification; works for all partner types
- **Cost**: Slightly higher false positives than type-specific model
- **Benefit**: Unified matching logic; easier to maintain

### 6-Factor Scoring
- **Decision**: Multiply base factors × multipliers (not sum)
- **Rationale**: Recency & occurrence have larger impact this way
- **Cost**: Algorithm is less intuitive
- **Benefit**: Matches user expectations (recent high-occurrence partners prioritized)

### Learned Weights (Phase 4)
- **Decision**: Defer until after 100 successful matches
- **Rationale**: Not enough training data earlier; pattern-first sufficient initially
- **Cost**: Tier 2 not available immediately
- **Benefit**: More reliable model training; clearer migration path

### Ollama Integration (Phase 5)
- **Decision**: Dormant skeleton now; activate when container ready
- **Rationale**: Avoid external dependencies in Phase 0
- **Cost**: LLM tie-breaking not available until Phase 5
- **Benefit**: Zero API costs; self-hosted; 1-3s latency acceptable

---

## Conclusion

The Phase 0e foundation establishes a clean, testable architecture with:
- ✅ 61 new tests passing
- ✅ 0 regressions  
- ✅ 6 SRP classes decoupled from PROD code
- ✅ Clear remapping of all PROD functions to new services
- ✅ Detailed refactoring roadmap for Phases 1-5

**Next Step**: Review this mapping document and confirm the refactoring strategy before proceeding to Phase 1.
