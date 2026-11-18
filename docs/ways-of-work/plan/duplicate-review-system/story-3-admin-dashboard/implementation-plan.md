---
title: "Story 3: Admin Review Dashboard - Implementation Plan"
epic: "Duplicate Review System"
feature: "Admin Review UI"
status: "Ready for Implementation"
created: "2026-04-09"
version: "1.0"
---

# Story 3: Admin Review Dashboard - Implementation Plan

## 1. Goal

Implement a user-facing admin review dashboard that allows GL admins to view, filter, compare, and make decisions (approve/reject/investigate) on duplicate transactions. The dashboard will:
- Display pending duplicates with confidence scores and key details
- Provide side-by-side transaction comparison
- Submit decisions via REST API to existing DuplicateReviewService
- Record all decisions with audit trail for compliance
- Work responsively on mobile/tablet/desktop
- Meet WCAG AA accessibility standards
- Achieve ≥80% code coverage

**Completion Target**: 4-5 days (following TDD approach)

---

## 2. Technical Approach

### TDD (Test-Driven Development) Workflow
1. Write failing test cases first
2. Implement minimum code to pass tests (green)
3. Refactor for clarity and performance (blue)
4. Commit with conventional commits

### SRP (Single Responsibility Principle)
Each class has ONE reason to change:
- **Controller**: HTTP routing & response serialization
- **Service**: Business logic & orchestration
- **Repository**: Data access (delegates to existing Story 1 repo)
- **DTO**: Data transfer (immutable, serializable)
- **View**: HTML rendering only

### SOLID Principles Applied
- **S**ingle Responsibility: Each class has one job
- **O**pen/Closed: Open for extension (interfaces), closed for modification
- **L**iskov Substitution: Controllers can inject mocked services
- **I**nterface Segregation: Small, focused interfaces (IAdminReviewService optional)
- **D**ependency Inversion: Inject dependencies, don't instantiate inside

### Package Selection Rationale
- **Bootstrap 5**: Already used in FA admin area, WCAG compliant built-in
- **Vanilla JS**: Keep it simple; no Webpack/build step needed
- **Fetch API**: Modern, <2KB alternative to jQuery
- **PHPUnit 10.x**: Already in composer.lock, works with PHP 8.0+
- **Mockery**: Already used in Story 2 tests

---

## 3. Implementation Phases

### Phase 1: Foundation (Day 1) - Backend Structure

#### Task 1.1: Create DTOs
**File**: `src/Ksfraser/FaBankImport/Import/Services/DTOs/AdminReviewDTOs.php`

```php
// Group related DTOs in one file for clarity

class DuplicateListItemDTO {
    public readonly int $id;
    public readonly string $transaction_code;
    public readonly string $trans_date; // YYYY-MM-DD
    public readonly float $amount;
    public readonly string $counterparty_name;
    public readonly int $confidence_score; // 0-100
    public readonly string $match_reason;
    public readonly string $created_at; // ISO 8601
    
    public function __construct(array $dbRow) { ... }
    public function toArray(): array { ... }
}

class DuplicateComparisonDTO {
    public readonly int $id;
    public readonly array $original_transaction;
    public readonly array $duplicate_transaction;
    public readonly array $confidence_breakdown;
    public readonly string $match_reason;
    
    public function __construct(DuplicateTransaction $tx) { ... }
    public function toArray(): array { ... }
}

class DecisionHistoryDTO {
    public readonly int $id;
    public readonly string $decision; // APPROVED, REJECTED, etc.
    public readonly string $decided_by;
    public readonly string $decided_at;
    public readonly ?string $reason;
    public readonly ?string $notes;
    
    public function __construct(array $auditRow) { ... }
}

class ApproveRequestDTO {
    public readonly string $reason;
    
    public static function fromRequest(array $data): self {
        // Sanitize and validate
        $reason = isset($data['reason']) ? trim((string)$data['reason']) : '';
        $reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        if (strlen($reason) > 500) $reason = substr($reason, 0, 500);
        return new self($reason);
    }
}

class RejectRequestDTO {
    public readonly string $reason;
    
    public static function fromRequest(array $data): self {
        $reason = isset($data['reason']) ? trim((string)$data['reason']) : '';
        if (empty($reason)) throw InvalidReasonException::reasonRequired();
        $reason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        if (strlen($reason) > 500) $reason = substr($reason, 0, 500);
        return new self($reason);
    }
}

class InvestigateRequestDTO {
    public readonly ?string $notes;
    
    public static function fromRequest(array $data): self {
        $notes = isset($data['notes']) ? trim((string)$data['notes']) : null;
        if ($notes) {
            $notes = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');
            if (strlen($notes) > 500) $notes = substr($notes, 0, 500);
        }
        return new self($notes);
    }
}
```

