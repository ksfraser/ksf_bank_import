# Use Case Specifications
## KSF Bank Import - Paired Transfer Processing

**Document ID:** UCS-001  
**Version:** 1.0  
**Date:** October 18, 2025  
**Status:** APPROVED  
**Author:** Kevin Fraser  

---

## Document Control

| Version | Date | Author | Changes | Approver |
|---------|------|--------|---------|----------|
| 0.1 | 2025-10-15 | Kevin Fraser | Initial draft | - |
| 1.0 | 2025-10-18 | Kevin Fraser | Final approval | Project Sponsor |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Actors](#2-actors)
3. [Use Case Overview](#3-use-case-overview)
4. [Use Case Diagrams](#4-use-case-diagrams)
5. [Detailed Use Case Specifications](#5-detailed-use-case-specifications)
6. [Use Case Relationships](#6-use-case-relationships)
7. [Business Rules](#7-business-rules)
8. [Traceability Matrix](#8-traceability-matrix)

---

## 1. Introduction

### 1.1 Purpose

This document provides detailed use case specifications for the KSF Bank Import Paired Transfer Processing system. It describes how actors interact with the system to accomplish their business goals.

### 1.2 Scope

This document covers all primary and alternative flows for:
- Processing paired bank transfers
- Managing transaction matching
- Handling exceptions and errors
- Viewing processing history

### 1.3 Document Conventions

**Use Case ID Format:** UC-XXX  
**Actor Notation:** [Actor Name]  
**System Notation:** <<System>>  
**Priority:** HIGH / MEDIUM / LOW  
**Status:** APPROVED / IN DEVELOPMENT / IMPLEMENTED  

---

## 2. Actors

### 2.1 Primary Actors

#### ACT-001: Finance Clerk
**Type:** Human  
**Description:** Staff member responsible for processing bank transactions and reconciling accounts  
**Responsibilities:**
- Import bank statements
- Process paired transfers
- Review and resolve exceptions
- Verify transaction accuracy

**Goals:**
- Process bank transfers efficiently
- Minimize manual data entry
- Ensure accurate accounting records
- Complete daily reconciliation tasks

**Technical Skill Level:** Intermediate  
**System Access:** Standard User  

---

#### ACT-002: Accounting Manager
**Type:** Human  
**Description:** Supervisor responsible for oversight and approval of financial transactions  
**Responsibilities:**
- Review processed transfers
- Approve exceptions
- Monitor system accuracy
- Ensure compliance with policies

**Goals:**
- Maintain financial control
- Ensure audit trail completeness
- Monitor team productivity
- Verify system reliability

**Technical Skill Level:** Intermediate  
**System Access:** Manager / Approver  

---

### 2.2 Secondary Actors

#### ACT-003: System Administrator
**Type:** Human  
**Description:** IT staff responsible for system configuration and maintenance  
**Responsibilities:**
- Configure system settings
- Manage user accounts
- Monitor system performance
- Troubleshoot technical issues

**Goals:**
- Ensure system availability
- Maintain data integrity
- Support end users
- Optimize performance

**Technical Skill Level:** Advanced  
**System Access:** Administrator  

---

### 2.3 External Systems (Actors)

#### ACT-101: FrontAccounting System
**Type:** External System  
**Description:** Accounting ERP system that receives bank transfer data  
**Interface:** Banking API, Direct Database Access  
**Responsibilities:**
- Store bank account information
- Accept bank transfer transactions
- Update account balances
- Maintain general ledger

---

#### ACT-102: Bank QFX File System
**Type:** External Data Source  
**Description:** Bank-provided transaction files in QFX/OFX format  
**Interface:** File Upload  
**Responsibilities:**
- Provide transaction data
- Include all required fields
- Follow QFX standard format

---

## 3. Use Case Overview

### 3.1 Use Case Summary

| Use Case ID | Name | Priority | Status | Actor |
|-------------|------|----------|--------|-------|
| UC-001 | Process Paired Transfer | HIGH | IMPLEMENTED | Finance Clerk |
| UC-002 | Match Transaction Pairs | HIGH | IMPLEMENTED | Finance Clerk |
| UC-003 | Handle Processing Exception | MEDIUM | IMPLEMENTED | Finance Clerk |
| UC-004 | Review Processing History | LOW | IMPLEMENTED | Finance Clerk |
| UC-005 | Bulk Process Multiple Pairs | MEDIUM | IMPLEMENTED | Finance Clerk |
| UC-006 | Manually Create Transfer | LOW | IMPLEMENTED | Finance Clerk |
| UC-007 | Approve Exception | MEDIUM | IMPLEMENTED | Accounting Mgr |
| UC-008 | View System Reports | LOW | IMPLEMENTED | Accounting Mgr |

---

## 4. Use Case Diagrams

### 4.1 High-Level System Context

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│              KSF Bank Import System                     │
│                                                         │
│  ┌─────────────────────────────────────────────────┐  │
│  │                                                 │  │
│  │     Process Paired Transfer (UC-001)            │  │
│  │              ↓                                  │  │
│  │     Match Transaction Pairs (UC-002)            │  │
│  │              ↓                                  │  │
│  │     Handle Processing Exception (UC-003)        │  │
│  │              ↓                                  │  │
│  │     Review Processing History (UC-004)          │  │
│  │                                                 │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
        ↑                                   ↓
        │                                   │
   Finance Clerk                    FrontAccounting
```

### 4.2 Primary Use Case Diagram

```
                    ┌─────────────────┐
                    │  Finance Clerk  │
                    │   (ACT-001)     │
                    └────────┬────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
          ┌─────────▼──────────┐  ┌──▼────────────────┐
          │  UC-001:           │  │  UC-002:          │
          │  Process Paired    │  │  Match            │
          │  Transfer          │  │  Transaction      │
          └─────────┬──────────┘  │  Pairs            │
                    │             └──┬────────────────┘
                    │ <<includes>>   │
                    └────────┬───────┘
                             │
                    ┌────────▼────────┐
                    │  UC-003:        │
                    │  Handle         │
                    │  Processing     │
                    │  Exception      │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │  UC-004:        │
                    │  Review         │
                    │  Processing     │
                    │  History        │
                    └─────────────────┘
                             │
                    ┌────────▼────────┐
                    │ FrontAccounting │
                    │   (ACT-101)     │
                    └─────────────────┘
```

### 4.3 Exception Handling Diagram

```
                    ┌─────────────────┐
                    │  Finance Clerk  │
                    └────────┬────────┘
                             │
                    ┌────────▼─────────┐
                    │  UC-003:         │
                    │  Handle          │
                    │  Exception       │
                    └──┬────────────┬──┘
                       │            │
              ┌────────▼───┐   ┌───▼─────────┐
              │ Skip       │   │ Manual      │
              │ Transaction│   │ Correction  │
              └────────────┘   └─────────────┘
```

---

## 5. Detailed Use Case Specifications

---

## UC-001: Process Paired Transfer

### Basic Information

| **Use Case ID** | UC-001 |
| **Use Case Name** | Process Paired Transfer |
| **Priority** | HIGH |
| **Status** | IMPLEMENTED |
| **Created** | 2025-10-15 |
| **Last Updated** | 2025-10-18 |

### Actors

- **Primary:** Finance Clerk (ACT-001)
- **Secondary:** FrontAccounting System (ACT-101)

### Business Requirements

- **BR-001:** Automate Processing
- **FR-001:** Automatic Pair Detection
- **FR-004:** DC Indicator-Based Direction
- **FR-006:** FrontAccounting Integration

### Preconditions

1. Finance Clerk is logged into the system
2. Bank statements have been imported
3. At least two unprocessed transactions exist
4. Transactions form a valid pair (same amount, opposite DC indicators, within date window)
5. FrontAccounting system is available

### Postconditions

**Success:**
- Bank transfer created in FrontAccounting
- Both transactions marked as processed
- Audit log entry created
- User sees success message

**Failure:**
- No transfer created
- Transactions remain unprocessed
- Error message displayed
- Error logged

### Main Success Scenario (Basic Flow)

1. Finance Clerk navigates to "Process Statements" page
2. System displays list of unprocessed transactions
3. System highlights potential paired transfers with visual indicators:
   - Debit transactions in RED with "D" indicator
   - Credit transactions in GREEN with "C" indicator
4. Finance Clerk reviews matched pair
5. Finance Clerk selects "Process Both Sides" operation for one transaction
6. System validates the pair:
   - Amounts match (within $0.01 tolerance)
   - Dates within ±2 day window
   - Different bank accounts
   - Opposite DC indicators
7. System determines transfer direction:
   - FROM account = transaction with "D" (Debit)
   - TO account = transaction with "C" (Credit)
8. System creates bank transfer in FrontAccounting:
   - FROM account
   - TO account
   - Amount (positive value)
   - Date (earlier of the two dates)
   - Memo (combined transaction titles)
9. FrontAccounting confirms transfer created
10. System marks both transactions as processed
11. System updates transaction records with transfer ID
12. System creates audit log entry
13. System displays success message to user
14. System removes processed transactions from unprocessed list

**Result:** Bank transfer successfully created, both transactions processed

---

### Alternative Flows

#### A1: Amounts Don't Match

**Trigger:** Step 6 - Amount validation fails

1. System detects amount difference exceeds $0.01
2. System displays error: "Transaction amounts do not match within tolerance"
3. System highlights both amounts for comparison
4. Finance Clerk reviews discrepancy
5. **Go to UC-003: Handle Processing Exception**

#### A2: Dates Outside Window

**Trigger:** Step 6 - Date validation fails

1. System detects dates are more than ±2 days apart
2. System displays error: "Transaction dates outside matching window (±2 days)"
3. System shows both dates for comparison
4. Finance Clerk reviews dates
5. **Go to UC-003: Handle Processing Exception**

#### A3: Same Bank Account

**Trigger:** Step 6 - Account validation fails

1. System detects both transactions in same bank account
2. System displays error: "FROM and TO accounts must be different"
3. Finance Clerk reviews transaction details
4. **Go to UC-003: Handle Processing Exception**

#### A4: Same DC Indicator

**Trigger:** Step 7 - DC indicator validation fails

1. System detects both transactions have same DC indicator (both D or both C)
2. System displays error: "Cannot determine transfer direction - same DC indicator"
3. Finance Clerk reviews DC indicators
4. **Go to UC-003: Handle Processing Exception**

#### A5: FrontAccounting API Error

**Trigger:** Step 8 - API call fails

1. System attempts to create transfer in FrontAccounting
2. FrontAccounting returns error response
3. System logs error details
4. System displays error: "Failed to create transfer in FrontAccounting: [error message]"
5. System does NOT mark transactions as processed
6. Finance Clerk acknowledges error
7. Finance Clerk contacts System Administrator
8. **Use case ends in failure**

#### A6: Database Error During Update

**Trigger:** Step 10 - Database update fails

1. System successfully creates FA transfer but fails to update transaction status
2. System logs critical error
3. System displays error: "Transfer created but failed to update transaction status"
4. System provides transfer ID for manual reconciliation
5. Finance Clerk notes transfer ID
6. Finance Clerk contacts System Administrator
7. **Use case ends in partial success**

---

### Exception Flows

#### E1: User Cancels Operation

**Trigger:** Any step before Step 8

1. Finance Clerk clicks "Cancel" button
2. System confirms cancellation
3. No changes made
4. **Use case ends**

#### E2: Session Timeout

**Trigger:** Any step

1. User session expires
2. System detects session timeout
3. System displays "Session expired" message
4. System redirects to login page
5. **Use case ends**

#### E3: Network Connection Lost

**Trigger:** Step 8 - During API call

1. Network connection lost during FrontAccounting API call
2. System detects connection failure
3. System displays error: "Network error - connection lost"
4. System does NOT mark transactions as processed
5. Finance Clerk waits for connection restore
6. Finance Clerk retries operation
7. **Return to Step 6**

---

### Business Rules

- **BR-MATCH-001:** Amount tolerance is exactly $0.01
- **BR-MATCH-002:** Date window is ±2 days (inclusive)
- **BR-MATCH-003:** Accounts must be different
- **BR-MATCH-004:** DC indicators must be opposite (D and C)
- **BR-DIR-001:** Debit (D) indicator = FROM account
- **BR-DIR-002:** Credit (C) indicator = TO account
- **BR-AMOUNT-001:** Transfer amount is always positive
- **BR-DATE-001:** Transfer date is earlier of the two transaction dates

---

### Special Requirements

**Performance:**
- Processing must complete within 2 seconds
- Page must load within 3 seconds

**Usability:**
- Visual indicators must be clear (red/green, D/C)
- Error messages must be actionable
- Success confirmation must be visible

**Security:**
- User must be authenticated
- Audit log must record all actions
- Sensitive data must not appear in logs

---

### Frequency of Use

- **Daily:** 5-20 times per day per user
- **Peak:** Month-end (50+ times per day)
- **Average Processing Time:** 1-2 minutes per transfer

---

### Open Issues

- None

---

## UC-002: Match Transaction Pairs

### Basic Information

| **Use Case ID** | UC-002 |
| **Use Case Name** | Match Transaction Pairs |
| **Priority** | HIGH |
| **Status** | IMPLEMENTED |

### Actors

- **Primary:** Finance Clerk (ACT-001)
- **Secondary:** None

### Business Requirements

- **FR-001:** Automatic Pair Detection
- **FR-002:** Date Window Matching
- **FR-003:** Amount Tolerance

### Preconditions

1. User is logged in
2. Unprocessed transactions exist
3. At least one valid pair exists in database

### Postconditions

**Success:**
- Potential matches identified
- Matches displayed to user
- Visual indicators applied

### Main Success Scenario

1. Finance Clerk navigates to "Process Statements"
2. System loads all unprocessed transactions from database
3. System groups transactions by bank account
4. System identifies potential pairs:
   - Same amount (±$0.01)
   - Different accounts
   - Within ±2 day window
   - Opposite DC indicators
5. System applies visual indicators:
   - Highlight matched pairs with border/background
   - Color-code by DC indicator (RED=D, GREEN=C)
   - Display confidence score (if ambiguous)
6. System sorts transactions:
   - Matched pairs first
   - Sorted by date
   - Grouped by match group
7. System displays transaction list to user
8. User reviews matched pairs

**Result:** User sees clearly identified transaction pairs ready for processing

---

### Alternative Flows

#### A1: No Matches Found

1. System completes matching algorithm
2. No valid pairs identified
3. System displays all transactions without pair highlighting
4. System shows message: "No matching transaction pairs found"
5. User can still process transactions individually

#### A2: Multiple Potential Matches

1. System finds multiple potential matches for one transaction
2. System calculates confidence score for each match
3. System highlights highest confidence match
4. System shows other potential matches with lower confidence
5. User can select preferred match manually

#### A3: Ambiguous Match

1. System finds two transactions with same amount, date, and accounts
2. System cannot determine correct pairing
3. System flags as "Requires Manual Review"
4. User reviews and selects correct pair

---

### Business Rules

- **BR-MATCH-001:** Amount tolerance exactly $0.01
- **BR-MATCH-002:** Date window ±2 days inclusive
- **BR-MATCH-005:** Prefer exact amount matches over tolerance matches
- **BR-MATCH-006:** Prefer same-day matches over date-difference matches

---

## UC-003: Handle Processing Exception

### Basic Information

| **Use Case ID** | UC-003 |
| **Use Case Name** | Handle Processing Exception |
| **Priority** | MEDIUM |
| **Status** | IMPLEMENTED |

### Actors

- **Primary:** Finance Clerk (ACT-001)
- **Secondary:** Accounting Manager (ACT-002)

### Business Requirements

- **FR-008:** Error Handling
- **FR-007:** Validation Rules
- **NFR-005:** Reliability

### Preconditions

1. User attempted to process transaction(s)
2. Validation failed or error occurred
3. Error message displayed

### Postconditions

**Success:**
- Exception resolved
- Transaction processed OR marked for review
- User continues work

**Failure:**
- Exception escalated to manager
- Transaction remains unprocessed

### Main Success Scenario

1. System detects validation error or exception
2. System displays clear error message explaining issue
3. System highlights problematic fields/data
4. Finance Clerk reviews error details
5. Finance Clerk chooses resolution:
   - **Option A:** Skip transaction for now
   - **Option B:** Manually correct data
   - **Option C:** Process individually (not as pair)
   - **Option D:** Flag for manager review
6. System executes chosen action
7. System logs exception and resolution
8. User continues processing other transactions

**Result:** Exception handled, user can continue work

---

### Alternative Flows

#### A1: Skip Transaction

1. Finance Clerk selects "Skip"
2. System marks transaction as "Skipped"
3. System adds note with skip reason
4. Transaction remains in unprocessed list
5. User can revisit later

#### A2: Manual Correction

1. Finance Clerk selects "Edit"
2. System opens edit dialog
3. Finance Clerk corrects data (amount, date, etc.)
4. System validates corrected data
5. If valid, returns to UC-001 Step 6
6. If still invalid, displays new error

#### A3: Process Individually

1. Finance Clerk selects "Process Single Side"
2. System processes transaction as standalone entry (not transfer)
3. System creates journal entry in FrontAccounting
4. System marks transaction as processed
5. Paired transaction remains unprocessed

#### A4: Flag for Manager Review

1. Finance Clerk selects "Flag for Review"
2. System prompts for note/comment
3. Finance Clerk enters explanation
4. System marks transaction as "Pending Manager Review"
5. System notifies Accounting Manager
6. Manager receives task in queue
7. **Go to UC-007: Approve Exception**

---

### Business Rules

- **BR-EXC-001:** All exceptions must be logged
- **BR-EXC-002:** Flagged items require manager action within 1 business day
- **BR-EXC-003:** Skipped items must be reviewed within 5 business days

---

## UC-004: Review Processing History

### Basic Information

| **Use Case ID** | UC-004 |
| **Use Case Name** | Review Processing History |
| **Priority** | LOW |
| **Status** | IMPLEMENTED |

### Actors

- **Primary:** Finance Clerk (ACT-001)
- **Primary:** Accounting Manager (ACT-002)

### Business Requirements

- **NFR-010:** Audit Trail
- **FR-013:** Processing History

### Main Success Scenario

1. User navigates to "Processing History" page
2. System displays filters:
   - Date range
   - Bank account
   - Processed by user
   - Status (Success/Failed)
3. User selects filters
4. System queries processing log
5. System displays results:
   - Transaction details
   - Processing timestamp
   - User who processed
   - Transfer ID (if created)
   - Status
   - Any errors/notes
6. User can view details, export report, or drill down
7. User can verify accuracy of processed transfers

**Result:** User has visibility into all processing activity

---

## UC-005: Bulk Process Multiple Pairs

### Basic Information

| **Use Case ID** | UC-005 |
| **Use Case Name** | Bulk Process Multiple Pairs |
| **Priority** | MEDIUM |
| **Status** | IMPLEMENTED |

### Main Success Scenario

1. Finance Clerk navigates to "Process Statements"
2. System displays all unprocessed transactions with matches
3. Finance Clerk reviews list of potential pairs
4. Finance Clerk selects multiple pairs using checkboxes
5. Finance Clerk clicks "Process Selected Pairs" button
6. System validates each selected pair
7. System displays summary:
   - Number of pairs selected
   - Total amount to transfer
   - Validation status for each pair
8. Finance Clerk confirms bulk operation
9. System processes each pair sequentially:
   - Creates transfer in FrontAccounting
   - Marks transactions as processed
   - Logs each action
10. System displays progress indicator during processing
11. System displays final results:
    - Number successful
    - Number failed
    - List of any errors
12. Finance Clerk reviews results

**Result:** Multiple transfers processed efficiently

---

### Alternative Flows

#### A1: Some Pairs Fail Validation

1. During Step 9, one or more pairs fail validation
2. System continues processing valid pairs
3. System skips invalid pairs
4. System displays partial success message
5. System lists failed pairs with error reasons
6. User can retry failed pairs individually

---

## UC-006: Manually Create Transfer

### Basic Information

| **Use Case ID** | UC-006 |
| **Use Case Name** | Manually Create Transfer (Without Pairing) |
| **Priority** | LOW |
| **Status** | IMPLEMENTED |

### Main Success Scenario

1. Finance Clerk encounters transaction that cannot be auto-matched
2. Finance Clerk selects transaction
3. Finance Clerk clicks "Manual Transfer" button
4. System displays manual transfer form:
   - FROM account (pre-filled from transaction)
   - TO account (user selects)
   - Amount (pre-filled)
   - Date (pre-filled)
   - Memo (user enters)
5. Finance Clerk fills in TO account and memo
6. Finance Clerk clicks "Create Transfer"
7. System validates inputs
8. System creates transfer in FrontAccounting
9. System marks transaction as processed
10. System displays success message

**Result:** Transfer created manually, transaction processed

---

## UC-007: Approve Exception (Manager)

### Basic Information

| **Use Case ID** | UC-007 |
| **Use Case Name** | Approve Exception |
| **Priority** | MEDIUM |
| **Status** | IMPLEMENTED |

### Actors

- **Primary:** Accounting Manager (ACT-002)

### Main Success Scenario

1. Accounting Manager logs in
2. System displays dashboard with pending reviews
3. Manager sees count of flagged transactions
4. Manager navigates to "Pending Reviews" queue
5. System displays list of flagged transactions with:
   - Transaction details
   - Reason for flag
   - Clerk comments
   - Days pending
6. Manager selects transaction to review
7. System displays full transaction details
8. Manager reviews and makes decision:
   - **Approve:** Process as clerk recommended
   - **Reject:** Return to clerk with instructions
   - **Override:** Make manual correction and process
9. System executes manager's decision
10. System notifies clerk of decision
11. System logs manager approval in audit trail

**Result:** Exception resolved with manager oversight

---

## UC-008: View System Reports

### Basic Information

| **Use Case ID** | UC-008 |
| **Use Case Name** | View System Reports |
| **Priority** | LOW |
| **Status** | IMPLEMENTED |

### Actors

- **Primary:** Accounting Manager (ACT-002)

### Main Success Scenario

1. Manager navigates to "Reports" section
2. System displays available reports:
   - Daily processing summary
   - Error rate trends
   - Processing time metrics
   - User productivity
   - Exception statistics
3. Manager selects report and date range
4. System generates report
5. System displays results with visualizations
6. Manager can export to PDF/Excel

**Result:** Manager has insight into system performance and team productivity

---

## 6. Use Case Relationships

### 6.1 Include Relationships

- **UC-001 includes UC-002:** Processing paired transfer requires matching
- **UC-001 includes UC-003:** Processing may trigger exception handling
- **UC-005 includes UC-001:** Bulk processing uses individual processing logic

### 6.2 Extend Relationships

- **UC-003 extends UC-001:** Exception handling extends normal processing
- **UC-006 extends UC-001:** Manual transfer extends automated processing
- **UC-007 extends UC-003:** Manager approval extends exception handling

### 6.3 Generalization

- **UC-001 generalizes to:** "Process Transaction"
- **UC-002 generalizes to:** "Match Records"
- **UC-003 generalizes to:** "Handle Error"

---

## 7. Business Rules

### 7.1 Matching Rules

| Rule ID | Description | Priority | Enforced By |
|---------|-------------|----------|-------------|
| BR-MATCH-001 | Amount tolerance exactly $0.01 | MUST | System |
| BR-MATCH-002 | Date window ±2 days inclusive | MUST | System |
| BR-MATCH-003 | Accounts must be different | MUST | System |
| BR-MATCH-004 | DC indicators must be opposite | MUST | System |
| BR-MATCH-005 | Prefer exact matches | SHOULD | System |
| BR-MATCH-006 | Prefer same-day matches | SHOULD | System |

### 7.2 Transfer Direction Rules

| Rule ID | Description | Priority | Enforced By |
|---------|-------------|----------|-------------|
| BR-DIR-001 | Debit (D) = FROM account | MUST | System |
| BR-DIR-002 | Credit (C) = TO account | MUST | System |
| BR-AMOUNT-001 | Amount always positive | MUST | System |
| BR-DATE-001 | Use earlier date | MUST | System |

### 7.3 Exception Rules

| Rule ID | Description | Priority | Enforced By |
|---------|-------------|----------|-------------|
| BR-EXC-001 | All exceptions logged | MUST | System |
| BR-EXC-002 | Manager review within 1 day | SHOULD | Policy |
| BR-EXC-003 | Skipped items reviewed within 5 days | SHOULD | Policy |

### 7.4 Audit Rules

| Rule ID | Description | Priority | Enforced By |
|---------|-------------|----------|-------------|
| BR-AUDIT-001 | Log all processing actions | MUST | System |
| BR-AUDIT-002 | Include user and timestamp | MUST | System |
| BR-AUDIT-003 | Retain logs 7 years | MUST | Policy |

---

## 8. Traceability Matrix

### 8.1 Use Cases to Requirements

| Use Case | Business Req | Functional Req | Non-Functional Req |
|----------|--------------|----------------|-------------------|
| UC-001 | BR-001, BR-002 | FR-001, FR-004, FR-006 | NFR-001, NFR-005, NFR-010 |
| UC-002 | BR-001 | FR-001, FR-002, FR-003 | NFR-001 |
| UC-003 | BR-002 | FR-007, FR-008 | NFR-005, NFR-007 |
| UC-004 | - | FR-013 | NFR-010 |
| UC-005 | BR-001 | FR-005 | NFR-001, NFR-002 |
| UC-006 | BR-002 | FR-006 | NFR-005 |
| UC-007 | BR-002 | FR-008 | NFR-005, NFR-010 |
| UC-008 | - | FR-013 | NFR-010 |

### 8.2 Use Cases to Test Scenarios

| Use Case | Unit Tests | Integration Tests | UAT Tests |
|----------|-----------|-------------------|-----------|
| UC-001 | TC-001-A to E | IT-001, IT-011, IT-021 | UAT-001, UAT-002 |
| UC-002 | TC-002-A to D | IT-012 | UAT-003, UAT-004 |
| UC-003 | TC-003-A to C | IT-015 | UAT-011, UAT-016, UAT-017 |
| UC-004 | - | IT-002 | UAT-024 |
| UC-005 | TC-005-A to B | IT-011 | UAT-021 |
| UC-006 | - | IT-021 | UAT-006 |
| UC-007 | - | - | UAT-007 |
| UC-008 | - | - | UAT-008 |

---

## 9. Appendices

### Appendix A: Use Case Prioritization

**HIGH Priority (Must Have):**
- UC-001: Process Paired Transfer
- UC-002: Match Transaction Pairs

**MEDIUM Priority (Should Have):**
- UC-003: Handle Processing Exception
- UC-005: Bulk Process Multiple Pairs
- UC-007: Approve Exception

**LOW Priority (Nice to Have):**
- UC-004: Review Processing History
- UC-006: Manually Create Transfer
- UC-008: View System Reports

### Appendix B: Actor Access Matrix

| Actor | UC-001 | UC-002 | UC-003 | UC-004 | UC-005 | UC-006 | UC-007 | UC-008 |
|-------|--------|--------|--------|--------|--------|--------|--------|--------|
| Finance Clerk | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | - | - |
| Accounting Manager | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| System Admin | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

### Appendix C: Glossary

**Paired Transfer:** Two bank transactions representing opposite sides of the same money movement

**DC Indicator:** Debit/Credit flag indicating transaction direction (D = outgoing, C = incoming)

**Match Confidence:** Calculated score indicating likelihood that two transactions are a pair

**Processing Exception:** Any error or validation failure during transaction processing

**Audit Trail:** Complete log of all system actions for compliance and troubleshooting

---

**END OF USE CASE SPECIFICATIONS**

*Document Classification: INTERNAL USE*  
*Next Review Date: January 18, 2026*
