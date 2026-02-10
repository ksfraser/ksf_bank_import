# Use Case: Validate GL Entries

## 1. Use Case Summary

**Goal**: Verify that staged bank transactions that have been processed match their linked FA GL entries, and flag issues for review.

**Primary Actor**: Accountant / Bookkeeper

**Entry Point (UI)**: “Validate GL Entries” menu item

**Implementation Anchor**: [validate_gl_entries.php](../../validate_gl_entries.php)

## 2. Preconditions
- There are processed staged transactions with `fa_trans_type` and `fa_trans_no` populated.
- User has permission to view GL transactions.

## 3. Trigger
- User clicks “Validate All” or validates a selected statement.

## 4. Main Success Scenario
1. User opens the Validate GL Entries screen.
2. User initiates validation:
   - Validate all; or
   - Validate for a specific statement.
3. System checks staged transactions and verifies:
   - Referenced FA transaction exists
   - Amounts match (within tolerance)
   - Date/account warnings are recorded where applicable
4. System presents a summary and detailed issue list.
5. User optionally flags a transaction for review.

## 5. Extensions

### E1. Missing GL Entry
- System reports the staged transaction as failed validation and may provide suggested matches.

### E2. Amount mismatch
- System reports variance and highlights large differences.

### E3. Flag a transaction
- User flags an issue for review.
- System records that the transaction is flagged.

## 6. Postconditions
- Validation itself does not change FA transactions.
- Flagging persists a “needs review” indicator for follow-up.

## 7. Related Use Cases
- Process staged transactions (creates/linkage to FA entries): [Project Documents/Process Statements/Use Case - Process Bank Transactions.md](../Process%20Statements/Use%20Case%20-%20Process%20Bank%20Transactions.md)
