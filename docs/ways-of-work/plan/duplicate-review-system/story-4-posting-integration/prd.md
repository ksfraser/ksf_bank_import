---
title: "Story 4: Transaction Posting Integration - Product Requirements Document"
epic: "Duplicate Review System"
feature: "Transaction Posting Integration"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 4: Transaction Posting Integration - PRD

## 1. Executive Summary

### Problem
After admins approve/reject/investigate duplicate transactions via the Story 3 dashboard, the system must integrate those decisions into the existing transaction posting workflow. Currently, the posting service has no awareness of the duplicate review status, which means:
- APPROVED duplicates sit in staging indefinitely (never posted to GL)
- REJECTED duplicates could be accidentally posted
- INVESTIGATE transactions might bypass GL controls
- No audit trail linking posting decisions back to review decisions

### Solution
Enhance the transaction posting workflow to:
- Query duplicate review status before posting
- Only post APPROVED transactions to GL statement
- Archive REJECTED transactions with decision reason
- Hold INVESTIGATE transactions in staging pending final review
- Record full audit trail linking review → posting
- Provide rollback capability for posting errors

### Impact
- **GL Data Quality**: Only validated transactions post to general ledger
- **Audit Trail**: Complete decision history (review → posting) for compliance
- **Error Recovery**: Rollback posting on error without losing review decisions
- **Operational Efficiency**: Automated decision workflow (manual review only for INVESTIGATE)
- **Compliance**: Meets internal controls for dual authorization

---

## 2. User Personas

### Persona 1: Batch Processing System (Primary)
- **Role**: Automated background job that runs nightly
- **Goals**: Post approved transactions to GL without manual intervention
- **Pain Points**: 
  - Must not post transactions without GL approval
  - Needs clear error handling for edge cases
  - Requires audit trail for SOX compliance
- **Technical Level**: N/A (automated)
- **Frequency**: Nightly (1x per day at 2 AM)

### Persona 2: Finance Manager (Secondary)
- **Role**: Oversees posting operations and handles exceptions
- **Goals**: Monitor posting results, investigate failures, handle rolled-back transactions
- **Pain Points**:
  - Needs visibility into what posted and what didn't
  - Wants to understand why transactions were held
  - Requires reports for GL reconciliation
- **Technical Level**: Medium
- **Frequency**: Daily (10 min review, on-demand investigation)

### Persona 3: System Admin (Tertiary)
- **Role**: Troubleshoots posting failures and maintains data integrity
- **Goals**: Debug posting errors, verify audit trails, recover from posting failures
- **Pain Points**:
  - Needs detailed error logs with transaction context
  - Wants ability to roll back and retry failed postings
  - Requires data consistency validation
- **Technical Level**: High
- **Frequency**: As-needed (1-2 times per week)

---

## 3. User Stories

### Story 4.1: Query Duplicate Review Status Before Posting
**As a** Batch Processing System  
**I want to** check the review status of transactions from `bi_transactions_dupe` before posting  
**So that** I can determine which transactions are eligible for GL posting

**Acceptance Criteria:**
- Before posting a transaction, system queries `bi_transactions_dupe` table
- If transaction exists in `bi_transactions_dupe`:
  - If status = 'APPROVED': Mark as eligible for posting
  - If status = 'REJECTED': Skip (do not post)
  - If status = 'INVESTIGATE': Skip and hold (do not post)
  - If status = 'PENDING': Skip and wait (decision not yet made)
- If transaction does NOT exist in `bi_transactions_dupe`: Post normally (not a duplicate)
- Query completes within 10ms (indexed lookup)
- Logging captures decision status for each transaction checked

### Story 4.2: Post APPROVED Duplicates to GL Statement
**As a** Finance Manager  
**I want to** see APPROVED duplicates posted to GL with full audit trail  
**So that** I can reconcile GL transactions and track duplicate handling

**Acceptance Criteria:**
- APPROVED transactions (`decision_status='APPROVED'`) post to GL statement table
- GL posting includes fields: transaction_code, amount, date, counterparty, review_decision_id
- Posting updates `bi_transactions_dupe_audit` with posting timestamp
- Posting creates GL reconciliation record linking review → posting
- Exported GL transactions show "Duplicate (Approved)" notation
- Posting audit log includes: transaction_id, user who approved, approval timestamp

### Story 4.3: Archive REJECTED Duplicates with Reason
**As a** Finance Manager  
**I want to** see REJECTED duplicates moved to archive with decision reason  
**So that** I can review rejected matches and understand why they were excluded

