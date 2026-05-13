# HTML Class Library Consolidation Plan

**Date**: November 6, 2025  
**Priority**: CRITICAL  
**Status**: Planning Phase

## Executive Summary

Major architectural issue discovered: HTML classes duplicated across 3 directories with inconsistent implementations. This violates DRY principle and creates maintenance nightmare. Need immediate consolidation into single authoritative source.

## Problem Statement

### Current State
- **views/HTML/**: 129 HTML class files (likely original location)
- **src/Ksfraser/HTML/**: 99 HTML class files (intended authoritative location)
- **src/Ksfraser/FaBankImport/views/HTML/**: 140 HTML class files (legacy copy?)

### Issues
1. **Massive Duplication**: Same classes exist in 3+ locations with potentially different implementations
2. **No Single Source of Truth**: Unclear which version is correct/current
3. **Maintenance Burden**: Bug fixes must be applied 3 times
4. **Testing Gaps**: Tests may be testing wrong version
5. **Submodule Not Used**: `src/Ksfraser/HTML/` was intended to be git submodule from `github.com/ksfraser/HTML` but isn't
6. **Hardcoded HTML Still Exists**: Files like `class.ViewBiLineItems.php` still use FA functions and hardcoded HTML strings

## Code Examples of Problems

### class.ViewBiLineItems.php (Line 34)
```php
// WRONG - Hardcoded HTML
echo '<td width="50%">';
$table = new HTML_TABLE( null, 100 );
```

Should be:
```php
// CORRECT - Proper HtmlTd object
$table = new HtmlTable($content);
$table->addAttribute(new HtmlAttribute('width', '100%'));
$td = new HtmlTd($table);
$td->addAttribute(new HtmlAttribute('width', '50%'));
```

### class.ViewBiLineItems.php (Lines 148-150)
```php
// WRONG - FA functions
label_row(_("Payment To:"), supplier_list("partnerId_$this->id", $matched_supplier, false, false));
```

Should be:
```php
// CORRECT - DataProvider + HtmlSelect
$supplierProvider = new \Ksfraser\SupplierDataProvider();
$supplierSelectHtml = $supplierProvider->generateSelectHtml("partnerId_$this->id", $matched_supplier);
$label = new HtmlString(_("Payment To:"));
$content = new HtmlString($supplierSelectHtml);
$labelRow = new HtmlLabelRow($label, $content);
$labelRow->toHtml();
```

### class.ViewBiLineItems.php (Lines 463-465)
```php
// WRONG - Hardcoded table tags
echo '<table class="tablestyle2" width="100%">';
// ... content ...
echo '</table>';
```

Should be:
```php
// CORRECT - HtmlTable with automatic closing
$tableContent = new HtmlRaw($content);
$table = new HtmlTable($tableContent);
$table->addAttribute(new HtmlAttribute('class', 'tablestyle2'));
$table->addAttribute(new HtmlAttribute('width', '100%'));
$table->toHtml(); // Automatically renders opening AND closing tags
```

## Consolidation Strategy

### Phase 1: Inventory & Analysis (CURRENT PHASE)
**Goal**: Understand what exists, where, and which version is canonical

**Tasks**:
1. ✅ Count files in each directory (DONE: 129, 99, 140)
2. ⏳ Create file comparison matrix
3. ⏳ Identify which directory has most recent/correct code
4. ⏳ List files unique to each directory
5. ⏳ Check test coverage for each file
6. ⏳ Check PHPDoc completeness
7. ⏳ Identify dependencies on FrontAccounting

**Deliverable**: `HTML_CLASS_INVENTORY.md` with complete file listing

### Phase 2: W3Schools Element Coverage
**Goal**: Ensure all HTML5 elements from W3Schools have corresponding classes

**Reference**: https://www.w3schools.com/html/

**HTML5 Elements to Check**:
- **Basic**: `<html>`, `<head>`, `<title>`, `<body>`, `<h1>`-`<h6>`, `<p>`, `<br>`, `<hr>`
- **Formatting**: `<b>`, `<strong>`, `<i>`, `<em>`, `<mark>`, `<small>`, `<del>`, `<ins>`, `<sub>`, `<sup>`
- **Forms**: `<form>`, `<input>`, `<textarea>`, `<button>`, `<select>`, `<option>`, `<label>`, `<fieldset>`, `<legend>`
- **Lists**: `<ul>`, `<ol>`, `<li>`, `<dl>`, `<dt>`, `<dd>`
- **Tables**: `<table>`, `<caption>`, `<th>`, `<tr>`, `<td>`, `<thead>`, `<tbody>`, `<tfoot>`, `<col>`, `<colgroup>`
- **Semantic**: `<header>`, `<nav>`, `<main>`, `<article>`, `<section>`, `<aside>`, `<footer>`, `<details>`, `<summary>`
- **Media**: `<img>`, `<picture>`, `<audio>`, `<video>`, `<source>`, `<track>`, `<canvas>`, `<svg>`
- **Links**: `<a>`, `<link>`
- **Meta**: `<meta>`, `<base>`, `<style>`, `<script>`, `<noscript>`
- **Inline**: `<span>`, `<div>`, `<iframe>`, `<embed>`, `<object>`, `<param>`

**Deliverable**: `HTML_ELEMENT_COVERAGE_MATRIX.md` showing implemented vs missing

### Phase 3: Determine Canonical Source
**Goal**: Identify which directory has the "correct" implementation

**Criteria**:
1. Most recent file modification dates
2. Best code quality (SOLID principles, no hardcoded HTML)
3. Complete test coverage
4. Complete PHPDoc
5. Follows established patterns (HtmlElement base class, automatic tag rendering)

**Hypothesis**: `src/Ksfraser/HTML/` is likely canonical based on:
- Proper namespace (`Ksfraser\HTML\`)
- Intended git submodule location
- Recent refactoring work references this location

**Verification Steps**:
```powershell
# Compare file dates
Get-ChildItem -Path "src\Ksfraser\HTML\Elements" -Recurse | 
    Select-Object Name, LastWriteTime | 
    Sort-Object LastWriteTime -Descending | 
    Select-Object -First 20

# Compare with views/HTML
Get-ChildItem -Path "views\HTML" | 
    Select-Object Name, LastWriteTime | 
    Sort-Object LastWriteTime -Descending | 
    Select-Object -First 20
```

**Deliverable**: Decision document on canonical source

### Phase 4: Migrate Unique Classes
**Goal**: Ensure all unique/useful code from other directories is preserved

**Process**:
1. Identify files unique to `views/HTML/`
2. Identify files unique to `src/Ksfraser/FaBankImport/views/HTML/`
3. For each unique file:
   - Review code quality
   - Check if functionality already exists in canonical location
   - If unique and useful: migrate to `src/Ksfraser/HTML/`
   - If duplicate: discard
   - If deprecated: document and discard
4. Update namespaces to `Ksfraser\HTML\` or `Ksfraser\HTML\Elements\`
5. Ensure PSR-4 autoloading works

**Deliverable**: All unique classes migrated to `src/Ksfraser/HTML/`

### Phase 5: Update All require_once Paths
**Goal**: Point all code to use canonical source

**Search Patterns**:
```regex
require_once.*views/HTML/Html.*\.php
require_once.*FaBankImport/views/HTML/Html.*\.php
```

**Replace With**:
```php
require_once( __DIR__ . '/src/Ksfraser/HTML/Elements/HtmlXxx.php' );
// Or better: use composer autoloading
use Ksfraser\HTML\Elements\HtmlXxx;
```

**Files to Update**:
- `class.bi_lineitem.php`
- `class.ViewBiLineItems.php`
- `class.transactions_table.php`
- All View classes in `Views/` directory
- Any other files using HTML classes

**Deliverable**: All require_once updated, old directories ready for deletion

### Phase 6: Test Coverage
**Goal**: Ensure 100% test coverage before deletion

**Process**:
1. Run all existing tests: `vendor\bin\phpunit tests\unit\ --testdox`
2. Identify untested classes
3. Create tests using TDD approach
4. Achieve minimum 80% code coverage
5. Document test patterns for new contributors

**Deliverable**: Complete test suite with ≥80% coverage

### Phase 7: PHPDoc Completion
**Goal**: Every class fully documented

**Standards**:
```php
/**
 * Represents an HTML <element> tag
 *
 * Provides methods for setting attributes, adding children, and rendering
 * the element with automatic opening/closing tag generation.
 *
 * @package Ksfraser\HTML\Elements
 * @author Kevin Fraser
 * @since YYYY-MM-DD
 * 
 * @example
 * $element = new HtmlElement($content);
 * $element->addAttribute(new HtmlAttribute('class', 'my-class'));
 * echo $element->getHtml(); // <element class="my-class">content</element>
 */