**Test File**: `tests/unit/DTOs/AdminReviewDTOTest.php`
```php
class AdminReviewDTOTest extends TestCase {
    public function test_duplicate_list_item_sanitizes_counterparty() { ... }
    public function test_comparison_dto_builds_from_duplicate_transaction() { ... }
    public function test_approve_request_truncates_reason_at_500_chars() { ... }
    public function test_reject_request_requires_reason() { ... }
    public function test_investigate_request_allows_null_notes() { ... }
    public function test_request_dtos_escape_html_special_chars() { ... }
}
```

**Estimation**: 2 points | **Duration**: 1-2 hours

#### Task 1.2: Create AdminReviewService
**File**: `src/Ksfraser/FaBankImport/Import/Services/Review/AdminReviewService.php`

```php
class AdminReviewService {
    public function __construct(
        private readonly IDuplicateTransactionRepository $repository,
        private readonly DuplicateReviewService $reviewService,
        private readonly ILogger $logger,
    ) {}
    
    /**
     * Search pending duplicates with filters and pagination
     */
    public function searchPendingDuplicates(
        int $limit = 25,
        int $offset = 0,
        array $filters = []
    ): SearchResultDTO {
        // Validation
        $limit = max(1, min(100, $limit)); // Clamp to 1-100
        $offset = max(0, $offset);
        
        // Query with indexes
        $result = $this->repository->findPending(
            limit: $limit + 1, // Fetch one extra to know if more exist
            offset: $offset,
            filters: $filters // date_from, date_to, confidence_min, counterparty, search
        );
        
        $hasMore = count($result['items']) > $limit;
        $items = array_slice($result['items'], 0, $limit);
        
        // Transform to DTOs
        $dtoItems = array_map(
            fn(array $row) => new DuplicateListItemDTO($row),
            $items
        );
        
        return new SearchResultDTO(
            total: $result['total'],
            page: $offset / $limit + 1,
            limit: $limit,
            items: $dtoItems,
            has_more: $hasMore
        );
    }
    
    /**
     * Get comparison view for a single duplicate
     */
    public function getDuplicateComparison(int $id): DuplicateComparisonDTO {
        $tx = $this->repository->findById($id);
        if (!$tx) throw EntityNotFoundException::forTransactionId($id);
        
        return new DuplicateComparisonDTO($tx);
    }
    
    /**
     * Get decision audit trail
     */
    public function getDecisionHistory(int $id): array {
        $history = $this->repository->getAuditHistory($id);
        return array_map(
            fn(array $row) => new DecisionHistoryDTO($row),
            $history
        );
    }
    
    /**
     * Submit approve decision (delegates to ReviewService from Story 2)
     */
    public function approveDuplicate(
        int $transactionId,
        string $decidedBy,
        string $reason
    ): ReviewDecision {
        $tx = $this->repository->findById($transactionId);
        if (!$tx) throw EntityNotFoundException::forTransactionId($transactionId);
        
        $dto = ApproveRequestDTO::fromRequest(['reason' => $reason]);
        
        return $this->reviewService->approve($tx, $decidedBy, $dto->reason);
    }
    
    /**
     * Submit reject decision
     */
    public function rejectDuplicate(
        int $transactionId,
        string $decidedBy,
        string $reason
    ): ReviewDecision {
        $tx = $this->repository->findById($transactionId);
        if (!$tx) throw EntityNotFoundException::forTransactionId($transactionId);
        
        $dto = RejectRequestDTO::fromRequest(['reason' => $reason]);
        
        return $this->reviewService->reject($tx, $decidedBy, $dto->reason);
    }
    
    /**
     * Submit investigate decision
     */
    public function investigateDuplicate(
        int $transactionId,
        string $decidedBy,
        ?string $notes = null
    ): ReviewDecision {
        $tx = $this->repository->findById($transactionId);
        if (!$tx) throw EntityNotFoundException::forTransactionId($transactionId);
        
        $dto = InvestigateRequestDTO::fromRequest(['notes' => $notes]);
        
        return $this->reviewService->investigate($tx, $decidedBy, $dto->notes);
    }
}
```

**Test File**: `tests/unit/Services/AdminReviewServiceTest.php`
```php
class AdminReviewServiceTest extends TestCase {
    // Mocking setup
    private MockObject $repository;
    private MockObject $reviewService;
    private AdminReviewService $service;
    
    protected function setUp(): void {
        $this->repository = $this->createMock(IDuplicateTransactionRepository::class);
        $this->reviewService = $this->createMock(DuplicateReviewService::class);
        $this->service = new AdminReviewService($this->repository, $this->reviewService);
    }
    
    public function test_search_returns_paginated_results() { ... }
    public function test_search_applies_filters_correctly() { ... }
    public function test_search_clamps_limit_to_100() { ... }
    public function test_get_comparison_throws_on_missing_id() { ... }
    public function test_approve_calls_review_service() { ... }
    public function test_reject_requires_reason() { ... }
    public function test_investigate_allows_null_notes() { ... }
    public function test_audit_history_returns_all_decisions() { ... }
}
```

