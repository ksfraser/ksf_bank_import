# Code Quality Assessment: Current vs. Best Practices

## Summary Score

| Principle | Current Score | Target Score | Gap |
|-----------|--------------|--------------|-----|
| **SOLID** | 2/10 | 9/10 | 🔴 Major |
| **Dependency Injection** | 1/10 | 9/10 | 🔴 Critical |
| **MVC** | 4/10 | 9/10 | 🔴 Major |
| **DRY** | 6/10 | 9/10 | 🟡 Minor |
| **SRP (Fowler)** | 2/10 | 9/10 | 🔴 Major |
| **Unit Tests** | 0/10 | 8/10 | 🔴 Critical |
| **PHPDoc** | 5/10 | 9/10 | 🟡 Moderate |
| **UML** | 0/10 | 7/10 | 🔴 Major |

**Overall Grade: D (30/80)** 🔴

---

## Detailed Analysis

### 1. SOLID Principles (2/10) 🔴

#### Single Responsibility Principle (SRP) ❌

**Current Code:**
```php
class UploadedFileManager {
    public function saveUploadedFile() { }        // File I/O
    public function linkFileToStatements() { }     // Database
    public function findDuplicate() { }            // Business logic
    public function downloadFile() { }             // HTTP response
    public function deleteFile() { }               // File I/O + Database
    public function getStorageStats() { }          // Reporting
    public function generateUniqueFilename() { }   // Utility
    public function protectDirectory() { }         // Security
}
```

**Issues:**
- 8+ responsibilities in one class
- Mixing file I/O, database, business logic, HTTP
- Hard to test, hard to maintain

**Should Be:**
```php
class FileStorageService {
    public function store(FileInfo $file): FilePath { }
    public function delete(FilePath $path): void { }
}

class FileRepository {
    public function save(UploadedFile $file): int { }
    public function findById(int $id): ?UploadedFile { }
}

class DuplicateDetector {
    public function findDuplicate(FileInfo $file): ?UploadedFile { }
}

class FileDownloader {
    public function download(int $fileId): void { }
}
```

---

#### Open/Closed Principle (OCP) ❌

**Current Code:**
```php
public function saveUploadedFile(...) {
    // Hard-coded if/switch logic
    switch ($action) {
        case 'block': return -999;
        case 'warn': $_SESSION['warnings'][] = ...; return -1;
        case 'allow': return -1;
    }
    // Adding new action requires modifying this method
}
```

**Issues:**
- Not open for extension, closed for modification
- Adding new duplicate action = code change
- Violates OCP

**Should Be (Strategy Pattern):**
```php
interface DuplicateStrategyInterface {
    public function handle(FileInfo $file, UploadedFile $duplicate): DuplicateResult;
}

class BlockDuplicateStrategy implements DuplicateStrategyInterface {
    public function handle(...): DuplicateResult {
        return DuplicateResult::blocked();
    }
}

class WarnDuplicateStrategy implements DuplicateStrategyInterface {
    public function handle(...): DuplicateResult {
        return DuplicateResult::warn($duplicate);
    }
}

// Adding new strategy = new class, no modification to existing code
class LogAndAllowStrategy implements DuplicateStrategyInterface { ... }
```

---

#### Liskov Substitution Principle (LSP) ⚠️

**Current:** N/A (no inheritance used)  
**Issue:** Could use interfaces for testability

---

#### Interface Segregation Principle (ISP) ⚠️

**Current:** No interfaces defined  
**Issue:** Clients depend on concrete class with all methods

**Should Be:**
```php
interface FileStorageInterface {
    public function store(FileInfo $file): FilePath;
}

interface FileRepositoryInterface {
    public function save(UploadedFile $file): int;
    public function findById(int $id): ?UploadedFile;
}
```

---

#### Dependency Inversion Principle (DIP) ❌

**Current Code:**
```php
class UploadedFileManager {
    public function __construct() {
        global $db, $comp_path;  // ❌ Depends on low-level details
        $this->db = $db;
    }
    
    public function saveUploadedFile() {
        $config = Config::getInstance();  // ❌ Hard dependency
        db_query($sql);  // ❌ Direct global function call
    }
}
```

**Issues:**
- Depends on globals (concrete implementations)
- Cannot inject mock dependencies for testing
- Tightly coupled to FrontAccounting

