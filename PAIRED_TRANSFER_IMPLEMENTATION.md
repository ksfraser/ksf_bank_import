# Paired Bank Transfer Matching - Implementation Summary

## Overview
This document describes the implementation of automatic paired bank transfer detection and concurrent processing for the KSF Bank Import module.

## Problem Statement
Bank transfers between accounts (e.g., Manulife bank, CIBC HISA to CIBC Savings) should be automatically detected and matched on both sides. The system should:
1. Identify paired transactions where money leaves one account and arrives in another
2. Display visual indicators when pairs are detected
3. Allow concurrent processing of both sides with a single action
4. Create a single bank transfer entry that properly records both accounts

## Implementation Details

### 1. Enhanced `findPaired()` Function
**File:** `views/class.bi_lineitem.php`

**Changes:**
- Implemented comprehensive search logic to find paired transactions
- Search window: -2 to +2 days from transaction date
- Matching criteria:
  - Same absolute amount
  - Opposite transaction type (Debit vs Credit)
  - Different bank accounts
  - Status = 0 (unprocessed)
  
**Key Logic:**
```php
function findPaired()
{
    // Search within date range
    $startDate = add_days( $this->valueTimestamp, -2 );
    $endDate = add_days( $this->valueTimestamp, 2 );
    
    // Get matching transactions
    $trzs = $bi_t->get_transactions( 0, $startDate, $endDate, $this->amount, null );
    
    // Filter for valid pairs
    foreach( $trzs as $trans ) {
        // Skip same transaction
        // Skip same account
        // Skip same DC type
        // Verify amount matches
        // Add to results
    }
    
    return $pairedTransactions;
}
```

### 2. Updated `isPaired()` Function
**File:** `views/class.bi_lineitem.php`

**Changes:**
- Now actively calls `findPaired()` and caches results
- Returns `true` if unprocessed paired transactions found
- Caches results in `$this->pairedTransactions` property

### 3. Visual Indicators - `displayPaired()` Function
**File:** `views/class.bi_lineitem.php`

**Features:**
- Bright yellow/orange highlighting with border
- Arrow icon (⇄) to indicate transfer
- Displays paired transaction details:
  - Transaction ID
  - Account information
  - Debit/Credit type
  - Amount (formatted)
  - Date
  - Transaction title
- "Process Both Sides Together" button
- Informative help text

**Visual Design:**
- Background: `#ffffcc` (light yellow)
- Border: `2px solid #ffa500` (orange)
- Icon color: `#ff8c00` (dark orange)
- Clear, prominent display to catch user attention

### 4. Concurrent Processing Handler
**File:** `process_statements.php`

**New Section:** `ProcessBothSides`

**Process Flow:**
1. Receive first transaction ID from form submission
2. Load first transaction and get bank account details
3. Use `bi_lineitem::findPaired()` to get paired transaction(s)
4. Load second transaction and bank account details
5. Determine FROM and TO accounts based on Debit/Credit
6. Create `fa_bank_transfer` object
7. Set all required fields (accounts, amount, date, memo)
8. Generate reference number
9. Begin database transaction
10. Add bank transfer to FrontAccounting
11. Update BOTH imported transactions as processed
12. Link both to the same FA transaction
13. Commit database transaction
14. Display success message with GL view link

**Key Logic:**
```php
// Determine direction based on DC
if( $trz1['transactionDC'] == 'D' ) {
    $from_account = $our_account1['id'];
    $to_account = $our_account2['id'];
} else {
    $from_account = $our_account2['id'];
    $to_account = $our_account1['id'];
}

// Create transfer
$bttrf->set( "FromBankAccount", $from_account );
$bttrf->set( "ToBankAccount", $to_account );
$bttrf->add_bank_transfer();

// Update BOTH transactions
update_transactions( $from_trans_id, $_cids, 1, $trans_no, $trans_type, false, true, "BT", $to_account );
update_transactions( $to_trans_id, $_cids, 1, $trans_no, $trans_type, false, true, "BT", $from_account );
```

## How It Works - User Experience

### Scenario: CIBC HISA to CIBC Savings Transfer