**Estimation**: 3 points | **Duration**: 2-3 hours

#### Task 1.3: Create AdminReviewController
**File**: `src/Ksfraser/FaBankImport/Import/Controllers/AdminReviewController.php`

```php
class AdminReviewController {
    use AuthenticationTrait; // Provided by FA framework
    
    public function __construct(
        private readonly AdminReviewService $adminReviewService,
        private readonly ILogger $logger,
    ) {}
    
    /**
     * GET /admin/review
     * Display the dashboard HTML page
     */
    public function dashboardAction(): void {
        $this->requireAuthentication();
        $this->requireRole('GL_ADMIN', 'FINANCE_MANAGER');
        
        $user = $this->getAuthenticatedUser();
        
        // Render view with empty state; JS will fetch data
        render('admin/duplicate_review/dashboard.php', [
            'user' => $user,
            'csrf_token' => generate_csrf_token(),
        ]);
    }
    
    /**
     * POST /admin/api/duplicates/search
     * Fetch pending duplicates with filters, return JSON
     */
    public function searchDuplicatesAction(): void {
        $this->requireAuthentication();
        $this->requireRole('GL_ADMIN', 'FINANCE_MANAGER');
        $this->requireMethod('POST');
        $this->requireContentType('application/json');
        
        $user = $this->getAuthenticatedUser();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $page = (int)($data['page'] ?? 1);
            $limit = (int)($data['limit'] ?? 25);
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'date_from' => $data['filters']['date_from'] ?? null,
                'date_to' => $data['filters']['date_to'] ?? null,
                'confidence_min' => (int)($data['filters']['confidence_min'] ?? 0),
                'counterparty' => $data['filters']['counterparty'] ?? null,
                'status' => 'PENDING', // Always filter to pending
            ];
            
            $search = $data['search'] ?? null;
            if ($search) {
                $filters['search'] = $search;
            }
            
            $result = $this->adminReviewService->searchPendingDuplicates(
                limit: $limit,
                offset: $offset,
                filters: $filters
            );
            
            $this->logger->info('Search duplicates', [
                'user' => $user->getEmail(),
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters,
                'result_count' => count($result->items),
            ]);
            
            return json_response([
                'success' => true,
                'data' => [
                    'total' => $result->total,
                    'page' => $page,
                    'limit' => $limit,
                    'items' => array_map(fn($dto) => $dto->toArray(), $result->items),
                ],
                'meta' => [
                    'has_more' => $result->has_more,
                ],
            ]);
        } catch (Exception $e) {
            $this->logger->error('Search failed', ['error' => $e->getMessage()]);
            return json_response(['success' => false, 'error' => 'Search failed'], 500);
        }
    }
    
    /**
     * GET /admin/api/duplicate/{id}
     */
    public function getDuplicateDetailAction(int $id): void {
        $this->requireAuthentication();
        $this->requireRole('GL_ADMIN', 'FINANCE_MANAGER');
        $this->requireMethod('GET');
        
        try {
            $comparison = $this->adminReviewService->getDuplicateComparison($id);
            
            return json_response([
                'success' => true,
                'data' => $comparison->toArray(),
            ]);
        } catch (EntityNotFoundException $e) {
            return json_response(['success' => false, 'error' => 'Not found'], 404);
        } catch (Exception $e) {
            $this->logger->error('Failed to get detail', ['id' => $id, 'error' => $e->getMessage()]);
            return json_response(['success' => false, 'error' => 'Failed'], 500);
        }
    }
    
    /**
     * POST /admin/api/duplicate/{id}/approve
     */
    public function approveAction(int $id): void {
        $this->requireAuthentication();
        $this->requireRole('GL_ADMIN', 'FINANCE_MANAGER');
        $this->requireMethod('POST');
        $this->validateCSRFToken();
        
        $user = $this->getAuthenticatedUser();
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $reason = $data['reason'] ?? '';
            
            $decision = $this->adminReviewService->approveDuplicate(
                transactionId: $id,
                decidedBy: $user->getEmail(),
                reason: $reason
            );
            
            $this->logger->info('Duplicate approved', [
                'tx_id' => $id,
                'user' => $user->getEmail(),
                'reason' => substr($reason, 0, 50), // Log first 50 chars
            ]);
            
            return json_response([
                'success' => true,
                'message' => sprintf('Decision recorded by %s at %s', $user->getEmail(), now('UTC')),
                'data' => $decision->toArray(),
            ]);
        } catch (InvalidWorkflowTransitionException $e) {
            $this->logger->warn('Workflow error', ['id' => $id, 'error' => $e->getMessage()]);
            return json_response(['success' => false, 'error' => $e->getMessage()], 409);
        } catch (Exception $e) {
            $this->logger->error('Approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            return json_response(['success' => false, 'error' => 'Failed to approve'], 500);
        }
    }
    
    /**
     * POST /admin/api/duplicate/{id}/reject
     */
    public function rejectAction(int $id): void {
        // Similar to approve but calls rejectDuplicate
        // Requires 'reason' field (validation in DTO)
    }
    
    /**
     * POST /admin/api/duplicate/{id}/investigate
     */
    public function investigateAction(int $id): void {
        // Similar to approve but calls investigateDuplicate
        // 'notes' field optional
    }
    
    /**
     * GET /admin/api/duplicate/{id}/history
     */
    public function getHistoryAction(int $id): void {
        $this->requireAuthentication();
        $this->requireRole('GL_ADMIN', 'FINANCE_MANAGER');
        
        try {
            $history = $this->adminReviewService->getDecisionHistory($id);
            
            return json_response([
                'success' => true,
                'data' => array_map(fn($dto) => $dto->toArray(), $history),
            ]);
        } catch (EntityNotFoundException) {
            return json_response(['success' => false, 'error' => 'Not found'], 404);
        }
    }
}
```

