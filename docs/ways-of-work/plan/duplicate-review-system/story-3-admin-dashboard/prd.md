---
title: "Story 3: Admin Review Dashboard - Product Requirements Document"
epic: "Duplicate Review System"
feature: "Admin Review UI"
status: "In Planning"
created: "2026-04-09"
version: "1.0"
---

# Story 3: Admin Review Dashboard - PRD

## 1. Executive Summary

### Problem
After transactions are imported, duplicate matches are detected and stored in a staging table (`bi_transactions_dupe`). However, without a user interface, admins have no way to review these flagged duplicates, understand why they were flagged, or make decisions (approve/reject/investigate) before posting to the general ledger. This creates a bottleneck in the transaction import lifecycle.

### Solution
Build an admin-accessible review dashboard that displays pending duplicate transactions with:
- Confidence scores and match details
- Side-by-side transaction comparison
- One-click decision buttons (Approve/Reject/Investigate)
- Audit trail visibility
- Filtering and sorting capabilities
- Mobile-responsive design

### Impact
- **Reduced Manual Work**: Streamlines duplicate review from hours of SQL queries to minutes in a user-friendly interface
- **Audit Compliance**: Records who made each decision, when, and with what reasoning
- **Error Prevention**: Clear visualization prevents accidental approval of false-positive matches
- **Decision Velocity**: Admins can review and decide on 50+ transactions per shift
- **Data Quality**: Ensures only validated transactions reach GL posting

---

## 2. User Personas

### Persona 1: Senior Accountant (Primary)
- **Role**: GL accountant with 5+ years experience
- **Goals**: Review and approve/reject duplicate matches quickly with confidence
- **Pain Points**: 
  - Can't easily identify false positives
  - Desires audit trail for regulatory compliance
  - Needs mobile access during reconciliation work
- **Technical Level**: Medium (comfortable with web UIs, not command-line)
- **Frequency**: Daily (30-60 min session)

### Persona 2: Financial Operations Manager (Secondary)
- **Role**: Oversees GL posting operations and compliance
- **Goals**: Monitor duplicate decision queue, see trends, create audit reports
- **Pain Points**:
  - Needs visibility into decision backlog
  - Wants to spot decisions that need escalation
  - Requires compliance documentation
- **Technical Level**: Low-Medium
- **Frequency**: 2-3 times per week (10-15 min sessions)

### Persona 3: System Admin (Tertiary)
- **Role**: Manages data integrity and troubleshoots import issues
- **Goals**: Investigate failed duplicate detection, test decision workflows
- **Pain Points**:
  - Needs raw transaction data for debugging
  - Wants to bulk reject obviously-false matches
  - Requires export for offline analysis
- **Technical Level**: High
- **Frequency**: As-needed (1-2 times per week)

---

## 3. User Stories

### Story 3.1: View Pending Duplicates Queue
**As a** Senior Accountant  
**I want to** see a list of pending duplicate transactions with key details  
**So that** I can quickly understand what needs review and prioritize my work

**Acceptance Criteria:**
- Dashboard displays all transactions with status = 'PENDING'
- For each transaction, show:
  - Transaction code (e.g., `TRNS-2026-001234`)
  - Transaction date
  - Amount
  - Counterparty name
  - Confidence score (0-100%)
  - Reason for flagging
- Sort order defaults to: oldest first, then by confidence (high→low)
- Display max 50 pending transactions per page (pagination options: 10, 25, 50, 100)
- Dashboard loads within 2 seconds
- On mobile: collapse non-critical columns, show inline decision buttons

### Story 3.2: Filter and Search Duplicates
**As a** Senior Accountant  
**I want to** filter duplicates by date range, confidence, status, counterparty  
**So that** I can focus on specific types of matches without manual scrolling

**Acceptance Criteria:**
- Filter by: Date range (from/to), Confidence threshold (≥60%, ≥75%, ≥90%), Status (PENDING only initially)
- Search by: Transaction code, Counterparty name (case-insensitive, partial match)
- Filters are persistent within session (survive navigation)
- Clearing filters returns to default view
- Filter count badge shows active filters applied
- On mobile: collapsible filter panel (hamburger menu)

### Story 3.3: View Duplicate Comparison
**As a** Senior Accountant  
**I want to** see side-by-side comparison of matched transactions  
**So that** I can understand why they were flagged and make an informed decision

