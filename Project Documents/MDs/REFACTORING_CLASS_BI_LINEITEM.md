# Refactoring Plan: class.bi_lineitem.php

## Current State Analysis

### File: `views/class.bi_lineitem.php`
- **Lines**: 1973
- **Classes**: 8 (MASSIVE SRP VIOLATION!)
- **Responsibilities**: Model + View + HTML Rendering
- **Test Coverage**: 0% ❌

### Classes in File:
1. `HTML_SUBMIT` - HTML submit button (lines 47-54)
2. `HTML_ROW` - HTML table row (lines 58-69)
3. `HTML_ROW_LABEL` - Label row (lines 71-87)
4. `HTML_TABLE` - HTML table (lines 89-130)
5. `displayLeft` - Display left side (lines 176-178)
6. `displayRight` - Display right side (lines 180-182)
7. `ViewBILineItems` - View for line items (lines 220-896) ✅ ALREADY HAS PHPDOC
8. `bi_lineitem` - Model for line items (lines 898-1973)

---

## SOLID Violations

### Single Responsibility Principle
❌ **SEVERE VIOLATION**: One file with 8 classes
- HTML rendering classes mixed with business logic
- Model and View in same file
- Utility classes alongside domain models

### Open/Closed Principle
⚠️ **MODERATE VIOLATION**: Hard to extend without modifying file

### Liskov Substitution Principle
✅ **NO VIOLATION**: Inheritance appears correct

### Interface Segregation Principle
❌ **VIOLATION**: No interfaces defined

### Dependency Inversion Principle
❌ **VIOLATION**: Direct dependencies on concrete classes

---

## Refactoring Strategy

### Phase 1: Extract HTML Classes (TDD)
**Target**: Move HTML rendering classes to proper location

#### 1.1 Extract HTML_SUBMIT
```
Location: src/Ksfraser/HTML/HtmlSubmit.php
Test: tests/HTML/HtmlSubmitTest.php
Steps:
1. Write test for HTML_SUBMIT (RED)
2. Create HtmlSubmit class with namespace (GREEN)
3. Implement toHTML() method (GREEN)
4. Add deprecation comment to old class (REFACTOR)
5. Update references (if any)
6. Commit: "refactor: Extract HtmlSubmit from class.bi_lineitem.php"
```

#### 1.2 Extract HTML_ROW
```
Location: views/HTML/HtmlRow.php (already exists at src/Ksfraser/HTML/HTML_ROW.php)
Test: tests/HTML/HtmlRowTest.php
Steps:
1. Write test for HTML_ROW (RED)
2. Verify existing HtmlRow class matches
3. Add deprecation comment to old class
4. Update references
5. Commit: "refactor: Use existing HtmlRow, deprecate duplicate"
```

#### 1.3 Extract HTML_ROW_LABEL
```
Location: src/Ksfraser/HTML/HtmlRowLabel.php
Test: tests/HTML/HtmlRowLabelTest.php
Steps:
1. Write test (RED)
2. Create class with proper namespace (GREEN)
3. Implement functionality (GREEN)
4. Add deprecation comment
5. Update references
6. Commit: "refactor: Extract HtmlRowLabel from class.bi_lineitem.php"
```

#### 1.4 Extract HTML_TABLE
```
Location: src/Ksfraser/HTML/HtmlTable.php
Test: tests/HTML/HtmlTableTest.php
Steps:
1. Write test (RED)
2. Create class with namespace (GREEN)
3. Implement appendRow() and toHTML() (GREEN)
4. Add deprecation comment
5. Update references
6. Commit: "refactor: Extract HtmlTable from class.bi_lineitem.php"
```

---

### Phase 2: Extract Display Classes (TDD)

#### 2.1 Extract displayLeft
```
Location: views/DisplayLeft.php (or src/Ksfraser/FaBankImport/views/DisplayLeft.php)
Test: tests/views/DisplayLeftTest.php
Steps:
1. Analyze displayLeft - it extends LineitemDisplayLeft (already exists!)
2. Write test (RED)
3. Verify it's just a placeholder class
4. Add deprecation comment
5. Update to use parent directly
6. Commit: "refactor: Remove redundant displayLeft class"
```

