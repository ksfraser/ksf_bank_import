# QIF Parser Integration Guide

**Status**: Ready to Implement  
**Effort**: 2-3 hours  
**Priority**: High  
**Depends on**: qfx_parser integration ✅ (optional, can run in parallel)

---

## 📍 File Location

`/vendor/ksfraser/qifparser/qif_parser.php` - **This is YOUR wrapper, not the external package**

This file is a LOCAL adaptation layer between the external `ksfraser/qifparser` package and bank_import's parser interface. You have full modification rights.

---

## 🔍 Current Status

The qif_parser is a well-structured wrapper that:
- Reads raw QIF text content
- Delegates to LibQifParser (the external package)
- Maps QifTransaction objects to bank_import transaction objects
- Returns same statement/transaction structure as qfx_parser

**Key method**: `mapTransaction()` - Lines 183-211

---

## 🎯 Implementation Goal

Add contact extraction in `mapTransaction()` so that every QIF transaction gets linked to a contact in `0_bi_contact` table.

---

## 📋 Step-by-Step Implementation

### Step 1: Understand mapTransaction() Current Logic

Read lines 183-211 to see how transaction is mapped:

```php
private function mapTransaction($qifTrx, string $accountId, string $bankId)
{
    $trz = new transaction();
    
    // ... dates, amounts, identifiers ...
    
    // Payee / merchant (THIS IS WHERE CONTACT DATA COMES FROM)
    $payee = (string) ($qifTrx->payee ?? '');
    $trz->account = $payee;
    $trz->accountName = $payee;
    $trz->accountName1 = $payee;
    $trz->merchant = $payee;
    $trz->transactionTitle1 = $payee;
    
    // Category & memo
    $trz->category = (string) ($qifTrx->category ?? '');
    $trz->transactionTitle2 = (string) ($qifTrx->category ?? '');
    $trz->memo = (string) ($qifTrx->memo ?? '');
    $trz->transactionTitle4 = (string) ($qifTrx->memo ?? '');
    
    // Account context
    $trz->acctid = $accountId;
    $trz->bankid = $bankId;
    $trz->intu_bid = $bankId;
    
    return $trz;  // <-- THIS IS WHERE WE ADD CONTACT EXTRACTION
}
```

**Key Insight**: Payee is in `$qifTrx->payee`

### Step 2: Add Contact Extraction Call

At line 211 (just before `return $trz`), call new extraction method:

**Replace** (lines 207-211):
```php
    // Account context
    $trz->acctid = $accountId;
    $trz->bankid = $bankId;
    $trz->intu_bid = $bankId;

    return $trz;
```

**With**:
```php
    // Account context
    $trz->acctid = $accountId;
    $trz->bankid = $bankId;
    $trz->intu_bid = $bankId;

    // Extract/create contact from QIF payee
    $this->extractContactForTransaction($trz, $qifTrx);

    return $trz;
```

### Step 3: Add extractContactForTransaction() Method

Add this new private method BEFORE the closing brace of the class (after mapTransaction):

