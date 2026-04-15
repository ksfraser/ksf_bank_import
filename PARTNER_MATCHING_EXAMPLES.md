# Multi-Factor Scoring - Concrete Examples

This document shows how the proposed scoring algorithm handles your real-world patterns without special-case code.

## Example 1: Pre-Auth CC Payments (Always from Same Savings Account)

### Pattern Characteristics
- **Pattern**: "Pre-Auth" + "Credit Card" + "12345-67-890" (specific savings account)
- **Consistency**: Always from same account (until reconfig)
- **Partner**: ST_BANKTRANSFER to CIBC account or QE CC Payment

### Data Stored in bi_partners_data
```
After 10 Pre-Auth CC payments processed:

partner_id=1 (ST_BANKTRANSFER-VisaMC), data="pre-auth", data_type=substring, 
  occurrence_count=10, last_matched_ts=2026-04-14

partner_id=1 (ST_BANKTRANSFER-VisaMC), data="credit", data_type=keyword,
  occurrence_count=9

partner_id=1 (ST_BANKTRANSFER-VisaMC), data="card", data_type=keyword,
  occurrence_count=9

partner_id=1 (ST_BANKTRANSFER-VisaMC), data="12345678", data_type=account,
  occurrence_count=10, last_matched_ts=2026-04-14
```

### Scoring When New Pre-Auth CC Arrives (11th transaction)
```
Transaction:
  otherBankAccountName: "Credit Card Payment"
  transactionTitle: "Pre-Auth CC"
  otherBankAccount: "12345-67-890"
  transactionDC: "D"

Extracted:
  Substrings: ["Pre-Auth", "Credit Card", "CC"]
  Keywords: ["pre-auth", "credit", "card"]
  Account: "12345678"

Scoring Against Partner 1 (ST_BANKTRANSFER-VisaMC):
  ├─ Substring "pre-auth" matches: +100
  ├─ Keyword "credit" matches: +10
  ├─ Keyword "card" matches: +10
  ├─ Occurrence multiplier: 10 × 0.5 = +5
  ├─ Account match: "12345678" == "12345678" → +80
  ├─ Recency (matched 2026-04-14, today 2026-04-14): ×1.0 = no penalty
  ├─ Clustering bonus (3 factor matches): × (1 + 2×0.2) = ×1.4
  └─ Total: (100 + 10 + 10 + 5 + 80) × 1.4 = 205 × 1.4 = 287 points

Confidence: 287 / 300 (max possible) = 95.7% ✓ AUTO-SELECT

Result: Partner 1 (ST_BANKTRANSFER) auto-selected with 95.7% confidence
Next time: occurrence_count incremented to 11, score will be even higher
```

---

## Example 2: Manual CC Payments - Learning Cluster (Primary + Occasional)

### Pattern Characteristics
- **Primary**: Most manual CC payments from Account A (70%)
- **Occasional**: Some from Account B (25%)
- **Rare**: Very occasional from Account C (5%)
- **Challenge**: Need to differentiate despite same "Credit Card" pattern

### Data After Learning Multiple Transactions
```
ST_BANKTRANSFER-VisaMC:
  - data="credit", occurrence_count=50
  - data="card", occurrence_count=50
  - data="payment", occurrence_count=45

Account A (Primary):
  - data="11111111", data_type=account, occurrence_count=35, 
    last_matched_ts=2026-04-12 (recent)

Account B (Occasional):
  - data="22222222", data_type=account, occurrence_count=12,
    last_matched_ts=2026-04-05 (less recent)

Account C (Rare):
  - data="33333333", data_type=account, occurrence_count=2,
    last_matched_ts=2026-03-20 (old)
```

### Scoring When Manual CC Payment Arrives from Account A
```
Transaction:
  otherBankAccount: "11111111"

Score for Account A:
  ├─ Account match: +80
  ├─ Occurrence (35 × 0.5): +17.5
  ├─ Recency (2026-04-12 to 2026-04-14 = 2 days): ×0.99 (minimal decay)
  └─ Total: (80 + 17.5) × 0.99 = 96 points

Score for Account B:
  ├─ Account match: +80
  ├─ Occurrence (12 × 0.5): +6
  ├─ Recency (2026-04-05 to 2026-04-14 = 9 days): ×0.97 (small decay)
  └─ Total: (80 + 6) × 0.97 = 83 points

Score for Account C:
  ├─ Account match: +80
  ├─ Occurrence (2 × 0.5): +1
  ├─ Recency (2026-03-20 to 2026-04-14 = 25 days): ×0.93 (more decay)
  └─ Total: (80 + 1) × 0.93 = 75 points

Ranking: A (96) > B (83) > C (75)
Result: Account A selected with highest confidence
```

