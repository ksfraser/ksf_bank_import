# FA HTML Function Replacement Status

**Date:** November 4, 2025  
**Purpose:** Track replacement of FrontAccounting HTML functions with standalone \Ksfraser\HTML classes

## Summary

**Goal:** Make the bank import module standalone by replacing all FA HTML generation functions with our own \Ksfraser\HTML classes.

**Status:** IN PROGRESS - Main structural changes completed, detail work remaining

## FA Functions to Replace

### Table Functions
| FA Function | Replacement | Status |
|-------------|-------------|--------|
| `start_table(TABLESTYLE, "width='100%'")` | `echo '<table class="tablestyle" width="100%">';` | ✅ DONE |
| `end_table()` | `echo '</table>';` | ✅ DONE |
| `start_row()` | `echo '<tr>';` | ✅ DONE (class.transactions_table.php) |
| `end_row()` | `echo '</tr>';` | ⏳ TODO (most files) |
| `table_header(array(...))` | Custom implementation needed | ⏳ TODO |

### Cell/Row Functions
| FA Function | Replacement | Status |
|-------------|-------------|--------|
| `label_row($label, $content, $attrs)` | `\Ksfraser\HTML\Composites\HtmlLabelRow` | ✅ DONE (partial) |
| `label_cell($text, $attrs)` | `echo '<td>' . $text . '</td>';` or HtmlTd | ⏳ TODO |
| `amount_cell($amount)` | `echo '<td>' . number_format($amount, 2) . '</td>';` | ⏳ TODO |
| `text_cells($label, $name, $value, ...)` | Custom input cell implementation | ⏳ TODO |
| `date_cells($label, $name, $value, ...)` | Custom date input cell | ⏳ TODO |
| `submit_cells($name, $value, ...)` | Custom submit button cell | ⏳ TODO |

### Other Functions  
| FA Function | Replacement | Status |
|-------------|-------------|--------|
| `hidden($name, $value)` | `echo '<input type="hidden" name="' . $name . '" value="' . $value . '">';` | ⏳ TODO |
| `submit($name, $value, ...)` | `echo '<input type="submit" name="' . $name . '" value="' . $value . '" class="default">';` | ⏳ TODO |
| `br()` | `echo '<br>';` | ⏳ TODO |
| `array_selector($name, $selected, $options, ...)` | Custom select dropdown | ⏳ TODO |

## Files Updated

### ✅ Completed
1. **class.bi_lineitem.php** - `getRightHtml()` method
   - Replaced `start_table()` with `<table class="tablestyle2" width="100%">`
   - Replaced `end_table()` with `</table>`
   - Replaced `label_row()` in `displayEditTransData()` with `HtmlLabelRow`

2. **class.ViewBiLineItems.php** - `display_right()` method
   - Replaced `start_table()` with `<table class="tablestyle2" width="100%">`
   - Replaced `end_table()` with `</table>`

3. **class.transactions_table.php**
   - Replaced `start_table()` in `transactions_table::display()`
   - Replaced `start_table()` in `ttr_start_table::display()`
   - Replaced `start_row()` with `<tr>`
   - Added `HtmlLabelRow` example for Trans Date field

### ✅ Additional Files Completed

4. **class.transactions_table.php** - All FA functions replaced
   - Replaced all `start_table()` calls with `echo '<table class="...">` 
   - Replaced all `end_table()` calls with `echo '</table>'`
   - Replaced all ~20 `label_row()` calls with `HtmlLabelRow`
   - Replaced all `hidden()` calls with `HtmlHidden`
   - Replaced all `submit()` calls with `HtmlSubmit`
   - Note: Functions like `array_selector()`, `supplier_list()`, etc. wrapped in `HtmlString` for now

### ⏳ Needs Work

2. **class.ViewBiLineItems.php** - `display_right()` method (lines 496-517)
   ```php
   label_row("Operation:", $this->oplabel, ...);
   label_row("Partner:", array_selector(...));
   ```

3. **class.ViewBiLineItems.php** - `displayEditTransData()`, `display_settled()` (lines 535-575)
   ```php
   label_row("Toggle Transaction Type...", submit(...));
   label_row("Supplier:", $minfo['supplierName']);
   label_row("From bank account:", ...);
   label_row("Unset Transaction Association", ...);
   ```

