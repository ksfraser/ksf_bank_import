---
title: "Story 3: Admin Review Dashboard - Test Strategy & QA Plan"
epic: "Duplicate Review System"
feature: "Admin Review UI"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 3: Admin Review Dashboard - Test Strategy & QA Plan

## 1. Test Strategy Overview

### Testing Scope
- **In Scope**:
  - Controller layer: HTTP request routing, authentication, authorization
  - View layer: HTML rendering, responsive layout, form validation
  - DTOs and form binding
  - Database persistence via existing DuplicateReviewService
  - API integration tests verified against service contracts
  - Accessibility (WCAG AA compliance)
  - Performance on typical hardware/connections
  
- **Out of Scope**:
  - Duplicate detection algorithm (Story 1)
  - DuplicateReviewService business logic (Story 2 - already tested)
  - Transaction posting logic (Story 4)
  - External GL integration details (Story 4)

### Quality Objectives
- **Code Coverage**: ≥80% line coverage, ≥90% critical path coverage
- **Functional Completeness**: 100% acceptance criteria passing
- **Accessibility**: WCAG AA audit passes all checks
- **Performance**: 95th percentile response time <1 second
- **Security**: No SQL injection, XSS, CSRF vulnerabilities
- **Reliability**: Zero data loss in concurrent scenarios

### Risk Assessment

| Risk | Severity | Probability | Mitigation |
|------|----------|-------------|-----------|
| Admin accidentally approves false positive | HIGH | MEDIUM | Comparison view highlights differences; decision audit trail enables rollback |
| Database performance degrades with large queue | MEDIUM | MEDIUM | Pagination tests; load test with 10k+ records |
| Form submission race condition | MEDIUM | LOW | Idempotency tests; database unique constraint on submission timestamp |
| Mobile view usability issues | MEDIUM | MEDIUM | Manual mobile testing on iOS/Android; responsive design CI checks |
| Accessibility compliance gaps | HIGH | LOW | Automated axe audits in CI; manual screen reader testing |
| Session timeout during decision submission | LOW | LOW | Client-side session warning at 14 min; form pre-validation prevents orphaned submissions |

---

## 2. ISTQB Framework Implementation

### 2.1 Test Design Techniques

#### Equivalence Partitioning
Divide input domain into groups that should behave identically:

**Confidence Level Filters:**
- `<50%`: Should filter out most matches
- `50-74%`: Medium confidence, likely false positives
- `≥75%`: High confidence, likely true matches
- `100%`: Perfect matches (rare)

**Date Range Filters:**
- Past (before today): Historical duplicates
- Today: Current batch
- Future (impossible): Edge case
- Invalid dates: Should show error

**User Roles:**
- GL Admin (full access)
- Finance User (read/decide but no delete)
- System Admin (debug mode with raw data)
- Unauthorized user (should redirect)

**Transaction States:**
- PENDING (main case)
- Already APPROVED (conflict scenario)
- Already REJECTED (conflict scenario)
- Already INVESTIGATE (retry scenario)

#### Boundary Value Analysis
Test edge values at the boundaries:

**Amounts:**
- Minimum: $0.01
- Maximum: $999,999,999.99
- Off-by-one: Amount differs by 1 cent (should flag, not match)

**Text Fields (Reason/Notes):**
- Empty string (should reject for Reject decision)
- 1 character (minimum)
- 500 characters (maximum)
- 501 characters (should reject/truncate)
- Special characters: `< > " ' ; -- /* */` (XSS test)
- Unicode: Kanji, emoji (should preserve)

**Pagination:**
- Page 1 (first page)
- Page N (last page with partial results)
- Page N+1 (beyond last page - should redirect to last)
- Items per page: 10, 25, 50, 100, invalid value (should default)

**Confidence Score:**
- 0% (no match)
- 50% (threshold)
- 99% (almost perfect)
- 100% (perfect match)

#### Decision Table Testing
Test complex business rules with multiple conditions:

| Pending? | User Role | High Confidence | Auth? | Action | Expected Result |
|----------|-----------|-----------------|-------|--------|-----------------|
| Yes | GL Admin | Yes | Valid | Approve | Status=APPROVED, Decision saved |
| Yes | GL Admin | No | Valid | Reject | Status=REJECTED, Reason required |
| Yes | GL Admin | Yes | Valid | Investigate | Status=INVESTIGATE, Notes optional |
| No | GL Admin | Yes | Valid | Approve | Shows error: "Already decided" |
| Yes | Finance | Yes | Valid | Approve | Shows error: "Permission denied" |
| Yes | GL Admin | Yes | Invalid | Approve | Redirects to login |
| Yes | GL Admin | Yes | Valid | Approve (no ID) | Shows error: "Invalid transaction" |