### What If Payment from B Arrives Hours Later?
```
Score for Account B:
  └─ Total: (80 + 6) × 0.98 = 84 points (recency updated to 9 hours now)

Score for Account A:
  └─ Total: (80 + 17.5) × 0.98 = 95 points (recency now ~9 hours, was 2 days)

Ranking: A (95) > B (84)
Result: A still wins, but gap narrows

On Save: Account B occurrence_count → 13, last_matched_ts → NOW()
Next B payment will score higher due to recency boost
```

---

## Example 3: Square Up Deposits - Account-Based Differentiation (Your CIBC vs Marcia's Manulife)

### Pattern Characteristics
- **Your Deposits**: CIBC bank account, frequent (weekly)
- **Marcia's Deposits**: Manulife bank account, occasional (monthly)
- **Same substring**: "Square Up" appears in both
- **Differentiator**: Account number + recency

### Data After Several Months
```
Partner 1001 (Bank Transfer to FHS Equity via CIBC):
  - data="square", occurrence_count=47, last_matched_ts=2026-04-12
  - data="up", occurrence_count=47, last_matched_ts=2026-04-12
  - data="09876543", data_type=account, 
    occurrence_count=47, last_matched_ts=2026-04-12 (RECENT, FREQUENT)

Partner 2045 (Bank Transfer to Marcia Equity via Manulife):
  - data="square", occurrence_count=12, last_matched_ts=2026-04-01
  - data="up", occurrence_count=12, last_matched_ts=2026-04-01
  - data="55555555", data_type=account,
    occurrence_count=12, last_matched_ts=2026-04-01 (LESS FREQUENT, STALE)
```

### Scenario A: Square Up Deposit to Your CIBC Account
```
Transaction:
  otherBankAccount: "09876543"
  transactionTitle: "Square Up Deposit"

Score for Partner 1001 (YOUR Square):
  ├─ Substring "square up": +100
  ├─ Keyword "square": +10
  ├─ Keyword "up": +10
  ├─ Occurrence (47 × 0.5): +23.5
  ├─ Account match: +80
  ├─ Recency (2026-04-12 to 2026-04-14 = 2 days): ×0.99
  ├─ Clustering (4 factors): × 1.6
  └─ Total: (100 + 10 + 10 + 23.5 + 80) × 1.6 × 0.99 = 245 × 1.59 = 390 points

Score for Partner 2045 (MARCIA'S Square):
  ├─ Substring "square up": +100
  ├─ Keyword "square": +10
  ├─ Keyword "up": +10
  ├─ Occurrence (12 × 0.5): +6
  ├─ Account match: NO (-account mismatch)
  ├─ Recency (2026-04-01 to 2026-04-14 = 13 days): ×0.96
  ├─ Clustering (3 factors): × 1.4
  └─ Total: (100 + 10 + 10 + 6) × 1.4 × 0.96 = 126 × 1.344 = 169 points

Ranking: YOUR account (390) >> MARCIA'S (169)
Confidence: 390 / 450 = 86.7% ✓ AUTO-SELECT
Result: Your CIBC account selected with high confidence
```

### Scenario B: Square Up Deposit to Marcia's Manulife Account
```
Transaction:
  otherBankAccount: "55555555" (Manulife)
  transactionTitle: "Square Up Payment"

Score for Partner 2045 (MARCIA'S Square):
  ├─ Substring match: +100
  ├─ Keywords: +20
  ├─ Occurrence (12 × 0.5): +6
  ├─ Account match: +80
  ├─ Recency (2026-04-01 to 2026-04-14 = 13 days): ×0.96
  ├─ Clustering: × 1.4
  └─ Total: 206 × 1.344 × 0.96 = 266 points

Score for Partner 1001 (YOUR Square):
  ├─ Substring match: +100
  ├─ Keywords: +20
  ├─ Occurrence (47 × 0.5): +23.5
  ├─ Account match: NO (09876543 ≠ 55555555): -0
  ├─ Recency (2026-04-12 to 2026-04-14 = 2 days): ×0.99
  ├─ Clustering: × 1.4
  └─ Total: 143.5 × 1.4 × 0.99 = 199 points

Ranking: MARCIA'S account (266) > YOUR account (199)
Confidence: 266 / 450 = 59.1% ✗ Below 75% threshold → Show suggestions

User sees options:
  1. Marcia's Manulife (59.1%) ← recommended
  2. Your CIBC (44.2%)

User can confirm for Marcia, and learning updates:
  - Marcia's occurrence_count → 13
  - Marcia's last_matched_ts → NOW()
  - Next time from Manulife, score will be ~300 (above 75% threshold)
```

---

## Example 4: Interest Payments (No Special Cases Needed)

### Pattern Characteristics
- **Interest Earned Credit**: QE Interest Income
- **Interest Paid Debit**: QE Interest Expense
- **Bank Pattern**: "Interest Paid" or "Interest" in memo
- **No special case code needed** - scoring learns it completely

