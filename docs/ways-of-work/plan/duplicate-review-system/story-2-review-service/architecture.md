---
title: "Story 2: Duplicate Review Service - Architecture Specification"
version: "1.0"
date_created: "2026-04-09"
owner: "Development Team"
tags: ["architecture", "domain-driven-design", "event-driven"]
---

# Architecture Specification - DuplicateReviewService

## 1. Purpose & Scope

### Purpose
This specification defines the architectural design for the **DuplicateReviewService**, a domain service responsible for recording reviewer decisions on detected duplicate transactions. It establishes clear interfaces, data contracts, and interaction patterns for the service layer that bridges Stories 1 (database) and 3 (admin UI).

### Scope
- Service component architecture and responsibilities
- Data flow and state transitions
- Interface contracts with Story 1 (repository/entity)
- Event publishing mechanism
- Error handling and resilience patterns
- Dependency injection and decoupling strategies

### Audience
- Backend engineering team (implementation)
- QA team (testing strategy)
- Technical architects (design review)
- Future maintainers (long-term understanding)

### Assumptions
- Story 1 infrastructure (database, migrations, repository) is complete and working
- Event publishing uses in-memory event dispatcher (not external MQ)
- Service is framework-agnostic PHP (no Laravel/Symfony specifics)
- PHP 8.0+ features available (typed properties, match expressions, etc.)

## 2. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        BOUNDARY LAYER                           │
│  (Story 3: Admin UI, REST Controllers - TBD)                   │
└────────────────┬────────────────────────────────────────────────┘
                 │ HTTP Request
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ DuplicateReviewController (Story 3)                     │   │
│  │ - HTTP input validation                                 │   │
│  │ - Calls DuplicateReviewService                          │   │
│  │ - Returns HTTP response                                 │   │
│  └──────────────┬───────────────────────────────────────────┘   │
│                 │
│  ┌──────────────▼───────────────────────────────────────────┐   │
│  │ DuplicateReviewService (STORY 2)                        │   │
│  │ ┌────────────────────────────────────────────────────┐  │   │
│  │ │ Public Methods:                                    │  │   │
│  │ │ • approve(tx, decidedBy, reason): ReviewDecision  │  │   │
│  │ │ • reject(tx, decidedBy, reason): ReviewDecision   │  │   │
│  │ │ • investigate(tx, decidedBy): ReviewDecision      │  │   │
│  │ ├────────────────────────────────────────────────────┤  │   │
│  │ │ Private Methods:                                   │  │   │
│  │ │ • validateWorkflowTransition(status, decision)     │  │   │
│  │ │ • createAuditRecord(...)                           │  │   │
│  │ │ • publishDecisionEvent(...)                        │  │   │
│  │ └────────────────────────────────────────────────────┘  │   │
│  └──────────────┬──────────┬────────────────┬──────────────┘   │
│                 │          │                │                   │
└─────────────────┼──────────┼────────────────┼───────────────────┘
                  │          │                │
         ┌────────▼────┐  ┌──▼────────────┐  │
         │ DOMAIN      │  │ EVENT         │  │
         │ LAYER       │  │ PUBLISHING    │  │
         └────────┬────┘  └───┬──────────┬┘  │
                  │           │         │    │
         ┌────────▼─────────────────────▼┐   │
         │ DuplicateTransaction Entity   │   │
         │ (Immutable Value Object)      │   │
         │ - transaction_code            │   │
         │ - matched_to_code             │   │
         │ - decision_status: Enum       │   │
         │ - confidence_score            │   │
         │ - created_by/at               │   │
         └────────┬─────────────────────┬┘   │
                  │                     │    │
         ┌────────▼────┐  ┌─────────────▼┐  │
         │ ReviewDecision
         │ DTO (Output) │  │ Domain Events│   │
         │ - tx_id      │  │            │  │
         │ - status     │  │ DuplicateDe│  │
         │ - decidedBy  │  │ cisionMade │  │
         │ - decidedAt  │  └────┬───────┘  │
         │ - reason     │        │          │
         └──────────────┘        │          │
                         ┌───────▼──┐      │
         ┌───────────────►│ Event    │      │
         │               │ Listeners│      │
         │               │ (Repos,  │      │
         │               │  Logging)│      │
         │               └──────────┘      │
         │                                 │
