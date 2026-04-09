---
title: "Story 2: Duplicate Review Service"
epic: "Phase 1: Duplicate Review System"
version: "1.0"
date_created: "2026-04-09"
owner: "Development Team"
tags: ["duplicate-detection", "review-workflow", "domain-events"]
---

# Duplicate Review Service - Product Requirements Document

## Feature Name

Duplicate Review Service (Story 2 of Phase 1 Duplicate Review System)

## Epic

**Parent Epic:** [Phase 1: Duplicate Review System](../epic-duplicate-review-system.md)

**Previous Story:** [Story 1: Duplicate Staging Database](./story-1-database-schema)
- Provides foundation: `DuplicateTransaction` entity, `DuplicateTransactionRepository`, migration system

## Goal

### Problem
After bank transactions are analyzed for duplicates by Story 1's staging database, a human reviewer must approve or reject each detected duplicate. Currently, there is no service layer to capture, persist, and audit these review decisions. Without this, there's no workflow to convert duplicate analyses into actionable business decisions.

### Solution
Implement a `DuplicateReviewService` that:
- Records reviewer decisions (approve, reject, investigate) with full audit trails
- Enforces valid workflow transitions (PENDING → approved/rejected/investigated states)
- Publishes domain events for downstream integration (Story 4)
- Handles concurrent review requests safely
- Prevents race conditions during decision recording

### Impact
- **Primary**: Enable Story 3 UI to safely record and persist user decisions
- **Quality**: Audit trail for compliance and debugging duplicate detection accuracy
- **Reliability**: Event-driven integration ready for posting workflows
- **Scalability**: Foundation for async processing and batch review operations

## User Personas

| Persona | Role | Involvement |
|---------|------|------------|
| **Bank Reconciliation Specialist** | Reviews and decides on detected duplicates | Primary user of decisions recorded by this service |
| **Finance Manager** | Monitors duplicate review metrics and audit trails | Consumes published events and audit data |
| **System Administrator** | Debugs failed reviews and data inconsistencies | Uses audit columns and event history |
| **Integration System** | Downstream posting service (Story 4) | Consumes DuplicateDecisionMade events |

## User Stories

### User Story 1: Approve Duplicate Detection
**As a** Bank Reconciliation Specialist  
**I want to** approve a detected duplicate transaction pair  
**So that** the system knows these are confirmed duplicates and can proceed with posting integration

**Acceptance Criteria:**
- Service accepts a `DuplicateTransaction` with `PENDING` status
- Saves decision as `APPROVED` with decision timestamp
- Records reviewer name (`decided_by`) and optional reason
- Publishes `DuplicateDecisionMade` event with decision details
- Updates database transaction status atomically
- Returns decision DTO with confirmation details

### User Story 2: Reject Duplicate Detection
**As a** Bank Reconciliation Specialist  
**I want to** reject a detected duplicate (mark as false positive)  
**So that** the system doesn't waste time on incorrect matches and maintains data quality

**Acceptance Criteria:**
- Service accepts a `DuplicateTransaction` with `PENDING` status
- Saves decision as `REJECTED` with decision timestamp
- Records reviewer name and required reason for rejection
- Publishes `DuplicateDecisionMade` event
- Updates database transaction status atomically
- Returns decision DTO confirming rejection

### User Story 3: Mark for Investigation
**As a** Bank Reconciliation Specialist  
**I want to** mark a duplicate pair for deeper investigation  
**So that** we can set aside uncertain cases for expert review later

**Acceptance Criteria:**
- Service accepts a `DuplicateTransaction` with `PENDING` status
- Saves decision as `INVESTIGATE` with decision timestamp
- Records reviewer name and investigation reason/notes
- Publishes `DuplicateDecisionMade` event
- Updates database transaction status atomically
- Returns decision DTO with investigation flag

### User Story 4: Prevent Workflow Violations
**As a** System Administrator  
**I want to** be protected from invalid workflow transitions  
**So that** audit trails remain consistent and we can trust the status history

**Acceptance Criteria:**
- Service rejects reviewing already-approved/rejected/investigated transactions
- Throws `InvalidWorkflowTransitionException` with clear error message
- Does NOT update database or publish events on violation
- Logs violation attempt for audit
- Returns appropriate HTTP error in API layer

