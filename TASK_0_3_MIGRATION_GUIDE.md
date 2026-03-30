# Task 0.3: Import Update Migration Guide (Phase 2)

**Status:** In Progress  
**Date:** 2026-03-30  
**Phase:** 0.3 - Update all files to reference Shared kernel classes  
**Total Files to Update:** 40+  
**Estimated Effort:** Complete systematic refactoring of entire codebase

---

## Overview

This document provides the complete migration pattern and step-by-step instructions for updating all 40+ files in ksf_bank_import to use the Shared kernel classes instead of legacy bi_* classes.

**Key Principle:** Comment out old references, don't delete them. This allows easy review and validation.

---

## Migration Pattern (Apply to All Files)

### Pattern 1: At top of file - require_once() statements

**BEFORE:**
```php
require_once(__DIR__ . '/class.bi_transactions.php');
require_once(__DIR__ . '/class.bi_lineitem.php');
```

**AFTER:**
```php
// TODO [Phase-0-review]: Moved to Shared kernel - see app/Shared/Entities/
// Old: require_once(__DIR__ . '/class.bi_transactions.php');
// New namespace: use Ksfraser\FaBankImport\Shared\Entities\Transaction;
require_once(__DIR__ . '/class.bi_transactions.php'); // TEMP: Keep for compatibility

// Old: require_once(__DIR__ . '/class.bi_lineitem.php');
// New namespace: use Ksfraser\FaBankImport\Shared\Entities\LineItem;
require_once(__DIR__ . '/class.bi_lineitem.php'); // TEMP: Keep for compatibility
```

### Pattern 2: Direct instantiation

**BEFORE:**
```php
$transaction = new bi_transactions_model();
$lineitem = new bi_lineitem($trz, $vendor_list, $optypes);
```

**AFTER:**
```php
// TODO [Phase-0-review]: Transition to Ksfraser\FaBankImport\Shared\Entities\Transaction
$transaction = new bi_transactions_model(); // TEMP: Legacy class

// TODO [Phase-0-review]: Transition to Ksfraser\FaBankImport\Shared\Entities\LineItem
$lineitem = new bi_lineitem($trz, $vendor_list, $optypes); // TEMP: Legacy class
```

### Pattern 3: Static method calls

**BEFORE:**
```php
$exists = bi_bank_accounts_model::table_exists();
$row = bi_bank_accounts_model::get_row($id);
```

**AFTER:**
```php
// TODO [Phase-0-review]: Migrate to Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping
$exists = bi_bank_accounts_model::table_exists(); // TEMP: Legacy static call
// TODO [Phase-0-review]: Migrate to Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping
$row = bi_bank_accounts_model::get_row($id); // TEMP: Legacy static call
```

---

## LEGACY → SHARED MAPPING REFERENCE

| Legacy Class | New Shared Namespace | Status |
|---|---|---|
| `bi_transaction` | `Ksfraser\FaBankImport\Shared\Entities\Transaction` | ✅ Created |
| `bi_transactions_model` | `Ksfraser\FaBankImport\Shared\Entities\Transaction` | ✅ Created |
| `bi_statements_model` | `Ksfraser\FaBankImport\Shared\Entities\BankStatement` | ✅ Created |
| `bi_bank_accounts_model` | `Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping` | ✅ Created |
| `bi_counterparty_model` | `Ksfraser\FaBankImport\Shared\Entities\Counterparty` | ✅ Created |
| `bi_partners_data` | `Ksfraser\FaBankImport\Shared\Entities\PartnerKeyword` | ✅ Created |
| `bi_transfer_matches_model` | `Ksfraser\FaBankImport\Shared\Entities\TransferMatch` | ✅ Created |
| `bi_lineitem` | `Ksfraser\FaBankImport\Shared\Entities\LineItem` | ✅ Created |
| `bi_contact` | ⚠️ External Package: `Ksfraser\Contact\DTO\ContactData` | ✅ Package v0.1.0 |
| `bi_transactionTitle_model` | `Ksfraser\FaBankImport\Shared\Entities\TransactionTitle` | ✅ Created |

---

## Files to Update (By Priority)

### TIER 1: CRITICAL (Core Logic)  
**Total: 3 files**  
⚠️ These block all other work

