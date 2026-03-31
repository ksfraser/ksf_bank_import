# Phase 0: Shared Kernel Implementation Guide

## Overview

Phase 0 establishes the foundation layer (`Shared/`) that all 4 modules depend on. This document provides detailed guidance for completing Phase 0 implementation.

**Current Status**: 
- ✅ Phase 0.1: Directory structure complete
- ✅ Phase 0.2: DTO consolidation complete (10 DTOs + 18 file updates)
- 🔄 Phase 0.3: Entity consolidation in progress (2/11 entities completed)
- 🔄 Phase 0.4: Interface contracts in progress (3/4 repository interfaces + exception hierarchy)
- ❌ Phase 0.5-0.8: Not yet started

---

## Phase 0.3: Entity Consolidation (Continued)

### Completed Entities

1. **BiTransaction** (`Shared/Entities/BiTransaction.php`)
   - Immutable domain entity for single transaction
   - Factory methods: `create()` for new, `fromDatabase()` for loading
   - 25+ properties with typed getters only
   - Invariants validated in constructor

2. **BiStatement** (`Shared/Entities/BiStatement.php`)
   - Immutable domain entity for statement with transactions
   - Manages list of BiTransaction entities
   - Factory methods follow same pattern as BiTransaction
   - 14 properties, supports transaction aggregation

### Remaining Entities (Priority Order)

#### Priority 3: BiLineItem
**Source**: `class.bi_lineitem.php`
**Current State**: Mixed concerns (Entity + UI rendering + FA integration)
**Refactoring Steps**:
1. Extract core entity properties from database schema comments
2. Remove all HTML rendering code
3. Create `BiLineItem` Entity with immutable pattern
4. Factory methods for creation and loading
5. Key properties: id, bi_transaction_id, amount, fa_gl_account, fa_memo, etc.

**Template**:
```php
<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

final class BiLineItem {
    private int $id;
    private int $biTransactionId;
    private float $amount;
    private ?int $faGlAccount;
    private string $faMemo;
    // ... other properties
    
    private function __construct(int $biTransactionId, float $amount) {
        // validate invariants
        $this->id = 0;
        $this->biTransactionId = $biTransactionId;
        $this->amount = $amount;
        // ... initialize defaults
    }
    
    public static function create(int $biTransactionId, float $amount): self {
        return new self($biTransactionId, $amount);
    }
    
    public static function fromDatabase(array $row): self {
        // populate from DB row
    }
    
    // Getters only
    public function getId(): int { return $this->id; }
    public function getAmount(): float { return $this->amount; }
    // ... other getters
}
```

#### Priority 4: BankAccount (Mapping)
**Source**: `class.bi_bank_accounts.php`
**Current State**: Static utility class with FA coupling
**Refactoring Strategy**:
1. Create `BankAccountMapping` Entity for individual mapping
2. Keep repository as implementation detail
3. Entity focuses on data, repository handles persistence

**Template**:
```php
<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

final class BankAccountMapping {
    private int $id;
    private int $faAccountId;
    private string $bankId;
    private string $acctId;
    private string $intuBid;
    
    // Factory methods, getters, toDatabase()
}
```

#### Priority 5: BankPartner
**Source**: `class.bi_partners_data.php`
**Refactoring**: Similar pattern to BankAccountMapping
**Key Properties**: id, partner_id (FK to FA), partner_type, bank_code, etc.

#### Priority 6: CounterpartyModel
**Source**: `class.bi_counterparty_model.php`
**Refactoring**: May be ValueObject rather than Entity (immutable)

#### Priority 7: TransactionTitleModel
**Source**: `class.bi_transactionTitle_model.php`
**Refactoring**: Likely ValueObject (parts of narrative title)

#### Priority 8: TransferMatches
**Source**: `class.bi_transfer_matches.php`
**Refactoring**: Relationship entity linking transfers

#### Priority 9: Contact (Legacy)
**Source**: `class.bi_contact.php`
**Refactoring**: Already using traits; light refactor needed

#### Priority 10: Transaction (Transactions Model)
**Source**: `class.bi_transactions.php`
**Refactoring**: Base model class; extract key functionality

### Remaining Repository Interfaces

Already created:
- ✅ TransactionRepositoryInterface
- ✅ StatementRepositoryInterface
- ✅ BankAccountMappingRepositoryInterface

