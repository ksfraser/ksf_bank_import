---
title: "Story 2: Duplicate Review Service - Implementation Plan"
version: "1.0"
date_created: "2026-04-09"
---

# Implementation Plan - DuplicateReviewService

## 1. Goal

Implement a robust, testable `DuplicateReviewService` that records human reviewer decisions on detected duplicate transactions. The service will:
- Record decisions (approve/reject/investigate) with full audit trails
- Enforce valid workflow transitions using state machine pattern
- Publish domain events for downstream integration
- Handle concurrent requests safely via database transactions
- Follow SOLID principles with 85%+ unit test coverage
- Be framework-agnostic and horizontally scalable

**Completion Criteria:**
- All 28 unit/integration/performance tests passing (100%)
- Code coverage ≥ 85%
- SonarQube Grade A
- No security vulnerabilities
- Conventional commit with comprehensive message

## 2. Technical Considerations

### 2.1 System Architecture Diagram

```
┌──────────────────────────────────────────────────────────┐
│                  STORY 3: Admin Controller                │
│         (HTTP Request → DuplicateReviewService)          │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│  DuplicateReviewService                                   │
│  ├─ approve(tx, decidedBy, reason): ReviewDecision       │
│  ├─ reject(tx, decidedBy, reason): ReviewDecision        │
│  └─ investigate(tx, decidedBy, notes): ReviewDecision    │
│                                                           │
│  Dependencies (via DI):                                   │
│  ├─ DuplicateTransactionRepository                        │
│  ├─ EventPublisher (interface-based)                      │
│  └─ Logger                                                │
└─┬──────────────────────────────────┬──────────────────┬──┘
  │                                  │                  │
  ▼                                  ▼                  ▼
STORY 1: Repository        Event Publishing      Domain Events
├─ queries by ID           ├─ Publish to         ├─ DuplicateDecisionMade
├─ updates status            listeners             (transactional)
└─ creates audit record    └─ Exception safe     └─ Can be queued/logged

                                    │
                                    ▼
                            STORY 4: Integration
                            (Listen for APPROVED)
```

### 2.2 Key Design Patterns

| Pattern | Application | Benefit |
|---------|-------------|---------|
| **Dependency Injection** | Service constructor receives repository, event publisher, logger | Testability, decoupling |
| **State Machine** | Workflow transitions (PENDING → {APPROVED,REJECTED,INVESTIGATE}) | Correctness, prevents invalid states |
| **Domain Event** | DuplicateDecisionMade published on success | Loose coupling, Story 4 integration |
| **Value Object** | ReviewDecision DTO (immutable readonly properties) | Type safety, serializable |
| **Repository Pattern** | Abstract storage via repository interface | Persistence ignorance, testing |
| **Immutable Entity** | DuplicateTransaction from Story 1 | Thread-safe, functional approach |

### 2.3 Dependencies & Package Selection

**Required (already available):**
- `symfony/event-dispatcher` (v5.4+) - For in-memory event publishing
- `psr/log` (v1.1+) - Logging interface (already in project)
- `ramsey/uuid` (v4.0+) - For unique audit identifiers (if needed)

**Already in Project:**
- `phpunit/phpunit` (v10.x) - Testing framework
- `mockery/mockery` (v1.x) - Mocking library
- PDO via Story 1 infrastructure

**Well-Tested Rationale:**
- `symfony/event-dispatcher`: Industry standard, 50M+ downloads, excellent test support
- `psr/log`: PSR-3 standard, ensures framework compatibility
- All packages have 5+ years of stable releases, no reinvention

## 3. File Structure & Module Organization

