# KSF Bank Import - User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Processing Bank Transfers](#processing-bank-transfers)
4. [Understanding Transaction Matching](#understanding-transaction-matching)
5. [Operation Types](#operation-types)
6. [Troubleshooting](#troubleshooting)
7. [FAQ](#faq)

## Introduction

The KSF Bank Import module automatically imports and processes bank transactions from QFX/OFX files, with special support for **paired transfers** between your accounts.

### What are Paired Transfers?

When you transfer money between your own bank accounts (e.g., from Manulife to CIBC HISA), the transaction appears twice:
- Once as a **debit** in the source account (money leaving)
- Once as a **credit** in the destination account (money arriving)

This module automatically detects and links these pairs, creating a single FrontAccounting bank transfer instead of two separate transactions.

### Key Features

✅ **Automatic Matching** - Finds paired transfers within ±2 days  
✅ **Smart Direction Detection** - Determines FROM/TO accounts automatically  
✅ **Visual Indicators** - Color-coded display of transaction types  
✅ **Bulk Processing** - Handle multiple transactions efficiently  
✅ **Validation** - Prevents errors with comprehensive checks  

## Getting Started

### Requirements

- FrontAccounting 2.4+ installed
- PHP 7.4 or higher
- Banking accounts configured in FrontAccounting
- QFX/OFX export files from your banks

### Installation

1. Copy the module to your FrontAccounting `/modules/` directory
2. Run `composer install` to install dependencies
3. Configure your bank accounts in FrontAccounting
4. Import your first QFX file using the import_statements.php page

### First Time Setup

1. **Configure Bank Accounts**
   - Go to Banking → Bank Accounts
   - Add all your bank accounts
   - Note the account IDs for reference

2. **Import Test File**
   - Go to Bank Import → Import Statements
   - Upload a QFX file
   - Review the imported transactions

3. **Process First Transfer**
   - Go to Bank Import → Process Statements
   - Find two matching transactions
   - Select "Process Both Sides" from the dropdown
   - Click Process

## Processing Bank Transfers

### Step-by-Step Guide

#### 1. View Unprocessed Transactions

Navigate to **Bank Import → Process Statements**

You'll see a list of imported transactions:

```
┌─────┬──────────────┬─────────────────────┬──────────┬────────────┬──────────┐
│ ID  │ Account      │ Description         │ DC       │ Amount     │ Actions  │
├─────┼──────────────┼─────────────────────┼──────────┼────────────┼──────────┤
│ 123 │ Manulife     │ Transfer to CIBC    │ D (Red)  │ -$1,000.00 │ [▼]      │
│ 124 │ CIBC HISA    │ Transfer from Manu  │ C (Green)│ +$1,000.00 │ [▼]      │
└─────┴──────────────┴─────────────────────┴──────────┴────────────┴──────────┘
```

**Color Coding:**
- 🔴 **Red (Debit)** - Money leaving the account
- 🟢 **Green (Credit)** - Money arriving to the account

#### 2. Identify Matching Pairs

Look for transactions that:
- Have the **same amount** (one positive, one negative)
- Occurred within **±2 days** of each other
- Have **opposite DC indicators** (one D, one C)
- Are in **different accounts**

**Example Match:**
```
Transaction 1:
- Account: Manulife Bank
- Date: 2025-01-15
- Amount: -$500.00 (Debit - money leaving)
- Description: "Transfer to CIBC"

Transaction 2:
- Account: CIBC HISA
- Date: 2025-01-16  (1 day later - within window ✓)
- Amount: +$500.00 (Credit - money arriving)
- Description: "From Manulife"

Result: MATCHED! This is a transfer from Manulife → CIBC
```

#### 3. Process the Pair

For each matched pair:

1. Select **"Process Both Sides"** from the dropdown on either transaction
2. Click the **Process** button
3. The system will:
   - Validate the transaction pair
   - Determine the correct direction (FROM → TO)
   - Create a FrontAccounting bank transfer
   - Mark both transactions as processed
   - Link them together in the database

#### 4. Verify the Transfer

After processing:
- Check **Banking → Bank Transfers** in FrontAccounting
- Verify the transfer appears correctly
- Confirm both accounts are updated

### Operation Types

When processing transactions, you can select from these types:

| Code | Name | Description | Use Case |
|------|------|-------------|----------|
| **BT** | Bank Transfer | Paired transfer between your accounts | Moving money between Manulife and CIBC |
| **SP** | Spending | Regular expense transaction | Paying bills, purchases |
| **CU** | Customer Payment | Income from customers | Sales revenue, client payments |
| **QE** | QuickEntry | Fast entry for common transactions | Recurring transactions |
| **MA** | Manual Adjustment | Manual correction | Fixing errors, adjustments |
| **ZZ** | Ignore | Skip this transaction | Duplicate or irrelevant |

### Single Transaction Processing

Not all transactions are transfers. For single transactions:

1. Select the appropriate operation type (SP, CU, etc.)
2. Choose the counterparty (vendor/customer)
3. Click Process

The transaction will be recorded in FrontAccounting as a single-sided entry.

## Understanding Transaction Matching

### The ±2 Day Window

Transfers may not appear on the same day due to:
- **Processing time** - Banks process at different speeds
- **Business days** - Weekends cause delays
- **Cut-off times** - Transfers initiated late may post next day

The system searches **2 days before and 2 days after** each transaction for matches.

### Matching Algorithm

```
For each unprocessed transaction:
  1. Find other transactions with matching amount (within $0.01)
  2. Check if DC indicators are opposite (D vs C)
  3. Verify dates are within ±2 days
  4. Confirm accounts are different
  5. If all checks pass → MATCH!
```

### Direction Detection Logic

The system uses **DC indicators** to determine direction:

**Debit (D)** = Money **leaving** account = **FROM** account  
**Credit (C)** = Money **arriving** to account = **TO** account

**Example 1:** Manulife to CIBC
```
Manulife Transaction: Amount=-100, DC=D → FROM Manulife (money leaving)
CIBC Transaction:     Amount=+100, DC=C → TO CIBC (money arriving)
Result: Transfer FROM Manulife TO CIBC
```

**Example 2:** CIBC to Manulife
```
CIBC Transaction:     Amount=-100, DC=D → FROM CIBC (money leaving)
Manulife Transaction: Amount=+100, DC=C → TO Manulife (money arriving)
Result: Transfer FROM CIBC TO Manulife
```

### Visual Indicators

The UI provides visual cues:

- **Amount Color**
  - Red/Negative: Debit (money out)
  - Green/Positive: Credit (money in)

- **DC Column**
  - D: Debit transaction
  - C: Credit transaction

- **Processed Status**
  - ✓ = Processed
  - ○ = Unprocessed

## Operation Types

### Using the Dropdown

Each transaction has an operation type dropdown:

```
┌─────────────────────────────┐
│ [BT] Bank Transfer    ▼ │
│ [SP] Spending          │
│ [CU] Customer Payment  │
│ [QE] QuickEntry        │
│ [MA] Manual Adjustment │
│ [ZZ] Ignore            │
└────────────────────────────┘
```

### When to Use Each Type

**BT - Bank Transfer**
- ✅ Moving money between your own accounts
- ✅ Always requires a matching transaction
- ❌ Don't use for payments to vendors

**SP - Spending**
- ✅ Regular expenses (utilities, supplies)
- ✅ Credit card payments
- ✅ Vendor payments
- ❌ Don't use for transfers

**CU - Customer Payment**
- ✅ Income from sales
- ✅ Client payments
- ✅ Revenue transactions
- ❌ Don't use for transfers

**QE - QuickEntry**
- ✅ Fast processing of common transactions
- ✅ Predefined GL codes
- ✅ Recurring patterns

**MA - Manual Adjustment**
- ✅ Correcting errors
- ✅ Bank fee adjustments
- ✅ One-time corrections

**ZZ - Ignore**
- ✅ Duplicate transactions
- ✅ Already processed entries
- ✅ Irrelevant transactions

## Troubleshooting

### Common Issues

#### "No matching transaction found"

**Problem:** System can't find a pair for your transfer

**Solutions:**
1. Check the date range - is the match outside ±2 days?
2. Verify amounts match exactly (within $0.01)
3. Confirm DC indicators are opposite (one D, one C)
4. Ensure transactions are in different accounts
5. Check if the matching transaction was already processed

#### "Invalid transfer data"

**Problem:** Validation failed

**Solutions:**
1. Verify both accounts exist in FrontAccounting
2. Check amount is positive and non-zero
3. Ensure FROM and TO accounts are different
4. Confirm transaction date is valid

#### "Transaction already processed"

**Problem:** Attempting to process a transaction twice

**Solutions:**
1. Check if transaction shows ✓ (processed)
2. Look for the transfer in Banking → Bank Transfers
3. If processed incorrectly, reverse the transfer first
4. Then re-process with correct settings

#### Amounts Don't Match

**Problem:** Two transactions should match but amounts are slightly different

**Causes:**
- Exchange rate differences
- Bank fees deducted
- Rounding differences

**Solutions:**
1. Check for bank fee transactions
2. Use Manual Adjustment (MA) for small discrepancies
3. Process as two separate transactions if amounts truly differ

### Debug Mode

To see detailed processing information:

1. Enable debug mode in `process_statements.php`:
   ```php
   define('DEBUG_MODE', true);
   ```

2. Check the logs in `var/log/bank_import.log`

3. Review validation messages in the browser console

## FAQ

### Q: Can I process transfers more than 2 days apart?

A: Not automatically. The matching window is configurable in the code but defaults to ±2 days. For older transfers, you can:
1. Manually create the bank transfer in FrontAccounting
2. Mark both transactions as processed

### Q: What happens if I process the wrong pair?

A: You can reverse the process:
1. Delete the bank transfer in FrontAccounting (Banking → Bank Transfers)
2. Mark the transactions as unprocessed in the database
3. Re-process with the correct pair

### Q: Can I batch process multiple transfers?

A: Currently, transfers are processed one pair at a time. Batch processing is planned for a future release.

### Q: Does the system handle fees?

A: Bank fees should be imported as separate transactions and processed as Spending (SP) type.

### Q: What if a transfer has three transactions?

A: Some banks show:
1. Debit from Account A
2. Credit to Account B
3. Fee transaction

Process the main pair (1 & 2) as a transfer, and process the fee (#3) separately as Spending.

### Q: Can I customize the matching window?

A: Yes! Edit `Services/PairedTransferProcessor.php`:
```php
private const MATCH_WINDOW_DAYS = 2; // Change to your preference
```

### Q: How do I handle foreign currency?

A: The module currently expects all amounts in the same currency. For multi-currency:
1. Convert amounts before import, OR
2. Process manually with exchange rate

### Q: Where are transactions stored?

A: Imported transactions are in the `bi_transactions` table. After processing, they link to FrontAccounting's standard tables.

### Q: Can I undo a processed transaction?

A: To undo:
1. Delete the bank transfer in FrontAccounting
2. Update `bi_transactions`: Set `processed=0` and `linked_transfer_id=NULL`
3. The transaction will reappear in the unprocessed list

### Q: What's the performance impact?

A: The module uses session caching for:
- Vendor lists (~95% faster)
- Operation types (instant access)
- This means minimal performance impact even with thousands of transactions

## Best Practices

### 1. Import Regularly
- Import statements weekly or monthly
- Don't let unprocessed transactions accumulate
- Regular imports make matching easier

### 2. Review Before Processing
- Always verify the match is correct
- Check amounts, dates, and accounts
- Look for unusual patterns

### 3. Use Consistent Naming
- Configure your banks to use consistent descriptions
- Makes visual identification easier
- Helps with future automated matching

### 4. Handle Fees Separately
- Bank fees are usually separate transactions
- Process fees as Spending (SP)
- Don't include fees in transfer amounts

### 5. Document Special Cases
- Keep notes on unusual transfers
- Document manual adjustments
- Create a reference guide for your organization

## Support

For issues or questions:

- Check the [Architecture Documentation](ARCHITECTURE.md)
- Review test cases in `tests/unit/`
- Contact: Kevin Fraser

---

**Version:** 1.0.0  
**Last Updated:** 2025-01-18  
**License:** MIT
