# POST Action Handler Refactoring Plan

**Date**: October 21, 2025  
**Goal**: Extract POST action handling from process_statements.php into proper MVC architecture

---

## Current Problems

### 1. **Code Smell: Feature Envy**
```php
// process_statements.php lines 100-130
if (isset($_POST['UnsetTrans'])) {
    $bi_controller->unsetTrans();  // Controller method directly accesses $_POST internally
}
if (isset($_POST['AddCustomer'])) {
    $bi_controller->addCustomer();  // Same problem
}
```

**Issues**:
- Controller methods reach into global `$_POST` array
- Violates Command pattern - actions not encapsulated
- Hard to test (depends on global state)
- Mixing presentation logic with business logic

### 2. **God Object: bank_import_controller**
The controller tries to do everything:
- Transaction management
- Customer creation
- Vendor creation
- Toggle operations
- Charge calculations
- POST data extraction

**Violates**: Single Responsibility Principle

### 3. **Procedural Code in MVC Framework**
```php
// Lines 100-130 in process_statements.php
if (isset($_POST['UnsetTrans'])) { ... }
if (isset($_POST['AddCustomer'])) { ... }
if (isset($_POST['ToggleTransaction'])) { ... }
if (isset($_POST['ProcessTransaction'])) { ... }
```

Should be: **Command Pattern** with **Front Controller**

---

## Solution: Command Pattern + MVC

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│              process_statements.php (View/Router)            │
│  - Renders HTML form                                         │
│  - Delegates POST to CommandDispatcher                       │
└───────────────┬─────────────────────────────────────────────┘
                │
                ↓
┌─────────────────────────────────────────────────────────────┐
│           CommandDispatcher (Front Controller)               │
│  - Maps POST action → Command class                          │
│  - Executes command                                          │
│  - Returns TransactionResult                                 │
└───────────────┬─────────────────────────────────────────────┘
                │
                ↓
┌─────────────────────────────────────────────────────────────┐
│                    Command Classes                           │
│  - UnsetTransactionCommand                                   │
│  - AddCustomerCommand                                        │
│  - AddVendorCommand                                          │
│  - ToggleDebitCreditCommand                                  │
│  Each: execute() returns TransactionResult                   │
└───────────────┬─────────────────────────────────────────────┘
                │
                ↓
┌─────────────────────────────────────────────────────────────┐
│                Service Layer (Business Logic)                │
│  - TransactionService                                        │
│  - CustomerService                                           │
│  - VendorService                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## SOLID Principles Applied

### Single Responsibility (S)
- **Before**: bank_import_controller does everything
- **After**: 
  - `UnsetTransactionCommand` - only unsets transactions
  - `AddCustomerCommand` - only creates customers
  - `VendorService` - only manages vendors

### Open/Closed (O)
- **Before**: Adding new action requires editing multiple places
- **After**: Add new command class, register in dispatcher

### Liskov Substitution (L)
- All commands implement `CommandInterface`
- Can be swapped without breaking caller

### Interface Segregation (I)
- Separate interfaces for commands, services, repositories
- No fat interfaces

### Dependency Inversion (D)
- Commands depend on `ServiceInterface`, not concrete classes
- Services injected via constructor

---

## Implementation Plan (TDD)

### Phase 1: Define Interfaces (Test First)

#### 1.1 CommandInterface
```php
interface CommandInterface
{
    public function execute(): TransactionResult;
    public function getName(): string;
}
```

#### 1.2 CommandDispatcherInterface
```php
interface CommandDispatcherInterface
{
    public function register(string $actionName, string $commandClass): void;
    public function dispatch(string $actionName, array $postData): TransactionResult;
    public function hasCommand(string $actionName): bool;
}
```

### Phase 2: Write Tests

#### 2.1 CommandDispatcherTest
```php
class CommandDispatcherTest extends TestCase
{
    public function it_registers_commands(): void
    public function it_dispatches_to_correct_command(): void
    public function it_throws_exception_for_unknown_action(): void
    public function it_passes_post_data_to_command(): void
}
```

#### 2.2 UnsetTransactionCommandTest
```php
class UnsetTransactionCommandTest extends TestCase
{
    public function it_unsets_single_transaction(): void
    public function it_unsets_multiple_transactions(): void
    public function it_returns_success_result(): void
    public function it_returns_error_result_on_failure(): void
}
```

### Phase 3: Implement Commands