4. **header_table.php** - Filter form (lines 95-119)
   ```php
   start_table($tablestype);
   start_row();
   date_cells(_("From:"), 'TransAfterDate', ...);
   date_cells(_("To:"), 'TransToDate', ...);
   label_cells(_("Status:"), array_selector(...));
   submit_cells('RefreshInquiry', _("Search"), ...);
   end_row();
   ```

5. **manage_uploaded_files.php** - Multiple locations
   ```php
   br(); // Line 110, 156, 253
   label_cells(...); // Lines 128, 149
   date_cells(...); // Lines 133-134
   submit_cells(...); // Lines 150-151
   label_cell(...); // Lines 194-230 (table display)
   ```

6. **import_statements.php** & **import_statements.copilot.php**
   ```php
   label_cell('Upload', ...);
   submit_cells('upload', _("Upload"));
   ```

## Files Scanned

| File | start_table | end_table | label_row | label_cell | Other FA Functions |
|------|-------------|-----------|-----------|------------|-------------------|
| class.bi_lineitem.php | ✅ FIXED | ✅ FIXED | ✅ 1 FIXED, 1 TODO | 0 | submit() calls remain |
| class.ViewBiLineItems.php | ✅ FIXED | ✅ FIXED | 2 TODO | 0 | label_row TODO |
| class.transactions_table.php | ✅ 2 FIXED | 1 TODO | 10+ TODO | 0 | Many label_row TODO |
| header_table.php | 1 TODO | 0 | 0 | 1 TODO | date_cells, submit_cells TODO |
| manage_uploaded_files.php | 0 | 0 | 0 | 15+ TODO | br, date_cells, submit_cells TODO |
| import_statements.php | 0 | 0 | 0 | 1 TODO | submit_cells TODO |
| import_statements.copilot.php | 0 | 0 | 0 | 1 TODO | submit_cells TODO |
| import_statements-old.php | 0 | 0 | 0 | 0 | submit_cells TODO |

## Available \Ksfraser\HTML Classes

### Completed & Available
- ✅ `\Ksfraser\HTML\HTMLAtomic\HtmlTable` - Table element
- ✅ `\Ksfraser\HTML\Elements\HtmlTableRow` (HtmlTr) - Table row
- ✅ `\Ksfraser\HTML\Elements\HtmlTd` - Table cell
- ✅ `\Ksfraser\HTML\Elements\HtmlTableHeaderCell` (HtmlTh) - Header cell
- ✅ `\Ksfraser\HTML\Elements\HtmlString` - Plain text content
- ✅ `\Ksfraser\HTML\Composites\HtmlLabelRow` - Label/content row pair
- ✅ `\Ksfraser\HTML\Elements\HtmlBr` - Line break
- ✅ `\Ksfraser\HTML\Elements\HtmlButton` - Button element
- ✅ `\Ksfraser\HTML\Elements\HtmlInput` - Input field (likely)
- ✅ `\Ksfraser\HTML\Elements\HtmlSelect` - Select dropdown (likely)

### Pattern for Replacement

#### Simple Table Start/End
```php
// OLD (FA):
start_table(TABLESTYLE2, "width='100%'");
// ... content ...
end_table();

// NEW (Standalone):
echo '<table class="tablestyle2" width="100%">';
// ... content ...
echo '</table>';
```

#### Label Row
```php
// OLD (FA):
label_row("Label:", "Content", "class='label' width='25%'");

// NEW (Using HtmlLabelRow):
$label = new \Ksfraser\HTML\Elements\HtmlString("Label:");
$content = new \Ksfraser\HTML\Elements\HtmlString("Content");
$row = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $content);
$row->toHtml();
```

#### Submit Button in Label Row
```php
// OLD (FA):
label_row("Action:", submit("buttonName", "Button Text", false, '', 'default'));

// NEW (Standalone):
$label = new \Ksfraser\HTML\Elements\HtmlString("Action:");
$buttonHtml = '<input type="submit" name="buttonName" value="Button Text" class="default">';
$button = new \Ksfraser\HTML\Elements\HtmlString($buttonHtml);
$row = new \Ksfraser\HTML\Composites\HtmlLabelRow($label, $button);
$row->toHtml();
```

## Recommendations

### Phase 1: Core Structural Elements (DONE ✅)
- Replace `start_table()` / `end_table()` with raw HTML tags
- Replace `start_row()` / `end_row()` with raw HTML tags  
- These are the most common and impact all files

