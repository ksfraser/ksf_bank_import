# Architecture Quick Reference - ksf_bank_import

## 🎯 At a Glance

**Type:** Modular Monolith with Clean Architecture  
**Language:** PHP 7.4+ with PSR-4 autoloading  
**Framework:** FrontAccounting integration  
**Patterns:** Command, Strategy, Repository, DI Container, Middleware

---

## 📍 Where to Find Things

### Entry Points
- **Web Request:** `process_statements.php` → calls `command_bootstrap.php`
- **Command Routing:** `src/Ksfraser/FaBankImport/command_bootstrap.php`
- **DI Container:** `src/Ksfraser/FaBankImport/Container.php`

### Transaction Processing
- **Handlers:** `src/Ksfraser/FaBankImport/handlers/` (Strategy Pattern)
  - `SupplierTransactionHandler`
  - `CustomerTransactionHandler`
  - `BankTransferTransactionHandler`
  - `QuickEntryTransactionHandler`
  - etc.

### Services (Business Logic)
- **Bank Mapping:** `src/Ksfraser/FaBankImport/Service/BankAccountMapping/BankAccountMappingService.php`
- **Transaction Service:** `src/Ksfraser/FaBankImport/services/TransactionService.php`
- **Monitoring:** `src/Ksfraser/FaBankImport/services/PerformanceMonitor.php` (Singleton)

### Data Models (Shared Kernel - Phase 0)
- **Entities:** `app/Shared/Entities/` (immutable domain objects)
- **DTOs:** `app/Shared/DTOs/` (cross-layer communication)
- **Repositories:** `app/Shared/Repositories/` (interfaces + legacy implementations)
- **Value Objects:** `app/Shared/ValueObjects/`

### Data Access
- **Repositories:** `src/Ksfraser/FaBankImport/Repository/`
- **Database Models:** `src/Ksfraser/FaBankImport/models/`

### Controllers & Views
- **Controllers:** `src/Ksfraser/FaBankImport/controllers/`
  - `BankImportController` (main)
  - `AdminController` (monitoring)
- **Views:** `src/Ksfraser/FaBankImport/views/` (PHP templates)

### Configuration & Setup
- **Config:** `src/Ksfraser/FaBankImport/Config/Config.php` (Singleton, dot notation)
- **Middleware:** `src/Ksfraser/FaBankImport/Middleware/` → `Application.php`
- **Routes:** `src/Ksfraser/FaBankImport/config/admin_routes.php`

### Testing
- **Test Root:** `tests/bootstrap.php`
- **PHPUnit Config:** `phpunit.xml`
- **Test Suites:** 
  - `tests/Unit/` (fast, isolated)
  - `tests/Integration/` (with database)

---

## 🏗️ Layer Diagram

```
REQUEST → process_statements.php
           ↓
BOOTSTRAP → command_bootstrap.php → Container + CommandDispatcher
           ↓
COMMAND → CommandDispatcher::dispatch($action, $_POST)
           ↓
HANDLER → SupplierTransactionHandler | CustomerTransactionHandler | ...
           ↓
SERVICE → TransactionService + ReferenceNumberService + BankAccountMappingService
           ↓
REPOSITORY → DatabasePartnerDataRepository | SquareTransactionRepository | ...
           ↓
DATABASE → FrontAccounting bi_transactions table
           ↓
RESPONSE → TransactionResult → View rendering
```

---

## 🔄 Key Design Patterns

### 1. Command Pattern (POST Handling)
**File:** `command_bootstrap.php`
```php
$dispatcher->dispatch('AddCustomer', $_POST)
  ├─ Maps to AddCustomerCommand
  ├─ Executes handler
  └─ Returns TransactionResult
```

### 2. Strategy Pattern (Transaction Processing)
**File:** `handlers/AbstractTransactionHandler.php`
```php
Factory::create($type) → SupplierHandler | CustomerHandler | ...
  └─ $handler->process($transaction)
```

### 3. Repository Pattern (Data Access)
**File:** `Repository/`, `Shared/Repositories/`
```php
Service depends on RepositoryInterface
  ├─ DatabasePartnerDataRepository (real DB)
  ├─ SquareTransactionRepository (API)
  └─ MockRepository (testing)
```

### 4. Dependency Injection
**File:** `Container.php`
```php
Container::getInstance()
  ├─ getTransactionService()
  ├─ getBankImportController()
  └─ getCommandDispatcher()
```

### 5. Middleware Pipeline
**File:** `Middleware/`, `Application.php`
```
Request
  ├─ PerformanceMonitoringMiddleware
  ├─ AuthMiddleware
  ├─ TransactionValidationMiddleware
  └─ Controller
  ← Response (processed in reverse)
```

---

## 🚀 Adding New Functionality

### Add Transaction Handler
1. Create class in `handlers/` extending `AbstractTransactionHandler`
2. Implement `getPartnerTypeConstant()` and `process()`
3. Factory auto-registers it (no manual registration needed)

### Add Service
1. Create class in `services/` with constructor dependencies
2. Add getter method to `Container.php`
3. Inject via `$this->container->getService()`

### Add Middleware
1. Create class in `Middleware/` implementing `MiddlewareInterface`
2. Add to pipeline in `Application.php::setupMiddleware()`