#### 3.1 UnsetTransactionCommand
**Responsibility**: Reset transaction status to unprocessed

```php
class UnsetTransactionCommand implements CommandInterface
{
    private array $postData;
    private TransactionRepositoryInterface $repository;
    
    public function __construct(
        array $postData,
        TransactionRepositoryInterface $repository
    ) {
        $this->postData = $postData;
        $this->repository = $repository;
    }
    
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['UnsetTrans'])) {
            return TransactionResult::error('No transactions to unset');
        }
        
        $count = 0;
        foreach ($this->postData['UnsetTrans'] as $transactionId => $value) {
            $this->repository->reset($transactionId);
            $count++;
        }
        
        return TransactionResult::success(
            0,
            0,
            "Disassociated {$count} transaction(s)",
            ['count' => $count]
        );
    }
    
    public function getName(): string
    {
        return 'UnsetTransaction';
    }
}
```

#### 3.2 AddCustomerCommand
**Responsibility**: Create customer from transaction data

```php
class AddCustomerCommand implements CommandInterface
{
    private array $postData;
    private CustomerServiceInterface $customerService;
    private TransactionRepositoryInterface $transactionRepo;
    
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['AddCustomer'])) {
            return TransactionResult::error('No customer data provided');
        }
        
        $results = [];
        foreach ($this->postData['AddCustomer'] as $transactionId => $value) {
            $transaction = $this->transactionRepo->findById($transactionId);
            
            try {
                $customerId = $this->customerService->createFromTransaction($transaction);
                $results[] = "Created Customer ID {$customerId}";
            } catch (CustomerCreationException $e) {
                return TransactionResult::error(
                    "Failed to create customer: " . $e->getMessage()
                );
            }
        }
        
        return TransactionResult::success(
            0,
            0,
            implode(', ', $results),
            ['customers_created' => count($results)]
        );
    }
}
```

#### 3.3 AddVendorCommand
**Responsibility**: Create vendor from transaction data

```php
class AddVendorCommand implements CommandInterface
{
    private array $postData;
    private VendorServiceInterface $vendorService;
    private TransactionRepositoryInterface $transactionRepo;
    
    public function execute(): TransactionResult
    {
        // Similar to AddCustomerCommand
        // Uses VendorService instead
    }
}
```

#### 3.4 ToggleDebitCreditCommand
**Responsibility**: Toggle transaction debit/credit indicator

```php
class ToggleDebitCreditCommand implements CommandInterface
{
    private array $postData;
    private TransactionServiceInterface $transactionService;
    
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['ToggleTransaction'])) {
            return TransactionResult::error('No transaction to toggle');
        }
        
        foreach ($this->postData['ToggleTransaction'] as $transactionId => $value) {
            $this->transactionService->toggleDebitCredit($transactionId);
        }
        
        return TransactionResult::success(
            0,
            0,
            'Toggled debit/credit indicator',
            ['transaction_id' => $transactionId]
        );
    }
}
```

### Phase 4: Implement CommandDispatcher

```php
class CommandDispatcher implements CommandDispatcherInterface
{
    private array $commands = [];
    private ContainerInterface $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->registerDefaultCommands();
    }
    
    private function registerDefaultCommands(): void
    {
        $this->register('UnsetTrans', UnsetTransactionCommand::class);
        $this->register('AddCustomer', AddCustomerCommand::class);
        $this->register('AddVendor', AddVendorCommand::class);
        $this->register('ToggleTransaction', ToggleDebitCreditCommand::class);
    }
    
    public function register(string $actionName, string $commandClass): void
    {
        if (!is_subclass_of($commandClass, CommandInterface::class)) {
            throw new InvalidArgumentException(
                "{$commandClass} must implement CommandInterface"
            );
        }
        
        $this->commands[$actionName] = $commandClass;
    }
    
    public function dispatch(string $actionName, array $postData): TransactionResult
    {
        if (!$this->hasCommand($actionName)) {
            return TransactionResult::error("Unknown action: {$actionName}");
        }
        
        $commandClass = $this->commands[$actionName];
        
        // Use DI container to instantiate command with dependencies
        $command = $this->container->make($commandClass, ['postData' => $postData]);
        
        return $command->execute();
    }
    
    public function hasCommand(string $actionName): bool
    {
        return isset($this->commands[$actionName]);
    }
}
```

### Phase 5: Update process_statements.php

