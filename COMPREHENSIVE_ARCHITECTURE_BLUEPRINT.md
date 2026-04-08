# KSF Bank Import - Comprehensive Architecture Blueprint

**Project:** ksf_bank_import  
**Latest Analysis:** April 4, 2026  
**Status:** Phase 0 (Shared Kernel Foundation) in progress with Phase 1-4 implementations  
**Architecture Pattern:** Hybrid - Modular Monolith with Clean Architecture + Command Pattern + DDD  

---

## 1. PROJECT STRUCTURE & DIRECTORIES

### 1.1 Root Directory Organization

```
ksf_bank_import/
├── app/                          # New Shared Kernel (Phase 0)
│   └── Shared/                   # Common domain models, entities, DTOs
│       ├── DTOs/                 # Data Transfer Objects
│       ├── Entities/             # Domain entities (immutable)
│       ├── Factories/            # Factory methods
│       ├── Repositories/         # Repository interfaces/implementations
│       ├── ValueObjects/         # Value Objects
│       └── index.php
│
├── src/Ksfraser/                 # Main application source code
│   ├── FaBankImport/             # Primary module (PSR-4 root)
│   │   ├── Actions/              # Application actions
│   │   ├── controllers/          # HTTP controllers
│   │   ├── Config/               # Configuration management
│   │   ├── Container.php         # Dependency Injection container
│   │   ├── Database/             # Database access layer
│   │   ├── Domain/               # Domain logic
│   │   │   ├── Exceptions/       # Domain exceptions
│   │   │   └── ValueObjects/     # Domain value objects
│   │   ├── DTO/                  # Legacy DTOs
│   │   ├── Entity/               # Legacy entities
│   │   ├── Events/               # Event system
│   │   ├── Exceptions/           # General exceptions
│   │   ├── Factories/            # Factory pattern implementations
│   │   ├── handlers/             # Transaction handlers (strategy pattern)
│   │   ├── Http/                 # HTTP request/response handling
│   │   ├── Interfaces/           # Contracts/interfaces
│   │   ├── Middleware/           # Middleware pipeline
│   │   ├── Models/               # ORM models
│   │   ├── Parsers/              # Bank statement parsers
│   │   ├── Repository/           # Data access layer
│   │   ├── Results/              # Result/response objects
│   │   ├── Schema/               # Schema management services
│   │   ├── Service/              # Business services
│   │   │   ├── BankAccountMapping/
│   │   │   └── Schema/
│   │   ├── Seed/                 # Database seeding
│   │   ├── Services/             # Business logic services
│   │   ├── Strategy/             # Strategy pattern implementations
│   │   ├── TransactionDC/        # Transaction debit/credit
│   │   ├── Traits/               # Reusable traits
│   │   ├── Types/                # Type definitions
│   │   ├── Application.php       # Application bootstrap
│   │   ├── Bootstrap.php         # Initialization logic
│   │   ├── command_bootstrap.php # Command Pattern setup
│   │   └── views/                # View templates
│   │
│   ├── Application/              # Alternative application namespace
│   ├── ModulesDAO/               # Database access object for modules
│   └── PartnerTypes/             # Partner type constants
│
├── tests/                         # Test suites
│   ├── Unit/                      # Unit tests
│   ├── Integration/               # Integration tests
│   ├── Feature/                   # Feature tests
│   └── bootstrap.php              # Test initialization
│
├── views/                         # Legacy view files
└── config/                        # Configuration files
```

### 1.2 PSR-4 Namespace Mapping

From `composer.json`:

| Namespace | Path | Purpose |
|-----------|------|---------|
| `Ksfraser\FaBankImport\` | `src/Ksfraser/FaBankImport/` | Main module |
| `Ksfraser\FaBankImport\Shared\` | `app/Shared/` | Shared kernel (Phase 0) |
| `Ksfraser\FaBankImport\Shared\DTOs\` | `app/Shared/DTOs/` | Shared DTOs |
| `Ksfraser\FaBankImport\Shared\Entities\` | `app/Shared/Entities/` | Shared domain entities |
| `Ksfraser\FaBankImport\Shared\Repositories\` | `app/Shared/Repositories/` | Repository interfaces |
| `Ksfraser\FaBankImport\Shared\ValueObjects\` | `app/Shared/ValueObjects/` | Value objects |
| `Ksfraser\FaBankImport\Services\` | `src/Ksfraser/FaBankImport/services/` | Business services |
| `Ksfraser\FaBankImport\Handlers\` | `src/Ksfraser/FaBankImport/handlers/` | Command/Strategy handlers |
| `Ksfraser\FaBankImport\Controllers\` | `src/Ksfraser/FaBankImport/controllers/` | HTTP controllers |
| `Ksfraser\FaBankImport\Config\` | `src/Ksfraser/FaBankImport/config/` | Configuration |
| `Controllers\` | `src/Controllers/` | Root controllers |

---

## 2. TECHNOLOGY STACK

### 2.1 Core Technologies

| Layer | Technologies |
|-------|--------------|
| **Language** | PHP 7.4+ (compatible with PHP 8.0+) |
| **Testing** | PHPUnit 9.6.34 |
| **Build/Autoload** | Composer 2.x with PSR-4 autoloading |
| **Project Type** | Library (modular monolith within FA) |
| **Host Integration** | FrontAccounting (legacy ERP system) |

### 2.2 Key Dependencies

```json
{
  "nesbot/carbon": "^2.x",           // Date/time handling
  "phpunit/phpunit": "^9.5",         // Unit testing
  "codeception/codeception": "^4.x", // Integration testing
  "php-http/client": "^2.x"          // HTTP client abstraction
}
```

### 2.3 Coding Standards & PSR Compliance

- **PSR-4**: Autoloading Standard (fully implemented)
- **PSR-1**: Basic Coding Standard
- **PSR-2**: Coding Style Guide (with PSR-12 extended)
- **PSR-5**: PHPDoc Standard (documented)
- **PSR-12**: Extended Coding Style

### 2.4 Development Standards

```
Naming Conventions:
- Classes:    PascalCase         (TransactionHandler, BankAccountMapping)
- Methods:    camelCase()        (processTransaction, getAccountMapping)
- Properties: camelCase          ($transactionAmount, $partnerId)
- Constants:  SCREAMING_CASE     (PARTNER_TYPE_SUPPLIER, DEFAULT_TIMEOUT)
- Files:      PascalCase.php     (BankImportController.php)
```

---

## 3. ARCHITECTURAL PATTERN

### 3.1 Architectural Classification

**Hybrid Pattern: Modular Monolith with Clean Architecture Elements**

```
┌─────────────────────────────────────────────────────────────┐
│                   PRESENTATION LAYER                         │
│  (Controllers, Views, HTTP Middleware, Command Routing)     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   INTERACTION LAYER                          │
│  (Handlers, Commands, Request/Response, Results)            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                          │
│  (Services, Business Logic, Transactions, Factory Methods)  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   DOMAIN LAYER (SHARED)                      │
│  (Entities, Value Objects, Domain Exceptions, DTOs)         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATA ACCESS LAYER                          │
│  (Repositories, Database Models, Data Mappers)              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
         ┌───────────────────────────┐
         │   FrontAccounting Database │
         └───────────────────────────┘
