# Phase 1: Duplicate Review System

**Status:** In Progress (TDD-based Implementation)
**Duration:** 2-3 weeks
**Risk Level:** MEDIUM
**Team:** 1-2 Senior Developers + 1 QA
**Baseline:** phase-0-shared-kernel (fe96...latest)

---

## Executive Summary

Implement a user-facing duplicate transaction review workflow allowing admins to review flagged duplicates, confirm matches, move transactions to statements, or reject false positives. This introduces the dual-storage pattern (`bi_transactions_dupe` staging table) and completes the transaction import lifecycle.

**Business Value:**
- Transaction quality assurance before posting to GL
- Audit trail for duplicate decisions  
- Reduced manual reconciliation work
- Compliance with data integrity requirements

---

## Success Criteria

- [ ] `bi_transactions_dupe` staging table with full audit columns
- [ ] Level-1 duplicate matching (exact code + field comparison)
- [ ] Admin review UI accessible from import module
- [ ] User can approve/reject/investigate duplicates with decision tracking
- [ ] All transactions flow through review before statement posting
- [ ] Test coverage ≥ 80% for review service layer

---

## Feature Breakdown (Epic → Features → Stories → Tasks)

### Epic: Duplicate Review System

```
Duplicate Review System
├── Feature: Duplicate Staging & Storage
│   ├── Story 1: Create bi_transactions_dupe table
│   ├── Story 2: Implement DuplicateTransactionRepository  
│   └── Enabler: Database migrations & rollback scripts
│
├── Feature: Duplicate Review Workflow
│   ├── Story 1: Implement DuplicateReviewService
│   ├── Story 2: Build ReviewStatusHandler  
│   ├── Story 3: Implement decision tracking
│   └── Enabler: Event logging & audit trail
│
├── Feature: Admin Review UI
│   ├── Story 1: Create admin review dashboard view
│   ├── Story 2: Implement duplicate comparison table
│   ├── Story 3: Add decision buttons (Approve/Reject/Investigate)
│   └── Enabler: Form state management
│
├── Feature: Transaction Posting Integration
│   ├── Story 1: Update statement posting to use review status
│   ├── Story 2: Implement posting queue logic
│   └── Enabler: Posting transaction hooks
│
└── Feature: Quality Assurance
    ├── Test: Unit tests for DuplicateReviewService
    ├── Test: Integration tests for decision workflow
    ├── Test: UI acceptance tests for admin dashboard
    └── Test: E2E scenarios (import → review → post)
```

---

## Work Item Details (TDD Structure)

### **Story 1: Create bi_transactions_dupe Table**

**Acceptance Criteria:**
- [ ] Table exists with all transaction fields plus audit columns
- [ ] Proper indexing on transaction_code, trans_date, amount
- [ ] Unique constraint on (transaction_code, duplicate_id) preventing duplicates
- [ ] Audit columns: decision_status, decided_by, decided_at, reason, notes
- [ ] Migration script is idempotent and includes rollback

**Tests (Write First - TDD):**
```php
// tests/integration/DuplicateStagingTableTest.php
class DuplicateStagingTableTest extends TestCase {
    public function test_table_exists_with_required_columns() {...}
    public function test_audit_columns_present() {...}
    public function test_unique_constraint_on_transaction_duplicate() {...}
    public function test_migration_is_idempotent() {...}
    public function test_rollback_removes_table() {...}
}
```

**Implementation Tasks:**
- [ ] Create SQL migration file
- [ ] Define entity: `DuplicateTransaction`
- [ ] Create Repository: `DuplicateTransactionRepository`
- [ ] Write integration tests
- [ ] Verify database constraints

**Files to Create:**
- `sql/migrations/001_create_bi_transactions_dupe_table.sql`
- `src/Ksfraser/FaBankImport/Sharred/Entities/DuplicateTransaction.php`
- `src/Ksfraser/FaBankImport/Repositories/DuplicateTransactionRepository.php`
- `tests/integration/DuplicateStagingTableTest.php`

---

### **Story 2: Implement DuplicateReviewService**

**Acceptance Criteria:**
- [ ] Service accepts duplicate flagged transactions
- [ ] Records decision (APPROVED, REJECTED, INVESTIGATE)
- [ ] Updates `bi_transactions_dupe` with decision metadata
- [ ] Returns decision confirmation with audit trail
- [ ] Validates decision against duplicate confidence score

**Tests (Write First - TDD):**
```php
// tests/unit/Services/DuplicateReviewServiceTest.php
class DuplicateReviewServiceTest extends TestCase {
    public function test_approve_duplicate_records_decision() {...}
    public function test_reject_duplicate_updates_status() {...}
    public function test_investigate_creates_investigation_record() {...}
    public function test_invalid_decision_throws_exception() {...}
    public function test_audit_trail_recorded_for_all_decisions() {...}
    public function test_decision_updates_transaction_status() {...}
}
```

**Implementation Tasks:**
- [ ] Create Service: `DuplicateReviewService`
- [ ] Implement decision recording logic
- [ ] Add audit trail logging
- [ ] Create decision events
- [ ] Write unit tests with mocks
- [ ] Document service API

**Files to Create:**
- `src/Ksfraser/FaBankImport/Import/Services/Review/DuplicateReviewService.php`
- `src/Ksfraser/FaBankImport/Import/Services/Review/ReviewDecision.php` (DTO)
- `src/Ksfraser/FaBankImport/Import/Events/DuplicateDecisionMade.php`
- `tests/unit/Services/DuplicateReviewServiceTest.php`

---

### **Story 3: Build Admin Review Dashboard UI**