└─────────┼───────────────────────────────┤┘
          │                               │
     ┌────▼────────────────────────────────▼─┐
     │  PERSISTENCE LAYER                     │
     │  ┌─────────────────────────────────┐  │
     │  │ DuplicateTransactionRepository   │  │
     │  │ (Story 1)                        │  │
     │  │ • findPendingDuplicate()         │  │
     │  │ • update(tx)                     │  │
     │  │ • auditDecision(tx, audit)       │  │
     │  └────────────┬─────────────────────┘  │
     │              │                          │
     │  ┌───────────▼─────────────────────┐   │
     │  │ Database (Story 1 Schema)        │   │
     │  │ • 0_bi_transactions_dupe         │   │
     │  │ • 0_bi_transactions_dupe_audit   │   │
     │  └──────────────────────────────────┘   │
     └─────────────────────────────────────────┘
```

## 3. Definitions

### Key Terms

| Term | Definition |
|------|-----------|
| **DuplicateTransaction** | Entity (from Story 1) representing a transaction flagged as potentially duplicated |
| **Decision Status** | Enum: `PENDING`, `APPROVED`, `REJECTED`, `INVESTIGATE` |
| **Workflow Transition** | State change: `PENDING` → one of {`APPROVED`, `REJECTED`, `INVESTIGATE`} OR `INVESTIGATE` → {`APPROVED`, `REJECTED`} |
| **Audit Trail** | Complete history of all decisions with reviewer, timestamp, reason/notes |
| **ReviewDecision** | DTO returned by service containing decision outcome and details |
| **DuplicateDecisionMade** | Domain event published when decision is recorded |
| **Event Listener** | Callback/subscriber that reacts to domain events (logging, further processing) |
| **Repository Pattern** | Data access abstraction layer (Story 1) that isolates persistence from business logic |
| **Immutable Object** | Object whose state cannot be changed after construction (entity design) |
| **DTO (Data Transfer Object)** | Simple object for transferring decision data between layers |

### Acronyms

| Acronym | Full Form |
|---------|-----------|
| **SOLID** | Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion |
| **SRP** | Single Responsibility Principle |
| **DI** | Dependency Injection |
| **DTO** | Data Transfer Object |
| **ACID** | Atomicity, Consistency, Isolation, Durability |
| **UTC** | Coordinated Universal Time |
| **ORM** | Object-Relational Mapping |

## 4. Requirements, Constraints & Guidelines

### Functional Requirements

- **REQ-001**: Service accepts `DuplicateTransaction` entity and decision type
- **REQ-002**: Service validates workflow transition before recording decision
- **REQ-003**: Service atomically updates database (transaction status + audit record)
- **REQ-004**: Service publishes domain event after successful decision
- **REQ-005**: Service returns `ReviewDecision` DTO with confirmation details
- **REQ-006**: Service enforces required fields (reviewer ID, decision type)
- **REQ-007**: Service sanitizes text inputs (reason, notes) to prevent injection

### Architectural Constraints

- **ARC-001**: Service is framework-agnostic (no framework-specific code)
- **ARC-002**: Service uses constructor dependency injection (no service locator)
- **ARC-003**: Service is stateless (all state passed via parameters, no instance variables)
- **ARC-004**: Repository interface must be mockable for unit tests
- **ARC-005**: Event publishing must be pluggable (interface-based, not directly dependent on event class)

### Design Guidelines

- **GUD-001**: Follow Single Responsibility Principle (service focuses only on decision recording)
- **GUD-002**: All public methods must have return types and parameter type hints
- **GUD-003**: Exceptions should be specific (custom exception classes, not generic `Exception`)
- **GUD-004**: Database transactions ensure atomicity (use repository's transaction support)
- **GUD-005**: Error messages must be clear and actionable (include context)
- **GUD-006**: Timestamps must always use UTC timezone (never local time)

### Code Quality Guidelines

- **COD-001**: Minimum 85% unit test coverage
- **COD-002**: Maximum cyclomatic complexity per method: 10
- **COD-003**: All classes must have docblocks with `@param`, `@return`, `@throws`
- **COD-004**: Variables named descriptively (no 1-letter vars except loop counters)
- **COD-005**: Methods should do one thing (extract complex logic to helpers)

## 5. Component Architecture

### 5.1 Core Service Class: `DuplicateReviewService`

```php
namespace Ksfraser\FaBankImport\Import\Services\Review;

