# Quick Start Guide: Paired Bank Transfer Processing

## What's New?

The bank import module now automatically detects and matches bank transfers between your accounts (like transfers from Manulife, CIBC HISA to CIBC Savings, credit card payments, etc.)

## How to Use

### Step 1: Import Your Statements
Import statements from both accounts as usual.

### Step 2: Look for the Orange Highlight
When viewing the Process Statements page, paired transactions will be highlighted in **bright yellow with an orange border** and show:
```
⇄ PAIRED BANK TRANSFER DETECTED
```

### Step 3: Review the Paired Transaction
The system will show you details of the matching transaction:
- Account name
- Amount
- Date  
- Transaction type (Debit or Credit)

### Step 4: Process Both Sides Together
Click the button: **"Process Bank Transfer (Both Sides)"**

That's it! The system will:
- Create a single bank transfer entry in FrontAccounting
- Mark both imported transactions as processed
- Link both to the same GL transaction
- Show you a link to view the GL entry

## Example

**Your CIBC HISA statement shows:**
- Date: Jan 15, 2025
- Amount: -$1,000.00 (Debit)
- Description: "TRANSFER TO SAVINGS"

**Your CIBC Savings statement shows:**
- Date: Jan 15, 2025  
- Amount: +$1,000.00 (Credit)
- Description: "TRANSFER FROM HISA"

**The system will:**
1. Automatically detect these are paired
2. Highlight both transactions in orange
3. Show paired transaction details
4. Let you process both with one click

## What Makes Transactions "Paired"?

The system looks for transactions that match ALL of these:
- ✅ Same dollar amount
- ✅ Opposite type (one Debit, one Credit)
- ✅ Different bank accounts
- ✅ Dates within 2 days of each other
- ✅ Both unprocessed

## Common Scenarios

### ✅ Will Match
- Savings → Chequing transfers
- HISA → Savings transfers
- Credit card payments from your bank account
- Investment transfers between accounts
- Line of credit payments

### ❌ Won't Match
- Transfers with different amounts
- Transactions more than 2 days apart
- Transfers within the same account (impossible anyway!)
- Already processed transactions

## Tips

1. **Import both sides** before processing for best results
2. **Look for the orange highlight** - you can't miss it!
3. **Process same-day transfers immediately** for cleanest books
4. **Check the details** shown in the paired transaction box before clicking

## Troubleshooting

**Q: Why isn't my transfer showing as paired?**
- Check if dates are within 2 days
- Verify amounts match exactly
- Make sure one is Debit, other is Credit
- Confirm both are unprocessed

**Q: Can I still process one side manually?**
- Yes! The normal "Process Transaction" button still works
- Use this if you want to handle each side separately
- Or if the pairing isn't correct for some reason

**Q: What if I see multiple possible pairs?**
- The system shows the best match first
- Review the details carefully
- Process the correct one using the button

## Benefits

- ⚡ **Faster**: One click instead of two separate processes
- ✅ **Accurate**: No chance of mismatching sides
- 🔗 **Connected**: Both sides always link to same GL entry
- 👀 **Visible**: Can't miss paired transactions with bright highlighting

## Need Help?

If you see paired transactions but aren't sure about them:
1. Check both bank statements
2. Verify the dates and amounts match
3. Review the transaction descriptions
4. If in doubt, process manually using the regular buttons

---

**Note:** The system searches within a 2-day window (±2 days) to account for processing delays and weekends. Most same-day transfers will match immediately.