**Acceptance Criteria:**
- REJECTED transactions (`decision_status='REJECTED'`) do NOT post to GL
- Create `bi_transactions_rejected_archive` table to store rejected transactions
- Archive includes: transaction_id, rejection_reason, rejected_by, rejected_at, original_data_snapshot
- Legacy duplicate handling: If originally from import file, mark import record as "reviewed"
- Archive records are read-only (audit trail, no modifications)
- System generates summary report: "N transactions rejected today with reasons"

### Story 4.4: Hold INVESTIGATE Transactions in Staging
**As a** Finance Manager  
**I want to** see INVESTIGATE transactions held in staging pending final review  
**So that** they remain available if additional review is needed

**Acceptance Criteria:**
- INVESTIGATE transactions (`decision_status='INVESTIGATE'`) do NOT post to GL
- Transactions remain in `bi_transactions_dupe` with status='INVESTIGATE'
- Dashboard shows "Held for Investigation" count
- Time-based alerts: If held >30 days, flag for management review
- Admin can manually convert INVESTIGATE → APPROVED/REJECTED
- Transition tracked in audit log with new approver, reason, timestamp

### Story 4.5: Provide Rollback Capability for Posting Errors
**As a** System Admin  
**I want to** rollback transactions posted in error without losing review decisions  
**So that** data consistency is maintained and audit trail is preserved

**Acceptance Criteria:**
- If posting to GL fails (database error, constraint violation, etc.):
  - Entire batch rolled back (all-or-nothing transaction)
  - Review decisions preserved in `bi_transactions_dupe`
  - Transaction retry automatically scheduled (up to 3 attempts)
  - Error logged with full context (transaction, GL error, timestamp)
  - System admin notified via alert
- Manual rollback available: `POST /admin/api/posting/{batch_id}/rollback`
  - Reverts GL posting for specific batch
  - Creates audit record: "Posted transactions rolled back by [admin], reason: [msg]"
  - Transactions return to eligible for posting on next job run
- Rollback audit trail: "Transaction TNXN-123 posted 2026-04-09 10:30, rolled back 2026-04-09 11:15 by sys_admin"

### Story 4.6: Record Complete Audit Trail for Compliance
**As a** Finance Manager  
**I want to** see the complete decision journey: review → posting → GL  
**So that** I can provide compliance documentation and answer GL discrepancies

**Acceptance Criteria:**
- Audit report shows: Transaction ID → Review Decision → Posting Status → GL Record
- Report exports to CSV with columns:
  - Transaction Code
  - Original Amount
  - Review Decision (Approved/Rejected/Investigate)
  - Decided By
  - Decided At
  - Posted to GL (Yes/No)
  - Posted At
  - GL Account
  - GL Posting Status
  - Notes/Reason
- Audit immutable: Once recorded, cannot be modified (append-only)
- Available on-demand: "Generate Audit Trail Report" button in Finance Dashboard
- Scheduled daily report: Emailed to Finance Manager at 6 AM

---

## 4. Functional Requirements

### 4.1 Duplicate Status Query
- [ ] Before each posting attempt, query `bi_transactions_dupe` table for transaction
- [ ] Indexed lookup on `transaction_code` + `trans_date` for performance
- [ ] Cache query results for 5 minutes (reduce database hits)
- [ ] If transaction found, read `decision_status` field
- [ ] If transaction not found, treat as "non-duplicate" (post normally)
- [ ] Log all status checks to `posting_status_log` table

### 4.2 GL Posting Logic
- [ ] Update `ProcessStatementsFetchService` to call status check
- [ ] Only post transactions with status = 'APPROVED' or no duplicate record
- [ ] Post to existing GL statement table (no schema changes needed)
- [ ] Include posting timestamp in GL record
- [ ] Create link between review and posting: `posting_decisions` table

### 4.3 Rejection Handling
- [ ] Create `bi_transactions_rejected_archive` table (new)
- [ ] On posting: If status='REJECTED', insert into archive instead of GL
- [ ] Archive includes snapshot of original transaction data
- [ ] Archive includes decision reason from `bi_transactions_dupe.reason`

### 4.4 Investigation Handling
- [ ] Do not post transactions with status='INVESTIGATE'
- [ ] Keep in `bi_transactions_dupe` for potential future approval
- [ ] Provide query: "SELECT * FROM bi_transactions_dupe WHERE decision_status='INVESTIGATE' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
- [ ] Alert if investigations >30 days old