class DuplicateReviewService
{
    private DuplicateTransactionRepository $repository;
    private EventPublisher $eventPublisher;
    private Logger $logger;
    
    // Constructor injection with explicit types
    public function __construct(
        DuplicateTransactionRepository $repository,
        EventPublisher $eventPublisher,
        Logger $logger
    )
    
    /**
     * Record an approval decision for a duplicate transaction
     *
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy (User ID or system identifier)
     * @param string $reason (Optional decision justification)
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     * @throws EntityNotFoundException
     */
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision
    
    /**
     * Record a rejection decision for a duplicate transaction
     *
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy
     * @param string $reason (Required - must explain why rejected)
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     */
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision
    
    /**
     * Mark a duplicate for further investigation
     *
     * @param DuplicateTransaction $transaction
     * @param string $decidedBy
     * @param ?string $notes
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     */
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision
    
    // Private helper methods
    private function validateWorkflowTransition(
        string $currentStatus,
        string $requestedDecision
    ): bool
    
    private function createAuditRecord(
        DuplicateTransaction $transaction,
        string $newStatus,
        string $decidedBy,
        string $decidedAt,
        ?string $reason,
        ?string $notes
    ): void
    
    private function publishDecisionEvent(
        ReviewDecision $decision
    ): void
}
```

**Responsibilities:**
- Accept decision requests
- Validate workflow transitions
- Delegate persistence to repository
- Publish domain events
- Handle errors gracefully

**Single Responsibility:** Coordinates decision recording workflow

### 5.2 Data Transfer Object: `ReviewDecision`

```php
namespace Ksfraser\FaBankImport\Import\Services\Review;

class ReviewDecision
{
    public readonly int $transactionId;
    public readonly string $decisionStatus;
    public readonly string $decidedBy;
    public readonly DateTimeImmutable $decidedAt;
    public readonly ?string $reason;
    public readonly ?string $notes;
    
    public function __construct(
        int $transactionId,
        string $decisionStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        ?string $reason = null,
        ?string $notes = null
    )
}
```

**Purpose:** Immutable DTO for returning decision confirmation to caller

**Advantages:**
- Readonly properties (PHP 8.1+) prevent accidental modification
- Self-documenting: all decision details in one object
- Serializable for HTTP responses
- Type-safe (no arrays, type hints on fields)

### 5.3 Domain Event: `DuplicateDecisionMade`

```php
namespace Ksfraser\FaBankImport\Import\Events;

class DuplicateDecisionMade
{
    public readonly int $transactionId;
    public readonly string $previousStatus;
    public readonly string $newStatus;
    public readonly string $decidedBy;
    public readonly DateTimeImmutable $decidedAt;
    public readonly ?string $reason;
    
    // Methods:
    // public function toArray(): array  (for serialization)
    // public static function fromArray(array $data): self (for deserialization)
}
```

**Purpose:** Signal that a decision was successfully recorded (for event listeners)

**Use Cases:**
- Story 4 posting integration listens for APPROVED events
- Logging system records all events
- Async job queues for batch processing

### 5.4 Exception Hierarchy

```php
namespace Ksfraser\FaBankImport\Import\Exceptions;

// Base exception
class DuplicateReviewException extends RuntimeException {}

