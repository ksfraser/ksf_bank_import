-- ============================================================================
-- Mantis #3178: Interest Transactions Incorrectly Recorded as Debit
-- ============================================================================
-- 
-- ISSUE: Some banks are sending Interest earned/promotional Interest 
--        transactions as Debit (D) when they should be Credit (C).
--
-- GOAL: Analyze production data to:
--       1. Identify which banks/accounts have this issue
--       2. Count affected transactions
--       3. Determine patterns (keywords, transaction codes)
--       4. Assess impact (total amounts, date ranges)
--
-- USAGE: Run this SQL against production database to gather data before
--        making code changes.
-- ============================================================================

-- Set current database (adjust as needed)
-- USE frontaccounting_prod;

-- ----------------------------------------------------------------------------
-- QUERY 1: Find Interest transactions marked as Debit
-- ----------------------------------------------------------------------------
-- Look for transactions with "interest" in title/memo that are marked as Debit
-- These are likely incorrect (interest should be Credit)
-- ----------------------------------------------------------------------------

SELECT 
    COUNT(*) as total_suspect_transactions,
    'Suspect Interest Debits' as description
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE t.transactionDC = 'D'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  );

-- ----------------------------------------------------------------------------
-- QUERY 2: Detailed list of suspect interest transactions
-- ----------------------------------------------------------------------------

SELECT 
    t.id,
    t.transDate,
    s.account as bank_account,
    t.transactionDC as DC,
    t.transactionAmount as amount,
    t.transactionTitle,
    t.transactionCodeDesc,
    t.transactionCode,
    t.memo,
    t.merchant,
    t.status,
    CASE 
        WHEN t.status = 0 THEN 'Pending'
        WHEN t.status = 1 THEN 'Processed'
        ELSE 'Unknown'
    END as status_label
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE t.transactionDC = 'D'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  )
ORDER BY t.transDate DESC, t.id DESC
LIMIT 100;

-- ----------------------------------------------------------------------------
-- QUERY 3: Group by bank account to identify problem accounts
-- ----------------------------------------------------------------------------

SELECT 
    s.account as bank_account,
    s.currency,
    COUNT(*) as suspect_count,
    SUM(ABS(t.transactionAmount)) as total_amount,
    MIN(t.transDate) as earliest_date,
    MAX(t.transDate) as latest_date
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE t.transactionDC = 'D'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  )
GROUP BY s.account, s.currency
ORDER BY suspect_count DESC;

-- ----------------------------------------------------------------------------
-- QUERY 4: Group by transaction code/description patterns
-- ----------------------------------------------------------------------------

SELECT 
    t.transactionCode,
    t.transactionCodeDesc,
    COUNT(*) as occurrence_count,
    SUM(ABS(t.transactionAmount)) as total_amount,
    GROUP_CONCAT(DISTINCT s.account) as affected_accounts
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE t.transactionDC = 'D'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  )
GROUP BY t.transactionCode, t.transactionCodeDesc
ORDER BY occurrence_count DESC;

-- ----------------------------------------------------------------------------
-- QUERY 5: Find ALL interest transactions (both Debit and Credit) for comparison
-- ----------------------------------------------------------------------------

SELECT 
    t.transactionDC as DC,
    COUNT(*) as transaction_count,
    SUM(ABS(t.transactionAmount)) as total_amount,
    AVG(ABS(t.transactionAmount)) as avg_amount,
    MIN(t.transDate) as earliest_date,
    MAX(t.transDate) as latest_date
FROM 0_bi_transactions t
WHERE (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
)
GROUP BY t.transactionDC
ORDER BY t.transactionDC;

-- ----------------------------------------------------------------------------
-- QUERY 6: Interest transactions by title/description pattern
-- ----------------------------------------------------------------------------

SELECT 
    SUBSTRING(t.transactionTitle, 1, 50) as title_pattern,
    t.transactionDC,
    COUNT(*) as count,
    SUM(ABS(t.transactionAmount)) as total_amount