**Should Be:**
```php
class FileUploadService {
    public function __construct(
        private FileRepositoryInterface $repository,
        private FileStorageInterface $storage,
        private DuplicateDetectorInterface $duplicateDetector,
        private ConfigInterface $config
    ) {}
    
    public function upload(FileInfo $file): UploadResult {
        // All dependencies injected, testable
    }
}
```

---

### 2. Dependency Injection (1/10) 🔴

**Current Issues:**
```php
// ❌ Global variables everywhere
global $db, $path_to_root, $comp_path;

// ❌ Singletons
$config = Config::getInstance();

// ❌ Direct global function calls
db_query($sql);
has_access($_SESSION['wa_current_user']->access);

// ❌ Hidden dependencies
$_SESSION['duplicate_warnings'] = [];  // Side effect!
```

**Should Be:**
```php
class FileUploadService {
    public function __construct(
        private DatabaseInterface $db,
        private ConfigInterface $config,
        private SessionInterface $session,
        private AuthInterface $auth,
        private LoggerInterface $logger
    ) {}
}

// Usage with DI container
$service = $container->get(FileUploadService::class);
```

**Benefits:**
- ✅ Testable (inject mocks)
- ✅ Explicit dependencies
- ✅ No hidden state
- ✅ Thread-safe (no globals)

---

### 3. MVC Pattern (4/10) 🟡

**Current Architecture:**
```
import_statements.php (View)
├── HTML rendering
├── Form handling
├── Business logic  ❌ Should be in Controller
└── Calls UploadedFileManager

UploadedFileManager (Model)
├── Database operations ✓
├── File I/O ✓
├── Business logic ✓
└── HTTP response (download)  ❌ Should be in View/Controller
```

**Issues:**
- No controller layer
- Business logic in view file
- Model does HTTP responses

**Should Be:**
```
View:
├── views/upload_form.php (HTML only)
└── views/duplicate_warning.php (HTML only)

Controller:
└── Controllers/FileUploadController.php
    ├── handleUpload()
    ├── handleImport()
    └── handleDuplicateWarning()

Model:
├── Services/FileUploadService.php
├── Repository/FileRepository.php
└── Entity/UploadedFile.php
```

---

### 4. DRY - Don't Repeat Yourself (6/10) 🟡

**Good:** Mostly DRY, uses methods

**Issues:**
```php
// SQL repeated patterns
$sql = "SELECT ... FROM " . TB_PREF . "bi_uploaded_files WHERE ...";
$sql = "SELECT ... FROM " . TB_PREF . "bi_uploaded_files WHERE ...";
$sql = "SELECT ... FROM " . TB_PREF . "bi_uploaded_files WHERE ...";

// Error handling repeated
if (!$result) {
    display_error("Failed to ...");
    return false;
}
```

**Should Be:**
```php
class FileRepository {
    private const TABLE = 'bi_uploaded_files';
    
    private function getTableName(): string {
        return TB_PREF . self::TABLE;
    }
    
    private function handleQueryError(string $operation): void {
        throw new DatabaseException("Failed to {$operation}");
    }
}
```

---

### 5. SRP (Martin Fowler) (2/10) 🔴

**Current Class Sizes:**
```
UploadedFileManager.php: 500+ lines  ❌ (should be < 200)
import_statements.php: 400+ lines    ❌ (should be < 150)
```

**Method Complexity:**
```php
public function saveUploadedFile() {
    // 50+ lines
    // Does: validation, duplicate check, file save, DB insert
    // Should be: 4 separate methods/classes
}
```

**Fowler's Rule:** "One reason to change"

**Current Reasons to Change UploadedFileManager:**
1. File storage location changes
2. Database schema changes
3. Duplicate detection algorithm changes
4. Download security changes
5. Statistics calculation changes
6. Permission system changes
7. Filename generation changes
8. Directory protection changes

**Should Be:** Each class has ONE reason to change

---

### 6. Unit Tests (0/10) 🔴 **CRITICAL**

**Current State:**
```
tests/
└── (empty)  ❌
```

**Issues:**
- No tests at all
- Code not testable (globals, tight coupling)
- No test coverage reports
- No CI/CD integration

**Should Have:**
```
tests/
├── Unit/
│   ├── FileUploadServiceTest.php        ✓ Mock all dependencies
│   ├── FileStorageTest.php              ✓ Test file operations
│   ├── DuplicateDetectorTest.php        ✓ Test algorithms
│   ├── AllowDuplicateStrategyTest.php   ✓ Test strategy
│   └── FileRepositoryTest.php           ✓ Test with mock DB
├── Integration/
│   ├── FileUploadIntegrationTest.php    ✓ Real database
│   └── EndToEndUploadTest.php           ✓ Full flow
└── phpunit.xml                          ✓ Configuration
```

