# Database Schema for Parser Synonyms (Phase 2 - Future)

## Overview
When database-backed synonyms are implemented, they will enable dynamic synonym management without code redeploys. This schema supports:
- Universal synonyms (apply to all parser types)
- Parser-specific synonyms (csv, ofx, qif, mt940)
- Synonym aliases (alternative names for the same field)
- Audit trail (created/updated timestamps)

## Tables

### `parser_synonyms` (Main)
Stores column header synonyms associated with standard field names.

```sql
CREATE TABLE parser_synonyms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_name VARCHAR(64) NOT NULL,
    synonym VARCHAR(255) NOT NULL,
    parser_type ENUM('csv', 'ofx', 'qif', 'mt940', 'ALL') NOT NULL DEFAULT 'ALL',
    priority INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255),
    updated_by VARCHAR(255),
    
    UNIQUE INDEX uk_field_synonym_parser (field_name, synonym, parser_type),
    INDEX idx_parser_type (parser_type),
    INDEX idx_field_name (field_name),
    INDEX idx_is_active (is_active)
);
```

### `parser_field_definitions` (Reference)
Defines standard field names that parsers map to.

```sql
CREATE TABLE parser_field_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_name VARCHAR(64) NOT NULL UNIQUE,
    field_type VARCHAR(32) NOT NULL, -- string, amount, date, etc.
    is_required BOOLEAN DEFAULT FALSE,
    description VARCHAR(500),
    example_values VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_field_name (field_name)
);
```

### `bank_parser_configurations` (Bank-to-Parser Mapping)
Associates banks with preferred parsers and synonym configurations.

```sql
CREATE TABLE bank_parser_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_code VARCHAR(16) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    parser_type ENUM('csv', 'ofx', 'qif', 'mt940') NOT NULL,
    csv_separator CHAR(1) DEFAULT ',',
    csv_encoding VARCHAR(32) DEFAULT 'UTF-8',
    custom_synonyms_json JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX uk_bank_code_parser (bank_code, parser_type),
    INDEX idx_parser_type (parser_type)
);
```

### `parser_synonym_audit_log` (Audit Trail - Optional)
Tracks changes to synonyms for compliance and debugging.

```sql
CREATE TABLE parser_synonym_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    synonym_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by VARCHAR(255),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_synonym_id (synonym_id),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (synonym_id) REFERENCES parser_synonyms(id) ON DELETE CASCADE
);
```

## Sample Data

### Field Definitions
```sql
INSERT INTO parser_field_definitions (field_name, field_type, is_required, description, example_values) VALUES
('transactionDate', 'date', TRUE, 'Transaction posting date', 'Date, Transaction Date, Posted Date, valueDate'),
('amount', 'amount', TRUE, 'Transaction amount', 'Amount, Sum, Debit, Credit'),
('merchant', 'string', FALSE, 'Merchant/Beneficiary name', 'Merchant Name, Beneficiary, Vendor'),
('description', 'string', FALSE, 'Transaction description', 'Description, memo, Narration'),
('reference', 'string', FALSE, 'Transaction reference number', 'Reference, Check Number, Invoice Number'),
('account', 'string', TRUE, 'Account identifier', 'Account, Card Number, Account Number'),
('currency', 'string', TRUE, 'Currency code (ISO 4217)', 'Currency, CurrencyCode, USD, EUR');
```

### Initial Synonyms
```sql
INSERT INTO parser_synonyms (field_name, synonym, parser_type, priority) VALUES
-- Universal synonyms (all parsers)
('transactionDate', 'Date', 'ALL', 100),
('transactionDate', 'Transaction Date', 'ALL', 95),
('transactionDate', 'Posted Date', 'ALL', 90),
('amount', 'Amount', 'ALL', 100),
('amount', 'Sum', 'ALL', 90),
-- Parser-specific
('transactionDate', 'D', 'qif', 100),
('amount', 'T', 'qif', 100),
('reference', 'TRN', 'ofx', 90),
('reference', 'FITID', 'ofx', 85);
```

### Bank Configurations
```sql
INSERT INTO bank_parser_configurations (bank_code, bank_name, parser_type, custom_synonyms_json) VALUES
('ro_wmmc', 'Banca Românească (WMMC)', 'csv', NULL),
('ro_bcr', 'Banca Comercială Română', 'csv', NULL),
('ro_ing', 'ING Bank', 'csv', NULL);
```

## API Layer (Future)

### Repository Pattern
```php
class ParserSynonymRepository {
    public function getSynonymsForField(string $fieldName, string $parserType = 'ALL'): array
    public function addSynonym(string $fieldName, string $synonym, string $parserType = 'ALL'): void
    public function removeSynonym(int $id): void
    public function updateSynonym(int $id, array $data): void
    public function searchSynonyms(string $query): array
}
```

### Admin Service (for UI)
```php
class AdminParserSynonymService {
    public function listSynonymsByField(string $fieldName): array
    public function listSynonymsByParserType(string $parserType): array
    public function bulkImportSynonyms(array $synonyms): ImportResult
    public function exportSynonymsAsJson(): string
    public function validateSynonym(string $fieldName, string $synonym): ValidationResult
}
```

## Migration Timeline

### Phase 2.2.1 (Current)
- ✅ SynonymResolver service (in-memory, file-backed)
- ✅ Runtime synonym injection
- ✅ Config file support (JSON)
- ✅ Backward compatible with hardcoded defaults

### Phase 2.x (Future - Separate Epic)
- DB schema creation
- Repository layer implementation
- Admin service with validation
- UI component for managing synonyms
- Migration of existing synonyms to DB
- Caching layer for performance

### Phase 3.x (Long-term)
- ML-based synonym suggestion
- Synonyms audit trail
- Multi-tenancy support (if needed)
- Synonym versioning

## Future UI Features (Mockup)

The admin UI will include:

1. **Synonym Management Table**
   - List all synonyms with filters (field, parser type, active status)
   - Add new synonyms with validation
   - Edit/delete existing synonyms
   - Bulk import/export from JSON

2. **Bank Configuration Panel**
   - Select parser type per bank
   - Custom synonym overrides per bank
   - CSV encoding/separator settings
   - Test file upload for column detection

3. **Synonym Assistant**
   - Upload CSV file
   - Auto-detect columns
   - Show confidence scores
   - Suggest new synonyms based on patterns
   - Manual mapping override

4. **Audit Trail**
   - Who changed what and when
   - Rollback capability
   - Change log per synonym

## Design Principles

1. **Backward Compatible**: Phase 2.2.1 doesn't require DB; works standalone
2. **Optionally Extensible**: DB layer is added later without breaking changes
3. **Performance**: In-memory cache + file fallback for synonyms
4. **Validation**: UI validates synonyms before storing (no dupes, length limits)
5. **Audit**: Track all changes for compliance
