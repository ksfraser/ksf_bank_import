# OFX Parser Repository Capability Comparison

**Date:** January 13, 2026  
**Purpose:** Compare functional capabilities across all ofxparser repos

---

## Repositories Compared

1. **ksf_ofxparser** (Our repo - Type hints added Jan 13)
2. **jacques-ofxparser** (lib/jacques-ofxparser)
3. **ofx4** (lib/ofx4)
4. **ofx2** (lib/ofx2)
5. **memhetcoban-ofxparser** (lib/memhetcoban-ofxparser)

---

## Key Properties Comparison

### Ofx.php Public Properties

| Property | KSF | Jacques | ofx4 | ofx2 | memhetcoban |
|----------|-----|---------|------|------|-------------|
| `$signOn` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `$signupAccountInfo` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `$bankAccounts` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `$bankAccount` | ✅ | ✅ | ✅ | ✅ | ✅ |
| **`$header`** | ❌ | ✅ | ✅ | ❌ | ❌ |

**FINDING:** KSF is missing `public $header = [];` property!

---

## Method Capabilities Comparison

### Ofx.php Methods

| Method | KSF | Jacques | ofx4 | ofx2 | memhetcoban | Purpose |
|--------|-----|---------|------|------|-------------|---------|
| `__construct()` | ✅ | ✅ | ✅ | ✅ | ✅ | Initialize |
| `getTransactions()` | ✅ | ✅ | ✅ | ✅ | ✅ | Get transactions |
| **`buildHeader()`** | ❌ | ✅ | ✅ | ❌ | ❌ | **Store OFX header** |
| `buildSignOn()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse signon |
| `buildAccountInfo()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse accounts |
| `buildBankAccounts()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse bank accounts |
| `buildCreditAccounts()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse credit cards |
| `buildTransactions()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse transactions |
| `buildStatus()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse status |
| `createDateTimeFromStr()` | ✅ | ❌* | ❌* | ❌* | ❌* | Parse dates |
| `createAmountFromStr()` | ✅ | ❌* | ❌* | ❌* | ❌* | Parse amounts |
| **`createTags()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **Fix malformed XML** |
| **`copyChildren()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **Helper for createTags** |
| **`buildPayee()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **Parse payee info** |
| **`buildBankAccountTo()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **Parse destination account** |
| **`buildCardAccountTo()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **Parse destination card** |

*Note: Others use `Utils::createDateTimeFromStr()` instead of local method

**KEY FINDING:** KSF is **MISSING** `buildHeader()` method but has **UNIQUE** features:
- `createTags()` - Handles malformed XML
- `buildPayee()` - Payee support
- `buildBankAccountTo()` / `buildCardAccountTo()` - Transfer destination accounts

---

## Parser.php Methods

| Method | KSF | Jacques | ofx4 | ofx2 | memhetcoban | Purpose |
|--------|-----|---------|------|------|-------------|---------|
| `loadFromFile()` | ✅ | ✅ | ✅ | ✅ | ✅ | Load from file |
| `loadFromString()` | ✅ | ✅ | ✅ | ✅ | ✅ | Load from string |
| `createOfx()` | ✅ | ✅ | ✅ | ❌ | ❌ | Factory method |
| `conditionallyAddNewlines()` | ✅ | ✅ | ✅ | ✅ | ✅ | Add newlines |
| `xmlLoadString()` | ✅ | ✅ | ✅ | ✅ | ✅ | Parse XML |
| `closeUnclosedXmlTags()` | ✅ | ✅ | ✅ | ✅ | ✅ | Close tags |
| `convertSgmlToXml()` | ✅ | ✅ | ✅ | ✅ | ✅ | Convert SGML |
| `parseHeader()` | ✅ | ✅ | ✅ | ❌ | ❌ | **Parse header** |
| **`extract_tag()`** | ✅ | ❌ | ❌ | ❌ | ❌ | **KSF helper** |

---

## Critical Differences in loadFromString()

### ofx4 (HAS header support):
```php
public function loadFromString($ofxContent)
{
    $ofxContent = str_replace(["\r\n", "\r"], "\n", $ofxContent);
    $ofxContent = utf8_encode($ofxContent);

    $sgmlStart = stripos($ofxContent, '<OFX>');
    $ofxHeader = trim(substr($ofxContent, 0, $sgmlStart));
    $header = $this->parseHeader($ofxHeader);  // ← Parses header
    
    // ... parse XML ...
    
    $ofx = $this->createOfx($xml);
    $ofx->buildHeader($header);  // ← CALLS buildHeader()!
    return $ofx;
}
```

### KSF (MISSING header call):
```php
public function loadFromString(string $ofxContent): Ofx
{
    $ofxContent = mb_convert_encoding($ofxContent, "UTF-8", mb_detect_encoding($ofxContent));
    // ...
    $ofxHeader = trim(substr($ofxContent, 0, $sgmlStart));
    $header = $this->parseHeader($ofxHeader);  // ← Parses but doesn't use!
    
    // ... parse XML ...
    
    $ofx = $this->createOfx($xml);
    // ← MISSING: $ofx->buildHeader($header);  // <-- This line commented out!
    return $ofx;
}
```

**CRITICAL BUG CONFIRMED:** Line 100 in KSF Parser.php has:
```php
//I haven't updated OFX yet so buildHeader isn't there
//$ofx->buildHeader($header);
```

---

## Entity Classes Comparison

### Transaction.php Properties

| Property | KSF | Jacques | ofx4 | Purpose |
|----------|-----|---------|------|---------|
| `$type` | ✅ | ✅ | ✅ | Transaction type |
| `$date` | ✅ | ✅ | ✅ | Post date |
| `$userInitiatedDate` | ✅ | ✅ | ✅ | User date |
| `$amount` | ✅ | ✅ | ✅ | Amount |
| `$uniqueId` | ✅ | ✅ | ✅ | FITID |
| `$name` | ✅ | ✅ | ✅ | Name |
| `$memo` | ✅ | ✅ | ✅ | Memo |
| `$sic` | ✅ | ✅ | ✅ | SIC code |
| `$checkNumber` | ✅ | ✅ | ✅ | Check number |
| **`$refNumber`** | ✅ | ❌ | ❌ | **Reference number** |
| **`$nameExtended`** | ✅ | ❌ | ❌ | **Extended name** |
| **`$payeeId`** | ✅ | ❌ | ❌ | **Payee ID** |
| **`$payee`** | ✅ | ❌ | ❌ | **Payee object** |
| **`$bankAccountTo`** | ✅ | ❌ | ❌ | **Destination bank account** |
| **`$cardAccountTo`** | ✅ | ❌ | ❌ | **Destination card account** |

**FINDING:** KSF has **6 additional transaction properties** not in other repos!

---

## Unique KSF Entities

These entities exist ONLY in KSF:

1. **Entities/Payee.php** - Complete payee information
   - `$name`, `$address`, `$city`, `$state`, `$postalCode`, `$country`, `$phone`

2. **Entities/CreditCardAccount.php** - Credit card specific

3. **Entities/CreditCardAccountInfo.php** - Credit card info

4. **Entities/BankAccountInformation.php** - Extended bank info

5. **Entities/BankingAccount.php** - Banking specific

6. **Entities/LoaderTrait.php** - Reusable loading functionality

---

## Encoding Differences

| Approach | Repos | Code |
|----------|-------|------|
| **utf8_encode()** | ofx2, ofx4, jacques, memhetcoban | `$ofxContent = utf8_encode($ofxContent);` |
| **mb_convert_encoding()** | **KSF only** | `$ofxContent = mb_convert_encoding($ofxContent, "UTF-8", mb_detect_encoding($ofxContent));` |

**KSF Advantage:** Uses `mb_convert_encoding()` which is PHP 8.2+ compatible (utf8_encode deprecated in 8.2)

---

## XML Handling Differences

### Malformed XML Support

| Feature | KSF | Others | Impact |
|---------|-----|--------|--------|
| `createTags()` | ✅ | ❌ | Handles missing SIGNONMSGSRSV1 or SONRS tags |
| `copyChildren()` | ✅ | ❌ | Recursively copies XML nodes |
| INTU.BID fallback | ✅ | ❌ | Handles MANU files without FI->FID |

**KSF handles MORE malformed OFX files than other repos!**

---

## Action Items

### 🔴 CRITICAL - Add Missing from jacques/ofx4:

1. **Add property to Ofx.php:**
   ```php
   public $header = [];
   ```

2. **Add method to Ofx.php:**
   ```php
   public function buildHeader(array $header): self
   {
       $this->header = $header;
       return $this;
   }
   ```

3. **Uncomment in Parser.php line 100:**
   ```php
   $ofx->buildHeader($header);  // Remove the comment!
   ```

### ✅ KSF Unique Strengths to KEEP:

1. ✅ `mb_convert_encoding()` (PHP 8.2+ ready)
2. ✅ `createTags()` / `copyChildren()` (malformed XML support)
3. ✅ `buildPayee()` - Payee entity support
4. ✅ `buildBankAccountTo()` / `buildCardAccountTo()` - Transfer support
5. ✅ Additional Transaction properties (refNumber, nameExtended, payeeId, etc.)
6. ✅ CreditCard entities
7. ✅ LoaderTrait
8. ✅ Type hints (just added!)

### 📊 Summary:

**KSF has MORE features than any other repo, but is missing header support!**

- **Missing:** 1 property + 1 method + 1 uncommented line
- **Unique to KSF:** 5 methods + 6 transaction properties + 6 entity classes + better encoding

---

## Recommendation

**FIX THE HEADER SUPPORT** then KSF will be the most feature-complete OFX parser!
