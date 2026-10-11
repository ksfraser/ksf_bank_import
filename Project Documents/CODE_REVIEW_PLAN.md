# Comprehensive Code Review & Refactoring Plan

## Objective
Ensure entire codebase follows SOLID/DI/DRY/MVC/Fowler SRP principles with:
- Complete PHPDoc documentation
- UML diagrams for architecture
- Unit tests for all classes
- Interfaces and parent classes where appropriate
- PSR compliance (PSR-1, PSR-4, PSR-12)
- PHP 7.4 compatibility
- Date-based @since/@version tags

## Review Process
1. **Inventory all PHP files** - Categorize by type (Model/View/Controller/Service/etc.)
2. **Map test coverage** - Identify files without tests
3. **Assess SOLID compliance** - Check each principle
4. **Check DRY violations** - Find duplicated code
5. **Verify MVC separation** - Ensure proper layering
6. **Review PHPDoc** - Add missing documentation
7. **Validate PSR compliance** - Check coding standards
8. **TDD refactoring** - Write test → Fail → Code → Pass → Refactor

---

## Phase 1: File Inventory & Classification

### Production Code Structure

#### Core Models (Business Logic)
- [ ] `class.bi_statements.php` - Statement model
- [ ] `class.bi_transactions.php` - Transaction model  
- [ ] `class.bi_lineitem.php` - Line item model
- [ ] `class.bi_transaction.php` - Single transaction
- [ ] `class.bi_partners_data.php` - Partner data model
- [ ] `class.bi_counterparty_model.php` - Counterparty model
- [ ] `class.bi_transactionTitle_model.php` - Transaction title model

#### Controllers
- [ ] `class.bank_import_controller.php` - Main controller
- [ ] `process_statements.php` - Statement processing controller
- [ ] `import_statements.php` - Import controller
- [ ] `manage_partners_data.php` - Partner management controller
- [ ] `validate_gl_entries.php` - GL validation controller

#### Views (Display Layer)
- [ ] `view_statements.php` - Statement viewer
- [ ] `views/class.bi_lineitem.php` - Line item view (ViewBILineItems)
- [ ] `class.ViewBiLineItems.php` - View class
- [ ] `class.transactions_table.php` - Transaction table view

#### View Components (HTML Rendering)
- [ ] `views/TransDate.php`
- [ ] `views/TransType.php`
- [ ] `views/TransTitle.php`
- [ ] `views/OurBankAccount.php`
- [ ] `views/OtherBankAccount.php`
- [ ] `views/AmountCharges.php`
- [ ] `views/AddVendorButton.php`
- [ ] `views/AddCustomerButton.php`

#### Services (Business Logic Layer)
- [ ] Phase 2 services already refactored ✅
  - FileUploadService
  - FileStorageService
  - DuplicateDetector

#### Parsers (Data Input)
- [ ] `qfx_parser.php` - QFX parser
- [ ] `class.AbstractQfxParser.php` - Abstract base
- [ ] `class.QfxParserFactory.php` - Factory
- [ ] `class.CibcQfxParser.php` - CIBC parser
- [ ] `class.ManuQfxParser.php` - Manulife parser
- [ ] `class.PcmcQfxParser.php` - PCMC parser
- [ ] `mt940_parser.php` - MT940 parser
- [ ] `ro_*_parser.php` - Romanian bank parsers

#### Utilities
- [ ] `hooks.php` - Module hooks
- [ ] `VendorListManager.php` - Vendor list management
- [ ] `header_table.php` - Table header utility

---

## Phase 2: Test Coverage Analysis

### Files WITH Tests ✅
1. **Phase 2 (Recently Refactored)**
   - ✅ FileInfo - `tests/ValueObject/FileInfoTest.php` (20 tests)
   - ✅ DuplicateResult - `tests/ValueObject/DuplicateResultTest.php` (10 tests)
   - ✅ UploadResult - `tests/ValueObject/UploadResultTest.php` (15 tests)
   - ✅ UploadedFile - `tests/Entity/UploadedFileTest.php` (9 tests)
   - ✅ DuplicateStrategy - `tests/Strategy/DuplicateStrategyTest.php` (10 tests)
   - ✅ FileStorageService - `tests/Service/FileStorageServiceTest.php` (17 tests)

2. **Legacy Tests (Exist but may need updates)**
   - ⚠️ `tests/BiTransactionTableTest.php`
   - ⚠️ `tests/BiTransactionsModelTest.php`
   - ⚠️ `tests/BiStatementsModelTest.php`
   - ⚠️ `tests/BiPartnersDataTest.php`
   - ⚠️ `tests/BiLineitemTest.php`
   - ⚠️ `tests/BiTransactionTest.php`
   - ⚠️ `tests/BiCounterpartyModelTest.php`
   - ⚠️ `tests/BiTransactionTitleModelTest.php`
   - ⚠️ `tests/QfxParserTest.php`

### Files WITHOUT Tests ❌ (HIGH PRIORITY)