#### Before (Lines 100-130):
```php
if (isset($_POST['UnsetTrans'])) {
    $bi_controller->unsetTrans();
}
if (isset($_POST['AddCustomer'])) {
    $bi_controller->addCustomer();
}
if (isset($_POST['AddVendor'])) {
    $bi_controller->addVendor();
}
if (isset($_POST['ToggleTransaction'])) {
    $bi_controller->toggleDebitCredit();
}
```

#### After:
```php
use Ksfraser\FaBankImport\Commands\CommandDispatcher;

// Initialize dispatcher (once, at top of file)
$commandDispatcher = new CommandDispatcher($container);

// Handle POST actions (replaces lines 100-130)
$result = handlePostAction($commandDispatcher, $_POST);
if ($result) {
    $result->display();
    $Ajax->activate('doc_tbl');
}

/**
 * Handle POST action using command pattern
 *
 * @param CommandDispatcher $dispatcher
 * @param array $postData
 * @return TransactionResult|null
 */
function handlePostAction(CommandDispatcher $dispatcher, array $postData): ?TransactionResult
{
    // Determine which action was submitted
    $actions = ['UnsetTrans', 'AddCustomer', 'AddVendor', 'ToggleTransaction'];
    
    foreach ($actions as $action) {
        if (isset($postData[$action])) {
            return $dispatcher->dispatch($action, $postData);
        }
    }
    
    return null; // No action submitted
}
```

---

## Service Layer Extraction

### TransactionService
```php
interface TransactionServiceInterface
{
    public function resetTransaction(int $transactionId): void;
    public function toggleDebitCredit(int $transactionId): void;
    public function findById(int $transactionId): array;
}

class TransactionService implements TransactionServiceInterface
{
    private TransactionRepositoryInterface $repository;
    
    public function resetTransaction(int $transactionId): void
    {
        $this->repository->updateStatus($transactionId, 0);
    }
    
    public function toggleDebitCredit(int $transactionId): void
    {
        $transaction = $this->repository->findById($transactionId);
        $newDc = ($transaction['dc'] === 'D') ? 'C' : 'D';
        $this->repository->updateDc($transactionId, $newDc);
    }
}
```

### CustomerService
```php
interface CustomerServiceInterface
{
    public function createFromTransaction(array $transaction): int;
}

class CustomerService implements CustomerServiceInterface
{
    private CustomerRepositoryInterface $repository;
    
    public function createFromTransaction(array $transaction): int
    {
        // Extract customer data from transaction
        $customerData = [
            'name' => $transaction['counterpartyName'],
            'account_number' => $transaction['counterpartyAccount'],
            // ... more fields
        ];
        
        return $this->repository->create($customerData);
    }
}
```

---

## Benefits

### 1. Testability ✅
```php
// Before: Hard to test (depends on global $_POST)
$bi_controller->addCustomer();  // ← How do we test this?

// After: Easy to test with mock data
$command = new AddCustomerCommand(
    ['AddCustomer' => [123 => 'Add']],
    $mockCustomerService,
    $mockTransactionRepo
);
$result = $command->execute();
$this->assertTrue($result->isSuccess());
```

### 2. Separation of Concerns ✅
- **Commands**: Coordinate between services
- **Services**: Business logic
- **Repositories**: Data access
- **Results**: Presentation

### 3. Extensibility ✅
```php
// Add new action without modifying existing code
$dispatcher->register('ExportToExcel', ExportCommand::class);
```

### 4. Dependency Injection ✅
```php
// Dependencies explicit and testable
public function __construct(
    array $postData,
    CustomerServiceInterface $customerService,
    TransactionRepositoryInterface $transactionRepo
)
```

### 5. Single Responsibility ✅
Each class has ONE reason to change:
- `UnsetTransactionCommand` - Changes when unset logic changes
- `CustomerService` - Changes when customer business rules change
- `TransactionRepository` - Changes when data access changes

---

## File Structure

```
src/Ksfraser/FaBankImport/
├── Commands/
│   ├── CommandInterface.php
│   ├── CommandDispatcher.php
│   ├── UnsetTransactionCommand.php
│   ├── AddCustomerCommand.php
│   ├── AddVendorCommand.php
│   └── ToggleDebitCreditCommand.php
├── Services/
│   ├── TransactionService.php
│   ├── CustomerService.php
│   └── VendorService.php
├── Contracts/  (interfaces)
│   ├── CommandInterface.php
│   ├── CommandDispatcherInterface.php
│   ├── TransactionServiceInterface.php
│   ├── CustomerServiceInterface.php
│   └── VendorServiceInterface.php
└── Results/
    └── TransactionResult.php  (already exists)

tests/unit/Commands/
├── CommandDispatcherTest.php
├── UnsetTransactionCommandTest.php
├── AddCustomerCommandTest.php
├── AddVendorCommandTest.php
└── ToggleDebitCreditCommandTest.php

tests/unit/Services/
├── TransactionServiceTest.php
├── CustomerServiceTest.php
└── VendorServiceTest.php
```

