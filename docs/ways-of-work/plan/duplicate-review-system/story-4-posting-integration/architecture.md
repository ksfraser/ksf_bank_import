---
title: "Story 4: Transaction Posting Integration - Architecture & Technical Design"
epic: "Duplicate Review System"
feature: "Transaction Posting Integration"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 4: Transaction Posting Integration - Architecture & Technical Design

## 1. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│           Batch Posting Job (Nightly - After Review)            │
│              PostingOrchestratorService Trigger                 │
└────────────────────────┬─────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│    PostingOrchestratorService (NEW - Story 4 Core)              │
│                                                                 │
│  Main workflow orchestrator:                                     │
│  ├─ queryDuplicateStatus(transaction_code)                      │
│  ├─ determinPostingEligibility(status)                          │
│  ├─ executePosting(transaction)                                 │
│  └─ handleErrors(error) → retry/rollback/escalate              │
│                                                                 │
│  Dependencies (via DI):                                          │
│  ├─ IDuplicateTransactionRepository (Story 1)                   │
│  ├─ ITransactionRepository (main transactions table)            │
│  ├─ IArchiveService (archive rejected transactions)             │
│  ├─ IAuditLogger (record posting decisions)                     │
│  ├─ IRetryPolicy (exponential backoff)                          │
│  ├─ IEventPublisher (publish posting events - optional)         │
│  └─ Psr\Log\LoggerInterface (PSR-3 logging)                     │
└────────────┬──────────────────────────────────────┬─────────────┘
             │                                      │
             ▼                                      ▼
    ┌─────────────────────────────────┐    ┌────────────────────┐
    │  PostingEligibilityService      │    │  ArchiveService    │
    │                                 │    │                    │
    │  Determines if transaction      │    │  Moves rejected    │
    │  should be posted:              │    │  to archive table  │
    │  ├─ Check review status         │    │  ├─ Validates      │
    │  ├─ Validate business rules     │    │  ├─ Creates        │
    │  ├─ Check amount limits         │    │  │   snapshot      │
    │  └─ Return: ELIGIBLE, SKIP,    │    │  ├─ Ensures        │
    │    HOLD, ERROR                  │    │  │   immutability   │
    │                                 │    │  └─ Publishes      │
    │  Pure function:                 │    │    event           │
    │  eligibility = f(status,        │    │                    │
    │    business_rules)              │    │  Interface:        │
    │                                 │    │  IArchiveService   │
    │  Interface:                     │    │                    │
    │  IPostingEligibilityService     │    └────────────────────┘
    └─────────────────────────────────┘
             │                    │
             ▼                    ▼
    ┌──────────────────────────────────────┐
    │    Repository Layer (Data Access)    │
    │                                      │
    │  IDuplicateTransactionRepository     │
    │  (Story 1 - read source duplicates)  │
    │  ├─ findById(id)                     │
    │  ├─ findApprovedForPosting()         │
    │  ├─ getAuditHistory(id)              │
    │  └─ markAsPosted(id)                 │
    │                                      │
    │  ITransactionRepository (NEW)        │
    │  ├─ create(transaction)              │
    │  ├─ update(transaction)              │
    │  └─ findByCode(code)                 │
    │                                      │
    │  IAuditLogger (NEW)                  │
    │  ├─ logPosting(...)                  │
    │  ├─ logSkipped(...)                  │
    │  ├─ logError(...)                    │
    │  └─ logRollback(...)                 │
    └──────────────────────────────────────┘
             │              │              │
             ▼              ▼              ▼