**Acceptance Criteria:**
- Click row to expand detail view showing:
  - **Left panel**: Original transaction from `bi_transactions` or import staging
  - **Right panel**: Potential duplicate from `bi_transactions_dupe`
  - Fields shown: Code, Date, Amount, Description, Counterparty, Reference
  - Matching fields highlighted in same color (green if match, yellow if close)
  - Confidence score calculation breakdown (e.g., "Code match: 100%, Date: 95%, Amount: 85%")
  - Match reason (e.g., "Exact code + date match but amount differs by 2%")
- Comparison readable on mobile (vertical stacking of panels)
- Copy buttons for each field for manual verification

### Story 3.4: Make Decision (Approve/Reject/Investigate)
**As a** Senior Accountant  
**I want to** click action buttons to approve, reject, or mark for investigation  
**So that** I can process the duplicate decision and track my choices

**Acceptance Criteria:**
- Three clearly labeled buttons visible:
  - **Approve** (green): "This is a real duplicate - merge and post"
  - **Reject** (red): "This is a false positive - keep separate"
  - **Investigate** (yellow): "Needs more research - hold for later"
- Approve/Reject require optional reason field (max 500 chars, sanitized)
- Investigate populates notes field for follow-up details
- On click: Show loading state → Save decision via API → Refresh table → Show success toast
- Success message includes: "Decision recorded by [user] at [time]. Next transaction loaded."
- Failed decisions show error toast with retry option
- Undo not available (intentional - audit only)

### Story 3.5: View Decision Audit Trail
**As a** Financial Operations Manager  
**I want to** see who made each decision, when, and with what reasoning  
**So that** I can verify compliance and investigate disputes

**Acceptance Criteria:**
- Clicking "History" or "Audit" link on a transaction shows:
  - Decision timestamp (UTC)
  - User who made decision (display name)
  - Decision type (APPROVED/REJECTED/INVESTIGATE)
  - Reason provided (if any)
  - Reason provided (if any)
  - Current status of transaction
- History is read-only, append-only
- Export audit trail to CSV for compliance reports
- Searchable by decision date range, user, decision type

### Story 3.6: Dashboard Responsiveness & Accessibility
**As a** Senior Accountant (using tablet during reconciliation)  
**I want to** access the dashboard on mobile/tablet with full functionality  
**So that** I can review and approve duplicates while away from my desk

**Acceptance Criteria:**
- Responsive breakpoints: 
  - Mobile (≤768px): Vertical layout, collapsed tables, large touch targets
  - Tablet (769-1024px): Hybrid layout
  - Desktop (≥1025px): Full feature table
- Touch-friendly buttons (min 44x44px)
- Accessible keyboard navigation (Tab, Enter, Arrow keys)
- WCAG AA compliant:
  - Color not sole means of conveying meaning
  - Sufficient color contrast (4.5:1 for text)
  - Form labels and ARIA attributes
  - Focus indicators visible
  - Screen reader announcements for decision confirmation
- Fast load: <3 seconds on 4G
- No external image dependencies (use SVG/CSS where possible)

---

## 4. Functional Requirements

### 4.1 Dashboard View
- [ ] Display list of pending duplicates from `bi_transactions_dupe` table
- [ ] Filter by: date range, confidence threshold, counterparty
- [ ] Search by: transaction code, counterparty name
- [ ] Sort by: date (default), confidence, amount
- [ ] Pagination: Show 10/25/50/100 per page
- [ ] Real-time filter feedback (show matched count)

### 4.2 Detail View / Comparison
- [ ] Expand row to show side-by-side transaction comparison
- [ ] Highlight matching/differing fields
- [ ] Show confidence calculation breakdown
- [ ] Display original transaction source (import file, statement, manual entry)

### 4.3 Decision Submission
- [ ] Approve button submits decision via REST API to `DuplicateReviewService::approve()`
- [ ] Reject button accepts reason, submits via `DuplicateReviewService::reject()`
- [ ] Investigate button accepts notes, submits via `DuplicateReviewService::investigate()`
- [ ] Validate reason/notes on client (max 500 chars, no injection)
- [ ] Show loading state during submission
- [ ] Handle API errors gracefully (show error message, allow retry)

### 4.4 Audit & History
- [ ] Track all decisions: user, timestamp, action, reason
- [ ] Provide "View History" link per transaction
- [ ] Read-only history view
- [ ] Export audit trail to CSV

