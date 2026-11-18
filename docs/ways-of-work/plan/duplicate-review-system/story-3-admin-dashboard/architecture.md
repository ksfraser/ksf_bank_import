---
title: "Story 3: Admin Review Dashboard - Architecture & Technical Design"
epic: "Duplicate Review System"
feature: "Admin Review UI"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 3: Admin Review Dashboard - Architecture & Technical Design

## 1. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     HTTP Client Layer                           │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │  Admin Browser   │  │   Mobile Safari  │  │  Tablet App  │  │
│  │  (Desktop View)  │  │  (Phone View)    │  │  (Hybrid)    │  │
│  └────────┬─────────┘  └────────┬─────────┘  └──────┬───────┘  │
│           │                     │                    │          │
├───────────┼─────────────────────┼────────────────────┼──────────┤
│           ▼                     ▼                    ▼          │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    Admin Review Dashboard (View Layer)              │       │
│   │                                                     │       │
│   │  Templates:                                         │       │
│   │  ├─ duplicate_review_dashboard.php (main view)     │       │
│   │  ├─ duplicate_comparison_detail.php (expandable)   │       │
│   │  ├─ decision_form_modal.html (embedded forms)      │       │
│   │  └─ filter_panel.php (sidebar filters)             │       │
│   │                                                     │       │
│   │  Styling: Bootstrap 5 + custom CSS for responsive  │       │
│   │  Scripts: Vanilla JS + AJAX for decision submission │       │
│   └─────────────┬───────────────────────────────────────┘       │
│                 │                                               │
├─────────────────┼───────────────────────────────────────────────┤
│                 ▼                                               │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    Controller Layer (HTTP Request Handler)          │       │
│   │                                                     │       │
│   │  AdminReviewController                              │       │
│   │  ├─ GET /admin/review (show dashboard)             │       │
│   │  ├─ POST /admin/api/duplicates/search (fetch list) │       │
│   │  ├─ GET /admin/api/duplicate/{id} (fetch detail)   │       │
│   │  ├─ POST /admin/api/duplicate/{id}/approve         │       │
│   │  ├─ POST /admin/api/duplicate/{id}/reject          │       │
│   │  ├─ POST /admin/api/duplicate/{id}/investigate     │       │
│   │  └─ GET /admin/api/duplicate/{id}/history          │       │
│   │                                                     │       │
│   │  Responsibilities:                                  │       │
│   │  ├─ HTTP routing & middleware                       │       │
│   │  ├─ Authentication & authorization checks          │       │
│   │  ├─ Request parameter binding                       │       │
│   │  ├─ Response serialization (JSON)                   │       │
│   │  └─ Error handling (HTTP status codes)              │       │
│   └─────────────┬───────────────────────────────────────┘       │
│                 │                                               │
├─────────────────┼───────────────────────────────────────────────┤
│                 ▼                                               │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    Service Layer (Business Logic)                   │       │
│   │                                                     │       │
│   │  AdminReviewService (NEW)                           │       │
│   │  ├─ searchDuplicates(filters): array[]             │       │
│   │  ├─ getDuplicateDetail(id): DuplicateTransaction    │       │
│   │  ├─ getDecisionHistory(id): array[]                 │       │
│   │  └─ buildComparisonView(tx): ComparisonDTO          │       │
│   │                                                     │       │
│   │  DuplicateReviewService (Story 2, already built)    │       │
│   │  ├─ approve(tx, decidedBy, reason)                 │       │
│   │  ├─ reject(tx, decidedBy, reason)                  │       │
│   │  └─ investigate(tx, decidedBy, notes)              │       │
│   │                                                     │       │
│   │  Responsibilities:                                  │       │
│   │  ├─ Query orchestration                             │       │
│   │  ├─ Data transformation                             │       │
│   │  ├─ Business rule validation                        │       │
│   │  ├─ Delegation to Story 2 service for decisions     │       │
│   │  └─ Event listening & reaction                      │       │
│   └─────────────┬───────────────────────────────────────┘       │
│                 │                                               │
├─────────────────┼───────────────────────────────────────────────┤
│                 ▼                                               │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    DTOs / Domain Models                             │       │
│   │                                                     │       │
│   │  DuplicateListItemDTO                               │       │
│   │  ├─ id                                              │       │
│   │  ├─ transaction_code                                │       │
│   │  ├─ amount                                          │       │
│   │  ├─ counterparty_name                               │       │
│   │  ├─ confidence_score                                │       │
│   │  ├─ decision_status                                 │       │
│   │  └─ created_at                                      │       │
│   │                                                     │       │
│   │  DuplicateComparisonDTO                             │       │
│   │  ├─ original_transaction (fields array)             │       │
│   │  ├─ duplicate_transaction (fields array)            │       │
│   │  ├─ confidence_breakdown (calculation detail)       │       │
│   │  └─ match_reason                                    │       │
│   │                                                     │       │
│   │  Existing:                                          │       │
│   │  ├─ DuplicateTransaction (Story 1 entity)           │       │
│   │  ├─ ReviewDecision (Story 2 DTO)                    │       │
│   │  └─ DuplicateDecisionMade (Story 2 event)           │       │
│   └─────────────┬───────────────────────────────────────┘       │
│                 │                                               │
├─────────────────┼───────────────────────────────────────────────┤
│                 ▼                                               │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    Repository Pattern (Data Access)                 │       │
│   │                                                     │       │
│   │  IDuplicateTransactionRepository (Story 1 interface)│       │
│   │  └─ Implementation: DuplicateTransactionRepository  │       │
│   │     ├─ findPending(limit, offset, filters)         │       │
│   │     ├─ findById(id)                                 │       │
│   │     ├─ count(filters)                              │       │
│   │     ├─ getAuditHistory(id)                         │       │
│   │     └─ update(tx)                   [used by Story 2 service] │
│   │                                                     │       │
│   │  Storage: bi_transactions_dupe table (Story 1)      │       │
│   └─────────────┬───────────────────────────────────────┘       │
│                 │                                               │
├─────────────────┼───────────────────────────────────────────────┤
│                 ▼                                               │
│   ┌─────────────────────────────────────────────────────┐       │
│   │    Data Layer (Database)                            │       │
│   │                                                     │       │
│   │  bi_transactions_dupe                               │       │
│   │  ├─ id (PK)                                         │       │
│   │  ├─ transaction_code                                │       │
│   │  ├─ trans_date, amount, counterparty_name           │       │
│   │  ├─ confidence_score, match_reason                  │       │
│   │  ├─ decision_status (PENDING, APPROVED, REJECTED)   │       │
│   │  ├─ decided_by, decided_at, reason, notes (audit)   │       │
│   │  └─ created_at, updated_at (timestamps, UTC)        │       │
│   │                                                     │       │
│   │  bi_transactions_dupe_audit (append-only history)   │       │
│   │  └─ Immutable record of all decision changes        │       │
│   │                                                     │       │
│   │  Indexes:                                           │       │
│   │  ├─ (decision_status, created_at) [main query]      │       │
│   │  ├─ (transaction_code) [search]                     │       │
│   │  ├─ (counterparty_name) [search]                    │       │
│   │  └─ (decided_at) [audit reporting]                  │       │
│   └─────────────────────────────────────────────────────┘       │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Middleware Stack:                                              │
│  ├─ AuthenticationMiddleware (require login)                   │
│  ├─ AuthorizationMiddleware (require GL Admin role)            │
│  ├─ CSRFMiddleware (validate CSRF tokens)                      │
│  ├─ RequestLoggingMiddleware (audit trail)                     │
│  └─ ExceptionHandlingMiddleware (error responses)              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. File Structure & Module Organization