```php
/**
 * Extract/create contact for parsed QIF transaction.
 * 
 * Integrates with ContactService and ContactDeduplicationService to maintain
 * a persistent contact database linked to bank transactions.
 * 
 * Design: Non-blocking. Errors caught and logged but don't interrupt parsing.
 * 
 * @param transaction $trz The bank_import transaction object being populated
 * @param \Ksfraser\QifParser\Entities\QifTransaction $qifTrx The raw QIF transaction
 * @return void
 */
private function extractContactForTransaction($trz, $qifTrx): void
{
    // Only attempt extraction if we have payee name
    if (empty($qifTrx->payee)) {
        return;
    }

    try {
        // Graceful degradation: if db not available, skip contact extraction
        if (!isset($GLOBALS['db'])) {
            return;
        }

        $db = $GLOBALS['db'];

        // Load services (lazy load to avoid mandatory dependency)
        if (!class_exists('\Ksfraser\FaBankImport\Services\ContactService')) {
            return;
        }

        $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
        $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);

        // Determine contact type based on transaction direction
        // DEBIT (outgoing) = supplier; CREDIT (incoming) = customer
        $contactType = ($trz->transactionDC === 'D') ? 'supplier' : 'customer';

        // Prepare contact data from QIF payee
        $contactData = new \Ksfraser\Contact\DTO\ContactData([
            'name' => (string) $qifTrx->payee,
            'contact_type' => $contactType,
            'sic' => null,  // QIF format doesn't provide SIC codes
        ]);

        // Get or create contact with deduplication
        $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);

        // Link transaction to contact if creation succeeded
        if ($contact && !empty($contact->id)) {
            $trz->contact_id = (int) $contact->id;
        }

    } catch (\Throwable $e) {
        // Non-blocking error handling: Log but don't fail the import
        // This ensures QIF parsing continues even if contact extraction fails
        @error_log('QIF parser: Contact extraction failed for "' 
            . substr((string) $qifTrx->payee, 0, 50) 
            . '": ' . $e->getMessage());
    }
}
```

---

## 🧪 Testing Checklist

- [ ] Test with sample QIF file (e.g., from real download)
- [ ] Upload QIF file in import_statements.php
- [ ] Verify:
  - Import completes successfully (no parse errors)
  - Contacts created in `0_bi_contact` table
  - Transaction objects have `contact_id` populated
  - Multiple transactions to same payee link to same contact (deduplication works)
- [ ] Test with QIF file containing:
  - Deposits (customer type contacts)
  - Withdrawals (supplier type contacts)
  - Same payee repeated (verify deduplication)
  - Special characters in payee name (verify sanitization)

---

## ⚠️ Important Notes

### About Bank Account Info for QIF

The user noted that **qifparser expects bank account info to be passed in**. This is already handled:

**In import_statements.php** (line ~823):
```php
$static_data['account'] = $bank_account['bank_account_number'];
$static_data['account_code'] = $bank_account['account_code'];
$static_data['currency'] = $bank_account['bank_curr_code'];
```

**In qif_parser.php** (line ~145):
```php
$bankId = $static_data['bank_id'] ?? ($static_data['account_code'] ?? 'QIF');
$accountId = $static_data['account_code'] ?? $bankId;
```

✅ **This already works correctly**. The import flow automatically provides account info.

### Difference: OFX Auto-Detect vs QIF User-Specified

- **OFX/QFX**: Bank provides account info in file header → auto-detected
- **QIF**: No account header in format → user must select bank account in form

This is why QIF requires explicit form selection, but the selected account info is already passed to the parser.

---

## 🔗 Related Code

- **ContactService**: `src/Ksfraser/FaBankImport/Services/ContactService.php`
- **ContactDeduplicationService**: `src/Ksfraser/FaBankImport/Services/ContactDeduplicationService.php`
- **ContactData DTO**: `src/Ksfraser/Contact/DTO/ContactData.php`
- **Comparison**: See qfx_parser integration for reference pattern

---

## 📍 Exact File Path

```
/vendor/ksfraser/qifparser/qif_parser.php
    Line 183-211: mapTransaction() method
    Line 211: Add extractContactForTransaction() call
    Line 212+: Add extractContactForTransaction() method
```

---

## 🚀 Next Steps

1. Open `/vendor/ksfraser/qifparser/qif_parser.php`
2. Locate `mapTransaction()` method (line ~183)
3. Add extraction call before `return $trz` (line 211)
4. Add `extractContactForTransaction()` private method after `mapTransaction()`
5. Test with QIF file
6. Verify contacts are created and linked

**Estimated time**: 30-45 minutes implementation + 30 minutes testing = **60-90 minutes (1-1.5 hours)**

---

## 💡 Tips

- Start with small QIF file (1-2 transactions) to verify logic
- Use error_log() output in browser console to debug
- If ContactService not available, code gracefully skips (won't break import)
- Compare with qfx_parser.php for reference implementation pattern

---

**Status**: ✅ Ready to implement  
**Last updated**: 2026-03-22