**Test File**: `tests/unit/Controllers/AdminReviewControllerTest.php`
```php
class AdminReviewControllerTest extends TestCase {
    private MockObject $adminReviewService;
    private MockObject $logger;
    private AdminReviewController $controller;
    private MockObject $request;
    private MockObject $user;
    
    protected function setUp(): void {
        $this->adminReviewService = $this->createMock(AdminReviewService::class);
        $this->logger = $this->createMock(ILogger::class);
        $this->controller = new AdminReviewController($this->adminReviewService, $this->logger);
        
        // Mock authenticated user
        $this->user = $this->createMock(User::class);
        $this->user->method('getEmail')->willReturn('admin@company.test');
        $this->user->method('getRole')->willReturn('GL_ADMIN');
    }
    
    public function test_dashboard_requires_authentication() { ... }
    public function test_dashboard_requires_gl_admin_role() { ... }
    public function test_dashboard_renders_html() { ... }
    
    public function test_search_returns_json_with_results() { ... }
    public function test_search_applies_pagination_correctly() { ... }
    public function test_search_filters_by_date_range() { ... }
    public function test_search_clamps_limit() { ... }
    public function test_search_logs_operation() { ... }
    
    public function test_get_detail_returns_comparison_dto() { ... }
    public function test_get_detail_returns_404_for_missing_id() { ... }
    
    public function test_approve_calls_service() { ... }
    public function test_approve_requires_csrf_token() { ... }
    public function test_approve_handles_workflow_error() { ... }
    public function test_approve_logs_decision() { ... }
    
    // ... similar for reject, investigate, history
}
```

**Estimation**: 3 points | **Duration**: 2.5-3 hours

---

### Phase 2: Integration Tests (Day 2)

#### Task 2.1: Integration Tests - API Contracts
**File**: `tests/integration/AdminReviewAPITest.php`

```php
class AdminReviewAPITest extends TestCase {
    private string $api_base = '/admin/api';
    
    public function test_fetch_pending_returns_correct_schema() {
        // Requires database with real data
        $response = $this->post("$this->api_base/duplicates/search", [
            'page' => 1,
            'limit' => 10,
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('total', $body['data']);
        $this->assertArrayHasKey('items', $body['data']);
        
        // Validate item schema
        foreach ($body['data']['items'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('transaction_code', $item);
            $this->assertArrayHasKey('confidence_score', $item);
            // ... etc
        }
    }
    
    public function test_fetch_pending_with_filters() { ... }
    
    public function test_approve_decision_records_in_database() {
        $response = $this->post("$this->api_base/duplicate/42/approve", [
            'reason' => 'Test approval',
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify database was updated
        $tx = $this->db->query('SELECT * FROM bi_transactions_dupe WHERE id=42')->fetch();
        $this->assertEquals('APPROVED', $tx['decision_status']);
        $this->assertEquals('test@company.test', $tx['decided_by']);
        $this->assertNotNull($tx['decided_at']);
    }
    
    public function test_concurrent_decisions_use_optimistic_locking() { ... }
    
    public function test_idempotent_submission() { ... }
}
```

**Estimation**: 3 points | **Duration**: 2-3 hours

#### Task 2.2: E2E Smoke Tests
**File**: `tests/e2e/AdminReviewWorkflows.spec.php`