#### 2.2 Extract displayRight
```
Location: views/DisplayRight.php
Test: tests/views/DisplayRightTest.php
Steps:
1. Write test (RED)
2. Create proper DisplayRight class (GREEN)
3. Implement functionality (GREEN)
4. Add deprecation comment to old
5. Update references
6. Commit: "refactor: Extract DisplayRight from class.bi_lineitem.php"
```

---

### Phase 3: Refactor ViewBILineItems (TDD)

**Current Status**: ✅ Already has PHPDoc, magic getter, delegate methods (159 errors → 0)

#### 3.1 Create ViewBILineItems Interface
```php
Location: src/Ksfraser/FaBankImport/views/ViewBILineItemsInterface.php
Test: tests/views/ViewBILineItemsTest.php

interface ViewBILineItemsInterface
{
    public function display(): void;
    public function display_left(): void;
    public function display_right(): void;
    public function displayAddVendorOrCustomer(): void;
    public function displayEditTransData(): void;
    public function displayPaired(): void;
    public function isPaired(): bool;
}
```

#### 3.2 Move ViewBILineItems to Own File
```
Steps:
1. Write comprehensive tests (RED)
2. Create src/Ksfraser/FaBankImport/views/ViewBILineItems.php
3. Copy class with proper namespace
4. Add use statements
5. Verify tests pass (GREEN)
6. Add deprecation comment in old file
7. Update imports elsewhere
8. Commit: "refactor: Extract ViewBILineItems to separate file"
```

---

### Phase 4: Refactor bi_lineitem Model (TDD) ⚠️ CRITICAL

**Current State**: 1075 lines of model code!

#### 4.1 Create BiLineItem Interface
```php
Location: src/Ksfraser/FaBankImport/Model/BiLineItemInterface.php

interface BiLineItemInterface
{
    // Core model methods
    public function getBankAccountDetails(): void;
    public function isPaired(): bool;
    public function findPaired(): array;
    public function matchedVendor();
    public function matchedSupplierId(array $matchedVendor): int;
    public function findMatchingExistingJE(): array;
    public function setPartnerType(): string;
    public function determineTransactionTypeLabel(): void;
    
    // Getters
    public function getId(): int;
    public function getOurAccount(): string;
    public function getOtherBankAccount(): string;
    // ... etc
}
```

#### 4.2 Extract Repository Pattern
```
Create: src/Ksfraser/FaBankImport/Repository/BiLineItemRepositoryInterface.php
Create: src/Ksfraser/FaBankImport/Repository/DatabaseBiLineItemRepository.php
Test: tests/Repository/BiLineItemRepositoryTest.php

Steps:
1. Write repository interface (contract)
2. Write repository tests (RED)
3. Extract database logic from model (GREEN)
4. Inject repository into model (DIP)
5. Verify tests pass (GREEN)
6. Commit: "refactor: Extract BiLineItem repository pattern"
```

#### 4.3 Extract Services from bi_lineitem
```
Create: src/Ksfraser/FaBankImport/Service/PairDetectionService.php
Create: src/Ksfraser/FaBankImport/Service/TransactionMatchingService.php
Create: src/Ksfraser/FaBankImport/Service/PartnerTypeService.php
Tests: Create corresponding test files

Responsibilities:
- PairDetectionService: isPaired(), findPaired()
- TransactionMatchingService: findMatchingExistingJE()
- PartnerTypeService: setPartnerType(), partner logic
```

#### 4.4 Create Clean bi_lineitem Model
```php
Location: src/Ksfraser/FaBankImport/Model/BiLineItem.php
Test: tests/Model/BiLineItemTest.php

Steps:
1. Write comprehensive model tests (RED)
2. Create new clean model class (GREEN)
3. Add namespace and proper PSR-4 structure
4. Inject dependencies via constructor
5. Remove display logic (belongs in View)
6. Remove database logic (belongs in Repository)
7. Keep only business logic
8. Verify tests pass (GREEN)
9. Add deprecation to old class
10. Commit: "refactor: Create clean BiLineItem model with DI"
```

