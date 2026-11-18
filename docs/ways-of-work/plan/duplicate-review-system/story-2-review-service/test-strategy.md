---
title: "Story 2: Duplicate Review Service - Test Strategy & Plan"
version: "1.0"
date_created: "2026-04-09"
---

# Test Strategy & Quality Assurance Plan

## 1. Test Strategy Overview

### Testing Scope

**In Scope:**
- `DuplicateReviewService` - core business logic for all decision workflows
- `ReviewDecision` DTO - data structure and validation
- `DuplicateDecisionMade` event - event creation and properties
- Event publishing mechanism (in-memory)
- Database interactions via repository
- Workflow state transitions
- Concurrent request handling
- Error handling and exceptions

**Out of Scope:**
- HTTP/REST endpoint layer (Story 3)
- Authentication/authorization validation (caller responsibility)
- Event persistence and delivery (Story 4)
- UI forms and validation (Story 3)
- Database connection pooling
- Infrastructure/deployment

### Quality Objectives

| Objective | Target | Justification |
|-----------|--------|---------------|
| **Unit Test Coverage** | ≥ 85% | Core business logic requires high reliability |
| **Integration Test Coverage** | ≥ 70% | Database interactions must work end-to-end |
| **Performance** | < 500ms p95 | Real-time review workflow requirement |
| **Error Rate** | < 0.1% | Production reliability for financial data |
| **Code Quality** | SonarQube A | Maintainability for team consistency |
| **Documentation** | 100% classes/methods | SOLID principle - single responsibility clarity |

### Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| **Concurrent Decision Corruption** | HIGH | HIGH | Unit tests for concurrent state; integration tests with DB transactions |
| **Audit Data Loss** | MEDIUM | HIGH | Integration tests verify audit row creation; schema constraints |
| **Event Not Published** | MEDIUM | MEDIUM | Application events pattern; mocked event bus in tests |
| **Workflow Violation Undetected** | MEDIUM | MEDIUM | State transition tests; invalid state exception tests |
| **Race Condition Deadlock** | LOW | HIGH | Load testing with 10+ concurrent requests; database lock timeout tests |
| **Invalid Decision Persisted** | MEDIUM | HIGH | Input validation tests; constraint violation handling |

## 2. ISTQB Framework Implementation

### Test Design Techniques

#### 2.1 Equivalence Partitioning

**Decision Input Classes:**

| Class | Examples | Test Case |
|-------|----------|-----------|
| **Valid Decisions** | "APPROVED", "REJECTED", "INVESTIGATE" | TC-001: Each decision type |
| **Invalid Decisions** | "PENDING", "UNKNOWN", null | TC-002: Reject invalid decision |
| **Valid Reviewer IDs** | "123", "user@example.com", "SYSTEM" | TC-003: Record reviewer properly |
| **Invalid Reviewer IDs** | null, "", special chars | TC-004: Reject invalid reviewer |
| **Valid Reasons** | min 1 char, max 500 chars | TC-005: Normalize reason length |
| **Invalid Reasons** | null for REJECTED, > 500 chars | TC-006: Reject oversized reason |

#### 2.2 Boundary Value Analysis

| Parameter | Min Boundary | Max Boundary | Test Cases |
|-----------|--------------|--------------|-----------|
| **Reason Field** | 0 chars | 500 chars | Empty reason, 500-char reason, 501-char reason |
| **Reviewer ID Length** | 1 char | 255 chars | Single letter, 255 chars, 256 chars |
| **Concurrent Requests** | 2 requests | 100+ requests | 2, 10, 50, 100 concurrent |
| **Timestamp Precision** | Milliseconds | Seconds | UTC timezone, daylight saving transitions |

#### 2.3 Decision Table Testing

**Workflow State Transitions:**

| Current State | Decision Request | Valid? | Expected Result | Test ID |
|---------------|------------------|--------|-----------------|---------|
| PENDING | approve | YES | APPROVED, event published | TC-007 |
| PENDING | reject | YES | REJECTED, event published | TC-008 |
| PENDING | investigate | YES | INVESTIGATE, event published | TC-009 |
| APPROVED | approve | NO | Exception thrown | TC-010 |
| APPROVED | reject | NO | Exception thrown | TC-011 |
| REJECTED | approve | NO | Exception thrown | TC-012 |
| INVESTIGATE | approve | YES | APPROVED, event published | TC-013 |
| INVESTIGATE | reject | YES | REJECTED, event published | TC-014 |
| NON_EXISTENT | approve | NO | Exception thrown | TC-015 |