```
src/Ksfraser/FaBankImport/
├── Import/
│   ├── Controllers/
│   │   └── AdminReviewController.php          [NEW - HTTP routing]
│   │
│   ├── Services/
│   │   ├── Review/
│   │   │   ├── DuplicateReviewService.php      [Story 2 - decision logic]
│   │   │   ├── AdminReviewService.php          [NEW - dashboard logic]
│   │   │   └── Interfaces/
│   │   │       ├── IDuplicateReviewService.php [Story 2]
│   │   │       ├── IAdminReviewService.php     [NEW - optional contract]
│   │   │       ├── IEventPublisher.php         [Story 2]
│   │   │       └── ILogger.php                 [Story 2]
│   │   │
│   │   └── DTOs/
│   │       ├── DuplicateListItemDTO.php        [NEW - for table display]
│   │       ├── DuplicateComparisonDTO.php      [NEW - for detail view]
│   │       └── DecisionHistoryDTO.php          [NEW - for audit trail]
│   │
│   ├── Entities/
│   │   └── DuplicateTransaction.php            [Story 1 - already built]
│   │
│   ├── Events/
│   │   ├── DomainEvent.php                     [Story 2]
│   │   └── DuplicateDecisionMade.php           [Story 2]
│   │
│   └── Repositories/
│       └── DuplicateTransactionRepository.php  [Story 1 - already built]
│
tests/unit/
├── Controllers/
│   └── AdminReviewControllerTest.php           [NEW]
│
├── Services/
│   ├── AdminReviewServiceTest.php              [NEW]
│   └── Review/
│       └── DuplicateReviewServiceTest.php      [Story 2 - already done]
│
└── DTOs/
    └── AdminReviewDTOTest.php                  [NEW]

tests/integration/
├── AdminReviewAPITest.php                      [NEW]
├── AdminReviewConcurrencyTest.php              [NEW]
└── DuplicateStagingTableTest.php               [Story 1 - already done]

tests/e2e/
└── AdminReviewWorkflows.spec.php               [NEW - Playwright/Selenium]

docs/ways-of-work/plan/duplicate-review-system/story-3-admin-dashboard/
├── prd.md                                      [DONE ✅]
├── test-strategy.md                            [DONE ✅]
├── architecture.md                             [THIS FILE]
└── implementation-plan.md                      [NEXT]

views/admin/
└── duplicate_review/
    ├── dashboard.php                           [NEW - main view]
    ├── comparison_detail.php                   [NEW - expandable row]
    ├── decision_modal.php                      [NEW - form modals]
    ├── filter_panel.php                        [NEW - sidebar]
    ├── styles.css                              [NEW - responsive styling]
    └── scripts.js                              [NEW - AJAX interactions]
```