### 4.5 Event Handling
- [ ] Listen for `DuplicateDecisionMade` events (from Story 2)
- [ ] On event: Log to `posting_status_log` for visibility
- [ ] Optional: Trigger immediate posting check if decision was APPROVED (timely posting)

### 4.6 Error Handling & Retry
- [ ] If posting fails: Log error, rollback transaction, schedule retry
- [ ] Retry logic: Exponential backoff (5s, 10s, 20s delays)
- [ ] Max retries: 3 before escalation to System Admin
- [ ] Escalation: Email alert + create Jira task

### 4.7 Audit Logging
- [ ] Create `posting_audit_log` table with all decision journey
- [ ] Fields: transaction_id, review_decision, review_decided_by, review_decided_at, posting_status, posted_at, posted_to_account, error_message
- [ ] Immutable: Triggers prevent UPDATE/DELETE on audit log

### 4.8 Reporting & Visibility
- [ ] Dashboard widget: Count of APPROVED/REJECTED/INVESTIGATE posted today
- [ ] Dashboard widget: Failed posting attempts (with retry status)
- [ ] Export audit trail: CSV download for Finance reports
- [ ] Scheduled report: Daily email with summary

---

## 5. Non-Functional Requirements

### 5.1 Performance
- [ ] Duplicate status check: <10ms per query (indexed)
- [ ] Batch posting: <2 seconds for 1,000 transactions
- [ ] Posting API response: <1 second
- [ ] Report generation: <30 seconds for daily report
- [ ] No performance regression on existing posting service

### 5.2 Reliability
- [ ] All-or-nothing posting: All transactions in batch post or all rollback
- [ ] No orphaned GL records: Every posting tracked in audit
- [ ] Retry logic: Automatic recovery from transient failures
- [ ] Rollback capability: Manual intervention for unrecoverable errors

### 5.3 Security
- [ ] Access to posting controls: System Admin role only
- [ ] Access to audit reports: Finance Manager + GL Admin
- [ ] No direct modification of audit trail
- [ ] All manual rollback actions logged with admin identity

### 5.4 Data Integrity
- [ ] Referential integrity: GL postings reference duplicate decisions
- [ ] Constraints prevent: Posting REJECTED transactions, posting with missing review decision
- [ ] Consistency: Posting status in `posting_audit_log` matches GL records

### 5.5 Maintainability
- [ ] Code coverage ≥80% for posting logic
- [ ] Clear separation: Posting logic, decision evaluation, audit logging
- [ ] Extensible: Can add new decision statuses without refactoring
- [ ] Well-documented: API contracts, database schema, retry logic

---

## 6. Acceptance Criteria

### Functional AC
- [ ] APPROVED duplicates post to GL successfully
- [ ] REJECTED duplicates archived with reason
- [ ] INVESTIGATE duplicates held in staging
- [ ] Posting query completes <10ms per transaction
- [ ] Complete audit trail recorded for each posting
- [ ] Rollback restores GL to prior state
- [ ] Retry logic recovers from transient failures
- [ ] Dashboard shows posting status & counts
- [ ] Audit report exportable to CSV
- [ ] Daily email report sent to Finance Manager

### Quality AC
- [ ] Code coverage ≥80% for posting service
- [ ] All integration tests passing
- [ ] No performance regression vs. existing posting
- [ ] All error paths tested
- [ ] Rollback tested with various scenarios (GL error, duplicate records, etc.)
- [ ] Audit trail verified for accuracy
- [ ] Git commits follow conventional commits format

---

## 7. Out of Scope

### Explicitly NOT Included in Story 4
- ❌ GL system changes (only write to existing tables)
- ❌ GL reconciliation logic (use existing reconciliation module)
- ❌ Advanced analytics/ML for duplicate prediction (Story 1+)
- ❌ Bulk decision reversal (e.g., "approve all")
- ❌ Custom decision workflows (fixed 3-option model: APPROVED/REJECTED/INVESTIGATE)
- ❌ Message queue/async jobs (use DB-based job scheduling)
- ❌ Multi-currency posting (assume USD only for now)
- ❌ Webhook notification to external GL system (future enhancement)

---

## 8. Technical Constraints & Dependencies