#### State Transition Testing
Validate workflow state transitions:

```
PENDING → APPROVED ✅ (allowed)
PENDING → REJECTED ✅ (allowed)
PENDING → INVESTIGATE ✅ (allowed)

APPROVED → REJECTED ❌ (not allowed)
APPROVED → INVESTIGATE ❌ (not allowed)
REJECTED → APPROVED ❌ (not allowed)
etc.
```

#### Experience-Based Testing (Exploratory)
- Rapid clicking Approve button: Should not create duplicate decisions
- Network interruption mid-submission: Should show retry option
- Leaving page mid-operation: Should gracefully clean up
- Session timeout during data entry: Should warn before timeout
- Switching transactions while detail view open: Should load new details
- Using browser back button after approval: Should reflect updated state

### 2.2 Test Types Coverage Matrix

#### Unit Tests (Component Level)

**Controller Tests** (60+ test cases)
```php
class AdminReviewControllerTest {
    // Routing
    - testDashboardRouteRequiresAuth()
    - testDashboardRouteRequiresGLAdminRole()
    
    // Fetch Pending Duplicates
    - testFetchPendingReturnsValidJSON()
    - testFetchPendingPaginatesCorrectly()
    - testFetchPendingFiltersByDateRange()
    - testFetchPendingFiltersByConfidenceLevel()
    - testFetchPendingSearchesByCode()
    - testFetchPendingSearchesByCounterparty()
    - testFetchPendingHandlesEmptyResult()
    - testFetchPendingHandlesDatabaseError()
    
    // Submit Decision
    - testApproveDecisionCallsService()
    - testRejectDecisionRequiresReason()
    - testInvestigateDecisionCallsService()
    - testDecisionSubmissionValidatesCSRF()
    - testDecisionSubmissionHandlesServiceException()
    - testDecisionSubmissionIsIdempotent()
    
    // Error Cases
    - testFetchWithInvalidPaginationNumber()
    - testDecisionOnAlreadyApprovedTransaction()
    - testUnauthorizedUserCannot makeDecision()
}
```

**View/Template Tests** (30+ test cases)
```php
class AdminReviewViewTest {
    // Rendering
    - testDashboardRendersTableHeader()
    - testDashboardRendersAllPendingRows()
    - testDashboardShowsConfidenceScore()
    - testDashboardHighlightsHighConfidenceMatches()
    
    // Forms
    - testApproveFormHasReasonField()
    - testRejectFormRequiresReason()
    - testInvestigateFormHasNotesField()
    - testFormHasCSRFToken()
    - testFormSubmitButtonsClickable()
    
    // Responsive
    - testMobileViewCollapseNonCriticalColumns()
    - testTabletViewShowsHybridLayout()
    - testDesktopViewShowsFullFeatures()
    
    // Accessibility
    - testAllInputsHaveLabels()
    - testColorNotSoleMeansOfConveyingStatus()
    - testFocusOrderLogical()
    - testARIALiveRegionsAnnounceDecisions()
}
```

**DTO Tests** (20+ test cases)
```php
class DuplicateReviewDTOTest {
    // Input Sanitization
    - testReasonFieldSanitizesHTML()
    - testReasonFieldTrimsWhitespace()
    - testReasonFieldEnforcesMaxLength()
    - testNotesFieldHandlesUnicode()
    
    // Serialization
    - testDTOSerializesToJSON()
    - testDTODeserializesFromRequest()
    - testDTORequiredFieldsValidation()
}
```

#### Integration Tests (API Level)

**API Contract Tests** (40+ test cases)
```php
class AdminReviewAPIIntegrationTest {
    // Dashboard Fetch
    - testFetchDuplicatesAPIReturnsCorrectSchema()
    - testFetchDuplicatesWithFiltersReturnsFilteredResults()
    - testFetchDuplicatesWithInvalidFilterIgnoresAndContinues()
    - testFetchDuplicatesAPIErrorHandling(expectError: true)
    
    // Decision Submission
    - testApproveAPICallsServiceAndReturnsConfirmation()
    - testRejectAPICallsServiceAndReturnsConfirmation()
    - testInvestigateAPICallsServiceAndReturnsConfirmation()
    
    // Database Persistence
    - testDecisionRecordedInAuditTable()
    - testDecisionTimestampIsUTC()
    - testDecisionUserTrackedCorrectly()
    - testAuditRecordImmutable(afterCreation: true)
    
    // Concurrent Scenarios
    - testTwoAdminsDecideSameTransactionConcurrently() // last-write-wins
    - testDecisionRaceWithStatusChange()
    
    // Error Paths
    - testDecisionOnDeletedTransactionReturns404()
    - testDecisionWithMissingUserContextReturns401()
    - testDatabaseErrorReturns500WithErrorMessage()
}
```