---

## 3. Technology Stack & Justification

### Backend Stack

| Component | Technology | Rationale | Alternatives Considered |
|-----------|-----------|-----------|-------------------------|
| **Framework** | FA (existing) | Consistency with project | N/A |
| **Language** | PHP 8.0+ | Type hints, strict modes | N/A |
| **Database** | MySQL 8.0 | Existing schema, indexes efficient | N/A |
| **HTTP Library** | cURL / Guzzle | Standard PHP, already available | HTTPClient |
| **Logging** | PSR-3 (Monolog) | Industry standard, Story 2 uses it | File logging only |
| **Events** | Symfony/EventDispatcher | Well-tested, Story 2 uses it | Custom event bus |
| **Testing** | PHPUnit 10.x | De facto standard, existing use | N/A |
| **Mocking** | Mockery 1.x | Complements PHPUnit, already used | PHPUnit mocks only |

### Frontend Stack

| Component | Technology | Rationale | Alternatives Considered |
|-----------|-----------|-----------|-------------------------|
| **Template Engine** | FA (PHP) | Consistency with admin area | Blade, Twig |
| **HTML Framework** | Bootstrap 5 | Responsive, accessibility built-in | Tailwind, custom CSS |
| **Styling** | CSS3 + SCSS | SCSS for mixins/variables | Tailwind (newer choice) |
| **Interactivity** | Vanilla JS | No jQuery dependency needed | jQuery (legacy), Alpine.js |
| **HTTP Client** | Fetch API | Modern, native, no polyfills needed | AXIO (if React was used) |
| **Accessibility** | ARIA + semantic HTML | WCAG AA compliance built-in | Manual labeling only |
| **Responsiveness** | CSS Grid + Flexbox | Native, performant | Bootstrap grid only |

### Rationale for Library Choices

**Why NOT a JavaScript Framework (React, Vue)?**
- Story 3 is a simple CRUD dashboard, not a complex SPA
- FA framework already has templating
- No complex state management needed
- Reduces build pipeline complexity
- Faster load time (no JS bundle)
- Same accessibility possible with HTML/CSS/JS

**Why Vanilla JS instead of jQuery?**
- jQuery overhead not justified for this feature
- Fetch API is modern and well-supported (IE11 not supported by FA admin area)
- Smaller JavaScript payload
- Consistent with modern FA development

