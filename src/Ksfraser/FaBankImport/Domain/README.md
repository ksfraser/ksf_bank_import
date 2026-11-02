# Domain Layer Architecture

## Overview

This document explains the architectural patterns used in the Domain layer and clarifies the differences between Value Objects, Entities, DTOs, and other common patterns.

---

## Core Concepts

### Value Object

**Definition:** Immutable object defined by its values, not identity.

**Characteristics:**
- ✅ **Immutable** - Cannot be changed after creation
- ✅ **Equality by value** - Two objects with same values are equal
- ✅ **No identity** - No database ID or primary key
- ✅ **Self-validating** - Validates data in constructor
- ✅ **Domain logic** - Contains methods that operate on its data
- ✅ **Replaceable** - Two instances with same values are interchangeable

**When to Use:**
- Representing domain concepts that are defined by their attributes
- Immutable data structures
- Complex validation rules
- Domain calculations

**Examples in This Project:**
- `PartnerData` - A keyword associated with a partner
- `Keyword` - A normalized search term
- `KeywordMatch` - A search result with scoring
- `MatchConfidence` - Confidence calculation

**Example Code:**
```php
// Value Object
$data1 = new PartnerData(123, 2, 0, 'shoppers');
$data2 = new PartnerData(123, 2, 0, 'shoppers');

$data1->equals($data2); // true - same values

// Immutability - returns new instance
$data3 = $data1->withIncrementedCount();
// $data1 unchanged, $data3 is new object
```

**Real-World Analogy:**
Like a $20 bill - any $20 bill is equivalent to any other $20 bill. If you tear it and tape it back together, it's a "new" $20 bill. No identity, just the value matters.

---

### Entity (Domain Model)

**Definition:** Object with a unique identity that persists over time.

**Characteristics:**
- ✅ **Has identity** - Usually an `id` field (database primary key)
- ✅ **Mutable** - Can be changed over time
- ✅ **Equality by identity** - Compared by ID, not values
- ✅ **Lifecycle** - Created, modified, deleted
- ✅ **Can contain Value Objects** - Often composed of VOs
- ✅ **Business logic** - Core domain operations

**When to Use:**
- Objects that need to be tracked over time
- Objects with a unique identifier
- Objects that change state
- Core domain concepts with lifecycle

**Example (Not in this module yet):**
```php
// Entity
class Customer {
    private int $id;              // Identity!
    private string $name;
    private EmailAddress $email;  // Value Object
    
    public function changeName(string $newName): void {
        $this->name = $newName; // Mutable!
    }
    
    public function equals(Customer $other): bool {
        return $this->id === $other->id; // Compare by ID
    }
}

// Usage
$customer1 = new Customer(123, 'John Doe', new EmailAddress('john@example.com'));
$customer1->changeName('Jane Doe'); // Modifies existing object

$customer2 = new Customer(123, 'Different Name', new EmailAddress('other@example.com'));
$customer1->equals($customer2); // true - same ID!
```

**Real-World Analogy:**
Like your bank account - your account #12345 is unique, even if the balance changes. Identity persists over time. Two accounts with the same balance are NOT the same account.

---

### DTO (Data Transfer Object)

**Definition:** Simple container for transferring data between layers or systems.

**Characteristics:**
- ✅ **No behavior** - Just getters/setters or public properties
- ✅ **Minimal validation** - Or none at all
- ✅ **Serializable** - Often used for API responses/requests
- ✅ **Mutable** - Usually has setters or public properties
- ✅ **No identity** - Just a data bag
- ✅ **No business logic** - Pure data transfer

**When to Use:**
- Transferring data between layers (e.g., Controller → Service)
- API request/response payloads
- Decoupling domain objects from external interfaces
- Serialization for JSON, XML, etc.

**Example:**
```php
// DTO
class PartnerDataDTO {
    public int $partnerId;
    public string $data;
    public int $occurrenceCount;
    
    // No validation, no business logic
    // Just a container for transfer
}

// Usage in API
class PartnerDataController {
    public function index(): array {
        $partnerDataList = $this->service->getAll();
        
        return array_map(function($partnerData) {
            $dto = new PartnerDataDTO();
            $dto->partnerId = $partnerData->getPartnerId();
            $dto->data = $partnerData->getData();
            $dto->occurrenceCount = $partnerData->getOccurrenceCount();
            return $dto;
        }, $partnerDataList);
    }
}
```

**Real-World Analogy:**
Like a bank statement - just a snapshot of data at a point in time. No behavior, just information transfer. Not the real account, just a representation.

---

### Repository (DAO/Model Layer)

**Definition:** Interface for accessing and persisting domain objects.

**Characteristics:**
- ✅ **Data access only** - No business logic
- ✅ **Returns domain objects** - Value Objects or Entities
- ✅ **Hides database details** - Abstracts SQL, ORM, etc.
- ✅ **Collection-like interface** - find(), save(), delete()
- ✅ **Interface-based** - Allows swapping implementations

**When to Use:**
- Accessing data from database
- Abstracting persistence mechanism
- Testability (mock repository in tests)