```
src/Ksfraser/FaBankImport/
├── Import/
│   ├── Services/
│   │   ├── Review/
│   │   │   ├── DuplicateReviewService.php          [MAIN SERVICE]
│   │   │   ├── ReviewDecision.php                  [DTO OUTPUT]
│   │   │   └── Interfaces/
│   │   │       ├── IDuplicateReviewService.php     [OPTIONAL BUT RECOMMENDED]
│   │   │       └── IEventPublisher.php
│   │   └── ... (other services)
│   │
│   ├── Events/
│   │   ├── DuplicateDecisionMade.php               [DOMAIN EVENT]
│   │   └── DomainEvent.php                         [BASE CLASS]
│   │
│   ├── Exceptions/
│   │   ├── DuplicateReviewException.php            [BASE]
│   │   ├── InvalidWorkflowTransitionException.php  [SPECIFIC]
│   │   ├── InvalidReasonException.php              [SPECIFIC]
│   │   ├── EntityNotFoundException.php             [SPECIFIC]
│   │   └── RepositoryException.php                 [SPECIFIC]
│   │
│   └── Shared/
│       ├── Entities/
│       │   ├── DuplicateTransaction.php            [FROM STORY 1]
│       │   └── ... (other entities)
│       └── Repositories/
│           ├── DuplicateTransactionRepository.php  [FROM STORY 1]
│           └── ... (other repos)
│
tests/unit/Services/Review/
├── DuplicateReviewServiceTest.php                  [UNIT TESTS]
├── ReviewDecisionTest.php                          [DTO TESTS]
└── DuplicateDecisionMadeEventTest.php              [EVENT TESTS]

tests/integration/Services/Review/
├── DuplicateReviewServiceIntegrationTest.php       [DB TESTS]
└── Fixtures/
    └── ReviewTestData.php                          [TEST DATA]
```

## 4. Implementation Phases

### Phase 1: Foundation & Interfaces (2-3 hours)

#### Step 1.1: Create Exception Hierarchy
**File:** `src/Ksfraser/FaBankImport/Import/Exceptions/`

Create base exception class and 4 specific exceptions:
```php
// DuplicateReviewException (base)
class DuplicateReviewException extends RuntimeException {}

// InvalidWorkflowTransitionException
class InvalidWorkflowTransitionException extends DuplicateReviewException
{
    public static function attemptedInvalidTransition(
        string $currentStatus,
        string $attemptedDecision
    ): self
}

// InvalidReasonException
class InvalidReasonException extends DuplicateReviewException { ... }

// EntityNotFoundException  
class EntityNotFoundException extends DuplicateReviewException { ... }

// RepositoryException
class RepositoryException extends DuplicateReviewException { ... }
```

**TDD:** No tests required for exceptions (simple data classes)

---

#### Step 1.2: Create Data Transfer Objects (DTO)
**File:** `src/Ksfraser/FaBankImport/Import/Services/Review/ReviewDecision.php`

```php
class ReviewDecision
{
    public function __construct(
        public readonly int $transactionId,
        public readonly string $decisionStatus,
        public readonly string $decidedBy,
        public readonly DateTimeImmutable $decidedAt,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null,
    ) {}
    
    // Method: toArray() for HTTP response serialization
    // Method: fromArray() for deserialization (if needed)
}
```

**Unit Tests:** `ReviewDecisionTest.php`
- Test readonly properties after construction
- Test JSON serialization
- Test null handling for optional fields

---

#### Step 1.3: Create Domain Event
**File:** `src/Ksfraser/FaBankImport/Import/Events/DuplicateDecisionMade.php`

```php
class DuplicateDecisionMade extends DomainEvent
{
    public function __construct(
        public readonly int $transactionId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly string $decidedBy,
        public readonly DateTimeImmutable $decidedAt,
        public readonly ?string $reason = null,
    ) {
        parent::__construct();
    }
    
    public function toArray(): array { ... }
    public static function fromArray(array $data): self { ... }
}
```

Include base class `DomainEvent`:
```php
abstract class DomainEvent
{
    public readonly string $eventId;
    public readonly DateTimeImmutable $occurredAt;
    
    protected function __construct() {
        $this->eventId = (string) Uuid::uuid4();
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
```

**Unit Tests:** `DuplicateDecisionMadeEventTest.php`
- Test event construction
- Test immutability
- Test serialization/deserialization
- Test timestamp UTC handling

