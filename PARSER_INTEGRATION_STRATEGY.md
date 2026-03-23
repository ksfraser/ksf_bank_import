# Parser Integration Strategy - Ownership & Implementation Plan

**Date**: March 22, 2026  
**Status**: Verified - Ready to implement

---

## 📋 Summary: Your Assessment is CORRECT

✅ **You control**: `qfx_parser.php` (LOCAL wrapper, full modification rights)  
✅ **You control**: CSV parsers - ING, WMMC, BRD MT940, BCR (4 LOCAL parsers)  
❌ **You don't control**: ksf_ofxparser, qifparser, ksf_csvparser (EXTERNAL packages)

---

## 🎯 Parser Inventory & Integration Points

| Parser | Type | Location | Status | Integration Effort |
|--------|------|----------|--------|-------------------|
| **qfx_parser.php** | QFX/OFX | LOCAL - `/includes/qfx_parser.php` | ✅ Full control | **LOW** - 2-3 hrs |
| **ING CSV** | CSV | LOCAL - `/Parsers/` | ✅ Full control | **LOW** - 2-3 hrs |
| **WMMC CSV** | CSV | LOCAL - `/Parsers/` | ✅ Full control | **LOW** - 2-3 hrs |
| **BCR** | CSV | LOCAL - `/Parsers/` | ✅ Full control | **LOW** - 2-3 hrs |
| **BRD MT940** | MT940 | LOCAL - `/Parsers/` | ✅ Full control | **MEDIUM** - 4-5 hrs |
| **ksf_ofxparser** | OFX | EXTERNAL package | ⏳ Wrapper only | **MEDIUM** - 3-4 hrs |
| **qifparser** | QIF | EXTERNAL package | ⏳ Wrapper only | **MEDIUM** - 3-4 hrs |
| **ksf_csvparser** | CSV | EXTERNAL package | ⏳ Wrapper only | **LOW** - 2-3 hrs |

---

## 🔍 Current qfx_parser.php Architecture

### Location: `/includes/qfx_parser.php`

**Current flow** (lines 300-450):
```
OfxParser parses QFX file
    ↓
For each transaction in bank account:
    - Extract transaction.name (merchant string)
    - Extract transaction.sic (Standard Industrial Classification code)
    - Extract transaction.memo (transaction memo/description)
    - Extract transaction.checkNumber
    - Extract transaction.amount
    - Extract transaction.type (CREDIT/DEBIT)
    ↓
Merchant name processing:
    - Call shorten_bankAccount_Names($transaction->name)  [line 410]
    - Set $trz->account = shortname
    - Set $trz->accountName1 = $transaction->name (full merchant name)
    - Set $trz->transactionTitle1 = $transaction->name
    - Set $trz->transactionTitle2 = $transaction->sic
    - Set $trz->transactionTitle4 = $transaction->memo
    ↓
Return statement with transactions
```

### Key Insertion Point: Line ~410 in qfx_parser.php

**Current code**:
```php
$shortname = shorten_bankAccount_Names((string) $transaction->name);
$trz->account = $shortname;
$trz->accountName1 = (string) $transaction->name;  // Full merchant name
$trz->transactionTitle1 = (string) $transaction->name;
$trz->transactionTitle2 = (string) $transaction->sic;
$trz->transactionTitle4 = (string) $transaction->memo;
```

**What we ADD here**:
```php
// Extract/create contact from parsed merchant data
$contactService = new ContactService($db);
$deduplicationService = new ContactDeduplicationService($contactService);

$contactData = new ContactData([
    'name' => (string) $transaction->name,
    'contact_type' => $trz->transactionDC === 'D' ? 'supplier' : 'customer',
    // SIC code can map to industry
    // Memo might contain additional info (city, state, postal code)
]);

$contact = $deduplicationService->getOrCreateWithDeduplicate($contactData);
if ($contact) {
    $trz->contact_id = $contact->id;  // NEW FIELD - FK to 0_bi_contact
}
```

---

## 🛠️ Integration Implementation Plan

### Phase 1: qfx_parser.php (PRIMARY - 2-3 hours)

**File**: `/includes/qfx_parser.php`

**Changes**:
1. Add `use` statements for ContactService and ContactDeduplicationService
2. Inject $db into qfx_parser constructor or parse() method
3. At merchant processing point (~line 410):
   - Extract merchant name (already available)
   - Extract transaction type (DEBIT = supplier, CREDIT = customer)
   - Optionally extract SIC code for industry metadata
   - Call ContactDeduplicationService to get/create contact
   - Set transaction->contact_id = contact->id

**Key insight**: QFX merchant name is already in `$transaction->name` - ideal for contact creation!

### Phase 2: CSV Parsers (PARALLEL - 2-3 hours each)

For each CSV parser (ING, WMMC, BCR, BRD MT940):
- Identify merchant/payee field in parser output
- Add same contact extraction logic
- Map CSV-specific transaction type to customer/supplier

**CSV Parser locations**:
```
/Parsers/ directory
  - Contains individual parser.json config files
  - Each calls custom parse class extending `parser` base
```

### Phase 3: External Package Wrappers (SEQUENTIAL - 3-4 hours each)

For ksf_ofxparser, qifparser, ksf_csvparser:
- Create adapter/wrapper classes
- Don't modify package code directly
- Intercept parsed output and inject contact extraction

**Strategy**: Wrap each external parser in a ContactExtractingWrapper class

```php
class ContactExtractingQifParser {
    private $baseParser;
    private $contactService;
    
    public function parse($content, ...) {
        $statements = $this->baseParser->parse($content, ...);
        // Inject contact extraction into statements
        foreach ($statements as $stmt) {
            foreach ($stmt->transactions as $trx) {
                $this->injectContact($trx);
            }
        }
        return $statements;
    }
}
```