**Example in This Project:**
```php
// Repository Interface
interface PartnerDataRepositoryInterface {
    public function find(int $partnerId, ...): ?PartnerData;
    public function save(PartnerData $data): bool;
    public function delete(...): bool;
}

// Implementation
class DatabasePartnerDataRepository implements PartnerDataRepositoryInterface {
    public function find(int $partnerId, ...): ?PartnerData {
        $sql = "SELECT * FROM bi_partners_data WHERE partner_id = ?";
        $result = db_query($sql, [$partnerId]);
        
        if ($row = db_fetch($result)) {
            return PartnerData::fromArray($row); // Returns Value Object
        }
        return null;
    }
}
```

---

### Service (Business Logic Layer)

**Definition:** Orchestrates domain objects and repositories to perform business operations.

**Characteristics:**
- ✅ **Business logic** - Coordinates domain operations
- ✅ **Uses repositories** - For data access
- ✅ **Stateless** - No instance state (or minimal)
- ✅ **Transaction management** - Handles database transactions
- ✅ **Validation** - Business rule validation

**When to Use:**
- Business operations that span multiple objects
- Operations that don't naturally belong to a single object
- Coordinating repositories and domain objects

**Example (Coming in Phase 3):**
```php
// Service
class PartnerDataService {
    public function __construct(
        private PartnerDataRepositoryInterface $repository
    ) {}
    
    public function saveKeyword(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword
    ): bool {
        // Business logic
        if (strlen($keyword) < 3) {
            throw new InvalidArgumentException('Keyword too short');
        }
        
        // Create Value Object
        $partnerData = new PartnerData($partnerId, $partnerType, $partnerDetailId, $keyword);
        
        // Use repository
        return $this->repository->save($partnerData);
    }
}
```

---

## Comparison Table

| Aspect | Value Object | Entity | DTO | Repository | Service |
|--------|-------------|--------|-----|------------|---------|
| **Identity** | No | Yes (ID) | No | N/A | N/A |
| **Mutability** | Immutable | Mutable | Mutable | N/A | Stateless |
| **Equality** | By value | By ID | N/A | N/A | N/A |
| **Validation** | Yes | Yes | Minimal | No | Yes |
| **Business Logic** | Yes | Yes | No | No | Yes |
| **Persistence** | Via Repo | Via Repo | No | Handles | Uses Repo |
| **Layer** | Domain | Domain | Interface | Data Access | Application |

---

## Architecture Layers (What We're Building)

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  (Controllers, Views - Not part of Domain)                   │
└────────────────────────────────────┬────────────────────────┘
                                     │
┌────────────────────────────────────┴────────────────────────┐
│                   Service Layer                              │
│  ┌────────────────────┐  ┌──────────────────────────────┐  │
│  │ PartnerDataService │  │ KeywordMatchingService       │  │
│  │ (Business Logic)   │  │ (Search & Scoring)           │  │
│  └────────────────────┘  └──────────────────────────────┘  │
└────────────────────────────────────┬────────────────────────┘
                                     │
┌────────────────────────────────────┴────────────────────────┐
│                   Domain Layer                               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Value Objects (Immutable)                              │ │
│  │ ├── PartnerData                                        │ │
│  │ ├── Keyword                                            │ │
│  │ ├── KeywordMatch                                       │ │
│  │ └── MatchConfidence                                    │ │
│  └────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Entities (if we had them - mutable, with ID)          │ │
│  │ └── (None in this module yet)                         │ │
│  └────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Exceptions                                             │ │
│  │ ├── PartnerDataNotFoundException                      │ │
│  │ └── InvalidKeywordException                           │ │
│  └────────────────────────────────────────────────────────┘ │
└────────────────────────────────────┬────────────────────────┘
                                     │
┌────────────────────────────────────┴────────────────────────┐
│                   Repository Layer (Data Access)             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ PartnerDataRepositoryInterface                         │ │
│  └───────────────────────┬────────────────────────────────┘ │
│                          │                                   │
│  ┌───────────────────────▼────────────────────────────────┐ │
│  │ DatabasePartnerDataRepository                          │ │
│  │ (SQL queries, returns Value Objects)                   │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Our Domain Objects Explained

### PartnerData (Value Object)

```php
class PartnerData {
    private int $partnerId;      // NOT an identity, just a reference
    private int $partnerType;
    private int $partnerDetailId;
    private string $data;        // The actual keyword
    private int $occurrenceCount;
}
```

**Why it's a Value Object:**
- ❌ No unique identity (no auto-increment ID)
- ✅ Immutable (no setters, `withIncrementedCount()` returns new instance)
- ✅ Compared by value (`equals()` compares all fields)
- ✅ Represents a concept: "a keyword associated with a partner"
- ✅ Self-validating (validates in constructor)

**Not an Entity because:**
- The combination of `(partnerId, partnerType, partnerDetailId, data)` IS the identity
- There's no separate `id` field that tracks it over time
- Two instances with same values are considered identical
- It's immutable - you don't modify it, you create a new one

### Keyword (Value Object)

```php
class Keyword {
    private string $text;
}
```