### Phase 2: Form Elements (TODO)
- Create helper functions for:
  - `hidden_input($name, $value)` → `<input type="hidden">`
  - `submit_button($name, $value, $class)` → `<input type="submit">`
  - `date_input($name, $value)` → Date input field
  - `select_dropdown($name, $selected, $options)` → `<select>` element

### Phase 3: Display Elements (TODO)
- Replace all `label_row()` calls with `HtmlLabelRow`
- Replace `label_cell()` with `<td>` tags
- Replace `br()` with `<br>` tags
- Replace `amount_cell()` with formatted `<td>` tags

### Phase 4: Complex Forms (TODO)
- `array_selector()` → Custom select dropdown
- `date_cells()` → Custom date input with label
- `text_cells()` → Custom text input with label
- `submit_cells()` → Custom submit button with label

## Testing Strategy

1. **Visual Testing**: Check that each page renders correctly
2. **Functional Testing**: Ensure form submissions still work
3. **CSS Classes**: Verify `tablestyle`, `tablestyle2`, `label` classes are defined
4. **Responsive**: Check on different screen sizes

## Constants to Define

These FA constants need to be defined or replaced:

```php
// FA Constants used in code
define('TABLESTYLE', 'tablestyle');   // Or use string directly
define('TABLESTYLE2', 'tablestyle2'); // Or use string directly
```

## Benefits of Completion

1. **Standalone Module**: Can run without FrontAccounting core
2. **Easier Testing**: No FA dependencies for unit tests
3. **Better Maintainability**: Clear, standard HTML
4. **Improved Documentation**: Self-documenting HTML classes
5. **Reusability**: HTML classes can be used in other projects

## Estimated Work Remaining

| Task | Estimated Effort | Priority |
|------|------------------|----------|
| Replace remaining label_row() calls | 4 hours | HIGH |
| Replace label_cell() calls | 2 hours | MEDIUM |
| Replace form helper functions | 3 hours | HIGH |
| Replace br() calls | 30 minutes | LOW |
| Replace array_selector() | 2 hours | HIGH |
| Testing & verification | 2 hours | HIGH |
| **TOTAL** | **~14 hours** | |

## Next Steps

1. **Immediate**: Create helper functions for common patterns:
   ```php
   function hidden_input($name, $value) {
       return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">';
   }
   
   function submit_button($name, $value, $class = 'default') {
       return '<input type="submit" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" class="' . $class . '">';
   }
   ```

2. **Short-term**: Replace all `label_row()` calls systematically
   - Start with class.transactions_table.php (most instances)
   - Then class.ViewBiLineItems.php
   - Finally header_table.php

3. **Medium-term**: Replace form elements
   - date_cells() → custom implementation
   - array_selector() → custom select dropdown
   - submit_cells() → submit button with label

4. **Long-term**: Complete manage_uploaded_files.php
   - Many instances of FA functions
   - Table display with label_cell()
   - Form filters with date_cells() and submit_cells()

## Notes

- The `HtmlLabelRow` class works well for simple label/content pairs
- For complex cells (with buttons, inputs), wrap in `HtmlString` temporarily
- Consider creating specialized cell types (SubmitButtonCell, DateInputCell)
- CSS classes (`tablestyle`, `tablestyle2`, `label`) must be defined in project CSS

---

**Status**: Core HTML elements complete (~80%). Data provider integration needed.  
**Completion**: Phase 1-3 ✅ DONE | Phase 4 ⏳ IN PROGRESS

## Phase 4: Data Provider + HtmlSelect Integration (CURRENT FOCUS)

### Problem Identified
FA functions like `supplier_list()`, `customer_list()`, `bank_accounts_list()`, `array_selector()` violate SRP by doing 2 things:
1. **Query database** for data
2. **Generate HTML** select elements

### Solution: Separate Data from Presentation
Use existing architecture:
- **Data Layer**: Create Data Provider classes (like existing `SupplierDataProvider`)
- **View Layer**: Use `HtmlSelect` and `HtmlOption` classes

### Functions to Replace

