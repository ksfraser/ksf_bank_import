# Context Map: REST API Endpoints for Contact Management Workflow

**Document Date**: March 23, 2026  
**Scope**: Before building API endpoints for contact search, linking, creation, history, and transaction completion  
**Thorough Level**: Medium (API structure focus)

---

## 1. EXISTING HTTP REQUEST/ROUTING ARCHITECTURE

### Current Framework
- **HTTP Foundation**: Symfony `HttpFoundation` component (Request/Response abstraction)
- **Request Handler**: [src/Ksfraser/FaBankImport/http/RequestHandler.php](src/Ksfraser/FaBankImport/http/RequestHandler.php)
  - Wraps `Symfony\Component\HttpFoundation\Request::createFromGlobals()`
  - Extracts POST parameters and query strings
  - Supports middleware pipeline for request processing
  
- **Response Handler**: [src/Ksfraser/FaBankImport/http/ResponseHandler.php](src/Ksfraser/FaBankImport/http/ResponseHandler.php)
  - Uses `Symfony\Component\HttpFoundation\JsonResponse` for JSON responses
  - Method: `public function json(array $data): void` (automatically sends + exits)
  - Supports header setting, status codes, redirects

### Current Request Patterns
1. **Form Submissions** (legacy)
   - POST actions via `process_statements.php` (lines 100-130)
   - Action detection: `isset($_POST['ActionName'])`
   - Examples: `UnsetTrans`, `AddCustomer`, `AddVendor`, `ToggleTransaction`

2. **Command Pattern** (recent refactoring)
   - [src/Ksfraser/FaBankImport/command_bootstrap.php](src/Ksfraser/FaBankImport/command_bootstrap.php)
   - Dispatcher-based: `CommandDispatcher->dispatch($action, $postData)`
   - Returns `TransactionResult` object with display() method
   - Feature flag: `USE_COMMAND_PATTERN` (config.php)

3. **Middleware Pipeline**
   - [src/Ksfraser/FaBankImport/middleware/](src/Ksfraser/FaBankImport/middleware/)
   - Auth middleware & Admin middleware in place
   - Pipeline supports: request → middleware chain → action → response

---

## 2. EXISTING API/AJAX ENDPOINTS

### Current Status
**❌ No REST API endpoints exist** in `/api/*` routes

### What Exists Instead
- **Admin Routes** (View-based, not REST): [src/Ksfraser/FaBankImport/config/admin_routes.php](src/Ksfraser/FaBankImport/config/admin_routes.php)
  - Pattern: `'admin/action' => ['controller' => 'X', 'action' => 'Y', 'middleware' => [...]]`
  - Renders HTML views, not JSON responses
  - Examples: `admin/dashboard`, `admin/performance`, `admin/metrics/export`

- **AJAX Handlers** (Legacy): [class.bank_import_controller.php](class.bank_import_controller.php)
  - Uses FrontAccounting `$Ajax` class
  - Activation pattern: `$Ajax->activate('doc_tbl')` (refreshes HTML tables)
  - Not REST API; tightly coupled to HTML view rendering

### Ajax Object Usage
- Located in: FrontAccounting framework (external)
- Purpose: Manage JavaScript/AJAX DOM updates
- Current calls: `$Ajax->activate('doc_tbl')` (line 224, 769, 1037)
- **This is NOT a REST API** - it's view-layer AJAX

---

## 3. EXISTING CONTROLLERS & FRAMEWORK

### Controller Architecture
**Base Class**: [src/Ksfraser/FaBankImport/controllers/AbstractController.php](src/Ksfraser/FaBankImport/controllers/AbstractController.php)

```php
abstract class AbstractController
{
    protected $request;        // RequestHandler
    protected $response;       // ResponseHandler
    protected $pipeline;       // MiddlewarePipeline
    
    protected function render(string $view, array $data = []): void
    protected function json(array $data): void           // ← USE THIS FOR API
    protected function redirect(string $url): void
    public function handle(string $action, array $params = []): void
}
```

### Existing Controllers (Inheriting from AbstractController)
1. **BankImportController** ([src/Ksfraser/FaBankImport/controllers/BankImportController.php](src/Ksfraser/FaBankImport/controllers/BankImportController.php))
   - Methods: `index()`, `processTransaction(int, string)`
   - Middleware: AuthMiddleware
   
2. **BiLineItemController** ([src/Ksfraser/FaBankImport/controllers/BiLineItemController.php](src/Ksfraser/FaBankImport/controllers/BiLineItemController.php))
   - Purpose: Handle line item operations
   