Still needed:
1. **LineItemRepositoryInterface**
   ```php
   interface LineItemRepositoryInterface {
       public function findById(int $id): BiLineItem;
       public function findByTransactionId(int $txId): array;
       public function save(BiLineItem $lineItem): void;
       public function delete(int $id): void;
   }
   ```

2. **BankPartnerRepositoryInterface**
   ```php
   interface BankPartnerRepositoryInterface {
       public function findById(int $id): BankPartner;
       public function findByPartnerId(int $partnerId): array;
       public function save(BankPartner $partner): void;
   }
   ```

3. **CounterpartyRepositoryInterface**
4. **TransferMatchRepositoryInterface**

---

## Phase 0.4: Interface Contracts (In Progress)

### Completed

✅ **Exception Hierarchy** (`Shared/Exceptions/Exceptions.php`)
- BaseKsfException (root)
- InvalidTransactionException
- InvalidStatementException
- RepositoryException (and subclasses)
- ConfigurationException
- ModuleBootstrapException
- ContainerException (and subclasses)

✅ **Repository Interfaces**
- TransactionRepositoryInterface
- StatementRepositoryInterface
- BankAccountMappingRepositoryInterface

### Remaining Contracts to Create

1. **ModuleBootstrapInterface** (`Shared/Contracts/ModuleBootstrapInterface.php`)
   ```php
   <?php
   namespace Ksfraser\FaBankImport\Shared\Contracts;
   
   use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;
   
   interface ModuleBootstrapInterface {
       /**
        * Bootstrap module and register services in container
        * Called during application initialization
        */
       public static function bootstrap(ServiceContainer $container): void;
       
       /**
        * Get module identifier (must be unique)
        */
       public static function getModuleId(): string;
   }
   ```

2. **DuplicateDetectionInterface** (`Shared/Contracts/DuplicateDetectionInterface.php`)
   - Used by Import module to call Dedupe module
   - Defines contract for checking duplicates

3. **TransactionProcessorInterface** (`Shared/Contracts/TransactionProcessorInterface.php`)
   - Used by Process module to call Admin module
   - Defines contract for transaction processing

4. **ImportHandlerInterface** (`Shared/Contracts/ImportHandlerInterface.php`)
   - Defines contract for import operations
   - Used for orchestration between modules

---

## Phase 0.5: Dependency Container

### ServiceContainer Class

**File**: `Shared/Container/ServiceContainer.php`

**Responsibilities**:
- Register services with optional factory functions
- Resolve services by name/interface
- Manage singleton vs. transient lifecycles
- Throw `ServiceNotFoundException` if not found

**Minimal Implementation**:
```php
<?php
namespace Ksfraser\FaBankImport\Shared\Container;

use Ksfraser\FaBankImport\Shared\Exceptions\ServiceNotFoundException;

final class ServiceContainer {
    private array $services = [];
    private array $singletons = [];
    
    public function register(string $name, callable $factory): void {
        $this->services[$name] = $factory;
    }
    
    public function registerSingleton(string $name, callable $factory): void {
        $this->services[$name] = $factory;
        // Mark as singleton
    }
    
    public function resolve(string $name): mixed {
        if (!isset($this->services[$name])) {
            throw new ServiceNotFoundException("Service not found: $name");
        }
        // Return cached singleton or call factory
        return $this->services[$name]($this);
    }
}
```

### ModuleRegistry Class

**File**: `Shared/Container/ModuleRegistry.php`

**Responsibilities**:
- Track which modules are loaded
- Call bootstrap for each module
- Provide module lookup

---

## Phase 0.6: Configuration & Exceptions

### Configuration Loading

**File**: `Shared/Config/Config.php`

- Load from environment variables
- Load from config files (yaml/php)
- Override per environment (dev/test/prod)
- Cache configuration

**Example**:
```php
<?php
namespace Ksfraser\FaBankImport\Shared\Config;

final class Config {
    private array $config = [];
    
    public static function load(string $env = 'prod'): self {
        $config = new self();
        // Load defaults
        // Load environment-specific overrides
        return $config;
    }
    
    public function get(string $key, mixed $default = null): mixed {
        // Hierarchical get with dot notation support
    }
}
```

### Exception Hierarchy

Already created in file: `Shared/Exceptions/Exceptions.php`

Usage pattern:
```php
try {
    $tx = BiTransaction::create(...);
} catch (InvalidTransactionException $e) {
    // Handle validation errors
} catch (RepositoryException $e) {
    // Handle persistence errors
}
```

---

## Phase 0.7: Unit Tests

### Test Structure

