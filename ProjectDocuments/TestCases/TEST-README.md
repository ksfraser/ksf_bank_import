# Unit Tests - Bank Import Module

## Overview

Comprehensive unit test suite for Phase 2 refactoring following TDD principles.

**Test Coverage Target**: 80%+  
**Testing Framework**: PHPUnit 9.x  
**Test Location**: `tests/`

---

## Test Structure

```
tests/
├── ValueObject/
│   ├── FileInfoTest.php             ✅ 20 tests
│   ├── DuplicateResultTest.php      ✅ 10 tests
│   └── UploadResultTest.php         ✅ 15 tests
│
├── Entity/
│   └── UploadedFileTest.php         ✅ 12 tests
│
├── Strategy/
│   └── DuplicateStrategyTest.php    ✅ 15 tests
│
└── Service/
    ├── FileStorageServiceTest.php   ✅ 20 tests
    ├── DuplicateDetectorTest.php    ⏳ TODO
    ├── FileUploadServiceTest.php    ⏳ TODO
    └── Integration/
        └── FullUploadFlowTest.php   ⏳ TODO
```

---

## Installation

### Install PHPUnit via Composer

```bash
# Navigate to module directory
cd c:\Users\prote\Documents\ksf_bank_import

# Install PHPUnit (dev dependency)
composer require --dev phpunit/phpunit ^9.6

# Install PHPUnit Bridge (if needed for FrontAccounting)
composer require --dev symfony/phpunit-bridge
```

### Verify Installation

```bash
vendor/bin/phpunit --version
# Should output: PHPUnit 9.6.x
```

---

## Running Tests

### Run All Tests

```powershell
# Windows PowerShell
.\vendor\bin\phpunit

# Or with explicit config
.\vendor\bin\phpunit --configuration phpunit.xml
```

### Run Specific Test Suite

```powershell
# Value Objects only
.\vendor\bin\phpunit tests/ValueObject

# Entities only
.\vendor\bin\phpunit tests/Entity

# Strategies only
.\vendor\bin\phpunit tests/Strategy

# Services only
.\vendor\bin\phpunit tests/Service
```

### Run Single Test File

```powershell
.\vendor\bin\phpunit tests/ValueObject/FileInfoTest.php
```

### Run Single Test Method

```powershell
.\vendor\bin\phpunit --filter testConstructorWithValidData tests/ValueObject/FileInfoTest.php
```

### Run with Coverage Report

```powershell
# HTML coverage report
.\vendor\bin\phpunit --coverage-html coverage/

# Text coverage report
.\vendor\bin\phpunit --coverage-text

# Coverage for specific class
.\vendor\bin\phpunit --coverage-filter src/Ksfraser/FaBankImport/ValueObject --coverage-text
```

---

## Test Configuration

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    
    <testsuites>
        <testsuite name="Value Objects">
            <directory>tests/ValueObject</directory>
        </testsuite>
        <testsuite name="Entities">
            <directory>tests/Entity</directory>
        </testsuite>
        <testsuite name="Strategies">
            <directory>tests/Strategy</directory>
        </testsuite>
        <testsuite name="Services">
            <directory>tests/Service</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">src/Ksfraser/FaBankImport</directory>
        </include>
        <exclude>
            <directory>src/Ksfraser/FaBankImport/Tests</directory>
        </exclude>
    </coverage>
    
    <php>
        <ini name="memory_limit" value="256M"/>
        <ini name="error_reporting" value="E_ALL"/>
    </php>
