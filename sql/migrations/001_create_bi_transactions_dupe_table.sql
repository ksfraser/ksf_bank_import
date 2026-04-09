-- SQL Migration: Create bi_transactions_dupe Table
-- Purpose: Staging table for duplicate transactions awaiting review/decision
-- Date: 2026-04-08
-- Phase: Phase-1 Duplicate Review System
--
-- This table stores transactions flagged as duplicates during import,
-- with audit columns tracking review decisions before posting to statement.

-- Idempotent: Safe to run multiple times
CREATE TABLE IF NOT EXISTS `bi_transactions_dupe` (
    -- Primary Key
    `duplicate_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (`duplicate_id`),

    -- Transaction Reference (from bi_transactions)
    `transaction_code` VARCHAR(100) NOT NULL COMMENT 'Unique code from bi_transactions',
    `trans_date` DATE NOT NULL COMMENT 'Transaction date',
    `amount` DECIMAL(14, 2) NOT NULL COMMENT 'Transaction amount',

    -- Transaction Details
    `counterparty_name` VARCHAR(255) NOT NULL COMMENT 'Vendor/customer name',
    `description` TEXT NULL COMMENT 'Transaction description from import',
    `reference_number` VARCHAR(100) NULL COMMENT 'Bank reference/serial number',
    `bank_account_id` INT UNSIGNED NOT NULL COMMENT 'FA bank account ID',
    
    -- Classification
    `partner_type` VARCHAR(50) NULL COMMENT 'SUPPLIER, CUSTOMER, BANK_TRANSFER, QUICK_ENTRY',
    `bank_code` VARCHAR(50) NULL COMMENT 'Code for bank/source identification',

    -- Duplicate Matching Details
    `matched_to_code` VARCHAR(100) NULL COMMENT 'Code of matched transaction',
    `confidence_score` DECIMAL(5, 2) NULL COMMENT 'Match confidence (0-100)',
    `match_type` VARCHAR(50) NULL COMMENT 'EXACT_MATCH, FUZZY_MATCH, CODE_AND_AMOUNT',

    -- Decision Tracking (Audit Trail)
    `decision_status` ENUM('PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE') 
        NOT NULL DEFAULT 'PENDING' COMMENT 'Review decision status',
    `decided_by` VARCHAR(255) NULL COMMENT 'User who made the decision',
    `decided_at` DATETIME NULL COMMENT 'When decision was made',
    `reason` VARCHAR(255) NULL COMMENT 'Brief reason for decision',
    `notes` TEXT NULL COMMENT 'Detailed notes on investigation/decision',

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When record was created',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',

    -- Unique Constraint: Prevent duplicate staging of same pair
    UNIQUE KEY `uk_transaction_duplicate` (`transaction_code`, `matched_to_code`),

    -- Indexes for query performance
    KEY `idx_transaction_code` (`transaction_code`),
    KEY `idx_trans_date` (`trans_date`),
    KEY `idx_decision_status` (`decision_status`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_confidence_score` (`confidence_score`),

    -- Composite index for dashboard queries
    KEY `idx_review_dashboard` (`decision_status`, `created_at`, `confidence_score`)

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Staging table for duplicate transactions awaiting review decision'
;

-- Indexes for better query performance
CREATE INDEX idx_decided_at ON `bi_transactions_dupe` (`decided_at`);

-- Audit table to track decision history (optional - for deep audit trail)
CREATE TABLE IF NOT EXISTS `bi_transactions_dupe_audit` (
    `audit_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `duplicate_id` BIGINT UNSIGNED NOT NULL COMMENT 'References bi_transactions_dupe',
    `old_decision_status` VARCHAR(50) NULL COMMENT 'Previous status',
    `new_decision_status` VARCHAR(50) NOT NULL COMMENT 'New status',
    `changed_by` VARCHAR(255) NOT NULL COMMENT 'User who made change',
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When changed',
    `change_reason` TEXT NULL COMMENT 'Why the change was made',
    
    KEY `idx_duplicate_id` (`duplicate_id`),
    KEY `idx_changed_at` (`changed_at`),
    CONSTRAINT `fk_audit_duplicate` FOREIGN KEY (`duplicate_id`) 
        REFERENCES `bi_transactions_dupe` (`duplicate_id`) ON DELETE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for duplicate transaction review decisions'
;

-- Enable foreign key constraints check
ALTER TABLE `bi_transactions_dupe` 
ADD CONSTRAINT `fk_bi_transactions_dupe_bank_account` 
FOREIGN KEY (`bank_account_id`) 
REFERENCES `0_bank_account` (`id`) ON DELETE RESTRICT;

-- Create view for pending duplicates dashboard
CREATE OR REPLACE VIEW `v_pending_duplicates` AS
SELECT 
    dtd.duplicate_id,
    dtd.transaction_code,
    dtd.trans_date,
    dtd.amount,
    dtd.counterparty_name,
    dtd.matched_to_code,
    dtd.confidence_score,
    dtd.created_at,
    ba.bank_name,
    dtd.decision_status
FROM `bi_transactions_dupe` dtd
LEFT JOIN `0_bank_account` ba ON dtd.bank_account_id = ba.id
WHERE dtd.decision_status = 'PENDING'
ORDER BY dtd.confidence_score DESC, dtd.created_at ASC;

-- Rollback Script (for development/testing - not typically used in production)
-- DROP TABLE IF EXISTS `bi_transactions_dupe_audit`;
-- DROP TABLE IF EXISTS `bi_transactions_dupe`;
-- DROP VIEW IF EXISTS `v_pending_duplicates`;