```

### 3.2 Design Patterns Implemented

| Pattern | Location | Purpose |
|---------|----------|---------|
| **Dependency Injection** | `Container.php` | Singleton container for service binding |
| **Service Locator** | Container methods | Lazy-load services on demand |
| **Command Pattern** | `handlers/`, `command_bootstrap.php` | Encapsulate requests as objects |
| **Strategy Pattern** | `handlers/` subclasses | Multiple transaction processing strategies |
| **Factory Method** | `factories/TransactionTypeFactory.php` | Create transaction types polymorphically |
| **Template Method** | `handlers/AbstractTransactionHandler.php` | Define algorithm skeleton in base class |
| **Repository** | `Repository/`, `Repositories/` | Abstract data access from domain |
| **Value Object** | `ValueObject/`, `Shared/ValueObjects/` | Immutable typed wrappers |
| **Middleware Pipeline** | `Middleware/`, `Application.php` | Cross-cutting concerns |
| **Singleton** | Config, Container, PerformanceMonitor | Single instance management |
| **Data Transfer Object** | `DTO/`, `Shared/DTOs/` | Encapsulate data across boundaries |
| **Event Dispatcher** | `Events/`, `Services/EventDispatcher.php` | Publish/subscribe pattern for events |

---

## 4. CORE ARCHITECTURAL COMPONENTS

### 4.1 Presentation Layer

#### 4.1.1 Controllers

**Location:** `src/Ksfraser/FaBankImport/controllers/`

```
Controllers/
├── AbstractController.php           # Base class with request/response/middleware
│   └── Responsibilities:
│       - Request handling (RequestHandler)
│       - Response building (ResponseHandler)
│       - Middleware pipeline initialization
│       - View rendering, JSON responses
│
├── BankImportController.php         # Main transaction processing
│   └── Responsibilities:
│       - Load transactions from database
│       - Delegate to service layer
│       - Render transaction views via TransactionViewService
│
├── AdminController.php              # Administrative functions
│   └── Responsibilities:
│       - Performance metrics display
│       - Historical trend analysis
│       - Anomaly detection reporting
│
└── Api/                             # RESTful API endpoints
    └── (Future API layer)
```

**Entry Point Flow:**

```php
process_statements.php
    ↓ includes
src/Ksfraser/FaBankImport/command_bootstrap.php
    ↓ creates
Container (DI) + CommandDispatcher
    ↓ for POST requests
CommandDispatcher::dispatch($action, $_POST)
    ↓ routes to
Transaction Handlers (Strategy objects)
```

#### 4.1.2 Views

**Location:** `src/Ksfraser/FaBankImport/views/` and legacy `views/`

- PHP view templates rendered by controller `render()` method
- HTML component library (`Ksfraser\HTML\Elements\*`) for abstraction
- View factory pattern for consistent rendering

### 4.2 Command & Handler Layer

#### 4.2.1 Command Pattern Implementation

**Location:** `src/Ksfraser/FaBankImport/command_bootstrap.php`

Encapsulates POST actions into command objects:

```
CommandDispatcher
├── dispatch($action, $postData)
│   └── maps actions to handlers
│
└── Registered Commands:
    ├── UnsetTransactionCommand                  (reset transaction status)
    ├── AddCustomerCommand                       (create FA customer)
    ├── AddVendorCommand                         (create FA vendor)
    ├── ToggleDebitCreditCommand                 (D/C indicator swap)
    ├── ProcessTransactionCommandHandler         (generic transaction process)
    └── (extensible for future commands)
```

**Decoupling:**
- Separates POST action routing from business logic
- Feature flag toggle: `USE_COMMAND_PATTERN` (true/false)
- Fallback to legacy procedural code possible

#### 4.2.2 Transaction Handler Strategy Pattern

**Location:** `src/Ksfraser/FaBankImport/handlers/`

```
TransactionHandlerInterface
    ↑
    │ implements
    │
AbstractTransactionHandler (base class with template method)
    ├── canProcess(Transaction): bool
    ├── getPartnerTypeConstant(): string
    └── process(...): array
        │
        ├─ SupplierTransactionHandler
        ├─ CustomerTransactionHandler
        ├─ BankTransferTransactionHandler
        ├─ QuickEntryTransactionHandler
        ├─ MatchedTransactionHandler
        ├─ ManualSettlementHandler
        ├─ DuplicateResolutionHandler
        └─ ErrorHandler
```

**Handler Registry:**

```
ImportHandlers (orchestrator)
    ├── registeredHandlers[]
    └── route(transaction) → handler.process()
```

**Partner Type Dispatch:**
```
AbstractTransactionHandler
├── PartnerType SUPPLIER  → SupplierTransactionHandler
├── PartnerType CUSTOMER  → CustomerTransactionHandler
├── PartnerType BANK_TRANSFER → BankTransferTransactionHandler
├── PartnerType QUICK_ENTRY → QuickEntryTransactionHandler
└── ... (others)
```

### 4.3 Application/Service Layer

#### 4.3.1 Services

**Location:** `src/Ksfraser/FaBankImport/services/` and `src/Ksfraser/FaBankImport/Service/`

```
Services (Business Logic):
├── TransactionViewService                   # Format transaction for display
├── TransactionService                       # Core transaction operations
├── ReferenceNumberService                   # Generate/validate transaction refs
├── SimpleCommandBus                         # Command routing (optional)
├── EventDispatcher                          # Event publishing
├── PerformanceMonitor                       # Performance metrics (Singleton)
├── MetricsAggregator                        # Historical trend analysis
├── TransactionLogger                        # Event-driven logging
├── BankAccountMapping/
│   └── BankAccountMappingService            # Bank account mapping queries
└── Schema/
    └── BankImportModuleSchemaService        # Database schema management