1. **Import both bank statements** containing the transfer:
   - CIBC HISA shows a Debit of $1,000 on 2025-01-15
   - CIBC Savings shows a Credit of $1,000 on 2025-01-15

2. **View Process Statements page**:
   - System automatically detects paired transactions
   - Bright yellow/orange highlight box appears
   - Shows "⇄ PAIRED BANK TRANSFER DETECTED"
   - Displays details of the paired transaction

3. **Process the transfer**:
   - Click "Process Bank Transfer (Both Sides)" button
   - System creates a single ST_BANKTRANSFER entry
   - Both imported transactions marked as processed
   - Both link to the same GL transaction
   - Success message with link to view GL entry

### Benefits

1. **No Manual Matching**: System automatically finds pairs
2. **Visual Clarity**: Can't miss paired transactions
3. **Single Action**: One click processes both sides
4. **Data Integrity**: Both sides always linked to same GL entry
5. **Audit Trail**: Clear connection between import and GL

## Technical Notes

### Database Fields Used
- `bi_transactions.status`: Set to 1 when processed
- `bi_transactions.fa_trans_no`: Linked to created bank transfer
- `bi_transactions.fa_trans_type`: Set to ST_BANKTRANSFER
- `bi_transactions.g_partner`: Set to 'BT'
- `bi_transactions.g_option`: Set to partner account ID

### Date Window Rationale
The ±2 day window accommodates:
- Bank processing delays
- Weekend/holiday processing
- Timing differences between institutions
- Manual entry date variations

Can be adjusted if needed by modifying the date calculations in `findPaired()`.

### Amount Matching
Uses absolute value comparison to handle:
- Different DC indicators (D vs C)
- Sign differences in amount storage
- Currency formatting variations

### Account Matching
The system identifies pairs by:
- Different source accounts (can't transfer to self)
- Matching absolute amounts
- Opposite debit/credit indicators
- Close transaction dates

It does NOT require exact name matching in the description fields, making it robust against bank naming variations (e.g., "TRANSFER TO SAVINGS" vs "TRANSFER FROM CHEQUING").

## Files Modified

1. **views/class.bi_lineitem.php**
   - Enhanced `findPaired()` method (lines ~1089-1152)
   - Updated `isPaired()` method (lines ~1154-1179)
   - Implemented `displayPaired()` method (lines ~1081-1127)
   - Added `$pairedTransactions` property (line ~806)

2. **process_statements.php**
   - Added ProcessBothSides handler (lines ~106-207)
   - Integrated with existing transaction processing flow

## Testing Checklist

- [ ] Test Manulife bank transfers with explicit references
- [ ] Test CIBC HISA ↔ CIBC Savings transfers
- [ ] Test transfers with dates within ±2 day window
- [ ] Test transfers with exact same-day dates
- [ ] Verify visual highlighting displays correctly
- [ ] Verify "Process Both Sides" button appears when paired
- [ ] Test concurrent processing creates correct GL entry
- [ ] Verify both transactions marked as processed
- [ ] Verify both link to same FA transaction number
- [ ] Test with already-processed transactions (should not show as paired)
- [ ] Test with mismatched amounts (should not pair)
- [ ] Test with same account transfers (should not pair)
- [ ] Test with same DC type (should not pair)

## Future Enhancements (Optional)

1. **Configurable Date Window**: Add admin setting for date range
2. **Confidence Score**: Display matching confidence percentage
3. **Multiple Pairs**: Handle scenarios with multiple possible pairs
4. **Partial Matches**: Suggest near-matches for manual review
5. **Batch Processing**: Process multiple pairs at once
6. **Audit Report**: Generate report of all auto-matched transfers
7. **Undo Function**: Allow unlinking paired transactions if needed

## Compatibility

- Requires existing `fa_bank_transfer` class
- Uses existing `bi_transactions_model` methods
- Compatible with current FrontAccounting GL structure
- Works with existing bank import parsers (QFX, MT940, CSV)

## Support

For issues or questions:
- Check transaction dates are within ±2 day window
- Verify amounts match exactly
- Ensure one transaction is Debit, other is Credit
- Confirm both transactions are unprocessed (status = 0)
- Check bank accounts are different

## Implementation Date
January 2025

## Version
1.0.0