| # | File | Legacy Classes Used | Status |
|---|---|---|---|
| 1 | [import_statements.php](import_statements.php) | bi_transactions_model (9x), bi_bank_accounts_model (12x), bi_partners_data (2x) | 🟡 IN PROGRESS |
| 2 | [process_statements.php](process_statements.php) | bi_transactions_model (4x), bi_lineitem (2x) | 🟡 IN PROGRESS |
| 3 | [class.bank_import_controller.php](class.bank_import_controller.php) | bi_transaction (2x), bi_transactions_model (2x) | 🟡 IN PROGRESS |

### TIER 2: HIGH (Services & Models)  
**Total: 10 files**  
🔴 Must complete after Tier 1

| # | File | Legacy Classes Used | Status |
|---|---|---|---|
| 4 | [Services/TransferMatchService.php](Services/TransferMatchService.php) | bi_transfer_matches_model (1x) | ⏳ TODO |
| 5 | [Services/TransferMatchAuditService.php](Services/TransferMatchAuditService.php) | bi_transfer_matches_model (1x) | ⏳ TODO |
| 6 | [Services/PairedTransferProcessor.php](Services/PairedTransferProcessor.php) | bi_transfer_matches_model (2x), bi_transactions_model (1x) | ⏳ TODO |
| 7 | [class.bi_lineitem.php](class.bi_lineitem.php) | bi_transfer_matches_model (1x) | ⏳ TODO |
| 8 | [class.bi_transactions.php](class.bi_transactions.php) | bi_transfer_matches_model (1x) | ⏳ TODO |
| 9 | [src/Ksfraser/Model/BiLineItemModel.php](src/Ksfraser/Model/BiLineItemModel.php) | bi_transactions_model (1x), bi_partners_data (1x) | ⏳ TODO |
| 10 | [src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php](src/Ksfraser/FaBankImport/Service/Schema/BankImportModuleSchemaService.php) | bi_partners_data (1x), bi_transfer_matches_model (1x) | ⏳ TODO |
| 11 | [transfer_match_review.php](transfer_match_review.php) | bi_transfer_matches_model (1x) | ⏳ TODO |
| 12 | [includes/GetTransCounterparty.php](includes/GetTransCounterparty.php) | bi_transactions_model (1x) | ⏳ TODO |
| 13 | [GetTransaction.php](GetTransaction.php) | bi_transactions_model (1x) | ⏳ TODO |

### TIER 3: MEDIUM (Utilities & Includes)  
**Total: 15 files**  
🟢 Can be updated in parallel with Tier 2

| # | File | Legacy Classes Used | Status |
|---|---|---|---|
| 14 | [build_partner_keyword_data.php](build_partner_keyword_data.php) | bi_partners_data (3x) | ⏳ TODO |
| 15 | [includes/test_import.php](includes/test_import.php) | bi_statements_model (1x) | ⏳ TODO |
| 16 | [src/Ksfraser/FaBankImport/controllers/Api/ContactController.php](src/Ksfraser/FaBankImport/controllers/Api/ContactController.php) | contact-dto (1x) | ✅ DONE |
| 17 | [src/Ksfraser/FaBankImport/services/ContactDataFactory.php](src/Ksfraser/FaBankImport/services/ContactDataFactory.php) | contact-dto (1x) | ✅ DONE |
| 18 | [src/Ksfraser/FaBankImport/services/ContactService.php](src/Ksfraser/FaBankImport/services/ContactService.php) | contact-dto (1x) | ⏳ TODO |
| 19 | [src/Ksfraser/FaBankImport/services/ContactDeduplicationService.php](src/Ksfraser/FaBankImport/services/ContactDeduplicationService.php) | contact-dto (1x) | ⏳ TODO |
| 20 | [src/Ksfraser/FaBankImport/services/ContactMatchingService.php](src/Ksfraser/FaBankImport/services/ContactMatchingService.php) | contact-dto (1x) | ⏳ TODO |
| ...and 10 more test/utility files | | | ⏳ TODO |

### TIER 4: LOW (Tests & Deprecated)  
**Total: 12 files**  
🟢 Can be updated last

| # | File | Legacy Classes Used | Status |
|---|---|---|---|
| 30+ | Test files, deprecated code, etc. | Various | ⏳ TODO |

---

## Step-by-Step Execution Plan

### Phase 2A: TIER 1 (Hours 1-2)

1. **import_statements.php**
   - Find all `require_once( '/class.bi_*.php')` - comment and document
   - Find all `new bi_*_model()` - add TODO comments
   - Find all static calls like `bi_bank_accounts_model::` - add TODO comments
   - Lines to check: 199, 264, 287, 279, 300, 446, 902, 911, 917, 923, 929, 1160