#### 2.4 State Transition Testing

```
STATE DIAGRAM:
┌─────────┐
│ PENDING │◄─────── Initial State
└────┬────┘
     │
     ├─► APPROVED ───┐
     │                │
     ├─► REJECTED ────┤─► [Final States]
     │                │
     └─► INVESTIGATE ─┤
           │          │
           └──► APPROVED
           │
           └──► REJECTED

Test Paths:
- PENDING → APPROVED (direct approval)
- PENDING → REJECTED (direct rejection)
- PENDING → INVESTIGATE → APPROVED (two-stage)
- PENDING → INVESTIGATE → REJECTED (two-stage)
- Invalid transitions (all → PENDING, repeats, etc.)
```

#### 2.5 Experience-Based Testing

**Error Guessing:**
- Null/empty transaction ID
- Database connection failures (simulate)
- Event publisher exceptions
- Race conditions between reviewers
- Clock role-backs (duplicate timestamps)
- Very long audit notes (Unicode edge cases)
- Special characters in reason field (SQL injection attempts)

### Test Types Coverage Matrix

| Test Type | Coverage | Framework | Entry Criteria |
|-----------|----------|-----------|----------------|
| **Unit Tests** | 85% code coverage | PHPUnit | Service methods, DTOs, events |
| **Integration Tests** | 70% database paths | PHPUnit + TestDatabaseMigrator | Repository interactions, audit creation |
| **Error Handling** | 90% exception paths | PHPUnit mock exceptions | Exception factory, invalid states |
| **Concurrency Tests** | High-contention scenarios | PHPUnit + parallel runner | Multi-threaded decision requests |
| **Performance Tests** | Response time < 500ms | Simple timer in tests | Load simulation with 100+ requests |

## 3. ISO 25010 Quality Characteristics Assessment

| Characteristic | Target Level | How Measured | Test Strategy |
|----------------|--------------|--------------|----------------|
| **Functional Suitability** | HIGH (Critical) | All 6 user stories pass acceptance | Unit + integration tests for each story |
| **Performance Efficiency** | HIGH | < 500ms p95 response time | Load testing with concurrent requests |
| **Compatibility** | MEDIUM | Works with repository/event contracts | Contract tests with mocks |
| **Usability** | N/A | Service is not user-facing | N/A |
| **Reliability** | CRITICAL | 99.9% uptime, no data loss | Error injection, retry logic tests |
| **Security** | HIGH | No SQL injection, audit logged | Input validation tests, log verification |
| **Maintainability** | HIGH | SonarQube A grade, SOLID compliance | Code review checklist, architecture tests |
| **Portability** | MEDIUM | Framework-agnostic service | Mockable dependencies, interface contracts |

## 4. Test Environment & Data Strategy

### 4.1 Test Environment Requirements

**Database:**
- UAT MySQL database (ksfraser_devel_frontaccounting)
- `0_bi_transactions_dupe` table with test data
- `0_bi_transactions_dupe_audit` table (empty before each test)
- Foreign key constraints enabled

**PHP Environment:**
- PHP 8.0+
- PHPUnit 10.x
- Composer auto-loading
- Environment variables from .env (TEST_DB_DSN, etc.)

**Event System:**
- In-memory event dispatcher (no external message queue needed)
- Mocked event listeners for test verification
- Event spy/capture for assertion

### 4.2 Test Data Management

**Setup Strategy:**
1. TestDatabaseMigrator creates schema (via Story 1 migrations)
2. Seed test data:
   - 10 PENDING transactions with various details
   - 2 APPROVED transactions (for negative tests)
   - 2 REJECTED transactions
   - 2 INVESTIGATE transactions with non-final status
3. Teardown: Delete test data, keep schema

**Data Fixtures:**
```php
// Transaction fixture
{
  "transaction_code": "TX-20260401-001",
  "matched_to_code": "TX-20260402-001",
  "decision_status": "PENDING",
  "confidence_score": 0.92,
  "bank_account_id": 1,
  "created_by": "SYSTEM",
  "created_at": "2026-04-01T10:00:00Z"
}

// Decision fixture
{
  "decided_by": "user_123",
  "decided_at": "2026-04-09T14:30:00Z",
  "reason": "Same amount, same description, 1 day apart",
  "notes": null
}
```