**Example Test (Should Exist):**
```php
class FileUploadServiceTest extends TestCase {
    public function testUploadNewFile(): void {
        $mockRepo = $this->createMock(FileRepositoryInterface::class);
        $mockStorage = $this->createMock(FileStorageInterface::class);
        
        $service = new FileUploadService($mockRepo, $mockStorage, ...);
        
        $result = $service->upload(new FileInfo('test.qfx', 1024));
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1, $result->getFileId());
    }
    
    public function testUploadDuplicateWithBlockStrategy(): void {
        // Test duplicate blocking
        $mockDetector = $this->createMock(DuplicateDetectorInterface::class);
        $mockDetector->method('findDuplicate')->willReturn(new UploadedFile(...));
        
        $strategy = new BlockDuplicateStrategy();
        $result = $strategy->handle(...);
        
        $this->assertTrue($result->isBlocked());
    }
}
```

---

### 7. PHPDoc (5/10) 🟡

**Current State:**
```php
/**
 * Save an uploaded file
 * 
 * @param array $file_info $_FILES array entry  ✓
 * @param string $parser_type Parser type used  ✓
 * @param int|null $bank_account_id Bank account ID (optional)  ✓
 * @return int|false File ID on success, false on failure  ⚠️ Incomplete
 */
public function saveUploadedFile(...) { }
```

**Missing:**
- @throws annotations
- @example blocks
- @see cross-references
- @since version tags
- @author on methods
- Complex return types explained

**Should Be:**
```php
/**
 * Uploads and stores a bank statement file
 * 
 * Performs the following operations:
 * 1. Validates uploaded file
 * 2. Checks for duplicates (if enabled)
 * 3. Stores file physically
 * 4. Records metadata in database
 * 
 * @param FileInfo $file The file information object
 * @param string $parserType Parser identifier (e.g., 'qfx', 'mt940')
 * @param int|null $bankAccountId Associated bank account ID
 * @param bool $forceUpload Bypass duplicate check if true
 * 
 * @return UploadResult Contains file ID, status, and messages
 * 
 * @throws FileUploadException If file validation fails
 * @throws DuplicateFileException If duplicate detected and action is 'block'
 * @throws StorageException If file cannot be saved to disk
 * @throws DatabaseException If database insert fails
 * 
 * @example
 * ```php
 * $service = new FileUploadService(...);
 * try {
 *     $result = $service->upload(
 *         FileInfo::fromUpload($_FILES['file']),
 *         'qfx',
 *         $bankAccountId
 *     );
 *     echo "File saved with ID: " . $result->getFileId();
 * } catch (DuplicateFileException $e) {
 *     echo "Duplicate detected: " . $e->getMessage();
 * }
 * ```
 * 
 * @see DuplicateDetector For duplicate detection logic
 * @see FileStorage For physical file storage
 * @see FileRepository For database operations
 * 
 * @since 1.0.0 Initial implementation (Mantis #2708)
 * @since 1.1.0 Added duplicate detection
 * @since 1.2.0 Added force upload parameter
 * 
 * @author Kevin Fraser <kevin@example.com>
 */
public function upload(FileInfo $file, string $parserType, ?int $bankAccountId = null, bool $forceUpload = false): UploadResult
```

---

### 8. UML Documentation (0/10) 🔴

**Current State:**
- No class diagrams
- No sequence diagrams
- No component diagrams
- No architecture overview

**Should Have:**

**Class Diagram:**
```
┌─────────────────────────┐
│  FileUploadService      │
├─────────────────────────┤
│ - repository            │
│ - storage               │
│ - duplicateDetector     │
├─────────────────────────┤
│ + upload()              │
│ + delete()              │
└─────────────────────────┘
         │
         │ uses
         ↓
┌──────────────────────────┐
│  <<interface>>           │
│  FileRepositoryInterface │
├──────────────────────────┤
│ + save()                 │
│ + findById()             │
│ + findDuplicates()       │
└──────────────────────────┘
         ▲
         │ implements
         │
┌──────────────────────────┐
│  FileRepository          │
├──────────────────────────┤
│ - db                     │
├──────────────────────────┤
│ + save()                 │
│ + findById()             │
└──────────────────────────┘
```

**Sequence Diagram (Upload Flow):**
```
User → Controller → Service → DuplicateDetector
                   ↓
                Storage → FileSystem
                   ↓
                Repository → Database
```