### 4.5 Performance & Reliability
- [ ] Dashboard loads within 2 seconds first-time
- [ ] Filter/search operations complete within 500ms
- [ ] API calls have proper error handling and timeouts (10s)
- [ ] Client-side form validation before submission
- [ ] Session timeout warning at 14 minutes (15 min timeout)

---

## 5. Non-Functional Requirements

### 5.1 Security
- [ ] Authentication required (same as staff area)
- [ ] Authorization: Only staff with "GL Admin" or "Finance" role
- [ ] CSRF token on all state-changing forms
- [ ] Input sanitization: HTML-escape reason/notes fields
- [ ] Audit log: Track who viewed which transactions (for SOX compliance)
- [ ] No sensitive data in URLs (use POST for decision submission)
- [ ] HTTPS only (enforced by FA framework)
- [ ] No client-side storage of transaction data (except session)

### 5.2 Performance
- [ ] Page load (full render): <2 seconds on fast connection, <4 seconds on 4G
- [ ] Filter/sort operations: <500ms
- [ ] API response time: <1 second (p95)
- [ ] Database query optimization: Indexes on status, date, counterparty
- [ ] Pagination prevents large result sets (max 1000 at once)

### 5.3 Accessibility (WCAG AA Compliance)
- [ ] All form inputs labeled with `<label for="...">`
- [ ] Color not sole means of conveying info (e.g., status shown as text + color)
- [ ] Contrast ratio ≥4.5:1 for all text
- [ ] Keyboard navigation: Tab through filters → table rows → action buttons
- [ ] Screen reader support: ARIA live regions for async confirmations
- [ ] Focus indicators visible (not removed)
- [ ] Mobile: Touch targets 44x44px minimum

### 5.4 Data Integrity
- [ ] Each decision recorded exactly once (no duplicate submissions on rapid clicks)
- [ ] Idempotent API: Submitting same decision twice yields same result, no error
- [ ] Transactional: Decision + audit record updated together, or both rollback
- [ ] Concurrency: Handle simultaneous decisions on same transaction (last-write-wins or queue)

