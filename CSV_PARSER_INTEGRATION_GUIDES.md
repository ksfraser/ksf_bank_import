# CSV Parser Integration Guides

**Status**: Ready to Implement (After Phase 1 & 2)  
**Parsers**: 4 LOCAL implementations  
**Total Effort**: 8-14 hours  
**Priority**: Medium (Phase 3)

---

## 📋 CSV Parsers Overview

| Parser | File | Complexity | Effort | Fields |
|--------|------|-----------|--------|--------|
| **ING** | `includes/ro_ing_csv_parser.php` | Low | 2-3 hrs | Date, Type, Description, Amount |
| **WMMC (Walmart Mastercard)** | `includes/ro_wmmc_csv_parser.php` | Low | 2-3 hrs | Date, Description, Amount |
| **BCR** | `includes/ro_bcr_csv_parser.php` | Low | 2-3 hrs | Date, Type, Description, Amount |
| **BRD MT940** | `includes/ro_brd_mt940_parser.php` | High | 3-5 hrs | MT940 format (most complex) |

---

## 🎯 Common Implementation Pattern

All CSV parsers follow similar integration pattern:

1. **Identify merchant/description field** in parse() method
2. **Populate transaction object** with merchant data
3. **Call contact extraction** before adding to statement
4. **Handle errors gracefully**

---

## 📍 ING CSV Parser

### File: `/includes/ro_ing_csv_parser.php`

### Current Structure (Lines 1-150)

The parser:
- Reads CSV lines (header skipped)
- Parses Romanian date format: "24 martie 2024" → "2024-03-24"
- Extracts transaction type from field [1]
- Extracts amount from fields [2] or [3]
- Extracts description from available fields

### Merchant Data Location

**CSV Format** (approx):
```
Data | Tip operatiune | Debit | Credit | Beneficiar/Descriere | ...
24 martie 2024 | Transfer domestic | 1000 | | COMPANY NAME SRL | ...
```

The description/beneficiary is typically in later CSV fields. Need to identify exact field.

### Integration Steps

1. **Find where transaction title is set** (search for `transactionTitle1` or `account` in parse method)
2. **Identify the merchant/description field** from CSV structure
3. **Add extraction call** before `$smts[$sid]->addTransaction($trz);`

### Code Template

```php
// In parse() method, before adding transaction to statement:

// CONTACT EXTRACTION - ING CSV
$this->extractContactForTransaction($trz);
$smts[$sid]->addTransaction($trz);  // EXISTING

// Add this method to class:
private function extractContactForTransaction($trz): void
{
    if (empty($trz->merchant) && empty($trz->account)) {
        return;
    }

    try {
        if (!isset($GLOBALS['db'])) {
            return;
        }

        $db = $GLOBALS['db'];

        if (!class_exists('\Ksfraser\FaBankImport\Services\ContactService')) {
            return;
        }

        $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
        $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);

        $merchant = $trz->merchant ?: ($trz->account ?? '');
        if (empty($merchant)) {
            return;
        }

        $contactType = ($trz->transactionDC === 'D') ? 'supplier' : 'customer';

        $contactData = new \Ksfraser\Contact\DTO\ContactData([
            'name' => (string) $merchant,
            'contact_type' => $contactType,
        ]);

        $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
        if ($contact && !empty($contact->id)) {
            $trz->contact_id = (int) $contact->id;
        }

    } catch (\Throwable $e) {
        @error_log('ING CSV parser: Contact extraction failed: ' . $e->getMessage());
    }
}
```

### Testing

- [ ] Test with ING CSV export
- [ ] Verify contacts created
- [ ] Test deduplication (same merchant multiple times)

---

## 📍 Walmart Mastercard (WMMC) CSV Parser

### File: `/includes/ro_wmmc_csv_parser.php`

### Current Structure

The parser handles Walmart Mastercard CSV format with:
- Transaction dates
- Descriptions (merchant names)
- Amounts (usually single column)

### Merchant Data Location