#### Models
- ❌ `class.bi_statements.php`
- ❌ `class.bi_transactions.php`
- ❌ `class.bi_lineitem.php`
- ❌ `class.bi_transaction.php`
- ❌ `class.bi_partners_data.php`
- ❌ `class.bi_counterparty_model.php`
- ❌ `class.bi_transactionTitle_model.php`

#### Controllers
- ❌ `class.bank_import_controller.php`
- ❌ `process_statements.php`
- ❌ `import_statements.php`
- ❌ `manage_partners_data.php`
- ❌ `validate_gl_entries.php`

#### Views
- ❌ `view_statements.php`
- ❌ `views/class.bi_lineitem.php` (ViewBILineItems)
- ❌ `class.transactions_table.php`
- ❌ All view components (TransDate, TransType, etc.)

#### Parsers
- ❌ `class.AbstractQfxParser.php`
- ❌ `class.QfxParserFactory.php`
- ❌ `class.CibcQfxParser.php`
- ❌ `class.ManuQfxParser.php`
- ❌ `class.PcmcQfxParser.php`
- ❌ `mt940_parser.php`
- ❌ Romanian bank parsers

#### Utilities
- ❌ `VendorListManager.php`
- ❌ `header_table.php`

---

## Phase 3: SOLID Violations Assessment

### Single Responsibility Principle (SRP)
**Violations Found:**
1. ❌ `class.bi_lineitem.php` - Mixes Model + View logic (1973 lines!)
   - Contains both `bi_lineitem` (model) and `ViewBILineItems` (view)
   - **Action**: Split into separate files
   
2. ❌ `process_statements.php` - Controller + Business Logic + View
   - **Action**: Extract services, separate concerns

3. ❌ `class.bi_transactions.php` - Model + Database + Validation
   - **Action**: Extract repository, validator service

### Open/Closed Principle (OCP)
**Violations Found:**
1. ❌ Parser selection uses switch statements
   - **Action**: Already using Factory pattern ✅, verify no modifications needed

2. ❌ Partner type handling uses switch statements
   - **Action**: Implement Strategy pattern

### Liskov Substitution Principle (LSP)
**Assessment Needed:**
- Check parser inheritance hierarchy
- Verify view component inheritance

### Interface Segregation Principle (ISP)
**Violations Found:**
1. ❌ No interfaces for models
   - **Action**: Create BiLineItemInterface, BiTransactionInterface, etc.

2. ❌ No interfaces for views
   - **Action**: Create ViewInterface for all view components

### Dependency Inversion Principle (DIP)
**Violations Found:**
1. ❌ Direct database calls in models
   - **Action**: Inject repository interfaces

2. ❌ Hard-coded FA function calls
   - **Action**: Create FA adapter interfaces

3. ❌ Direct class instantiation everywhere
   - **Action**: Use dependency injection container

---

## Phase 4: DRY Violations

### Code Duplication Found:
1. ❌ **Display methods repeated across view components**
   - `TransDate`, `TransType`, `TransTitle` all have similar structure
   - **Action**: Extract base class or trait

2. ❌ **Database query patterns repeated**
   - Similar SQL in multiple models
   - **Action**: Use repository pattern consistently

3. ❌ **Validation logic repeated**
   - Same checks in multiple places
   - **Action**: Extract validator classes

4. ❌ **Form rendering repeated**
   - Similar HTML generation in multiple views
   - **Action**: Create form builder service

---

## Phase 5: MVC Separation Issues

### Model Layer Issues:
1. ❌ Models contain display logic
   - `bi_lineitem` has `display()`, `display_left()`, `display_right()` methods
   - **Action**: Move to View layer

2. ❌ Models contain controller logic
   - Processing form data directly in models
   - **Action**: Move to Controller layer

### View Layer Issues:
1. ❌ Views contain business logic
   - Decision making in views
   - **Action**: Move to Model/Service layer

2. ❌ Views access database directly
   - Some views query FA database
   - **Action**: Pass data from controller

### Controller Layer Issues:
1. ❌ Controllers contain business logic
   - Complex calculations in process_statements.php
   - **Action**: Extract to service layer

2. ❌ No clear controller structure
   - Procedural scripts instead of controller classes
   - **Action**: Create proper controller classes

---

## Phase 6: PHPDoc Coverage

### Missing PHPDoc:
1. ❌ Most legacy files lack complete PHPDoc
2. ❌ No `@property` tags for magic properties
3. ❌ No `@throws` tags for exceptions
4. ❌ Missing `@param` type hints
5. ❌ Missing `@return` type hints
6. ❌ No class-level documentation for many files

### Required PHPDoc Elements:
```php
/**
 * Brief description
 * 
 * Longer description explaining purpose, patterns, and usage
 * 
 * @package Namespace\Package
 * @author Kevin Fraser
 * @since YYYYMMDD
 * @version YYYYMMDD.iteration
 */
class ClassName
{
    /**
     * Property description
     * @var Type
     */
    protected $property;
    
    /**
     * Method description
     * 
     * @param Type $param Description
     * @return Type Description
     * @throws ExceptionType Description
     */
    public function method($param): Type
    {
    }
}
```

