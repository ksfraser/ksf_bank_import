# BiTransaction Refactoring - SRP Architecture

**Status**: Design Phase (Ready for TDD implementation)  
**Goal**: Refactor BiTransaction/BiTransactions into multiple SRP classes  

---

## Current Architecture Issues

### Problem 1: Singular vs Plural Confusion
```
class bi_transactions extends generic_fa_interface_model  // DAO for TABLE operations
  ├── Bulk query methods: get_transactions(), update_transactions(), reset_transactions()
  ├── Single record methods: get_transaction(), trans_exists()
  ├── Insert/update/delete: insert_transaction(), update(), toggle_debit_credit()
  └── Status management: matched, created flags

class bi_transaction extends bi_transactions  // Single ENTITY + inherits table operations
  ├── Extends parent to get all table methods
  ├── Adds partner-specific fields: partnerId, custBranch, invoiceNo
  ├── Adds partner-specific methods: extractPost(), handlePartnerSelection()
  └── Mixes concerns: Entity + Form Handling + Data Access
```

### Problem 2: DAO Anti-Pattern (Mixed Concerns)
- **Data Access**: query building, SQL execution, row mapping
- **Domain Logic**: transaction validation, state transitions (matched/created flags)
- **Form Handling**: extractPost(), partner selection logic
- **Business Logic**: toggleDebitCredit(), transaction counter logic
- **All mixed in one class!**

### Problem 3: Inheritance Over Composition
- BiTransaction extends BiTransactions to reuse table methods
- But BiTransaction represents ONE entity, not a collection
- This violates Liskov Substitution Principle

---

## Target Architecture: SRP Refactoring

### Layer 1: Domain Models (Immutable Entities)
```php
// src/Ksfraser/FaBankImport/Models/BiTransaction.php
final class BiTransaction {
    private int $id;
    private int $smtId;
    private \DateTimeImmutable $valueTimestamp;
    private string $account;
    private string $accountName;
    private string $transactionCode;
    private string $transactionDC;  // 'D' or 'C'
    private float $transactionAmount;
    private string $transactionTitle;
    private int $status;            // 0=unmatched, 1=matched, etc
    private bool $matched;
    private bool $created;
    // ... other properties
    
    // IMMUTABLE: only getters, no setters
    // Validation in constructor
    private function __construct(array $row) {
        // Strict validation
        $this->id = (int)($row['id'] ?? 0);
        // ... validate each field
    }
    
    // Factory methods
    public static function create(array $data): self { ... }
    public static function fromDatabase(array $row): self { ... }
    
    // Value object methods
    public function getId(): int { ... }
    public function getTransactionDC(): string { ... }
    public function getAmount(): float { ... }
    // ... getters only
    
    // Domain methods (immutable, return new instances)
    public function withMatchedStatus(): self { ... }  // returns new BiTransaction
    public function withCreatedStatus(): self { ... }
    public function toggleDebitCredit(): self { ... }  // returns new with toggled DC
}
```

### Layer 2: Data Transfer Objects
```php
// src/Ksfraser/FaBankImport/DTOs/BiTransactionDTO.php
final class BiTransactionDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $transactionCode,
        public readonly float $amount,
        public readonly string $status,
        public readonly ?int $partnerId = null,
        // ... other properties
    ) {}
    
    public static function fromEntity(BiTransaction $entity): self { ... }
    public static function fromArray(array $data): self { ... }
    
    public function toArray(): array { ... }
}

// Collection DTO
final class BiTransactionCollectionDTO {
    /** @var BiTransactionDTO[] */
    public readonly array $items;
    public readonly int $totalCount;
    public readonly int $currentPage;
    public readonly int $totalPages;
    public readonly int $offset;
    public readonly int $limit;
    
    public function __construct(...) { ... }
}
```

### Layer 3: Repository Pattern
```php
// src/Ksfraser/FaBankImport/Contracts/BiTransactionRepositoryInterface.php
interface BiTransactionRepositoryInterface {
    public function findById(int $id): BiTransaction;
    public function findBy(BiTransactionQuerySpecification $spec): array;
    public function save(BiTransaction $transaction): void;
    public function delete(int $id): void;
    public function countMatching(BiTransactionQuerySpecification $spec): int;
}

// src/Ksfraser/FaBankImport/Repositories/BiTransactionRepository.php
final class BiTransactionRepository implements BiTransactionRepositoryInterface {
    private string $tablePrefix;
    private PDO $pdo;
    
    public function __construct(PDO $pdo, string $tablePrefix = '0_') {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }
    
    public function findById(int $id): BiTransaction {
        $sql = "SELECT * FROM " . $this->tablePrefix . "bi_transactions WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new BiTransactionNotFoundException("Transaction $id not found");
        }
        
        return BiTransaction::fromDatabase($row);
    }
    
    // Delegation to QueryBuilder for complex queries
    public function findBy(BiTransactionQuerySpecification $spec): array {
        $builder = new BiTransactionQueryBuilder($this->pdo, $this->tablePrefix);
        $sql = $builder->buildSelect($spec);
        // ... execute and return BiTransaction[]
    }
}
```