### 5.5 Scalability
- [ ] Support 100+ concurrent users on same dashboard
- [ ] Database indexes prevent N+1 queries
- [ ] Lazy load transaction details (don't fetch all data on page load)
- [ ] Cache read-only data (counterparty list, confidence configs) for 1 hour

### 5.6 Maintainability
- [ ] Code organized by feature (components, services, DTOs)
- [ ] Unit test coverage ≥80% for business logic
- [ ] Integration tests for API contracts
- [ ] Comprehensive error logging
- [ ] Clear separation of concerns (view/logic/data)
- [ ] Well-commented complex business logic
- [ ] Dependency injection for testability

---

## 6. Acceptance Criteria

### Functional AC
- [ ] All pending duplicates load in dashboard within 2 seconds
- [ ] Filters (date, confidence, counterparty) work correctly
- [ ] Search returns correct results (case-insensitive, partial match)
- [ ] Clicking row expands side-by-side comparison view
- [ ] Approve/Reject/Investigate buttons submit decisions successfully
- [ ] Decision records created in database with correct user/timestamp
- [ ] Success toast confirms decision recorded
- [ ] Next pending transaction auto-loads after decision
- [ ] History/Audit trail viewable and exportable
- [ ] Mobile view is fully functional (no horizontal scroll, large touch targets)

### Quality AC
- [ ] Code coverage ≥80% for all service classes (unit tests pass)
- [ ] Integration tests verify API contracts (controller ↔ service)
- [ ] No SQL injection vulnerabilities (all queries parameterized)
- [ ] No XSS vulnerabilities (output HTML-escaped)
- [ ] WCAG AA accessibility audit passes
- [ ] Load test: 100+ concurrent users on 4G connection (acceptable slowdown ≤20%)
- [ ] All error paths handled with appropriate HTTP status codes
- [ ] Git commit follows conventional commits format

---

## 7. Out of Scope

### Explicitly NOT Included in Story 3
- ❌ Advanced duplicate matching algorithms (Story 1 scope)
- ❌ Bulk decision operations (e.g., "approve all >95% confidence")
- ❌ Custom dashboard themes or user preferences
- ❌ Email notifications on decision events (Story 4 scope)
- ❌ Mobile app (web-responsive only)
- ❌ Two-factor authentication (use existing FA staff area MFA)
- ❌ Fine-grained audit permissions (all GL admins see full history)
- ❌ Duplicate decision appeal/override workflow
- ❌ Analytics/reporting dashboard (future story)
- ❌ Integration with external GL systems (Story 4 scope)

---

## 8. Technical Constraints & Dependencies

### Dependencies
- **Story 1**: `bi_transactions_dupe` table must exist with all audit columns
- **Story 2**: `DuplicateReviewService` must be production-ready (already complete ✅)
- **Story 2**: Event publishing infrastructure (`DuplicateDecisionMade` events)
- **Framework**: Must use existing FA framework (PHP, MySQL, jQuery/AJAX)
- **Existing Patterns**: Follow admin area conventions for authentication/authorization

### Technical Notes
- Service layer already implements decision logic; UI just calls it
- No breaking changes to existing transaction posting workflow (Story 4 builds on this)
- Decision events published by Story 2 service will be consumed by Story 4
- All timestamps stored in UTC, converted to user's timezone on display

---

## 9. Success Metrics

### Business Metrics
- **Decision Velocity**: Admins review/decide on ≥50 duplicates per shift (measured by audit log)
- **Queue Clearance**: Pending duplicates queue stays <100 at end-of-day
- **User Satisfaction**: Dashboard UI rated ≥4/5 in staff survey

### Technical Metrics
- **Performance**: 95th percentile API response time <1 second
- **Reliability**: Dashboard uptime ≥99.9% (measured over 1 month)
- **Code Quality**: Coverage ≥80%, SonarQube grade A, zero high-priority vulnerabilities
- **Accessibility**: WCAG AA audit passes 100%

---

## 10. Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|-----------|
| Database performance degrades with large duplicate queue (10k+ records) | Users experience 5+ second dashboard loads | Medium | Implement pagination (max 50), lazy load comparison details, add indexes on status/date |
| Admin makes decision on wrong duplicate (false positive) | Wrong transactions posted, manual reversal needed | Low | Highlight differences in comparison view, require reason for rejections, audit trail enables reversal |
| Concurrent admin decisions on same transaction | Data inconsistency | Low | Use database optimistic locking (version column) or queue decisions |
| Accessibility compliance not met | Legal liability, poor mobile UX | Low | Use shadcn/ui components (built for A11y), run axe audit in CI, test with screen reader |
| API rate limiting causes decision submission to fail | User frustration, blocked decision-making | Low | Implement per-user rate limits (e.g., 10 decisions/min), show queue status on UI |

---

## 11. Timeline & Dependencies

### Release Plan
- **Phase 1** (Days 1-2): Create PRD, architecture, test strategy (THIS DOCUMENT + following docs)
- **Phase 2** (Days 2-3): TDD code implementation (controller, view, DTOs, tests)
- **Phase 3** (Days 4): Integration testing, accessibility audit, UAT with Finance lead
- **Phase 4** (Day 5): Deployment, monitoring, post-launch support

### Blockers
- ✅ Story 1 (Database schema) - COMPLETE
- ✅ Story 2 (DuplicateReviewService) - COMPLETE **← Story 3 can START NOW**
- ⏳ Story 3 → Story 4 (no blocker, can parallelize)

---

## 12. Appendix: Data Model Context

### Source Table: `bi_transactions_dupe`
```
- id (INT, PK)
- transaction_code (VARCHAR)
- trans_date (DATE)
- amount (DECIMAL)
- counterparty_name (VARCHAR)
- description (TEXT)
- confidence_score (INT, 0-100)
- match_reason (TEXT)
- decision_status (ENUM: PENDING, APPROVED, REJECTED, INVESTIGATE)
- decided_by (VARCHAR, nullable)
- decided_at (DATETIME, nullable, UTC)
- reason (TEXT, nullable, max 500)
- notes (TEXT, nullable)
- created_at (DATETIME, UTC)
- updated_at (DATETIME, UTC)
```

### Related Entities
- **DuplicateTransaction** (Story 1 entity, already built)
- **DuplicateReviewService** (Story 2 service, already built)
- **DuplicateDecisionMade** (Story 2 domain event, already built)
- **ReviewDecision** (Story 2 DTO, already built)

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-04-09 | AI | Initial PRD from epic requirements |