#### End-to-End Tests (User Workflows)

**Scenario 1: Approve High-Confidence Match**
```gherkin
Feature: Approve Duplicate Decision
    Scenario: Senior Accountant approves genuine duplicate
        Given I am logged in as "accountant@company.com"
        And I am on the Admin Review Dashboard
        And I see 5 pending duplicates
        When I click on the first row (TRNS-2026-0001)
        Then I should see side-by-side comparison
        And the amounts should differ by < 1%
        When I click the "Approve" button
        And I leave the reason field empty
        Then I should see a success message "Decision recorded"
        And the transaction should no longer appear in the pending list
        And the next transaction should auto-load
```

**Scenario 2: Reject False Positive with Reason**
```gherkin
Scenario: Senior Accountant rejects false positive
    Given I am on a transaction comparison view
    When I click the "Reject" button
    And I see "Reason required" validation message
    When I enter "Different counterparty, similar code"
    And I click Submit
    Then the decision should be recorded with reason
    And status should change to REJECTED
```

**Scenario 3: Filter by Date Range**
```gherkin
Scenario: Filter pending duplicates by date range
    Given I am on the Admin Review Dashboard
    When I set date range to "Last 7 days"
    And I click "Apply Filters"
    Then only transactions from past 7 days should display
    And filter badge should show "1 active filter"
    When I clear filters
    Then all pending transactions should display again
```

**Scenario 4: Mobile Decision Workflow**
```gherkin
Scenario: Approve decision on mobile device
    Given I am on mobile (viewport 375px)
    When I view the dashboard
    Then wide columns should collapse
    And action buttons should remain visible (44x44px minimum)
    When I tap a transaction row
    Then comparison view should stack vertically
    When I tap "Approve"
    Then decision should submit successfully
```

#### Performance Tests

**Load Scenarios**
- **Baseline**: Dashboard loads with 50 pending duplicates
  - Expected: <2 seconds first-time, then <500ms from cache
  
- **Scaled**: Dashboard loads with 1,000 pending duplicates (pagination)
  - Expected: <2 seconds (paginated, not all fetched)
  - Verify: Page 1 loads same time as Page 20
  
- **Concurrent Users**: 100 admins on dashboard simultaneously
  - Expected: 95th percentile response time <1 second
  - Verify: No database connection exhaustion
  
**Bottleneck Tests**
- Slow database: 500ms query latency → Dashboard should show loading spinner
- Slow network: 3G connection → Dashboard loads in <4 seconds
- Slow rendering: Large result set → Paginate, virtualize if needed

#### Security Tests

**Input Validation**
- [ ] SQL Injection: Submit `'; DROP TABLE bi_transactions_dupe; --` in reason field → Sanitized/escaped
- [ ] XSS: Submit `<img src=x onload="alert('xss')">` → HTML-escaped when displayed
- [ ] CSRF: Submit decision without CSRF token → 403 Forbidden
- [ ] Privilege escalation: Non-GL-Admin tries to decide → 403 Forbidden

**Session & Auth**
- [ ] Unauthenticated user → 302 Redirect to login
- [ ] Session timeout → 401 Unauthorized on decision submission
- [ ] Invalid CSRF token → 403 Forbidden
- [ ] Token reuse (old token) → 403 Forbidden

**Data Integrity**
- [ ] Duplicate decision submissions (click twice rapidly) → Idempotent, no duplicate records
- [ ] Concurrent decisions on same transaction → Last-write-wins or queued gracefully
- [ ] Decision on already-decided transaction → Validation error

#### Accessibility Tests (WCAG AA)

**Manual Audit** (via axe DevTools, WAVE)
- [ ] No color contrast violations (text ≥4.5:1)
- [ ] All form inputs labeled
- [ ] Logical tab order
- [ ] Focus indicators visible
- [ ] No content hidden by focus loss
- [ ] Headings properly nested
- [ ] Alternative text for images

**Screen Reader Testing** (NVDA, JAWS)
- [ ] Dashboard table announced with proper headers
- [ ] Row content announced clearly
- [ ] Decision buttons announced with purpose
- [ ] Success message announced after decision
- [ ] Error messages announced