```

#### 4.3.2 Data Services

**BankAccountMappingService**

```php
getBankAccountMappingByOFXIdentifiers(
    ?string $bankid,
    ?string $acctid,
    ?string $intuit_bid
): BankAccountMapping|null

getMappingsForFABankAccount(int $faAccountId): array[]

countBankAccountMappings(): int
```

**Error Handling Pattern:**
- Delegates to repository
- Catches exceptions
- Returns null/empty array on failure (fail-safe)

### 4.4 Domain Layer (Shared Kernel - Phase 0)

#### 4.4.1 Shared Entities

**Location:** `app/Shared/Entities/`

```
Entities (Immutable Domain Models):
├── Transaction                              # Bank transaction aggregate root
├── BankStatement                            # Statement from bank (OFX/CSV/MT940)
├── BankAccountMapping                       # Maps OFX IDs to FA bank account
├── Counterparty                             # Customer/vendor/bank details
├── LineItem                                 # GL line item in transaction
├── TransactionTitle                         # Transaction description
├── TransferMatch                            # Paired transfer matching
├── PartnerKeyword                           # Keyword for partner matching
└── Related Domain Exceptions:
    ├── InvalidKeywordException
    └── PartnerDataNotFoundException
```

**Entity Characteristics:**
- **Immutable:** No setters, only getters
- **Factory methods:** `create()` for new, `fromDatabase()` for loaded
- **Rich domain logic:** `isMatched()`, `isDebit()`, `isCredit()` methods
- **Type safety:** Leverage PHP type hints where possible

#### 4.4.2 Shared Data Transfer Objects

**Location:** `app/Shared/DTOs/`

```
DTOs (Cross-Layer Communication):
├── AccountResolutionDTO                     # Account resolution data
├── DuplicateResolutionDTO                   # Duplicate resolution options
├── ImportSummaryDTO                         # Import operation results
│   ├── getResultCount(): int
│   ├── getSuccessfulResults(): array[]
│   ├── getFailedResults(): array[]
│   └── getSuccessRate(): float
├── MappingConfirmationDTO                   # Mapping confirmation data
├── ParseFilesDTO                            # File parsing parameters
└── UploadFormDTO                            # File upload form data
```

#### 4.4.3 Shared Value Objects

**Location:** `app/Shared/ValueObjects/`

Immutable wrappers for primitive types with domain semantics:
- Money, Amount, Percentage
- Date ranges, Timestamps
- Identifiers (AccountId, PartnerId)

### 4.5 Data Access Layer

#### 4.5.1 Repository Pattern

**Location:** `src/Ksfraser/FaBankImport/Repository/` and `Repositories/`

```
RepositoryInterfaces (Contracts):
├── ThirdPartyTransactionRepositoryInterface
├── PartnerDataRepositoryInterface
├── UploadedFileRepositoryInterface
├── ConfigRepositoryInterface
└── (abstract parent: AbstractThirdPartyTransactionRepository)

Implementations:
├── SquareTransactionRepository
├── DatabasePartnerDataRepository
├── DatabaseUploadedFileRepository
├── DatabaseConfigRepository
└── ...
```

**Key Repositories:**

```php
ThirdPartyTransactionRepositoryInterface
├── findById(int $id): Transaction
├── findByStatus(string $status): Transaction[]
├── save(Transaction $entity): void
├── delete(int $id): void
└── findByOFXIdentifiers(...): Transaction[]

PartnerDataRepositoryInterface
├── findById(int $id): Counterparty
├── findByCode(string $code): Counterparty
├── save(Counterparty $entity): void
└── findByKeyword(string $keyword): Counterparty[]
```

**Abstraction Strategy:**
- Repositories hide database impl from services
- Service calls repository, not direct DB queries
- Error handling occurs at service layer

### 4.6 Configuration Management

#### 4.6.1 Config Singleton

**Location:** `src/Ksfraser/FaBankImport/Config/Config.php`

```php
Config::getInstance()
├── Dot notation access:              $config->get('db.host')
├── Settings structure:
│   ├── db.*                          (database connection)
│   ├── logging.*                     (log path, enabled flag)
│   ├── transaction.*                 (type whitelist, defaults, limits)
│   └── (environment-based overrides via getenv())
├── get(string $key, $default): mixed
├── set(string $key, $value): void
└── reset(): void                     (for testing)
```

#### 4.6.2 Route Configuration

**Location:** `src/Ksfraser/FaBankImport/config/admin_routes.php`

```php
[
    'admin/dashboard' => [
        'controller' => 'AdminController',
        'action' => 'dashboard',
        'middleware' => ['auth', 'admin']
    ],
    'admin/performance' => [...],
    'admin/metrics/export' => [...],
    'admin/metrics/anomalies' => [...]
]
```

---

## 5. DEPENDENCY FLOW & ARCHITECTURAL BOUNDARIES

### 5.1 Dependency Direction

**Unidirectional dependency flow (Clean Architecture principle):**

```
Outer Layers → Inner Layers (ALLOWED)
Inner Layers → Outer Layers (FORBIDDEN)

Specifically:
┌─────────────────────────────────────────┐
│ Controllers    (outer)                  │  depends on ↓
└─────────────────────────────────────────┘
                ↓
┌─────────────────────────────────────────┐
│ Handlers       (command/strategy)        │  depends on ↓
└─────────────────────────────────────────┘
                ↓
┌─────────────────────────────────────────┐
│ Services       (application logic)       │  depends on ↓
└─────────────────────────────────────────┘
                ↓
┌─────────────────────────────────────────┐
│ Domain         (entities, DTOs, repos)   │  depends on ↓
└─────────────────────────────────────────┘
                ↓