2. **process_statements.php**
   - Find all `require_once( '/class.bi_*.php')`
   - Find lines: 278 (bi_transactions), 325 (bi_lineitem)
   - Add pattern comments

3. **class.bank_import_controller.php**
   - Find line 15: bi_transaction require
   - Find line 37: bi_transaction instantiation
   - Find line 218: bi_transaction in method

### Phase 2B: TIER 2 (Hours 3-4)

1. All Service files (Services/TransferMatch*.php, PairedTransferProcessor.php)
2. Legacy model classes (class.bi_*.php files themselves)
3. Schema utilities

### Phase 2C: TIER 3 (Hours 5-6)

1. Utility/helper files
2. View/display files
3. Admin scripts

### Phase 2D: TIER 4 (Hours 7-8)

1. Test files
2. Deprecated code
3. Backup/legacy paths

---

## Validation Checklist (After Each Tier)

After completing each tier:

- [ ] Run PHPUnit tests: `php vendor/bin/phpunit --configuration phpunit.xml`
- [ ] Check for syntax errors: `php -l` on each updated file
- [ ] Verify no new warnings/errors in error logs
- [ ] Confirm git diff shows TODOs but keeps temp require statements

---

## Review Process

Files should be reviewed with focus on:

1. **Syntax:** All old require statements still work (marked TEMP)
2. **TODOs:** All Phase-0-review TODOs are present
3. **No Breaking Changes:** All functionality remains the same
4. **Comments:** Clear migration path documented

---

## Notes

- **DO NOT delete** the old `class.bi_*.php` files yet - they're still being required
- **DO comment** all old references
- **DO add TODOs** for each transition point
- **Keep requires** for backward compatibility during this phase
- After complete review, a Phase 0.4 will systematically transition to new namespaces

---

## Files Already Updated (✅ Complete)

1. ✅ [app/Shared/Entities/Transaction.php](app/Shared/Entities/Transaction.php) - Created
2. ✅ [app/Shared/Entities/BankStatement.php](app/Shared/Entities/BankStatement.php) - Created
3. ✅ [app/Shared/Entities/BankAccountMapping.php](app/Shared/Entities/BankAccountMapping.php) - Created
4. ✅ [app/Shared/Entities/Counterparty.php](app/Shared/Entities/Counterparty.php) - Created
5. ✅ [app/Shared/Entities/PartnerKeyword.php](app/Shared/Entities/PartnerKeyword.php) - Created
6. ✅ [app/Shared/Entities/TransferMatch.php](app/Shared/Entities/TransferMatch.php) - Created
7. ✅ [app/Shared/Entities/LineItem.php](app/Shared/Entities/LineItem.php) - Created
8. ✅ [app/Shared/Entities/TransactionTitle.php](app/Shared/Entities/TransactionTitle.php) - Created
9. ✅ [app/Shared/DTOs/*.php](app/Shared/DTOs/) - 6 DTOs created
10. ✅ [app/Shared/ValueObjects/PartnerData.php](app/Shared/ValueObjects/PartnerData.php) - Created
11. ✅ [ContactController.php](src/Ksfraser/FaBankImport/controllers/Api/ContactController.php) - Namespace fixed
12. ✅ [ContactDataFactory.php](src/Ksfraser/FaBankImport/services/ContactDataFactory.php) - Namespace fixed
13. ✅ [process_statements.php](process_statements.php) - Partially updated
14. ✅ [class.bank_import_controller.php](class.bank_import_controller.php) - Partially updated
15. ✅ [import_statements.php](import_statements.php) - Wrapper functions updated with TODOs

---

## Next Actions

1. **Team Review:** Review LEGACY_BI_CLASS_DEPENDENCIES_AUDIT.md for full context
2. **Systematic Update:** Follow this guide's execution plan tier-by-tier
3. **Validation:** Run tests after each tier
4. **Commit:** Create commits per tier for easy rollback if needed
5. **Phase 0.4:** After all imports updated, create Phase 0.4 to transition to new namespaces

---

**Generated:** 2026-03-30  
**Related Files:** LEGACY_BI_CLASS_DEPENDENCIES_AUDIT.md, PHASE_0_IMPLEMENTATION_PLAN.md  
**Commit:** To be created after team review