**Why Bootstrap 5?**
- Already used in FA admin area
- Comprehensive component library
- Built-in accessibility
- Responsive utilities reduce custom CSS
- Large community for troubleshooting

---

## 4. API Design Specification

### Endpoints

#### GET /admin/review
**Display Dashboard**
```
GET /admin/review HTTP/1.1
Host: fa.local
```
**Response:** HTML page with filters + table
**Status Codes:**
- `200 OK`: Dashboard loaded
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Not GL Admin role

#### POST /admin/api/duplicates/search
**Fetch Pending Duplicates (with Filters)**
```
POST /admin/api/duplicates/search HTTP/1.1
Content-Type: application/json

{
  "page": 1,
  "limit": 25,
  "sort": "created_at",
  "order": "asc",
  "filters": {
    "date_from": "2026-04-01",
    "date_to": "2026-04-09",
    "confidence_min": 75,
    "counterparty": "ABC Corp",
    "status": "PENDING"
  },
  "search": "TRNS-2026"
}
```
**Response:**
```json
{
  "success": true,
  "data": {
    "total": 127,
    "page": 1,
    "limit": 25,
    "items": [
      {
        "id": 1,
        "transaction_code": "TRNS-2026-0001",
        "trans_date": "2026-04-08",
        "amount": 5000.00,
        "counterparty_name": "ABC Corp",
        "confidence_score": 92,
        "match_reason": "Exact code + date match",
        "created_at": "2026-04-09T10:30:00Z"
      },
      // ... more items
    ]
  },
  "meta": {
    "execution_time_ms": 245,
    "filtered_by": ["confidence_min", "date_range"]
  }
}
```
**Status Codes:**
- `200 OK`: Results returned
- `400 Bad Request`: Invalid filters or pagination
- `401 Unauthorized`: Not authenticated
- `500 Internal Server Error`: Database error (logged)

#### GET /admin/api/duplicate/{id}
**Fetch Single Duplicate Detail**
```
GET /admin/api/duplicate/42 HTTP/1.1
```
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "original_transaction": {
      "code": "TRNS-2026-0001",
      "date": "2026-04-08",
      "amount": 5000.00,
      "counterparty": "ABC Corp",
      "description": "Payment for services",
      "reference": "INV-2026-1234"
    },
    "duplicate_transaction": {
      "code": "TRNS-2026-0001",
      "date": "2026-04-08",
      "amount": 5000.15,
      "counterparty": "ABC Corporation",
      "description": "Payment",
      "reference": "INV-2026-1234"
    },
    "confidence_breakdown": {
      "code_match": 100,
      "date_match": 100,
      "amount_match": 99.97,
      "counterparty_match": 95,
      "overall": 92
    },
    "match_reason": "Exact code + date, amount diff 0.15 (0.003%), Similar counterparty name"
  }
}
```
**Status Codes:**
- `200 OK`: Detail returned
- `404 Not Found`: ID doesn't exist or not PENDING
- `401 Unauthorized`: Not authenticated

#### POST /admin/api/duplicate/{id}/approve
**Submit Approve Decision**
```
POST /admin/api/duplicate/42/approve HTTP/1.1
Content-Type: application/json
X-CSRF-Token: abc123xyz

{
  "reason": ""
}
```
**Response:**
```json
{
  "success": true,
  "message": "Decision recorded by user@company.com at 2026-04-09T11:45:30Z",
  "data": {
    "id": 42,
    "decision_status": "APPROVED",
    "decided_by": "user@company.com",
    "decided_at": "2026-04-09T11:45:30Z"
  }
}
```
**Status Codes:**
- `200 OK`: Decision recorded
- `400 Bad Request`: Invalid transaction state or validation failed
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Permission denied
- `404 Not Found`: Transaction not found
- `409 Conflict`: Transaction already decided (race condition)

#### POST /admin/api/duplicate/{id}/reject
**Submit Reject Decision**
```
POST /admin/api/duplicate/42/reject HTTP/1.1
Content-Type: application/json

{
  "reason": "Different counterparty - false positive"
}
```
**Response:** Same structure as /approve
**Status Codes:** Same as /approve

#### POST /admin/api/duplicate/{id}/investigate
**Submit Investigate Decision**
```
POST /admin/api/duplicate/42/investigate HTTP/1.1
Content-Type: application/json

