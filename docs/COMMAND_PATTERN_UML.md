# Command Pattern Architecture - UML Diagrams

## Class Diagram

```mermaid
classDiagram
    class CommandInterface {
        <<interface>>
        +execute() TransactionResult
        +getName() string
    }

    class CommandDispatcherInterface {
        <<interface>>
        +register(actionName, commandClass) void
        +dispatch(actionName, postData) TransactionResult
        +hasCommand(actionName) bool
        +getRegisteredActions() array
    }

    class CommandDispatcher {
        -commands: array
        -container: object
        +__construct(container)
        +register(actionName, commandClass) void
        +dispatch(actionName, postData) TransactionResult
        +hasCommand(actionName) bool
        +getRegisteredActions() array
        -registerDefaultCommands() void
    }

    class UnsetTransactionCommand {
        -postData: array
        -repository: object
        +__construct(postData, repository)
        +execute() TransactionResult
        +getName() string
    }

    class AddCustomerCommand {
        -postData: array
        -customerService: object
        -transactionRepository: object
        +__construct(postData, customerService, transactionRepository)
        +execute() TransactionResult
        +getName() string
    }

    class AddVendorCommand {
        -postData: array
        -vendorService: object
        -transactionRepository: object
        +__construct(postData, vendorService, transactionRepository)
        +execute() TransactionResult
        +getName() string
    }

    class ToggleDebitCreditCommand {
        -postData: array
        -transactionService: object
        +__construct(postData, transactionService)
        +execute() TransactionResult
        +getName() string
    }

    class TransactionResult {
        <<value object>>
        -transNo: int
        -transType: int
        -message: string
        -data: array
        -status: string
        +success(transNo, transType, message, data) TransactionResult
        +error(message, data) TransactionResult
        +warning(message, transNo, transType, data) TransactionResult
        +isSuccess() bool
        +isError() bool
        +isWarning() bool
        +display() void
        +toHtml() string
    }

    CommandInterface <|.. UnsetTransactionCommand
    CommandInterface <|.. AddCustomerCommand
    CommandInterface <|.. AddVendorCommand
    CommandInterface <|.. ToggleDebitCreditCommand
    CommandDispatcherInterface <|.. CommandDispatcher
    CommandDispatcher --> CommandInterface : creates
    UnsetTransactionCommand --> TransactionResult : returns
    AddCustomerCommand --> TransactionResult : returns
    AddVendorCommand --> TransactionResult : returns
    ToggleDebitCreditCommand --> TransactionResult : returns
    CommandDispatcher ..> TransactionResult : returns
```

## Sequence Diagram: UnsetTransaction Flow

```mermaid
sequenceDiagram
    actor User
    participant UI as process_statements.php
    participant Dispatcher as CommandDispatcher
    participant Command as UnsetTransactionCommand
    participant Repo as TransactionRepository
    participant Result as TransactionResult

    User->>UI: POST UnsetTrans=[123,456]
    UI->>Dispatcher: dispatch('UnsetTrans', $_POST)
    Dispatcher->>Dispatcher: hasCommand('UnsetTrans')?
    Dispatcher->>Command: make(UnsetTransactionCommand)
    Dispatcher->>Command: execute()
    
    loop For each transaction
        Command->>Repo: reset(transactionId)
        Repo-->>Command: void
    end
    
    Command->>Result: success(0, 0, "Disassociated 2 transactions", data)
    Result-->>Command: TransactionResult
    Command-->>Dispatcher: TransactionResult
    Dispatcher-->>UI: TransactionResult
    UI->>Result: display()
    Result->>User: Green notification banner
```

## Sequence Diagram: AddCustomer Flow

```mermaid
sequenceDiagram
    actor User
    participant UI as process_statements.php
    participant Dispatcher as CommandDispatcher
    participant Command as AddCustomerCommand
    participant TransRepo as TransactionRepository
    participant CustService as CustomerService
    participant CustRepo as CustomerRepository
    participant Result as TransactionResult

    User->>UI: POST AddCustomer=[123]
    UI->>Dispatcher: dispatch('AddCustomer', $_POST)
    Dispatcher->>Command: make(AddCustomerCommand)
    Dispatcher->>Command: execute()
    
    Command->>TransRepo: findById(123)
    TransRepo-->>Command: transaction data
    
    Command->>CustService: createFromTransaction(transaction)
    CustService->>CustService: extractCustomerData(transaction)
    CustService->>CustRepo: create(customerData)
    CustRepo-->>CustService: customerId=456
    CustService-->>Command: customerId=456
    
    Command->>Result: success(0, 0, "Created 1 customer", data)
    Result-->>Command: TransactionResult
    Command-->>Dispatcher: TransactionResult
    Dispatcher-->>UI: TransactionResult
    UI->>Result: display()
    Result->>User: Green notification "Created 1 customer"
```

## Component Diagram