┌─────────────────────────────────────────┐
│ Infrastructure (DB, Config, vendor libs) │
└─────────────────────────────────────────┘
```

### 5.2 Container Initialization

**Location:** `src/Ksfraser/FaBankImport/Container.php`

```php
$container = Container::getInstance()
    // Repositories
    ├── getTransactionRepository(): TransactionRepository
    ├── getConfigRepository(): DatabaseConfigRepository
    └── getUploadedFileRepository(): DatabaseUploadedFileRepository

    // Services
    ├── getTransactionService(): TransactionService
    ├── getReferenceNumberService(): ReferenceNumberService
    ├── getBankAccountMappingService(): BankAccountMappingService
    └── getEventDispatcher(): EventDispatcher

    // Factories
    ├── getTransactionTypeFactory(): TransactionTypeFactory
    └── getTransactionViewFactory(): HtmlTransactionView

    // Controllers
    ├── getBankImportController(): BankImportController
    ├── getAdminController(): AdminController
    └── getBiLineItemController(): BiLineItemController

    // Command Pattern
    ├── getCommandDispatcher(): CommandDispatcher
    └── getProcessTransactionCommandHandler(): ProcessTransactionCommandHandler

    // Monitoring
    ├── getPerformanceMonitor(): PerformanceMonitor
    └── getMetricsAggregator(): MetricsAggregator
```

**Service Lifetime Pattern:**

```
Services registered with Container:
├── Singleton (getXxxInstance)     → Shared across entire app
│   (Config, PerformanceMonitor, MetricsAggregator, EventDispatcher)
│
├── Transient (per-request)         → New instance each call
│   (Transaction handlers, Commands)
│
└── Scoped (session-level)          → (Future implementation)
```

### 5.3 Middleware Pipeline

**Location:** `src/Ksfraser/FaBankImport/Middleware/` and `Application.php`

```
Request enters Application
    ↓
Middleware Pipeline (ordered):
    1. PerformanceMonitoringMiddleware (measures all operations)
    2. AuthMiddleware                  (authenticate user)
    3. TransactionValidationMiddleware (validate transaction data)
    4. (Future: CorsMiddleware, RateLimitMiddleware, etc.)
    ↓
Controller.handle()
    ↓
Response exits Pipeline (in reverse order)
```

### 5.4 Event System

**Location:** `src/Ksfraser/FaBankImport/Events/`, `Services/EventDispatcher.php`

```
TransactionProcessedEvent
    ↓ fired by
Handlers after processing
    ↓ dispatched by
EventDispatcher
    ↓ subscribed by
TransactionLogger (event listener)
    └── logTransactionProcessed(TransactionProcessedEvent)
```

---

## 6. ENTRY POINTS & REQUEST FLOWS

### 6.1 Web Request Entry Point

**Primary Entry:** `process_statements.php`

```
┌─────────────────────────────────────────────────────────────┐
│ process_statements.php (FrontAccounting view)                │
│ Execution Flow:                                              │
└─────────────────────────────────────────────────────────────┘
        ↓
    1. Include FrontAccounting header & globals
    2. require_once('command_bootstrap.php')
        ├─ Container::getInstance()
        ├─ CommandDispatcher initialization
        └─ SimpleContainer binding
    3. Check $_SERVER['REQUEST_METHOD']
    4. If POST:
        └─ $commandDispatcher->dispatch($action, $_POST)
            ├─ Route to appropriate Command
            ├─ Execute command handler
            └─ Return TransactionResult
    5. If GET:
        └─ $controller->index()
            ├─ Load transactions
            ├─ Render via TransactionViewService
            └─ Display list
```

### 6.2 Command Processing Flow

**Example: AddCustomerCommand**

```
POST ['AddCustomer' => 1, 'trans_id' => 42]
    ↓
CommandDispatcher::dispatch('AddCustomer', $_POST)
    ↓
SimpleContainer::get('LegacyController')
    ↓
AddCustomerCommand::handle()
    ├── Extract transaction data from $_POST
    ├── Validate data (ReferenceNumberService)
    ├── Call $controller->addCustomer()
    │   ├─ DB operations (legacy)
    │   └─ Returns success/error
    ├── Wrap result in TransactionResult
    └─ Return result
    ↓
process_statements.php
    ├─ Display success/error message
    └─ AJAX refresh transaction list
```

### 6.3 Transaction Processing Flow

**Traditional Flow (not command-routed):**

```
BankImportController::processTransaction($transactionId, $type)
    ↓
TransactionTypeFactory::createTransactionType($type, $data)
    ├─ $type = 'SUPPLIER'
    │   └─ return new SupplierTransactionHandler()
    ├─ $type = 'CUSTOMER'
    │   └─ return new CustomerTransactionHandler()
    └─ $type = 'BANK_TRANSFER'
        └─ return new BankTransferTransactionHandler()
    ↓
$handler->process($transaction)
    ├─ Validate via ReferenceNumberService
    ├─ Check via BankAccountMappingService
    ├─ DB operations
    ├─ Return TransactionResult
    ↓
ResultsView::render($result)
```

### 6.4 Import Processing Flow

**Bank Statement Import:**

```
ParseFilesHandler::handle(ParseFilesDTO)
    ├─ Determine parser (QFX, CSV, MT940)
    ├─ ParseUploadedFilesHandler
    ├─ Extract transactions
    ├─ Return ImportSummaryDTO
    ↓
ProcessStatementHandler::handle(...)
    ├─ For each transaction:
    │   ├─ BankAccountMappingService::getMapping()
    │   ├─ ImportHandlers::route($transaction)
    │   │   └─ Dispatch to specific handler
    │   ├─ Handler::process($transaction)
    │   └─ Collect results
    ├─ Return ImportSummaryDTO with results
    ↓
View renders ImportSummaryDTO
    ├─ Success count
    ├─ Failed count
    ├─ Success rate
    └─ Detailed failure reasons
```

---

## 7. DESIGN PATTERNS IN DEPTH

### 7.1 Dependency Injection Pattern

**Container as Service Locator:**

```php
// Provider (Container)
class Container {
    public function getBankImportController(): BankImportController {
        return $this->services[BankImportController::class]
            ?? $this->services[BankImportController::class] = new BankImportController();
    }
}

// Consumer (Controller)
class BankImportController {
    public function __construct() {
        $this->service = Container::getInstance()->getTransactionService();
    }
}
```

**Anti-pattern:** Direct `new` instantiation in services would couple to concrete implementations.

### 7.2 Command Pattern in Detail

**Benefits in this codebase:**
1. **Decoupling:** POST handlers not hardcoded in process_statements.php
2. **Testing:** Commands can be tested independently
3. **Extensibility:** New commands register with dispatcher
4. **Undoability:** Command objects can be logged/replayed

**Structure:**

```php
interface CommandInterface {
    public function handle(array $payload): TransactionResult;
}