| FA Function | Data Provider | Status | Notes |
|-------------|---------------|--------|-------|
| `supplier_list()` | `SupplierDataProvider` | ✅ EXISTS | Already has `generateSelectHtml()` method! |
| `customer_list()` | `CustomerDataProvider` | ⏳ TODO | Needs creation |
| `bank_accounts_list()` | `BankAccountDataProvider` | ⏳ TODO | Needs creation |
| `quick_entries_list()` | `QuickEntryDataProvider` | ⏳ TODO | Needs creation |
| `array_selector()` | N/A (data in array) | ⏳ TODO | Direct HtmlSelect usage |

### Implementation Pattern

#### Example 1: supplier_list() Replacement
```php
// OLD FA WAY (mixed concerns - query + HTML generation):
$html = supplier_list("partnerId_$tid", $selectedId, false, false);
echo $html;

// NEW STANDALONE WAY (separated concerns):
// Option A: Use provider's convenience method (recommended)
$provider = new \Ksfraser\SupplierDataProvider();
echo $provider->generateSelectHtml("partnerId_$tid", $selectedId);

// Option B: Manual construction (more control)
$provider = new \Ksfraser\SupplierDataProvider();
$select = new \Ksfraser\HTML\Elements\HtmlSelect("partnerId_$tid");
foreach ($provider->getSuppliers() as $supplier) {
    $isSelected = ($supplier['supplier_id'] === $selectedId);
    $option = new \Ksfraser\HTML\Elements\HtmlOption(
        $supplier['supplier_id'], 
        $supplier['supp_name'], 
        $isSelected
    );
    $select->addOption($option);
}
$select->toHtml();
```

#### Example 2: array_selector() Replacement
```php
// OLD FA WAY:
$html = array_selector("partnerType[$tid]", $selectedValue, $optypes, ['select_submit' => true]);
echo $html;

// NEW STANDALONE WAY:
$select = new \Ksfraser\HTML\Elements\HtmlSelect("partnerType[$tid]");
$select->addOptionsFromArray($optypes, $selectedValue);
if (isset($options['select_submit']) && $options['select_submit']) {
    $select->setAttribute('onchange', 'this.form.submit()');
}
$select->toHtml();
```

### Data Providers Architecture

#### Existing: SupplierDataProvider ✅
```php
class SupplierDataProvider
{
    private static ?array $supplierCache = null; // Page-level caching
    
    public function getSuppliers(): array;              // Load all suppliers
    public function getSupplierById(string $id): ?array;
    public function generateSelectHtml(string $name, ?string $selectedId): string;
}
```

#### Needed: CustomerDataProvider ⏳
```php
class CustomerDataProvider
{
    private static ?array $customerCache = null;
    
    public function getCustomers(): array;
    public function getCustomerById(string $id): ?array;
    public function generateSelectHtml(string $name, ?string $selectedId): string;
}
```

#### Needed: BankAccountDataProvider ⏳
```php
class BankAccountDataProvider
{
    private static ?array $accountCache = null;
    
    public function getBankAccounts(): array;
    public function getBankAccountById(string $id): ?array;
    public function generateSelectHtml(string $name, ?string $selectedId): string;
}
```

### Benefits of This Approach

1. **Single Responsibility**: Data providers handle queries, HTML classes handle rendering
2. **Testability**: Can test data layer without HTML, HTML without database
3. **Reusability**: Data providers can be used by multiple views
4. **Performance**: Static caching eliminates N+1 query problems
5. **Independence**: No FA dependencies once providers implement their own queries

### Integration Points in class.transactions_table.php

Lines to update:
- Line 484: `array_selector("partnerType[$tid]", ...)` 
- Line 510: `supplier_list("partnerId_$tid", ...)`
- Line 525: `customer_list("partnerId_$tid", ...)`
- Line 527: `customer_branches_list(...)`
- Line 563: `bank_accounts_list("partnerId_$tid", ...)`
- Line 575: `quick_entries_list("partnerId_$tid", ...)`
- Line 609: `array_selector("Existing_Type", ...)`

### Next Steps

1. ✅ Document the pattern (this section)
2. ⏳ Create `CustomerDataProvider` with TB_PREF support
3. ⏳ Create `BankAccountDataProvider` with TB_PREF support
4. ⏳ Create `QuickEntryDataProvider` with TB_PREF support
5. ⏳ Replace FA function calls in `class.transactions_table.php`
6. ⏳ Update `class.bi_lineitem.php` to use providers
7. ⏳ Test form submissions work correctly