**CSV Format** (approx):
```
Date | Reference | Description/Merchant | Amount | ...
2024-03-24 | TXN123 | AMAZON MARKETPLACE | 99.99 | ...
```

Description/merchant typically in field [2] or [3].

### Integration Steps

1. Open `ro_wmmc_csv_parser.php`
2. Search for where `$trz->merchant` or `$trz->account` is set
3. That field is your merchant data source
4. Add extraction call before `$smts[$sid]->addTransaction($trz);`

### Code Template

Same as ING (use the template above, just copy-paste the method)

```php
// Before addTransaction line:
$this->extractContactForTransaction($trz);
$smts[$sid]->addTransaction($trz);
```

### Testing

- [ ] Test with Walmart Mastercard CSV export
- [ ] Verify credit card transactions (CREDIT type) create customer contacts
- [ ] Verify payments (if any DEBIT) create supplier contacts

---

## 📍 BCR CSV Parser

### File: `/includes/ro_bcr_csv_parser.php`

### Current Structure

BCR (Banca Comerciala Romana) CSV format parser.

Similar to ING and WMMC, but with BCR-specific field layout.

### Integration Steps

Identical to ING and WMMC:

1. Find merchant data field in parse()
2. Add extraction call before addTransaction
3. Copy the `extractContactForTransaction()` method

### Code Pattern (All CSV Parsers)

All four CSV parsers use this same base pattern:

```php
// At transaction completion, before: $smts[$sid]->addTransaction($trz);

$this->extractContactForTransaction($trz);  // NEW LINE
$smts[$sid]->addTransaction($trz);          // EXISTING LINE
```

And all four parsers add this same method:

```php
private function extractContactForTransaction($trz): void
{
    // ... (same code as ING template above)
}
```

---

## 📍 BRD MT940 Parser (Most Complex)

### File: `/includes/ro_brd_mt940_parser.php`

### What is MT940?

MT940 is an international bank statement format (ISO 20022).
- More structured than CSV
- Multiple data fields per transaction
- Includes settlement dates, booking dates, transaction codes

**Complexity**: Higher because:
- Merchant data spread across multiple fields
- More parsing required
- Potential for missing/optional fields

### Current Structure

`ro_brd_mt940_parser.php` extends `mt940_parser` which handles core parsing.

### Merchant Data Location

In MT940 format:
- **Field 60**: Balance opening
- **Field 20**: Reference
- **Field 25**: Account identification
- **Field 28C**: Statement number
- **Field 61**: Transaction details
  - Transaction date
  - Booking date
  - Amount
  - Type (Debit/Credit)
  - Reference
- **Field 86**: Additional details (often contains merchant/description)

### Integration Steps

1. Open `ro_brd_mt940_parser.php`
2. Check parsing logic for how transactions are built
3. Identify where transaction description/merchant is populated
4. Add extraction call at transaction completion
5. Add `extractContactForTransaction()` method (same as CSV parsers)

### Code Pattern

```php
// Same pattern as CSV parsers
$this->extractContactForTransaction($trz);
$smts[$sid]->addTransaction($trz);

// Same extraction method
private function extractContactForTransaction($trz): void { ... }
```

### Complexity Notes

- MT940 may have empty merchant fields (use transaction code as fallback)
- Multiple detail lines per transaction (consolidate)
- Special characters in merchant names (sanitization handled by ContactService)

---

## 🔄 Implementation Sequence

### Recommended Order

1. **ING** (2-3 hrs) - Simple, straightforward
2. **WMMC** (2-3 hrs) - Usually simple format
3. **BCR** (2-3 hrs) - Similar to ING
4. **BRD MT940** (3-5 hrs) - Most complex, do last

**Why this order**: 
- Learn pattern with simple CSV parsers
- Apply knowledge to complex MT940
- Parallel testing (multiple CSV samples)

---

## 🛠️ Generic CSV Parser Integration Checklist

For each CSV parser:

- [ ] **Step 1**: Open `/includes/[parser].php`
- [ ] **Step 2**: Find `parse()` method
- [ ] **Step 3**: Identify where merchant/description data is assigned to transaction object
- [ ] **Step 4**: Locate line with `$smts[$sid]->addTransaction($trz);`
- [ ] **Step 5**: Add `$this->extractContactForTransaction($trz);` immediately before
- [ ] **Step 6**: Add `extractContactForTransaction()` private method to class (before closing brace)
- [ ] **Step 7**: Test with sample CSV file
- [ ] **Step 8**: Verify contacts created and linked
- [ ] **Step 9**: Commit with conventional commit

---

## 💻 Code Location Reference

### All CSV Parser Files
```
/includes/ro_ing_csv_parser.php         Line ??: $smts[$sid]->addTransaction
/includes/ro_wmmc_csv_parser.php        Line ??: $smts[$sid]->addTransaction
/includes/ro_bcr_csv_parser.php         Line ??: $smts[$sid]->addTransaction
/includes/ro_brd_mt940_parser.php       Line ??: $smts[$sid]->addTransaction
```

To find the exact line, search for: `addTransaction`

### Generic Search Commands

In each file, search for:
- `$smts[` 
- `addTransaction`
- `transactionTitle1` or `account` (to find merchant assignment)

---

## 🧪 Testing Each Parser

### Test Data

For each parser, prepare a real export file or sample:

```
ro_ing_csv_parser:      Sample ING CSV (Romania)
ro_wmmc_csv_parser:     Sample Walmart Mastercard CSV
ro_bcr_csv_parser:      Sample BCR CSV (Romania)
ro_brd_mt940_parser:    Sample BRD MT940 statement (Romania)
```

### Test Procedure

1. Login to import_statements.php
2. Select parser type
3. Select bank account (or auto-detect if available)
4. Upload sample file
5. Submit import
6. Verify:
   - Import completes (no parse errors)
   - At least 1 contact created in `0_bi_contact`
   - Transactions have `contact_id` populated
   - Same merchant appears once (deduplication)

### Expected Results

After import of CSV with 5 transactions to 3 unique merchants:
- `0_bi_contact`: 3 rows (one per unique merchant)
- `0_bi_transactions`: 5 new rows, each with `contact_id` set
- Deduplication working: Merchant appearing 2x → 1 contact, 2 transactions

---

## 📋 Master Checklist

Complete these for each parser to mark as DONE:

- [ ] ING CSV: Integrated
- [ ] ING CSV: Tested
- [ ] ING CSV: Committed
- [ ] WMMC CSV: Integrated
- [ ] WMMC CSV: Tested
- [ ] WMMC CSV: Committed
- [ ] BCR CSV: Integrated
- [ ] BCR CSV: Tested
- [ ] BCR CSV: Committed
- [ ] BRD MT940: Integrated
- [ ] BRD MT940: Tested
- [ ] BRD MT940: Committed

---

## 🚀 Getting Started

**Pick one CSV file to complete first**:

Recommendation: Start with **ING** (typically simplest)

```
1. Open:  /includes/ro_ing_csv_parser.php
2. Search for: "addTransaction"
3. Find that line
4. Add extraction call before it
5. Add extraction method
6. Test
7. Commit
8. Move to next parser
```

**Estimated total time**: 8-14 hours = 1-2 workdays

---

## 🔗 Related Documentation

- [PARSER_CONTACT_EXTRACTION_IMPLEMENTATION_PLAN.md](PARSER_CONTACT_EXTRACTION_IMPLEMENTATION_PLAN.md)
- [QIF_PARSER_INTEGRATION_GUIDE.md](QIF_PARSER_INTEGRATION_GUIDE.md)
- [qfx_parser.php implementation](includes/qfx_parser.php) - Reference pattern
- [ContactService](src/Ksfraser/FaBankImport/Services/ContactService.php)
- [ContactDeduplicationService](src/Ksfraser/FaBankImport/Services/ContactDeduplicationService.php)

---

**Status**: ✅ Ready to implement (after Phase 1 & 2)  
**Last updated**: 2026-03-22