class UnsetTransactionCommand implements CommandInterface {
    private $repository;
    
    public function __construct(TransactionRepository $repo) {
        $this->repository = $repo;
    }
    
    public function handle(array $payload): TransactionResult {
        $transactionId = $payload['trans_id'];
        // Business logic here
    }
}

class CommandDispatcher {
    private $commands = [];
    
    public function register(string $name, string $commandClass): void {
        $this->commands[$name] = $commandClass;
    }
    
    public function dispatch(string $action, array $payload): TransactionResult {
        $commandClass = $this->commands[$action];
        $command = new $commandClass($this->container);
        return $command->handle($payload);
    }
}
```

### 7.3 Strategy Pattern for Transaction Types

**Problem:** Different transaction types (supplier, customer, bank transfer) need different processing.

**Solution:**

```php
abstract class AbstractTransactionHandler implements TransactionHandlerInterface {
    abstract protected function getPartnerTypeConstant(): string;
    abstract public function process(Transaction $transaction, array $context): array;
}

class SupplierTransactionHandler extends AbstractTransactionHandler {
    protected function getPartnerTypeConstant(): string {
        return 'SUPPLIER';
    }
    
    public function process(Transaction $transaction, array $context): array {
        // Supplier-specific logic
    }
}

class CustomerTransactionHandler extends AbstractTransactionHandler {
    protected function getPartnerTypeConstant(): string {
        return 'CUSTOMER';
    }
    
    public function process(Transaction $transaction, array $context): array {
        // Customer-specific logic
    }
}

// Usage
$handler = TransactionTypeFactory::create($type); // Returns correct strategy
$result = $handler->process($transaction, $context);
```

### 7.4 Repository Pattern for Data Access

**Interface Segregation (Dependency Inversion):**

```php
// Service depends on interface, not concrete DB impl
interface ThirdPartyTransactionRepositoryInterface {
    public function findById(int $id): ?Transaction;
    public function save(Transaction $entity): void;
    public function delete(int $id): void;
}

// Service
class TransactionService {
    private $repository;
    
    public function __construct(ThirdPartyTransactionRepositoryInterface $repo) {
        $this->repository = $repo;
    }
    
    public function processTransaction(int $id): TransactionResult {
        $transaction = $this->repository->findById($id);
        // Process transaction
        $this->repository->save($transaction);
    }
}

// Multiple implementations possible
class DatabaseTransactionRepository implements ThirdPartyTransactionRepositoryInterface {
    // DB queries
}

class SquareTransactionRepository implements ThirdPartyTransactionRepositoryInterface {
    // API calls
}
```

### 7.5 Factory Method Pattern

**TransactionTypeFactory:**

```php
class TransactionTypeFactory {
    public function createTransactionType(
        string $type,
        array $transactionData
    ): TransactionHandlerInterface {
        return match($type) {
            'SUPPLIER' => new SupplierTransactionHandler(),
            'CUSTOMER' => new CustomerTransactionHandler(),
            'BANK_TRANSFER' => new BankTransferTransactionHandler(),
            'QUICK_ENTRY' => new QuickEntryTransactionHandler(),
            default => throw new UnsupportedTransactionTypeException($type)
        };
    }
}
```

### 7.6 Template Method Pattern

**AbstractTransactionHandler defines skeleton, subclasses fill in details:**

```php
abstract class AbstractTransactionHandler implements TransactionHandlerInterface {
    // Template method
    public function process(Transaction $transaction, array $context): array {
        // 1. Validate
        if (!$this->canProcess($transaction)) {
            return ['success' => false, 'error' => 'Cannot process'];
        }
        
        // 2. Pre-process (hook for subclasses)
        $this->preProcess($transaction, $context);
        
        // 3. Core processing (implemented by subclass)
        $result = $this->doProcess($transaction, $context);
        
        // 4. Post-process (hook for subclasses)
        $this->postProcess($result, $context);
        
        return $result;
    }
    
    abstract protected function doProcess(Transaction $t, array $ctx): array;
    
    protected function preProcess(Transaction $t, array $ctx): void {}
    protected function postProcess(array &$result, array $ctx): void {}
}
```

### 7.7 Event Dispatcher Pattern

**Publish-Subscribe for loose coupling:**

```php
class EventDispatcher {
    private $listeners = [];
    
    public function addListener(string $eventClass, callable $listener): void {
        $this->listeners[$eventClass][] = $listener;
    }
    
    public function dispatch(object $event): void {
        $eventClass = get_class($event);
        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            $listener($event);
        }
    }
}

// Bootstrap
$eventDispatcher->addListener(
    TransactionProcessedEvent::class,
    [$logger, 'logTransactionProcessed']
);

// Usage in handler
$event = new TransactionProcessedEvent($transaction);
$this->eventDispatcher->dispatch($event);
```

---

## 8. CROSS-CUTTING CONCERNS

### 8.1 Error Handling & Exceptions

**Exception Hierarchy:**

```
Exception (PHP)
    │
    ├─ Domain Exceptions
    │  ├─ InvalidKeywordException           (Domain\Exceptions)
    │  └─ PartnerDataNotFoundException      (Domain\Exceptions)
    │
    ├─ General Exceptions
    │  ├─ TransactionValidationException    (exceptions)
    │  ├─ HandlerDiscoveryException         (exceptions)
    │  └─ UnauthorizedException            (exceptions)
    │
    └─ Propagates to
       └─ ErrorHandler (middleware)
           ├─ handleException($e)
           ├─ Log error
           └─ Return error response
```

**Usage:**

```php
try {
    $transaction = $repository->findById($id);
    if (!$transaction) {
        throw new PartnerDataNotFoundException("Transaction $id not found");
    }
} catch (PartnerDataNotFoundException $e) {
    $this->logger->error($e->getMessage());
    return new TransactionResult(false, $e->getMessage());
}
```

### 8.2 Logging & Monitoring

**Performance Monitoring:**

```
PerformanceMonitor (Singleton)
├── addMetric(string $operation, float $duration)
├── getAverageMetrics(string $op, int $window): array
├── getAllMetrics(): array
└── Used by PerformanceMonitoringMiddleware