{
  "notes": "Need to verify counterparty spelling with accounting"
}
```
**Response:** Same structure as /approve
**Status Codes:** Same as /approve

#### GET /admin/api/duplicate/{id}/history
**Fetch Decision Audit Trail**
```
GET /admin/api/duplicate/42/history HTTP/1.1
```
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "decision": "APPROVED",
      "decided_by": "john@company.com",
      "decided_at": "2026-04-09T11:45:30Z",
      "reason": "",
      "notes": ""
    },
    {
      "id": 2,
      "decision": "INVESTIGATE",
      "decided_by": "jane@company.com",
      "decided_at": "2026-04-08T14:20:00Z",
      "reason": "",
      "notes": "Need counterparty verification"
    }
  ]
}
```
**Status Codes:**
- `200 OK`: History returned
- `401 Unauthorized`: Not authenticated
- `404 Not Found`: Transaction not found

---

## 5. Data Flow Diagrams

### Happy Path: Approve Decision

```
┌─────────────┐
│   Browser   │
│  (Admin UI) │
└──────┬──────┘
       │ 1. Click "Approve" button
       │    (sends POST /api/duplicate/42/approve)
       ▼
┌──────────────────────────┐
│ AdminReviewController    │
│ ::approveAction()        │
└──────┬───────────────────┘
       │ 2. Extract requestor from session
       │    Bind DTO from request
       │    Validate CSRF token
       ▼
┌──────────────────────────┐
│ AdminReviewService       │
│ ::approveDuplicate()     │
└──────┬───────────────────┘
       │ 3. Fetch transaction from repo
       │    Validate state (PENDING)
       ▼
┌──────────────────────────┐
│ DuplicateReviewService   │
│ ::approve()              │
└──────┬───────────────────┘
       │ 4. Call repository.update()
       │    Publish DuplicateDecisionMade event
       ▼
┌──────────────────────────┐
│ DuplicateTransactionRepo │
│ ::update()               │
└──────┬───────────────────┘
       │ Database Transaction
       │ UPDATE bi_transactions_dupe SET
       │   decision_status='APPROVED',
       │   decided_by='user@..',
       │   decided_at=NOW(),
       │   reason=''
       │ INSERT INTO audit similar data
       ▼
┌──────────────────────────┐
│ MySQL Database           │
└──────┬───────────────────┘
       │ 5. Event listeners react (if any)
       │
       ▼
┌──────────────────────────┐
│ Response to Browser      │
│ {success: true, ...}     │
└──────────────────────────┘
       │ 6. JavaScript shows toast
       │    Removes row from table
       │    Loads next pending dupicate
       ▼
┌──────────────────────────┐
│ GUI Updated              │
└──────────────────────────┘
```

### Error Path: Reject Without Reason

```
┌──────────────────────────┐
│ Browser Form Submission  │
│ (reason field empty)     │
└──────┬───────────────────┘
       │
       ▼ CLIENT-SIDE VALIDATION FIRST
┌──────────────────────────┐
│ JavaScript Form Check    │
│ if (reason.trim() === '')│
└──────┬───────────────────┘
       │ Error: Show validation message
       ▼
┌──────────────────────────┐
│ Toast: "Reason required" │
│ Form not submitted       │
└──────────────────────────┘
       │
       │ IF validation passed but backend rejects:
       │
       ▼
┌──────────────────────────┐
│ POST /api/duplicate/42   │
│ /reject with empty reason│
└──────┬───────────────────┘
       │
       ▼ SERVER-SIDE VALIDATION
┌──────────────────────────┐
│ DuplicateReviewService   │
│ ::reject()               │
│ if(!reason) throw error  │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ InvalidReasonException   │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Controller catches       │
│ Returns 400 JSON error   │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Browser shows error      │
│ Allows user to correct   │
└──────────────────────────┘
```

---

## 6. Security Architecture

### Authentication Flow
1. User already authenticated in FA staff area (session exists)
2. AdminReviewController checks session for `user_id` and role
3. If role ≠ "GL Admin" → 403 Forbidden

### Authorization Matrix