Using Playwright/Selenium:
```php
class AdminReviewE2ETest extends TestCase {
    private Browser $browser;
    
    public function test_approve_workflow_end_to_end() {
        // 1. Login
        $this->browser->goto('/admin/review');
        $this->browser->fill('input[name="email"]', 'admin@company.test');
        $this->browser->fill('input[name="password"]', 'password123');
        $this->browser->click('button:contains("Login")');
        
        // 2. Search and filter
        $this->browser->click('button:contains("Filters")');
        $this->browser->selectOption('select[name="confidence"]', '75');
        $this->browser->click('button:contains("Apply")');
        
        // 3. Expand detail view
        $this->browser->click('tr:nth-child(1) td:first-child');
        $this->browser->waitForSelector('.comparison-view');
        
        // 4. Approve
        $this->browser->click('button:contains("Approve")');
        
        // 5. Verify success message
        $toast = $this->browser->waitForSelector('.toast-success');
        $this->assertStringContainsString('Decision recorded', $toast->textContent());
        
        // 6. Verify row removed
        $this->browser->waitForFunction(fn() => 
            $this->browser->querySelectorAll('tbody tr')->count() === 49
        );
    }
    
    public function test_mobile_viewport_usable() { ... }
}
```

**Estimation**: 2 points | **Duration**: 1.5-2 hours

---

### Phase 3: Frontend Implementation (Day 2-3)

#### Task 3.1: Create View & Styling
**File**: `views/admin/duplicate_review/dashboard.php`

```php
<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Duplicates - Admin</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/admin/duplicate_review.css">
</head>
<body>
<main class="admin-container">
    <div class="dashboard-header">
        <h1>Duplicate Transaction Review</h1>
        <p class="lead">Review and approve/reject duplicate matches before posting to GL</p>
    </div>
    
    <div class="dashboard-layout">
        <!-- Sidebar: Filters -->
        <aside class="filters-panel">
            <h2>Filters</h2>
            <form id="filters-form" class="filters-form">
                <div class="form-group mb-3">
                    <label for="filter-date-from">From Date</label>
                    <input type="date" id="filter-date-from" name="date_from" class="form-control">
                </div>
                
                <div class="form-group mb-3">
                    <label for="filter-date-to">To Date</label>
                    <input type="date" id="filter-date-to" name="date_to" class="form-control">
                </div>
                
                <div class="form-group mb-3">
                    <label for="filter-confidence">Minimum Confidence</label>
                    <select id="filter-confidence" name="confidence_min" class="form-select">
                        <option value="">All</option>
                        <option value="50">50%+</option>
                        <option value="75">75%+</option>
                        <option value="90">90%+</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label for="filter-counterparty">Counterparty</label>
                    <input type="text" id="filter-counterparty" name="counterparty" class="form-control" placeholder="Search...">
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <button type="reset" class="btn btn-secondary w-100 mt-2">Clear</button>
                </div>
            </form>
        </aside>
        
        <!-- Main: Table & Detail View -->
        <section class="main-content">
            <div class="search-bar mb-3">
                <input type="text" id="search-box" class="form-control" placeholder="Search by code or counterparty..." aria-label="Search">
            </div>
            
            <div id="loading-spinner" class="spinner-border" role="status" style="display:none;">
                <span class="visually-hidden">Loading...</span>
            </div>
            
            <div id="duplicates-table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Date</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Counterparty</th>
                            <th scope="col">Confidence</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="duplicates-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            
            <nav class="mt-3">
                <ul class="pagination" id="pagination">
                    <!-- Populated by JS -->
                </ul>
            </nav>
            
            <!-- Detail View (Expandable) -->
            <div id="detail-modal" class="modal fade" tabindex="-1" aria-labelledby="detailLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailLabel">Duplicate Comparison</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="detail-content">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Decision Modal -->
    <div id="decision-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <!-- Form for decision submission -->
    </div>
    
    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3">
        <!-- Populated by JS -->
    </div>
</main>

<script src="/assets/js/admin_review.js" defer></script>
</body>
</html>
```

**CSS File**: `views/admin/duplicate_review/css/styles.css`

