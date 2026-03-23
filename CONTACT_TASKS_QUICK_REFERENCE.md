# Contact Integration - Next Steps Quick Reference

## ✅ Completed
- [x] bi_contact unit tests (18 tests, 72% passing)
- [x] ContactService wrapper (20+ methods)
- [x] ContactDeduplicationService (12+ methods with fuzzy matching)

---

## 🔄 Task 4: Update bank_import_controller Linking

**File**: `src/Ksfraser/FaBankImport/controllers/bank_import_controller.php`

**What to do**:
1. In the post-import success hook, add transaction-contact linking
2. For each imported transaction with merchant/payee data:
   ```php
   $contactService = new ContactService($db);
   $deduplication = new ContactDeduplicationService($contactService);
   
   $contactData = new ContactData([
       'name' => $transaction->merchant ?? $transaction->payee,
       'contact_type' => $transactionDC === 'D' ? 'supplier' : 'customer',
       // ... populate from parsed merchant data
   ]);
   
   $contact = $deduplication->getOrCreateWithDeduplicate($contactData);
   if ($contact) {
       $transaction->contact_id = $contact->id;
   }
   ```

**Estimated effort**: 3-4 hours

---

## 🔄 Task 5: Contact Matching/Lookup Service

**File to create**: `src/Ksfraser/FaBankImport/services/ContactMatchingService.php`

**Purpose**: Match merchant names to existing customers/suppliers

**Key Methods to Implement**:
- `matchCustomerByName($name)` - Fuzzy match to existing customers
- `matchSupplierByName($name)` - Fuzzy match to existing suppliers  
- `getPreferredContact($idList)` - Select best match from candidates
- `recordMatchConfidence($matchScore)` - Track match quality

**Use ContactDeduplicationService.calculateSimilarity()** for scoring

**Estimated effort**: 4-6 hours

---

## 🔄 Task 6: Contact Management UI/Views

**Files to create**:
- `src/Ksfraser/FaBankImport/views/contact_extraction_display.php`
- `src/Ksfraser/FaBankImport/views/contact_deduplication_panel.php`
- `src/Ksfraser/FaBankImport/views/contact_merge_confirmation.php`

**Features**:
1. **Extraction Display**:
   - List extracted contacts from parsed data
   - Show extraction confidence
   - Link to existing contacts

2. **Deduplication Panel**:
   - Display potential duplicates
   - Show similarity scores
   - Action buttons (merge/skip/create)

3. **Merge Confirmation**:
   - Before/after comparison
   - Merge field selection
   - Transaction reassignment info

**Technology**: Use existing FA HTML/form components

**Estimated effort**: 12-16 hours

---

## 🔄 Task 7: ContactData Reference Mapper

**File to create**: `CONTACT_DATA_MAPPER_GUIDE.md` or `PARSER_INTEGRATION_GUIDE.md`

**Purpose**: Documentation for parser developers

**Content to include**:
1. ContactData DTO field mapping
2. Parser → ContactData population examples
3. QIF parser implementation example
4. OFX parser implementation example  
5. CSV parser implementation example
6. Validation rules and constraints
7. Error handling patterns

**Example structure**:
```markdown
# Parser Integration Guide

## ContactData DTO Fields (40 fields)

| Field | Type | Required | Source | Validation |
|-------|------|----------|--------|-----------|
| name | string | YES | Merchant/Payee | Max 100 chars |
| email | string | NO | Memo/Description | Valid email |
| ...more fields... |

## QIF Parser Implementation
```php
// Example code showing how to extract contact data from QIF
$contactData = new ContactData([
    'name' => $qifRecord['!MERCHANT'],
    'contact_type' => $direction === 'OUT' ? 'supplier' : 'customer',
    // ... populate remaining fields from QIF record
]);
```

## OFX Parser Implementation
// Similar example...

```

**Estimated effort**: 2-3 hours

---

## 🎯 Execution Checklist

### Before Starting Task 4
- [ ] Review bank_import_controller.php structure
- [ ] Identify post-import hook location
- [ ] Verify transaction object has merchant/payee fields
- [ ] Test ContactService methods manually

### Before Starting Task 5
- [ ] Review existing PartnerDataService for comparison utils
- [ ] Test ContactDeduplicationService fuzzy matching
- [ ] Define customer/supplier type detection logic
- [ ] Create test dataset for matching validation

### Before Starting Task 6
- [ ] Review existing FA import UI components
- [ ] Design contact management workflow
- [ ] Create UI wireframes/mockups
- [ ] Plan AJAX endpoints for live matching

### Before Starting Task 7
- [ ] Review all parser interfaces (QIF, OFX, CSV)
- [ ] Document required vs optional fields per parser
- [ ] Create code examples for each parser type
- [ ] Create validation and error handling guide

---

## 📚 Reference Implementation

### ContactData Field Reference (from DTO)
```php
// Core identification
- id (int)
- name (string) - Required, unique
- display_name (string)

// Contact information
- email (string)
- phone (string)
- mobile_phone (string)
- fax (string)

// Business information
- company_name (string)
- department (string)
- contact_person (string)
- contact_type (enum: customer|supplier|employee|partner|other)

// Address
- address_line_1, address_line_2 (strings)
- city, state_province, postal_code (strings)
- country, country_code (strings)

// Business registration
- tax_id (string)
- registration_number (string)
- website (string)

// CRM linkage
- fa_customer_id (string)
- fa_supplier_id (string)

// Operational
- is_active (boolean)
- transaction_count (int)
- last_transaction_ts (datetime)
- total_transaction_amount (decimal)

// Timestamps
- created_ts (datetime)
- updated_ts (datetime)
```

---

## 🐛 Quick Fixes Before Proceeding

**Fix these in bi_contact.php** (5 min):
1. Line ~102: Change `private function getDB()` → `public function getDB()`
2. Line ~485: Fix `dump()` method to ensure it returns a string (currently may return null)
3. Update `fromContactData()` to properly map contact name from DTO

---

## ⏱️ Total Remaining Effort

| Task | Hours | Complexity | Priority |
|------|-------|-----------|----------|
| 4. Controller linking | 3-4 | Medium | HIGH |
| 5. Matching service | 4-6 | High | HIGH |
| 6. UI/Views | 12-16 | High | MEDIUM |
| 7. Reference guide | 2-3 | Low | LOW |
| **Total** | **21-29** | - | - |

---

## 📋 Database Readiness

**When to execute sql/update.sql**:
- Wait for UAT environment
- Verify backup of existing database
- Run in off-business hours
- Verify 0_bi_contact table created with 40+ fields
- Verify FK relationship to 0_bi_transactions created
- Verify all indexes created

**Post-execution verification**:
```php
// Quick test
$contact = new bi_contact($db);
$contact->name = 'Test Contact';
$contact->email = 'test@example.com';
$result = $contact->create(/*...*/);
// Should succeed with new table
```

---

## 🔗 Service Integration Pattern

For any new code integrating contacts:

```php
// Standard pattern
$contactService = new ContactService($db);
$deduplication = new ContactDeduplicationService($contactService);

// Use deduplication for new contacts
$contact = $deduplication->getOrCreateWithDeduplicate($contactData);

// Use raw service for existing contacts
if ($contactId) {
    $contact = $contactService->getContactById($contactId);
}

// Always use ContactService for updates
$contactService->updateContact($contact);
```

---

**Next Session**: Start with Task 4 after quick fixes to bi_contact.php