**Acceptance Criteria:**
- [ ] Dashboard accessible from `/modules/bank_import/admin_review.php`
- [ ] Lists pending duplicate reviews with transaction summary
- [ ] Shows confidence score and match details
- [ ] Decision buttons visible (Approve/Reject/Investigate)
- [ ] Responsive design works on mobile/tablet
- [ ] Filters by date range, confidence threshold, status

**Tests (Write First - TDD):**
```php
// tests/unit/Views/AdminReviewDashboardTest.php
class AdminReviewDashboardTest extends TestCase {
    public function test_dashboard_renders_pending_duplicates() {...}
    public function test_confidence_score_displayed() {...}
    public function test_decision_buttons_present_and_clickable() {...}
    public function test_filters_apply_correctly() {...}
    public function test_responsive_layout_renders() {...}
}
```

**Implementation Tasks:**
- [ ] Create Controller: `AdminReviewController`
- [ ] Create DTO for dashboard data
- [ ] Create View: `AdminReviewDashboardView`
- [ ] Implement filter logic
- [ ] Add form submission handlers
- [ ] Style with FA framework CSS
- [ ] Test with accessibility checks

**Files to Create:**
- `src/Ksfraser/FaBankImport/controllers/AdminReviewController.php`
- `src/Ksfraser/FaBankImport/views/AdminReviewDashboard.php`
- `src/Ksfraser/FaBankImport/DTOs/DuplicateReviewDisplay.php`
- `tests/unit/Views/AdminReviewDashboardTest.php`
- `tests/integration/AdminReviewWorkflowTest.php`

---

### **Story 4: Transaction Posting Integration**

**Acceptance Criteria:**
- [ ] Statement posting queries `bi_transactions_dupe` for current status
- [ ] Only APPROVED transactions are posted to statement
- [ ] REJECTED transactions are archived with reason
- [ ] INVESTIGATE transactions remain in staging
- [ ] Posting API returns decision audit trail

**Tests (Write First - TDD):**
```php
// tests/integration/TransactionPostingWithReviewTest.php
class TransactionPostingWithReviewTest extends TestCase {
    public function test_approved_duplicate_posts_to_statement() {...}
    public function test_rejected_duplicate_not_posted() {...}
    public function test_investigate_status_blocks_posting() {...}
    public function test_posting_updates_audit_trail() {...}
    public function test_rollback_restores_review_status() {...}
}
```

**Implementation Tasks:**
- [ ] Update `ProcessStatementsFetchService` to check review status
- [ ] Create posting validation logic
- [ ] Implement transaction hooks for audit
- [ ] Add rollback/retry logic
- [ ] Create integration tests

**Files to Modify:**
- `src/Ksfraser/FaBankImport/Import/Services/ProcessStatementsFetchService.php`
- `src/Ksfraser/FaBankImport/Import/Services/Posting/PostingValidator.php` (new)

---

## Implementation Sequence (TDD Order)

**Week 1: Foundation (Tests → Implementation)**
1. **Day 1-2:** Story 1 - Database schema + tests
   - Write migration tests first
   - Implement schema
   - Verify with 100% pass rate

2. **Day 3-4:** Story 2 - Review service + tests
   - Write service tests (mocks + stubs)
   - Implement DuplicateReviewService
   - Write edge case tests

3. **Day 5:** Story 4 - Posting integration tests
   - Write integration test suite
   - Implement posting validation hooks

**Week 2: UI & UX (Tests → Implementation)**
4. **Day 1-2:** Story 3 - Admin dashboard + tests
   - Write view tests
   - Implement dashboard controller
   - Add filter logic
   - Style UI

5. **Day 3-4:** Integration testing
   - E2E workflows (import → review → post)
   - Multi-step decision scenarios
   - Error handling & edge cases

6. **Day 5:** QA & Documentation
   - Full regression testing
   - Performance benchmarks
   - Update documentation

---

## Test Coverage Target

| Component | Current | Target | Type |
|-----------|---------|--------|------|
| DuplicateReviewService | 0% | 90% | Unit |
| DuplicateTransactionRepository | 0% | 85% | Unit |
| AdminReviewController | 0% | 80% | Integration |
| AdminReviewDashboardView | 0% | 75% | UI/Component |
| Posting Integration | 0% | 88% | Integration |
| **Overall** | **~45%** | **≥80%** | **Target** |

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|-----------|
| Database migration on production | MEDIUM | HIGH | Test on staging, rollback script, backup |
| Decision state machine complexity | MEDIUM | MEDIUM | Comprehensive state tests, state diagram in code |
| Performance degradation with staging table | LOW | MEDIUM | Index strategy, query optimization, load testing |
| User confusion with review UI | MEDIUM | LOW | Intuitive design, help text, training |
| Incomplete edge case testing | MEDIUM | HIGH | Pair programming, thorough test coverage |

---

## Deliverables

- [ ] `bi_transactions_dupe` table with migrations
- [ ] `DuplicateReviewService` API (fully tested)
- [ ] Admin review dashboard (responsive, tested)
- [ ] Posting integration hooks (validated)
- [ ] Test suite (≥80% coverage)
- [ ] Updated documentation

---

## Definition of Done

✅ All tests written first (TDD)
✅ All tests passing (100%)
✅ Code coverage ≥80%
✅ Security review passed
✅ Performance benchmarks met
✅ Documentation updated
✅ No breaking changes to existing APIs
✅ Backwards compatibility verified

---

## Next Phase Preview

**Phase 2: Process & Admin Modules** (3-4 weeks)
- Independent module structure for Process, Admin, Import, Dedupe submodules  
- Module interface contracts to enforce independence
- Cross-module dependency elimination
- Submodule-specific test suites