```css
/* Dashboard Layout */
.dashboard-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.filters-panel {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    height: fit-content;
    position: sticky;
    top: 80px;
}

.main-content {
    min-height: 600px;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-layout {
        grid-template-columns: 1fr;
    }
    
    .filters-panel {
        position: static;
        margin-bottom: 2rem;
    }
}

/* Confidence Score Visual Indicator */
.confidence-badge {
    display: inline-block;
    padding: 0.25em 0.75em;
    border-radius: 12px;
    font-weight: 500;
    min-width: 60px;
    text-align: center;
}

.confidence-90 { background-color: #d4edda; color: #155724; }
.confidence-75 { background-color: #fff3cd; color: #856404; }
.confidence-50 { background-color: #f8d7da; color: #721c24; }

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-approve { background-color: #28a745; }
.btn-reject  { background-color: #dc3545; }
.btn-investigate { background-color: #ffc107; color: #000; }

/* Touch-friendly on mobile */
@media (max-width: 576px) {
    .btn {
        padding: 0.75rem 1rem;
        min-height: 44px;
        min-width: 44px;
    }
}

/* Accessibility: Focus indicators */
button:focus, input:focus, select:focus {
    outline: 3px solid #0c5ef8 !important;
    outline-offset: 2px;
}

/* Comparison Detail View */
.transaction-comparison {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.transaction-panel {
    border: 1px solid #dee2e6;
    padding: 1rem;
    border-radius: 4px;
}

.transaction-panel h3 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.transaction-field {
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.transaction-field label {
    font-weight: 500;
    color: #666;
    display: block;
    margin-bottom: 0.25rem;
}

.transaction-field-value {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

/* Matching field highlight */
.field-match { background-color: #d4edda !important; }
.field-diff  { background-color: #fff3cd !important; }

/* Loading spinner */
.spinner-border {
    width: 3rem;
    height: 3rem;
    margin: 2rem auto;
}

/* Toast notifications */
.toast {
    min-width: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.toast-success { border-left: 4px solid #28a745; }
.toast-error   { border-left: 4px solid #dc3545; }
.toast-warning { border-left: 4px solid #ffc107; }
```

**Estimation**: 3 points | **Duration**: 2-3 hours

#### Task 3.2: Frontend JavaScript
**File**: `views/admin/duplicate_review/js/scripts.js`