### User Story 5: Handle Concurrent Reviews Safely
**As a** System Administrator  
**I want to** ensure concurrent review requests don't corrupt data  
**So that** multiple reviewers can safely work simultaneously without race conditions

**Acceptance Criteria:**
- Service handles simultaneous decision requests on same transaction
- Uses database transaction to ensure atomicity
- Last reviewer decision wins (or implements configured concurrency strategy)
- Both decisions are logged in audit table
- Publishes both events in order
- No data corruption or duplicate records

### User Story 6: Audit All Decision Data
**As a** Finance Manager  
**I want to** see complete audit trails for all decisions  
**So that** I can trace who decided what and when for compliance

**Acceptance Criteria:**
- Service records `decided_by` (user/system identifier)
- Service records `decided_at` (exact timestamp)
- Service saves decision `reason` or `notes`
- Service saves all columns to `0_bi_transactions_dupe_audit` table
- Audit entries include foreign key to transaction
- No audit data is ever deleted (append-only)

## Requirements

### Functional Requirements

#### Decision Recording
- **FR-001**: Service must record decisions (APPROVED, REJECTED, INVESTIGATE) for pending duplicates
- **FR-002**: Service must capture reviewer identifier (`decided_by`: user ID, name, or system account)
- **FR-003**: Service must capture exact decision timestamp (`decided_at`: UTC datetime)
- **FR-004**: Service must save optional reason/notes field (`reason`, `notes` in audit table)
- **FR-005**: Service must atomically update transaction status and create audit record in transaction

#### Workflow State Management
- **FR-006**: Service must enforce valid state transitions: PENDING → {APPROVED, REJECTED, INVESTIGATE}
- **FR-007**: Service must reject reviewing transactions not in PENDING state
- **FR-008**: Service must prevent duplicate decisions (idempotent review prevention)
- **FR-009**: Service must support workflow: PENDING → INVESTIGATE → APPROVED or REJECTED (two-stage review)

#### Event Publishing
- **FR-010**: Service must publish `DuplicateDecisionMade` domain event for each decision
- **FR-011**: Event must contain transaction ID, decision status, reviewer info, timestamp
- **FR-012**: Event must be published transactionally (after DB commit, before response)
- **FR-013**: Service must support multiple event listeners (repo pattern)

#### Data Persistence
- **FR-014**: Service must update `decision_status` column in `0_bi_transactions_dupe` table
- **FR-015**: Service must create row in `0_bi_transactions_dupe_audit` table
- **FR-016**: Service must maintain foreign key: audit row → transaction row
- **FR-017**: Service must preserve audit data (never delete audit records)

#### Integration with Story 1
- **FR-018**: Service must use `DuplicateTransactionRepository` from Story 1
- **FR-019**: Service must use `DuplicateTransaction` entity for input validation
- **FR-020**: Service must respect entity immutability (creates new objects, no mutations)

### Non-Functional Requirements

#### Performance
- **NFR-001**: Service method response time < 500ms (p95) under normal load
- **NFR-002**: Database query execution < 100ms for single decision recording
- **NFR-003**: Event publishing (in-memory) < 50ms
- **NFR-004**: Support batch decision recording (Story 3 feature): ≤100 decisions/sec

#### Reliability
- **NFR-005**: 99.9% availability (database connection failures handled gracefully)
- **NFR-006**: Automatic retry logic for transient database errors (up to 3 attempts)
- **NFR-007**: Graceful degradation if event publishing fails (decision persisted, event queued for retry)

#### Security
- **NFR-008**: Service must validate reviewer identity (not accept arbitrary `decided_by` values)
- **NFR-009**: Service must log all decision attempts (failed and successful)
- **NFR-010**: Service must sanitize reason/notes fields to prevent injection attacks
- **NFR-011**: Service must enforce authorization (only authenticated users can record decisions)

#### Maintainability
- **NFR-012**: Code must follow SOLID principles (Single Responsibility, etc.)
- **NFR-013**: Unit test coverage ≥ 85% for all service methods
- **NFR-014**: Service must use dependency injection for repository and event publisher
- **NFR-015**: Service must be decoupled from HTTP/UI framework (framework-agnostic)

