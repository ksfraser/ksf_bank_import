# CSV Parser + Synonym Resolver Guide

## Overview

The refactored CSV Parser now uses `SynonymResolver` to provide flexible, configurable column mapping without hardcoding. This supports:

- **3-tier synonym resolution**: Runtime → Config File → Hardcoded Defaults
- **Parser-specific synonyms**: Different parsers (csv, ofx, qif) can have their own synonym lists
- **Universal synonyms**: Synonyms that apply to all parsers  
- **Fluent API**: Easy runtime configuration

## Usage Scenarios

### Scenario 1: Use Parser with Defaults (WMMC without changes)

```php
$parser = new CsvParser();
$statements = $parser->parse('/path/to/wmmc-statement.csv');
// Result: Uses hardcoded WMMC synonyms automatically
```

### Scenario 2: Load Synonyms from Config File

WMMC changes their header format. Instead of redeploying code, create a config file:

```json
// config/parsers/wmmc-synonyms.json
{
  "synonyms": {
    "transactionDate": [
      "Date",
      "Transaction Date",
      "PostingDate",
      "MatureDate"  // NEW format
    ],
    "amount": [
      "Amount", 
      "Sum",
      "Value"  // NEW format
    ]
  }
}
```

Then use it:

```php
$parser = new CsvParser();
$statements = $parser->parse('/path/to/wmmc-statement.csv', [
    'synonymConfigFile' => 'config/parsers/wmmc-synonyms.json'
]);
// Result: Uses custom synonyms, automatically picks up new "MatureDate", "Value" headers
```

### Scenario 3: Runtime Custom Synonyms for One-Off Files

A customer sends a CSV with non-standard headers on one file:

```php
$parser = new CsvParser();
$statements = $parser->parse('/path/to/custom-export.csv', [
    'customSynonyms' => [
        'transactionDate' => ['Txn Date', 'Date', 'Posted Date'],
        'amount' => ['Txn Amount', 'Amount', 'Sum'],
        'merchant' => ['Vendor', 'Merchant Name', 'Company']
    ]
]);
// Result: Parses file using custom synonyms without touching global config
```

### Scenario 4: Parser-Specific Synonyms (QIF vs CSV)

Different parsers have different field name conventions:

```json
// config/parsers/universal-synonyms.json
{
  "synonyms": {
    "transactionDate": ["Date", "Posted Date"],
    "amount": ["Amount", "Sum"]
  },
  "parserSpecific": {
    "qif": {
      "transactionDate": ["D", "Date"],
      "amount": ["T", "Amount"]
    },
    "csv": {
      "transactionDate": ["Date", "Posted Date"],
      "amount": ["Amount", "Sum"]
    },
    "ofx": {
      "reference": ["FITID", "TRN"]
    }
  }
}
```

Parser automatically uses appropriate synonyms:

```php
$csvParser = new CsvParser();
$qifParser = new QIFParser();

$resolver = new SynonymResolver('config/parsers/universal-synonyms.json');

$csvStatements = $csvParser->parse('export.csv', [
    'customSynonyms' => $resolver->getAllSynonyms('csv')
]);

$qifStatements = $qifParser->parse('export.qif', [
    'customSynonyms' => $resolver->getAllSynonyms('qif')
]);
```

### Scenario 5: Programmatic Synonym Addition

Add synonyms at runtime for specific banks:

```php
$resolver = new SynonymResolver();

// Add new WMMC format variations
$resolver->addSynonym('transactionDate', 'MatureDate', 'csv');
$resolver->addSynonym('transactionDate', 'SettlementDate', 'csv');

// Add universal synonym (all parsers)
$resolver->addSynonym('merchant', 'Counterparty', 'ALL');

$csvParser = new CsvParser($resolver);
$statements = $csvParser->parse('/path/to/statement.csv');
```

### Scenario 6: Combine All Methods (Strongest to Weakest)

```php
$customResolver = new SynonymResolver('config/parsers/global-synonyms.json');

// Priority builds: global config → custom runtime → hardcoded defaults
$parser = new CsvParser($customResolver);

$statements = $parser->parse('file.csv', [
    'customSynonyms' => [
        'transactionDate' => ['PostDate'],  // Highest priority
        'amount' => ['Qty']
    ]
]);
```

Resolution order:
1. Runtime `customSynonyms['transactionDate']`: ['PostDate']
2. Config file synonyms (already loaded)
3. Hardcoded defaults

## SynonymResolver API

### Constructor
```php
// No args - use hardcoded defaults only
$resolver = new SynonymResolver();

// With config file path
$resolver = new SynonymResolver('config/parsers/synonyms.json');
```

### Methods

#### `loadConfigFile(string $filePath): void`
Load synonyms from JSON config file. Throws exception if file not found or invalid JSON.

```php
$resolver = new SynonymResolver();
$resolver->loadConfigFile('config/synonyms.json');
```

#### `setRuntimeSynonyms(array $synonyms): self`
Set runtime synonyms (overrides config). Returns self for fluent chaining.

```php
$resolver->setRuntimeSynonyms([
    'transactionDate' => ['Posted', 'ValueDate'],
    'amount' => ['Debit']
])->addSynonym('merchant', 'PaymentTo', 'csv');
```

#### `getSynonymsForField(string $fieldName, string $parserType = 'csv'): array`
Get resolved synonyms for a specific field. Resolution order:
1. Parser-specific config
2. Universal runtime
3. Universal config
4. Defaults

```php
$dateAliases = $resolver->getSynonymsForField('transactionDate', 'csv');
// ['Date', 'Posted Date', 'MatureDate', ... custom ones ...]
```