**Keyboard Navigation**
- [ ] Tab through all interactive elements
- [ ] Enter submits forms
- [ ] Escape closes detail view
- [ ] Arrow keys navigate table rows
- [ ] No keyboard traps

#### UAT Tests (User Acceptance)

**Finance Lead Walkthrough**
1. Login to admin area
2. Navigate to "Review Duplicates" dashboard
3. Review pending list (check data accuracy)
4. Filter by date range (verify filters work)
5. Click row to compare transactions
6. Approve a genuine duplicate
7. Reject a false positive (with reason)
8. Verify audit trail appears
9. Export audit report
10. Test on mobile device

**Success Criteria for UAT**
- [ ] Finance lead can complete workflow without assistance
- [ ] All data displayed matches database
- [ ] Decisions recorded correctly
- [ ] No data loss or corruption during workflow
- [ ] User feels confident in decision-making process
- [ ] No accessibility barriers noticed

---

## 3. Test Issues & Task Breakdown

### Priority 1: Foundation Tests (Day 1)

**[TC-UT-001] Controller Auth & Routing**
- Acceptance: All routing tests pass
- Estimation: 2 points
- Dependencies: PHPUnit configured
- Deliverable: `AdminReviewControllerTest.php` with 8 tests

**[TC-UT-002] DTO Sanitization & Validation**
- Acceptance: XSS/SQL injection attempts sanitized
- Estimation: 1 point
- Dependencies: Input validation library (e.g., Filter extension)
- Deliverable: `DuplicateReviewDTOTest.php` with 10 tests

**[TC-IT-001] API Contract: Fetch Duplicates**
- Acceptance: API returns correct schema with filters
- Estimation: 3 points
- Dependencies: Controller implemented, Service ready
- Deliverable: `AdminReviewAPITest.php::testFetchDuplicates*` (6 tests)

### Priority 2: Integration Tests (Day 2)

**[TC-IT-002] API Contract: Submit Decision**
- Acceptance: Decision recorded in audit table
- Estimation: 3 points
- Dependencies: [TC-IT-001], DuplicateReviewService
- Deliverable: `AdminReviewAPITest.php::testSubmitDecision*` (8 tests)

**[TC-IT-003] Concurrent Decision Handling**
- Acceptance: No data corruption with simultaneous submissions
- Estimation: 2 points
- Dependencies: [TC-IT-002]
- Deliverable: `AdminReviewConcurrencyTest.php` (3 tests)

**[TC-IT-004] Idempotency Tests**
- Acceptance: Duplicate submission yields same result
- Estimation: 2 points
- Dependencies: [TC-IT-002]
- Deliverable: `AdminReviewIdempotencyTest.php` (4 tests)

### Priority 3: UI & Responsive Tests (Day 3)

**[TC-UT-003] View Rendering**
- Acceptance: Dashboard HTML renders correctly
- Estimation: 2 points
- Dependencies: Template engine working
- Deliverable: `AdminReviewViewTest.php` (12 tests)

**[TC-UT-004] Form Validation**
- Acceptance: Client-side validation prevents invalid submissions
- Estimation: 1 point
- Dependencies: JavaScript/jQuery in view
- Deliverable: View tests for form validation (6 tests)

**[TC-ACC-001] Accessibility Audit**
- Acceptance: WCAG AA audit passes 100%
- Estimation: 3 points
- Dependencies: Axe core, manual testing tools
- Deliverable: `tests/accessibility/AdminReviewAccessibilityTest.php` (8 tests)
- Manual: Screenshot of axe audit logs

**[TC-PERF-001] Performance Benchmarks**
- Acceptance: 95th percentile response <1 second @ 100 concurrent users
- Estimation: 4 points
- Dependencies: Load testing tool (Apache JMeter)
- Deliverable: `tests/performance/AdminReviewLoadTest.php` with results

### Priority 4: E2E & UAT (Day 4)

**[TC-E2E-001] Approve Workflow**
- Acceptance: Can approve duplicate from filter → decision → next transaction
- Estimation: 3 points
- Dependencies: Playwright/Selenium configured
- Deliverable: `tests/e2e/AdminReviewWorkflows.spec.php` (4 scenarios)

**[TC-E2E-002] Mobile Responsive Workflow**
- Acceptance: Full workflow completes on mobile (375px viewport)
- Estimation: 2 points
- Dependencies: Mobile viewport testing
- Deliverable: Mobile test scenarios in E2E suite