---

## 💾 Database Readiness

**Required for parser integration**:
1. ✅ `0_bi_contact` table exists (defined in sql/update.sql - not yet migrated)
2. ✅ `0_bi_transactions.contact_id` FK field exists (defined - not yet migrated)
3. ✅ ContactService ready (created ✅)
4. ✅ ContactDeduplicationService ready (created ✅)

**When executing sql/update.sql**:
- Do this AFTER parsers are modified
- Or: Modify parsers with defensive null-checking on $trz->contact_id

---

## 📊 Current State - What's in qfx_parser.php RIGHT NOW

### Fields extracted from OFX transaction:
```php
transaction->name           // Merchant name: "AMAZON.COM SEATTLE WA"
transaction->sic            // Industry code: "5961" (misc retailers)
transaction->checkNumber    // Check number if applicable
transaction->memo           // Transaction memo/description
transaction->type           // CREDIT or DEBIT
transaction->amount         // Transaction amount
transaction->uniqueId       // Transaction ID
transaction->date           // Transaction date
transaction->userInitiatedDate // User init date (if available)
```

### Where merchant name currently ends up:
```php
$trz->account            = $shortname;              // Shortened version
$trz->accountName1       = full merchant name;      // Full name preserved
$trz->transactionTitle1  = full merchant name;      // Title field
$trz->transactionTitle2  = $sic code;               // Industry classification
$trz->transactionTitle4  = memo;                    // Additional info
```

### Transaction object after qfx_parser:
```php
$trz->contact_id  // <-- NOT CURRENTLY SET (this is what we add!)
$trz->account                   // Shortened merchant name
$trz->accountName1              // Full merchant name  
$trz->transactionTitle1/2/3/4   // Parsed merchant details
$trz->transactionDC             // 'C' for credit, 'D' for debit
$trz->transactionType           // 'TRF' for transfer
$trz->transactionAmount         // Amount
$trz->valueTimestamp            // Transaction date
$trz->memo                      // Additional info
```

---

## 🎓 Code Pattern - How to Integrate

### Minimal integration (what we need in qfx_parser.php around line 410):

```php
// BEFORE: Current code
$shortname = shorten_bankAccount_Names((string) $transaction->name);
$trz->account = $shortname;

// AFTER: With contact extraction
$shortname = shorten_bankAccount_Names((string) $transaction->name);
$trz->account = $shortname;

// NEW: Contact extraction
if (isset($db) && !empty($transaction->name)) {
    try {
        $contactService = new \Ksfraser\FaBankImport\Services\ContactService($db);
        $deduplicateService = new \Ksfraser\FaBankImport\Services\ContactDeduplicationService($contactService);
        
        $contactData = new \Ksfraser\Contact\DTO\ContactData([
            'name' => (string) $transaction->name,
            'contact_type' => $trz->transactionDC === 'D' ? 'supplier' : 'customer',
            'sic' => !empty($transaction->sic) ? (string) $transaction->sic : null,
        ]);
        
        $contact = $deduplicateService->getOrCreateWithDeduplicate($contactData);
        if ($contact) {
            $trz->contact_id = $contact->id;
        }
    } catch (Exception $e) {
        // Log error but don't fail import
        error_log('Contact extraction failed: ' . $e->getMessage());
    }
}
```

---

## ✅ Why qfx_parser.php Is Your Best Starting Point

1. **Full Control**: Not a dependency, can modify directly
2. **Clean Data**: OFX format provides structured merchant data
3. **Non-blocking**: Parser still works if $db not available (defensive coding)
4. **High Volume**: QFX files likely your primary import format
5. **Clear Integration Point**: Merchant name extraction ~line 410
6. **Proven to Work**: ContactService & ContactDeduplicationService ready
7. **Fast Implementation**: 2-3 hours for full integration

---

## 🚀 Execution Sequence (if implementing now)

### Week 1: LOCAL Parsers (High ROI)
- **Task 4.1**: Modify qfx_parser.php (2-3 hrs)
- **Task 4.2**: Update ING CSV parser  (2-3 hrs)
- **Task 4.3**: Update WMMC CSV parser (2-3 hrs)

### Week 2: Remaining LOCAL Parsers
- **Task 4.4**: Update BCR parser (2-3 hrs)
- **Task 4.5**: Update BRD MT940 parser (4-5 hrs) - most complex

### Week 3: EXTERNAL Package Wrappers
- **Task 4.6**: ksf_ofxparser wrapper (3-4 hrs)
- **Task 4.7**: qifparser wrapper (3-4 hrs)
- **Task 4.8**: ksf_csvparser wrapper (2-3 hrs)

**Total Effort**: 21-32 hours spread across 3 weeks

---

## 🎯 Next Action

1. **Database**: Confirm sql/update.sql will be executed (creates 0_bi_contact table, adds contact_id FK)
2. **qfx_parser.php Integration**: Start here - modify `/includes/qfx_parser.php` around line 410
3. **Defensive Coding**: Wrap in try/catch so parser still works if db operations fail
4. **Testing**: Test with sample QFX file, verify contacts are created/linked

---

## 💡 Key Advantages of This Approach

✅ **No breaking changes** to existing transaction import  
✅ **Gradual rollout** - implement one parser at a time  
✅ **Defensive coding** - contact extraction optional/non-blocking  
✅ **Proven components** - ContactService already tested  
✅ **High-quality contact data** - deduplication baked in  
✅ **Separate from parsers** - ContactService completely independent module  

---

**Document Version**: 1.0  
**Status**: Ready for implementation  
**Next Review**: After qfx_parser.php integration complete