```javascript
class AdminReviewDashboard {
    constructor() {
        this.baseUrl = '/admin/api';
        this.currentPage = 1;
        this.limit = 25;
        this.filters = {};
        this.setupEventListeners();
        this.loadDuplicates();
    }
    
    setupEventListeners() {
        // Filter form
        document.getElementById('filters-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.currentPage = 1;
            this.collectFilters();
            this.loadDuplicates();
        });
        
        // Search box (debounced)
        const searchBox = document.getElementById('search-box');
        let timeout;
        searchBox.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.currentPage = 1;
                this.collectFilters();
                this.loadDuplicates();
            }, 300);
        });
        
        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                const page = parseInt(e.target.dataset.page);
                if (!isNaN(page)) {
                    this.currentPage = page;
                    this.loadDuplicates();
                }
            }
        });
        
        // Row click to expand
        document.addEventListener('click', (e) => {
            const row = e.target.closest('tbody tr');
            if (row) {
                const id = row.dataset.id;
                this.showDetailView(id);
            }
        });
    }
    
    collectFilters() {
        const form = document.getElementById('filters-form');
        this.filters = {
            date_from: form.date_from.value || null,
            date_to: form.date_to.value || null,
            confidence_min: parseInt(form.confidence_min.value) || 0,
            counterparty: form.counterparty.value || null,
        };
        
        // Add search
        const search = document.getElementById('search-box').value;
        if (search) this.filters.search = search;
    }
    
    async loadDuplicates() {
        this.showSpinner(true);
        
        try {
            const response = await fetch(`${this.baseUrl}/duplicates/search`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    page: this.currentPage,
                    limit: this.limit,
                    filters: this.filters,
                }),
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const json = await response.json();
            if (!json.success) throw new Error(json.error || 'Unknown error');
            
            this.renderTable(json.data.items);
            this.renderPagination(json.data.total, json.data.page, json.data.limit);
        } catch (error) {
            this.showToast(`Error loading duplicates: ${error.message}`, 'error');
            console.error('loadDuplicates error:', error);
        } finally {
            this.showSpinner(false);
        }
    }
    
    renderTable(items) {
        const tbody = document.getElementById('duplicates-tbody');
        tbody.innerHTML = '';
        
        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.dataset.id = item.id;
            tr.innerHTML = `
                <td><strong>${this.escape(item.transaction_code)}</strong></td>
                <td>${item.trans_date}</td>
                <td>${this.formatCurrency(item.amount)}</td>
                <td>${this.escape(item.counterparty_name)}</td>
                <td>
                    <span class="confidence-badge confidence-${this.getConfidenceBandclass(item.confidence_score)}">
                        ${item.confidence_score}%
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" aria-label="View details">View</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Announce update to screen readers
        this.announceToScreenReader(`Loaded ${items.length} duplicates`);
    }
    
    renderPagination(total, page, limit) {
        const totalPages = Math.ceil(total / limit);
        const container = document.getElementById('pagination');
        container.innerHTML = '';
        
        // Previous button
        if (page > 1) {
            container.innerHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${page - 1}">Previous</a></li>`;
        }
        
        // Page numbers
        for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++) {
            const active = i === page ? ' active' : '';
            container.innerHTML += `<li class="page-item${active}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        
        // Next button
        if (page < totalPages) {
            container.innerHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${page + 1}">Next</a></li>`;
        }
    }
    
    async showDetailView(id) {
        try {
            const response = await fetch(`${this.baseUrl}/duplicate/${id}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const json = await response.json();
            if (!json.success) throw new Error(json.error);
            
            const data = json.data;
            
            // Render comparison view with decision form
            const detailContent = document.getElementById('detail-content');
            detailContent.innerHTML = this.renderComparison(data);
            
            // Show modal
            new bootstrap.Modal(document.getElementById('detail-modal')).show();
            
            // Attach decision button handlers
            document.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', () => this.submitDecision(id, btn.dataset.action));
            });
        } catch (error) {
            this.showToast(`Error loading detail: ${error.message}`, 'error');
        }
    }
    
    renderComparison(data) {
        const original = data.original_transaction;
        const duplicate = data.duplicate_transaction;
        
        return `
            <div class="transaction-comparison">
                <div class="transaction-panel">
                    <h3>Original</h3>
                    ${this.renderTransactionFields(original)}
                </div>
                
                <div class="transaction-panel">
                    <h3>Duplicate Match</h3>
                    ${this.renderTransactionFields(duplicate)}
                </div>
            </div>
            
            <div class="mt-4 mb-4">
                <h4>Confidence Breakdown</h4>
                <ul>
                    ${Object.entries(data.confidence_breakdown).map(([key, value]) => 
                        `<li>${key}: ${value}%</li>`
                    ).join('')}
                </ul>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-approve" data-action="approve" aria-label="Approve as duplicate">Approve</button>
                <button class="btn btn-reject" data-action="reject" aria-label="Reject as false positive">Reject</button>
                <button class="btn btn-investigate" data-action="investigate" aria-label="Mark for investigation">Investigate</button>
            </div>
        `;
    }
    
    renderTransactionFields(tx) {
        return Object.entries(tx).map(([key, value]) => `
            <div class="transaction-field">
                <label>${this.humanize(key)}</label>
                <div class="transaction-field-value">${this.escape(String(value))}</div>
            </div>
        `).join('');
    }
    
    async submitDecision(id, action) {
        let reason = '';
        
        if (action === 'reject') {
            reason = prompt('Please provide a reason for rejection:', '');
            if (reason === null) return; // User canceled
            if (!reason.trim()) {
                this.showToast('Reason is required for rejection', 'warning');
                return;
            }
        } else if (action === 'investigate') {
            reason = prompt('Optional: notes for investigation:', '');
        }
        
        try {
            const endpoint = `${this.baseUrl}/duplicate/${id}/${action}`;
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reason: reason || '' }),
            });
            
            if (response.status === 409) {
                this.showToast('This transaction has already been decided', 'warning');
                return;
            }
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const json = await response.json();
            if (!json.success) throw new Error(json.error);
            
            this.showToast(json.message || `Decision saved (${action})`, 'success');
            
            // Close modal, reload table
            bootstrap.Modal.getInstance(document.getElementById('detail-modal')).hide();
            this.loadDuplicates();
        } catch (error) {
            this.showToast(`Error saving decision: ${error.message}`, 'error');
        }
    }
    
    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.role = 'alert';
        toast.innerHTML = `
            <div class="toast-body">
                ${this.escape(message)}
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        container.appendChild(toast);
        new bootstrap.Toast(toast).show();
        
        // Auto-remove after 5 seconds
        setTimeout(() => toast.remove(), 5000);
    }
    
    announceToScreenReader(message) {
        // Create temporary aria-live region for announcement
        const region = document.createElement('div');
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'true');
        region.className = 'visually-hidden';
        region.textContent = message;
        document.body.appendChild(region);
        setTimeout(() => region.remove(), 1000);
    }
    
    showSpinner(show) {
        document.getElementById('loading-spinner').style.display = show ? 'block' : 'none';
    }
    
    // Utility methods
    escape(str) { const div = document.createElement('div'); div.textContent = str; return div.innerHTML; }
    formatCurrency(num) { return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num); }
    humanize(str) { return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()); }
    getConfidenceBandclass(score) { return score >= 90 ? '90' : score >= 75 ? '75' : '50'; }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => new AdminReviewDashboard());