┌──────────────────────────────────────────────────────────────────┐
│                     Database Layer                               │
│                                                                  │
│  bi_transactions_dupe (Story 1 - Source)                        │
│  ├─ id, transaction_code, trans_date, amount                    │
│  ├─ decision_status (APPROVED, REJECTED, INVESTIGATE, PENDING)  │
│  ├─ decided_by, decided_at, reason                              │
│  ├─ posted_at (NEW - when copied to bi_transactions)            │
│  └─ created_at, updated_at                                      │
│                                                                  │
│  bi_transactions (Existing - Main ledger)                       │
│  ├─ id, transaction_code, trans_date, amount                    │
│  ├─ account_code, GL_account                                    │
│  ├─ source (NEW: 'duplicate_review')                            │
│  ├─ duplicate_id (FK to bi_transactions_dupe) (NEW)             │
│  └─ created_at, updated_at                                      │
│                                                                  │
│  posting_audit_log (NEW)                                        │
│  ├─ id (PK)                                                      │
│  ├─ duplicate_id (FK)                                           │
│  ├─ main_txn_id (FK - bi_transactions.id) (nullable)            │
│  ├─ review_decision (snapshot: APPROVED/REJECTED/INVESTIGATE)   │
│  ├─ review_decided_by, review_decided_at                        │
│  ├─ posted_status (POSTED, SKIPPED, HELD, ERROR)                │
│  ├─ posted_at (nullable)                                        │
│  ├─ error_message (nullable)                                    │
│  ├─ retry_count (tinyint)                                       │
│  └─ created_at (immutable)                                      │
│                                                                  │
│  bi_transactions_rejected_archive (NEW)                         │
│  ├─ id (PK)                                                      │
│  ├─ duplicate_id (FK)                                           │
│  ├─ rejection_reason, rejected_by, rejected_at                  │
│  ├─ original_data_snapshot (JSON)                               │
│  ├─ archived_at                                                 │
│  └─ created_at (immutable)                                      │
│                                                                  │
│  Indexes:                                                        │
│  ├─ posting_audit_log(duplicate_id, posted_at)                  │
│  ├─ posting_audit_log(posted_status)                            │
│  ├─ bi_transactions_dupe(decision_status, decided_at)           │
│  ├─ bi_transactions(duplicate_id)                               │
│  └─ bi_transactions_rejected_archive(archived_at)               │
│                                                                  │
│  Constraints:                                                    │
│  ├─ FOREIGN KEY: duplicate_id refs bi_transactions_dupe         │
│  ├─ CHECK: REJECTED transactions NOT in bi_transactions         │
│  └─ UNIQUE: audit_key (duplicate_id, posted_at)                 │
└──────────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌──────────────────────────────────────────────────────────────────┐
│              Event Publisher (Story 2)                          │
│                                                                  │
│  Publish domain events:                                          │
│  ├─ Publish TransactionPosted event                             │
│  ├─ Publish ArchivingCompleted event                            │
│  └─ Publish PostingFailed event (with escalation)               │
│                                                                  │
│  For downstream integration (Story 5+):                         │
│  ├─ GL posting system listener                                  │
│  ├─ Email notification listener                                 │
│  └─ Reporting system listener                                   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 2. File Structure & Module Organization

```
src/Ksfraser/FaBankImport/Import/
├── Services/
│   ├── Posting/
│   │   ├── PostingOrchestratorService.php           [MAIN - NEW]
│   │   ├── PostingEligibilityService.php            [NEW]
│   │   ├── RetryPolicyService.php                   [NEW]
│   │   ├── ArchiveService.php                       [NEW]
│   │   ├── Interfaces/
│   │   │   ├── IPostingOrchestratorService.php      [Contract - NEW]
│   │   │   ├── IPostingEligibilityService.php       [Contract - NEW]
│   │   │   ├── ITransactionPostingService.php       [Contract - NEW]
│   │   │   ├── IRetryPolicy.php                     [Contract - NEW]
│   │   │   └── IArchiveService.php                  [Contract - NEW]
│   │   │
│   │   └── DTOs/
│   │       ├── PostingRequestDTO.php                [NEW]
│   │       ├── PostingResultDTO.php                 [NEW]
│   │       └── PostingAuditDTO.php                  [NEW]
│   │
│   └── Review/
│       └── [Story 2 services already exist]
│
├── Repositories/
│   ├── Interfaces/
│   │   ├── IDuplicateTransactionRepository.php      [Story 1]
│   │   ├── ITransactionRepository.php               [NEW]
│   │   └── IAuditLogRepository.php                  [NEW]
│   │
│   └── Implementations/
│       ├── DuplicateTransactionRepository.php       [Story 1]
│       ├── TransactionRepository.php                [NEW]
│       └── AuditLogRepository.php                   [NEW]
│
├── Events/
│   ├── TransactionPosted.php                        [NEW]
│   ├── TransactionArchived.php                      [NEW]
│   └── PostingFailed.php                            [NEW]
│
└── Exceptions/
    ├── PostingException.php                         [NEW]
    ├── InvalidPostingStatusException.php            [NEW]
    └── TransactionPostingException.php              [NEW]

tests/unit/
├── Services/
│   └── Posting/
│       ├── PostingOrchestratorServiceTest.php       [NEW]
│       ├── PostingEligibilityServiceTest.php        [NEW]
│       └── RetryPolicyServiceTest.php               [NEW]
│
└── DTOs/
    └── PostingDTOTest.php                           [NEW]

tests/integration/
├── PostingAPIIntegrationTest.php                    [NEW]
├── PostingDatabaseIntegrationTest.php               [NEW]
└── PostingWorkflowIntegrationTest.php               [NEW]

tests/performance/
└── PostingLoadTest.php                              [NEW]

tests/e2e/
└── PostingWorkflows.spec.php                        [NEW - Playwright]

docs/ways-of-work/plan/duplicate-review-system/story-4-posting-integration/
├── prd.md                                           [DONE ✅]
├── test-strategy.md                                 [DONE ✅]
├── architecture.md                                  [THIS FILE]
└── implementation-plan.md                           [NEXT]
```