// Specific exceptions
class InvalidWorkflowTransitionException extends DuplicateReviewException
{
    // Current status was not allowed to transition to requested decision
}

class InvalidReasonException extends DuplicateReviewException
{
    // Reason validation failed (too short, too long, invalid chars)
}

class EntityNotFoundException extends DuplicateReviewException
{
    // Transaction not found in database
}

class RepositoryException extends DuplicateReviewException
{
    // Database operation failed
}
```

## 6. Interfaces & Data Contracts

### 6.1 Service Interface

```php
interface IDuplicateReviewService
{
    /**
     * Approve a duplicate detection
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     * @throws EntityNotFoundException
     */
    public function approve(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $reason = null
    ): ReviewDecision;
    
    /**
     * Reject a duplicate detection as false positive
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     */
    public function reject(
        DuplicateTransaction $transaction,
        string $decidedBy,
        string $reason
    ): ReviewDecision;
    
    /**
     * Mark duplicate for investigation
     * @return ReviewDecision
     * @throws InvalidWorkflowTransitionException
     */
    public function investigate(
        DuplicateTransaction $transaction,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision;
}
```

### 6.2 Repository Dependency Contract (Story 1)

The service depends on `DuplicateTransactionRepository` with these methods:

```php
interface IDuplicateTransactionRepository
{
    /**
     * Fetch transaction by ID
     * @return DuplicateTransaction
     * @throws EntityNotFoundException
     */
    public function findById(int $id): DuplicateTransaction;
    
    /**
     * Update transaction status
     */
    public function update(DuplicateTransaction $transaction): void;
    