```mermaid
graph TB
    subgraph Presentation Layer
        UI[process_statements.php<br/>View + Router]
    end

    subgraph Application Layer
        Dispatcher[CommandDispatcher<br/>Front Controller]
        Commands[Command Classes<br/>UnsetTrans, AddCustomer, etc.]
    end

    subgraph Domain Layer
        Services[Business Services<br/>CustomerService, VendorService, etc.]
    end

    subgraph Infrastructure Layer
        Repos[Repositories<br/>TransactionRepo, CustomerRepo, etc.]
        DB[(Database)]
    end

    subgraph Cross-Cutting
        Results[TransactionResult<br/>Value Object]
        Container[DI Container]
    end

    UI -->|POST data| Dispatcher
    Dispatcher -->|instantiate| Commands
    Commands -->|use| Services
    Services -->|use| Repos
    Repos -->|query| DB
    Commands -.->|return| Results
    Services -.->|return| Results
    Dispatcher -.->|inject| Container
    Dispatcher -->|return| Results
    Results -->|display| UI
```

## Deployment Diagram

```mermaid
graph LR
    subgraph Before Refactor
        UI1[process_statements.php<br/>100+ lines procedural POST handling]
        Controller1[bank_import_controller.php<br/>God Object with 10+ responsibilities]
        
        UI1 -->|isset POST| Controller1
        Controller1 -->|direct $_POST access| Controller1
    end

    subgraph After Refactor
        UI2[process_statements.php<br/>Clean router, 10 lines]
        Dispatcher2[CommandDispatcher<br/>Single responsibility]
        Commands2[4 Command Classes<br/>Each handles one action]
        Services2[3 Service Classes<br/>Business logic]
        
        UI2 -->|delegate| Dispatcher2
        Dispatcher2 -->|execute| Commands2
        Commands2 -->|use| Services2
    end

    style Before Refactor fill:#ffcccc
    style After Refactor fill:#ccffcc
```

## State Diagram: Command Execution

```mermaid
stateDiagram-v2
    [*] --> Idle
    Idle --> Validating : dispatch(action, POST)
    Validating --> Executing : Command found
    Validating --> Error : Command not found
    Executing --> ProcessingData : Valid POST data
    Executing --> Error : Invalid POST data
    ProcessingData --> Success : Business logic succeeds
    ProcessingData --> Warning : Partial success
    ProcessingData --> Error : Business logic fails
    Success --> [*] : return TransactionResult
    Warning --> [*] : return TransactionResult
    Error --> [*] : return TransactionResult
```

## Object Diagram: Runtime Example

```mermaid
graph TD
    subgraph "Runtime State"
        POST["$_POST<br/>['UnsetTrans' => [123 => 'Unset']]"]
        
        DispatcherObj["CommandDispatcher instance<br/>commands = ['UnsetTrans' => 'UnsetTransactionCommand']"]
        
        CommandObj["UnsetTransactionCommand instance<br/>postData = $_POST<br/>repository = TransactionRepository"]
        
        RepoObj["TransactionRepository instance<br/>connection = PDO"]
        
        ResultObj["TransactionResult instance<br/>status = 'success'<br/>message = 'Disassociated 1 transaction'<br/>data = ['count' => 1]"]
    end
    
    POST --> DispatcherObj
    DispatcherObj --> CommandObj
    CommandObj --> RepoObj
    CommandObj --> ResultObj
```

## Design Patterns Applied

### 1. Command Pattern
**Problem**: Procedural POST handling scattered across files  
**Solution**: Encapsulate each action as a Command object  
**Benefit**: Testability, extensibility, SRP

### 2. Front Controller Pattern
**Problem**: Multiple entry points for POST actions  
**Solution**: Single CommandDispatcher routes all actions  
**Benefit**: Centralized request handling

### 3. Dependency Injection
**Problem**: Hard-coded dependencies, tight coupling  
**Solution**: Constructor injection of services/repositories  
**Benefit**: Testability, flexibility, SOLID compliance

### 4. Value Object Pattern
**Problem**: Inconsistent result handling  
**Solution**: TransactionResult immutable value object  
**Benefit**: Type safety, consistent API

### 5. Repository Pattern
**Problem**: Direct database access in business logic  
**Solution**: Repositories abstract data access  
**Benefit**: Testability, separation of concerns

---

## SOLID Principles Compliance

### Single Responsibility Principle (SRP) ✅
- **Before**: bank_import_controller handles 10+ different actions
- **After**: Each command class handles exactly ONE action

### Open/Closed Principle (OCP) ✅
- **Before**: Adding new action requires modifying controller
- **After**: Add new command class, register in dispatcher

### Liskov Substitution Principle (LSP) ✅
- All commands implement CommandInterface
- Can be swapped without breaking dispatcher

### Interface Segregation Principle (ISP) ✅
- Separate interfaces for commands, services, repositories
- Clients depend only on methods they use

### Dependency Inversion Principle (DIP) ✅
- Commands depend on interfaces (object), not concrete classes
- High-level modules (commands) don't depend on low-level modules (repositories)

---

## File Organization

```
src/Ksfraser/FaBankImport/
├── Commands/                   # Application layer
│   ├── CommandDispatcher.php   # Front controller
│   ├── UnsetTransactionCommand.php
│   ├── AddCustomerCommand.php
│   ├── AddVendorCommand.php
│   └── ToggleDebitCreditCommand.php
│
├── Contracts/                  # Interface layer
│   ├── CommandInterface.php
│   └── CommandDispatcherInterface.php
│
├── Results/                    # Value objects
│   └── TransactionResult.php   # Already exists
│
└── Services/                   # Domain layer (future)
    ├── CustomerService.php
    ├── VendorService.php
    └── TransactionService.php
```

---

**Status**: Architecture documented  
**Next**: Run tests, verify implementation