3. **AdminController** ([src/Ksfraser/FaBankImport/controllers/AdminController.php](src/Ksfraser/FaBankImport/controllers/AdminController.php))
   - Dashboard, performance reports, metrics export
   - Methods return HTML via `$this->render()` or JSON via `$this->json()`
   - Sample: `public function anomalyReport(): void` checks `$this->request->getQuery('format')` for 'json'

### Legacy Controller (Not MVC)
**[class.bank_import_controller.php](class.bank_import_controller.php)** (Deprecated)
- Extends `origin` (FrontAccounting base class)
- Methods: `unsetTrans()`, `addCustomer()`, `addVendor()`, `toggleDebitCredit()`, etc.
- Direct `$_POST` access within methods (testability issue)
- Mixed concerns: business logic + view manipulation
- **Status**: Being replaced by Command Pattern + MVC

---

## 4. BANK_IMPORT_CONTROLLER.PHP STRUCTURE

### Legacy Class Analysis
**File**: [class.bank_import_controller.php](class.bank_import_controller.php)

**Key Issues**:
- Lines 1-30: Constructor detects POST actions but doesn't dispatch
- Lines 100-130 (process_statements.php): Manual POST checks for actions
- Mixes FrontAccounting lifecycle with custom bank import logic
- Direct `$_POST` access: NOT testable, NOT API-friendly

**What to Leave Behind**:
- Direct `$_POST` access
- FrontAccounting-specific `$Ajax` manipulations
- God object anti-pattern (controller doing too much)

**What to Leverage**:
- Repository pattern: `$this->repository = new bi_transaction()`
- Domain models: `bi_transaction`, `bi_lineitem`, `bi_transaction`
- Existing business logic: transaction processing, customer/vendor creation

---

## 5. EXISTING API ENDPOINT PATTERNS

### Pattern: Admin Route Configuration
**File**: [src/Ksfraser/FaBankImport/config/admin_routes.php](src/Ksfraser/FaBankImport/config/admin_routes.php)

```php
return [
    'admin/dashboard' => [
        'controller' => 'AdminController',
        'action' => 'dashboard',
        'middleware' => ['auth', 'admin']
    ],
    // Uses controller methods that can return HTML or JSON
];
```

### Pattern: Controller JSON Response Method
**Pattern from AdminController**:
```php
protected function json(array $data): void
{
    $this->response->json($data);  // Uses ResponseHandler
}

// Example usage in action method:
public function anomalyReport(): void
{
    $anomalies = $this->aggregator->detectPerformanceAnomalies(...);
    
    if ($this->request->getQuery('format') === 'json') {
        $this->json(['anomalies' => $anomalies]);  // Auto-sends + exits
        return;
    }
    
    $this->render('admin/anomaly_report', [...]);
}
```

### Pattern: Request Parameter Access
```php
// Via AbstractController's protected $request (RequestHandler)
$this->request->getQuery('param_name')      // GET params
$this->request->request->get('param_name')   // POST params
$this->request->getRequest()->getContent()    // Raw body (JSON)
```

---

## 6. RECOMMENDED LOCATION FOR NEW REST ENDPOINTS

### Recommendation: **Separate API Route File**

**Create**: `src/Ksfraser/FaBankImport/config/api_routes.php`

```php
return [
    'api/v1/contacts' => [
        'methods' => ['GET', 'POST'],
        'controller' => 'ContactController',
        'action' => 'index',
        'middleware' => ['auth']
    ],
    'api/v1/contacts/search' => [
        'methods' => ['GET'],
        'controller' => 'ContactController',
        'action' => 'search',
        'middleware' => ['auth']
    ],
    'api/v1/contacts/{id}' => [
        'methods' => ['GET', 'PUT', 'DELETE'],
        'controller' => 'ContactController',
        'action' => 'show',  // or determine via HTTP method
        'middleware' => ['auth']
    ],
    // ... more endpoints
];
```

### Rationale
1. ✅ Matches existing route architecture
2. ✅ Separates API routes from admin/view routes
3. ✅ Clean version namespace (`/api/v1/`)
4. ✅ Enables future v2 without breaking v1
5. ✅ Middleware can be applied per-route or globally
6. ✅ Easy to scan all endpoints in one file

### Alternative: New API Controllers Directory
**Create**: `src/Ksfraser/FaBankImport/controllers/Api/ContactController.php`

- Extends `AbstractController`
- Only implements `json()` responses, never `render()`
- Namespace: `Ksfraser\FaBankImport\Controllers\Api`

