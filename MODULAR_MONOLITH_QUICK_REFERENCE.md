# Modular Monolith: Quick Reference Guide

**For**: Developers working on ksf_bank_import  
**Purpose**: Quick lookup for module structure, responsibilities, and cross-module patterns  
**Date**: 2026-03-28

---

## Module Overview (1-Minute Summary)

```
┌─────────────────────────────────────────────────────────┐
│         Ksfraser\FaBankImport\Shared\                   │
│  (DTOs • Entities • Interfaces • Exceptions • Config)   │
│                                                         │
│ ✅ Depends on: Nothing except PHP stdlib               │
│ ✅ Used by: ALL modules                                │
│ ✅ Breaking changes: Major version bump required       │
└─────────────────────────────────────────────────────────┘
                          ▲
          ┌───────────────┼───────────────┐
          │               │               │
    ┌─────┴──────┐  ┌────┴─────┐  ┌──────┴──────┐
    │  Process   │  │  Import   │  │   Dedupe    │
    ├────────────┤  ├───────────┤  ├─────────────┤
    │ TX routing │  │ Parse &   │  │ Duplicate   │
    │ GL posting │  │ Validate  │  │ Detection   │
    │ Partners   │  │ & Stage   │  │ & Review    │
    └────────────┘  └───────────┘  └─────────────┘
    Entry point:     Entry point:   Entry point:
    process_        import_        transfer_match_
    statements.php  statements.php  review.php
    
    Plus: Admin module (configuration)
```

---

## Which Module Does What?

| **Need to...** | **Module** | **Where** | **File** |
|---|---|---|---|
| Upload bank statement | Import | Controllers | ImportPipelineController.php |
| Parse CSV/XLS files | Import | Parsers | *Parser.php |
| Validate transactions | Import | Services | ValidationService.php |
| Check for duplicates | Dedupe | Services | DuplicateDetectionService.php |
| Manage duplicate whitelist | Dedupe | Controllers | DuplicateReviewController.php |
| Process transaction (GL post) | Process | Actions | ProcessTransactionAction.php |
| Route to right processor | Process | Dispatcher | ActionDispatcher.php |
| Configure parser | Admin | Controllers | ParserAdminController.php |

---

## Module Responsibilities

### Import
```
Responsibility: FILE → STATEMENTS
                ↓
         Upload → Parse → Validate → Transform → Dedupe Check → Stage

LOC:      ~1,200 lines
Team:     Bank import specialists
Entry:    import_statements.php
Exit:     ImportResultDTO (staged for review)
Depends:  Shared + Dedupe interface
```

### Dedupe
```
Responsibility: TX ⟷ TX → MATCH SCORE
                ↓
         Exact Match ↓ Fuzzy Match ↓ Rules → Decision

LOC:      ~600 lines
Team:     Business rules / data quality
Entry:    transfer_match_review.php (UI) OR
          DuplicateDetectionInterface (called by Import)
Exit:     DuplicateCheckResultDTO (matches) OR
          AuditResultDTO (whitelist decision)
Depends:  Shared ONLY (no other modules!)
```

### Process
```
Responsibility: TX → GL POST + UPDATES
                ↓
         Route → Partner Type Handler → GL Post → Result

LOC:      ~1,000 lines
Team:     Accounting / GL integration
Entry:    process_statements.php
Exit:     ProcessResultDTO (success/error)
Depends:  Shared + Dispatcher pattern
```

### Admin
```
Responsibility: CONFIGURATION MANAGEMENT
                ↓
         Parser Config ↓ Rules Config ↓ Settings

LOC:      ~400 lines
Team:     Bank admins / system config
Entry:    admin_parsers.php, admin_transfer_rules.php
Exit:     Configuration updates
Depends:  Shared + can reference others via interfaces
```

### Shared
```
Responsibility: STABLE CONTRACTS & DATA STRUCTURES
                ↓
         DTOs ↓ Entities ↓ Interfaces ↓ Exceptions

LOC:      ~1,200 lines
Team:     Core architecture (changes carefully!)
Depends:  Nothing (only PHP stdlib)
Stability: Breaking changes = major version + notification
```

---

## When to Add Code to a Module

### ✅ Add to Import if...
- Parsing bank statements
- Validating transaction formats
- Calling duplicate detection
- Staging statements for review

### ✅ Add to Dedupe if...
- Creating matching algorithms
- Defining duplicate rules
- Auditing duplicate decisions
- **NO direct dependencies on other modules**

### ✅ Add to Process if...
- Processing transaction through GL
- Partner-specific business logic
- Dispatching transaction actions
- Posting GL entries

### ✅ Add to Admin if...
- Configuration UI for parsers
- Rules management interface
- System settings
- **Can reference other modules, but via config, not domain logic**