### Layer 4: Query Builder (Specification Pattern)
```php
// src/Ksfraser/FaBankImport/Specifications/BiTransactionQuerySpecification.php
final class BiTransactionQuerySpecification {
    private ?int $status = null;
    private ?\DateTimeImmutable $afterDate = null;
    private ?\DateTimeImmutable $beforeDate = null;
    private ?float $amount = null;
    private ?int $offset = null;
    private ?int $limit = null;
    
    public function withStatus(int $status): self { ... }
    public function afterDate(\DateTimeImmutable $date): self { ... }
    public function beforeDate(\DateTimeImmutable $date): self { ... }
    public function withAmount(float $amount): self { ... }
    public function paginate(int $offset, int $limit): self { ... }
}

// src/Ksfraser/FaBankImport/QueryBuilders/BiTransactionQueryBuilder.php
final class BiTransactionQueryBuilder {
    public function buildSelect(BiTransactionQuerySpecification $spec): string {
        $sql = "SELECT * FROM {$this->table}";
        $conditions = [];
        
        if ($spec->getStatus() !== null) {
            $conditions[] = "status = " . $spec->getStatus();
        }
        // ... build WHERE clause
        
        return $sql . " WHERE " . implode(' AND ', $conditions);
    }
}
```

### Layer 5: Service Layer (Business Logic)
```php
// src/Ksfraser/FaBankImport/Services/BiTransactionService.php
final class BiTransactionService {
    public function __construct(
        private BiTransactionRepositoryInterface $repository,
        private BiTransactionEventDispatcher $dispatcher,
    ) {}
    
    public function markAsMatched(int $transactionId, int $faTransNo, int $faTransType): void {
        $transaction = $this->repository->findById($transactionId);
        
        // Domain logic: transition to matched state
        $matched = $transaction->withMatchedStatus()
            ->withFaTransactionReference($faTransNo, $faTransType);
        
        $this->repository->save($matched);
        
        // Dispatch event for downstream processing
        $this->dispatcher->dispatch(new TransactionMatchedEvent($matched));
    }
    
    public function toggleDebitCredit(int $transactionId): BiTransactionDTO {
        $transaction = $this->repository->findById($transactionId);
        $toggled = $transaction->toggleDebitCredit();
        
        $this->repository->save($toggled);
        
        return BiTransactionDTO::fromEntity($toggled);
    }
    
    public function getTransactionsPaginated(
        int $page = 1,
        int $pageSize = 10,
        ?int $status = null
    ): BiTransactionCollectionDTO {
        $spec = new BiTransactionQuerySpecification();
        
        if ($status !== null) {
            $spec = $spec->withStatus($status);
        }
        
        $offset = ($page - 1) * $pageSize;
        $spec = $spec->paginate($offset, $pageSize);
        
        $items = $this->repository->findBy($spec);
        $total = $this->repository->countMatching($spec);
        
        return new BiTransactionCollectionDTO(
            array_map(fn(BiTransaction $t) => BiTransactionDTO::fromEntity($t), $items),
            $total,
            $page,
            (int)ceil($total / $pageSize),
            $offset,
            $pageSize
        );
    }
}
```

### Layer 6: Command Handlers (Form Processing)
```php
// src/Ksfraser/FaBankImport/Handlers/BiTransactionCommandHandler.php
final class BiTransactionCommandHandler {
    public function __construct(
        private BiTransactionService $service,
        private PartnerRepository $partnerRepository,
    ) {}
    
    public function handleExtractPost(int $transactionId, array $_POST): BiTransactionDTO {
        // Extract form data
        $partnerId = (int)($_POST["partnerId_{$transactionId}"] ?? 0);
        $cids = array_filter(explode(',', $_POST["cids_{$transactionId}"] ?? ''));
        
        // Load transaction and partner
        $transaction = $this->service->getTransaction($transactionId);
        $partner = $partnerId ? $this->partnerRepository->findById($partnerId) : null;
        
        // Business logic: assign partner
        $updated = $transaction->withPartner($partner);
        
        // Persist
        $this->service->update($updated);
        
        return BiTransactionDTO::fromEntity($updated);
    }
}
```

