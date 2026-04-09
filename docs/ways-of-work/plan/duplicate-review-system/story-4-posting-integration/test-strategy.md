---
title: "Story 4: Transaction Posting Integration - Test Strategy & QA Plan"
epic: "Duplicate Review System"
feature: "Transaction Posting Integration"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 4: Transaction Posting Integration - Test Strategy & QA Plan

## 1. Test Strategy Overview

### Testing Scope
- **In Scope**:
  - Posting service layer: Status checking, GL posting decision logic
  - Archive service: Rejection archival, data snapshots
  - Event handling: Listening for DuplicateDecisionMade events
  - Audit logging: Recording posting decisions and outcomes
  - Retry logic: Exponential backoff, error recovery
  - API endpoints: Rollback, status checks
  - Database transactions: All-or-nothing posting
  - Integration with existing ProcessStatementsFetchService

- **Out of Scope**:
  - GL system internals (assume GL API works correctly)
  - Email notification delivery (test triggering only)
  - Batch scheduler configuration (test job execution only)
  - External monitoring/alerting system

### Quality Objectives
- **Code Coverage**: ≥80% line coverage, ≥90% critical path coverage
- **Functional Completeness**: 100% acceptance criteria passing
- **Reliability**: Zero data loss in posting scenarios
- **Performance**: Batch posting <2s for 1,000 transactions
- **Audit Accuracy**: 100% of postings recorded
- **Rollback Capability**: All posting states recoverable

### Risk Assessment

| Risk | Severity | Probability | Mitigation |
|------|----------|-------------|-----------|
| GL posting succeeds but audit log fails | CRITICAL | LOW | Separate transaction; audit log writes before GL post; compensating transaction on error |
| REJECTED transaction posted to GL | CRITICAL | LOW | SQL CHECK constraint: invalid status combinations blocked at DB level |
| Posting deadlock with concurrent reviews | HIGH | MEDIUM | Optimistic locking; retry with exponential backoff + jitter |
| Audit trail becomes query bottleneck | MEDIUM | MEDIUM | Partition by month; archival strategy after 6 months |
| Batch posting partial failure (some post, some fail) | HIGH | LOW | Transaction wrapper ensures all-or-nothing; rollback if any error |
| Missing audit record for a posting | HIGH | LOW | Triggers enforce audit row creation; verify in integration tests |

---

## 2. ISTQB Framework Implementation

### 2.1 Test Design Techniques

#### Equivalence Partitioning
Divide posting scenarios into groups:

**Decision Status Partitions:**
- `APPROVED`: Should post to GL ✅
- `REJECTED`: Should archive, not post ❌
- `INVESTIGATE`: Should hold, not post ❌
- `PENDING`: Should not post (decision not made) ❌
- `(No record in bi_transactions_dupe)`: Should post normally ✅

**Transaction Amount Partitions:**
- Amounts < $0.01 (invalid): Should error
- Amounts $0.01 - $999,999.99 (valid): Should post
- Amounts > $999,999.99 (edge): Should post with precision check

**Time Partitions:**
- Recently approved (<1 hour): Should post immediately
- Approved yesterday: Should post on next batch run
- Approved 30+ days ago: Should post (but flag for investigation hold)

#### Boundary Value Analysis
Test edge values:

**Transaction Count Boundaries:**
- 0 transactions to post: Empty batch handled gracefully
- 1 transaction: Min posting size
- 999 transactions: Sub-1000 (expect <2s)
- 1000 transactions: Threshold
- 1001+ transactions: Expect pagination/chunking

**Retry Boundaries:**
- Retry 0 (initial attempt): Posts successfully
- Retry 1 (first backoff): 5s delay
- Retry 2 (second backoff): 10s delay
- Retry 3 (final attempt): 20s delay, then escalate

**Timestamp Boundaries:**
- decision_at = now(): Just approved
- decision_at = 1 second ago: Posted immediately
- decision_at = 30 days ago: Flag for investigation
- decision_at = 31 days ago: Flag for investigation

#### Decision Table Testing
Multi-condition posting rules:

| Status | Amount | GL ERROR | In batch? | Posted? | Archived? | Audit? |
|--------|--------|----------|-----------|---------|-----------|--------|
| APPROVED | Valid | No | Yes | YES | No | YES |
| APPROVED | Valid | Yes | Yes | ROLLBACK | No | YES |
| REJECTED | Valid | - | Yes | NO | YES | YES |
| INVESTIGATE | Valid | - | Yes | NO | No | YES |
| PENDING | Valid | - | Yes | NO | No | YES |
| APPROVED | NULL | - | Yes | ERROR | No | YES |

#### State Transition Testing
Posting workflow states:

```
PENDING → APPROVED → [POSTED | ERROR] → [SUCCESS | ROLLBACK]

PENDING: Awaiting review decision
  ↓ (on REJECTED)
REJECTED: Moved to archive
  ↓ (immutable state, no posting possible)
[END]

PENDING: Awaiting review decision
  ↓ (on INVESTIGATE)
INVESTIGATE: Held in staging
  ↓ (can manually convert to APPROVED/REJECTED)
APPROVED/REJECTED: See above

PENDING: Awaiting review decision
  ↓ (on APPROVED)
APPROVED: Eligible for posting
  ↓ (on posting attempt)
POSTING_IN_PROGRESS: Posted to GL
  ↓
POSTED: Successfully in GL
[END]
  OR
ERROR: GL posting failed
  ↓ (retry)
POSTING_IN_PROGRESS (retry)
```

#### Experience-Based Testing (Exploratory)
- Network interrupt mid-posting: Should rollback, retry on recovery
- Database locked during posting: Should retry with backoff
- GL system down: Should defer posting to next batch
- Duplicates approved in rapid succession: Should batch efficiently
- Mixing approved/rejected in single batch: Should handle correctly
- Posting same transaction twice: Should detect, not double-post
- Rollback immediately after posting: Should restore GL state

---

## 2.2 Test Types Coverage Matrix

### Unit Tests (Component Level - 80+ test cases)

**Posting Service Tests** (45+ cases)
```php
class PostingServiceTest extends TestCase {
    // Status Check
    - testQueryDuplicateStatusForApprovedTransaction()
    - testQueryDuplicateStatusForRejectedTransaction()
    - testQueryDuplicateStatusForInvestigateTransaction()
    - testQueryDuplicateStatusForNonDuplicateTransaction()
    - testQueryDuplicateStatusNotFoundReturnsNull()
    - testStatusCheckCachedFor5Minutes()
    - testStatusCheckUsesIndexedLookup()
    
    // Posting Decision
    - testApprovedTransactionMarkedForPosting()
    - testRejectedTransactionSkippedFromPosting()
    - testInvestigateTransactionHeldInStaging()
    - testPendingTransactionNotPosted()
    
    // GL Posting
    - testPostTransactionToGLTable()
    - testGLPostingIncludesReviewDecisionID()
    - testGLPostingIncludesTimestamp()
    - testPostingRollsBackOnDatabaseError()
    - testPostingRollsBackOnConstraintViolation()
    
    // Archive Handling
    - testRejectedTransactionMovedToArchive()
    - testArchiveIncludesRejectionReason()
    - testArchiveIncludesOriginalDataSnapshot()
    - testArchiveRecordImmutable()
    
    // Audit Logging
    - testAuditLogRecordsSuccessfulPosting()
    - testAuditLogRecordsSkippedPosting()
    - testAuditLogRecordsErrorPosting()
    - testAuditLogIncludesDecisionContext()
    - testAuditLogIncludesGLAccount()
    - testAuditLogAppendOnly()
    
    // Batch Processing
    - testBatchPostingAllOrNothingOnSuccess()
    - testBatchPostingAllOrNothingOnError()
    - testBatchProcesses1000TransactionsUnder2Seconds()
    - testBatchProcessingPartialFailureRollsBackAll()
    
    // Retry Logic
    - testRetryWithExponentialBackoff()
    - testRetryJitterAdded()
    - testMaxRetriesExceededEscalates()
    - testRetryScheduledOnTransientError()
    
    // Event Handling
    - testListensToDuplicateDecisionMadeEvent()
    - testLogsEventContextForVisibility()
}
```

**Archive Service Tests** (15+ cases)
```php
class ArchiveServiceTest extends TestCase {
    - testArchiveRejectedTransaction()
    - testArchiveIncludesDenormalized Data()
    - testArchiveCreatesDataSnapshot()
    - testArchiveTimezonesHandledCorrectly()
    - testArchiveQueryReturnsCorrectRecords()
}
```

**Audit Logger Tests** (20+ cases)
```php
class AuditLoggerTest extends TestCase {
    - testLogPosting(Status::POSTED)
    - testLogPosting(Status::SKIPPED)
    - testLogPostingError()
    - testLogRollback()
    - testAuditLogContextIncludes TransactionCode()
    - testAuditLogContextIncludesDecisionUser()
    - testAuditLogIsSerialized()
}
```

### Integration Tests (Database + Service - 50+ test cases)