---

#### Step 1.4: Create Service Interface (Optional but Recommended)
**File:** `src/Ksfraser/FaBankImport/Import/Services/Review/Interfaces/IDuplicateReviewService.php`

Defines contract that service implements. Enables mocking in Story 3 controllers:

```php
interface IDuplicateReviewService
{
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision;
    
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision;
    
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision;
}
```

**No Tests:** Interface is contract only

---

### Phase 2: Service Implementation (3-4 hours)

#### Step 2.1: TDD Cycle - Write Unit Tests FIRST
**File:** `tests/unit/Services/Review/DuplicateReviewServiceTest.php`

Write all 12 unit tests with mocked dependencies (RED PHASE):

```php
class DuplicateReviewServiceTest extends TestCase
{
    private DuplicateReviewService $service;
    private MockObject $mockRepository;
    private MockObject $mockEventPublisher;
    private MockObject $mockLogger;
    
    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(IDuplicateTransactionRepository::class);
        $this->mockEventPublisher = $this->createMock(IEventPublisher::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        
        $this->service = new DuplicateReviewService(
            $this->mockRepository,
            $this->mockEventPublisher,
            $this->mockLogger
        );
    }
    
    // TC-UT-001: testApproveValidPending
    public function testApproveValidPending(): void
    {
        // Arrange: Mocked transaction in PENDING state
        $transaction = /* create mock with status PENDING */;
        
        // Mock repository to expect update call
        $this->mockRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(fn($tx) => $tx->status === 'APPROVED'));
        
        // Mock repository to expect audit call
        $this->mockRepository
            ->expects($this->once())
            ->method('auditDecision');
        
        // Mock event publisher to expect event
        $this->mockEventPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(DuplicateDecisionMade::class));
        
        // Act
        $decision = $this->service->approve($transaction, 'user_123', 'same amount, 1 day apart');
        
        // Assert
        $this->assertEquals('APPROVED', $decision->decisionStatus);
        $this->assertEquals('user_123', $decision->decidedBy);
        $this->assertNotNull($decision->decidedAt);
    }
    
    // TC-UT-002: testRejectValidPending
    public function testRejectValidPending(): void { ... }
    
    // TC-UT-003: testInvestigateValidPending
    public function testInvestigateValidPending(): void { ... }
    
    // TC-UT-004: testApproveAlreadyApproved
    public function testApproveAlreadyApproved(): void
    {
        $transaction = /* APPROVED status */;
        
        $this->expectException(InvalidWorkflowTransitionException::class);
        $this->service->approve($transaction, 'user_123', null);
    }
    
    // TC-UT-005: testRejectWithoutReason
    public function testRejectWithoutReason(): void
    {
        $transaction = /* PENDING */;
        
        $this->expectException(InvalidReasonException::class);
        $this->service->reject($transaction, 'user_123', ''); // empty reason
    }
    
    // ... 6 more tests (TC-UT-006 through TC-UT-012)
}
```

