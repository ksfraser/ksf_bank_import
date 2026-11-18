---
title: "ADR-0001: Composable Data Validation Rules Architecture"
status: "Proposed"
date: "2026-04-04"
authors: "KS Fraser, AI Agent"
tags: ["architecture", "validation", "data-integrity"]
supersedes: ""
superseded_by: ""
---

# ADR-0001: Composable Data Validation Rules Architecture

## Status

**Proposed** | Accepted | Rejected | Superseded | Deprecated

## Context

The bank import system requires multi-layer data validation:

1. **Parse-time**: After parsing raw files → detect corrupted/malformed statements
2. **Import-time**: Before persisting to database → catch transform errors  
3. **Audit-time**: On archived data → detect post-import corruption/unauthorized changes
4. **Recovery-time**: When re-importing files → validate against original archives

Current implementation has monolithic `StatementValidator` (7 rules in single class). Future requires:
- Different rules for different data layers (ParsedStatement vs. BiStatement vs. Transaction)
- Selective rule inclusion/exclusion (audit vs. strict vs. relaxed profiles)
- Reusable rule patterns across validators
- Rule composition without code changes (configuration-driven)

Business requires traceability: "Which rules were checked? Which ran? What failed?" for audit compliance.

## Decision

Implement **Composable Rule Architecture** with:

1. **RuleInterface Pattern**: Each validation rule = single SRP class
   - `Rule` (base interface)
   - `ParsedStatementRule`, `BiStatementRule`, `TransactionRule` (layer-specific)
   - Each rule independently testable and deployable

2. **RuleRegistry**: Centralized rule management
   - Rule inclusion/exclusion by name
   - Profile-based selection (strict/audit/relaxed presets)
   - Context-aware filtering (which rule applies to which data)

3. **ValidationResult Enhancement**: Granular tracking
   - Track which rules were checked + results
   - Distinguish Error vs. Warning vs. InfoFlag (audit-only)
   - Support both field-level and general validation errors

4. **Two-Phase Rollout**:
   - **Phase 2.2.2** (Current): Complete transformers with stateless logic
   - **Phase 2.2.3** (Future): Refactor validators to extract RuleInterface patterns

## Consequences

### Positive

- **POS-001**: Single Responsibility: Each rule = one reason to change
- **POS-002**: Testability: Rules tested in isolation with minimal dependencies
- **POS-003**: Reusability: Rule logic shared across ParsedStatement + BiStatement validators
- **POS-004**: Flexibility: Enable/disable rules without code changes (config-driven)
- **POS-005**: Auditability: Complete rule execution history for compliance
- **POS-006**: Extensibility: Add new rules without modifying existing validators
- **POS-007**: Performance: Skip expensive rules in non-critical contexts (relaxed mode)

### Negative

- **NEG-001**: Increased code volume: One class per rule + registry overhead
- **NEG-002**: Abstraction complexity: Learning curve for new developers
- **NEG-003**: Configuration overhead: Must manage rule profiles and contexts
- **NEG-004**: Testing matrix expansion: More test files (one per rule class)

## Alternatives Considered

### Alternative 1: Keep Monolithic Validator

- **ALT-001**: **Description**: Single `DataIntegrityValidator` with all rules + configuration toggles
- **ALT-002**: **Rejection Reason**: Violates SRP; difficult to test; rule reuse requires copy-paste

### Alternative 2: Strategy Pattern (Single Strategy per Context)

- **ALT-003**: **Description**: `ParsedStatementStrategy`, `BiStatementStrategy`, etc. each with all rules
- **ALT-004**: **Rejection Reason**: Still couples unrelated rules; doesn't enable granular rule selection

### Alternative 3: Decorator Pattern (Wrap validator with rules)

- **ALT-005**: **Description**: Base validator + decorators for each rule
- **ALT-006**: **Rejection Reason**: Over-engineering for simple composition; adds decorator nesting overhead

## Implementation Notes

### Phase Sequencing

**Phase 2.2.2**: Transformers (INDEPENDENT - no validator changes)
- BiStatementTransformer (280 lines)
- BiTransactionTransformer (320 lines)
- NormalizationRules (180 lines)
- EnrichmentService (220 lines, optional metadata injection)
- QualityScorer (180 lines, 0-100 point scoring)
- TransformerFactory (150 lines, MIME type routing)
- All tests: 36+ test methods
- **Deliverable**: Complete StatementDTO → BiStatement transformation pipeline

**Phase 2.2.3**: Data Integrity Refactoring (FORWARD-COMPATIBLE)
- Extract `RuleInterface` base from `StatementValidator`
- Create `ParsedStatementRule` implementations (reuse existing 7 rules)
- Create `RuleRegistry` for dynamic rule selection
- Create `BiStatementIntegrityValidator` (database-layer validation)
- Create `TransactionIntegrityValidator` (per-transaction validation)
- All tests: 40+ test methods
- **Deliverable**: Composable validation framework

- **IMP-001**: No changes to Phase 2.2.2.x transformers
- **IMP-002**: ValidationResult already enhanced (supports new API for Phase 2.2.3)
- **IMP-003**: Archive validator can be added in Phase 2.2.4 (separate concern)

### Rule Registry Configuration Example

```php
// Audit profile (strict, all checks)
$config = RuleConfig::preset('audit')
    ->include('DateRange', 'Amount', 'Merchant', 'TransactionCount', 'AccountReference', 'Currency')
    ->excludeWarnings(false);

// Relaxed profile (skip non-essential checks, speed)
$config = RuleConfig::preset('relaxed')
    ->include('DateRange', 'TransactionCount')  // Only critical
    ->excludeWarnings(true)
    ->exclude('DuplicateDetection');

// Custom (explicit control)
$config = RuleConfig::custom()
    ->add(new DateRangeRule(maxDays: 365))
    ->add(new AmountValidationRule())
    ->skip('DuplicateDetection');  // Don't serialize rules we're not checking
```

### Success Criteria

- **SEC-001**: Phase 2.2.2 tests: 85%+ coverage, all passing
- **SEC-002**: Phase 2.2.2 zero Phase 2.1 regressions (still 36/36)
- **SEC-003**: Phase 2.2.3 refactoring: 90%+ coverage on rules
- **SEC-004**: RuleRegistry: Support ≥10 rules per context
- **SEC-005**: Audit trail: Every rule execution logged with timestamp + result

## References

- **REF-001**: [StatementValidator](../../src/Ksfraser/FaBankImport/Import/Validators/StatementValidator.php) - Current Phase 2.2.2.1 implementation
- **REF-002**: [ValidationResult](../../src/Ksfraser/FaBankImport/Import/Results/ValidationResult.php) - Enhanced DTO supporting new API
- **REF-003**: [STATEMENT_VALIDATOR_RULES.md](../STATEMENT_VALIDATOR_RULES.md) - Business rules documentation
- **REF-004**: [Phase 2.2.2 Implementation Plan](../../plan/feature-phase-2-2-2-validators-transformers-1.0.md) - Current task breakdown
- **REF-005**: Related: Phase 2.2.4 (Archive validator, historical tracking) - Future phase
