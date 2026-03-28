-- Create duplicate rules whitelist table for user-configured duplicate policies
--
-- Context:
-- The duplicate detection service uses three-level matching:
--  Level 1: Direct code match (transactionCode + acctid) - AUTHORITATIVE
--  Level 2: Fuzzy match (date + amount ± $0.01 + merchant/memo) - FALLBACK
--  Level 3: Whitelist rules (user policy) - OPTIONAL FOR LEVEL 2
--
-- This table stores patterns that users have whitelisted to allow known-safe duplicates.
-- Examples:
--  - SHOPPERS% → Retail chain with frequent same-day purchases
--  - ATM% → ATM withdrawals (legitimate repeats)
--  - PAYROLL% → Recurring payroll (should be once per period, but flagged for review)
--
-- Notes:
-- - This script assumes your company table prefix is `0_`.
-- - Run this against the *company database*.
-- - Patterns support SQL LIKE syntax: %, _ wildcards
-- - Multiple patterns can be OR'd together: SHOPPERS%|LOBLAWS% (pipe-separated)
-- - Indexes added for merchant_pattern and category for fast lookups
-- - Rules are NOT applied to Level 1 exact matches (only Level 2 fuzzy)

CREATE TABLE IF NOT EXISTS `0_bi_duplicate_rules` (
  `id`                  INT(11)         NOT NULL AUTO_INCREMENT,
  `merchant_pattern`    VARCHAR(255)    NOT NULL COMMENT 'SQL LIKE pattern (e.g., SHOPPERS%, %LOBLAWS%|%RETAIL%)',
  `category`            VARCHAR(50)     NULL COMMENT 'Categorize rule type: RETAIL, ATM, PAYROLL, SUBSCRIPTION, etc.',
  `rule_name`           VARCHAR(100)    NOT NULL COMMENT 'Human-readable name (e.g., "Shoppers Repeat Purchases")',
  `allow_duplicates`    TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1 = Allow duplicates, 0 = Flag for review',
  `active`              TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1 = Active, 0 = Disabled',
  `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes`               TEXT            NULL COMMENT 'Admin notes on this rule',
  PRIMARY KEY (`id`),
  INDEX `idx_merchant_pattern` (`merchant_pattern`),
  INDEX `idx_category` (`category`),
  INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common default rules that most users will want
INSERT INTO `0_bi_duplicate_rules` (
  `merchant_pattern`,
  `category`,
  `rule_name`,
  `allow_duplicates`,
  `active`,
  `notes`
) VALUES
-- Retail chains with frequent same-day purchases (legitimate repeats)
(
  'SHOPPERS%',
  'RETAIL',
  'Shoppers Drug Mart - Multiple Purchases',
  1,
  1,
  'Shoppers locations often appear for multiple same-day purchases (pharmacy, grocery, etc.). These are legitimate repeats.'
),
-- ATM withdrawals (same ATM same day is normal)
(
  'ATM%',
  'ATM',
  'ATM Withdrawals',
  1,
  1,
  'ATM withdrawals can occur multiple times per day at same or different locations. These are normal.'
),
-- Subscription services (may process multiple times)
(
  '%SUBSCRIPTION%|%RECURRING%',
  'SUBSCRIPTION',
  'Subscription & Recurring Services',
  1,
  1,
  'Subscriptions and recurring services may process duplicates (e.g., authorization + settlement). These should be flagged for review anyway.'
),
-- Large retail chains with multiple locations
(
  '%LOBLAWS%|%WALMART%|%COSTCO%|%TARGET%',
  'RETAIL',
  'Major Retail Chains - Multiple Purchases',
  1,
  1,
  'Major retail chains with multiple same-day purchases are normal.'
),
-- Payroll (should typically be once per period, but flag for manual review)
(
  'PAYROLL%|SALARY%|WAGES%',
  'PAYROLL',
  'Payroll & Wage Deposits',
  0,
  1,
  'Payroll deposits should typically be once per pay period. Duplicates are suspicious and should be reviewed.'
),
-- Gas stations (multiple cards or pump authorization + charge)
(
  'GAS%|FUEL%|PETRO%',
  'GAS_STATION',
  'Gas Station Purchases',
  1,
  1,
  'Gas stations may show authorization + charge on same day or near-simultaneous transactions.'
) ON DUPLICATE KEY UPDATE
  `category` = VALUES(`category`),
  `allow_duplicates` = VALUES(`allow_duplicates`),
  `notes` = VALUES(`notes`);
