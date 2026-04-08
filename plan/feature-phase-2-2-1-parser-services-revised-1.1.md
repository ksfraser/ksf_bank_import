---
goal: Implement Phase 2.2.1 Parser Services - Refactor existing CSV parsers and wrap external OFX/QIF libraries
version: 1.1
date_created: 2026-04-04
last_updated: 2026-04-04
owner: KS Fraser
status: 'In progress'
tags: ['feature', 'phase-2-2-1', 'parsers', 'refactor', 'libraries']
---

# Phase 2.2.1 Parser Implementation - REVISED

![Status: In Progress](https://img.shields.io/badge/status-In%20Progress-yellow)

## Overview: Leverage Existing Patterns

This phase implements parsers by:
1. **Consolidating** existing CSV parsers (ro_bcr, ro_ing, ro_wmmc) into a configurable `CsvParser` using column header mapping
2. **Wrapping** the already-available `ksfraser/ksf_ofxparser` library with `OFXParser`
3. **Wrapping** the already-available `ksfraser/qifparser` library with `QIFParser`
4. **Creating** a `ParserFactory` that detects file type and routes to appropriate parser

---

## 1. Existing Codebase Inventory

### CSV Parser Implementations (3 variants)
| File | Pattern | Strength |
|------|---------|----------|
| [ro_bcr_csv_parser.php](includes/ro_bcr_csv_parser.php) | Array index-based | Simple, performant |
| [ro_ing_csv_parser.php](includes/ro_ing_csv_parser.php) | Mixed index + logic | Bank-specific logic |
| [ro_wmmc_csv_parser.php](includes/ro_wmmc_csv_parser.php) | **Header-based mapping** | **Flexible, maintainable** |

**Recommendation**: Use WMMC pattern (header-based) as foundation. It handles:
- Variable column header names (tested over 2 years)
- str_getcsv() for proper CSV quoting/escaping
- array_combine() for dynamic mapping
- Fallback/synonym support ("Date" → "Posted Date", etc.)

### External Libraries (Already in composer.json)
```json
"ksfraser/ksf_ofxparser": "^0.1.1",    // OFX/QFX parsing
"ksfraser/qifparser": "*",              // QIF parsing
```

Both libraries are production-ready and licensed. Phase 2.2.1 wraps them with ParserInterface.

### Column Mapping Example (From WMMC Parser)
```php
$search_arr = ["Date", "Posted Date", "Activity Type", "Merchant Name", "Amount"];
$replace_arr = ["transdate", "posteddate", "activitytype", "merchant", "amount"];
$header = str_replace($search_arr, $replace_arr, strtolower($header));

// Result: Dynamic header → standard field names
$linedata = array_combine($header_arr, $linedata);
$transaction->amount = $linedata["amount"];  // access by mapped name
```

---

## 2. Implementation Tasks

### TASK-P1-001: Create Configurable CsvParser

**File**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/CsvParser.php`

**Interface Contract**:
```php
interface ParserInterface {
    public function parse(string $filePath, array $options = []): array;
    public function getSupportedTypes(): array;  // ['text/csv', 'application/csv']
    public function getName(): string;           // 'CSV Parser'
}
```

**Implementation Requirements**:

1. **Column Mapping Strategy**:
   - Accept optional `$options['columnMapping']` array in parse()
   - Default mapping for common field names: date, amount, description, merchant, account
   - Support synonym fallbacks: "Posted Date" → "Date", "Transaction Amount" → "Amount"
   - Use WMMC pattern: `str_getcsv()` → `array_combine()` → field extraction

2. **Parsing Logic**:
   - Read CSV file with proper quote/escape handling
   - Extract header row (row 1)
   - Normalize header names (lowercase, map to standard fields)
   - Parse data rows (2+)
   - Group transactions by statement date or account
   - Return array of ParsedStatementDTO objects

3. **Exception Handling**:
   - `FileNotFoundException::create()` - file not found/readable
   - `UnsupportedFileTypeException::create()` - not a CSV file
   - `ParsingFailedException::withLineContent()` - CSV parsing errors with line content
   - `EncodingMismatchException::create()` - detect and report encoding issues

4. **Dependencies**:
   - None (use PHP native functions: str_getcsv(), array_combine(), fopen())
   - Optional: ChargeCalculator service for amount parsing

5. **Configuration**:
   - Support bank-specific column mappings via `$options['columnMapping']` parameter
   - Include predefined mappings for ro_bcr, ro_ing, ro_wmmc
   - Allow custom mappings without code changes

**Example Usage**:
```php
$parser = new CsvParser();
$result = $parser->parse('/path/to/file.csv', [
    'columnMapping' => [
        'transactionDate' => 'Date',
        'amount' => 'Amount',
        'description' => 'Merchant Name',
        'account' => 'Card Number'
    ]
]);
// Returns: array of ParsedStatementDTO
```

---

### TASK-P1-002: Create OFXParser (Wrapper)

**File**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/OFXParser.php`

**Wraps Library**: `ksfraser/ksf_ofxparser`

**Interface Contract**: Same as CsvParser (ParserInterface)

**Implementation Requirements**:

1. **Library Integration**:
   - Load `ksfraser/ksf_ofxparser` via composer
   - Parse OFX/QFX file structure
   - Extract statement and transaction records

2. **Mapping to ParsedStatementDTO**:
   - OFX Statement → ParsedStatementDTO
   - OFX Transaction → Transaction data in DTO
   - Handle OFX date formats (YYYYMMDD)
   - Convert currency codes

3. **Supported Formats**:
   - OFX 1.0 (text-based)
   - OFX 2.0 (XML-based)
   - QFX (Intuit format variant)

4. **Exception Handling**:
   - `FileNotFoundException::create()` - file not found
   - `UnsupportedFileTypeException::create()` - not OFX/QFX file
   - `ParsingFailedException::create()` - OFX structure errors
   - `EncodingMismatchException::create()` - encoding issues

5. **MIME Types**: ['application/vnd.intu.qbo', 'x-ofx', 'application/x-ofx']

---

### TASK-P1-003: Create QIFParser (Wrapper)

**File**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/QIFParser.php`

**Wraps Library**: `ksfraser/qifparser`

**Interface Contract**: Same as CsvParser (ParserInterface)

**Implementation Requirements**:

1. **Library Integration**:
   - Load `ksfraser/qifparser` via composer
   - Parse QIF file format (Quicken/QuickBooks interchange)
   - Extract transaction records

2. **Mapping to ParsedStatementDTO**:
   - QIF entries → ParsedStatementDTO (may be single statement)
   - Handle QIF account context
   - Parse QIF date format

3. **Supported Format**: QIF (Quicken Interchange Format)

4. **Exception Handling**:
   - Same as OFXParser
   - `FileNotFoundException::create()` - file not found
   - `UnsupportedFileTypeException::create()` - not QIF file
   - `ParsingFailedException::create()` - QIF parsing errors
   - `EncodingMismatchException::create()` - encoding issues

5. **MIME Type**: ['application/x-qif', 'text/x-qif']

---

### TASK-P1-004: Create ParserFactory

**File**: `src/Ksfraser/FaBankImport/Import/Services/Parsers/ParserFactory.php`

**Interface Contract**:
```php
interface ParserFactoryInterface {
    public function create(string $filePath): ParserInterface;
    public function getAvailableParsers(): array;
    public function detectFileType(string $filePath): string;
}
```

**Implementation Requirements**:

1. **File Type Detection**:
   - Use `finfo_file()` to detect MIME type
   - Fallback to file extension if MIME unavailable
   - Map MIME → Parser instance

2. **Parser Registry**:
   - CsvParser: ['text/csv', 'application/csv']
   - OFXParser: ['application/vnd.intu.qbo', 'x-ofx', 'application/x-ofx']
   - QIFParser: ['application/x-qif', 'text/x-qif']

3. **Factory Logic**:
   ```php
   public function create(string $filePath): ParserInterface {
       if (!file_exists($filePath)) {
           throw FileNotFoundException::create($filePath);
       }
       
       $mimeType = $this->detectFileType($filePath);
       
       return match($mimeType) {
           'text/csv' => new CsvParser(),
           'application/vnd.intu.qbo' => new OFXParser(),
           'application/x-qif' => new QIFParser(),
           default => throw UnsupportedFileTypeException::create($mimeType, array_keys($this->parsers))
       };
   }
   ```

4. **Exception Handling**:
   - `FileNotFoundException::create()` - file not found
   - `UnsupportedFileTypeException::create()` - unknown file type

---

### TASK-P1-005: Write Unit Tests

**Test Files**:
- `tests/Unit/Services/Parsers/CsvParserTest.php` (14 test cases)
- `tests/Unit/Services/Parsers/OFXParserTest.php` (10 test cases)
- `tests/Unit/Services/Parsers/QIFParserTest.php` (10 test cases)
- `tests/Unit/Services/Parsers/ParserFactoryTest.php` (9 test cases)

**Test Coverage Requirements** (85% minimum):

#### CsvParser Tests
1. ✓ Parse valid CSV file with standard headers
2. ✓ Parse CSV with alternative column names (synonyms)
3. ✓ Parse CSV with custom column mapping via options
4. ✓ Handle missing required columns → ValidationException
5. ✓ Handle file encoding detection
6. ✓ Handle malformed CSV (unclosed quotes, escape issues)
7. ✓ Handle empty/blank lines in CSV
8. ✓ Parse bank-specific format (ro_wmmc example)
9. ✓ Parse bank-specific format (ro_bcr example)
10. ✓ FileNotFoundException on missing file
11. ✓ UnsupportedFileTypeException on non-CSV
12. ✓ ParsingFailedException on corrupted CSV
13. ✓ EncodingMismatchException on encoding mismatch
14. ✓ Return array of ParsedStatementDTO objects

#### OFXParser Tests
1. ✓ Parse valid OFX 1.0 file
2. ✓ Parse valid OFX 2.0 (XML) file
3. ✓ Parse QFX file format
4. ✓ Handle missing required OFX fields
5. ✓ Handle date format conversion (YYYYMMDD)
6. ✓ Handle currency conversion
7. ✓ FileNotFoundException on missing file
8. ✓ UnsupportedFileTypeException on non-OFX
9. ✓ ParsingFailedException on malformed OFX
10. ✓ Return ParsedStatementDTO objects

#### QIFParser Tests
1. ✓ Parse valid QIF file
2. ✓ Handle QIF account context
3. ✓ Parse QIF transactions
4. ✓ Handle QIF date format
5. ✓ Handle missing required QIF fields
6. ✓ FileNotFoundException on missing file
7. ✓ UnsupportedFileTypeException on non-QIF
8. ✓ ParsingFailedException on malformed QIF
9. ✓ Return ParsedStatementDTO objects
10. ✓ Handle multiple statements in single file

#### ParserFactory Tests
1. ✓ Create CsvParser for CSV files
2. ✓ Create OFXParser for OFX/QFX files
3. ✓ Create QIFParser for QIF files
4. ✓ detectFileType returns correct MIME for known files
5. ✓ Extension fallback works when MIME detection unavailable
6. ✓ FileNotFoundException on missing files
7. ✓ UnsupportedFileTypeException for unknown types
8. ✓ getAvailableParsers returns all registered parsers
9. ✓ List available MIME types for error messages

---

## 3. Test Data Requirements

Create test files in `tests/fixtures/parsers/`:

| File | Format | Purpose |
|------|--------|---------|
| valid_walmart.csv | CSV | WMMC format with modern headers |
| valid_bcr.csv | CSV | BCR format with 9,14,15... indices |
| valid_ing.csv | CSV | ING format with dynamic headers |
| encoding_utf16.csv | CSV | UTF-16 encoded file |
| corrupted_quotes.csv | CSV | Unclosed quotes, escape errors |
| valid_ofx1.ofx | OFX 1.0 | Text-based OFX format |
| valid_ofx2.xml | OFX 2.0 | XML-based OFX format |
| valid_qfx.qfx | QFX | Intuit QFX variant |
| valid_qif.qif | QIF | Quicken format |

Each test file should include:
- Valid transaction examples
- Edge cases (boundary amounts, special characters)
- Multiple statements/accounts where applicable

---

## 4. Implementation Sequence

### Phase 1: CsvParser (2-3 hours)
1. Study WMMC parser column mapping approach
2. Create abstract column mapping configuration
3. Implement parse() method with header detection
4. Implement ParsedStatementDTO mapping
5. Add exception handling
6. Write 14 comprehensive tests
7. Verify 85%+ coverage

### Phase 2: External Library Parsers (2-3 hours)
1. OFXParser wrapping ksfraser/ksf_ofxparser
2. QIFParser wrapping ksfraser/qifparser
3. Write 10 tests each
4. Verify exception handling
5. Verify 85%+ coverage

### Phase 3: ParserFactory (1 hour)
1. Create MIME type detection
2. Implement parser routing
3. Write 9 tests
4. Verify file type detection fallbacks

### Phase 4: Verification & Commit (1 hour)
1. Run all 43+ tests
2. Verify 85%+ coverage per parser
3. Verify Phase2_1_FoundationTest still passes (26/26)
4. Git commit with semantic message

**Total Estimated Time**: 6-8 hours

---

## 5. Success Criteria

### Code Quality
- ✅ All parsers implement ParserInterface exactly
- ✅ 85%+ code coverage per parser
- ✅ No public methods beyond interface
- ✅ All dependencies injected or none (stateless)
- ✅ Full phpDoc on all methods
- ✅ Exception chaining with $previous parameter

### Exception Handling
- ✅ FileNotFoundException thrown appropriately
- ✅ UnsupportedFileTypeException with context
- ✅ ParsingFailedException with line/reason
- ✅ EncodingMismatchException with detected/expected
- ✅ All exceptions caught and converted (no generic \Exception)

### Functional Correctness
- ✅ CsvParser handles 3+ column mapping patterns
- ✅ OFXParser wraps library correctly
- ✅ QIFParser wraps library correctly
- ✅ ParserFactory detects all file types
- ✅ All tests passing (43+)
- ✅ Phase2_1_FoundationTest still 26/26 ✓

### Git Compliance
- ✅ Semantic commit messages
- ✅ Branch: chore/phase-0-shared-kernel (existing)
- ✅ Optional: PR for review

---

## 6. References

- WMMC Parser (flexible header mapping): [includes/ro_wmmc_csv_parser.php](includes/ro_wmmc_csv_parser.php)
- BCR Parser (index-based): [includes/ro_bcr_csv_parser.php](includes/ro_bcr_csv_parser.php)
- OFX Library: `ksfraser/ksf_ofxparser` (in `composer.json`)
- QIF Library: `ksfraser/qifparser` (in `composer.json`)
- ParserInterface: [src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php](src/Ksfraser/FaBankImport/Import/Services/ParserInterface.php)
- ParsedStatementDTO: [src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php](src/Ksfraser/FaBankImport/Import/DTOs/ParsedStatementDTO.php)