    /**
     * Create audit record for decision
     */
    public function auditDecision(
        int $transactionId,
        string $newStatus,
        string $decidedBy,
        DateTimeImmutable $decidedAt,
        ?string $reason,
        ?string $notes
    ): void;
}
```

### 6.3 Event Publisher Contract

```php
interface IEventPublisher
{
    /**
     * Publish a domain event to all registered listeners
     */
    public function publish(DomainEvent $event): void;
}
```

## 7. Data Flow Diagrams

### 7.1 Approve Decision Flow

```
┌─────────────────┐
│ Story 3: UI     │
│ approve() call  │
└────────┬────────┘
         │ {tx, decidedBy, reason}
         ▼
┌─────────────────────────────────────────────┐
│ DuplicateReviewService::approve()           │
├─────────────────────────────────────────────┤
│ 1. Validate transition (PENDING → APPROVED) │
│ 2. Update tx.status = "APPROVED"            │
│ 3. Save to database via repository          │
│ 4. Create audit record                      │
│ 5. Publish DuplicateDecisionMade event      │
│ 6. Return ReviewDecision DTO                │
└────┬──────────┬──────────────┬──────────────┘
     │          │              │
     ▼          ▼              ▼
  ┌─────────────────────────┐  ┌──────────────┐
  │ Database Updates:       │  │ Event Posted │
  │ • UPDATE 0_bi_trans...  │  │ • Listeners  │
  │ • INSERT audit row      │  │   notified   │
  └─────────────────────────┘  └──────────────┘
     │
     ├──► Story 4: Posting service listens
     │    and proceeds with posting
     │
     └──► Logging: All decisions logged
```

### 7.2 Reject Decision Flow

```
Reject has stricter validation:
- Requires reason (not optional)
- Reason must be non-empty and <= 500 chars
- Performed same database updates as approve
```

### 7.3 Concurrent Request Handling

```
User A: approve(tx_id=100, ...)
User B: reject(tx_id=100, ...)

Timeline:
t0:  Both read tx status: PENDING ✓
t1:  User A starts database update (BEGIN TRANSACTION)
t2:  User B starts database update (waits on lock)
t3:  User A commits (status → APPROVED)
     Event published

t4:  User B acquires lock
t5:  User B reads status: APPROVED (now locked)
t6:  User B rejects due to invalid transition
     InvalidWorkflowTransitionException thrown
     (No changes made)

Design: Database PESSIMISTIC locking via transaction ensures consistency
```

## 8. Acceptance Criteria

### AC-001: Approve Decision Acceptance
- **Given:** Transaction exists with PENDING status
- **When:** Service calls `approve(tx, "user_123", "reason")`
- **Then:** 
  - Transaction status updated to APPROVED
  - Audit record created with all 4 columns filled
  - DuplicateDecisionMade event published
  - ReviewDecision DTO returned with confirmation
  - No exceptions thrown

### AC-002: Workflow Validation
- **Given:** Transaction with APPROVED status
- **When:** Service calls `approve(tx, "user_123", null)`
- **Then:** InvalidWorkflowTransitionException thrown, no database changes

### AC-003: Required Reason Enforcement
- **Given:** Request to reject  
- **When:** Service calls `reject(tx, "user_123", "")` (empty reason)
- **Then:** InvalidReasonException thrown or empty reject prevented

### AC-004: Event Publishing
- **Given:** Successful decision
- **When:** Service completes decision recording
- **Then:** All registered event listeners receive DuplicateDecisionMade event

### AC-005: Repository Abstraction
- **Given:** Mocked repository for testing
- **When:** Unit test provides mock repository via DI
- **Then:** Service calls repository methods, assertions verify calls

## 9. Technology Stack & Rationale

| Component | Choice | Rationale |
|-----------|--------|-----------|
| **Language** | PHP 8.0+ | Matches project; typed features | match expressions |
| **Framework** | None (abstract) | Decoupling from framework specifics; Story 3 wraps in controller |
| **Testing** | PHPUnit 10.x | Industry standard for PHP; well-tested |
| **Mocking** | Mockery 1.5+ | Excellent for PHP; supports all patterns needed |
| **Database** | MySQL/MariaDB | From Story 1; no change |
| **Event System** | Custom in-memory | External MQ complexity deferred to Story 4 |
| **Dependency Injection** | Constructor Injection | Simple, explicit, testable; no framework needed |
| **Validation** | Custom + PHP filters | Lightweight; Story 3 provides input validation |

## 10. Deployment & Scalability

### Stateless Design
- Service holds no instance state
- Each instance handles requests independently
- Supports horizontal scaling (multiple servers)
- No shared cache concerns

### Database Scalability
- Indexes on `decision_status`, `decided_at`, `decided_by` support concurrent queries
- Audit table is append-only (immutable after creation)
- Foreign key ensures referential integrity
- Transaction isolation level: REPEATABLE READ (default), prevents dirty reads

### Performance Characteristics
- Single decision: < 100ms (1 DB query, 1 audit insert, 1 event dispatch)
- Batch 100 decisions: < 500ms p95 (linear scaling expected)
- Concurrent 50 requests: Database lock contention minimal (different tx IDs)

## 11. Testing Architecture

### Unit Test Strategy
- Mock repository: Service doesn't access real database
- Mock event publisher: Verify events without side effects
- Test each public method independently
- Test exception paths
- 85% code coverage minimum

### Integration Test Strategy
- Use TestDatabaseMigrator to set up real database
- Real repository instance
- Real event system (or spy on it)
- Test end-to-end workflows
- Verify database state changes

## 12. Security Considerations

### Input Validation
- `decidedBy`: Validate non-empty, max 255 chars (matches DB constraint)
- `reason`/`notes`: Sanitize to prevent SQL injection; max 500 chars
- Transaction ID: Must be valid integer

### Audit & Logging
- All decisions logged (success and failure)
- Failed decisions don't modify database
- Timestamps immutable after creation

### Data Integrity
- Foreign keys enforce referential integrity
- Database constraints prevent invalid states
- Transaction ensures atomicity

---

**Appendix:** Referenced artifacts
- Story 1: Database schema (`0_bi_transactions_dupe`, `0_bi_transactions_dupe_audit`)
- Story 1: `DuplicateTransaction` entity
- Story 1: `DuplicateTransactionRepository`
- Test Plan: [test-strategy.md](./test-strategy.md)
- PRD: [prd.md](./prd.md)
