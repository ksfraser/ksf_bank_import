# Parser Contact Extraction Implementation Plan

**Status**: Ready for Implementation  
**Date**: March 22, 2026  
**Author**: Architecture Team  

---

## 📋 Executive Summary

This document provides detailed implementation guidance for integrating contact extraction into all bank import parsers. The parsers are divided into three categories:

1. **LOCAL Parsers** (you control): qfx_parser, qif_parser, CSV parsers
2. **EXTERNAL Package Wrappers**: ksf_ofxparser, qifparser, ksf_csvparser
3. **Not Your Responsibility**: Other packages (let their maintainers handle)

---

## 🎯 Key Insight: Import Flow Architecture

```
import_statements.php (line ~806)
    ↓
$selectedParser = user choice (e.g., "qfx", "qif", "ro_ing_csv")
$parserClass = $selectedParser . '_parser'  // e.g., "qfx_parser"
$parser = new $parserClass()  // Instantiate: qfx_parser, qif_parser, ro_ing_csv_parser, etc.
    ↓
parse_uploaded_files() - Line 902:
    $statements = $parser->parse($content, $static_data, $debug)
    ↓
Each parser's parse() receives:
    - $content: Raw file bytes (QFX text, QIF text, CSV lines)
    - $static_data: Bank account context (account #, currency, bank ID)
    - $debug: Boolean for debug output
```

### Key Implication for qifparser/qifparser vs qif_parser

**You already have a WRAPPER**: The file `/vendor/ksfraser/qifparser/qif_parser.php` is NOT the package itself—it's YOUR LOCAL WRAPPER that adapts the external `ksfraser/qifparser` package to match the bank_import interface.

This means you CAN modify qif_parser.php without touching the external package!

---

## 🔧 Implementation: Three Phases

### PHASE 1: LOCAL qfx_parser.php (Start Here → 2-3 hours)

**File**: `/includes/qfx_parser.php`

**Current State**: Lines 408-430 process merchant names:
```php
$shortname = shorten_bankAccount_Names( (string) $transaction->name );
$trz->account = $shortname;
$trz->accountName1 = (string) $transaction->name;
$trz->transactionTitle1 = (string) $transaction->name;
$trz->transactionTitle2 = (string) $transaction->sic;
$trz->sic = (string) $transaction->sic;
```

**Integration Strategy**:
1. Keep existing merchant processing (lines 408-430) unchanged
2. AFTER setting merchant data, add contact extraction
3. Extract merchant name (already in `$transaction->name`)
4. Determine customer vs supplier based on `$trz->transactionDC`
5. Call ContactDeduplicationService
6. Set `$trz->contact_id` if contact created

**Code to Add** (after line 430):
```php
// CONTACT EXTRACTION - Phase 1: qfx_parser integration
if (!empty($transaction->name)) {
    try {
        $contactService = new \Ksfraser\FaBankImport\Services\ContactService($GLOBALS['db']);
        $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);
        
        $contactData = new \Ksfraser\Contact\DTO\ContactData([
            'name' => (string) $transaction->name,
            'contact_type' => $trz->transactionDC === 'D' ? 'supplier' : 'customer',
            'sic' => !empty($transaction->sic) ? (string) $transaction->sic : null,
        ]);
        
        $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
        if ($contact && isset($contact->id)) {
            $trz->contact_id = (int) $contact->id;
        }
    } catch (\Throwable $e) {
        // Non-blocking: Log error but don't fail import
        error_log('QFX contact extraction failed: ' . $e->getMessage());
    }
}
```

**Testing**: 
- Test with QFX file containing known merchants
- Verify contacts are created in 0_bi_contact table
- Verify $trz->contact_id is populated in parsed transactions

---

### PHASE 2: LOCAL qif_parser.php Wrapper (2-3 hours)

**File**: `/vendor/ksfraser/qifparser/qif_parser.php` (YOUR WRAPPER, not the package!)

**Current State**: Lines 183-211 map QIF transactions:
```php
private function mapTransaction($qifTrx, string $accountId, string $bankId)
{
    $trz = new transaction();
    
    // ... existing code ...
    
    // Payee / merchant
    $payee = (string) ($qifTrx->payee ?? '');
    $trz->account = $payee;
    $trz->accountName = $payee;
    $trz->merchant = $payee;
    $trz->transactionTitle1 = $payee;
    
    // ... existing code ...
    
    return $trz;
}
```

**Integration Strategy**:
1. Modify `mapTransaction()` method to extract contacts
2. Same pattern as qfx_parser.php
3. Payee is in `$qifTrx->payee`
4. Direction is determined by `$trz->transactionDC`

