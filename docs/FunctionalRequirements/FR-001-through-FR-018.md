# Requirements Specification
## KSF Bank Import - Paired Transfer Processing

**Document ID:** REQ-001  
**Version:** 1.0  
**Date:** January 18, 2025  
**Status:** APPROVED  
**Author:** Kevin Fraser  

---

## Document Control

| Version | Date | Author | Changes | Approver |
|---------|------|--------|---------|----------|
| 0.1 | 2025-01-10 | Kevin Fraser | Initial draft | - |
| 0.5 | 2025-01-15 | Kevin Fraser | Stakeholder review | Accounting Mgr |
| 1.0 | 2025-01-18 | Kevin Fraser | Final approval | Business Owner |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Functional Requirements](#2-functional-requirements)
3. [Non-Functional Requirements](#3-non-functional-requirements)
4. [Interface Requirements](#4-interface-requirements)
5. [Data Requirements](#5-data-requirements)
6. [Business Rules](#6-business-rules)
7. [Requirements Attributes](#7-requirements-attributes)

---

## 1. Introduction

### 1.1 Purpose

This document specifies the detailed functional and non-functional requirements for the Paired Transfer Processing enhancement to the KSF Bank Import system.

### 1.2 Scope

This specification covers requirements for:
- Automatic transaction matching
- Transfer direction analysis
- Bank transfer creation
- User interface enhancements
- Performance optimization
- System integration

### 1.3 Requirement Prioritization

**MoSCoW Method:**
- **MUST** - Critical for system to function (MVP)
- **SHOULD** - Important but not critical
- **COULD** - Desirable if resources allow
- **WON'T** - Not in current scope (future phase)

---

## 2. Functional Requirements

### 2.1 Transaction Matching

#### FR-001: Automatic Pair Detection
**Priority:** MUST  
**Category:** Core Processing  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL automatically identify potential paired transfer transactions based on amount, date, and account criteria.

**Acceptance Criteria:**
- AC-001.1: System identifies pairs with matching amounts (±$0.01)
- AC-001.2: System checks dates within ±2 days
- AC-001.3: System verifies opposite DC indicators (D vs C)
- AC-001.4: System confirms different accounts
- AC-001.5: System displays match confidence indicator

**Business Value:** High - Eliminates manual matching effort  
**Test Cases:** TC-001-A through TC-001-E  
**Related Requirements:** FR-002, FR-003  

---

#### FR-002: Matching Window Configuration
**Priority:** SHOULD  
**Category:** Core Processing  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL use a configurable matching window of ±2 days for identifying paired transactions.

**Acceptance Criteria:**
- AC-002.1: Default window is ±2 days
- AC-002.2: Window is configurable in code constant
- AC-002.3: System searches both directions (before and after)
- AC-002.4: Date comparison uses transaction date, not import date

**Business Value:** Medium - Flexibility for different bank processing times  
**Test Cases:** TC-002-A through TC-002-D  
**Related Requirements:** FR-001, FR-009  

---

#### FR-003: Amount Matching Tolerance
**Priority:** MUST  
**Category:** Core Processing  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL consider amounts equal if they match within $0.01 tolerance.

**Acceptance Criteria:**
- AC-003.1: Amounts compared as absolute values
- AC-003.2: Tolerance is exactly $0.01
- AC-003.3: System handles floating-point precision correctly
- AC-003.4: Rounding errors do not prevent matching

**Business Value:** High - Handles banking system rounding differences  
**Test Cases:** TC-003-A through TC-003-D  
**Related Requirements:** FR-001, BR-001  

---

### 2.2 Direction Analysis

#### FR-004: DC Indicator-Based Direction
**Priority:** MUST  
**Category:** Business Logic  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL determine transfer direction using DC (Debit/Credit) indicators from bank statements.

**Acceptance Criteria:**
- AC-004.1: Debit (D) indicates money leaving account (FROM)
- AC-004.2: Credit (C) indicates money arriving (TO)
- AC-004.3: System validates both transactions have DC indicators
- AC-004.4: System throws exception if DC missing or invalid

**Business Value:** Critical - Ensures correct transfer direction  
**Test Cases:** TC-004-A through TC-004-D  
**Related Requirements:** FR-005, BR-002  

---

#### FR-005: Transfer Data Construction
**Priority:** MUST  
**Category:** Business Logic  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL construct transfer data with correct FROM/TO accounts, amount, date, and memo.

**Acceptance Criteria:**
- AC-005.1: FROM account determined by DC=D transaction
- AC-005.2: TO account determined by DC=C transaction
- AC-005.3: Amount is positive (absolute value)
- AC-005.4: Date uses earlier of two transaction dates
- AC-005.5: Memo includes both transaction titles

**Business Value:** Critical - Accurate transfer records  
**Test Cases:** TC-005-A through TC-005-E  
**Related Requirements:** FR-004, FR-006  

---

### 2.3 Bank Transfer Creation

#### FR-006: FrontAccounting Integration
**Priority:** MUST  
**Category:** Integration  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL create bank transfers in FrontAccounting using the standard add_bank_transfer() API.

**Acceptance Criteria:**
- AC-006.1: System calls add_bank_transfer() with correct parameters
- AC-006.2: Transfer ID returned and stored
- AC-006.3: Both transactions linked to transfer ID
- AC-006.4: Transaction status updated to "processed"
- AC-006.5: Failure triggers rollback of transaction updates

**Business Value:** Critical - Core integration requirement  
**Test Cases:** TC-006-A through TC-006-E  
**Related Requirements:** FR-007, FR-008  

---

#### FR-007: Transfer Validation
**Priority:** MUST  
**Category:** Validation  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL validate all transfer data before creating FrontAccounting transfers.

**Acceptance Criteria:**
- AC-007.1: FROM and TO accounts must exist
- AC-007.2: FROM and TO accounts must be different
- AC-007.3: Amount must be positive and non-zero
- AC-007.4: Date must be valid format
- AC-007.5: Memo must not be empty
- AC-007.6: Clear error messages for each validation failure

**Business Value:** High - Prevents data integrity issues  
**Test Cases:** TC-007-A through TC-007-F  
**Related Requirements:** FR-006, NFR-008  

---

#### FR-008: Transaction Status Management
**Priority:** MUST  
**Category:** Data Management  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL update transaction status and linkage after successful transfer creation.

**Acceptance Criteria:**
- AC-008.1: Both transactions marked as "processed"
- AC-008.2: Both transactions linked to transfer ID
- AC-008.3: Updates occur in database transaction
- AC-008.4: Rollback on any failure
- AC-008.5: Audit log records all status changes

**Business Value:** Critical - Data integrity and audit trail  
**Test Cases:** TC-008-A through TC-008-E  
**Related Requirements:** FR-006, NFR-009  

---

### 2.4 User Interface

#### FR-009: Visual Transaction Indicators
**Priority:** MUST  
**Category:** User Interface  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL display visual indicators to distinguish debit and credit transactions.

**Acceptance Criteria:**
- AC-009.1: Debit transactions displayed in red/negative
- AC-009.2: Credit transactions displayed in green/positive
- AC-009.3: DC indicator column shows D or C
- AC-009.4: Processed transactions show checkmark (✓)
- AC-009.5: Unprocessed transactions show circle (○)
- AC-009.6: Color scheme is accessible (WCAG 2.1 AA)

**Business Value:** High - Improves usability and reduces errors  
**Test Cases:** TC-009-A through TC-009-F  
**Related Requirements:** FR-010, NFR-012  

---

#### FR-010: Operation Type Selection
**Priority:** MUST  
**Category:** User Interface  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL provide dropdown selection for operation types including "Process Both Sides" for paired transfers.

**Acceptance Criteria:**
- AC-010.1: Dropdown includes all operation types (BT, SP, CU, QE, MA, ZZ)
- AC-010.2: "Process Both Sides" (BT) clearly labeled
- AC-010.3: Default selection is appropriate for transaction type
- AC-010.4: Selection persists during session
- AC-010.5: Invalid selections prevented via validation

**Business Value:** Medium - User control and flexibility  
**Test Cases:** TC-010-A through TC-010-E  
**Related Requirements:** FR-009, FR-017  

---

#### FR-011: Process Action Button
**Priority:** MUST  
**Category:** User Interface  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL provide a "Process" button to initiate transfer creation for selected transaction pairs.

**Acceptance Criteria:**
- AC-011.1: Button enabled only for valid selections
- AC-011.2: Button triggers validation before processing
- AC-011.3: Loading indicator shown during processing
- AC-011.4: Success message displayed on completion
- AC-011.5: Error message displayed on failure with details

**Business Value:** High - Core user interaction  
**Test Cases:** TC-011-A through TC-011-E  
**Related Requirements:** FR-010, NFR-011  

---

### 2.5 Performance Optimization

#### FR-012: Vendor List Caching
**Priority:** SHOULD  
**Category:** Performance  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL cache the vendor list in PHP session to improve performance.

**Acceptance Criteria:**
- AC-012.1: Vendor list loaded from database once per session
- AC-012.2: Cache stored in $_SESSION
- AC-012.3: Cache duration configurable (default 1 hour)
- AC-012.4: Cache invalidation on manual trigger
- AC-012.5: Graceful fallback if session unavailable

**Business Value:** High - 95% performance improvement measured  
**Test Cases:** TC-012-A through TC-012-E  
**Related Requirements:** FR-013, NFR-002  

---

#### FR-013: Operation Types Registry
**Priority:** SHOULD  
**Category:** Performance  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL implement a singleton registry for operation types with session caching.

**Acceptance Criteria:**
- AC-013.1: Single instance per request (singleton pattern)
- AC-013.2: Types cached in session
- AC-013.3: Plugin architecture for extensibility
- AC-013.4: Auto-discovery of custom types
- AC-013.5: Default types included (SP, CU, QE, BT, MA, ZZ)

**Business Value:** Medium - Extensibility and performance  
**Test Cases:** TC-013-A through TC-013-E  
**Related Requirements:** FR-012, FR-010  

---

### 2.6 Service Architecture

#### FR-014: Service-Oriented Design
**Priority:** MUST  
**Category:** Architecture  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL implement service-oriented architecture following SOLID principles.

**Acceptance Criteria:**
- AC-014.1: PairedTransferProcessor orchestrates workflow
- AC-014.2: TransferDirectionAnalyzer handles business logic
- AC-014.3: BankTransferFactory manages FA integration
- AC-014.4: TransactionUpdater handles database operations
- AC-014.5: Each service has single responsibility
- AC-014.6: Services loosely coupled via interfaces

**Business Value:** High - Maintainability and testability  
**Test Cases:** TC-014-A through TC-014-F  
**Related Requirements:** NFR-013, NFR-014  

---

#### FR-015: Dependency Injection
**Priority:** SHOULD  
**Category:** Architecture  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL use dependency injection for service initialization.

**Acceptance Criteria:**
- AC-015.1: Services receive dependencies via constructor
- AC-015.2: No hard-coded dependencies within services
- AC-015.3: Easy to mock dependencies for testing
- AC-015.4: Clear dependency chain documented

**Business Value:** Medium - Testability and flexibility  
**Test Cases:** TC-015-A through TC-015-D  
**Related Requirements:** FR-014, NFR-013  

---

### 2.7 Error Handling

#### FR-016: Validation Error Handling
**Priority:** MUST  
**Category:** Error Management  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL provide clear error messages for all validation failures.

**Acceptance Criteria:**
- AC-016.1: Each validation error has specific message
- AC-016.2: Messages indicate what's wrong and how to fix
- AC-016.3: Technical details logged but not shown to users
- AC-016.4: Multiple errors displayed together when applicable
- AC-016.5: Error messages follow consistent format

**Business Value:** High - User experience and support reduction  
**Test Cases:** TC-016-A through TC-016-E  
**Related Requirements:** FR-007, NFR-011  

---

#### FR-017: Exception Management
**Priority:** MUST  
**Category:** Error Management  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL use exceptions for error handling with proper try-catch blocks.

**Acceptance Criteria:**
- AC-017.1: InvalidArgumentException for validation errors
- AC-017.2: RuntimeException for processing errors
- AC-017.3: All exceptions logged with context
- AC-017.4: User-friendly messages derived from exceptions
- AC-017.5: Stack traces captured in logs (not shown to users)

**Business Value:** Medium - Debugging and error tracking  
**Test Cases:** TC-017-A through TC-017-E  
**Related Requirements:** FR-016, NFR-009  

---

### 2.8 Audit and Logging

#### FR-018: Transaction Audit Trail
**Priority:** MUST  
**Category:** Compliance  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL maintain complete audit trail of all automated actions.

**Acceptance Criteria:**
- AC-018.1: Log all transaction status changes
- AC-018.2: Log all transfer creations with details
- AC-018.3: Log user actions (who, what, when)
- AC-018.4: Logs include before/after states
- AC-018.5: Logs immutable and timestamped

**Business Value:** Critical - Compliance and troubleshooting  
**Test Cases:** TC-018-A through TC-018-E  
**Related Requirements:** NFR-009, NFR-010  

---

## 3. Non-Functional Requirements

### 3.1 Performance Requirements

#### NFR-001: Response Time
**Priority:** MUST  
**Category:** Performance  
**Status:** VERIFIED  

**Description:**  
The system SHALL meet specified response time targets for all operations.

**Acceptance Criteria:**
- AC-001.1: Page load time <3 seconds
- AC-001.2: Transaction matching <5 seconds
- AC-001.3: Transfer creation <2 seconds
- AC-001.4: Cache retrieval <100ms
- AC-001.5: 95th percentile within targets

**Measurement Method:** Performance monitoring tools  
**Test Cases:** PT-001-A through PT-001-E  

---

#### NFR-002: Throughput
**Priority:** SHOULD  
**Category:** Performance  
**Status:** VERIFIED  

**Description:**  
The system SHALL support concurrent processing of multiple transactions.

**Acceptance Criteria:**
- AC-002.1: Handle 50 concurrent users
- AC-002.2: Process 100 transactions per minute
- AC-002.3: No performance degradation under load
- AC-002.4: Graceful handling of peak loads

**Measurement Method:** Load testing  
**Test Cases:** PT-002-A through PT-002-D  

---

#### NFR-003: Scalability
**Priority:** SHOULD  
**Category:** Performance  
**Status:** DESIGN VERIFIED  

**Description:**  
The system SHALL support 5x current transaction volume without architectural changes.

**Acceptance Criteria:**
- AC-003.1: Handle 500 transactions per day (currently 100)
- AC-003.2: Support database growth to 1M transactions
- AC-003.3: Cache scales with increased data
- AC-003.4: Query performance remains constant

**Measurement Method:** Scalability testing  
**Test Cases:** PT-003-A through PT-003-D  

---

### 3.2 Reliability Requirements

#### NFR-004: Availability
**Priority:** MUST  
**Category:** Reliability  
**Status:** OPERATIONAL  

**Description:**  
The system SHALL maintain 99.5% availability during business hours (8am-6pm EST).

**Acceptance Criteria:**
- AC-004.1: Maximum 30 minutes downtime per month
- AC-004.2: Graceful degradation if dependencies fail
- AC-004.3: Auto-recovery from transient failures
- AC-004.4: Health check endpoint available

**Measurement Method:** Uptime monitoring  
**Test Cases:** RT-004-A through RT-004-D  

---

#### NFR-005: Fault Tolerance
**Priority:** SHOULD  
**Category:** Reliability  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL handle failures gracefully without data corruption.

**Acceptance Criteria:**
- AC-005.1: Database transaction rollback on errors
- AC-005.2: Session cache failure doesn't crash system
- AC-005.3: FA API failures handled gracefully
- AC-005.4: Partial results not committed

**Measurement Method:** Fault injection testing  
**Test Cases:** RT-005-A through RT-005-D  

---

#### NFR-006: Data Integrity
**Priority:** MUST  
**Category:** Reliability  
**Status:** VERIFIED  

**Description:**  
The system SHALL maintain data integrity at all times.

**Acceptance Criteria:**
- AC-006.1: No duplicate transfers created
- AC-006.2: Transaction status always consistent
- AC-006.3: Audit log complete and accurate
- AC-006.4: ACID properties maintained

**Measurement Method:** Data integrity tests  
**Test Cases:** RT-006-A through RT-006-D  

---

### 3.3 Usability Requirements

#### NFR-007: Learnability
**Priority:** SHOULD  
**Category:** Usability  
**Status:** UAT PENDING  

**Description:**  
The system SHALL be usable by finance staff with minimal training.

**Acceptance Criteria:**
- AC-007.1: New users productive within 5 minutes
- AC-007.2: 90% task completion without help
- AC-007.3: Intuitive interface following FA patterns
- AC-007.4: Context-sensitive help available

**Measurement Method:** Usability testing  
**Test Cases:** UT-007-A through UT-007-D  

---

#### NFR-008: Error Prevention
**Priority:** MUST  
**Category:** Usability  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL prevent user errors through validation and confirmation.

**Acceptance Criteria:**
- AC-008.1: Invalid actions disabled (greyed out)
- AC-008.2: Warnings before destructive actions
- AC-008.3: Undo capability for reversible actions
- AC-008.4: Clear feedback for all actions

**Measurement Method:** Usability testing  
**Test Cases:** UT-008-A through UT-008-D  

---

### 3.4 Security Requirements

#### NFR-009: Data Security
**Priority:** MUST  
**Category:** Security  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL protect sensitive financial data.

**Acceptance Criteria:**
- AC-009.1: Authentication via FA security model
- AC-009.2: Authorization checks before actions
- AC-009.3: SQL injection prevention (parameterized queries)
- AC-009.4: XSS prevention (output escaping)
- AC-009.5: Sensitive data not logged in plain text

**Measurement Method:** Security testing  
**Test Cases:** ST-009-A through ST-009-E  

---

#### NFR-010: Audit Security
**Priority:** MUST  
**Category:** Security  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL maintain secure and immutable audit logs.

**Acceptance Criteria:**
- AC-010.1: Logs cannot be modified by users
- AC-010.2: Log access restricted to administrators
- AC-010.3: Log retention per compliance requirements
- AC-010.4: Log tampering detected

**Measurement Method:** Security audit  
**Test Cases:** ST-010-A through ST-010-D  

---

### 3.5 Compatibility Requirements

#### NFR-011: PHP Compatibility
**Priority:** MUST  
**Category:** Compatibility  
**Status:** VERIFIED  

**Description:**  
The system SHALL be fully compatible with PHP 7.4+.

**Acceptance Criteria:**
- AC-011.1: No PHP 8.0+ exclusive features used
- AC-011.2: All code runs on PHP 7.4
- AC-011.3: Proper type hints maintained
- AC-011.4: PHPDoc annotations for IDE support

**Measurement Method:** Testing on PHP 7.4  
**Test Cases:** CT-011-A through CT-011-D  

---

#### NFR-012: FrontAccounting Compatibility
**Priority:** MUST  
**Category:** Compatibility  
**Status:** VERIFIED  

**Description:**  
The system SHALL be compatible with FrontAccounting 2.4+.

**Acceptance Criteria:**
- AC-012.1: Uses FA 2.4 API exclusively
- AC-012.2: No core FA files modified
- AC-012.3: Module follows FA extension guidelines
- AC-012.4: Backward compatible with existing data

**Measurement Method:** Integration testing  
**Test Cases:** CT-012-A through CT-012-D  

---

#### NFR-013: Browser Compatibility
**Priority:** SHOULD  
**Category:** Compatibility  
**Status:** VERIFIED  

**Description:**  
The system SHALL work in modern browsers.

**Acceptance Criteria:**
- AC-013.1: Chrome 90+
- AC-013.2: Firefox 88+
- AC-013.3: Safari 14+
- AC-013.4: Edge 90+
- AC-013.5: Responsive design (mobile-friendly)

**Measurement Method:** Cross-browser testing  
**Test Cases:** CT-013-A through CT-013-E  

---

### 3.6 Maintainability Requirements

#### NFR-014: Code Quality
**Priority:** MUST  
**Category:** Maintainability  
**Status:** VERIFIED  

**Description:**  
The system SHALL follow PSR coding standards.

**Acceptance Criteria:**
- AC-014.1: PSR-1: Basic Coding Standard
- AC-014.2: PSR-2: Coding Style Guide
- AC-014.3: PSR-4: Autoloading
- AC-014.4: PSR-5: PHPDoc Standard
- AC-014.5: PSR-12: Extended Coding Style

**Measurement Method:** Static code analysis  
**Test Cases:** MT-014-A through MT-014-E  

---

#### NFR-015: Documentation
**Priority:** MUST  
**Category:** Maintainability  
**Status:** COMPLETED  

**Description:**  
The system SHALL be comprehensively documented.

**Acceptance Criteria:**
- AC-015.1: All classes have PHPDoc headers
- AC-015.2: All public methods documented
- AC-015.3: Architecture documentation complete
- AC-015.4: User guide available
- AC-015.5: Deployment guide available
- AC-015.6: API documentation generated

**Measurement Method:** Documentation review  
**Test Cases:** MT-015-A through MT-015-F  

---

#### NFR-016: Testability
**Priority:** MUST  
**Category:** Maintainability  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL be designed for comprehensive testing.

**Acceptance Criteria:**
- AC-016.1: Unit tests for all business logic
- AC-016.2: Integration tests for FA integration
- AC-016.3: 80%+ code coverage for core services
- AC-016.4: Mockable dependencies
- AC-016.5: Test data fixtures available

**Measurement Method:** Code coverage analysis  
**Test Cases:** MT-016-A through MT-016-E  

---

## 4. Interface Requirements

### 4.1 User Interface Requirements

#### IR-001: Web Interface
**Priority:** MUST  
**Category:** UI  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL provide web-based user interface integrated with FrontAccounting.

**Acceptance Criteria:**
- AC-001.1: HTML/CSS interface following FA styles
- AC-001.2: Responsive layout
- AC-001.3: Accessible (WCAG 2.1 Level AA)
- AC-001.4: JavaScript for interactive elements

---

### 4.2 System Interface Requirements

#### IR-002: FrontAccounting API
**Priority:** MUST  
**Category:** Integration  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL integrate with FrontAccounting via standard API functions.

**Acceptance Criteria:**
- AC-002.1: add_bank_transfer() for transfers
- AC-002.2: Database functions via FA db layer
- AC-002.3: Session management via FA
- AC-002.4: Permission checks via FA security

---

#### IR-003: Database Interface
**Priority:** MUST  
**Category:** Integration  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL interact with MySQL database via FA database layer.

**Acceptance Criteria:**
- AC-003.1: No direct SQL queries (use FA functions)
- AC-003.2: Parameterized queries for security
- AC-003.3: Transaction support for data integrity
- AC-003.4: Connection pooling via FA

---

## 5. Data Requirements

### 5.1 Data Storage

#### DR-001: Transaction Data
**Priority:** MUST  
**Category:** Data  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL store transaction data in bi_transactions table.

**Fields:**
- id (INT, PK, AUTO_INCREMENT)
- account_id (INT, FK)
- transactionTitle (VARCHAR 255)
- transactionDC (CHAR 1: D or C)
- transactionAmount (DECIMAL 10,2)
- valueTimestamp (DATE)
- processed (BOOLEAN)
- linked_transfer_id (INT, FK to bank_transfers)

---

#### DR-002: Transfer Data
**Priority:** MUST  
**Category:** Data  
**Status:** FA MANAGED  

**Description:**  
The system SHALL utilize FrontAccounting's bank_transfers table.

**Fields:**
- id (INT, PK)
- from_account (INT, FK)
- to_account (INT, FK)
- amount (DECIMAL 10,2)
- trans_date (DATE)
- memo (TEXT)

---

### 5.2 Data Integrity

#### DR-003: Referential Integrity
**Priority:** MUST  
**Category:** Data  
**Status:** IMPLEMENTED  

**Description:**  
The system SHALL maintain referential integrity between transactions and transfers.

**Acceptance Criteria:**
- AC-003.1: Foreign key constraints enforced
- AC-003.2: Cascade rules defined
- AC-003.3: Orphan records prevented

---

## 6. Business Rules

### BR-001: Matching Criteria
**Rule ID:** BR-001  
**Priority:** MUST  
**Status:** IMPLEMENTED  

**Rule Statement:**  
Two transactions are considered a matched pair if and only if:
1. Absolute amounts match within $0.01
2. One has DC='D' and other has DC='C'
3. Dates are within ±2 days
4. Accounts are different
5. Neither transaction is already processed

---

### BR-002: Direction Logic
**Rule ID:** BR-002  
**Priority:** MUST  
**Status:** IMPLEMENTED  

**Rule Statement:**  
Transfer direction is determined as follows:
- Transaction with DC='D' → FROM account (money leaving)
- Transaction with DC='C' → TO account (money arriving)
- Amount is always positive (use absolute value)
- Date is the earlier of the two transaction dates

---

### BR-003: Processing Status
**Rule ID:** BR-003  
**Priority:** MUST  
**Status:** IMPLEMENTED  

**Rule Statement:**  
A transaction can only be processed once:
- processed=0 → Available for processing
- processed=1 → Cannot be reprocessed
- Rollback requires manual database update

---

### BR-004: Validation Sequence
**Rule ID:** BR-004  
**Priority:** MUST  
**Status:** IMPLEMENTED  

**Rule Statement:**  
Validation must occur in this order:
1. Check required fields present
2. Validate data types and formats
3. Verify business rules
4. Check FA account existence
5. Confirm transaction availability

---

## 7. Requirements Attributes

### 7.1 Requirement Template

Each requirement includes:

| Attribute | Description |
|-----------|-------------|
| **ID** | Unique identifier (FR-XXX, NFR-XXX, etc.) |
| **Priority** | MUST / SHOULD / COULD / WON'T |
| **Category** | Functional area |
| **Status** | IMPLEMENTED / IN PROGRESS / PENDING |
| **Description** | What the system shall do |
| **Acceptance Criteria** | How to verify requirement is met |
| **Business Value** | Why this requirement matters |
| **Test Cases** | Test case IDs |
| **Related Requirements** | Dependencies and relationships |

### 7.2 Requirements Summary

**Total Requirements:** 47

**By Priority:**
- MUST: 34 (72%)
- SHOULD: 11 (23%)
- COULD: 2 (5%)
- WON'T: 0 (0%)

**By Category:**
- Functional: 18 (38%)
- Non-Functional: 16 (34%)
- Interface: 3 (6%)
- Data: 3 (6%)
- Business Rules: 4 (9%)
- Architecture: 3 (6%)

**By Status:**
- IMPLEMENTED: 38 (81%)
- VERIFIED: 7 (15%)
- PENDING: 2 (4%)

---

## Approval

**Requirements Approved By:**

| Name | Role | Date | Signature |
|------|------|------|-----------|
| Kevin Fraser | Business Owner | 2025-01-18 | [Digital] |
| Accounting Manager | Process Owner | 2025-01-18 | [Digital] |
| IT Manager | Technical Authority | 2025-01-18 | [Digital] |

**Next Review Date:** April 18, 2025

---

**END OF REQUIREMENTS SPECIFICATION**