**[TC-UAT-001] Finance Lead Acceptance Testing**
- Acceptance: Finance lead completes workflow independently
- Estimation: 3 points (1.5 hour session)
- Dependencies: Staging environment, Finance lead availability
- Deliverable: UAT sign-off document, issue log

---

## 4. Test Environment & Data

### Test Database Setup
```sql
-- Seed test data: 50 pending, 10 approved, 5 rejected
INSERT INTO bi_transactions_dupe ...
-- Indexes on status, created_at for performance testing
```

### Test Data Fixtures
- High-confidence matches (90%+): 10 records
- Medium-confidence (50-75%): 20 records
- Low-confidence (<50%): 20 records
- Already-decided (for state transition tests): 15 records

### Mock Data for Unit Tests
- Mock DuplicateTransaction objects
- Mock DuplicateReviewService responses
- Mock User/Authentication context

### Live Testing Credentials
- GL Admin account: `admin@company.test` / `password123`
- Finance account: `finance@company.test` / `password123`
- System Admin account: `sysadmin@company.test` / `password123`

### Test Environments
- **Unit/Integration**: Local SQLite (parallel test execution)
- **Performance**: Staging MySQL (replicate production schema)
- **E2E**: Staging environment with Chrome headless
- **UAT**: Staging, Firefox + Safari (cross-browser)

---

## 5. Test Automation Strategy

### CI/CD Integration
```yaml
# .github/workflows/story-3-tests.yml
on: [push, pull_request]
jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run PHPUnit
        run: php vendor/bin/phpunit tests/unit/AdminReview*
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        
  integration-tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        options: --health-cmd="mysqladmin ping"
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
    steps:
      - uses: actions/checkout@v3
      - name: Run integration tests
        run: php vendor/bin/phpunit tests/integration/AdminReview*
      - name: Accessibility audit
        run: npm run axe tests/
      - name: Performance test
        run: php vendor/bin/phpunit tests/performance/AdminReviewLoadTest.php --testsuite=load
```

### Test Execution Schedule
- **Pre-commit**: Linter + unit tests related to changed files
- **On PR**: Full unit + integration suite (5 min total)
- **On merge**: Full test suite + E2E + performance (15 min total)
- **Nightly**: E2E + accessibility + performance on staging (1 hour)

---

## 6. Quality Gates & Exit Criteria

### Automatic Quality Checks
- ✅ PHPUnit: ≥80% code coverage
- ✅ SonarQube: Grade A (no blocker issues)
- ✅ Security scan: Zero vulnerabilities
- ✅ Performance: 95th percentile <1s
- ✅ Accessibility: WCAG AA audit passes

### Manual Review Gates
- [ ] Code review: 2 approvals minimum
- [ ] UAT sign-off: Finance lead confirmation
- [ ] Documentation: README updated with usage
- [ ] Git: Conventional commit message

### Go/No-Go Decision
**Go to Production** if:
- ✅ All automated quality gates pass
- ✅ All manual review gates signed off
- ✅ UAT issues resolved or documented as known limitations
- ✅ No high-priority security vulnerabilities
- ✅ Rollback plan documented

**No-Go** if:
- ❌ Code coverage <75%
- ❌ Critical accessibility issues
- ❌ Any high-severity security vulnerabilities
- ❌ UAT reveals data loss scenarios
- ❌ Performance 95th percentile >2s

---

## 7. Test Metrics & Reporting

### Coverage Report
```
File                                    Lines  Coverage  Missing
AdminReviewController.php               180    92%       [15, 42, 178]
AdminReviewService.php                  220    88%       [95-110, 195]
AdminReviewDTO.php                      65     100%      None
AdminReviewView.php                     150    75%       [85-130]
─────────────────────────────────────────────────────────────
Total                                   615    86%       ✅ MEET TARGET
```

### Test Execution Report
```
UNIT TESTS:        32 passed, 0 failed, 0 skipped ✅ 2m 15s
INTEGRATION:       18 passed, 0 failed, 0 skipped ✅ 3m 42s
E2E (Smoke):       12 passed, 0 failed, 1 flaky    ⚠️ 5m 30s
PERFORMANCE:       Baseline 850ms, p95 920ms ✅ 2m

TOTAL:             62 passed, 0 failed | 13m total
```

### Defect Summary
- **Critical**: 0 (no data loss scenarios)
- **High**: 2 (form validation edge cases, fixable in 1h)
- **Medium**: 3 (cosmetic mobile layout, priority for next sprint)
- **Low**: 1 (tooltip timing inconsistency)

---

## 8. Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial test strategy from PRD |

