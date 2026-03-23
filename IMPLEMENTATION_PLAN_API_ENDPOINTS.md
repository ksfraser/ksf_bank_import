# API Endpoints Implementation Plan

**Status:** Phase 2A - API Layer Created  
**Date:** March 23, 2026  
**Target Completion:** 3 phases

---

## Overview

This document tracks the implementation of REST API endpoints for the contact management system. The API serves the JavaScript frontend (contact-management.js) during bank transaction processing.

## Phase 1: Core API Infrastructure ✅ COMPLETE

### Completed Tasks

1. **Route Configuration** (`src/Ksfraser/FaBankImport/config/api_routes.php`)
   - ✅ Created route definitions for all endpoints
   - ✅ Follows existing admin_routes.php pattern
   - ✅ 8 routes defined (5 required + 3 extra CRUD operations)

2. **API Controller** (`src/Ksfraser/FaBankImport/Controllers/Api/ContactController.php`)
   - ✅ Extends AbstractController (Symfony-based)
   - ✅ Implements all 8 endpoint handlers
   - ✅ Comprehensive error handling with HTTP status codes
   - ✅ JSON response formatting
   - ✅ Input validation

3. **Endpoints Implemented**
   - ✅ `POST /api/contact-search` - Search with auto-detect, name, email, phone
   - ✅ `POST /api/contact-link` - Link contact to transaction
   - ✅ `POST /api/contact-create` - Create new contact with duplicate checking
   - ✅ `GET /api/contact-history/{contactId}` - Transaction history
   - ✅ `POST /api/transaction-complete` - Mark processing complete
   - ✅ `GET /api/contact/{contactId}` - Get contact details
   - ✅ `PUT /api/contact/{contactId}` - Update contact
   - ✅ `DELETE /api/contact/{contactId}` - Delete contact

---

## Phase 2: Route Registration & Middleware ⏳ PENDING

### Tasks

| Task | Status | Details |
|------|--------|---------|
| Register routes in main router | ⏳ PENDING | Load api_routes.php in request dispatcher |
| Add CORS middleware | ⏳ PENDING | Allow AJAX requests from same origin |
| Add authentication check | ⏳ PENDING | Verify user is logged in before allowing API access |
| Add authorization check | ⏳ PENDING | Verify user has permission to link/create contacts |
| Add request logging | ⏳ PENDING | Log all API calls for audit trail |

### Implementation Details

**Route Registration:**
```php
// In main request dispatcher/bootstrap
$apiRoutes = require __DIR__ . '/config/api_routes.php';
$router->registerRoutes($apiRoutes);
```

**Authentication:**
```php
// Check if user is logged in
if (!$this->isUserLoggedIn()) {
    return $this->json(['error' => 'Unauthorized'], 401);
}
```

**Authorization:**
```php
// Check if user can manage contacts
if (!$this->hasRole(['admin', 'bank_import_manager'])) {
    return $this->json(['error' => 'Forbidden'], 403);
}
```

---

## Phase 3: Database Integration ⏳ PENDING

### Schema Requirements

**bi_transactions table** (needs verification/updates):
```sql
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS contact_id INT NULL;
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE bi_transactions ADD COLUMN IF NOT EXISTS processed_at TIMESTAMP NULL;
ADD INDEX idx_contact_id (contact_id);
ADD INDEX idx_status (status);
```

**bi_contact table** (needs population):
```sql
-- Table should already exist from earlier phases
-- Verify structure matches ContactData DTO expectations
DESCRIBE bi_contact;
```

### Database Access Methods

**Current Implementation:** Raw SQL with global database functions
- Uses `query()` function if available
- Falls back to WordPress `$wpdb` if in WordPress environment
- Needs refactoring to use proper repository pattern

**Recommended Approach:**
```php
// Create ContactRepository class
$contactRepository = new ContactRepository();
$transactions = $contactRepository->findByContactId($contactId, 20);
```

---

## Testing Strategy

### Unit Tests to Create

1. **ContactControllerTest.php**
   - Test each endpoint with valid/invalid input
   - Mock ContactService, ContactMatchingService
   - Verify response formats and status codes
   - Total: ~20 test methods

2. **Integration Tests**
   - Test full workflow: search → select → link → complete
   - Use real database or fixtures
   - Test error scenarios

### Manual Testing

1. **Search Endpoint**
   ```bash
   curl -X POST http://localhost/api/contact-search \
     -d "search_term=Acme&search_by=name&threshold=0.75"
   ```