### Add Database Repository
1. Create class in `Repository/` implementing interface
2. Add getter to `Container.php`
3. Inject into service via constructor

---

## 📊 Data Entities

### Core Entities (`app/Shared/Entities/`)
- **Transaction:** Bank transaction record (immutable)
- **BankStatement:** Import batch container
- **BankAccountMapping:** OFX IDs → FA bank account
- **Counterparty:** Customer/vendor/bank details
- **LineItem:** GL entry in transaction
- **TransferMatch:** Paired transfer pairing

### DTOs (`app/Shared/DTOs/`)
- **ImportSummaryDTO:** Aggregates import results
- **ParseFilesDTO:** File parsing parameters
- **UploadFormDTO:** Upload form data
- **Others:** AccountResolutionDTO, DuplicateResolutionDTO, MappingConfirmationDTO

---

## ⚙️ Configuration

### Access Config
```php
$config = Config::getInstance();
$value = $config->get('db.host');           // Dot notation
$value = $config->get('key', 'default');    // With default
```

### Config Structure
```
db.host, db.name, db.user, db.pass
logging.enabled, logging.path
transaction.allowed_types, transaction.default_dc, transaction.max_amount
```

### Environment Variables
```
DB_HOST, DB_NAME, DB_USER, DB_PASS
BANK_IMPORT_DEFAULT_TRANSACTION_DC=D
USE_COMMAND_PATTERN=true
```

---

## 🧪 Testing

### Run All Tests
```bash
php vendor/bin/phpunit phpunit.xml --no-coverage
```

### Run Specific Suite
```bash
php vendor/bin/phpunit --testsuite "Shared Kernel"
php vendor/bin/phpunit --testsuite "Integration"
```

### Generate Coverage Report
```bash
php vendor/bin/phpunit --coverage-html coverage/
```

### Test Bootstrap
- `tests/bootstrap.php` - Loads autoloader, sets test environment
- `FAMock/` - FrontAccounting function mocks

---

## 📈 Performance Monitoring

### Track Metrics
Automatically tracked via `PerformanceMonitoringMiddleware`:
- `process_transaction` (handler duration)
- `list_transactions` (view render time)
- `import_batch` (total import time)

### Access From Admin Panel
```php
$monitor = PerformanceMonitor::getInstance();
$avg = $monitor->getAverageMetrics('process_transaction', 60); // 60-min window

$trends = $aggregator->getHistoricalTrends('process_transaction', 7); // 7 days
```

---

## 🔍 Common Scenarios

### Find Transaction Handler for Type
```php
$factory = new TransactionTypeFactory();
$handler = $factory->createTransactionType('SUPPLIER', $data);
```

### Get Bank Account Mapping
```php
$service = $container->getBankAccountMappingService();
$mapping = $service->getBankAccountMappingByOFXIdentifiers(
    $bankid, $acctid, $intuit_bid
);
```

### Run Command Programmatically
```php
$dispatcher = new CommandDispatcher($container);
$result = $dispatcher->dispatch('AddCustomer', [
    'trans_id' => 42,
    'name' => 'Customer Name'
]);
```

### Create Entity from Database Row
```php
$transaction = Transaction::fromDatabase($row);
$isMatched = $transaction->isMatched();
$isDebit = $transaction->isDebit();
```

### Dispatch Event
```php
$event = new TransactionProcessedEvent($transaction);
$eventDispatcher->dispatch($event);
```

---

## 🎓 Architecture Principles

1. **Dependency Inversion:** Depend on interfaces, not concrete implementations
2. **Single Responsibility:** Each class has one reason to change
3. **Open/Closed:** Open for extension (new handlers), closed for modification
4. **Layered Design:** Request flows inbound, never outbound (exceptions caught at boundaries)
5. **Fail-Safe:** Services return sensible defaults on error (null, empty array, etc.)

---

## 🚨 Anti-Patterns to Avoid

```
✗ Service directly querying database (should use Repository)
✗ Controller rendering database queries (should use Service)
✗ Handler instantiating its own dependencies (should be injected or use Container)
✗ Repository returning arrays instead of Entity objects
✗ Circular dependencies between layers
✗ Business logic in controllers or views
```

---

## 📞 Key Files Reference

| Need | File |
|------|------|
| Start here | `COMPREHENSIVE_ARCHITECTURE_BLUEPRINT.md` |
| Add handler | `src/Ksfraser/FaBankImport/handlers/AbstractTransactionHandler.php` |
| DI setup | `src/Ksfraser/FaBankImport/Container.php` |
| Configure | `src/Ksfraser/FaBankImport/Config/Config.php` |
| Route requests | `src/Ksfraser/FaBankImport/command_bootstrap.php` |
| Database | `src/Ksfraser/FaBankImport/Repository/` |
| Tests | `tests/bootstrap.php` + `phpunit.xml` |

---

## 🔗 Related Documentation

- `ARCHITECTURAL_BLUEPRINT.md` - Detailed architectural analysis
- `PHASE_0_IMPLEMENTATION_GUIDE.md` - Shared kernel patterns
- `.github/AGENTS.md` - AI agent guidelines for this project

---

**Last Updated:** April 4, 2026  
**Version:** 1.0 (Architecture finalized)  
**Status:** Ready for reference & development