### Layer 7: Exception Hierarchy
```php
// src/Ksfraser/FaBankImport/Exceptions/BiTransactionException.php
class BiTransactionException extends \RuntimeException {}

class BiTransactionNotFoundException extends BiTransactionException {}
class InvalidBiTransactionException extends BiTransactionException {}
class BiTransactionAlreadyMatchedException extends BiTransactionException {}
```

---

## Refactoring Strategy: Incremental TDD

### Phase 1: Create Entity + DTO (Week 1)
**Step 1a: Write Tests for BiTransaction Entity**
```php
// tests/Unit/Models/BiTransactionTest.php
class BiTransactionTest extends TestCase {
    public function testCanCreateFromDatabaseRow() {
        $row = [
            'id' => 123,
            'transactionCode' => 'ABC123',
            'transactionDC' => 'D',
            'transactionAmount' => 100.50,
            // ...
        ];
        
        $transaction = BiTransaction::fromDatabase($row);
        
        $this->assertEquals(123, $transaction->getId());
        $this->assertEquals('ABC123', $transaction->getTransactionCode());
    }
    
    public function testToggleDebitCreditReturnsNewInstance() {
        $original = BiTransaction::fromDatabase(['transactionDC' => 'D', ...]);
        $toggled = $original->toggleDebitCredit();
        
        // Original unchanged
        $this->assertEquals('D', $original->getTransactionDC());
        // New instance has toggled value
        $this->assertEquals('C', $toggled->getTransactionDC());
    }
    
    public function testValidatesRequiredFields() {
        $this->expectException(InvalidBiTransactionException::class);
        
        BiTransaction::fromDatabase(['id' => 123]); // missing required fields
    }
}
```

**Step 1b: Implement BiTransaction Entity**
```php
// src/Ksfraser/FaBankImport/Models/BiTransaction.php
final class BiTransaction {
    // Implementation matching tests
}
```

**Step 1c: Write Tests for BiTransactionDTO**
```php
// tests/Unit/DTOs/BiTransactionDTOTest.php
class BiTransactionDTOTest extends TestCase {
    public function testCanConvertFromEntity() {
        $entity = BiTransaction::fromDatabase([...]);
        $dto = BiTransactionDTO::fromEntity($entity);
        
        $this->assertEquals($entity->getId(), $dto->id);
    }
    
    public function testCanConvertToArray() {
        $dto = new BiTransactionDTO(123, 'ABC123', 100.50, 'matched');
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(123, $array['id']);
    }
}
```

### Phase 2: Create Repository (Week 2)
**Step 2a: Write Tests for BiTransactionRepository**
```php
// tests/Unit/Repositories/BiTransactionRepositoryTest.php
class BiTransactionRepositoryTest extends TestCase {
    public function testFindByIdLoadsFromDatabase() {
        $mockPdo = $this->createMock(PDO::class);
        $repo = new BiTransactionRepository($mockPdo, '0_');
        
        // Mock PDO execution
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(['id' => 123, ...]);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $transaction = $repo->findById(123);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals(123, $transaction->getId());
    }
    
    public function testFindByIdThrowsWhenNotFound() {
        $mockPdo = $this->createMock(PDO::class);
        $repo = new BiTransactionRepository($mockPdo);
        
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(false);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $this->expectException(BiTransactionNotFoundException::class);
        $repo->findById(999);
    }
    
    public function testSavePersistsTransaction() {
        $mockPdo = $this->createMock(PDO::class);
        $repo = new BiTransactionRepository($mockPdo);
        
        $transaction = BiTransaction::create([...]);
        
        // Assert save doesn't throw
        $repo->save($transaction);
    }
}
```

### Phase 3: Create Service Layer (Week 3)
**Step 3a: Write Tests for BiTransactionService**
```php
// tests/Unit/Services/BiTransactionServiceTest.php
class BiTransactionServiceTest extends TestCase {
    public function testMarkAsMatchedTransitionsState() {
        $mockRepo = $this->createMock(BiTransactionRepositoryInterface::class);
        $service = new BiTransactionService($mockRepo);
        
        $original = BiTransaction::fromDatabase(['id' => 123, 'matched' => false, ...]);
        $mockRepo->method('findById')->willReturn($original);
        
        $service->markAsMatched(123, 500, 10);
        
        // Assert save was called with matched transaction
        // Can use ArgumentCaptor or similar
    }
    
    public function testGetPaginatedTransactions() {
        $mockRepo = $this->createMock(BiTransactionRepositoryInterface::class);
        $service = new BiTransactionService($mockRepo);
        
        // Mock repository responses
        $transactions = [/* BiTransaction objects */];
        $mockRepo->method('findBy')->willReturn($transactions);
        $mockRepo->method('countMatching')->willReturn(150);
        
        $result = $service->getTransactionsPaginated(2, 10);
        
        $this->assertInstanceOf(BiTransactionCollectionDTO::class, $result);
        $this->assertEquals(150, $result->totalCount);
        $this->assertEquals(2, $result->currentPage);
    }
}
```