```

**Check List**:
- [ ] Class-level PHPDoc with description, @package, @author, @since, @example
- [ ] All public methods documented with @param, @return, @throws
- [ ] All protected methods documented
- [ ] Complex logic has inline comments
- [ ] Magic methods (__construct, __toString, etc.) documented

**Deliverable**: 100% PHPDoc coverage

### Phase 8: UML Documentation
**Goal**: Visual documentation of architecture

**Diagrams Needed**:
1. **Class Hierarchy Diagram**
   - HtmlElement base class
   - HtmlEmptyElement for void tags
   - Inheritance tree for all elements
   
2. **Interface Diagram**
   - HtmlElementInterface
   - Implementing classes
   
3. **Composition Diagram**
   - Container elements (HtmlTable, HtmlTr, HtmlDiv)
   - Child relationships (HtmlFragment)
   
4. **Sequence Diagram**
   - Recursive rendering process
   - How getHtml() calls children's getHtml()

**Tools**: PlantUML, Mermaid, or draw.io

**Deliverable**: `HTML_ARCHITECTURE.md` with all UML diagrams

### Phase 9: Delete Duplicate Directories
**Goal**: Remove technical debt

**Safety Checks**:
1. ✅ All tests passing
2. ✅ All require_once updated
3. ✅ No grep hits for old paths
4. ✅ Visual inspection of 5+ pages in FA works
5. ✅ Git commit with clear message

**Commands**:
```powershell
# Backup first!
git checkout -b consolidate-html-classes