### ✅ Add to Shared if...
- New DTO needed by multiple modules
- New interface contract
- New entity/value object
- Exception types
- **Careful: Breaking changes affect all modules!**

---

## Dependencies: What Can Reference What?

```
              Shared
        (can reference this)
              ▲
        ┌─────┼─────┬─────┐
        │     │     │     │
     Process Import Dedupe Admin

✅ ALLOWED:     ❌ NOT ALLOWED:
- Process → Shared       - Process → Import (direct)
- Import → Shared        - Process → Dedupe (direct)
- Import → Dedupe (interface)  - Import → Process
- Dedupe → Shared        - Dedupe → Import
- Admin → Shared         - Dedupe → Process
- Admin → Any (via interfaces)  - Any → Any (directly)
```

**RULE**: If module A needs things from module B, use an interface in Shared and dependency injection.

---

## Code Patterns: Do's & Don'ts

### ❌ DON'T: Direct imports between modules

```php
// In Import module
use Dedupe\Services\DuplicateDetectionService;  // ❌ NO!

$detector = new DuplicateDetectionService();    // ❌ NO!
```

### ✅ DO: Use interfaces and dependency injection

```php
// In Import module
use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;

class DuplicateDetectionHandler {
    public function __construct(
        private DuplicateDetectionInterface $detector  // ✅ Injected!
    ) {}
    
    public function handle(ImportStateDTO $state) {
        $result = $this->detector->detectDuplicates($tx);  // ✅ Via interface!
    }
}
```

### ❌ DON'T: Pass module-specific objects across modules

```php
// Import module calling Process module
$result = $processController->process(
    new Import\DTOs\CustomTransactionDTO()  // ❌ Module-specific!
);
```

### ✅ DO: Use Shared DTOs

```php
// Import module calling Dedupe module
$result = $this->duplicateDetector->detectDuplicates(
    new Shared\DTOs\TransactionDTO()  // ✅ Shared!
);
```

### ❌ DON'T: Hardcoded static calls

```php
// In Process module
require_once 'Dedupe/Services/DuplicateDetectionService.php';
$detector = DuplicateDetectionService::getInstance();  // ❌ NO!
```

### ✅ DO: Constructor injection

```php
// In Process module
class ProcessController {
    public function __construct(
        private DuplicateDetectionInterface $detector  // ✅ Injected in bootstrap!
    ) {}
}
```

---

## File Lookup Quick Reference

### "I need to find the X code"

| Looking for... | Path |
|---|---|
| Import handler for duplicates | `Import/Handlers/DuplicateDetectionHandler.php` |
| Duplicate matching logic | `Dedupe/Services/DirectCodeMatcher.php` |
| Transaction routing | `Process/Dispatcher/ActionDispatcher.php` |
| Transaction DTO definition | `Shared/DTOs/TransactionDTO.php` |
| Test for Import module | `tests/unit/Import/*/YourTest.php` |
| Interface for duplicate detection | `Shared/Contracts/DuplicateDetectionInterface.php` |
| Parser configuration | `Admin/Controllers/ParserAdminController.php` |
| GL posting logic | `Process/Processors/SupplierProcessor.php` |

---

## Testing Quick Reference

### Run tests for specific module

```bash
# Just Import module tests
vendor/bin/phpunit --testsuite Import

# Just Dedupe (fastest, no dependencies)
vendor/bin/phpunit --testsuite Dedupe

# Just Process
vendor/bin/phpunit --testsuite Process

# Cross-module integration tests
vendor/bin/phpunit --testsuite Integration

# Everything
vendor/bin/phpunit

# Single test file
vendor/bin/phpunit tests/unit/Import/Handlers/DuplicateDetectionHandlerTest.php

# Single test method
vendor/bin/phpunit --filter testDuplicateDetectionHandlerCallsService
```

### Create test for Import module handler

```php
// tests/unit/Import/Handlers/DuplicateDetectionHandlerTest.php

namespace Tests\Import\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Handlers\DuplicateDetectionHandler;
use Ksfraser\FaBankImport\Shared\Contracts\DuplicateDetectionInterface;
use Ksfraser\FaBankImport\Shared\DTOs\TransactionDTO;

class DuplicateDetectionHandlerTest extends TestCase {
    public function testCallsDuplicateDetectorForEachTransaction() {
        // Create mock Dedupe interface
        $mockDetector = $this->createMock(DuplicateDetectionInterface::class);
        $mockDetector->expects($this->once())
            ->method('detectDuplicates')
            ->willReturn(/* mock result */);
        
        // Create handler with injected mock
        $handler = new DuplicateDetectionHandler($mockDetector);
        
        // Test
        $result = $handler->handle($state);
        
        // Assertions
        $this->assertNotNull($result);
    }
}
```

