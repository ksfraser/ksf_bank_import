# Duplicate Transaction Detection & Review System
## Requirements Specification

**Document ID:** REQ-DUP-001  
**Version:** 1.0  
**Date:** 2026-03-27  
**Status:** IN DEVELOPMENT  
**Author:** Development Team

---

## Document Control

| Version | Date | Author | Status | Changes |
|---------|------|--------|--------|---------|
| 0.1 | 2026-02-15 | Dev Team | Design | Initial design phase documentation |
| 0.5 | 2026-03-10 | Dev Team | Implementation | Phase 1 detection logic implemented |
| 1.0 | 2026-03-27 | Dev Team | IN DEVELOPMENT | Phase 1 complete, Phase 2 design finalized |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Functional Requirements](#2-functional-requirements)
3. [Non-Functional Requirements](#3-non-functional-requirements)
4. [Data Requirements](#4-data-requirements)
5. [Business Rules](#5-business-rules)
6. [Implementation Phases](#6-implementation-phases)
7. [Requirements Status](#7-requirements-status)

---

## 1. Introduction

### 1.1 Purpose

This document specifies requirements for the Duplicate Transaction Detection and Review System—a multi-level detection and staged review workflow to prevent duplicate imports while accommodating legitimate transaction variations across different banking institutions.

### 1.2 Problem Statement

**Current Issues:**
- Banks may re-transmit transactions with altered transaction codes (RBC scenario)
- Simple duplicate detection fails when `transactionCode` changes → Same transaction imported twice
- Overly aggressive duplicate flagging prevents legitimate multi-purchase scenarios (SHOPPERS: multiple purchases same day/amount)
- Data corruption: orphaned GL entries, cross-contaminated line items when duplicates aren't handled atomically
- No user review/override capability for edge cases

### 1.3 Solution Overview

Three-level detection strategy with staged review workflow:
1. **Level 1 - Direct Code Match:** Fast path on exact `(transactionCode, accountId)` match
2. **Level 2 - Fuzzy Match:** When code changes, match on `(date, amount, merchant, memo)` + similarity scoring
3. **Level 3 - Whitelist Rules:** Configurable merchant patterns to allow known legitimate near-duplicates

Staged review system: Flagged duplicates held in `bi_transactions_dupe` table for user confirmation before import completion.

---

## 2. Functional Requirements

### 2.1 Phase 1: Duplicate Detection Logic (✅ COMPLETE)

#### FR-101: Direct Code Matching
**Priority:** MUST  
**Category:** Core Detection  
**Status:** ✅ IMPLEMENTED  
**Commit:** Phase 1 implementation

**Description:**  
The system SHALL detect exact duplicate transactions using the direct code match strategy: `(transactionCode, accountId)` comparison.

**Acceptance Criteria:**
- AC-101.1: Service class `DirectCodeMatcher` identifies exact `(code, acctid)` matches
- AC-101.2: Fast O(1) lookup using immutable cache structure
- AC-101.3: Returns `DuplicateCheckResult` with match score and confidence
- AC-101.4: Used as first/fastest detection level

**Test Cases:** Phase 1 test suite  
**Related Services:** `DirectCodeMatcher`, `DuplicateCheckResult`

#### FR-102: Fuzzy Matching on Code Changes
**Priority:** MUST  
**Category:** Core Detection  
**Status:** ✅ IMPLEMENTED  
**Commit:** Phase 1 implementation

**Description:**  
When direct code match fails, the system SHALL apply fuzzy matching on transaction attributes: date (±2 days), amount (±$0.01), merchant name similarity (fuzzy string match), and memo text.

**Acceptance Criteria:**
- AC-102.1: Service class `FuzzyMatcher` compares `(date, amount, merchant, memo)` attributes
- AC-102.2: Date window: ±2 business days (configurable constant)
- AC-102.3: Amount tolerance: ±$0.01 (configurable constant)
- AC-102.4: Merchant name uses Levenshtein distance → similarity score (threshold: 85%)
- AC-102.5: Returns confidence score 0-100 based on attribute matches
- AC-102.6: Only used if direct match failed

**Test Cases:** Phase 1 test suite (RBC re-download, SHOPPERS multi-purchase scenarios)  
**Related Services:** `FuzzyMatcher`, `DuplicateCheckResult`

#### FR-103: Whitelist Rules for Legitimate Variations
**Priority:** SHOULD  
**Category:** Core Detection  
**Status:** ✅ IMPLEMENTED  
**Commit:** Phase 1 implementation

**Description:**  
The system SHALL support user-configurable whitelist rules for known merchants/patterns that produce legitimate near-duplicates. Whitelisting only applies when fuzzy detection flags a potential duplicate—it does not suppress exact code matches.

**Acceptance Criteria:**
- AC-103.1: Service class `DuplicateRulesProvider` loads/caches whitelist rules from `bi_duplicate_rules` table
- AC-103.2: Rules support pattern matching: merchant name regex or exact match
- AC-103.3: Rules include: merchant name, description, allow_near_duplicates flag
- AC-103.4: Admin interface allows creating/managing rules (Phase 2)
- AC-103.5: Rules only override fuzzy detection, NOT direct code match (security)
- AC-103.6: Rules stored in database for persistence and versionability

**Test Cases:** Phase 1 test suite  
**Related Services:** `DuplicateRulesProvider`, `bi_duplicate_rules` table  
**Related Requirements:** FR-201 (Phase 2 Admin UI)

#### FR-104: Duplicate Detection Service Integration
**Priority:** MUST  
**Category:** Core Detection  
**Status:** ✅ IMPLEMENTED  
**Commit:** Phase 1 implementation

**Description:**  
The system SHALL provide a unified `DuplicateDetectionService` that orchestrates all three detection levels and returns a consolidated `DuplicateCheckResult`.

**Acceptance Criteria:**
- AC-104.1: Service class `DuplicateDetectionService` orchestrates Level 1→2→3 checks
- AC-104.2: Returns `DuplicateCheckResult` with: `isDuplicate` flag, `detectionLevel`, `confidence`, `matchedTransaction`
- AC-104.3: Called during import transaction processing pipeline
- AC-104.4: Supports batch operation for performance
- AC-104.5: Returns early on Level 1 match (optimization)

**Test Cases:** Phase 1 integration test suite  
**Related Services:** All detection services  
**Related Requirements:** FR-301 (import pipeline integration)

---

### 2.2 Phase 2: User Review & Staging System (🏗️ IN PROGRESS)

#### FR-201: Duplicate Review Staging Table
**Priority:** SHOULD  
**Category:** Review & Workflow  
**Status:** 🏗️ DESIGN COMPLETE  
**Implementation Phase:** Phase 2

**Description:**  
The system SHALL store flagged duplicates in a staging table (`bi_transactions_dupe`) for user review before final import decision.

**Acceptance Criteria:**
- AC-201.1: Table `bi_transactions_dupe` with fields: `id`, `source_txn_id`, `candidate_txn_id`, `detection_level`, `confidence`, `status`, `user_action`, `created_at`, `reviewed_at`, `reviewer_id`
- AC-201.2: Status values: `pending`, `confirmed_duplicate`, `confirmed_not_duplicate`, `whitelisted`
- AC-201.3: Default status on creation: `pending`
- AC-201.4: Final decision captured with `user_action` and `reviewer_id`
- AC-201.5: Audit trail: `created_at`, `reviewed_at` timestamps
- AC-201.6: SQL migration: `003_create_bi_duplicate_rules_table.sql`, `004_create_bi_transactions_dupe_table.sql`

**Test Cases:** Database schema validation, CRUD operations  
**Related Tables:** `bi_duplicate_rules`, `bi_transactions`

#### FR-202: User Review Workflow UI
**Priority:** SHOULD  
**Category:** Review & Workflow  
**Status:** 🏗️ DESIGN COMPLETE  
**Implementation Phase:** Phase 2

**Description:**  
The system SHALL provide a user interface for reviewing flagged duplicate transactions and making accept/reject decisions.

**Acceptance Criteria:**
- AC-202.1: Review page lists pending duplicates with side-by-side comparison
- AC-202.2: Display fields: transaction code, date, amount, merchant, memo for both candidate and source
- AC-202.3: Show detection level (1, 2, or 3) and confidence score
- AC-202.4: User actions: "Confirm Duplicate", "Not a Duplicate", "Create Whitelist Rule"
- AC-202.5: Bulk operations: review multiple at once, apply same decision to similar merchants
- AC-202.6: Linked to existing transaction views for context
- AC-202.7: Admin-only access (role-based authorization)

**Test Cases:** UI integration tests, user workflow tests  
**Related Requirements:** FR-104 (detection service provides data)

#### FR-203: Import Pipeline Integration with Review Holdback
**Priority:** SHOULD  
**Category:** Integration  
**Status:** 🏗️ DESIGN COMPLETE  
**Implementation Phase:** Phase 2

**Description:**  
When a duplicate is flagged, the system SHALL hold the transaction in the staging table for review before completing import.

**Acceptance Criteria:**
- AC-203.1: Import pipeline checks `DuplicateDetectionService` result
- AC-203.2: If `isDuplicate=true`, insert into `bi_transactions_dupe` table with `status=pending`
- AC-203.3: Transaction NOT posted to GL or bank reconciliation until review complete
- AC-203.4: User action: approve → complete import, reject → discard transaction
- AC-203.5: All changes wrapped in atomic transaction (prevent corruption)
- AC-203.6: Audit log captures all review decisions

**Test Cases:** Integration test with import pipeline  
**Related Requirements:** FR-401 (Data Corruption Prevention)

---

### 2.3 Phase 3: Data Corruption Prevention (📋 DESIGN COMPLETE)

#### FR-301: Atomic Transaction Wrapping
**Priority:** MUST  
**Category:** Data Integrity  
**Status:** 📋 SPECIFICATION COMPLETE  
**Implementation Phase:** Phase 3 (future)

**Description:**  
All duplicate handling operations SHALL be wrapped in database transactions to ensure atomicity: if any step fails, all changes are rolled back.

**Acceptance Criteria:**
- AC-301.1: Import transaction BEGIN/COMMIT/ROLLBACK wraps all GL posting
- AC-301.2: If duplicate review rejects transaction, all GL entries are removed atomically
- AC-301.3: If duplicate decision made, related transactions updated atomically
- AC-301.4: No orphaned GL entries possible
- AC-301.5: Cross-account contamination prevented by transaction isolation

**Test Cases:** Data corruption scenario tests  
**Related Requirements:** FR-203

#### FR-302: Orphaned GL Entry Prevention
**Priority:** MUST  
**Category:** Data Integrity  
**Status:** 📋 SPECIFICATION COMPLETE  
**Implementation Phase:** Phase 3 (future)

**Description:**  
The system SHALL prevent orphaned GL entries by maintaining referential integrity between transactions and their posted GL entries.

**Acceptance Criteria:**
- AC-302.1: GL entries reference source transaction ID
- AC-302.2: Delete cascade: if transaction deleted, GL entries removed automatically
- AC-302.3: Foreign key constraints enforced in database schema
- AC-302.4: Regular audit reports identify any orphaned entries

**Test Cases:** Cascade delete tests, orphaned entry detection  
**Related Requirements:** FR-301

---

## 3. Non-Functional Requirements

### NFR-301: Performance
**Priority:** SHOULD  
**Category:** Performance  

**Description:**  
Detection processing SHALL complete within acceptable time for high-volume imports.

**Acceptance Criteria:**
- NFR-301.1: Direct code match: < 1ms per transaction
- NFR-301.2: Fuzzy match: < 10ms per transaction
- NFR-301.3: Batch processing supports 1000+ transactions/min
- NFR-301.4: Whitelist cache loaded once per import session

### NFR-302: Auditability
**Priority:** MUST  
**Category:** Compliance  

**Description:**  
All duplicate detection and review decisions SHALL be auditable.

**Acceptance Criteria:**
- NFR-302.1: Detection method logged for each flagged duplicate
- NFR-302.2: User review decision captured with timestamp and user ID
- NFR-302.3: All changes have immutable audit trail
- NFR-302.4: Audit logs queryable by transaction, user, date range

### NFR-303: Configurability
**Priority:** SHOULD  
**Category:** Operational  

**Description:**  
Detection thresholds and rules SHALL be configurable without code changes.

**Acceptance Criteria:**
- NFR-303.1: Date window configurable in constants
- NFR-303.2: Amount tolerance configurable in constants
- NFR-303.3: Fuzzy match threshold (merchant similarity) configurable
- NFR-303.4: Whitelist rules managed via database

---

## 4. Data Requirements

### DR-401: Duplicate Rules Table Schema

```sql
CREATE TABLE bi_duplicate_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    merchant_pattern VARCHAR(255) NOT NULL COMMENT 'Regex or exact match',
    description VARCHAR(500),
    allow_near_duplicates BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    UNIQUE KEY uk_merchant_pattern (merchant_pattern)
);
```

**Migration:** `sql/migrations/003_create_bi_duplicate_rules_table.sql`

### DR-402: Duplicate Transactions Staging Table

```sql
CREATE TABLE bi_transactions_dupe (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_txn_id INT NOT NULL,
    candidate_txn_id INT NOT NULL,
    detection_level INT COMMENT '1 (code), 2 (fuzzy), 3 (rules)',
    confidence INT COMMENT 'Match confidence 0-100',
    status ENUM('pending', 'confirmed_duplicate', 'confirmed_not_duplicate', 'whitelisted') DEFAULT 'pending',
    user_action VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewer_id INT,
    notes TEXT,
    FOREIGN KEY (source_txn_id) REFERENCES bi_transactions(id),
    FOREIGN KEY (candidate_txn_id) REFERENCES bi_transactions(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

**Migration:** `sql/migrations/004_create_bi_transactions_dupe_table.sql`

---

## 5. Business Rules

### BR-501: Detection Level Precedence
- Level 1 (Direct Code Match) takes absolute precedence: if code+acctid match exactly, transaction is duplicate regardless of other attributes
- Level 2 (Fuzzy Match) only evaluated if Level 1 fails
- Level 3 (Whitelist Rules) only suppress Level 2 matches, never Level 1

### BR-502: Whitelist Override Semantics
- Whitelist rules allow legitimate near-duplicates to bypass fuzzy detection
- Rules apply to **merchant-specific patterns** only (e.g., "SHOPPERS" allows multiple purchases same day)
- Rules stored in database for auditability and user control

### BR-503: Import Atomicity
- All transaction posting (GL entries, bank reconciliation, GL account updates) wrapped in single database transaction
- If duplicate review rejects import, entire transaction rolled back atomically
- No partial updates possible

### BR-504: Review Workflow States
- **pending**: Flagged duplicate awaiting user review
- **confirmed_duplicate**: User confirmed it's a duplicate → don't import
- **confirmed_not_duplicate**: User confirmed it's legitimate → import normally
- **whitelisted**: Rule or manual action whitelisted this merchant pattern

---

## 6. Implementation Phases

### Phase 1: ✅ COMPLETE
**Deliverables:**
- `DirectCodeMatcher` service class
- `FuzzyMatcher` service class  
- `DuplicateRulesProvider` service class
- `DuplicateCheckResult` DTO
- `DuplicateDetectionService` orchestrator
- Unit & integration test suite
- Database schema for `bi_duplicate_rules` table

**Status:** Committed to `chore/process-statements-logic-parity` branch

### Phase 2: 🏗️ IN PROGRESS
**Deliverables:**
- `bi_transactions_dupe` staging table schema
- `DuplicatePairRowView` UI component
- `DuplicateReviewView` page/controller
- Review workflow integration with import pipeline
- Admin UI for managing whitelist rules

**Status:** Design complete, implementation ready to start

### Phase 3: 📋 FUTURE
**Deliverables:**
- Data corruption prevention: atomic transaction wrapping
- Orphaned GL entry detection & audit reports
- Enhanced audit logging

**Status:** Design/specification complete, implementation deferred

---

## 7. Requirements Status

| Requirement | Phase | Status | Notes |
|------------|-------|--------|-------|
| FR-101: Direct Code Match | 1 | ✅ Complete | Implemented & tested |
| FR-102: Fuzzy Matching | 1 | ✅ Complete | Implemented & tested |
| FR-103: Whitelist Rules | 1 | ✅ Complete | Implemented & tested |
| FR-104: Detection Service | 1 | ✅ Complete | Orchestrator complete |
| FR-201: Review Staging | 2 | 🏗️ In Progress | Schema ready, UI in progress |
| FR-202: Review UI | 2 | 🏗️ In Progress | Design complete, implementation in progress |
| FR-203: Pipeline Integration | 2 | 🏗️ In Progress | Architecture defined, implementation in progress |
| FR-301: Atomic Transactions | 3 | 📋 Design | Specification complete, deferred |
| FR-302: Orphan Prevention | 3 | 📋 Design | Specification complete, deferred |

---

## 8. Change History & Notes

**2026-03-27:** Requirements spec finalized after Phase 1 implementation complete. Phase 2 design finalized, ready for development sprint.

**Outstanding Questions:**
- Phase 2 timeline: How many story points/sprint cycles?
- Phase 3 timeline: Include in next release or defer to 2.0?
- Whitelist rule UI: Self-service by users or admin-only?