# Delete duplicates (AFTER verification)
Remove-Item -Path "views\HTML" -Recurse -Force
Remove-Item -Path "src\Ksfraser\FaBankImport\views\HTML" -Recurse -Force

# Commit
git add -A
git commit -m "Consolidate HTML classes into src/Ksfraser/HTML/

- Removed duplicate directories: views/HTML/ and src/Ksfraser/FaBankImport/views/HTML/
- Updated all require_once paths to use src/Ksfraser/HTML/
- Verified all tests passing
- Documented architecture in HTML_ARCHITECTURE.md"
```

**Deliverable**: Clean directory structure, single source of truth

### Phase 10: Prepare for Git Submodule
**Goal**: Make `src/Ksfraser/HTML/` standalone repository

**Requirements**:
1. **No FA Dependencies**: Remove any `require_once` of FA files
2. **Standalone Tests**: Tests don't require FA database/functions
3. **Composer Support**: Create proper `composer.json`
4. **Documentation**: README with installation, usage, examples
5. **Licensing**: Add LICENSE file (MIT recommended)
6. **CI/CD**: GitHub Actions for automated testing

**Composer.json**:
```json
{
    "name": "ksfraser/html",
    "description": "Type-safe, object-oriented HTML generation library for PHP",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Kevin Fraser",
            "email": "kevin@example.com"
        }
    ],
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\HTML\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Ksfraser\\HTML\\Tests\\": "tests/"
        }
    }
}
```

**README.md Structure**:
```markdown
# Ksfraser HTML Library

Type-safe, object-oriented HTML generation for PHP.

## Features
- 🏗️ Container architecture with automatic tag closing
- 🔒 Type-safe with interfaces
- ♻️ DRY - no hardcoded HTML strings
- ✅ 100% test coverage
- 📖 Complete PHPDoc

## Installation
composer require ksfraser/html