### 4.3 Tool Selection

| Tool | Purpose | Usage |
|------|---------|-------|
| **PHPUnit 10.x** | Unit/integration test framework | All tests |
| **Mockery** | Mocking repository/event bus | Isolation tests |
| **Composer** | Dependency management | Auto-loading, package versioning |
| **TestDatabaseMigrator** | Auto-migration setup | Test bootstrap |
| **PHP-DI (or similar)** | Dependency injection | Constructor injection in tests |

### 4.4 CI/CD Integration

**GitHub Actions Workflow:**
```yaml
Test Suite:
  - Run PHPUnit with coverage reporting
  - Generate coverage reports (Cobertura format)
  - Generate SonarQube metrics
  - Fail if coverage < 85%
  - Fail if any critical test fails
```

### 4.5 Coverage Requirements

- **Line Coverage:** ≥ 85%
- **Branch Coverage:** ≥ 80%
- **Method Coverage:** 100%
- **Exception Paths:** ≥ 90%

## 5. Test Execution Plan

### 5.1 Unit Tests (Phase 1: Core Logic)

**Test Class:** `DuplicateReviewServiceTest`

| Test ID | Test Name | Scenario | Expected Result | Priority |
|---------|-----------|----------|-----------------|----------|
| **TC-UT-001** | testApproveValidPending | Approve PENDING transaction | Status → APPROVED, event published | P1 |
| **TC-UT-002** | testRejectValidPending | Reject PENDING transaction | Status → REJECTED, event published | P1 |
| **TC-UT-003** | testInvestigateValidPending | Investigate PENDING transaction | Status → INVESTIGATE, event published | P1 |
| **TC-UT-004** | testApproveAlreadyApproved | Approve already-approved transaction | InvalidWorkflowTransitionException | P1 |
| **TC-UT-005** | testRejectWithoutReason | Reject without required reason | ValidationException | P1 |
| **TC-UT-006** | testInvestigateWithNullNotes | Investigate with null notes | Default notes stored | P2 |
| **TC-UT-007** | testConcurrentApproveSameTransaction | Two reviewers approve same tx | Last decision persists | P1 |
| **TC-UT-008** | testEventPublishedWithCorrectData | Approve transaction | Event contains all decision data | P1 |
| **TC-UT-009** | testAuditRecordCreated | Approve transaction | Audit table updated | P1 |
| **TC-UT-010** | testMissingTransactionThrowsException | Approve non-existent transaction | EntityNotFoundException | P1 |
| **TC-UT-011** | testReasonFieldSanitized | Approve with HTML/JS in reason | HTML escaped in audit | P2 |
| **TC-UT-012** | testTimestampIsUTC | Approve transaction | Timestamp in UTC timezone | P1 |

### 5.2 Integration Tests (Phase 2: Database & Events)

**Test Class:** `DuplicateReviewServiceIntegrationTest`

| Test ID | Test Name | Scenario | Expected Result | Priority |
|---------|-----------|----------|-----------------|----------|
| **TC-IT-001** | testDecisionPersistedToDatabase | Approve decision | Row in audit table | P1 |
| **TC-IT-002** | testAuditForeignKeyValid | Create audit record | FK references correct transaction | P1 |
| **TC-IT-003** | testMultipleDuplicatesIndependent | Approve 2 different duplicates | Each audited separately | P1 |
| **TC-IT-004** | testConcurrent10Requests | 10 concurrent approve requests | All audited, no corruption | P1 |
| **TC-IT-005** | testEventPublisherCalled | Approve transaction | Event listener triggered | P1 |
| **TC-IT-006** | testDatabaseTransactionRollback | Decision fails mid-process | No partial audit records | P1 |
| **TC-IT-007** | testRejectRuleEnforcement | Reject requires reason | Reason field persisted | P1 |

### 5.3 Error Handling Tests (Phase 3: Resilience)

| Test ID | Test Name | Scenario | Expected Result | Priority |
|---------|-----------|----------|-----------------|----------|
| **TC-EH-001** | testDatabaseConnectionFailure | DB connection error | Graceful exception, retry logic | P1 |
| **TC-EH-002** | testEventPublisherFails | Event dispatch throws | Decision persisted, exception logged | P2 |
| **TC-EH-003** | testRepositoryExceptionHandling | Repository throws exception | Service catches and re-throws wrapped | P1 |
| **TC-EH-004** | testInvalidDecisionEnum | Invalid decision status | InvalidEnumValueException | P1 |
| **TC-EH-005** | testNullTransactionHandling | Null transaction object | NullPointerException or wrapped | P1 |