---

## Adding New Feature Checklist

### Before starting

- [ ] Which module does this logically belong to?
- [ ] Is it importing/parsing? → Import
- [ ] Is it detecting duplicates? → Dedupe
- [ ] Is it posting GL/updating records? → Process
- [ ] Is it configuration? → Admin
- [ ] Is it core data structure? → Shared (be careful!)

### Writing code

- [ ] Created code ONLY in that module's folder
- [ ] Used dependency injection for cross-module calls
- [ ] Passed interfaces, not concrete classes
- [ ] Used Shared DTOs for cross-module data
- [ ] No direct imports from other modules

### Testing

- [ ] Wrote unit tests in `tests/unit/ModuleName/`
- [ ] Tests pass: `phpunit --testsuite ModuleName`
- [ ] Integration tests pass: `phpunit --testsuite Integration`
- [ ] No new errors in `phpunit`

### Reviewing

- [ ] Code follows module boundaries
- [ ] No circular dependencies
- [ ] Interface contracts respected
- [ ] Dependency injection used
- [ ] Tests cover >80% of code

---

## Extracting Module to Separate Package (Future)

When ready to make Price module its own Composer package:

### Step 1: Create separate Git repo
```bash
git remote add faba-process git@github.com:org/faba-process.git
git subtree split --prefix=src/Ksfraser/FaBankImport/Process \
    -b faba-process-branch
git push faba-process faba-process-branch:main
```

### Step 2: Add composer.json
```json
{
    "name": "faba/process",
    "version": "1.0.0",
    "require": {
        "faba/shared-kernel": "^1.0"
    }
}
```

### Step 3: Update root composer.json
```json
{
    "require": {
        "faba/process": "^1.0",
        "faba/import": "^2.0"
    }
}
```

### Step 4: NO code changes needed!
- All interfaces stay the same
- All DTOs work the same
- Namespace can stay same or change

---

## Architecture Documents

| Document | Purpose | Read if... |
|---|---|---|
| MODULAR_MONOLITH_ARCHITECTURE.md | Full architecture overview | Understanding overall structure |
| ADR-002-MODULAR-MONOLITH...md | Architectural decision | Wondering "why this architecture?" |
| CONTRACT_SPECIFICATIONS.md | Module interfaces & contracts | Integrating across modules |
| MODULE_DEVELOPMENT_GUIDE.md | How to dev in a module | Adding new feature |
| MODULE_EXTRACTION_PROCESS.md | Extract to separate package | Making package from module |

---

## Common Questions

### Q: Can Process module call Dedupe directly?
**A**: NO. Use `DuplicateDetectionInterface` injected via constructor. Dedupe registers implementation in bootstrap.

### Q: Where do I test cross-module scenarios?
**A**: `tests/integration/` folder. Unit tests stay in `tests/unit/ModuleName/`.

### Q: What if I need shared logic?
**A**: Add to Shared if used by 2+ modules. If only one module uses it, keep in module.

### Q: Can I add a new DTO?
**A**: If used by 1 module → put in that module's DTOs/.
If used by 2+ → put in Shared/DTOs/ (be careful with breaking changes).

### Q: How do I handle errors across modules?
**A**: Define exceptions in Shared/Exceptions/, implement in module.

### Q: Can Admin module depend on Process directly?
**A**: NO. Admin can configure Process, but via interfaces/configuration, not domain logic.

### Q: How do I know if dependencies are good?
**A**: Run: `grep -r "use Ksfraser.*Import" src/Ksfraser/FaBankImport/Process/`
If that shows nothing, your Process module doesn't directly import Import. ✅

---

## Emergency: I Made a Circular Dependency!

If Import imports Process and Process imports Import:

```bash
# Find the circular reference
grep -r "use Ksfraser.*Import\|use Ksfraser.*Process" src/

# Undo your recent changes
git log --oneline  # Find your commit
git revert <commit-hash>

# Rethink the design
# - Move logic to Shared if both modules need it
# - Use interface injection instead of direct import
# - Break the import into two separate concerns
```

---

## Performance: Each Module Load Times

```
Shared kernel:         ~50ms (always loaded)
Process module:        ~30ms (thin dispatcher)
Import module:         ~40ms (heavy parsers)
Dedupe module:         ~20ms (lightweight)
Admin module:          ~15ms (just UI)

Full PHP startup:      ~100-150ms per request
```

Module tests run faster because they don't load unneeded modules!

---

## Next Steps

1. Read `MODULAR_MONOLITH_ARCHITECTURE.md` (full details)
2. Read `CONTRACT_SPECIFICATIONS.md` (interface contracts)
3. Review `ADR-002` (why this architecture)
4. Start Phase 0: Create Shared kernel
5. Then Phase 1: Extract Process module

