# AGENTS.md - AI Agent Guidelines for ksf_bank_import

This document provides guidance for AI agents (including GitHub Copilot) working on the ksf_bank_import project. It documents best practices, architectural context, and token-efficient workflows.

## Token Efficiency Guidelines

### Problem
Large test suites and verbose output parsing can burn excessive tokens when agents repeatedly:
- Run tests multiple times attempting different parsing strategies
- Parse unstructured terminal output with limited success
- Re-execute commands on parse failures

### Solution: Structured Output Extraction

**Always run tests with structured output formats**, then parse locally with PHP:

```bash
# Generate XML results (machine-readable, reliable parsing)
php vendor/bin/phpunit phpunit.xml --log-junit=test-results.xml --no-coverage
```

**Parse results with dedicated PHP script** instead of terminal pattern matching:

```php
<?php
$xml = simplexml_load_file('test-results.xml');
echo json_encode([
    'tests' => (int)$xml['tests'],
    'passed' => (int)$xml['tests'] - (int)$xml['errors'] - (int)$xml['failures'],
    'errors' => (int)$xml['errors'],
    'failures' => (int)$xml['failures'],
    'skipped' => (int)$xml['skipped'],
], JSON_PRETTY_PRINT);
```

### Best Practices for Agents

1. **Run tests ONCE with structured output**
   - Use `--log-junit=test-results.xml` for XML output
   - NOT: Multiple test runs with different terminal parsing strategies

2. **Share results files, not re-execute**
   - When analysis needed: `Run: php scripts/parse-tests.php > test-summary.json`
   - Share the JSON output in chat
   - Ask focused questions about specific failures (e.g., "Why is PaymentHandlerTest failing?")
   - NOT: "Can you run the tests again to see the results?"

3. **Avoid large text output parsing**
   - Terminal output parsing is unreliable due to encoding issues (UTF-16 BOM, multi-line content)
   - Structured formats (XML, JSON) are machine-readable and reliable
   - NOT: `Select-String`, `grep`, `tail` patterns on test output

4. **Use subagents for complex exploration**
   - For codebase exploration: Use `Explore` subagent instead of chaining multiple reads
   - For architectural analysis: Use `architecture-blueprint-generator` skill
   - Reduces back-and-forth and token usage

## Test Workflow Architecture

### Current State
- **Framework**: PHPUnit 9.6.34
- **Bootstrap**: `tests/bootstrap.php` (FA function mocks, FAMock loading)
- **Configuration**: `phpunit.xml` with multiple test suites
- **Output**: XML results via `--log-junit` flag

### Test Suites
```
- Value Objects (tests/ValueObject)
- Entities (tests/Entity)
- Strategies (tests/Strategy)
- Services (tests/Service)
- Unit (Legacy) (tests/unit)
- Integration (Legacy) (tests/integration)
```

### Known Test Issues to Avoid Re-running

**Already Fixed** (3 commits ago):
- ✅ TransactionRepositoryTest.php - Syntax error with orphaned code (commit 83e4712)
- ✅ UploadFormHandler.php - Wrong DTO namespace import (commit 76966b1)
- ✅ View files - Namespace standardization (15+ files, commit b05264a)

When encountering test failures:
1. Check recent commits in `TEST_CLEANUP_SESSION_SUMMARY.md`
2. If already addressed, don't re-run—check if cache/build needs clearing
3. If new failure, run targeted subset (e.g., `tests/unit/Handlers`) before full suite

## Project Context for Agents

### Architecture
- **Phase 0**: Shared kernel implementation (ongoing)
- **Namespace Pattern**: `Ksfraser\FaBankImport\{Subnamespace}`
- **Handler Pattern**: Dependency-injected handlers replacing legacy procedural code
- **DTO Location**: All in `Shared\DTOs` namespace for consistency

### Recent Work
- Branch: `chore/phase-0-shared-kernel` (9 commits ahead of origin)
- Previous cleanup: 7 commits of namespace fixes, deprecated assertion cleanup
- Current focus: Test failure reduction and architectural stability

### Files NOT to Re-Run Tests On (Already Done)

Recent refactoring work documented in:
- `TEST_CLEANUP_SESSION_SUMMARY.md` - Session work summary
- `ARCHITECTURAL_DECISION_FILE_ORGANIZATION.md` - Structure decisions
- Git history: `git log --oneline | head -20` for recent changes

## Communication Pattern for Agents

### Inefficient Pattern (❌ Token Waste)
```
User: "Run tests and analyze results"
Agent: Runs tests → Large output → Try parsing → Fails → Run again → Try different approach → Run again
Result: 3-4 test executions, 50KB+ output attempts
```

### Efficient Pattern (✅ Token Smart)
```
User: "Run tests and analyze results"
Agent: Runs tests ONCE with XML → `php scripts/parse-tests.php` → Share JSON summary
Agent: "Tests summary: 2019 total, 1800 passed, 100 errors, 50 failures, 69 skipped"
User: "Why is ConfigurationIntegrationTest failing?"
Agent: Runs THAT specific test only, focused analysis
Result: 1-2 test executions, targeted analysis, <10KB output
```

## Scripts to Use

### Existing Scripts
- `scripts/parse-tests.php` - Parse XML results to JSON (create if missing)
- `phpunit.xml` - Full test configuration (all suites)

### Useful VS Code Tasks
- `shell: phpunit-full-now` - Full suite, no coverage
- `shell: phpunit-root` - Root tests only
- `shell: phpunit-bi` - BiLineItem tests (legacy focus)

## When to NOT Run Tests

- Analyzing code architecture → Use code search and semantic_search
- Checking for syntax errors → Use `php -l filename.php`
- Understanding test structure → Read test files directly
- Debugging specific logic → Run targeted single test or use xdebug

Run full suite ONLY when:
1. Testing a complete feature implementation
2. Verifying no regressions after architectural changes
3. Establishing baseline after major refactoring

## References

- **Conversation Memory**: `/memories/session/` - Current task notes
- **Repository Memory**: `/memories/repo/` - Project-specific facts
- **Skill Files**: Use `create-implementation-plan`, `breakdown-plan` for major work