FROM 0_bi_transactions t
WHERE (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
)
GROUP BY SUBSTRING(t.transactionTitle, 1, 50), t.transactionDC
HAVING count > 1
ORDER BY count DESC
LIMIT 50;

-- ----------------------------------------------------------------------------
-- QUERY 7: Check for promotional interest patterns
-- ----------------------------------------------------------------------------

SELECT 
    t.id,
    t.transDate,
    s.account,
    t.transactionDC,
    t.transactionAmount,
    t.transactionTitle,
    t.memo
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE (
    LOWER(t.transactionTitle) LIKE '%promotional%interest%'
    OR LOWER(t.transactionTitle) LIKE '%promo%interest%'
    OR LOWER(t.transactionTitle) LIKE '%bonus%interest%'
    OR LOWER(t.memo) LIKE '%promotional%interest%'
    OR LOWER(t.memo) LIKE '%promo%interest%'
    OR LOWER(t.memo) LIKE '%bonus%interest%'
)
ORDER BY t.transDate DESC
LIMIT 50;

-- ----------------------------------------------------------------------------
-- QUERY 8: Impact assessment - processed vs pending
-- ----------------------------------------------------------------------------

SELECT 
    CASE 
        WHEN t.status = 0 THEN 'Pending (Not Yet Processed)'
        WHEN t.status = 1 THEN 'Processed (Already in GL)'
        ELSE 'Unknown Status'
    END as processing_status,
    COUNT(*) as transaction_count,
    SUM(ABS(t.transactionAmount)) as total_amount
FROM 0_bi_transactions t
WHERE t.transactionDC = 'D'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  )
GROUP BY t.status
ORDER BY t.status;

-- ----------------------------------------------------------------------------
-- QUERY 9: Sample correct interest transactions (Credits) for comparison
-- ----------------------------------------------------------------------------

SELECT 
    t.id,
    t.transDate,
    s.account,
    t.transactionDC,
    t.transactionAmount,
    t.transactionTitle,
    t.transactionCode,
    t.transactionCodeDesc,
    t.memo
FROM 0_bi_transactions t
LEFT JOIN 0_bi_statements s ON t.smt_id = s.id
WHERE t.transactionDC = 'C'
  AND (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
  )
ORDER BY t.transDate DESC
LIMIT 20;

-- ----------------------------------------------------------------------------
-- QUERY 10: Year-over-year trend
-- ----------------------------------------------------------------------------

SELECT 
    YEAR(t.transDate) as year,
    MONTH(t.transDate) as month,
    t.transactionDC,
    COUNT(*) as count,
    SUM(ABS(t.transactionAmount)) as total_amount
FROM 0_bi_transactions t
WHERE (
    LOWER(t.transactionTitle) LIKE '%interest%'
    OR LOWER(t.transactionCodeDesc) LIKE '%interest%'
    OR LOWER(t.memo) LIKE '%interest%'
)
GROUP BY YEAR(t.transDate), MONTH(t.transDate), t.transactionDC
ORDER BY year DESC, month DESC, t.transactionDC;

-- ============================================================================
-- RECOMMENDED NEXT STEPS BASED ON RESULTS:
-- ============================================================================
-- 
-- 1. Review QUERY 2 output to confirm suspect transactions are indeed errors
-- 2. Check QUERY 3 to identify which bank accounts are affected
-- 3. Use QUERY 4 to identify transaction code patterns for automated correction
-- 4. Review QUERY 8 to assess impact (how many already processed vs pending)
-- 5. Use QUERY 9 to compare with correct interest transactions
-- 
-- CORRECTION STRATEGY:
-- - If pending (status = 0): Can correct before processing
-- - If processed (status = 1): May need GL adjustment/correction
-- 
-- CODE CHANGES:
-- - Add validation in parser to detect interest + debit combination
-- - Flag for manual review or auto-correct based on patterns
-- - Add to TransactionFilterService for identifying suspect transactions
-- 
-- ============================================================================