#### `getFieldNameForHeader(string $headerValue, string $parserType = 'csv'): ?string`
Find field name for a CSV column header. Returns null if no match.

```php
$fieldName = $resolver->getFieldNameForHeader('MatureDate', 'csv');
// Returns: 'transactionDate'
```

#### `getAllSynonyms(string $parserType = 'csv'): array`
Get complete resolved synonym map for a parser type.

```php
$allcsv Synonyms = $resolver->getAllSynonyms('csv');
// [
//   'transactionDate' => ['Date', 'Posted Date', ...],
//   'amount' => ['Amount', 'Sum', ...],
//   ...
// ]
```

#### `addSynonym(string $fieldName, string $synonym, string $parserType = 'csv'): self`
Add single synonym at runtime. Use 'ALL' or '*' for universal.

```php
$resolver
    ->addSynonym('transactionDate', 'NewDateFormat', 'csv')
    ->addSynonym('merchant', 'Vendor', 'ALL');
```

#### `getSupportedFields(): array`
Get list of standard field names recognized by parser.

```php
$fields = $resolver->getSupportedFields();
// ['transactionDate', 'amount', 'merchant', 'description', ...]
```

## Config File Format (JSON)

### Minimal Config (Just Universal Synonyms)
```json
{
  "synonyms": {
    "transactionDate": ["Date", "Posted Date"],
    "amount": ["Amount", "Sum"]
  }
}
```

### Full Config (With Parser-Specific)
```json
{
  "synonyms": {
    "transactionDate": ["Date", "Posted Date"],
    "amount": ["Amount", "Sum"],
    "merchant": ["Beneficiary", "Vendor"],
    "description": ["Memo", "Details"],
    "reference": ["Ref", "Check Number"],
    "category": ["Type", "Classification"],
    "account": ["Account Number", "Card"],
    "currency": ["Currency Code", "ISO Code"]
  },
  "parserSpecific": {
    "csv": {
      "transactionDate": ["Date", "Posted Date", "MatureDate"],
      "amount": ["Amount", "Sum"]
    },
    "ofx": {
      "reference": ["FITID", "TRN"]
    },
    "qif": {
      "transactionDate": ["D", "Date"],
      "amount": ["T", "Amount"]
    }
  }
}
```

## Best Practices for WMMC Format Changes

### When WMMC Changes Their CSV Format...

**Option A: Update Global Config (Recommended)**
1. Detect new format in production
2. Update `config/parsers/wmmc-synonyms.json` with new field names
3. Redeploy config (no code change needed)
4. All WMMC files use new synonyms immediately

**Option B: Bank-Specific Config**
```php
// config/bank-configs.php
return [
    'ro_wmmc' => [
        'new_format_v2' => [
            'synonymConfigFile' => 'config/parsers/wmmc-v2-synonyms.json',
            'encoding' => 'UTF-8'
        ]
    ]
];
```

Use it:
```php
$config = require 'config/bank-configs.php';
$parser = new CsvParser();
$statements = $parser->parse('wmmc-statement.csv', $config['ro_wmmc']['new_format_v2']);
```

**Option C: Auto-Detect (Future - Machine Learning)**
Planned enhancement: Parser auto-detects format version from file content and loads appropriate synonyms.

## Migration Strategy (Existing Code)

If you have existing code that manually constructs column maps:

### Before (Hardcoded)
```php
$columnMap = [
    0 => 'transactionDate',
    3 => 'amount',
    7 => 'merchant'
];
```

### After (Config-Based)
```json
// config/parsers/custom-bank.json
{
  "synonyms": {
    "transactionDate": ["Date"],
    "amount": ["Amount"],
    "merchant": ["Merchant"]
  }
}
```

```php
$parser = new CsvParser();
$statements = $parser->parse('file.csv', [
    'synonymConfigFile' => 'config/parsers/custom-bank.json'
]);
```

## Troubleshooting

### Issue: Headers not matching custom synonyms

**Check Resolution Order**: Debug with this:
```php
$fieldName = $resolver->getFieldNameForHeader('Your Header Here', 'csv');
var_dump($fieldName); // Should return field name or null

// See all synonyms for debugging
$all = $resolver->getAllSynonyms('csv');
var_dump($all);
```

### Issue: Config file not loading

**Verify**:
```php
try {
    $resolver = new SynonymResolver('path/to/config.json');
} catch (\RuntimeException $e) {
    echo "Config Error: " . $e->getMessage();
}
```

### Issue: Parser uses wrong synonyms

**Check Priority**:
1. Runtime synonyms trump all
2. Config file middle
3. Hardcoded fallback

If passing `customSynonyms` in options AND a SynonymResolver with config file, config wins (by design - local override).

## Future: Database-Backed Synonyms (Phase 2)

When implemented, the same API remains unchanged:

```php
// Still works the same way
$resolver = new DatabaseSynonymResolver();
$resolver->loadFromDatabase('table_name');
$synonyms = $resolver->getSynonymsForField('transactionDate');

// Or via UI - admin interface manages synonyms without code
```

## Performance Considerations

- **Config File Loading**: Done once per parser instance (lazy loaded)
- **Memory**: All synonyms loaded into instance memory (~1KB per 100 synonyms)
- **Caching**: Consider caching resolver instance in DI container for high-throughput scenarios
- **Database Phase**: Will include optional Redis caching layer

## Questions & Support

For new formats from banks:
1. Add to config file
2. Test with sample CSV
3. No code deployment needed
4. Track changes in git

For complex cases:
1. Create bank-specific config
2. Use DI container for flexible injection
3. Add unit test with sample data
4. Document in this guide