**Code to Add** (after line 211, before `return $trz;` or create new method):
```php
// Extract contact from QIF payee
$this->extractContactForTransaction($trz, $qifTrx);
return $trz;
```

**New Method to Add**:
```php
/**
 * Extract/create contact for QIF transaction
 * @param transaction $trz
 * @param \Ksfraser\QifParser\Entities\QifTransaction $qifTrx
 */
private function extractContactForTransaction($trz, $qifTrx): void
{
    if (empty($qifTrx->payee)) {
        return;
    }
    
    try {
        global $db;
        if (!isset($db)) {
            return;
        }
        
        $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
        $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);
        
        $contactData = new \Ksfraser\Contact\DTO\ContactData([
            'name' => (string) $qifTrx->payee,
            'contact_type' => $trz->transactionDC === 'D' ? 'supplier' : 'customer',
            'sic' => null,  // QIF doesn't provide SIC codes
        ]);
        
        $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
        if ($contact && isset($contact->id)) {
            $trz->contact_id = (int) $contact->id;
        }
    } catch (\Throwable $e) {
        // Non-blocking
        error_log('QIF contact extraction failed: ' . $e->getMessage());
    }
}
```

**Important Note About qifparser Requirements**:

The user correctly noted that qifparser expects bank account info to be passed in. Looking at qif_parser.php line ~145:

```php
$bankId     = $static_data['bank_id']      ?? ($static_data['account_code'] ?? 'QIF');
$accountId  = $static_data['account_code'] ?? $bankId;
```

These are extracted from $static_data (passed by import_statements.php around line 823):

```php
$static_data['account'] = $bank_account['bank_account_number'];
$static_data['account_code'] = $bank_account['account_code'];
$static_data['currency'] = $bank_account['bank_curr_code'];
```

**This is ALREADY provided by the import flow**. The difference with OFX is:
- **OFX/QFX**: Auto-detects account from file header (bank provides it)
- **QIF**: Must be user-specified (no account header in QIF format)

The import_statements.php flow already handles this - bank account is selected in the form and passed to all parsers.

---

### PHASE 3: LOCAL CSV Parsers (1 parser = 2-4 hours)

**Files**:
- `/includes/ro_ing_csv_parser.php` (ING) - 2-3 hrs
- `/includes/ro_wmmc_csv_parser.php` (Walmart Mastercard) - 2-3 hrs
- `/includes/ro_bcr_csv_parser.php` (BCR) - 2-3 hrs
- `/includes/ro_brd_mt940_parser.php` (BRD MT940) - 3-5 hrs (most complex)

**Pattern for Each**:

1. **Identify merchant/payee field** in parser

   Example from ro_ing_csv_parser.php line ~134:
   ```php
   if (!empty($f[1])) {  // $f[1] is transaction type
       // ...
   }
   // Merchant info would be in another field
   ```

2. **Create extraction method** (like qif_parser):
   ```php
   private function extractContactForTransaction($trz): void
   {
       if (empty($trz->merchant) && empty($trz->account)) {
           return;
       }
       
       try {
           global $db;
           if (!isset($db)) return;
           
           $merchant = $trz->merchant ?: $trz->account;
           
           $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
           $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);
           
           $contactData = new \Ksfraser\Contact\DTO\ContactData([
               'name' => (string) $merchant,
               'contact_type' => $trz->transactionDC === 'D' ? 'supplier' : 'customer',
           ]);
           
           $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
           if ($contact && isset($contact->id)) {
               $trz->contact_id = (int) $contact->id;
           }
       } catch (\Throwable $e) {
           error_log('CSV contact extraction failed: ' . $e->getMessage());
       }
   }
   ```

3. **Call extraction** just before `$smts[$sid]->addTransaction($trz);`

   Example pattern:
   ```php
   // ... existing merchant processing ...
   $this->extractContactForTransaction($trz);  // NEW
   $smts[$sid]->addTransaction($trz);  // EXISTING
   ```

**CSV Parser Priority**:
1. ro_ing_csv_parser (ING) - START HERE after qfx
2. ro_wmmc_csv_parser (Walmart) - NEXT
3. ro_bcr_csv_parser (BCR) - NEXT
4. ro_brd_mt940_parser (BRD MT940) - LAST (most complex format)

---

## ⚠️ EXTERNAL PACKAGES - DO NOT MODIFY DIRECTLY

These packages will need their own contact integration by their maintainers:
- `ksfraser/ksf_ofxparser` - You'll create a wrapper if needed
- `ksfraser/qifparser` - Already have a wrapper (qif_parser.php)
- `ksfraser/ksf_csvparser` - You'll create a wrapper if needed

**Strategy if needed**: Create adapter wrapper classes that intercept output and inject contact extraction before returning to import_statements.php.