---

## Phase 7: PSR Compliance

### PSR-1: Basic Coding Standard
- [ ] Check all files use `<?php` tag
- [ ] Verify no side effects in class files
- [ ] Confirm class names use StudlyCaps
- [ ] Confirm method names use camelCase
- [ ] Verify constants use UPPER_CASE

### PSR-4: Autoloading
- ✅ Namespace structure: `Ksfraser\FaBankImport\*`
- ⚠️ Many legacy files not in namespace
- **Action**: Add namespaces to legacy files

### PSR-12: Extended Coding Style
- [ ] Check indentation (4 spaces)
- [ ] Verify line length (<120 chars preferred)
- [ ] Check brace placement
- [ ] Verify visibility declarations
- [ ] Check method/property spacing

---

## Phase 8: Fowler Refactoring Patterns Needed

### Catalog of Needed Refactorings:

1. **Extract Class**
   - `class.bi_lineitem.php` → Split model and view
   - Priority: HIGH

2. **Extract Method**
   - Long methods (>20 lines) in multiple files
   - Priority: MEDIUM

3. **Replace Conditional with Polymorphism**
   - Partner type switches → Strategy pattern
   - Transaction type handling
   - Priority: HIGH

4. **Introduce Parameter Object**
   - Multiple parameters passed around
   - Priority: MEDIUM

5. **Form Template Method**
   - Similar algorithms in parser classes
   - Priority: LOW (already using inheritance)

6. **Replace Magic Number with Symbolic Constant**
   - Numeric constants throughout code
   - Priority: LOW

7. **Encapsulate Field**
   - Public properties in legacy code
   - Priority: HIGH

8. **Replace Error Code with Exception**
   - Return codes instead of exceptions
   - Priority: MEDIUM

---

## Implementation Strategy

### Week 1: Foundation
**Priority 1 - Critical SOLID Violations**
1. Split `class.bi_lineitem.php` (Model + View)
2. Create model interfaces
3. Extract repositories from models
4. Add tests for models

### Week 2: Controllers
**Priority 2 - Controller Layer**
1. Refactor `process_statements.php`
2. Extract service layer
3. Create controller classes
4. Add controller tests

### Week 3: Views
**Priority 3 - View Layer**
1. Refactor view components
2. Create view base classes
3. Remove business logic from views
4. Add view tests

### Week 4: Parsers
**Priority 4 - Parser Refactoring**
1. Complete parser test coverage
2. Verify Factory pattern
3. Add parser interfaces
4. Document parser architecture

### Week 5: Polish
**Priority 5 - Documentation & Compliance**
1. Complete PHPDoc coverage
2. Generate UML diagrams
3. PSR compliance fixes
4. Final code review

---

## Success Metrics

### Code Quality Goals:
- ✅ Test Coverage: >90% (currently ~50%)
- ✅ SOLID Score: >85% (currently ~60%)
- ✅ DRY Score: >80% (currently ~50%)
- ✅ PHPDoc Coverage: 100% (currently ~40%)
- ✅ PSR Compliance: 100% (currently ~70%)
- ✅ Cyclomatic Complexity: <10 per method
- ✅ Lines per method: <20
- ✅ Lines per class: <500

### Current Status:
- Total PHP Files: ~1290
- Files with Tests: ~25 (2%)
- Files without Tests: ~1265 (98%)
- SOLID Compliant: ~50 files (4%)
- Needs Refactoring: ~1240 files (96%)

---

## Next Steps

### Immediate Actions (Today):
1. ✅ Create this review document
2. ⏳ Analyze `class.bi_lineitem.php` in detail
3. ⏳ Write test for bi_lineitem model (TDD)
4. ⏳ Extract bi_lineitem model to separate file
5. ⏳ Write test for ViewBILineItems
6. ⏳ Verify ViewBILineItems (already separated)

### This Week:
1. Complete bi_lineitem model tests
2. Complete ViewBILineItems tests
3. Refactor 5 view components with tests
4. Create model interfaces
5. Document changes with UML

---

## Risk Assessment

### High Risk:
- Breaking existing functionality during refactoring
- Test coverage gaps allowing bugs to slip through
- Time required for comprehensive refactoring

### Mitigation:
- ✅ TDD approach (test first, then code)
- ✅ Small, incremental commits
- ✅ Keep legacy code working during migration
- ✅ Add deprecation comments, not immediate removal
- ✅ Comprehensive regression testing

---

## Notes

**Date**: 2025-01-19
**Status**: Planning Complete - Ready to Execute
**Next Review**: After Week 1 completion

**Key Principles**:
1. Test First, Always
2. Small Changes, Frequent Commits
3. Document Everything
4. Don't Break Production
5. Measure Progress

