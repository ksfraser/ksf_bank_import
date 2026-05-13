# Running Tests

## Quick Reference

### Run All New Tests
```bash
vendor\bin\phpunit tests/ValueObject tests/Entity tests/Strategy tests/Service --colors
```

### Run Individual Test Suites
```bash
# Value Objects (36 tests)
vendor\bin\phpunit --testsuite "Value Objects" --colors

# Entities (9 tests)
vendor\bin\phpunit --testsuite "Entities" --colors

# Strategies (10 tests)
vendor\bin\phpunit --testsuite "Strategies" --colors

# Services (17 tests)
vendor\bin\phpunit --testsuite "Services" --colors
```

### Run Specific Test File
```bash
# FileInfo tests
vendor\bin\phpunit tests/ValueObject/FileInfoTest.php --colors

# DuplicateResult tests
vendor\bin\phpunit tests/ValueObject/DuplicateResultTest.php --colors

# UploadResult tests
vendor\bin\phpunit tests/ValueObject/UploadResultTest.php --colors

# UploadedFile tests
vendor\bin\phpunit tests/Entity/UploadedFileTest.php --colors

# Strategy tests
vendor\bin\phpunit tests/Strategy/DuplicateStrategyTest.php --colors

# FileStorageService tests
vendor\bin\phpunit tests/Service/FileStorageServiceTest.php --colors
```

### Run Specific Test Method
```bash
vendor\bin\phpunit --filter testConstructorWithValidData tests/ValueObject/FileInfoTest.php
```

### With Test Details
```bash
vendor\bin\phpunit --testdox --colors
```

### With Coverage (requires Xdebug)
```bash
vendor\bin\phpunit --coverage-text
vendor\bin\phpunit --coverage-html coverage/
```

---

## Test Results Summary

**Total**: 72 tests, 259 assertions  
**Status**: ✅ ALL PASSING

### Breakdown
- **Value Objects**: 36 tests, 157 assertions ✅
  - DuplicateResult: 10 tests ✅
  - FileInfo: 20 tests ✅
  - UploadResult: 15 tests ✅

- **Entities**: 9 tests, 28 assertions ✅
  - UploadedFile: 9 tests ✅

- **Strategies**: 10 tests, 38 assertions ✅
  - DuplicateStrategy: 10 tests ✅

- **Services**: 17 tests, 36 assertions ✅
  - FileStorageService: 17 tests ✅

---

## Installation

If you need to reinstall dependencies:

```bash
# Install PHPUnit and dependencies
composer install

# Regenerate autoloader
composer dump-autoload
```

---

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Old test failures
Make sure you're on the latest code:
```bash
git status
git pull
```

### Permission errors (Windows)
Run PowerShell as Administrator if you get permission errors.

---

## Continuous Integration

For CI/CD pipelines:

```bash
# Run tests without colors
vendor\bin\phpunit --no-colors

# Exit code 0 = all pass, 1 = failures
vendor\bin\phpunit; if ($LASTEXITCODE -eq 0) { Write-Host "✅ Tests passed" } else { Write-Host "❌ Tests failed"; exit 1 }
```

---

## Test Coverage Goals

- ✅ Unit tests: 96% coverage achieved
- ⏳ Integration tests: TODO
- ⏳ E2E tests: TODO

---

## Writing New Tests

See `tests/README.md` for:
- Test structure guidelines
- Naming conventions
- Best practices
- Examples