| Action | GL Admin | Finance User | Sysadmin | Unauthorized |
|--------|----------|--------------|----------|--------------|
| View Dashboard | ✅ | ✅ | ✅ | ❌ (302 redirect) |
| View Duplicates | ✅ | ✅ | ✅ | ❌ |
| Submit Decision | ✅ | ✅ | ✅ | ❌ |
| View Audit Trail | ✅ | ✅ | ✅ | ❌ |
| Export Audit (future) | ✅ | ✅ | ✅ | ❌ |

### CSRF Protection
- Form POST endpoints require `X-CSRF-Token` header
- Token generated fresh for each session
- Token validated server-side on every submit
- Mismatch → 403 Forbidden

### Input Sanitization
```php
// Before storing in database
$reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
$reason = trim($reason);
if (strlen($reason) > 500) $reason = substr($reason, 0, 500);
// Result: XSS attempt becomes harmless text
```

### Output Escaping
```php
// In view when displaying user input
<div class="reason-text">
  <?= htmlspecialchars($decision->getReason(), ENT_QUOTES, 'UTF-8') ?>
</div>
// Result: Even if sanitization was missed, display-time escaping protects
```

### SQL Injection Prevention
- **All queries use prepared statements** (via repository pattern)
- **No string concatenation** in WHERE clauses
- **Parameter binding** for all user input:
  ```php
  $stmt->bind_param("i", $id);  // INTEGER type hint
  $stmt->bind_param("s", $code); // STRING type hint
  ```

### Audit Logging
```php
// Every decision logged with user context
$auditLog->info('Decision recorded', [
    'transaction_id' => 42,
    'decision' => 'APPROVED',
    'user_id' => $user->getId(),
    'user_email' => $user->getEmail(),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'timestamp' => now('UTC'),
]);
```

---

## 7. Performance Optimization

### Query Optimization

**Main Query (Fetch Pending Duplicates):**
```sql
SELECT 
    dt.id, dt.transaction_code, dt.trans_date, dt.amount,
    dt.counterparty_name, dt.confidence_score, dt.match_reason,
    dt.created_at
FROM bi_transactions_dupe dt
WHERE dt.decision_status = 'PENDING'
  AND dt.created_at >= ? -- date filter
  AND dt.confidence_score >= ? -- confidence filter
ORDER BY dt.created_at ASC
LIMIT ?, 25;

-- Indexes needed:
-- PRIMARY KEY (id)
-- INDEX (decision_status, created_at)
-- INDEX (confidence_score, created_at)
-- INDEX (transaction_code)
-- INDEX (counterparty_name)
-- INDEX (decided_at) [for audit queries]
```

**Verify index usage:**
```sql
EXPLAIN SELECT ... FROM bi_transactions_dupe WHERE decision_status='PENDING' ...
-- Should show: type=range, key=status_created_idx
```

### Pagination Strategy
- **Always paginate**: Max 50 items per page (default 25)
- **Never fetch all**: Prevents OOM errors with large datasets
- **Offset-based**: Simple, works with MySQL 8.0+
- Alternative if dataset grows: Cursor-based pagination (Story 4+)

### Lazy Loading
- **Dashboard table**: 20 lightweight fields only
- **Detail view**: Only fetch on row expand (not all rows at once)
- **Comparison view**: Fetch matching original transaction on demand

### Caching Strategy
- **Read-only data** (counterparty list, confidence configs): Cache 1 hour
- **Transaction list**: No caching (must show real-time pending count)
- **User session**: Standard FA framework session

### Frontend Performance
- **CSS**: Inline critical styles, defer non-critical
- **JS**: Vanilla JS (no build process), lazy-load heavy functions
- **Images**: SVG for icons (no image file downloads)
- **Network**: GZIP compression (server-level)

### Reported Metrics
- **Page Load**: <2s first-time, <500ms subsequent (cached)
- **API Response**: <1s p95 under normal load
- **Database Query**: <200ms p95 with proper indexes
- **Search/Filter**: <500ms to return results

---

## 8. Concurrency & Data Consistency

### Race Condition Scenarios

**Scenario 1: Two Admins Approve Same Transaction Simultaneously**
```
Admin A                          Admin B
GET /api/duplicate/42 (PENDING)
                                 GET /api/duplicate/42 (PENDING)
POST /approve
  UPDATE SET decision_status='APPROVED'
                                 POST /approve
                                   UPDATE SET decision_status='APPROVED' [no error]
```
**Problem**: Both succeed, but audit shows two approvals
**Solution**: Implement optimistic locking with version column
```sql
UPDATE bi_transactions_dupe 
SET decision_status='APPROVED', version = version + 1
WHERE id = 42 AND version = 1
-- If Admin B's version is stale, UPDATE matches 0 rows → detect conflict
```