---

## UML Diagrams

### Class Diagram

```
┌─────────────────────────┐
│  CommandInterface       │
├─────────────────────────┤
│ + execute(): Result     │
│ + getName(): string     │
└─────────────────────────┘
           △
           │ implements
           │
    ┌──────┴──────────────────────┬────────────────┐
    │                              │                │
┌───────────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ UnsetTransCommand     │  │ AddCustomerCmd   │  │ AddVendorCmd     │
├───────────────────────┤  ├──────────────────┤  ├──────────────────┤
│ - postData: array     │  │ - postData       │  │ - postData       │
│ - repository          │  │ - customerSvc    │  │ - vendorSvc      │
├───────────────────────┤  ├──────────────────┤  ├──────────────────┤
│ + execute(): Result   │  │ + execute()      │  │ + execute()      │
└───────────────────────┘  └──────────────────┘  └──────────────────┘


┌─────────────────────────────────────┐
│  CommandDispatcher                  │
├─────────────────────────────────────┤
│ - commands: array<string, string>   │
│ - container: ContainerInterface     │
├─────────────────────────────────────┤
│ + register(action, class): void     │
│ + dispatch(action, post): Result    │
│ + hasCommand(action): bool          │
└─────────────────────────────────────┘
```

### Sequence Diagram: AddCustomer Flow

```
User         process_statements    CommandDispatcher    AddCustomerCommand    CustomerService    Repository
 │                  │                     │                      │                    │              │
 │ POST             │                     │                      │                    │              │
 │ AddCustomer      │                     │                      │                    │              │
 ├─────────────────>│                     │                      │                    │              │
 │                  │ dispatch()          │                      │                    │              │
 │                  ├────────────────────>│                      │                    │              │
 │                  │                     │ make(AddCustomerCmd) │                    │              │
 │                  │                     ├─────────────────────>│                    │              │
 │                  │                     │                      │ createFromTrans()  │              │
 │                  │                     │                      ├───────────────────>│              │
 │                  │                     │                      │                    │ create()     │
 │                  │                     │                      │                    ├─────────────>│
 │                  │                     │                      │                    │<─────────────┤
 │                  │                     │                      │<───────────────────┤              │
 │                  │                     │<─────────────────────┤                    │              │
 │                  │<────────────────────┤                      │                    │              │
 │                  │ result->display()   │                      │                    │              │
 │<─────────────────┤                     │                      │                    │              │
 │ Green banner     │                     │                      │                    │              │
```

---

## Migration Strategy

### Step 1: Create Interfaces (Week 1)
- Define all interfaces
- Write interface tests

### Step 2: Implement Commands (Week 1-2)
- One command at a time
- Test first, then implement
- Each command fully tested before moving to next

### Step 3: Create Services (Week 2)
- Extract business logic from controller
- Move to service classes
- Full test coverage

### Step 4: Implement Dispatcher (Week 2)
- Create dispatcher
- Register all commands
- Integration tests

### Step 5: Update process_statements.php (Week 3)
- Replace POST handling
- Keep old code commented out
- Run parallel testing

### Step 6: Remove Legacy Code (Week 3)
- Delete bank_import_controller POST methods
- Delete procedural POST handling
- Final cleanup

---

## Testing Strategy

### Unit Tests
- Each command in isolation
- Mock all dependencies
- Test success and error paths

### Integration Tests
- Dispatcher with real commands
- Commands with real services
- End-to-end POST handling

### Acceptance Tests
- Full workflow tests
- UI interaction simulation
- Real database transactions

---

## Success Criteria

✅ All POST actions handled by commands  
✅ 100% test coverage for commands  
✅ No direct `$_POST` access in commands  
✅ All dependencies injected  
✅ TransactionResult used consistently  
✅ Legacy controller can be deleted  
✅ No breaking changes to UI  

---

**Status**: Ready to implement  
**Next Step**: Create CommandInterface and write first test
