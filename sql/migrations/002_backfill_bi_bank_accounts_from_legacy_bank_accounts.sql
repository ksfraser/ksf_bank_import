-- Backfill module-owned bank metadata from legacy modified FA core table.
--
-- Context:
-- Some PROD installs historically added OFX/Intuit identifier columns directly
-- onto FA's core `0_bank_accounts` table (ACCTID/BANKID/ACCTTYPE/CURDEF/intu_bid).
-- This module now stores those values in `0_bi_bank_accounts` keyed by bank_accounts.id.
--
-- Notes:
-- - This script assumes your company table prefix is `0_`.
-- - Run this against the *company database*.
-- - This script does not drop or alter `0_bank_accounts`.

CREATE TABLE IF NOT EXISTS `0_bi_bank_accounts` (
  `bank_account_id` SMALLINT(6) NOT NULL,
  `updated_ts`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `intu_bid`        VARCHAR(16) NULL,
  `bankid`          VARCHAR(16) NULL,
  `acctid`          VARCHAR(32) NULL,
  `accttype`        VARCHAR(32) NULL,
  `curdef`          VARCHAR(3) NULL,
  PRIMARY KEY (`bank_account_id`),
  INDEX `idx_acctid` (`acctid`),
  INDEX `idx_bankid` (`bankid`),
  INDEX `idx_intu_bid` (`intu_bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert any rows not yet present.
INSERT INTO `0_bi_bank_accounts` (`bank_account_id`, `intu_bid`, `bankid`, `acctid`, `accttype`, `curdef`)
SELECT b.`id`, b.`intu_bid`, b.`BANKID`, b.`ACCTID`, b.`ACCTTYPE`, b.`CURDEF`
FROM `0_bank_accounts` b
LEFT JOIN `0_bi_bank_accounts` bb ON bb.`bank_account_id` = b.`id`
WHERE bb.`bank_account_id` IS NULL
  AND (
    (b.`ACCTID` IS NOT NULL AND b.`ACCTID` <> '')
    OR (b.`BANKID` IS NOT NULL AND b.`BANKID` <> '')
    OR (b.`intu_bid` IS NOT NULL AND b.`intu_bid` <> '')
  );

-- Optional: if you want to refresh missing values for rows that already exist,
-- use an UPSERT. Kept commented to avoid accidental overwrites.
-- INSERT INTO `0_bi_bank_accounts` (`bank_account_id`, `intu_bid`, `bankid`, `acctid`, `accttype`, `curdef`)
-- SELECT b.`id`, b.`intu_bid`, b.`BANKID`, b.`ACCTID`, b.`ACCTTYPE`, b.`CURDEF`
-- FROM `0_bank_accounts` b
-- WHERE (b.`ACCTID` IS NOT NULL AND b.`ACCTID` <> '')
--    OR (b.`BANKID` IS NOT NULL AND b.`BANKID` <> '')
--    OR (b.`intu_bid` IS NOT NULL AND b.`intu_bid` <> '')
-- ON DUPLICATE KEY UPDATE
--   `intu_bid` = CASE WHEN (`intu_bid` IS NULL OR `intu_bid` = '') THEN VALUES(`intu_bid`) ELSE `intu_bid` END,
--   `bankid`   = CASE WHEN (`bankid`   IS NULL OR `bankid`   = '') THEN VALUES(`bankid`)   ELSE `bankid`   END,
--   `acctid`   = CASE WHEN (`acctid`   IS NULL OR `acctid`   = '') THEN VALUES(`acctid`)   ELSE `acctid`   END,
--   `accttype` = CASE WHEN (`accttype` IS NULL OR `accttype` = '') THEN VALUES(`accttype`) ELSE `accttype` END,
--   `curdef`   = CASE WHEN (`curdef`   IS NULL OR `curdef`   = '') THEN VALUES(`curdef`)   ELSE `curdef`   END;
