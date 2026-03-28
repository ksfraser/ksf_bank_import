-- Phase 2: Create bi_transactions_dupe table for duplicate review workflow
-- 
-- Purpose: Store transactions that are flagged as potential duplicates for manual review
-- Strategy: Full clone of bi_transactions + metadata fields for duplicate tracking
-- 
-- Difference Detection: Fields that differ are stored as CSV in 'fields_that_differ'
-- This allows highlighting differences in the UI without recalculation on review

CREATE TABLE IF NOT EXISTS `bi_transactions_dupe` (
  -- Original transaction fields (cloned from bi_transactions)
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  statement_id int(11) NOT NULL,
  valueTimestamp datetime DEFAULT NULL,
  transactionAmount decimal(20,6) DEFAULT NULL,
  merchant varchar(255) DEFAULT NULL,
  memo mediumtext DEFAULT NULL,
  reference varchar(255) DEFAULT NULL,
  transactionCode varchar(255) DEFAULT NULL,
  acctid varchar(50) DEFAULT NULL,
  parser_reference varchar(255) DEFAULT NULL,
  category varchar(50) DEFAULT NULL,
  
  -- Phase 2: Duplicate tracking metadata
  matching_bi_transaction_id int(11) DEFAULT NULL COMMENT 'FK to bi_transactions (the original/existing duplicate)',
  fields_that_differ varchar(500) DEFAULT NULL COMMENT 'CSV list of fields that differ: "memo,amount" or "merchant,memo,reference"',
  match_type ENUM('EXACT_CODE_MISMATCH', 'FUZZY_MATCH') DEFAULT NULL COMMENT 'Level 1 matches code but fields differ, or Level 2 fuzzy match',
  status ENUM('PENDING', 'CONFIRMED_DUPE', 'MOVED_TO_STATEMENT', 'REJECTED') DEFAULT 'PENDING',
  notes mediumtext DEFAULT NULL COMMENT 'User notes when reviewing duplicate',
  reviewed_by varchar(50) DEFAULT NULL,
  reviewed_at datetime DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for efficient querying
  KEY statement_id (statement_id),
  KEY matching_bi_transaction_id (matching_bi_transaction_id),
  KEY transactionCode_acctid (transactionCode, acctid),
  KEY status (status),
  KEY match_type (match_type),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Phase 2: Duplicate transaction review staging table';