MetricsAggregator
├── Persists metrics to disk
├── getHistoricalTrends(string $op, int $days): array
└── Used by AdminController for dashboard

TransactionLogger (Event listener)
├── Subscribes to TransactionProcessedEvent
└── logTransactionProcessed(event): void
```

### 8.3 Validation

**Layer 1: Input Validation**
- ReferenceNumberService validates transaction refs
- Controller validates POST data
- Middleware validates transaction data

**Layer 2: Business Rule Validation**
- Handler checks account mappings
- Handler validates partner data
- Service checks constraints

**Layer 3: Database Constraints**
- Foreign keys
- Unique constraints
- Data type constraints

### 8.4 Configuration Management

**Environment-based:**

```php
$config = Config::getInstance();

// Read from environment (12-factor app pattern)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$useCommandPattern = getenv('USE_COMMAND_PATTERN') ?: true;

// Dot notation access
$settings = [
    'db.host' => $dbHost,
    'transaction.default_dc' => 'D',
    'logging.path' => __DIR__ . '/logs'
];

// Retrieve
$logPath = $config->get('logging.path');
$default = $config->get('transaction.default_dc'); // 'D'
$missing = $config->get('missing.key', 'default'); // 'default'
```

---

## 9. DATA ARCHITECTURE

### 9.1 Entity Relationships

```
                    ┌─────────────────────┐
                    │  BankStatement      │
                    │  (1 per import)     │
                    └──────────┬──────────┘
                               │ 1:M
                               ▼
                    ┌─────────────────────┐
                    │   Transaction       │
                    │  (bank transaction) │
                    └──────┬──────┬───────┘
                           │      │
                       1:1 │      │ M:1
                           ▼      ▼
              ┌──────────────────────────────┐
              │  BankAccountMapping          │
              │ (OFX IDs → FA account)       │
              └──────────────────────────────┘
                           △
                           │ 1:M
                           │
              ┌──────────────────────────────┐
              │   Counterparty               │
              │ (customer/vendor/bank)       │
              └──────────────────────────────┘


Transaction entity may also reference:
├─ TransactionTitle (description text)
├─ LineItem (GL entries)
├─ TransferMatch (paired transfer)
├─ PartnerKeyword (matching logic)
└─ TransactionDC (debit/credit indicator)
```

### 9.2 Data Storage

**Primary Tables (FrontAccounting database):**

```
bank_import tables:
├─ bi_transactions          (main transaction log)
├─ bi_statements            (import batches)
├─ bi_bank_accounts         (OFX ID mappings)
├─ bi_transfer_matches      (paired transfer tracking)
├─ bi_partners_data         (counterparty cache)
├─ bi_lineitem              (GL entry cache)
├─ bi_contact               (contact info cache)
└─ ... (others)

Related FA tables:
├─ gl_trans                 (GL transactions)
├─ suppliers                (vendor master)
├─ debtors                  (customer master)
├─ debtor_trans_detail      (AR detail)
├─ supp_trans_details       (AP detail)
└─ bank_accounts            (bank accounts)
```

### 9.3 Transaction States

```
┌─────────────────────────────┐
│  PENDING (initial import)   │
└────────────┬────────────────┘
             │
             ├─→ SCHEDULED (waiting approval)
             ├─→ PROCESSING (actively matched)
             ├─→ COMPLETED (successfully matched)
             ├─→ UNMATCHED (no matching transaction found)
             ├─→ DUPLICATE (multiple possible matches)
             ├─→ MANUAL_REVIEW (requires user intervention)
             └─→ ERROR (processing error occurred)
```

---

## 10. EXTENSIBILITY & VARIATION POINTS

### 10.1 Adding New Transaction Handler

**Steps:**

1. Create new handler class:
```php
namespace Ksfraser\FaBankImport\Handlers;

class PayPalTransactionHandler extends AbstractTransactionHandler {
    protected function getPartnerTypeConstant(): string {
        return 'PAYPAL'; // Or similar
    }
    
    public function process(Transaction $transaction, array $context): array {
        // PayPal-specific logic
    }
}
```

2. Register in handler registry:
```php
$handlers = new ImportHandlers();
$handlers->register(new PayPalTransactionHandler());
```

3. Factory method dispatches automatically:
```php
$handler = $factory->create('PAYPAL', $data);
```

### 10.2 Adding New Parser

**Supported parsers:**
- QFX (Quicken Financial Exchange)
- CSV (generic comma-separated)
- MT940 (SWIFT standard)

**To add new parser:**

1. Create parser class:
```php
class YodleeStatementParser extends AbstractParser {
    public function parse(string $content): array {
        // Parse and return Transaction[]
    }
}
```

2. Register in parser factory:
```php
class ParserFactory {
    public static function create(string $fileContent): ParserInterface {
        if (stripos($fileContent, 'yodlee') !== false) {
            return new YodleeStatementParser();
        }
    }
}
```

### 10.3 Adding New Service

**Pattern for new service:**

```php
class NewService {
    private $repository;
    private $eventDispatcher;
    
    public function __construct(
        RepositoryInterface $repository,
        EventDispatcher $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function operation(): Result {
        // Business logic
        $this->eventDispatcher->dispatch(new OperationCompletedEvent($result));
        return $result;
    }
}

// Register in Container
public function getNewService(): NewService {
    return new NewService(
        $this->getRepository(),
        $this->getEventDispatcher()
    );
}
```

### 10.4 Adding New Middleware

```php
class CustomMiddleware implements MiddlewareInterface {
    public function process(Request $request, callable $next) {
        // Pre-process
        $response = $next($request);
        // Post-process
        return $response;
    }
}

// Register in Application
$app->setupMiddleware();
$this->pipeline->pipe(new CustomMiddleware());
```

---

## 11. TESTING ARCHITECTURE

### 11.1 Test Suite Organization

**Location:** `tests/`

```
tests/
├── Unit/                          # Unit tests (fast, isolated)
│   ├── Shared/                    # Phase 0 kernel tests
│   ├── ValueObject/               # Value object tests
│   ├── Entity/                    # Entity tests
│   ├── Service/                   # Service tests (mocked repos)
│   └── Strategy/                  # Handler strategy tests
│
├── Integration/                   # Integration tests (with DB)
│   ├── Repository/                # Repository tests (real DB)
│   ├── Service/                   # Service tests (real repos)
│   └── Handler/                   # Handler tests (full flow)
│
├── Feature/                       # End-to-end feature tests
│   ├── ImportProcessing/
│   ├── TransactionMatching/
│   └── DuplicateDetection/
│
├── bootstrap.php                  # Test setup & mocks
└── FAMock/                        # FrontAccounting mocks
```

### 11.2 Test Framework & Configuration

**PHPUnit 9.6.34:**

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Shared Kernel">      <!-- Phase 0 -->
        <directory>tests/ValueObject</directory>
        <directory>tests/Entity</directory>
    </testsuite>
    <testsuite name="Services">
        <directory>tests/Service</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

### 11.3 Mocking & Test Patterns

```php
// Mock repository for unit tests
class MockTransactionRepository implements ThirdPartyTransactionRepositoryInterface {
    private $storage = [];
    
