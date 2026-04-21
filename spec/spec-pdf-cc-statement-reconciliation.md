---
title: PDF CC Statement Reconciliation Specification
version: 1.0
date_created: 2026-04-20
last_updated: 2026-04-20
owner: Finance Application / Engineering
tags: [feature, reconciliation, ocr, ollama, pdf, data]
---

# Introduction

This specification defines the requirements, data contracts, interfaces, acceptance criteria, and constraints for adding PDF credit-card (CC) statement reconciliation as a future phase to the FA bank reconciliation workflow. The feature uses an on-premises Ollama model instance to OCR PDF statements, extracts statement metadata and line-items, and attempts automated reconciliation against the FA bank transactions list.

## 1. Purpose & Scope

Purpose: Provide an automated pipeline to OCR credit-card PDF statements, match statement transactions to FA bank transactions, surface an "auto-reconciled" review screen, allow user adjustments/approval, and persist reconciliation results plus statement metadata and raw OCR JSON.

Scope:
- OCR PDF statements using Ollama API (on-prem server).
- Extract statement-level meta: opening balance, closing balance, statement date range (due date if present), account identifier, and parsed line-items (date, amount, description, fitid-like identifier if present).
- Clone FA reconciliation screen behavior for loading bank transactions and committing reconciliation results.
- Provide an "Auto-Reconciled" review UI showing matches, unmatched statement items, and unmatched bank transactions.
- Persist reconciliation results into the same storage mechanism FA uses and additionally store statement metadata and raw OCR JSON.
- Provide reports of statement-only transactions not present in bank list.

Out of scope for Phase 1:
- Full multi-currency remapping (unless present in statement and bank data); currency support will be documented as constraint.
- Integrations other than configured Ollama server.

## 2. Definitions

- Ollama: in-house models server used to run an OCR/parsing model.
- FA: the Finance Application with an existing bank reconciliation screen and persistence patterns.
- OCR JSON: the structured JSON returned from model parsing that includes raw text and extracted fields.
- Statement Metadata: opening_balance, closing_balance, statement_start_date, statement_end_date, due_date (optional), account_id, raw_ocr_json.
- Reconciliation Result: mapping from statement line(s) to bank transaction record id(s) plus match confidence.

## 3. Requirements, Constraints & Guidelines

- **REQ-001**: The system shall accept a PDF file upload and call the configured Ollama OCR model endpoint with the PDF bytes.
- **REQ-002**: The system shall parse Ollama response into a well-defined JSON schema (see Interfaces & Data Contracts) and validate presence of statement metadata.
- **REQ-003**: The system shall load bank transactions for the same account and date range as FA's reconciliation screen and attempt automated matching.
- **REQ-004**: The system shall present an Auto-Reconciled review UI that mirrors FA screen functionality and allows adjustment and approval.
- **REQ-005**: Upon user approval, the system shall persist reconciliation actions exactly as FA's existing commit flow does (same APIs / DB patterns).
- **REQ-006**: The system shall store statement metadata and the raw OCR JSON in a new database table linked to the reconciliation session.
- **REQ-007**: The system shall flag and list any statement transactions that have no matching bank transactions.
- **SEC-001**: PDF uploads and OCR JSON storage shall follow existing FA data protection policies; personally-identifiable data must be handled consistently with current rules.
- **CON-001**: Ollama endpoint is on-prem and may require mutual-TLS or internal network access; the implementation must be configurable.
- **GUD-001**: Use existing FA UI components and commit routines wherever possible to minimize divergence.

## 4. Interfaces & Data Contracts

### 4.1 Ollama OCR Request

- Endpoint: configurable `OLLAMA_BASE_URL` + `/v1/ocr` (example path; configurable)
- Auth: as configured (API key, mTLS, or internal network)
- Request: multipart/form-data or application/pdf binary body depending on Ollama model API

Example (high level):
- POST {OLLAMA_BASE_URL}/api/parse-pdf
  - Headers: Authorization: Bearer <OLLAMA_KEY>
  - Body: PDF bytes

### 4.2 Ollama OCR Response (normalized into `StatementOcr`)

Schema (example):
- statement_id: string (optional generated)
- account_identifier: string | null
- statement_start_date: YYYY-MM-DD
- statement_end_date: YYYY-MM-DD
- opening_balance: decimal
- closing_balance: decimal
- due_date: YYYY-MM-DD | null
- lines: [
  { line_id: string, date: YYYY-MM-DD, description: string, amount: decimal, type: credit|debit, raw_text: string }
]
- raw_text: string
- raw_ocr_json: object (full model response)
- model_metadata: { model: string, version: string, confidence_overview: { ... } }

### 4.3 Internal Reconciliation Result

- reconciliation_session_id: integer (PK)
- statement_source_id: string (links to stored OCR record)
- matched_pairs: [ { statement_line_id, bank_transaction_id, match_confidence, rules_matched } ]
- unmatched_statement_lines: [statement_line_id]
- unmatched_bank_transactions: [bank_transaction_id]
- persisted_by_user_id: integer (on approval)
- persisted_at: timestamp

## 5. Acceptance Criteria

- **AC-001**: Given a valid PDF statement, when uploaded, then the system returns a parsed `StatementOcr` with statement metadata and at least one line item.
- **AC-002**: Given parsed statement data and loaded bank transactions, when auto-reconcile runs, then >=75% of statement line items with exact amount/dates present in bank data should be auto-matched (configurable threshold).
- **AC-003**: UI shall present unmatched statement lines and unmatched bank transactions for manual user adjustment.
- **AC-004**: After user approval, reconciliation results are persisted using the same flow as FA and statement metadata + raw OCR JSON are stored in a linked table.
- **AC-005**: Security: PDF uploads and stored OCR JSON are accessible only to authorized finance users matching FA permissions.

## 6. Test Automation Strategy

- Unit tests for Ollama client with mocked responses
- Integration tests for parsing pipeline using sample PDFs (golden files)
- UI end-to-end tests for upload → auto-reconcile → review → approve flow
- Migration tests for DB schema changes
- Performance test for OCR throughput (single-file latency target < 5s for typical statements)

## 7. Rationale & Context

Automating CC statement reconciliation reduces manual review time and improves detection of missing transactions. Using an on-prem Ollama instance keeps sensitive financial PDFs within infrastructure boundaries.

## 8. Dependencies & External Integrations

- **EXT-001**: Ollama on-prem server (OCR/parsing model)
- **DEP-001**: FA existing reconciliation APIs and DB schema for persisting results
- **INF-001**: Network route and credentials to access Ollama

## 9. Examples & Edge Cases

- Example: Statement line with amount 123.45 and date 2026-03-15 maps to bank transaction with same date and amount but description mismatch; match_confidence=0.95 if amount/date match.
- Edge: Statement uses different timezone or date formatting — parser must normalize.
- Edge: Statement includes card refunds labeled as negative amounts — must map to credit transactions.

## 10. Validation Criteria

- Automated test coverage for parser and matching logic
- Manual QA sign-off on sample statements from 3 major banks

## 11. Related Specifications / Further Reading

- FA reconciliation UI docs (existing internal doc)
- Ollama model operational runbook