**API Contract Tests** (30+ cases)
```php
class PostingAPIIntegrationTest extends TestCase {
    // Status Query API
    - testQueryDuplicateStatusReturnsCorrectSchema()
    - testQueryDuplicateStatusWithFilters()
    - testQueryDuplicateStatusErrorHandling()
    
    // Posting API
    - testPostTransactionsAPIExecutesSuccessfully()
    - testPostTransactionsAPIRollsBackOnError()
    - testPostTransactionsAPIConcurrentRequests()
    
    // Rollback API
    - testRollbackAPIReversesGLPosting()
    - testRollbackAPIClearsAuditRecord()
    - testRollbackAPICreatesAuditEntry()
    - testRollbackAPIRequiresAdminRole()
    
    // Status check API
    - testStatusCheckAPIReturnsLatestStatus()
    - testStatusCheckAPICaches Results()
    - testStatusCheckAPITimeoutHandling()
}
```

**Database Integrity Tests** (20+ cases)
```php
class PostingDatabaseIntegrationTest extends TestCase {
    // Transactions
    - testPostingTransactionAllOrNothing()
    - testPostingTransactionLocking()
    - testPostingTransactionDeadlockDetection()
    
    // Constraints
    - testDatabaseConstraintPreventsInvalidStatusTransition()
    - testForeignKeyIntegrity()
    - testUniqueConstraintOnAuditKey()
    
    // Triggers
    - testTriggerCreatesAuditRowOnPosting()
    - testTriggerEnforcesImmutability()
    
    // Versioning & Locking
    - testOptimisticLockingPreventsConflicts()
    - testConflictGeneratesRetryableError()
}
```

**Workflow Integration Tests** (15+ cases)
```php
class PostingWorkflowIntegrationTest extends TestCase {
    - testCompleteWorkflow_Approve_Post_Audit()
    - testCompleteWorkflow_Reject_Archive()
    - testCompleteWorkflow_Investigate_Hold()
    - testConcurrentApprovals_LastWriteWins()
    - testEventDriven_DecisionMade_ThenPost()
    - testEventDriven_MultipleDecisions()
}
```

### End-to-End Tests (Full Workflow - 12+ scenarios)

**Scenario 1: Happy Path - Approve and Post**
```gherkin
Feature: Post approved duplicates to GL
    Scenario: Successfully post approved transaction
        Given I have an approved duplicate transaction
        And the transaction amount is valid
        When the posting batch runs
        Then the transaction should post to GL
        And the audit log should record success
        And the status should display as POSTED
```

**Scenario 2: Archive Rejected**
```gherkin
Scenario: Archive rejected transaction
    Given I have a rejected duplicate transaction
    When the posting batch runs
    Then the transaction should NOT post to GL
    And it should move to rejected archive
    And the audit log should record rejection reason
```

**Scenario 3: Hold Investigate**
```gherkin
Scenario: Hold investigation transactions
    Given I have an investigate duplicate transaction
    When the posting batch runs
    Then the transaction should NOT post to GL
    And it should remain in bi_transactions_dupe
    And it should be available for manual conversion
```

**Scenario 4: Concurrent Approvals**
```gherkin
Scenario: Handle concurrent approval events
    Given two admins approve the same transaction
    When the posting batch runs
    Then one approval should win (last-write-wins)
    And the transaction should post once
    And audit trail should show both decisions
```

**Scenario 5: Rollback on Error**
```gherkin
Scenario: Rollback posting on GL error
    Given an approved transaction
    And the GL system returns an error
    When the posting attempts
    Then the posting should rollback
    And the transaction should remain eligible
    And retry should be scheduled
```

**Scenario 6: Retry with Backoff**
```gherkin
Scenario: Retry failed posting with backoff
    Given a transaction failed to post
    When retries are attempted
    Then delays should increase (5s, 10s, 20s)
    And jitter should be added
    And after 3 attempts, escalate to admin
```

**Scenario 7: Dashboard Visibility**
```gherkin
Scenario: Finance manager sees posting status
    Given transactions were posted today
    When finance manager views dashboard
    Then should see count of APPROVED posted
    And count of REJECTED archived
    And count of INVESTIGATE held
    And any failed postings with retry status
```

**Scenario 8: Audit Report**
```gherkin
Scenario: Export audit trail
    Given multiple transactions posted today
    When finance manager exports audit report
    Then CSV should include decision journey
    And review_decided_by and review_decided_at
    And posted_at and posted_to_account
    And any errors or rollbacks
```

### Performance Tests (3+ scenarios)

**Load Test: Batch Posting**
```
Given: 1,000 transactions to post
Expected: Complete in <2 seconds
Verify: CPU usage <80%, memory <256MB, no connection pool exhaustion
```

**Bottleneck Test: Status Query**
```
Given: 10,000 concurrent status checks
Expected: <10ms per query (indexed lookup)
Verify: Database not blocking, cache effective
```

**Endurance Test: Overnight Batch**
```
Given: Continuous posting job for 8 hours
Expected: No memory leaks, connection pools healthy
Verify: Logs show normal throughput, no slowdowns
```