### Data After Multiple Interest Transactions
```
QE Interest Paid (Debit):
  - data="interest paid", data_type=substring, 
    occurrence_count=47, last_matched_ts=2026-04-10
  - data="interest", data_type=keyword,
    occurrence_count=48, last_matched_ts=2026-04-10

QE Interest Earned (Credit):
  - data="interest earned", data_type=substring,
    occurrence_count=23, last_matched_ts=2026-04-12
  - data="interest", data_type=keyword,
    occurrence_count=23, last_matched_ts=2026-04-12
```

### When Interest Payment Arrives (Debit)
```
Transaction:
  transactionTitle: "Monthly Interest Paid"
  transactionDC: "D"

Score for QE Interest Paid:
  ├─ Substring "interest paid": +100
  ├─ Occurrence (47 × 0.5): +23.5
  ├─ Recency (2026-04-10 to 2026-04-14 = 4 days): ×0.99
  └─ Total: (100 + 23.5) × 0.99 = 122 points

Score for QE Interest Earned:
  ├─ Substring "interest earned": +100 (matches pattern but wrong type)
  ├─ Occurrence (23 × 0.5): +11.5
  ├─ Recency (2026-04-12 to 2026-04-14 = 2 days): ×0.99
  └─ Total: (100 + 11.5) × 0.99 = 110 points

Ranking: Interest Paid (122) > Interest Earned (110)
Result: QE Interest Paid auto-selected
```

**No special case code!** The "Interest Paid" substring naturally scores highest when:
1. It's a debit (DC="D")
2. The substring appears
3. It has high occurrence from previous learning

---

## Example 5: E-Transfer (Context-Aware Without Special Cases)

### Scenario A: E-Transfer Credit (Receiving from Customer)
```
Transaction:
  otherBankAccountName: "John Smith Company"
  transactionTitle: "E-Transfer Received"
  transactionDC: "C" (Credit)

Extracted:
  Substrings: ["E-Transfer", "John Smith"]
  Keywords: ["e-transfer", "john", "smith", "company"]

Scoring:
- If "John Smith" → Customer entry exists with occurrence_count=15
- If "E-Transfer" → Generic substring with multiple partners
  
Top candidates:
  1. Customer: John Smith (based on name match)
  2. QE: Payment Received (based on e-transfer pattern)

Result: Customer "John Smith" scores highest due to strong name match
```

### Scenario B: E-Transfer Debit (Sending to Supplier)
```
Transaction:
  otherBankAccountName: "ACME Supplies Ltd"
  transactionTitle: "E-Transfer Sent"
  transactionDC: "D" (Debit)

Same algorithm:
- "ACME Supplies" → Supplier entry with occurrence_count=22
- "E-Transfer" → Generic pattern

Result: Supplier "ACME Supplies" scores highest due to name match
```

**No special case code needed!** The transaction data itself (name, debit/credit) drives the scoring.

---

## Summary: Why Multi-Factor Scoring > Special Cases

| Pattern | With Special Cases | With Multi-Factor Scoring |
|---------|-------------------|---------------------------|
| **Interest** | `if (preg_match('/interest/')) { QE_INTEREST }`| Learned from past 20+ transactions, auto-match |
| **CC Payments** | Need separate code for account clustering | Single account factor handles primary + occasional accounts |
| **Square Up Differentiation** | Need account + name checking | Account + recency factors differentiate automatically |
| **E-Transfer** | Need direction check (send/receive) | Name + transaction text patterns handle it |
| **Future Patterns** | Add new special case code | No code changes - patterns learned automatically |
| **Maintenance Burden** | Special cases accumulate (Interest, CC, Bank Charges, ...) | Single algorithm handles all |

---

## Tuning Parameters for Your Specific Patterns

```php
$weights = array(
    // Strong signals (account-based patterns matter most)
    'substring' => 100,      // e.g., "Credit Card", "Pre-Auth", "Square Up"
    'account' => 80,         // Account number is very discriminating for CC
    
    // Medium signals
    'keyword' => 10,         // Individual words: "interest", "credit", "card"
    'occurrence' => 0.5,     // Learned frequency (50% multiplier)
    
    // Weak signals
    'recency' => 0.1,        // Recent matches better, but old patterns still work
    'clustering' => 0.2      // Multiple factors reinforce each other
);

// Recency decay: 1 year
$recency_decay_days = 365;

// Auto-select threshold
$auto_select_confidence = 75;  // 75% = 287/383 composite score
```

These tuning parameters naturally handle:
- Pre-Auth CC (high occurrence_count, always same account)
- Manual CC clusters (account differentiation by frequency + recency)
- Square Up (account differentiation)
- Interest (substring + debit/credit patterns)
- E-Transfer (name matching through keywords)