```
tests/
├── unit/
│   └── Shared/
│       ├── Entities/
│       │   ├── BiTransactionTest.php
│       │   ├── BiStatementTest.php
│       │   └── BiLineItemTest.php
│       ├── Repositories/
│       │   ├── TransactionRepositoryTest.php
│       │   └── StatementRepositoryTest.php
│       ├── Config/
│       │   └── ConfigTest.php
│       └── Container/
│           └── ServiceContainerTest.php
```

### Example Test

```php
<?php
namespace Tests\Unit\Shared\Entities;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidTransactionException;

final class BiTransactionTest extends TestCase {
    public function testCreateThrowsIfFitIdEmpty(): void {
        $this->expectException(InvalidTransactionException::class);
        BiTransaction::create(1, '', 'acctid', 100, 'title');
    }
    
    public function testCreateThrowsIfAcctIdEmpty(): void {
        $this->expectException(InvalidTransactionException::class);
        BiTransaction::create(1, 'fitid', '', 100, 'title');
    }
    
    public function testFromDatabaseRestoresAllProperties(): void {
        $row = [
            'id' => 123,
            'smt_id' => 45,
            'fitid' => 'fit123',
            'acctid' => 'acct456',
            // ... other fields
        ];
        
        $tx = BiTransaction::fromDatabase($row);
        $this->assertEquals(123, $tx->getId());
        $this->assertEquals(45, $tx->getSmtId());
    }
    
    public function testToDatabasePreparesForPersistence(): void {
        $tx = BiTransaction::create(1, 'fit1', 'acct1', 100.50, 'title');
        $db = $tx->toDatabase();
        
        $this->assertEquals('fit1', $db['fitid']);
        $this->assertEquals(100.50, $db['transactionAmount']);
    }
}
```

### Coverage Target: ≥80%

Focus on:
- Factory method creation
- Invariant validation
- Property getters
- Database round-tripping (toDatabase/fromDatabase)
- Exception cases

---

## Phase 0.8: Bootstrap Integration

### Root Entry Point

**File**: `src/bootstrap.php` or `public/index.php`

```php
<?php
// Initialize Shared Kernel
$container = new ServiceContainer();

// Initialize configuration
Config::load(getenv('APP_ENV') ?? 'prod');

// Bootstrap modules in dependency order
foreach (['Shared', 'Admin', 'Dedupe', 'Import', 'Process'] as $moduleName) {
    $bootstrapClass = "Ksfraser\\FaBankImport\\{$moduleName}\\Bootstrap";
    $bootstrapClass::bootstrap($container);
}

// Application is ready
return $container;
```

---

## Integration Checklist

### Before Phase 0 Completion

- [ ] All DTOs use `Ksfraser\FaBankImport\Shared\DTOs` namespace (verified: grep 0 results for old namespace)
- [ ] All Entities in `Shared/Entities/` are immutable (no setters)
- [ ] All Repository Interfaces in `Shared/Repositories/`
- [ ] Exception hierarchy defined in `Shared/Exceptions/Exceptions.php`
- [ ] ServiceContainer implemented in `Shared/Container/ServiceContainer.php`
- [ ] Config loading in `Shared/Config/Config.php`
- [ ] Unit tests written for all Shared components (80%+ coverage)
- [ ] Bootstrap integration tested

### After Phase 0 Completion

Each module (Admin, Dedupe, Import, Process) needs:
- Bootstrap class implementing ModuleBootstrapInterface
- Service registration in container
- Module-specific DTOs if needed
- Module-specific Entities if needed

---

## Performance Benchmarks

After Phase 0 implementation, target metrics:
- Container resolution: <1ms per service
- Entity creation: <0.5ms
- Repository find operations: <50ms typical
- Full bootstrap: <100ms

Test with: `php -d zend_extension=xdebug.so tests/ --benchmark`

---

## Rollback Strategy

If Phase 0 causes issues:
1. Keep old DTO namespace active alongside new one (deprecation period)
2. Entity changes are additive (don't remove legacy classes yet)
3. Repository interfaces are contracts only (keep old implementations)

---

## Next Steps After Phase 0

1. **Phase 1 (Admin Module)**: Refactor with new DI
2. **Phase 2 (Dedupe Module)**: Implement interfaces
3. **Phase 3 (Import Module)**: Use Shared repositories
4. **Phase 4 (Process Module)**: Orchestrate with Shared contracts

All modules depend on Phase 0 completion. Estimated total time: 40 hours over 1 week.