### Hard Dependencies
- ✅ Story 1: `bi_transactions_dupe` table with audit columns
- ✅ Story 2: `DuplicateReviewService` & `DuplicateDecisionMade` events
- ✅ Story 3: Admin review dashboard (decisions must be made before posting)
- Existing: `ProcessStatementsFetchService` (must be enhanced, not replaced)

### Platform Constraints
- MySQL 8.0+ (use transactions, triggers for audit logging)
- PHP 8.0+ (type hints, named arguments)
- FA framework (existing posting infrastructure)
- No external job queue (use DB-based scheduling)

---

## 9. Success Metrics

### Business Metrics
- **GL Accuracy**: 100% of posted transactions have matching review decision
- **Posting Velocity**: Post ≥95% of eligible duplicates within 24 hours of approval
- **Error Rate**: <0.1% of postings fail (per 1M transactions)
- **Manual Intervention**: <1% of INVESTIGATE transactions need escalation >30 days

### Technical Metrics
- **Performance**: Batch posting <2s for 1,000 transactions
- **Reliability**: 99.9% uptime of posting service (measured monthly)
- **Code Quality**: Coverage ≥80%, SonarQube A grade, zero high-priority vulnerabilities
- **Audit Completeness**: 100% of postings recorded in audit trail

---

## 10. Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|-----------|
| GL posting fails, review decisions lost | Critical data loss | Low | Transaction wrapper; audit separate from GL; rollback capability |
| REJECTED transactions accidentally posted | GL corruption | Low | SQL constraint: `CHECK (decision_status != 'REJECTED' OR posted_to_gl = FALSE)` |
| Posting deadlocks with concurrent approvals | Service unavailable | Low | Use optimistic locking; retry with jitter |
| Audit trail becomes append-only performance bottleneck | Slow reports | Low | Partition audit table by month; archive to cold storage after 6 months |
| No visibility into posting status | Finance can't reconcile | Medium | Implement dashboard metrics, scheduled reports, real-time API endpoint |

---

## 11. Timeline & Dependencies

### Release Plan
- **Phase 1** (Days 1-2): Design & Planning (THIS DOCUMENT + architecture + tests)
- **Phase 2** (Days 2-3): TDD implementation (posting service, audit logging)
- **Phase 3** (Days 4): Integration testing & reporting
- **Phase 4** (Day 5): Deployment, monitoring, Go-live

### Blockers
- ✅ Story 1 (Database schema) - COMPLETE
- ✅ Story 2 (DuplicateReviewService) - COMPLETE
- ✅ Story 3 (Admin dashboard) - PLANNING COMPLETE (ready to start)
- ⏳ Story 4 → Can parallelize with Story 3 (no sequential dependency)

---

## 12. Appendix: Data Model Context

### Source Tables

#### `bi_transactions_dupe` (Story 1)
```
- id (INT, PK)
- transaction_code (VARCHAR)
- trans_date (DATE)
- amount (DECIMAL)
- counterparty_name (VARCHAR)
- decision_status (ENUM: PENDING, APPROVED, REJECTED, INVESTIGATE)
- decided_by (VARCHAR, nullable)
- decided_at (DATETIME, nullable, UTC)
- reason (TEXT, nullable)
- created_at (DATETIME, UTC)
- updated_at (DATETIME, UTC)
```

#### `posting_audit_log` (NEW - Story 4)
```
- id (INT, PK, auto-increment)
- transaction_id (INT, FK → bi_transactions_dupe)
- transaction_code (VARCHAR)
- review_decision (ENUM: APPROVED, REJECTED, INVESTIGATE, PENDING)
- review_decided_by (VARCHAR, nullable)
- review_decided_at (DATETIME, nullable, UTC)
- posted_status (ENUM: POSTED, SKIPPED, HELD_FOR_REVIEW, ERROR)
- posted_at (DATETIME, nullable, UTC)
- posted_to_account (VARCHAR, nullable) -- GL account code
- error_message (TEXT, nullable)
- created_at (DATETIME, UTC) -- immutable
```

#### `bi_transactions_rejected_archive` (NEW - Story 4)
```
- id (INT, PK, auto-increment)
- transaction_id (INT)
- transaction_code (VARCHAR)
- trans_date (DATE)
- amount (DECIMAL)
- counterparty_name (VARCHAR)
- rejection_reason (TEXT)
- rejected_by (VARCHAR)
- rejected_at (DATETIME, UTC)
- original_data_snapshot (JSON) -- full transaction data backup
- created_at (DATETIME, UTC)
- archived_at (DATETIME, UTC)
```

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial PRD from epic requirements |