**Total Tests:** 12 unit tests
**Current Status:** RED (all fail, service doesn't exist yet)

---

#### Step 2.2: Implement Service (GREEN PHASE)
**File:** `src/Ksfraser/FaBankImport/Import/Services/Review/DuplicateReviewService.php`

Implement core service with minimal logic to make tests pass:

```php
class DuplicateReviewService implements IDuplicateReviewService
{
    public function __construct(
        private IDuplicateTransactionRepository $repository,
        private IEventPublisher $eventPublisher,
        private LoggerInterface $logger,
    ) {}
    
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision {
        // 1. Validate workflow transition
        $this->validateWorkflowTransition($transaction->status, 'APPROVED');
        
        // 2. Update transaction status
        $updatedTransaction = $transaction->withStatus('APPROVED');
        $this->repository->update($updatedTransaction);
        
        // 3. Create audit record
        $decidedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repository->auditDecision(
            $transaction->id,
            'APPROVED',
            $decidedBy,
            $decidedAt,
            $reason,
            null  // no notes on approve
        );
        
        // 4. Create decision DTO
        $decision = new ReviewDecision(
            transactionId: $transaction->id,
            decisionStatus: 'APPROVED',
            decidedBy: $decidedBy,
            decidedAt: $decidedAt,
            reason: $reason,
        );
        
        // 5. Publish event
        $event = new DuplicateDecisionMade(
            transactionId: $transaction->id,
            previousStatus: $transaction->status,
            newStatus: 'APPROVED',
            decidedBy: $decidedBy,
            decidedAt: $decidedAt,
            reason: $reason,
        );
        $this->eventPublisher->publish($event);
        
        // 6. Log success
        $this->logger->info("Duplicate approved by {decidedBy}", ['decidedBy' => $decidedBy]);
        
        return $decision;
    }
    
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision {
        // Validate reason not empty
        if (empty(trim($reason))) {
            throw new InvalidReasonException('Reject reason is required');
        }
        
        // Similar flow to approve...
    }
    
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision {
        // Similar flow...
    }
    
    private function validateWorkflowTransition(
        string $currentStatus,
        string $requestedDecision
    ): void {
        // State machine validation
        $validTransitions = [
            'PENDING' => ['APPROVED', 'REJECTED', 'INVESTIGATE'],
            'INVESTIGATE' => ['APPROVED', 'REJECTED'],
        ];
        
        if (!isset($validTransitions[$currentStatus])) {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $currentStatus,
                $requestedDecision
            );
        }
        
        if (!in_array($requestedDecision, $validTransitions[$currentStatus])) {
            throw InvalidWorkflowTransitionException::attemptedInvalidTransition(
                $currentStatus,
                $requestedDecision
            );
        }
    }
}
```

**Current Status:** GREEN (12 unit tests passing)

---

#### Step 2.3: Refactor for Quality (REFACTOR PHASE)
**Refactorings:**
- Extract complex methods
- Add docblock comments
- Sanitize input fields
- Improve error messages
- Add logging with context

```php
private function sanitizeReason(?string $reason): ?string
{
    if ($reason === null) {
        return null;
    }
    
    $trimmed = trim($reason);
    if (strlen($trimmed) > 500) {
        throw new InvalidReasonException('Reason exceeds 500 character limit');
    }
    
    // Prevent SQL injection (additional safety layer before DB)
    return htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
}

private function createAuditRecord(...): void
{
    // Extracted method for clarity
}

private function publishEvent(?DuplicateDecisionMade $event): void
{
    try {
        if ($event) {
            $this->eventPublisher->publish($event);
        }
    } catch (Exception $e) {
        // Log but don't fail service if event publishing fails
        $this->logger->error("Failed to publish event: {error}", ['error' => $e->getMessage()]);
    }
}
```

**Current Status:** GREEN+REFACTOR (12 unit tests passing, improved code)

---

### Phase 3: Integration Testing (2-3 hours)

#### Step 3.1: Write Integration Tests
**File:** `tests/integration/Services/Review/DuplicateReviewServiceIntegrationTest.php`

Integration tests use REAL database and repository:

```php
class DuplicateReviewServiceIntegrationTest extends TestCase
{
    private DuplicateReviewService $service;
    private DuplicateTransactionRepository $repository;
    private PDO $pdo;
    
    protected function setUp(): void
    {
        // TestDatabaseMigrator runs migrations (from Story 1 bootstrap)
        
        // Real repository with real database
        $this->repository = new DuplicateTransactionRepository($this->pdo);
        
        // Mock event publisher (we don't want side effects)
        $eventPublisher = $this->createMock(IEventPublisher::class);
        
        $this->service = new DuplicateReviewService(
            $this->repository,
            $eventPublisher,
            $this->getLogger(),
        );
        
        // Seed test data
        $this->seedTestTransactions();
    }
    
    // TC-IT-001: testDecisionPersistedToDatabase
    public function testDecisionPersistedToDatabase(): void
    {
        // Arrange: PENDING transaction from seeded data
        $tx = $this->repository->findById(1);
        
        // Act: Approve it
        $this->service->approve($tx, 'user_123', 'approved in test');
        
        // Assert: Check database directly
        $stmt = $this->pdo->prepare(
            'SELECT decided_by, reason FROM 0_bi_transactions_dupe_audit WHERE transaction_id = ?'
        );
        $stmt->execute([1]);
        $audit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('user_123', $audit['decided_by']);
        $this->assertEquals('approved in test', $audit['reason']);
    }
    
    // TC-IT-002: testAuditForeignKeyValid
    public function testAuditForeignKeyValid(): void
    {
        $tx = $this->repository->findById(1);
        $this->service->reject($tx, 'user_123', 'false positive');
        
        // Verify FK works and audit references correct transaction
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as cnt FROM 0_bi_transactions_dupe_audit 
             WHERE transaction_id = ? AND 0_bi_transactions_dupe.id = ?'
        );
        // JOIN verification...
    }
    
    // TC-IT-003: testConcurrent10Requests
    public function testConcurrent10Requests(): void
    {
        // Simulate 10 concurrent approval requests
        $decisions = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $tx = $this->repository->findById($i);
            $decisions[] = $this->service->approve($tx, "user_$i", null);
        }
        
        // All should succeed
        $this->assertCount(10, $decisions);
        
        // Verify all persisted
        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM 0_bi_transactions_dupe_audit');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(10, $row['cnt']);
    }
    
    // ... 4 more integration tests
}
```

**Total Tests:** 7 integration tests
**Current Status:** GREEN (all integration tests passing)

---

#### Step 3.2: Write Error Handling Tests
**File:** `tests/unit/Services/Review/DuplicateReviewServiceExceptionTest.php` (or within main test class)

```php
// TC-EH-001: testDatabaseConnectionFailure
public function testDatabaseConnectionFailure(): void
{
    // Mock repository to throw exception
    $this->mockRepository
        ->method('update')
        ->willThrowException(new Exception('DB connection failed'));
    
    $tx = /* PENDING transaction */;
    
    $this->expectException(RepositoryException::class);
    $this->service->approve($tx, 'user_123', null);
}

// ... 4 more error handling tests
```

**Total Tests:** 5 error handling tests

---

### Phase 4: Performance & Edge Case Testing (1-2 hours)

#### Step 4.1: Performance Tests

```php
// TC-PERF-001: testSingleDecisionPerformance
public function testSingleDecisionPerformance(): void
{
    $start = microtime(true);
    
    $tx = $this->repository->findById(1);
    $this->service->approve($tx, 'user_123', null);
    
    $elapsed = (microtime(true) - $start) * 1000; // milliseconds
    
    $this->assertLessThan(100, $elapsed, 'Single decision should complete < 100ms');
}

// TC-PERF-002: testBatch100Decisions
public function testBatch100Decisions(): void
{
    $start = microtime(true);
    
    for ($i = 1; $i <= 100; $i++) {
        $tx = $this->repository->findById($i % 50); // wrap around
        $this->service->approve($tx, "user_$i", null);
    }
    
    $elapsed = (microtime(true) - $start) * 1000;
    
    // Batch average should be < 5ms per decision
    $this->assertLessThan(500, $elapsed, 'Batch 100 decisions should complete < 500ms');
}
```

**Total Tests:** 4 performance tests

---

### Phase 5: Code Quality & Final Checks (1 hour)

#### Step 5.1: Coverage Check
```bash
php vendor/bin/phpunit --coverage-html coverage tests/
# Target: ≥ 85% coverage
```

#### Step 5.2: SonarQube Analysis
```bash
# In project: Run SonarQube scanner
sonar-scanner -Dsonar.projectKey=ksf-bank-import ...
# Target: Grade A, no critical issues
```

#### Step 5.3: Code Review Checklist

- [ ] All 28 tests passing (12 unit + 7 integration + 5 error + 4 performance)
- [ ] Code coverage ≥ 85%
- [ ] SOLID principles followed:
  - [ ] Single Responsibility - service focuses on one thing
  - [ ] Open/Closed - extensible via interfaces
  - [ ] Liskov Substitution - interface contracts honored
  - [ ] Interface Segregation - lean interfaces, not fat
  - [ ] Dependency Inversion - depends on abstractions (interfaces)
- [ ] Security:
  - [ ] Input validation on reason/notes
  - [ ] No SQL injection vulnerabilities
  - [ ] Audit logging complete
- [ ] Documentation:
  - [ ] All public methods have docblocks
  - [ ] @param and @return types documented
  - [ ] @throws documented for exceptions
- [ ] Performance:
  - [ ] Single decision < 100ms
  - [ ] Batch 100 < 500ms
  - [ ] Concurrent 50 < 1000ms
- [ ] Maintainability:
  - [ ] No hardcoded strings (except constants)
  - [ ] Readable variable names
  - [ ] Methods ≤ 20 lines (or extracted)
  - [ ] Cyclomatic complexity ≤ 10

---

## 5. Git Workflow

### Commit Strategy: Atomic Commits with Story Tracking

```bash
# 1. Create feature branch (from feat-dupe-check)
git checkout -b feat-story-2-review-service

# 2. Commit foundational files
git add src/Ksfraser/FaBankImport/Import/Exceptions/
git add src/Ksfraser/FaBankImport/Import/Events/
git add src/Ksfraser/FaBankImport/Import/Services/Review/Interfaces/
git commit -m "chore(story-2): add exception hierarchy, domain events, and service interfaces"

# 3. Commit TDD tests (RED phase)
git add tests/unit/Services/Review/DuplicateReviewServiceTest.php
git add tests/unit/Services/Review/DuplicateDecisionMadeEventTest.php
git add tests/unit/Services/Review/ReviewDecisionTest.php
git commit -m "test(story-2): write unit tests for review service (RED phase)"

# 4. Commit service implementation (GREEN phase)
git add src/Ksfraser/FaBankImport/Import/Services/Review/DuplicateReviewService.php
git add src/Ksfraser/FaBankImport/Import/Services/Review/ReviewDecision.php
git commit -m "feat(story-2): implement duplicate review service (GREEN phase)"

# 5. Commit integration tests
git add tests/integration/Services/Review/
git commit -m "test(story-2): add integration tests with database (7 tests)"

# 6. Commit error handling tests
git add tests/unit/Services/Review/DuplicateReviewServiceExceptionTest.php
git commit -m "test(story-2): add error handling and exception tests (5 tests)"

# 7. Commit performance tests
git add tests/unit/Services/Review/DuplicateReviewServicePerformanceTest.php
git commit -m "test(story-2): add performance and load tests (4 tests)"

# 8. Final commit with documentation
git add docs/
git commit -m "docs(story-2): add PRD, test strategy, architecture, and implementation plan"

# 9. Merge to main branch
git checkout feat-dupe-check
git merge feat-story-2-review-service

# OR: Create PR for review (recommended)
git push origin feat-story-2-review-service
# (Create PR in GitHub, request review, merge after approval)
```

**Conventional Commit Format:**
```
<type>(story-2): <subject>

<body>

<footer>

Examples:
- test(story-2): write unit tests for review service (RED phase)
- feat(story-2): implement duplicate review service (GREEN phase)
- docs(story-2): add PRD and architecture specification
```

---

## 6. Deliverables

### Code Artifacts
1. **DuplicateReviewService.php** (400+ lines) - Main service implementation
2. **ReviewDecision.php** (50 lines) - DTO for decision responses
3. **DuplicateDecisionMade.php** (100 lines) - Domain event
4. **DomainEvent.php** (40 lines) - Base event class
5. **Exception classes** (200 lines total) - Custom exceptions
6. **Service interface** (50 lines) - Contract definition

### Test Artifacts
7. **DuplicateReviewServiceTest.php** (400+ lines) - 12 unit tests
8. **DuplicateReviewServiceIntegrationTest.php** (300+ lines) - 7 integration tests
9. **Exception/Error tests** (150 lines) - 5 error handling tests
10. **Performance tests** (100 lines) - 4 performance tests

### Documentation Artifacts
11. **prd.md** - Product Requirements Document (this provides it)
12. **test-strategy.md** - Comprehensive test plan
13. **architecture.md** - System design and contracts
14. **implementation-plan.md** (this document) - Step-by-step implementation guide

### Quality Metrics
- 28/28 tests passing (100%)
- Code coverage: ≥ 85%
- SonarQube Grade: A
- Performance: < 500ms p95
- Security: 0 vulnerabilities

---

## 7. Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-----------|--------|-----------|
| **Unit tests too tightly coupled to implementation** | MEDIUM | MEDIUM | Write tests for behavior, not implementation details; use mocking effectively |
| **Integration test database state issues** | MEDIUM | HIGH | Use TestDatabaseMigrator for clean setup; seed same data each run; teardown after tests |
| **Concurrent access race condition** | LOW | HIGH | Database transactions ensure isolation; test with 50+ concurrent requests |
| **Event publisher throws exception** | LOW | MEDIUM | Wrap event publishing in try-catch; log failure; don't fail service |
| **Performance regression in later stories** | MEDIUM | MEDIUM | Establish baseline metrics now; rerun performance tests in each story |

---

## 8. Timeline & Effort Estimate

| Phase | Duration | Activities | Status |
|-------|----------|-----------|--------|
| **Phase 1: Foundation** | 2-3 hrs | Exceptions, DTOs, events, interfaces | Not Started |
| **Phase 2: Service Impl** | 3-4 hrs | Unit tests (TDD), service code, refactoring | Not Started |
| **Phase 3: Integration** | 2-3 hrs | Integration tests, database interactions | Not Started |
| **Phase 4: Quality Test** | 1-2 hrs | Error handling, performance, edge cases | Not Started |
| **Phase 5: Final QA** | 1 hr | Coverage check, SonarQube, code review | Not Started |
| **Phase 6: Git & Docs** | 30 mins | Atomic commits, conventional messages | Not Started |

**Total Story 2 Duration: 9-13 hours (1-2 business days)**

---

## 9. Definition of Done (DoD)

✅ **Code Excellence**
- [ ] All source code follows SOLID principles
- [ ] All methods have type hints and docblocks
- [ ] No hardcoded values (use constants)
- [ ] No commented-out code
- [ ] DRY principle applied (no duplication)

✅ **Testing**
- [ ] 28 tests written and passing (12 unit + 7 integration + 5 error + 4 performance)
- [ ] Code coverage ≥ 85%
- [ ] All edge cases tested
- [ ] Concurrent request scenarios tested
- [ ] Exception paths tested

✅ **Quality Assurance**
- [ ] SonarQube Grade A
- [ ] Zero critical security issues
- [ ] Performance benchmarks met
- [ ] No linting errors
- [ ] Code review approved

✅ **Documentation**
- [ ] PRD updated with acceptance criteria
- [ ] Test strategy documented
- [ ] Architecture specification complete
- [ ] Implementation plan (this document) updated
- [ ] Code comments for complex logic

✅ **Git Practices**
- [ ] All work committed with atomic commits
- [ ] Conventional commit messages used
- [ ] No merge conflicts
- [ ] Clean git history
- [ ] Merged to feat-dupe-check branch

✅ **Next Story Readiness**
- [ ] DuplicateReviewService ready for Story 3 integration
- [ ] Event publishing ready for Story 4 integration
- [ ] No outstanding technical debt
- [ ] No known bugs or regressions

---

**Next Steps:** Begin Phase 1 with exception hierarchy and DTO implementation.