    public function findById(int $id): ?Transaction {
        return $this->storage[$id] ?? null;
    }
}

// Test
public function testProcessTransaction() {
    $mockRepo = new MockTransactionRepository();
    $service = new TransactionService($mockRepo);
    
    $result = $service->processTransaction(1);
    $this->assertTrue($result->isSuccess());
}
```

---

## 12. TECHNOLOGY-SPECIFIC PATTERNS (PHP)

### 12.1 PHP Type Hints (PHP 7.4+ style)

```php
// Property types (PHP 7.4+)
private int $id;
private string $name;
private array $data;
private ?Transaction $transaction; // nullable

// Method signatures
public function process(Transaction $transaction): TransactionResult {
    // Enforced type safety
}

// Strict types disclaimer (recommended)
declare(strict_types=1);
```

### 12.2 Null Coalescing & Spaceship Operators

```php
// Null coalescing
$value = $_GET['id'] ?? 'default';

// Null safe operator (PHP 8.0+)
$name = $transaction?->getCounterparty()?->getName();

// Spaceship (comparison)
$result = $a <=> $b; // -1, 0, or 1
```

### 12.3 Anonymous Classes for Simple Use Cases

```php
// Simple value object
$result = new class {
    public bool $success = true;
    public string $message = 'Operation completed';
};
```

### 12.4 Namespace & Use Statements

```php
namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Repository\ThirdPartyTransactionRepositoryInterface;
use Ksfraser\FaBankImport\Shared\Entities\Transaction;
use Ksfraser\FaBankImport\Results\TransactionResult;

class TransactionService {
    // References to imported classes are unqualified
    public function process(Transaction $transaction): TransactionResult {
    }
}
```

---

## 13. CONFIGURATION & ENVIRONMENT SETUP

### 13.1 Composer Configuration

**Package Type:** `library` (provides PSR-4 autoloading)

**Key Configuration:**

```json
{
  "name": "ksfraser/fa-bank-import",
  "type": "library",
  "autoload": {
    "psr-4": {
      "Ksfraser\\FaBankImport\\": "src/Ksfraser/FaBankImport/",
      "Ksfraser\\FaBankImport\\Shared\\": "app/Shared/"
    }
  }
}
```

**Build Commands:**

```bash
composer dump-autoload              # Rebuild autoloader
composer validate --strict          # Validate configuration
```

### 13.2 Environment Variables

**Required:**

```
DB_HOST=localhost
DB_NAME=fa_bank_import
DB_USER=root
DB_PASS=password
```

**Optional:**

```
BANK_IMPORT_DEFAULT_TRANSACTION_DC=D    # Default Debit/Credit
USE_COMMAND_PATTERN=true                # Feature flag
APP_ENV=development                     # dev/test/production
```

### 13.3 Bootstrap Files

**Primary Bootstrap:** `command_bootstrap.php`

- Creates DI container
- Binds repositories
- Initializes CommandDispatcher
- Provides feature flag toggle

**Test Bootstrap:** `tests/bootstrap.php`

- Autoload vendor
- Set test environment
- Initialize session
- Register shutdown function

---

## 14. BUILD & DEPLOYMENT

### 14.1 Build Process

**For FrontAccounting Integration:**

```bash
# 1. Install dependencies
composer install

# 2. Generate autoloader
composer dump-autoload

# 3. Run tests (pre-deployment verification)
php vendor/bin/phpunit phpunit.xml --log-junit=test-results.xml

# 4. Copy to FA installation
# Typically: /path/to/FA/modules/ksf_bank_import/
```

### 14.2 Directory Structure in Production

```
FrontAccounting/
├── modules/
│   └── ksf_bank_import/
│       ├── src/                   (PSR-4 root)
│       ├── app/                   (Shared kernel)
│       ├── vendor/                (Composer dependencies)
│       ├── composer.json
│       ├── composer.lock
│       └── process_statements.php (Entry point)
```

---

## 15. SUBSYSTEM BOUNDARIES

### 15.1 Clear Architectural Seams

| Subsystem | Responsibility | Boundaries | Input | Output |
|-----------|-----------------|-----------|-------|--------|
| **Parser** | Extract transactions from bank files | File → Transaction[] | File content, format | Transaction objects |
| **Handler** | Process individual transactions | Transaction → Result | Transaction, context | TransactionResult |
| **Service** | Coordinate business operations | Command → Event | Service requests | Events, results |
| **Repository** | Abstract data persistence | Service → DB | Queries | Entity objects |
| **Controller** | Orchestrate request flow | Request → Response | HTTP request | HTTP response |
| **Container** | Manage dependencies | App start | Config | Service instances |
| **Middleware** | Cross-cutting concerns | Request ↔ Response | Request | Response (modified) |

### 15.2 Violation Detection

**Acceptable dependencies:**
```
✓ Service → Repository (abstracted via interface)
✓ Handler → Service (delegates business logic)
✓ Controller → Handler (orchestrates handlers)
✓ Middleware → Service (reads common data)
```

**Anti-patterns (forbidden):**
```
✗ Repository → Service (circular dependency)
✗ Entity → Service (entities should be passive)
✗ Handler → Controller (handlers shouldn't call controllers)
✗ Service → Controller (services shouldn't render)
```

---

## 16. PERFORMANCE CHARACTERISTICS

### 16.1 Monitoring & Metrics

**PerformanceMonitor tracks:**
- `process_transaction` duration
- `list_transactions` render time
- `import_batch` total time
- `bank_mapping_query` lookup time

**Aggregation (dashboard):**
- Average operation time (60-minute window)
- Historical trends (7-day lookback)
- Anomaly detection (variance > 2σ)

### 16.2 Optimization Patterns

**Implemented:**
1. **Lazy loading** - Services instantiated on-demand in Container
2. **Caching** - QuickEntryDataProvider singleton pattern
3. **Query optimization** - Repositories batch queries
4. **Metrics aggregation** - Off-load heavy calculations

**Future opportunities:**
- SQL query caching layer
- Redis session storage
- Query result memoization
- Parallel import processing

---

## 17. KNOWN ARCHITECTURAL DECISIONS

### 17.1 Why Modular Monolith?

- **Integration:** Must coexist with FrontAccounting (single monolithic app)
- **Boundaries:** Logical separation via namespaces and PSR-4
- **Scalability:** Can extract modules independently as system grows

### 17.2 Command Pattern Adoption

- **Before:** 130 lines of procedural if-statements in process_statements.php
- **After:** Extensible command registry, single dispatcher
- **Trade-off:** Slight runtime overhead for testability and extensibility

### 17.3 Shared Kernel (Phase 0)

- **Location:** `app/Shared/` (separate from FA integration code)
- **Purpose:** Build reusable domain models independent of FA
- **Benefit:** Can one day migrate entities outside FA context

### 17.4 Service Locator vs. Dependency Injection

- **Chosen:** Service Locator (Container singleton)
- **Rationale:** Legacy FA codebase uses global functions; matches existing patterns
- **Alternative:** Could refactor to pure constructor injection in future

---

## 18. ARCHITECTURAL MATURITY & ROADMAP

### 18.1 Current Phase (Phase 0)

✓ **Completed:**
- Shared kernel entity definitions
- DTO standardization
- Repository abstraction for data access
- Command Pattern for POST routing
- Middleware pipeline foundation
- Handler strategy pattern
- Event system scaffolding
- DI container with lazy-loading

### 18.2 Future Phases (Proposed)

**Phase 1 & beyond:**
- Extract pure business logic into library
- Event sourcing for transaction audit trail
- Async job queue for batch imports
- API layer (REST endpoints)
- Caching strategy
- Horizontal scaling prep

---

## 19. FILE REFERENCE GUIDE

### Key Files by Purpose

| Purpose | Files |
|---------|-------|
| **Entry Point** | [process_statements.php](process_statements.php), [command_bootstrap.php](src/Ksfraser/FaBankImport/command_bootstrap.php) |
| **DI Container** | [Container.php](src/Ksfraser/FaBankImport/Container.php), [Configuration](src/Ksfraser/FaBankImport/Config/Config.php) |
| **Handlers** | [handlers/*Handler.php](src/Ksfraser/FaBankImport/handlers/) |
| **Services** | [Service/BankAccountMapping/*](src/Ksfraser/FaBankImport/Service/BankAccountMapping/), [services/*Service.php](src/Ksfraser/FaBankImport/services/) |
| **Repositories** | [Repository/*.php](src/Ksfraser/FaBankImport/Repository/) |
| **Entities** | [app/Shared/Entities/*.php](app/Shared/Entities/), [Entity/*.php](src/Ksfraser/FaBankImport/Entity/) |
| **DTOs** | [app/Shared/DTOs/*.php](app/Shared/DTOs/), [DTO/*.php](src/Ksfraser/FaBankImport/DTO/) |
| **Controllers** | [controllers/*.php](src/Ksfraser/FaBankImport/controllers/) |
| **Middleware** | [Middleware/*.php](src/Ksfraser/FaBankImport/Middleware/), [Application.php](src/Ksfraser/FaBankImport/Application.php) |
| **Tests** | [tests/bootstrap.php](tests/bootstrap.php), [phpunit.xml](phpunit.xml) |

---

## 20. SUMMARY

### Architecture Snapshot

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  Modular Monolith (In FrontAccounting)  ┃
┃                                         ┃
┃  Clean Architecture Principles:         ┃
┃  ✓ Layered with clear dependencies    ┃
┃  ✓ Dependency inversion (interfaces)   ┃
┃  ✓ Single Responsibility Pattern       ┃
┃  ✓ Open/Closed for extension          ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

┌──────────────────────────────────────────┐
│  Presentation (Controllers, Views)        │ ← HTTP requests
├──────────────────────────────────────────┤
│  Command/Handler (Strategy dispatch)      │
├──────────────────────────────────────────┤
│  Application (Services, Transactions)     │
├──────────────────────────────────────────┤
│  Domain (Entities, DTOs, Repositories)    │
├──────────────────────────────────────────┤
│  Infrastructure (DB, Config, Vendor)      │
└──────────────────────────────────────────┘
         ↓ (FrontAccounting API)
    FrontAccounting Database

Key Strengths:
✓ Extensible handler system via Strategy pattern
✓ Command pattern decouples routing from business logic
✓ Repository abstraction enables testing and future scaling
✓ Shared kernel (Phase 0) enables gradual refactoring
✓ Middleware pipeline for cross-cutting concerns
✓ Event system for loose coupling

Current Scope:
• ~8 transaction handler strategies
• 4+ command types for POST handling
• 5+ service classes for business logic
• Comprehensive test coverage (Unit + Integration)
• Production-ready integration with FrontAccounting
```

---

## Appendix: Quick Reference

### Common Operations

**Load Transaction:**
```php
$container = Container::getInstance();
$service = $container->getTransactionService();
$transaction = $service->findTransaction($id);
```

**Process Transaction:**
```php
$factory = new TransactionTypeFactory();
$handler = $factory->createTransactionType('SUPPLIER', $data);
$result = $handler->process($transaction, $context);
```

**Access Configuration:**
```php
$config = Config::getInstance();
$dbHost = $config->get('db.host');
$defaultDC = $config->get('transaction.default_dc', 'D');
```

**Dispatch Command:**
```php
$dispatcher = $commandDispatcher;
$result = $dispatcher->dispatch('AddCustomer', ['trans_id' => 42]);
```

---

**Last Updated:** April 4, 2026  
**Architect:** AI-Generated Architecture Blueprint  
**Status:** Reference Documentation