---

## 7. JSON RESPONSE STRUCTURE

### Current JSON Pattern (from ResponseHandler)
```php
// In controller:
$this->json(['anomalies' => $anomalies]);

// Sends:
{
    "anomalies": [...],
}
```

### Recommended API Response Structure

**Success Response** (HTTP 200):
```json
{
    "success": true,
    "data": {
        "id": 123,
        "firstName": "John",
        ...
    },
    "message": "Contact created successfully"
}
```

**Error Response** (HTTP 400/401/403/404/500):
```json
{
    "success": false,
    "data": null,
    "message": "Validation failed",
    "errors": {
        "email": "Invalid email format"
    }
}
```

**List Response** (HTTP 200):
```json
{
    "success": true,
    "data": [
        { "id": 1, "firstName": "John", ... },
        { "id": 2, "firstName": "Jane", ... }
    ],
    "pagination": {
        "page": 1,
        "pageSize": 20,
        "total": 150,
        "pages": 8
    }
}
```

### Implementation (Suggested Helper in AbstractController)
```php
protected function apiResponse($data, $message = '', $statusCode = 200): void
{
    $this->response->setStatusCode($statusCode);
    $this->json([
        'success' => ($statusCode < 400),
        'data' => $data,
        'message' => $message
    ]);
}

protected function apiError($message, $statusCode = 400, $errors = []): void
{
    $this->response->setStatusCode($statusCode);
    $this->json([
        'success' => false,
        'data' => null,
        'message' => $message,
        'errors' => $errors
    ]);
}
```

---

## 8. AUTHENTICATION/AUTHORIZATION PATTERNS

### Current Auth Middleware
**File**: [src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php](src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php)

```php
class AuthMiddleware
{
    private $response;
    
    public function __construct()
    {
        $this->response = new ResponseHandler();
    }
    
    public function process($request, $next)
    {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            $this->response->setStatusCode(401);
            // Returns 401 Unauthorized
        }
        
        return $next($request);
    }
}
```

### Admin Authorization Middleware
**File**: [src/Ksfraser/FaBankImport/middleware/AdminMiddleware.php](src/Ksfraser/FaBankImport/middleware/AdminMiddleware.php)

- Checks `$request->hasRole('admin')`
- Returns 403 Forbidden if not authorized

### Session-Based Authentication
- Uses FrontAccounting session management
- Accessed via `$_SESSION` (legacy) or dependency injection
- Check: `check_user_access($permission)` (FrontAccounting function)

### Recommended Auth Flow for REST APIs
```php
// In AuthMiddleware::process()
1. Check for Bearer token in Authorization header
2. Validate JWT signature (or session token)
3. Load user context into request
4. Pass to next middleware or action
```

**Example Headers Expected**:
```
GET /api/v1/contacts HTTP/1.1
Authorization: Bearer eyJhbGc...
Content-Type: application/json
```

---

## 9. CONTACT DTO & DATA STRUCTURES

### ContactData DTO (External Package)
**Package**: `ksfraser/contact-dto` v0.1.0 (in composer.json)  
**Namespace**: `Ksfraser\Contact\DTO\ContactData`  
**Status**: Declared dependency but NOT actively used in code

### ContactData Properties (40+)
- `id`, `createdAt`, `updatedAt`
- `firstName`, `middleName`, `lastName`, `displayName`
- `email`, `phoneNumber`, `mobileNumber`
- `address1`, `address2`, `city`, `state`, `zip`, `country`, `countryCode`
- `companyName`, `jobTitle`
- Custom fields for payment processor integration
- See: `/memories/session/contact-dto-documentation-complete.md`

### ContactData Methods
```php
public function getFullAddress(): string
public function recordTransaction($transactionId): void
public function linkToFAEntity($entityType, $entityId): void
public function fromArray(array $data): ContactData
public function toArray(): array
public function getDisplayName(): string
```