</phpunit>
```

---

## Test Categories

### ✅ Completed Tests (92 tests total)

#### Value Objects (45 tests)
- **FileInfoTest** (20 tests)
  - Constructor validation (empty filename, size limits, etc.)
  - Factory method (fromUpload)
  - Utility methods (getExtension, getBasename, getMd5Hash)
  - Immutability verification
  - Upload error handling

- **DuplicateResultTest** (10 tests)
  - Factory methods (notDuplicate, allow, warn, block)
  - Query methods (shouldAllow, shouldWarn, shouldBlock)
  - Mutual exclusion of actions
  - Immutability verification

- **UploadResultTest** (15 tests)
  - Factory methods (success, error, duplicate, reused)
  - State checks (isSuccess, isDuplicate, isReused)
  - toArray() serialization
  - Success flag consistency

#### Entities (12 tests)
- **UploadedFileTest**
  - Constructor with all/minimal properties
  - Setters (setId, setStatementCount)
  - getFormattedSize() for different sizes
  - exists() method with real files
  - Entity identity (ID-based equality)

#### Strategies (15 tests)
- **DuplicateStrategyTest**
  - AllowDuplicateStrategy behavior
  - WarnDuplicateStrategy behavior
  - BlockDuplicateStrategy behavior
  - Factory pattern creation
  - Strategy validation

#### Services (20 tests)
- **FileStorageServiceTest**
  - Directory management
  - File storage with unique names
  - CRUD operations (create, read, delete)
  - File metadata (size, mtime)
  - Copy operations
  - Error handling

---

## Test Coverage Report

### Current Coverage (Completed Tests)

| Component | Files | Coverage | Tests |
|-----------|-------|----------|-------|
| ValueObject/FileInfo | 1 | 100% | 20 |
| ValueObject/DuplicateResult | 1 | 100% | 10 |
| ValueObject/UploadResult | 1 | 100% | 15 |
| Entity/UploadedFile | 1 | 95% | 12 |
| Strategy/* | 5 | 100% | 15 |
| Service/FileStorageService | 1 | 90% | 20 |
| **Total** | **10** | **95%** | **92** |

### Remaining Coverage (TODO)

| Component | Files | Tests Needed | Est. Time |
|-----------|-------|--------------|-----------|
| Service/DuplicateDetector | 1 | 15 | 1 hour |
| Repository/DatabaseUploadedFileRepository | 1 | 20 | 1.5 hours |
| Service/FileUploadService | 1 | 25 | 1.5 hours |
| Integration Tests | 1 | 10 | 1 hour |
| **Total** | **4** | **70** | **5 hours** |

---

## Test Principles

### 1. Unit Tests (Isolated)
- Test ONE class at a time
- Mock all dependencies
- Fast execution (<1ms per test)
- No database, no filesystem (except FileStorageServiceTest)

### 2. Integration Tests
- Test multiple classes working together
- Use real dependencies where appropriate
- Test full upload flow end-to-end

### 3. Test Structure (Arrange-Act-Assert)
```php
public function testSomething(): void
{
    // Arrange - Set up test data and mocks
    $fileInfo = new FileInfo('test.qfx', '/tmp/test', 1024, 'text/plain');
    
    // Act - Execute the method under test
    $result = $fileInfo->getExtension();
    
    // Assert - Verify the outcome
    $this->assertEquals('qfx', $result);
}
```

### 4. Test Naming Convention
- `test` prefix required by PHPUnit
- Descriptive name: `testConstructorRejectsEmptyFilename`
- One assertion per test (when possible)

---

## Mocking Examples

### Mock Repository

```php
$mockRepo = $this->createMock(UploadedFileRepositoryInterface::class);

$mockRepo->expects($this->once())
    ->method('save')
    ->with($this->isInstanceOf(UploadedFile::class))
    ->willReturn(123);

$service = new FileUploadService($mockRepo, ...);
```

### Mock Config

```php
$mockConfig = $this->createMock(ConfigRepositoryInterface::class);

$mockConfig->method('get')
    ->willReturnMap([
        ['check_duplicates', true, true],
        ['duplicate_action', 'warn', 'warn'],
        ['duplicate_window_days', 90, 90]
    ]);
```

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: mbstring, xml
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run tests
      run: vendor/bin/phpunit --coverage-text
    
    - name: Upload coverage
      uses: codecov/codecov-action@v2
```

---

## Troubleshooting

### Issue: "Class not found"

**Solution**: Ensure autoloader is configured
```bash
composer dump-autoload
```

### Issue: "Call to undefined function"

**Solution**: Mock FrontAccounting functions
```php
// In test setUp()
if (!function_exists('db_query')) {
    function db_query($sql, $err_msg = '') {
        return true;
    }
}
```

### Issue: Tests failing on Windows paths

**Solution**: Use DIRECTORY_SEPARATOR constant
```php
$path = $dir . DIRECTORY_SEPARATOR . 'file.txt';
```

### Issue: Coverage report shows 0%

**Solution**: Install Xdebug
```bash
# Check if Xdebug is installed
php -m | grep xdebug

# If not, install via PECL
pecl install xdebug
```

---

## Best Practices

### ✅ DO
- Test public methods only
- Use descriptive test names
- Keep tests small and focused
- Mock external dependencies
- Test edge cases and errors
- Use setUp/tearDown for common setup

### ❌ DON'T
- Test private methods directly
- Test framework code (PHPUnit itself)
- Make tests depend on each other
- Use sleep() or time-based assertions
- Test implementation details
- Ignore failing tests

---

## Next Steps

1. ✅ **Value Objects** - Complete (45 tests)
2. ✅ **Entities** - Complete (12 tests)
3. ✅ **Strategies** - Complete (15 tests)
4. ✅ **File Storage** - Complete (20 tests)
5. ⏳ **Duplicate Detector** - TODO (15 tests, 1 hour)
6. ⏳ **Repository** - TODO (20 tests, 1.5 hours)
7. ⏳ **Upload Service** - TODO (25 tests, 1.5 hours)
8. ⏳ **Integration** - TODO (10 tests, 1 hour)

**Total Remaining**: ~5 hours to 100% coverage

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPUnit Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)
- [Mocking with PHPUnit](https://phpunit.de/manual/current/en/test-doubles.html)
