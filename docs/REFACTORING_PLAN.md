# Refactoring Plan: File Upload System to SOLID/DI/TDD

## Current Problems

### Violation Summary
- ❌ **SRP:** One class does 7+ responsibilities
- ❌ **DIP:** Uses `global` variables, no injection
- ❌ **OCP:** Hard-coded switch statements
- ❌ **Unit Tests:** None exist
- ❌ **Testability:** Tightly coupled to FrontAccounting
- ⚠️ **MVC:** No controller layer
- ⚠️ **PHPDoc:** Basic but incomplete

## Proposed Architecture

### Class Breakdown (Following SRP)

```
Before (1 class, 500 lines):
└── UploadedFileManager (does everything)

After (7 classes + interfaces):
├── FileUploadService (orchestrates)
├── FileStorage (stores/retrieves files)
├── FileRepository (database operations)
├── DuplicateDetector (finds duplicates)
├── DuplicateStrategy (interface)
│   ├── AllowDuplicateStrategy
│   ├── WarnDuplicateStrategy
│   └── BlockDuplicateStrategy
├── StatementLinker (links files to statements)
└── FileDownloader (handles downloads)
```

### Directory Structure

```
src/Ksfraser/FaBankImport/
├── Services/
│   ├── FileUpload/
│   │   ├── FileUploadService.php         (Facade/orchestrator)
│   │   ├── FileStorage.php                (File system operations)
│   │   ├── FileDownloader.php             (Download handling)
│   │   └── StatementLinker.php            (Link files to statements)
│   └── DuplicateDetection/
│       ├── DuplicateDetector.php          (Finds duplicates)
│       ├── Strategy/
│       │   ├── DuplicateStrategyInterface.php
│       │   ├── AllowDuplicateStrategy.php
│       │   ├── WarnDuplicateStrategy.php
│       │   └── BlockDuplicateStrategy.php
│       └── DuplicateStrategyFactory.php   (Creates strategy)
├── Repository/
│   ├── UploadedFileRepositoryInterface.php
│   └── UploadedFileRepository.php         (Database operations)
├── Entity/
│   ├── UploadedFile.php                   (Value object)
│   └── DuplicateResult.php                (Value object)
├── ValueObject/
│   ├── FileInfo.php
│   ├── FilePath.php
│   └── FileSize.php
└── Exception/
    ├── FileUploadException.php
    ├── DuplicateFileException.php
    └── FileNotFoundException.php
```

### Tests Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── FileUpload/
│   │   │   ├── FileUploadServiceTest.php
│   │   │   ├── FileStorageTest.php
│   │   │   └── FileDownloaderTest.php
│   │   └── DuplicateDetection/
│   │       ├── DuplicateDetectorTest.php
│   │       └── Strategy/
│   │           ├── AllowDuplicateStrategyTest.php
│   │           ├── WarnDuplicateStrategyTest.php
│   │           └── BlockDuplicateStrategyTest.php
│   └── Repository/
│       └── UploadedFileRepositoryTest.php
├── Integration/
│   ├── FileUploadIntegrationTest.php
│   └── DatabaseIntegrationTest.php
└── Fixtures/
    ├── test_file.qfx
    └── MockDatabaseConnection.php
```

## Implementation Steps

### Phase 1: Interfaces & Value Objects (1-2 hours)

1. Create interfaces
2. Create value objects (UploadedFile, FileInfo, etc.)
3. Create custom exceptions

### Phase 2: Repository Pattern (1-2 hours)

4. Extract database operations to Repository
5. Add interface for repository
6. Inject repository into services

### Phase 3: Strategy Pattern for Duplicates (2-3 hours)

7. Create DuplicateStrategyInterface
8. Implement 3 strategies (Allow, Warn, Block)
9. Create factory for strategy selection

### Phase 4: Service Layer (2-3 hours)

10. Split UploadedFileManager into focused services
11. Create FileUploadService as facade
12. Inject dependencies via constructor

### Phase 5: Dependency Injection (1-2 hours)

13. Create service container
14. Remove all `global` usage
15. Use constructor injection

### Phase 6: Unit Tests (4-6 hours)

16. Write unit tests for each class
17. Mock dependencies
18. Aim for 80%+ coverage

### Phase 7: Documentation (2-3 hours)

19. Complete PHPDoc for all classes/methods
20. Create UML class diagrams
21. Create sequence diagrams
22. Write integration examples

## Total Estimated Time: 13-21 hours

## Would you like me to implement this refactoring?