#### Scalability
- **NFR-016**: Service must be stateless (horizontally scalable)
- **NFR-017**: Service must support concurrent requests without shared mutable state
- **NFR-018**: Database indexes must support concurrent inserts/updates without contention

#### Data Integrity
- **NFR-019**: No partial failures (if event fails, decision recorded but flagged)
- **NFR-020**: Audit trail must be complete (every decision recorded, no gaps)
- **NFR-021**: Immutable audit records (audit table enforces append-only via constraints)

## Acceptance Criteria

### Feature-Level Acceptance

| Scenario | Given | When | Then |
|----------|-------|------|------|
| **AC-001** Approve duplicate | Transaction in PENDING status | Reviewer calls `approve()` with decision details | Status updated to APPROVED, audit row created, event published |
| **AC-002** Reject duplicate | Transaction in PENDING status | Reviewer calls `reject()` with reason | Status updated to REJECTED, audit row created, event published |
| **AC-003** Mark for investigation | Transaction in PENDING status | Reviewer calls `investigate()` with notes | Status updated to INVESTIGATE, audit row created, event published |
| **AC-004** Prevent duplicate decision | Transaction already APPROVED | Reviewer attempts `approve()` again | InvalidWorkflowTransitionException thrown, no changes made |
| **AC-005** Workflow progression | Transaction in INVESTIGATE status | Reviewer calls `approve()` | Status updated to APPROVED (two-stage review works) |
| **AC-006** Concurrent safe | Two reviewers simultaneously decide | Both call approve/reject on same tx | Last decision persists, both audited, no corruption |
| **AC-007** Audit completeness | Decision recorded | Audit table is queried | All 4 audit columns populated: decided_by, decided_at, reason, notes |
| **AC-008** Event published | Decision approved | Event listener checks event queue | `DuplicateDecisionMade` event published with correct data |

### Test-Level Acceptance

- **AC-009**: Unit tests cover all 6 user story scenarios (100% coverage)
- **AC-010**: Integration tests verify database state changes
- **AC-011**: Integration tests verify event publishing
- **AC-012**: Edge cases tested: null reasons, very long notes, special characters, concurrent requests
- **AC-013**: Error cases tested: non-existent transaction, invalid state, DB connection failure

## Out of Scope

### Explicitly NOT Included

1. **HTTP/API Layer**: Service is framework-agnostic; Story 3 provides REST endpoint wrapper
2. **Authentication System**: Service assumes authenticated `decided_by` value passed in; identity verification is caller's responsibility
3. **Event Delivery**: Service publishes events in-memory; Story 4 handles persistence and delivery
4. **Batch Operations**: Service processes one transaction at a time; Story 3 UI can call in a loop
5. **Email Notifications**: Notifications to reviewers are handled downstream, not by this service
6. **Dashboard/Reporting**: Story 3 handles review metrics and dashboards
7. **Bulk Undo/Rollback**: Individual decision changes only; mass rollback is admin operation (future)
8. **Decision Appeals/Overrides**: Once decided, decisions stand (admin override is separate concern)

### Dependencies Deferred to Story 3 or 4

- REST endpoint implementation
- User authentication context
- Transaction routing to posting system
- UI form validation
- Batch review operations
- Event persistence/delivery

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|------------|
| **Test Coverage** | ≥ 85% | Code coverage tool output |
| **Performance** | < 500ms p95 | Load test results |
| **Reliability** | 99.9% uptime | Error rate monitoring |
| **Data Integrity** | 0 audit gaps | Audit table verification |
| **Maintainability** | SonarQube A rating | Static analysis tool |

---

## Related Documents

- **Epic PRD**: [Phase 1: Duplicate Review System](../epic-duplicate-review-system.md)
- **Story 1 DB Schema**: [Database schema, entity, repository](./story-1-database-schema/)
- **Story 3 Admin UI**: [Admin review dashboard](./story-3-admin-dashboard/)
- **Story 4 Integration**: [Posting hooks and integration](./story-4-posting-integration/)