### Database Schema
**Table**: `0_bi_contact` (defined in [sql/update.sql](sql/update.sql#L90-L145))
- 40+ columns matching ContactData properties
- Foreign Key: `transactions.contact_id → contact.id`
- Not currently populated by application code

### Integration Status
- ✅ Database schema ready
- ❌ No model class (`bi_contact`) exists yet
- ❌ No ContactData usage in parsers or handlers
- ❌ No Service layer to populate contact records

---

## 10. CONTACT WORKFLOW INTEGRATION POINTS

### Current System Architecture
```
┌─ Parse Statement File ────────────────────┐
│  (MT940, CSV, QIF, OFX)                   │
│  ↓                                         │
│  Extract: transaction, amount, reference  │
│  (contact data scattered in various       │
│   fields, not normalized)                 │
└──────────────────────────────────────────┘
         ↓
┌─ Store in bi_transaction ────────────────┐
│ + bi_counterparty (email, phone, address) │
│ + bi_lineitem (details)                   │
│ contact_id field exists but NOT populated │
└──────────────────────────────────────────┘
         ↓
┌─ Manual Processing (process_statements.php)
│ User: Click "Add Customer" or "Add Vendor"
│ ↓
│ Call bi_controller->addCustomer()
│ ↓
│ Create FA entity (debtor, supplier)
└──────────────────────────────────────────┘
```

### Proposed API Contact Workflow
```
API Endpoint: POST /api/v1/contacts
├─ Request: Contact data (from transaction or manual entry)
├─ Middleware: AuthMiddleware → AdminMiddleware
├─ Handler: ContactController->store()
│   ├─ Validate email uniqueness
│   ├─ Create ContactData DTO from input
│   ├─ Insert into 0_bi_contact table
│   ├─ Link to transaction (if provided)
│   └─ Return: ContactData resource
└─ Response: 201 Created + location header

API Endpoint: GET /api/v1/contacts/search?q=john
├─ Search: firstName, lastName, email, company, phone
├─ Response: Paginated list of contacts
└─ Enable duplicate detection (before linking)

API Endpoint: PUT /api/v1/contacts/{id}
├─ Update contact properties
├─ Validate changes don't create duplicates
└─ Return: Updated ContactData

API Endpoint: GET /api/v1/transactions/{id}/contacts
├─ List all linked contacts for a transaction
├─ Grouped by type (payer, payee, intermediary)
└─ Returns: Array of ContactData + link metadata

API Endpoint: POST /api/v1/contacts/{contactId}/complete-transaction
├─ Mark contact-managed transaction as complete
├─ Create FA entities (customer/vendor) if not exists
├─ Return: Transaction result + link info
└─ This is the "transaction completion" workflow
```

---

## CONTEXT MAP SUMMARY

### Files to Modify
| File | Purpose | Changes Needed |
|------|---------|----------------|
| [src/Ksfraser/FaBankImport/config/api_routes.php](src/Ksfraser/FaBankImport/config/api_routes.php) | API Routes Configuration | **CREATE** - Define all REST endpoints |
| [src/Ksfraser/FaBankImport/controllers/Api/ContactController.php](src/Ksfraser/FaBankImport/controllers/Api/ContactController.php) | Contact REST Controller | **CREATE** - Main API handler |
| [src/Ksfraser/FaBankImport/controllers/AbstractController.php](src/Ksfraser/FaBankImport/controllers/AbstractController.php) | Base Controller | ENHANCE - Add `apiResponse()`, `apiError()` helpers |
| [src/Ksfraser/FaBankImport/http/RequestHandler.php](src/Ksfraser/FaBankImport/http/RequestHandler.php) | Request Handling | REVIEW - Ensure JSON body parsing |
| [src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php](src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php) | Authentication | ENHANCE - Add Bearer token support |

### Dependencies (Likely to Need Updates)
| File | Relationship |
|------|--------------|
| [src/Ksfraser/FaBankImport/services/ContactService.php](src/Ksfraser/FaBankImport/services/) | Must create - Business logic for contact operations |
| [src/Ksfraser/FaBankImport/repositories/ContactRepository.php](src/Ksfraser/FaBankImport/repositories/) | Must create - Database access layer |
| [src/Ksfraser/FaBankImport/validators/ContactValidator.php](src/Ksfraser/FaBankImport/validators/) | Must create - Input validation |
| [composer.json](composer.json) | Already has `ksfraser/contact-dto` dependency ✅ |
| [sql/update.sql](sql/update.sql) | Already has `0_bi_contact` schema ✅ |

### Test Files
| Test | Coverage |
|------|----------|
| [tests/unit/ContactControllerTest.php](tests/unit/) | **CREATE** - Unit tests for API endpoints |
| [tests/integration/ContactApiTest.php](tests/integration/) | **CREATE** - HTTP request/response testing |
| [tests/unit/ContactServiceTest.php](tests/unit/) | **CREATE** - Business logic tests |

### Reference Patterns
| File | Pattern |
|------|---------|
| [src/Ksfraser/FaBankImport/controllers/AdminController.php](src/Ksfraser/FaBankImport/controllers/AdminController.php) | JSON response pattern: `$this->json([$key => $value])` |
| [src/Ksfraser/FaBankImport/config/admin_routes.php](src/Ksfraser/FaBankImport/config/admin_routes.php) | Route configuration pattern |
| [src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php](src/Ksfraser/FaBankImport/middleware/AuthMiddleware.php) | Middleware pattern for request filtering |
| [src/Ksfraser/FaBankImport/controllers/AbstractController.php](src/Ksfraser/FaBankImport/controllers/AbstractController.php) | Controller base class with render() + json() |

### Risk Assessment
- [ ] ✅ **No breaking changes** - New API endpoints don't modify existing code paths
- [ ] ⚠️ **Database schema** - 0_bi_contact table exists; contact_id FK ready
- [ ] ⚠️ **ContactData DTO** - External package (v0.1.0); may need version pin
- [ ] ⚠️ **Auth integration** - Must handle both session-based (legacy) + Bearer tokens (API)
- [ ] ⚠️ **Backward compatibility** - Existing AJAX/legacy endpoints continue to work

---

## RECOMMENDED NEXT STEPS

### Phase 1: Setup (1-2 hours)
1. Create `src/Ksfraser/FaBankImport/config/api_routes.php` with endpoint definitions
2. Create `src/Ksfraser/FaBankImport/controllers/Api/ContactController.php` base class
3. Create `src/Ksfraser/FaBankImport/services/ContactService.php` (business logic)
4. Create `src/Ksfraser/FaBankImport/repositories/ContactRepository.php` (data access)
5. Add `apiResponse()` + `apiError()` helpers to AbstractController

### Phase 2: Middleware & Validation (1 hour)
1. Enhance `AuthMiddleware.php` to support Bearer tokens
2. Create `src/Ksfraser/FaBankImport/validators/ContactValidator.php`
3. Add request body JSON parsing to RequestHandler

### Phase 3: Endpoints (3-4 hours)
1. implement `GET /api/v1/contacts` (list)
2. Implement `POST /api/v1/contacts` (create)
3. Implement `GET /api/v1/contacts/search` (search)
4. Implement `GET /api/v1/contacts/{id}` (read)
5. Implement `PUT /api/v1/contacts/{id}` (update)
6. Implement `POST /api/v1/contacts/{id}/complete-transaction` (workflow)

### Phase 4: Testing (2-3 hours)
1. Unit tests for ContactController
2. Integration tests for HTTP requests
3. Service/Repository tests

---

## KEY FINDINGS

1. ✅ **Modern framework exists**: Symfony HttpFoundation + middleware pipeline in place
2. ✅ **Response handling ready**: `ResponseHandler->json()` method exists and works
3. ✅ **Auth infrastructure ready**: Middleware system + AdminMiddleware in place
4. ✅ **Route configuration ready**: Pattern exists in admin_routes.php
5. ❌ **No REST API yet**: All current endpoints are view/table-based, not JSON REST
6. ❌ **ContactData not integrated**: Package declared but never used in code
7. ⚠️ **Legacy code exists**: class.bank_import_controller.php uses old patterns, but BankImportController MVC version exists
8. ⚠️ **Database ready**: 0_bi_contact table created but never populated
9. ✅ **All pieces exist**: Just need to wire them together for contact API

---

## APPENDIX: EXISTING CONTROLLER JSON EXAMPLE

**Source**: [src/Ksfraser/FaBankImport/controllers/AdminController.php](src/Ksfraser/FaBankImport/controllers/AdminController.php)

```php
public function anomalyReport(): void
{
    $days = (int)$this->request->getQuery('days', '7');
    $threshold = (float)$this->request->getQuery('threshold', '2.0');
    
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    
    $metrics = $this->aggregator->aggregateMetrics($startDate, $endDate);
    $anomalies = $this->aggregator->detectPerformanceAnomalies($metrics, $threshold);
    
    if ($this->request->getQuery('format') === 'json') {
        $this->json(['anomalies' => $anomalies]);  // ← API RESPONSE
        return;
    }
    
    // Falls through to HTML view if format != 'json'
    $this->render('admin/anomaly_report', [
        'anomalies' => $anomalies,
        'days' => $days,
        'threshold' => $threshold
    ]);
}
```

**Key Pattern**:
- Check `$this->request->getQuery('format')` to determine response type
- Call `$this->json()` for REST responses (auto-sends + exits)
- Call `$this->render()` for HTML responses

---

**End of Context Map**