---

### Phase 5: Update References

#### 5.1 Update process_statements.php
```
Steps:
1. Add use statements for new classes
2. Update instantiation to use new classes
3. Run integration tests
4. Commit: "refactor: Update process_statements to use new BiLineItem"
```

#### 5.2 Update Other Files
- import_statements.php
- view_statements.php
- Any other files using bi_lineitem

---

### Phase 6: Deprecation Strategy

#### 6.1 Add Deprecation Comments
```php
/**
 * @deprecated 20250119 Moving to src/Ksfraser/HTML/HtmlSubmit.php
 * This class will be removed in version 20250301
 * Use Ksfraser\HTML\HtmlSubmit instead
 */
class HTML_SUBMIT
{
    // ... existing code
}
```

#### 6.2 Timeline
- ✅ **2025-01-19**: Add deprecation comments
- ⏳ **2025-02-01**: Update all references to new classes
- ⏳ **2025-03-01**: Remove old classes from file

---

## Test Coverage Plan

### Test Files to Create:

#### HTML Tests
1. `tests/HTML/HtmlSubmitTest.php` - 5 tests
   - Test toHTML() output
   - Test button creation
   
2. `tests/HTML/HtmlRowLabelTest.php` - 10 tests
   - Test constructor with label/data
   - Test width/class parameters
   - Test toHTML() output
   - Test inheritance from HTML_ROW

3. `tests/HTML/HtmlTableTest.php` - 15 tests
   - Test constructor with style/width
   - Test appendRow() with object
   - Test appendRow() with string
   - Test appendRow() throws exception
   - Test toHTML() output
   - Test multiple rows

#### View Tests
4. `tests/views/ViewBILineItemsTest.php` - 50 tests
   - Test constructor
   - Test __get() magic method
   - Test display() method
   - Test display_left() method
   - Test display_right() method
   - Test displayAddVendorOrCustomer()
   - Test displayEditTransData()
   - Test displayPaired()
   - Test isPaired()
   - Test matchedVendor()
   - Test matchedSupplierId()
   - Test selectAndDisplayButton()
   - Test setPartnerType()
   - Test getDisplayMatchingTrans()
   - Test all partner type displays (SP/CU/BT/QE/MA/ZZ)
   - Mock bi_lineitem object

#### Model Tests
5. `tests/Model/BiLineItemTest.php` - 100+ tests
   - Test constructor
   - Test property setters/getters
   - Test determineTransactionTypeLabel()
   - Test getBankAccountDetails()
   - Test isPaired()
   - Test findPaired()
   - Test matchedVendor()
   - Test matchedSupplierId()
   - Test setPartnerType()
   - Mock dependencies (repository, services)

#### Repository Tests
6. `tests/Repository/BiLineItemRepositoryTest.php` - 30 tests
   - Test findById()
   - Test findByStatement()
   - Test save()
   - Test update()
   - Test delete()
   - Mock database

#### Service Tests
7. `tests/Service/PairDetectionServiceTest.php` - 20 tests
8. `tests/Service/TransactionMatchingServiceTest.php` - 25 tests
9. `tests/Service/PartnerTypeServiceTest.php` - 15 tests

**Total New Tests**: ~270 tests

---

## PHPDoc Requirements

### Each Class Needs:
```php
/**
 * Brief one-line description
 * 
 * Longer description explaining:
 * - Purpose and responsibility
 * - Design patterns used
 * - SOLID principles followed
 * - Usage examples
 * 
 * @package Ksfraser\FaBankImport\[Model|View|Service|Repository]
 * @author Kevin Fraser
 * @since 20250119
 * @version 20250119.0
 */
```