**Scenario 2: Decision on Transaction Already in Different Status**
```
Admin A                          Story 4 (Posting Service)
                                 SELECT ... WHERE status='PENDING'
                                 [Auto-process pending → POSTED]
POST /api/duplicate/42/approve
  Check: Status is still 'PENDING'? NO
  Return error: "Already processed"
```
**Prevention**: Pre-check state before allowing decisions

### Solution: Database Constraints
```sql
-- Unique constraint prevents duplicate submissions from same user
ALTER TABLE bi_transactions_dupe_audit 
ADD CONSTRAINT uniq_tx_user_timestamp 
UNIQUE (transaction_id, decided_by, decided_at);

-- Ordering by version prevents old writes
ALTER TABLE bi_transactions_dupe
ADD COLUMN version INT DEFAULT 1;

-- Enforce valid state transitions
CHECK (decision_status IN ('PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE'));
```

---

## 9. Accessibility Architecture (WCAG AA)

### HTML Structure
- Semantic markup: `<section>`, `<article>`, `<nav>`, `<main>`
- Proper headings: `<h1>` (page title) → `<h2>` (sections) → `<h3>` (subsections)
- Form labels: `<label for="filter-date">Date:</label> <input id="filter-date">`
- ARIA live regions: `<div role="status" aria-live="polite" aria-atomic="true">`

### Color & Contrast
- **Text**: Minimum 4.5:1 ratio (white #FFF on dark blue #003 = 12.63:1 ✅)
- **UI Components**: Minimum 3:1 ratio
- **Focus indicators**: Minimum 3px solid border, distinct color
- **No color-only messaging**: E.g., red button = error + "Error:" text

### Mobile & Touch
- **Touch targets**: Minimum 44x44px (buttons, links, inputs)
- **Spacing**: Minimum 8px between interactive elements
- **Orientation**: Works in portrait and landscape
- **Text size**: No text smaller than 12px without zoom capability

### Keyboard Navigation
```
Tab ──→ date filter ──→ confidence filter ──→ search box
          ↓
      Enter to apply filters
          ↓
Tab ──→ First table row ──→ Arrow Down to next row
          ↓
      Enter to expand detail
          ↓
Tab ──→ Approve button ──→ Reason field ──→ Submit button
          ↓
      Enter to submit
          ↓
Live region announces: "Decision recorded"
```

### Screen Reader Support
```html
<!-- Table headers -->
<thead>
  <tr>
    <th scope="col">Code</th>
    <th scope="col">Amount</th>
    <th scope="col" aria-sort="ascending">Date</th>
  </tr>
</thead>

<!-- Live region for async updates -->
<div role="status" aria-live="polite" aria-atomic="true" aria-label="Decision updates">
  <!-- Content updated by JS when decision completes -->
</div>

<!-- Button with clear purpose -->
<button type="button" aria-label="Approve as duplicate and move to posting queue">
  Approve
</button>
```

---

## 10. Deployment & Infrastructure

### Environment Configuration
```env
# .env.production
DATABASE_URL=mysql://user:pass@db.prod/ksf_bank
LOG_LEVEL=info
SESSION_TIMEOUT=900 # 15 minutes
FEATURE_FLAGS=admin_review:enabled
```

### Database Migrations
```bash
# Version-controlled migration script
php migrations/001_create_bi_transactions_dupe.php
php migrations/002_add_audit_columns.php
# Rollback capability:
php migrations/rollback --step 1
```

### Deployment Checklist
- [ ] Database schema verified on staging
- [ ] All unit tests pass (PHPUnit)
- [ ] Integration tests pass with real database
- [ ] E2E tests pass on staging
- [ ] Accessibility audit passes (WCAG AA)
- [ ] Load test passes (100 concurrent users)
- [ ] Code review approved
- [ ] Git history clean (conventional commits)
- [ ] Rollback plan documented
- [ ] Monitoring alerts configured

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial architecture from system design |