---

## 3. Technology Stack & Justification

| Component | Technology | Rationale | Alternatives |
|-----------|-----------|-----------|--------------|
| **Event System** | Symfony/EventDispatcher | Story 2 uses it, already available | Custom event bus |
| **Database** | MySQL 8.0 triggers | Enforce audit trail at DB level | Application-level logging |
| **Logging** | Psr/Log + Monolog | Already in project, PSR-3 standard | File logging only |
| **Retry** | Exponential backoff library | Battle-tested pattern | Linear backoff, fixed delays |
| **HTTP Client** | Guzzle (if available) or cURL | Handle GL API timeouts | Direct cURL calls |
| **Testing** | PHPUnit + Mockery | Consistent with Story 2 | N/A |

---

## 4. API Specification

### Posting Status Query

**GET /admin/api/duplicate/{id}/posting-status**
```
GET /admin/api/duplicate/42/posting-status HTTP/1.1

Response 200 OK:
{
  "success": true,
  "data": {
    "id": 42,
    "decision_status": "APPROVED",
    "posting_status": "POSTED",
    "posted_at": "2026-04-09T14:30:00Z",
    "main_transaction_id": 999,
    "error_message": null
  }
}
```

### Bulk Posting Trigger

**POST /admin/api/posting/execute-batch**
```
POST /admin/api/posting/execute-batch HTTP/1.1
Content-Type: application/json

{
  "filter_date_from": "2026-04-08",
  "filter_date_to": "2026-04-09",
  "limit": 1000
}

Response 200 OK:
{
  "success": true,
  "data": {
    "batch_id": "batch-20260409-001",
    "posted": 234,
    "archived": 45,
    "held": 12,
    "errors": 1,
    "retry_scheduled": true
  }
}
```

### Query Transaction Posted Status

**GET /admin/api/duplicate/{id}/main-transaction**
```
GET /admin/api/duplicate/42/main-transaction HTTP/1.1

Response 200 OK:
{
  "success": true,
  "data": {
    "duplicate_id": 42,
    "main_transaction_id": 999,
    "transaction_code": "TXN001",
    "amount": 1000.00,
    "posted_at": "2026-04-09T14:30:00Z"
  }
}
```

---

## 5. Data Flow Diagrams

### Happy Path: APPROVED Transaction Posted

```
1. Batch Job Starts
   ├─ SELECT * FROM bi_transactions_dupe 
   │  WHERE decision_status='APPROVED' 
   │  ORDER BY decided_at ASC
   │  LIMIT 1000
   └─ Result: 234 APPROVED transactions
   
2. For Each Transaction:
   ├─ Query decision status ✅ APPROVED
   ├─ Check business rules (amount <1M, valid account) ✅
   ├─ Mark eligible for posting ✅
   └─ Continue to copying into bi_transactions
   
3. Copy to Main Transactions Table:
   ├─ BEGIN TRANSACTION
   ├─ INSERT INTO bi_transactions 
   │  (transaction_code, trans_date, amount, account_code,
   │   source='duplicate_review', duplicate_id)
   ├─ Receives: id=999 (auto-increment)
   ├─ UPDATE bi_transactions_dupe SET posted_at=NOW()
   ├─ INSERT INTO posting_audit_log 
   │  (duplicate_id, main_txn_id=999, posted_status='POSTED', 
   │   posted_at=NOW())
   ├─ COMMIT TRANSACTION
   └─ Return: { status: POSTED, main_txn_id: 999 }
   
4. Log & Notify:
   ├─ Log: INFO "Transaction copied to main ledger"
   ├─ Publish TransactionPosted event (with main_txn_id)
   └─ Include in daily report
```

### Error Path: Database Error During Copy