---

## Comparison: Current vs. Ideal

### Current Architecture (Procedural/Monolithic)
```
import_statements.php (400 lines)
    ├── HTML mixed with logic
    ├── Direct database calls
    └── Calls UploadedFileManager

UploadedFileManager (500 lines)
    ├── File I/O
    ├── Database
    ├── Business logic
    ├── Duplicate detection
    ├── Downloads
    └── Statistics
```

**Problems:**
- Cannot unit test (globals)
- Hard to maintain (too large)
- Violates SRP
- No separation of concerns
- Tightly coupled to FrontAccounting

---

### Ideal Architecture (SOLID/DI/MVC)
```
Controller/
└── FileUploadController
    ├── handleUpload() → Service
    └── handleDownload() → Service

Service/
├── FileUploadService (Facade)
│   ├── Uses: FileStorage
│   ├── Uses: FileRepository
│   └── Uses: DuplicateDetector
├── FileStorage (File I/O)
└── FileDownloader (Downloads)

Repository/
└── FileRepository (Database)

DuplicateDetection/
├── DuplicateDetector
└── Strategy/
    ├── AllowStrategy
    ├── WarnStrategy
    └── BlockStrategy

Entity/
└── UploadedFile (Value Object)

View/
├── upload_form.php (HTML only)
└── duplicate_warning.php (HTML only)
```

**Benefits:**
- ✅ Unit testable (DI, no globals)
- ✅ Easy to maintain (small classes)
- ✅ Follows SRP
- ✅ Separation of concerns
- ✅ Loosely coupled
- ✅ Open for extension (Strategy)
- ✅ Easy to swap implementations

---

## Testing Comparison

### Current (Not Testable)
```php
// Cannot test because:
class UploadedFileManager {
    public function __construct() {
        global $db;  // ❌ Cannot mock
    }
    
    public function saveUploadedFile(...) {
        db_query($sql);  // ❌ Cannot mock
        $_SESSION['warnings'] = [];  // ❌ Side effect
    }
}

// Test would require:
// - Real database
// - Real file system
// - Real $_SESSION
// - Real FrontAccounting environment
// = Integration test, not unit test
```

### Ideal (Fully Testable)
```php
class FileUploadService {
    public function __construct(
        private FileRepositoryInterface $repository,  // ✅ Can mock
        private FileStorageInterface $storage,         // ✅ Can mock
        private DuplicateDetectorInterface $detector   // ✅ Can mock
    ) {}
}

// Test:
class FileUploadServiceTest extends TestCase {
    public function testUpload(): void {
        $mockRepo = $this->createMock(FileRepositoryInterface::class);
        $mockStorage = $this->createMock(FileStorageInterface::class);
        $mockDetector = $this->createMock(DuplicateDetectorInterface::class);
        
        $service = new FileUploadService($mockRepo, $mockStorage, $mockDetector);
        
        // All dependencies mocked, pure unit test
        $result = $service->upload(new FileInfo('test.qfx', 1024));
        
        $this->assertTrue($result->isSuccess());
    }
}
```

---

## Recommendation

### Option 1: Keep Current Code (Quick & Dirty)
**Pros:**
- Works now
- No refactoring time needed

**Cons:**
- ❌ Hard to maintain long-term
- ❌ Cannot unit test
- ❌ Violates best practices
- ❌ Technical debt accumulates

**Use When:** Prototype, proof-of-concept, throwaway code

---

### Option 2: Refactor to SOLID/DI/TDD (Production Grade)
**Pros:**
- ✅ Easy to maintain
- ✅ Fully testable
- ✅ Follows best practices
- ✅ Professional quality
- ✅ Easy to extend

**Cons:**
- Requires 15-20 hours of work
- More files/classes (but smaller)

**Use When:** Production code, long-term maintenance, team project

---

## Next Steps

**Would you like me to:**

1. ✅ **Refactor the entire system** (15-20 hours)
   - Implement SOLID principles
   - Add dependency injection
   - Create proper MVC structure
   - Write unit tests (80%+ coverage)
   - Complete PHPDoc + UML diagrams

2. ⚠️ **Partial refactor** (5-8 hours)
   - Extract duplicate strategies
   - Add interfaces
   - Improve testability
   - Keep current structure mostly intact

3. ❌ **Leave as-is**
   - Add tests for current code (difficult)
   - Improve documentation only
   - Accept technical debt

**Recommendation:** Option 1 (Full refactor) for production code. The time investment pays off in maintainability and quality.