2. **Create Endpoint**
   ```bash
   curl -X POST http://localhost/api/contact-create \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Corp","type":"S"}'
   ```

3. **Link Endpoint**
   ```bash
   curl -X POST http://localhost/api/contact-link \
     -d "transaction_id=123&contact_id=456"
   ```

---

## Known Issues & Limitations

### ⚠️ Current Limitations

1. **Database Abstraction**
   - Controllers use raw SQL directly
   - No proper repository pattern
   - Will need refactoring: raw SQL → Repository → ORM

2. **Error Handling**
   - Limited database error information
   - May not gracefully handle connection failures
   - Middleware error handling not yet implemented

3. **Input Validation**
   - Basic validation implemented
   - Missing: sanitization, XSS prevention, SQL injection protection
   - Needs: unified validator class

4. **Performance**
   - No query optimization
   - No caching strategy
   - May be slow with large contact databases

5. **API Design**
   - No pagination for bulk operations
   - No filtering/sorting API
   - No batching operations support

### 🚀 Future Enhancements

- [ ] Add pagination: `/api/contact?page=1&limit=20`
- [ ] Add filtering: `/api/contact?type=S&status=active`
- [ ] Add sorting: `/api/contact?sort=name&order=asc`
- [ ] Add bulk operations: `POST /api/contact/batch`
- [ ] Add API versioning: `/api/v2/contact-search`
- [ ] Add rate limiting
- [ ] Add caching layer (Redis)
- [ ] Add Swagger/OpenAPI documentation
- [ ] Add API key authentication
- [ ] Add request signing

---

## File Status

| File | Status | Lines | Purpose |
|------|--------|-------|---------|
| api_routes.php | ✅ Ready | 28 | Route definitions |
| ContactController.php | ✅ Ready | 550+ | Endpoint handlers |
| ContactMatchingService.php | ✅ Ready | 400+ | Search logic |
| ContactDeduplicationService.php | ✅ Ready | 300+ | Fuzzy matching |
| ContactService.php | ⏳ Needs Update | - | CRUD operations |
| contact-management.js | ✅ Ready | 560+ | Frontend handlers |

---

## Next Steps (After Phase 1)

### Immediate (Phase 2 - Route Integration)
1. Locate main request dispatcher/bootstrap file
2. Register api_routes.php in router
3. Add authentication/authorization middleware
4. Add CORS headers for AJAX requests
5. Run basic smoke tests with curl

### Short Term (Phase 3 - Database Integration)
1. Verify bi_transactions schema
2. Create ContactRepository class
3. Implement database abstraction layer
4. Migrate raw SQL to repository methods
5. Run integration tests

### Medium Term (Post-Phase 3)
1. Add comprehensive unit tests (ContactControllerTest)
2. Add API documentation (Swagger/OpenAPI)
3. Add rate limiting
4. Add caching strategy
5. Performance optimization

---

## Integration Checklist

- [ ] Request dispatcher loads api_routes.php
- [ ] Routes are correctly parsed and mapped
- [ ] Authentication middleware blocks unauthenticated requests
- [ ] Authorization checks work correctly
- [ ] CORS headers allow browser AJAX requests
- [ ] All 5 main endpoints functional
- [ ] Error responses properly formatted
- [ ] Database operations work (not just mock)
- [ ] Transaction history queries work
- [ ] Contact creation actually saves to database
- [ ] Contact linking updates bi_transactions.contact_id
- [ ] All tests pass (unit + integration)

---

## Success Criteria

✅ **Phase 1 Complete When:**
- All 8 endpoint handlers implemented
- All routes defined
- Error handling in place
- Input validation present

⏳ **Phase 2 Complete When:**
- Routes registered and working
- Authentication/authorization enforced
- CORS headers correct
- Can test endpoints with curl

✅ **Phase 3 Complete When:**
- Database operations functional
- Transactions properly linked
- Contact creation saves to DB
- History retrieval works
- All integration tests pass

---

## References

- [ContactMatchingService.php](../Services/ContactMatchingService.php) - Search logic
- [ContactDeduplicationService.php](../Services/ContactDeduplicationService.php) - Matching algorithm
- [contact-management.js](../Views/contact-management.js) - Frontend API calls
- [admin_routes.php](./admin_routes.php) - Routing pattern reference
- [AbstractController.php](../Controllers/AbstractController.php) - Base class reference

