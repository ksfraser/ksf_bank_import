# Use Case: View Imported Statements

## 1. Use Case Summary

**Goal**: Allow users to query imported statement headers (date range, balances, delta) for audit/review.

**Primary Actor**: Accountant / Bookkeeper

**Entry Point (UI)**: “Bank Statements Inquiry” menu item

**Implementation Anchor**: [view_statements.php](../../view_statements.php)

## 2. Preconditions
- At least zero statements exist in `bi_statements` (empty state is allowed).

## 3. Trigger
- User selects a date range and clicks Search.

## 4. Main Success Scenario
1. User opens the View Bank Statements screen.
2. User selects “From” and “To” dates and clicks Search.
3. System queries staged statements in `bi_statements` within the date range.
4. System lists statement headers with delta (`endBalance - startBalance`).

## 5. Postconditions
- Read-only inquiry; no state changes.

## 6. Related Use Cases
- Import statements (creates `bi_statements`): [Project Documents/Import Statements/Use Case - Import Bank Statements.md](../Import%20Statements/Use%20Case%20-%20Import%20Bank%20Statements.md)