**Why it's a Value Object:**
- Represents a single normalized keyword
- Immutable (no way to change text after creation)
- No identity (just the text value)
- Self-validating (length, format checks)

### KeywordMatch (Value Object)

```php
class KeywordMatch {
    private int $partnerId;
    private array $matchedKeywords;
    private float $score;
    private MatchConfidence $confidence;
}
```

**Why it's a Value Object:**
- Represents a search result
- Immutable snapshot of a match
- No identity (you don't track matches over time)
- Composed of other Value Objects (Keyword, MatchConfidence)

### MatchConfidence (Value Object)

```php
class MatchConfidence {
    private float $keywordCoverage;
    private float $scoreStrength;
    private float $percentage;
}
```

**Why it's a Value Object:**
- Represents a calculation result
- Immutable
- No identity
- Pure domain logic (confidence calculation)

---

## Pattern Examples from Other Projects

### Laravel (ActiveRecord Pattern)

```php
// This combines Entity + Repository
class User extends Model {
    protected $fillable = ['name', 'email'];
    
    // Has identity ($id)
    // Mutable (can change properties)
    // Knows about database (save(), delete())
}

// Usage
$user = User::find(1);
$user->name = 'New Name';
$user->save(); // Saves to database
```

**Different from our approach:**
- We separate Entity from Repository
- More testable (can mock repository)
- Better separation of concerns

### Doctrine (Data Mapper Pattern)

```php
// Entity (similar to our approach)
class Product {
    private int $id;
    private string $name;
    // Business logic here
}

// Repository (similar to our approach)
class ProductRepository {
    public function find(int $id): ?Product {
        // Database logic here
    }
}
```

**Similar to our approach:**
- Separate Entity from Repository
- Entity doesn't know about database
- Repository returns domain objects

### Traditional PHP/FA (Table Data Gateway)

```php
// Traditional FA approach
class bi_partners_data extends generic_fa_interface_model {
    // Mixes data + data access + business logic
    public $partner_id;
    public $data;
    
    public function save() {
        // SQL here
    }
}
```

**Problems:**
- Tight coupling (data + database)
- Hard to test
- Violates Single Responsibility Principle

---

## Benefits of Our Approach

### Testability
```php
// Easy to test with mocked repository
$mockRepo = $this->createMock(PartnerDataRepositoryInterface::class);
$service = new PartnerDataService($mockRepo);
// Test service without database
```

### Separation of Concerns
- Value Objects: Data + validation
- Repository: Database access
- Service: Business logic

### Type Safety
```php
// Type hints catch errors at compile time
function processPartnerData(PartnerData $data): void {
    // $data is guaranteed to be valid
}
```

### Immutability Benefits
- Thread-safe
- No unexpected modifications
- Easy to reason about
- Can be cached safely

### Flexibility
- Swap database implementations
- Add caching layer
- Mock for testing
- Support multiple databases

---

## Common Misconceptions

### "Value Objects are just DTOs"
**Wrong!** Value Objects have:
- Business logic (e.g., `withIncrementedCount()`)
- Validation (e.g., `validatePartnerId()`)
- Domain behavior (e.g., `equals()`)

DTOs are just dumb data containers.

### "Value Objects can't have methods"
**Wrong!** Value Objects can have:
- Comparison methods (`equals()`)
- Transformation methods (`withIncrementedCount()`)
- Calculation methods (like `MatchConfidence::calculate()`)
- Domain logic (as long as it doesn't modify state)

### "Immutability is inefficient"
**Not really!** PHP's copy-on-write means:
- Shallow copies are cheap
- Only copied when modified
- Modern PHP (7.4+) has optimizations

Benefits outweigh minimal performance cost.

### "We don't need all this complexity"
**It pays off!**
- Better testability
- Fewer bugs (immutability, validation)
- Easier to understand (clear responsibilities)
- Maintainability (can change internals without affecting API)

---

## When to Use Each Pattern

| Pattern | Use When | Example |
|---------|----------|---------|
| **Value Object** | Concept defined by values, immutable | Money, DateRange, Keyword |
| **Entity** | Object with identity that changes | Customer, Order, Transaction |
| **DTO** | Transferring data between layers | API request/response |
| **Repository** | Data access needs abstraction | Any database operations |
| **Service** | Business logic spans multiple objects | Order processing, search |

---

## Further Reading

- **Domain-Driven Design** by Eric Evans
- **Implementing Domain-Driven Design** by Vaughn Vernon
- **Clean Architecture** by Robert C. Martin
- **Patterns of Enterprise Application Architecture** by Martin Fowler

---

## Questions?

If you're unsure whether something should be a Value Object, Entity, or DTO, ask:

1. **Does it have identity?**
   - Yes → Entity
   - No → Continue to #2

2. **Should it be immutable?**
   - Yes → Value Object
   - No → Continue to #3

3. **Does it have behavior/validation?**
   - Yes → Value Object
   - No → DTO

4. **Is it just for data transfer?**
   - Yes → DTO
   - No → Value Object or Entity

---

*Last updated: October 20, 2025*
