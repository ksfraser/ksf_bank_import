# Legacy bi_* Class Dependencies Audit

**Generated:** 2026-03-30  
**Scope:** Full ksf_bank_import codebase search  
**Exclusions:** class.bi_*.php definitions, legacy test files (tests/legacy/*), backup files, vendor/

---

## Summary Statistics

- **bi_transaction**: 5 active files (core legacy class)
- **bi_transactions_model**: 9 active files (plural variant)
- **bi_lineitem**: 3 active files
- **bi_statements_model**: 4 active files
- **bi_bank_accounts_model**: 12 active files
- **bi_counterparty_model**: 2 active files
- **bi_partners_data**: 6 active files
- **bi_transfer_matches_model**: 7 active files
- **bi_transactionTitle_model**: 0 active files (reference only in comments)
- **bi_contact**: 6 self-references within class definition (keep Ksfraser\Contact\DTO\ContactData)

---

## Detailed Findings by Legacy Class

### 1. **bi_transaction** (Singular Repository Class)

#### Direct Instantiations
| File | Line | Context | Syntax |
|------|------|---------|--------|
| [class.bank_import_controller.php](class.bank_import_controller.php#L15) | 15 | Module load | `require_once( 'class.bi_transaction.php' )` |
| [class.bank_import_controller.php](class.bank_import_controller.php#L37) | 37 | Constructor: `$this->repository = new bi_transaction()` | Direct instantiation in `__construct()` |
| [class.bank_import_controller.php](class.bank_import_controller.php#L218) | 218 | Method `get_transaction()`: `$bi_t = new bi_transaction()` | Direct instantiation |
| [includes/GetTransCounterparty.php](includes/GetTransCounterparty.php#L6) | 6 | Module load | `require_once( __DIR__ . '/../class.bi_transactions.php' )` |
| [GetTransaction.php](GetTransaction.php#L22) | 22 | Module load | `require_once( 'class.bi_transactions.php' )` |

**Key Notes:**
- Primarily used as repository pattern in `bank_import_controller`
- Property: `$this->repository` typed as bi_transaction
- Used for `get_transaction()` method calls

---

### 2. **bi_transactions_model** (Plural Repository - Preferred Variant)

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [import_statements.php](import_statements.php#L287) | 287 | Anonymous function | `$bit = new bi_transactions_model()` |
| [process_statements.php](process_statements.php#L279) | 279 | Anonymous function | `$bit = new bi_transactions_model()` |
| [class.bank_import_controller.php](class.bank_import_controller.php#L300) | 300 | Method `handle_upload_request()` | `$repository = new bi_transactions_model()` |
| [class.bank_import_controller.php](class.bank_import_controller.php#L446) | 446 | Method `list_transactions()` | `$bit = new bi_transactions_model()` |
| [class.bi_lineitem.php](class.bi_lineitem.php#L670) | 670 | Method `_find_duplicate_transfer()` | `$bi_t = new bi_transactions_model()` |
| [Services/TransferMatchService.php](Services/TransferMatchService.php#L25) | 25 | Constructor | `$this->transferMatches = $transferMatches ?: new \bi_transfer_matches_model()` |
| [transfer_match_review.php](transfer_match_review.php#L25) | 25 | Script root | `$transferMatches = new bi_transfer_matches_model()` |
| [tests/BiStatementsModelTest.php](tests/BiStatementsModelTest.php#L50) | 50 | Test method | `$result = $this->bi_statements->get_statement()` |
| [src/Ksfraser/Model/BiLineItemModel.php](src/Ksfraser/Model/BiLineItemModel.php#L376) | 376 | Method | `$bi_t = new bi_transactions_model()` |

#### Require Statements
| File | Lines |
|------|-------|
| [import_statements.php](import_statements.php#L264) | 264 |
| [process_statements.php](process_statements.php#L278) | 278 |
| [Services/TransferMatchService.php](Services/TransferMatchService.php#L5) | 5 |
| [Services/TransferMatchAuditService.php](Services/TransferMatchAuditService.php#L5) | 5 |
| [Services/PairedTransferProcessor.php](Services/PairedTransferProcessor.php#L181) | 181, 322 |

**Key Notes:**
- Primary production model for transactions
- Used for `get_transactions()`, `get_transaction()` queries
- Property type: `$this->repository`, `$this->transactions`

---

### 3. **bi_lineitem**

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [process_statements.php](process_statements.php#L325) | 325 | Inside loop | `$bi_lineitem = new bi_lineitem( $trz, $vendor_list, $optypes )` |
| [class.bi_lineitem.php](class.bi_lineitem.php#L88) | 88 | Class definition | `require_once( __DIR__ . '/class.bi_transfer_matches.php' )` |

#### Pattern: Self-References Within Class
- [class.bi_lineitem.php](class.bi_lineitem.php#L179): Property `public $transferMatchModel` (used internally)
- [class.bi_lineitem.php](class.bi_lineitem.php#L1367): `instanceof bi_transfer_matches_model` check

**Key Notes:**
- Constructor requires: `$trz`, `$vendor_list`, `$optypes`
- Has internal `$transferMatchModel` property for delegation
- Used to process line items from statements

---

### 4. **bi_statements_model**

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [import_statements.php](import_statements.php#L199) | 199 | Anonymous function | `$bis = new bi_statements_model()` |
| [includes/test_import.php](includes/test_import.php#L56) | 56 | Script | `$bis = new bi_statements_model()` |

#### Require Statements
| File | Line |
|------|------|
| [import_statements.php](import_statements.php#L264) | 264 (requires bi_transactions) |

**Key Notes:**
- Lightweight data model for bank statements
- Not heavily coupled to main logic
- Primary usage in utility scripts

---

### 5. **bi_bank_accounts_model**

#### Requires/Static Calls (No Instantiation)
| File | Line | Function | Usage |
|------|------|----------|-------|
| [import_statements.php](import_statements.php#L902) | 902 | `faBankAccountNumberExists()` | Static: `bi_bank_accounts_model::fa_get_bank_account_id_by_number()` |
| [import_statements.php](import_statements.php#L911) | 911 | `faBankAccountId()` | Static: `bi_bank_accounts_model::fa_get_bank_account_id_by_number()` |
| [import_statements.php](import_statements.php#L917) | 917 | `bi_bank_accounts_table_exists()` | Static: `bi_bank_accounts_model::table_exists()` |
| [import_statements.php](import_statements.php#L923) | 923 | `bi_bank_accounts_get_row()` | Static: `bi_bank_accounts_model::get_row()` |
| [import_statements.php](import_statements.php#L929) | 929 | `bi_bank_accounts_upsert()` | Static: `bi_bank_accounts_model::upsert()` |
| [import_statements.php](import_statements.php#L1160) | 1160 | `resolve_detected_accounts_via_bi_bank_accounts()` | Static: `bi_bank_accounts_model::resolve_detected_accounts_to_bank_account_numbers()` |
| [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php#L25-L26) | 25-26 | Constructor | Inline instantiation: `(new \bi_partners_data())->ensure_schema()` → calls `(new \bi_transfer_matches_model())->ensure_schema()` |

**Key Notes:**
- All-static usage pattern (no instantiation in main code)
- Used to resolve bank account mappings
- Wrapper functions in `import_statements.php` provide higher-level API
- Extensive use in account confirmation workflow (lines 996-1148 in import_statements.php)

---

### 6. **bi_counterparty_model**

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [tests/BiCounterpartyModelTest.php](tests/BiCounterpartyModelTest.php#L12) | 12 | Constructor | `$this->biCounterpartyModel = new bi_counterparty_model()` |
| [src/Ksfraser/FaBankImport/tests/BiCounterpartyModelTest.php](src/Ksfraser/FaBankImport/tests/BiCounterpartyModelTest.php#L25) | 25 | Constructor | `$this->biCounterpartyModel = new bi_counterparty_model()` |

#### Require Statements
| File | Line |
|------|------|
| [src/Ksfraser/FaBankImport/tests/BiCounterpartyModelTest.php](src/Ksfraser/FaBankImport/tests/BiCounterpartyModelTest.php#L17) | 17 |

**Key Notes:**
- Minimal usage - only in test files
- Used as data model for counterparty records
- Not directly instantiated in production code paths

---

### 7. **bi_partners_data**

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [src/Ksfraser/Model/BiLineItemModel.php](src/Ksfraser/Model/BiLineItemModel.php#L422) | 422 | Method `build_keywordlist()` | `$pd = new bi_partners_data()` |
| [tests/BiPartnersDataTest.php](tests/BiPartnersDataTest.php#L11) | 11 | Constructor | `$this->biPartnersData = new bi_partners_data()` |
| [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php#L26) | 26 | Constructor | `(new \bi_partners_data())->ensure_schema()` |
| [src/Ksfraser/FaBankImport/tests/BiPartnersDataTest.php](src/Ksfraser/FaBankImport/tests/BiPartnersDataTest.php#L22) | 22 | Constructor | `$this->biPartnersData = new bi_partners_data()` |

#### Require Statements
| File | Line |
|------|------|
| [import_statements.php](import_statements.php) | Multiple (functions) |
| [build_partner_keyword_data.php](build_partner_keyword_data.php) | Multiple direct table refs |
| [src/Ksfraser/Model/BiLineItemModel.php](src/Ksfraser/Model/BiLineItemModel.php#L421) | 421 |
| [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php#L25) | 25 |

**Key Notes:**
- Table: TB_PREF + `bi_partners_data`
- Used extensively in [build_partner_keyword_data.php](build_partner_keyword_data.php) for INSERT/UPDATE operations
- Two entry paths: direct table manipulation + model class wrapping

---

### 8. **bi_transfer_matches_model**

#### Direct Instantiations
| File | Line | Method | Usage |
|------|------|--------|-------|
| [class.bi_transactions.php](class.bi_transactions.php#L585) | 585 | Method (conditional) | `$transferMatches = new bi_transfer_matches_model()` |
| [class.bi_lineitem.php](class.bi_lineitem.php#L1369) | 1369 | Property lazy loader | `$this->transferMatchModel = new bi_transfer_matches_model()` |
| [transfer_match_review.php](transfer_match_review.php#L25) | 25 | Script root | `$transferMatches = new bi_transfer_matches_model()` |
| [Services/TransferMatchService.php](Services/TransferMatchService.php#L25) | 25 | Constructor | `$this->transferMatches = $transferMatches ?: new \bi_transfer_matches_model()` |
| [Services/TransferMatchAuditService.php](Services/TransferMatchAuditService.php#L22) | 22 | Constructor | `$this->transferMatches = $transferMatches ?: new \bi_transfer_matches_model()` |
| [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php#L29) | 29 | Constructor | `(new \bi_transfer_matches_model())->ensure_schema()` |

#### Require Statements
| File | Line |
|------|------|
| [class.bi_transactions.php](class.bi_transactions.php#L47) | 47 |
| [class.bi_lineitem.php](class.bi_lineitem.php#L88) | 88 |
| [transfer_match_review.php](transfer_match_review.php#L11) | 11 |
| [Services/TransferMatchService.php](Services/TransferMatchService.php#L6) | 6 |
| [Services/TransferMatchAuditService.php](Services/TransferMatchAuditService.php#L6) | 6 |
| [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php#L28) | 28 |

**Key Notes:**
- Lazy-loaded pattern in `bi_lineitem` (line 1367 conditional check)
- Strong use in Transfer Match services (Service namespace)
- Primary methods: `get_candidates_for_transaction()`, schema validation
- Table: TB_PREF + `bi_transfer_matches`

---

### 9. **bi_contact**

#### Direct Self-References (Within class.bi_contact.php)
**⚠️ NOTE:** These are internal to the class definition. NO external files instantiate `bi_contact`.

| Line | Type | Context |
|------|------|---------|
| 369 | `new bi_contact($db)` | Method: getSessionKey() |
| 400 | `new bi_contact($db)` | Method: getSessionKey() variant |
| 439 | `new bi_contact($db)` | Method: getPartnerDetailId() |
| 474 | `new bi_contact($db)` | Method: toContactData() |
| 505 | `new bi_contact($db)` | Method: toContactData() variant |
| 561 | `new bi_contact($db)` | Method: clearSession() |

#### Status
✅ **SAFE TO MIGRATE** - No external dependencies found. These are internal factory methods.

#### Important
- `bi_contact` should NOT be converted to use ContactData directly - keep as-is for backwards compatibility
- Internal to class, not a public dependency for other code
- Only preserve `Ksfraser\Contact\DTO\ContactData` imports where they appear

---

### 10. **bi_transactionTitle_model**

#### Status
⚠️ **REFERENCE ONLY** - No active usage found in code

#### References Found
- [app/Shared/Entities/TransactionTitle.php](app/Shared/Entities/TransactionTitle.php#L8) - **Comment only:** "This is a consolidation of the legacy bi_transactionTitle_model class"
- [app/Shared/Entities/TransactionTitle.php](app/Shared/Entities/TransactionTitle.php#L12) - **Property:** `public $id_bi_transactionTitle_model` (architectural artifact)
- [app/Shared/index.php](app/Shared/index.php#L37) - **Comment documentation**

**Status:** Can be safely removed/migrated without runtime impact.

---

## Files NOT Importing Legacy Classes (Modern Code Paths)

The following files use new ModularMonolith patterns exclusively:

- `src/Ksfraser/FaBankImport/` (entire namespace - uses new Entities)
- `app/Shared/Entities/` (all - replacement models)
- `Services/` (TransferMatchService, TransferMatchAuditService - some legacy deps, but wrapper pattern)

---

## Migration Roadmap

### Phase 1: No External Deps (Safe First)
- ✅ **bi_contact** - Only self-references, safe to leave as-is
- ✅ **bi_transactionTitle_model** - No active usage

### Phase 2: Service Wrapper Pattern (Medium Risk)
- **bi_bank_accounts_model** - All use via wrapper functions in import_statements.php
  - **Action:** Create BankAccountMappingService wrapper
  - **Affected:** import_statements.php functions (lines 901-1161)

### Phase 3: Core Replacements (High Risk)
- **bi_transaction/bi_transactions_model** - Replace with Transaction entity
  - **Critical files:** class.bank_import_controller.php, Services/*
  - **Action:** Implement TransactionRepository pattern

- **bi_statements_model** - Replace with BankStatement entity
  - **Affected:** import_statements.php, process_statements.php

- **bi_lineitem** - Replace with LineItemModel wrapper
  - **Affected:** process_statements.php, Services/*

- **bi_partners_data** - Replace with PartnerKeyword entity
  - **Affected:** build_partner_keyword_data.php, BiLineItemModel

- **bi_counterparty_model** - Replace with Counterparty entity
  - **Affected:** Test files only (low risk)

- **bi_transfer_matches_model** - Replace with TransferMatch entity
  - **Critical files:** Services/TransferMatch*.php, class.bi_lineitem.php

---

## Excluded Locations (As Requested)

### ✅ Properly Excluded:
- ✅ `class.bi_*.php` definition files (source)
- ✅ `tests/legacy/` directory
- ✅ Backup files: `*.bak`, `*.old`, `~`, `.corrupted`, `.prod`
- ✅ `vendor/` directory
- ✅ `duplicates_backup_*/` directories

### ℹ️ Included (Active Code Paths):
- ✅ `Services/` - Production service layer
- ✅ `src/Ksfraser/` - Namespace migrations in progress
- ✅ `app/` - New modular monolith
- ✅ `tests/` - Active test suite
- ✅ Root scripts: import_statements.php, process_statements.php, etc.

---

## Integration Points Summary

| Class | Primary Use | Service Layer | Test Files |
|-------|------------|---------------|-----------|
| bi_transaction | BankImportController | - | - |
| bi_transactions_model | Queries/reporting | TransferMatchService | BiStatementsModelTest |
| bi_lineitem | Statement processing | - | - |
| bi_statements_model | Model access | - | BiStatementsProductionBaselineTest |
| bi_bank_accounts_model | Account resolution | Import wrapper functions | BiCounterpartyModelProductionBaselineTest |
| bi_counterparty_model | Data model | - | BiCounterpartyModelTest |
| bi_partners_data | Partner keywords | BiLineItemModel | BiPartnersDataTest |
| bi_transfer_matches_model | Transfer matching | TransferMatch* Services | - |
| bi_transactionTitle_model | *Unused* | - | - |
| bi_contact | Self-contained | - | BiContactTest |