---

## Migration Path (Incremental)

### Step 1: Create PSR-4 versions alongside legacy
```
Root-level (legacy):           PSR-4 (new):
class.bi_transaction.php  →    src/.../Models/BiTransaction.php
class.bi_transactions.php →    src/.../Models/BiTransaction + Repository + Service
```

### Step 2: Create backward-compat adapter (old class extends new)
```php
// class.bi_transaction.php (after migration)
<?php
trigger_error('bi_transaction.php is deprecated', E_USER_DEPRECATED);

class bi_transaction extends \Ksfraser\FaBankImport\Models\BiTransactionAdapter {}
```

### Step 3: Update tests incrementally
- Activate deprecated tests
- Run tests with new classes
- Verify 1495 baseline stays passing

### Step 4: Remove legacy files (final cleanup)

---

## Other bi_* Classes Refactoring Plan

| Legacy Class | Refactored To | SRP Breakdown |
|---|---|---|
| bi_lineitem | BiLineItem Entity | Entity + Repository + Service + DTO |
| bi_statements | BiStatement Entity | Entity + Repository + Service |
| bi_partners_data | BiPartnerData Entity | Entity + Repository + Cache Service |
| bi_counterparty_model | BiCounterparty Entity | Entity + Repository + Matcher Service |
| bi_transactionTitle_model | BiTransactionTitle VO | Value Object + Repository |

---

## Expected Outcomes After Refactoring

### Before (Anti-Pattern)
```php
$trans = new bi_transaction();
$trans->set('transactionDC', 'D');
$result = $trans->toggleDebitCredit();
// Result mixed with side effects, uncertain state
```

### After (SRP)
```php
$transaction = $repository->findById(123);
$toggled = $transaction->toggleDebitCredit(); // New immutable instance
$repository->save($toggled);

// Or via service
$dto = $service->toggleDebitCredit(123); // Returns updated DTO
```

### Benefits
✓ Immutable entities (predictable state)
✓ Testable without database (DTOs, entities)
✓ Separated concerns (repository, service, handlers)
✓ Reusable components
✓ Type-safe (specific exceptions)
✓ Easy to compose complex queries (QueryBuilder)
✓ Easier FA version upgrades (DI)
✓ SOLID principles compliance

---

## Testing Strategy: TDD Approach

### Test Pyramid
```
       /\           E2E Integration Tests
      /  \
     /----\
    /      \      Service Layer Tests
   /        \
  /----------\
 /            \    Repository Tests
/              \
 Entity/DTO      Domain Object Tests
  Tests (Core)
```

### Test Files to Create
1. `tests/Unit/Models/BiTransactionTest.php` - Entity tests
2. `tests/Unit/DTOs/BiTransactionDTOTest.php` - DTO tests
3. `tests/Unit/Repositories/BiTransactionRepositoryTest.php` - Repo tests
4. `tests/Unit/Services/BiTransactionServiceTest.php` - Service tests
5. `tests/Unit/QueryBuilders/BiTransactionQueryBuilderTest.php` - Query tests
6. `tests/Integration/BiTransactionIntegrationTest.php` - Full flow tests

### Run Tests Constantly
```bash
# After each small change
php vendor/bin/phpunit tests/Unit/Models/BiTransactionTest.php

# After implementation phase
php vendor/bin/phpunit tests/Unit/

# Verify baseline
php run-approved-tests.php
```

---

## Implementation Timeline

| Week | Task | Tests | Deliverable |
|------|------|-------|-------------|
| 1 | Entity + DTO | Unit tests | BiTransaction + BiTransactionDTO classes |
| 2 | Repository | Repo tests | BiTransactionRepository (with mocks) |
| 3 | Service | Service tests | BiTransactionService |
| 4 | QueryBuilder | Builder tests | BiTransactionQueryBuilder + Specification |
| 5 | Handlers + cleanup | Integration | Command handlers + backward compat |
| **Total** | **PSR-4 complete** | **100+ tests** | **Full SRP refactor** |

---

## Success Criteria

✓ All 1495 approved tests still passing  
✓ 50+ new unit tests for refactored classes  
✓ Zero require_once in new PSR-4 classes  
✓ 100% type hints in new code  
✓ Immutable domain objects  
✓ Dependency injection throughout  
✓ No mixed concerns (entity ≠ repository ≠ service)  
✓ Old tests activated and passing with new classes  