---

## 📊 Integration Timeline

| Phase | Parser(s) | Effort | Duration | Status |
|-------|-----------|--------|----------|--------|
| **1** | qfx_parser.php | 2-3 hrs | Week 1 | Ready to start |
| **2** | qif_parser.php wrapper | 2-3 hrs | Week 1 | Ready to start |
| **3a** | ro_ing_csv_parser.php | 2-3 hrs | Week 2 | Planned |
| **3b** | ro_wmmc_csv_parser.php | 2-3 hrs | Week 2 | Planned |
| **3c** | ro_bcr_csv_parser.php | 2-3 hrs | Week 2 | Planned |
| **3d** | ro_brd_mt940_parser.php | 3-5 hrs | Week 2-3 | Planned |

**Total**: 17-24 hours across 2-3 weeks

---

## 🛡️ Defensive Coding Pattern

All implementations follow this pattern to ensure parser remains functional if contact extraction fails:

```php
try {
    $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
    $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);
    
    $contactData = new \Ksfraser\Contact\DTO\ContactData([...]);
    $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
    
    if ($contact && isset($contact->id)) {
        $trz->contact_id = (int) $contact->id;
    }
} catch (\Throwable $e) {
    // Log but don't fail the import
    error_log('Contact extraction failed: ' . $e->getMessage());
}
```

**Benefits**:
- If db unavailable → import still works
- If ContactService error → import continues
- If deduplication timeout → import completes
- Errors logged for debugging

---

## ✅ Pre-Implementation Checklist

- [ ] Database: Confirm sql/update.sql will be executed before live import
  - Creates `0_bi_contact` table
  - Adds `0_bi_transactions.contact_id` FK field
- [ ] ContactService: Verified ready and tested ✅
- [ ] ContactDeduplicationService: Verified ready and tested ✅
- [ ] ContactData DTO: Verified and documented ✅
- [ ] Test data: QFX, QIF, and CSV sample files available
- [ ] Backup: Code committed to version control before starting

---

## 🚀 Implementation Checklist (Per Parser)

For each parser integration:

- [ ] Read the parser class to understand merchant/payee field location
- [ ] Add extraction method (or inline extraction logic)
- [ ] Merge with existing transaction processing (don't break existing logic)
- [ ] Add error handling (try/catch around ContactService calls)
- [ ] Test with sample file (verify contacts created)
- [ ] Verify $trz->contact_id populated in parsed transactions
- [ ] Test import flow end-to-end (full import with contact matching)
- [ ] Commit with conventional commit message

---

## 📝 Database Requirement

Before deploying contact extraction to production, ensure this SQL is executed:

```sql
-- From sql/update.sql - Lines #1-80 (approx)

CREATE TABLE IF NOT EXISTS 0_bi_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_type ENUM('customer', 'supplier', 'both') DEFAULT 'customer',
    sic VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    dedup_hash VARCHAR(64) UNIQUE,
    UNIQUE KEY unique_name_type (name, contact_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add FK to transactions table
ALTER TABLE 0_bi_transactions 
ADD COLUMN contact_id INT,
ADD CONSTRAINT fk_transactions_contact 
FOREIGN KEY (contact_id) REFERENCES 0_bi_contact(id) ON DELETE SET NULL;
```

---

## 🔗 Related Documents

- [PARSER_INTEGRATION_STRATEGY.md](PARSER_INTEGRATION_STRATEGY.md) - High-level architecture
- [ContactService Documentation](src/Ksfraser/FaBankImport/Services/ContactService.php)
- [ContactDeduplicationService Documentation](src/Ksfraser/FaBankImport/Services/ContactDeduplicationService.php)
- [ContactData DTO Documentation](src/Ksfraser/Contact/DTO/ContactData.php)

---

## 📞 Questions & Support

**Q**: What if ContactService is not available?  
**A**: It's already implemented and tested. See ContactService.php.

**Q**: What if $db is not available when parser runs?  
**A**: Code checks `if (!isset($db))` and gracefully skips contact extraction.

**Q**: Can I integrate multiple parsers in parallel?  
**A**: Yes! They don't interact. Start with qfx_parser while someone else works on CSV parsers.

**Q**: What happens to existing transactions without contact_id?  
**A**: They remain valid. contact_id is nullable. New imports will have it populated.

**Q**: How do I test this?  
**A**: Upload QFX/QIF/CSV file, verify transactions have contact_id set, check 0_bi_contact table for created contacts.

---

**Document Status**: ✅ Ready for Implementation  
**Last Updated**: 2026-03-22  
**Next Step**: Integrate qfx_parser.php (Phase 1)