### Each Method Needs:
```php
/**
 * Brief description of what method does
 * 
 * @param Type $param Description
 * @return Type Description
 * @throws ExceptionType When this happens
 */
```

### Properties Need:
```php
/**
 * Description of property
 * @var Type
 */
protected $property;
```

---

## UML Diagrams to Create

### 1. Current State (Before)
```
┌─────────────────────────────────┐
│   class.bi_lineitem.php         │
├─────────────────────────────────┤
│ HTML_SUBMIT                     │
│ HTML_ROW                        │
│ HTML_ROW_LABEL                  │
│ HTML_TABLE                      │
│ displayLeft                     │
│ displayRight                    │
│ ViewBILineItems                 │
│ bi_lineitem                     │
└─────────────────────────────────┘
     ↓ VIOLATES SRP
```

### 2. Future State (After)
```
┌──────────────────┐
│  HTML Layer      │
├──────────────────┤
│ HtmlSubmit.php   │
│ HtmlRow.php      │
│ HtmlRowLabel.php │
│ HtmlTable.php    │
└──────────────────┘
         ↑
         │
┌──────────────────┐     ┌─────────────────────┐
│  View Layer      │     │  Model Layer        │
├──────────────────┤     ├─────────────────────┤
│ViewBILineItems   │────→│ BiLineItem          │
│DisplayLeft       │     │   + Repository      │
│DisplayRight      │     │   + Services        │
└──────────────────┘     └─────────────────────┘
```

### 3. Class Diagram for bi_lineitem
- Show all properties
- Show all methods
- Show relationships (associations, dependencies)
- Show interfaces implemented

### 4. Sequence Diagram
- Show interaction between View → Model → Repository
- Show service layer interactions

---

## PSR Compliance Checklist

### PSR-1: Basic Coding Standard
- [ ] Use <?php tag only
- [ ] Files MUST use UTF-8 without BOM
- [ ] Class names MUST be StudlyCaps
- [ ] Method names MUST be camelCase
- [ ] Constants MUST be UPPER_SNAKE_CASE

### PSR-4: Autoloading
- [ ] Namespace MUST match directory structure
- [ ] One class per file
- [ ] Proper use statements

### PSR-12: Extended Coding Style
- [ ] 4 spaces for indentation
- [ ] Opening brace on same line for methods
- [ ] Visibility on all properties/methods
- [ ] Type hints where possible (PHP 7.4)

---

## Execution Timeline

### Day 1 (Today - 2025-01-19)
- ✅ Create CODE_REVIEW_PLAN.md
- ✅ Create this refactoring plan
- ⏳ Extract HTML_SUBMIT (test + code)
- ⏳ Extract HTML_ROW (test + code)

### Day 2
- Extract HTML_ROW_LABEL (test + code)
- Extract HTML_TABLE (test + code)
- Extract displayLeft (test + code)
- Extract displayRight (test + code)

### Day 3
- Create ViewBILineItemsInterface
- Write ViewBILineItems tests
- Extract ViewBILineItems to separate file
- Verify all tests pass

### Day 4
- Create BiLineItemInterface
- Write BiLineItemRepository interface/tests
- Extract repository logic

### Day 5
- Extract service classes with tests
- Create clean BiLineItem model
- Write comprehensive model tests

### Day 6-7
- Update all references
- Add deprecation comments
- Final integration testing
- Documentation and UML diagrams

---

## Success Criteria

- [ ] 8 classes → 8 separate files
- [ ] 270+ new tests written and passing
- [ ] 100% PHPDoc coverage
- [ ] PSR-1, PSR-4, PSR-12 compliant
- [ ] SOLID principles followed
- [ ] UML diagrams created
- [ ] Deprecation comments added
- [ ] All integration tests pass
- [ ] Code review complete

---

## Notes

- Keep legacy code working during migration
- Use adapter pattern if needed for backward compatibility
- Document all changes in commit messages
- Update CLEANUP_SUMMARY.md with progress

**Status**: READY TO EXECUTE
**Next**: Start with HTML_SUBMIT extraction