```
1. Posting Attempt Fails:
   ├─ INSERT INTO bi_transactions → Database constraint violation
   └─ Exception: TransactionPostingException("Duplicate transaction code exists")
   
2. Error Handling:
   ├─ ROLLBACK TRANSACTION (if in progress)
   ├─ INSERT INTO posting_audit_log
   │  (posted_status='ERROR', error_message='Duplicate transaction code')
   ├─ Log: ERROR "Posting failed for duplicate 42"
   └─ Schedule Retry
   
3. Retry Logic:
   ├─ Create retry task: delay=5s, attempt=1
   ├─ After 5 seconds: Retry copying transaction
   ├─ If fails again: delay=10s, attempt=2
   ├─ After 10 seconds: Retry copying transaction
   ├─ If fails again: delay=20s, attempt=3
   ├─ After 20 seconds: Retry copying transaction
   ├─ If still fails (all 3 retries exhausted): 
   │  ├─ Log: CRITICAL "Max retries exceeded"
   │  ├─ Email admin: "Transaction copy failed - manual intervention needed"
   │  ├─ Mark duplicate as HELD for manual review
   │  └─ Do NOT modify bi_transactions_dupe decision status
   └─ End: Transaction not copied, review decision preserved
```

### Rejection Archive Path

```
1. Batch Encounters REJECTED Transaction:
   ├─ SELECT * FROM bi_transactions_dupe 
   │  WHERE decision_status='REJECTED'
   └─ Result: 45 REJECTED transactions
   
2. For Each REJECTED Transaction:
   ├─ Check status ✅ REJECTED
   ├─ Determine action: ARCHIVE (not copy to bi_transactions)
   └─ Call ArchiveService
   
3. Archive Process:
   ├─ Validate rejection reason exists
   ├─ Create JSON snapshot of transaction data
   ├─ BEGIN TRANSACTION
   ├─ INSERT INTO bi_transactions_rejected_archive
   │  (duplicate_id, rejection_reason, 
   │   rejected_by, rejected_at, 
   │   original_data_snapshot={...})
   ├─ UPDATE bi_transactions_dupe SET posted_at=NOW()
   ├─ INSERT INTO posting_audit_log (posted_status='SKIPPED')
   ├─ COMMIT TRANSACTION
   ├─ Log: INFO "Transaction rejected and archived"
   ├─ Publish TransactionArchived event
   └─ Result: { status: SKIPPED, archived: true }
```

---

## 6. Security Architecture

### Access Control
- **Posting API**: System only (cron job with service account)
- **Rollback API**: System Admin role only
- **Audit Query**: Finance Manager + GL Admin roles
- **Status Check**: Any authenticated user (read-only)

### Data Integrity
- Audit log: Append-only, no modifications
- GL posting: Transactional at database level
- Archive: Immutable after creation
- Constraints: Database-level to prevent posting REJECTED

### Input Validation
- Transaction ID: Integer validation
- Decision status: Enum validation (APPROVED, REJECTED, INVESTIGATE, PENDING)
- GL account: Alphanumeric + '-' only
- Amount: Decimal with precision check

---

## 7. Performance Optimization

### Query Optimization
```sql
-- Index for main query
CREATE INDEX idx_approved_for_posting 
ON bi_transactions_dupe(decision_status, decided_at)
WHERE decision_status = 'APPROVED';

-- Audit log queries
CREATE INDEX idx_posting_audit_by_date 
ON posting_audit_log(posted_at DESC);

-- Archive queries
CREATE INDEX idx_archive_by_reason 
ON bi_transactions_rejected_archive(rejection_reason);
```

### Pagination Strategy
- Fetch 1,000 transactions per batch
- Process in memory  (expect <256MB for 1,000 txns)
- Chunked GL posting (50 at a time to avoid GL overload)

### Caching
- Decision status: Cache 5 minutes (query result)
- GL account validation: Cache 1 hour
- No caching of audit log (always fresh)

---

## 8. Concurrency Patterns

### Optimistic Locking
```sql
ALTER TABLE bi_transactions_dupe ADD COLUMN version INT DEFAULT 1;

-- When updating status:
UPDATE bi_transactions_dupe 
SET decision_status = 'APPROVED', version = version + 1
WHERE id = 42 AND version = 1;

-- If UPDATE matches 0 rows: Conflict detected, retry
```

### Handling Concurrent Postings
- Scenario: Two batch jobs run simultaneously
- Solution: First wins, second gets conflict detection
- Rollback and retry with exponential backoff + jitter

---

## 9. Deployment & Infrastructure

### Database Migrations
```bash
# Version-controlled migration scripts
php migrations/001_create_posting_audit_log.php
php migrations/002_create_rejected_archive.php
php migrations/003_add_audit_triggers.php
```

### Rollback Capability
- Automatic: Transaction wrapper (ROLLBACK on error)
- Manual: POST /admin/api/posting/{batch_id}/rollback
- All rollbacks logged with admin identity & reason

### Monitoring & Alerts
- Alert: If posting fails 3 times (escalate to admin)
- Alert: If >100 INVESTIGATE transactions >30 days old
- Dashboard: Real-time posting status
- Report: Daily email summary to Finance Manager

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial architecture |