## Usage
$div = new HtmlDiv($content);
$div->addClass('container');
echo $div->getHtml();
```

**Deliverable**: Ready for `git submodule add https://github.com/ksfraser/HTML src/Ksfraser/HTML`

### Phase 11: Fix class.ViewBiLineItems.php
**Goal**: Bring to same quality as class.bi_lineitem.php

**Issues Found**:
1. Hardcoded HTML: `echo '<td width="50%">'` (line 34)
2. Hardcoded HTML: `echo '<table class="tablestyle2" width="100%">'` (line 463)
3. FA function: `supplier_list()` (line 148)
4. FA function: `customer_list()` (line 162)
5. FA function: `bank_accounts_list()` (line 214)
6. FA function: `quick_entries_list()` (line 228)
7. FA function: `array_selector()` (line 241)
8. FA function: `label_row()` (used ~20 times)
9. FA function: `hidden()` (used ~15 times)
10. FA function: `submit()` (used ~5 times)

**Refactoring Pattern** (use same as class.bi_lineitem.php):
```php
// Before: supplier_list()
label_row(_("Payment To:"), supplier_list("partnerId_$this->id", $matched_supplier, false, false));

// After: SupplierDataProvider + HtmlLabelRow
$supplierProvider = new \Ksfraser\SupplierDataProvider();
$supplierSelectHtml = $supplierProvider->generateSelectHtml("partnerId_$this->id", $matched_supplier);
$label = new \Ksfraser\HTML\Elements\HtmlString(_("Payment To:"));
$content = new \Ksfraser\HTML\Elements\HtmlString($supplierSelectHtml);
$labelRow = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $content);
$labelRow->toHtml();
```

**Deliverable**: class.ViewBiLineItems.php with NO hardcoded HTML, NO FA functions

## Success Criteria

- [ ] Single authoritative source: `src/Ksfraser/HTML/`
- [ ] All W3Schools HTML5 elements have classes
- [ ] 100% test coverage (minimum 80%)
- [ ] 100% PHPDoc coverage
- [ ] Complete UML documentation
- [ ] No hardcoded HTML strings anywhere in codebase
- [ ] No FA HTML functions (supplier_list, customer_list, etc.)
- [ ] Ready for git submodule extraction
- [ ] All tests passing
- [ ] class.ViewBiLineItems.php matches quality of class.bi_lineitem.php

## Risks & Mitigation

**Risk**: Breaking existing functionality during consolidation  
**Mitigation**: 
- Work in feature branch
- Run full test suite after each change
- Manual testing of key pages
- Git allows rollback if needed

**Risk**: Losing important code from duplicate directories  
**Mitigation**: 
- Thorough inventory before deletion
- Keep git history (don't force delete)
- Code review before final commit

**Risk**: Tests passing but visual bugs  
**Mitigation**: 
- Manual visual inspection of 5+ key pages
- Screenshot comparison before/after
- Staging environment testing

## Timeline Estimate

- Phase 1 (Inventory): 2-4 hours
- Phase 2 (W3Schools): 2-3 hours  
- Phase 3 (Canonical Source): 1 hour
- Phase 4 (Migration): 4-6 hours
- Phase 5 (Update Paths): 2-3 hours
- Phase 6 (Tests): 8-12 hours
- Phase 7 (PHPDoc): 6-8 hours
- Phase 8 (UML): 4-6 hours
- Phase 9 (Delete): 1 hour
- Phase 10 (Submodule Prep): 4-6 hours
- Phase 11 (Fix ViewBiLineItems): 3-4 hours

**Total**: 37-56 hours (~1-1.5 weeks full-time)

## Next Steps

1. ✅ Create this plan document (DONE)
2. ⏳ Begin Phase 1: Create complete file inventory
3. ⏳ Share plan with team for review
4. ⏳ Create feature branch: `consolidate-html-classes`
5. ⏳ Execute phases sequentially with commits after each

## References

- W3Schools HTML Element Reference: https://www.w3schools.com/html/
- PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/
- PHPUnit Documentation: https://phpunit.de/
- PlantUML: https://plantuml.com/
- Git Submodules: https://git-scm.com/book/en/v2/Git-Tools-Submodules