### Security Tests (8+ scenarios)

**SQL Injection**
```php
- testStatusCheckParamterization()
- testAuditLogSQLInjectionAttempts()
- testRollbackAPIInputSanitization()
```

**Authorization**
```php
- testOnlyAdminCanCallRollbackAPI()
- testOnlyFinanceCanViewAuditReport()
- testOnlySystemCanTriggerPostingJob()
```

**Data Integrity**
```php
- testAuditLogCannotBeModified()
- testPostingCannotBypassDecisionCheck()
```

### Accessibility Tests (Mobile Reporting)

**Dashboard Widget**
```php
- testPostingStatusWidgetAccessible()
- testDashboardCumulativeCountsAccurate()
- testFailedPostingListsClickable()
```

---

## 3. Test Issues & Task Breakdown

### Priority 1: Foundation Tests (Day 1)

**[TC-UT-001] Posting Service Core Logic**
- Acceptance: Status checks return correct decision
- Estimation: 3 points
- Dependencies: PostingService class
- Deliverable: 20+ unit tests

**[TC-IT-001] Database Posting Transaction**
- Acceptance: All-or-nothing posting works
- Estimation: 3 points
- Dependencies: Database schema, audit tables
- Deliverable: 10+ integration tests

### Priority 2: Archive & Audit (Day 2)

**[TC-UT-002] Archive Service**
- Acceptance: Rejected transactions archived with snapshot
- Estimation: 2 points
- Deliverable: 15+ unit tests

**[TC-UT-003] Audit Logging**
- Acceptance: All postings recorded in audit log
- Estimation: 2 points
- Deliverable: 20+ unit tests

### Priority 3: Error Handling & Retry (Day 2)

**[TC-UT-004] Retry Logic**
- Acceptance: Exponential backoff with jitter works
- Estimation: 2 points
- Deliverable: 8+ unit tests

**[TC-IT-002] Error Path Integration**
- Acceptance: Batch rollback on GL error
- Estimation: 3 points
- Deliverable: 8+ integration tests

### Priority 4: API & Reporting (Day 3)

**[TC-IT-003] Rollback API**
- Acceptance: Manual rollback reverses GL posting
- Estimation: 2 points
- Deliverable: 6+ integration tests

**[TC-PERF-001] Performance Testing**
- Acceptance: Batch <2s, query <10ms
- Estimation: 3 points
- Deliverable: Load test results, bottleneck analysis

### Priority 5: E2E & UAT (Day 4)

**[TC-E2E-001] Complete Workflow**
- Acceptance: Approve → Post → Audit trail complete
- Estimation: 2 points
- Deliverable: 8+ E2E scenarios

**[TC-UAT-001] Finance Manager UAT**
- Acceptance: Finance lead can view posting status & reports
- Estimation: 2 points
- Deliverable: UAT sign-off document

---

## 4. Test Environment & Data

### Test Database Setup
```sql
-- Seed data: 100 approved, 50 rejected, 30 investigate
INSERT INTO bi_transactions_dupe ...
-- Create audit log table
CREATE TABLE posting_audit_log ...
-- Create archive table
CREATE TABLE bi_transactions_rejected_archive ...
```

### Test Data Fixtures
- Approved duplicates: Various amounts, dates, counterparties
- Rejected duplicates: Different rejection reasons
- Investigate duplicates: Vary in age (recent, 15 days, 31 days)
- Failed posting transactions: Various GL error scenarios

### Mock Data & Stubs
- Mock GL API responses (success, error, timeout)
- Mock email notifier (track call count)
- Mock scheduler (immediate job execution)

### CI/CD Integration
```yaml
on: [push, pull_request]
jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      mysql: mysql:8.0
    steps:
      - name: Run unit tests
        run: php vendor/bin/phpunit tests/unit/Posting*
      - name: Run integration tests
        run: php vendor/bin/phpunit tests/integration/Posting*
      - name: Performance test
        run: php vendor/bin/phpunit tests/performance/PostingLoadTest.php
      - name: Coverage report
        run: codecov/codecov-action@v3
```

---

## 5. Quality Gates & Exit Criteria

### Automatic Quality Checks
- ✅ Unit test coverage ≥80%
- ✅ Integration tests passing
- ✅ No performance regressions
- ✅ SonarQube Grade A
- ✅ Zero high-severity security issues

### Manual Review Gates
- [ ] Code review: 2 approvals
- [ ] UAT sign-off: Finance lead
- [ ] Audit trail verified for accuracy
- [ ] No data loss in 24-hour test run

### Go/No-Go Decision
**Go** if: All tests passing + UAT approved + No data loss observed
**No-Go** if: Coverage <75% OR UAT issues OR Data loss in testing

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial test strategy |