### 5.4 Performance Tests (Phase 4: Load Validation)

| Test ID | Test Name | Load | Expected Result | Priority |
|---------|-----------|------|-----------------|----------|
| **TC-PERF-001** | testSingleDecision | 1 request | < 100ms | P1 |
| **TC-PERF-002** | testBatch100Decisions | 100 sequential requests | p95 < 500ms | P1 |
| **TC-PERF-003** | testConcurrent50Decisions | 50 parallel requests | p95 < 1000ms | P2 |
| **TC-PERF-004** | testMemoryLeakDetection | 1000 decisions over time | Stable memory usage | P2 |

## 6. Test Issues Checklist

**GitHub Issues to Create (from Test Plan):**

- [ ] **TEST-001**: Implement unit test suite (12 tests) - DuplicateReviewServiceTest.php
- [ ] **TEST-002**: Implement integration test suite (7 tests) - DuplicateReviewServiceIntegrationTest.php
- [ ] **TEST-003**: Implement error handling tests (5 tests)
- [ ] **TEST-004**: Implement performance tests (4 tests)
- [ ] **TEST-005**: Set up test database fixture auto-seeding
- [ ] **TEST-006**: Configure code coverage reporting (≥ 85%)
- [ ] **TEST-007**: Create SonarQube quality gate (Grade A)
- [ ] **TEST-008**: Concurrent request test harness development

## 7. UAT (User Acceptance Testing) Strategy

**Story 3 provides admin UI; UAT via UI is Story 3 responsibility.**

**Story 2 UAT Scope:** Service API contract verification

| UAT Scenario | Tester | Steps | Pass Criteria |
|--------------|--------|-------|---------------|
| **UAT-001** Manual approve decision | Dev/QA | Call service with valid transaction | Service returns decision DTO, event published |
| **UAT-002** Manual reject with reason | Dev/QA | Call service with reason | Service persists reason in audit |
| **UAT-003** Two-stage review | Dev/QA | PENDING → INVESTIGATE → APPROVED | All transitions succeed |
| **UAT-004** Audit trail retrieval | QA | Query audit table after decisions | All 4 columns populated |
| **UAT-005** Concurrent 2 users | Dev | Simulate simultaneous approve/reject | Last decision persists, no data loss |

## 8. Quality Gate Criteria

### Entry Criteria (Before Development Starts)
- ✅ PRD approved (Story 2 prd.md complete)
- ✅ Test plan approved (this document)
- ✅ Team understands requirements (acceptance criteria clear)
- ✅ Test environment ready (database schema from Story 1)

### Exit Criteria (Before Story 2 Merge)
- ✅ All 28 tests (12 unit + 7 integration + 5 error + 4 performance) passing
- ✅ Code coverage ≥ 85%
- ✅ SonarQube Grade A
- ✅ All PR review comments resolved
- ✅ No high/critical security issues
- ✅ Performance benchmarks met (< 500ms p95)
- ✅ Commit message follows conventional commits

## 9. Test Execution Timeline

| Phase | Duration | Activities | Deliverable |
|-------|----------|------------|-------------|
| **1. Unit Test Implementation** | 1-2 hours | Write 12 unit tests (RED) | DuplicateReviewServiceTest.php |
| **2. Core Service Implementation** | 2-3 hours | Implement service to pass tests (GREEN+REFACTOR) | DuplicateReviewService.php |
| **3. Integration Test Implementation** | 1-2 hours | Write 7 integration tests | DuplicateReviewServiceIntegrationTest.php |
| **4. Error & Performance Tests** | 1-2 hours | Write error and performance tests | Additional test classes |
| **5. Code Review & Refinement** | 1 hour | Review, coverage checks, SonarQube | Coverage reports, code quality metrics |
| **6. Documentation & Commit** | 30 mins | Update docs, commit with conventional message | Git commit on feat-dupe-check |

**Total Story 2 Duration:** 6-10 hours (1-2 business days)

---

**Next:** Architecture Specification and Implementation Plan documents