```

**Estimation**: 4 points | **Duration**: 3-4 hours

---

### Phase 4: Testing & Quality (Day 4)

#### Task 4.1: Run Full Test Suite
- Unit tests: PHPUnit (6 test files, 40+ tests)
- Integration tests: API contracts (8+ tests)
- E2E smoke tests: Playwright (4+ scenarios)
- Accessibility audit: axe DevTools (automated + manual)
- Performance test: Load test with 100 concurrent users

**Estimation**: 2 points | **Duration**: 1.5-2 hours

#### Task 4.2: Code Review & Cleanup
- Review for SRP/SOLID compliance
- Check for missed edge cases
- Verify all error paths handled
- Ensure consistent code style
- Update documentation

**Estimation**: 1 point | **Duration**: 1 hour

---

## 4. Dependencies & Blockers

### Hard Dependencies (Must Be Complete)
- ✅ Story 1: `bi_transactions_dupe` table  ← **COMPLETE**
- ✅ Story 2: `DuplicateReviewService` ← **COMPLETE** (just committed)
- ✅ Story 2: `DuplicateDecisionMade` event ← **COMPLETE**

### Soft Dependencies (Nice-to-Have)
- FA framework authentication system (already exists)
- Bootstrap 5 CSS framework (already available)
- PHPUnit + Mockery (already in composer.lock)

### No Blockers - Ready to Start NOW! 🎯

---

## 5. Daily Standup Template

### Day 1 (Backend Foundation)
- [ ] Complete Task 1.1: DTOs
- [ ] Complete Task 1.2: AdminReviewService
- [ ] Complete Task 1.3: AdminReviewController
- Estimated unit test coverage: 85%

### Day 2 (Integration & Frontend View)
- [ ] Complete Task 2.1: Integration tests
- [ ] Complete Task 2.2: E2E smoke tests
- [ ] Complete Task 3.1: View & CSS
- [ ] Start Task 3.2: JavaScript

### Day 3 (Frontend Completion)
- [ ] Complete Task 3.2: JavaScript
- [ ] Run full test suite
- [ ] Debug/fix test failures
- [ ] Code review

### Day 4 (QA & Deployment)
- [ ] Accessibility audit
- [ ] Performance testing
- [ ] UAT with Finance lead
- [ ] Final deployment checklist

---

## 6. Code Review Checklist

### Functionality
- [ ] All 12+ acceptance criteria passing
- [ ] All unit tests passing (≥80% coverage)
- [ ] All integration tests passing
- [ ] E2E workflows working end-to-end

### Code Quality
- [ ] Single Responsibility applied (each class has one reason to change)
- [ ] No duplicate code (DRY principle)
- [ ] Method length <30 lines (readability)
- [ ] Cyclomatic complexity <5 per method
- [ ] All methods documented with @return/@param

### Security
- [ ] All user input sanitized (HTML-escaped)
- [ ] All queries parameterized (no SQL injection)
- [ ] CSRF token on all POST endpoints
- [ ] Authentication/authorization enforced
- [ ] No sensitive data in URLs or logs

### Performance
- [ ] Database queries optimized (using indexes)
- [ ] Pagination implemented (no unbounded result sets)
- [ ] JavaScript bundle size <50KB
- [ ] Page load <2s, API responses <1s

### Accessibility
- [ ] WCAG AA audit passes
- [ ] All form inputs labeled
- [ ] Keyboard navigation works
- [ ] Screen reader tested
- [ ] Focus indicators visible

### Documentation
- [ ] Inline comments for complex logic
- [ ] README updated with usage
- [ ] API documentation complete
- [ ] Conventional commits used

---

## 7. Rollback Plan

If critical issues discovered in production:

**Immediate** (0-5 min):
1. Disable routes: Remove `/admin/review` route from router
2. Redirect: `/admin/review` → `/admin/home` with warning message

**Short-Term** (5-30 min):
1. Revert database schema to prior state (run rollback migration)
2. Revert code changes (git revert to previous commit)
3. Notify Finance team of temporary unavailability

**Root Cause Analysis**:
1. Review error logs from incident time
2. Run regression test suite to identify issue
3. Fix on develop branch with tests

**Re-Deployment**:
1. Thorough testing on staging
2. Feature toggle: Disable via .env flag if issue suspected
3. Gradual rollout to 10% of users first

---

## 8. Success Criteria Summary

### By End of Day 4, Story 3 is COMPLETE if:

✅ **Functionality**
- Dashboard loads all pending duplicates
- Filters work correctly (date, confidence, counterparty)
- Search functionality works
- Side-by-side comparison displays correctly
- Decisions (approve/reject/investigate) submit Successfully
- Audit trail visible
- Mobile view is fully functional

✅ **Quality**
- Unit test coverage ≥80%
- All integration tests passing
- E2E smoke tests passing
- WCAG AA accessibility audit passing
- Load test: 100 concurrent users with acceptable performance

✅ **Code**
- SRP/SOLID principles applied
- Conventional commits used
- Code reviewed and approved
- No high-severity security vulnerabilities
- All error paths handled

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial implementation plan |

