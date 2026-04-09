DROP TABLE IF EXISTS `0_bi_statements`;
DROP TABLE IF EXISTS `0_bi_transactions`;
DROP TABLE IF EXISTS `0_bi_partners_data`;

CREATE TABLE `0_bi_statements` (
    `id`			INTEGER NOT NULL AUTO_INCREMENT,
    `bank`			VARCHAR(64),
    `account`			VARCHAR(64),
    `currency`			VARCHAR(3),
    `startBalance`		DOUBLE,
    `endBalance`		DOUBLE,
    `smtDate`			DATE,
    `number`			INTEGER,
    `seq`			INTEGER,
    `statementId`		VARCHAR(64),
    PRIMARY KEY(`id`),
    CONSTRAINT `unique_smt` UNIQUE(`bank`, `statementId`)
);

CREATE TABLE `0_bi_transactions` (
    `id`			INTEGER NOT NULL AUTO_INCREMENT,
    `smt_id`			INTEGER NOT NULL,
    
    `valueTimestamp`		DATE,
    `entryTimestamp`		DATE,
    `account`			VARCHAR(24),
    `accountName`		VARCHAR(60),
    `transactionType`		VARCHAR(3),
    `transactionCode`		VARCHAR(255),
    `transactionCodeDesc`	VARCHAR(32),
    `transactionDC`		VARCHAR(2),
    `transactionAmount`		DOUBLE,
    `transactionTitle`		VARCHAR(256),

-- information
    `status`			INTEGER default 0,
    `matchinfo`			VARCHAR(256),

-- settled info
    `fa_trans_type`		INTEGER default 0,
    `fa_trans_no`		INTEGER default 0,
-- transaction info
    `fitid`			VARCHAR(32),
    `acctid`			VARCHAR(32),
    `merchant`			VARCHAR(64),
    `category`			VARCHAR(64),
    `sic`			VARCHAR(64),
    `memo`			VARCHAR(64),
    `checknumber`		INTEGER,
    PRIMARY KEY(`id`)
);


CREATE TABLE `0_bi_partners_data` (
    `partner_id`		INTEGER,
    `partner_detail_id`		INTEGER,
    `partner_type`		INTEGER,
    `data`			varchar(256),
    CONSTRAINT `idx` UNIQUE(`partner_id`, `partner_detail_id`, `partner_type`)
);

-- Phase-1: Duplicate Transaction Staging Table (Story 1)
-- Table for reviewing and deciding on potential duplicate transactions

CREATE TABLE IF NOT EXISTS `0_bi_transactions_dupe` (
    `duplicate_id`          INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Core transaction fields
    `transaction_code`      VARCHAR(50) NOT NULL,
    `trans_date`            DATE NOT NULL,
    `amount`                DECIMAL(19, 4) NOT NULL,
    `counterparty_name`     VARCHAR(250),
    `description`           TEXT,
    `reference_number`      VARCHAR(100),
    `bank_account_id`       INT NOT NULL,
    `partner_type`          ENUM('SUPPLIER', 'CUSTOMER', 'BANK_TRANSFER', 'QUICK_ENTRY'),
    `bank_code`             VARCHAR(20),
    
    -- Matching/detection fields
    `matched_to_code`       VARCHAR(50),
    `confidence_score`      DECIMAL(5, 2),
    `match_type`            ENUM('EXACT_MATCH', 'FUZZY_MATCH', 'CODE_AND_AMOUNT'),
    
    -- Decision tracking
    `decision_status`       ENUM('PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE') DEFAULT 'PENDING',
    `decided_by`            VARCHAR(100),
    `decided_at`            DATETIME NULL,
    `reason`                TEXT,
    `notes`                 TEXT,
    
    -- Timestamps
    `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints & Indexes
    UNIQUE KEY `uq_duplicate_pair` (`transaction_code`, `matched_to_code`),
    
    INDEX `idx_transaction_code` (`transaction_code`),
    INDEX `idx_trans_date` (`trans_date`),
    INDEX `idx_decision_status` (`decision_status`),
    INDEX `idx_bank_account_id` (`bank_account_id`),
    INDEX `idx_confidence_score` (`confidence_score`),
    INDEX `idx_review_dashboard` (`decision_status`, `confidence_score` DESC, `trans_date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit table for decision history tracking (compliance & audit trail)
CREATE TABLE IF NOT EXISTS `0_bi_transactions_dupe_audit` (
    `audit_id`              INT AUTO_INCREMENT PRIMARY KEY,
    `duplicate_id`          INT NOT NULL,
    `decision_status`       VARCHAR(50),
    `decided_by`            VARCHAR(100),
    `reason`                TEXT,
    `changed_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_duplicate_id` (`duplicate_id`),
    INDEX `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints after tables exist (handles timing and schema issues)
-- FK to bi_transactions_dupe (parent table)
ALTER TABLE `0_bi_transactions_dupe_audit`
ADD CONSTRAINT `fk_audit_duplicate` 
FOREIGN KEY (`duplicate_id`) 
REFERENCES `0_bi_transactions_dupe`(`duplicate_id`) 
ON DELETE CASCADE;

-- Dashboard view for pending duplicate reviews
DROP VIEW IF EXISTS `0_v_pending_duplicates`;
CREATE VIEW `0_v_pending_duplicates` AS
SELECT 
    `duplicate_id`,
    `transaction_code`,
    `trans_date`,
    `amount`,
    `counterparty_name`,
    `matched_to_code`,
    `confidence_score`,
    `match_type`,
    `bank_account_id`,
    `partner_type`,
    `created_at`
FROM `0_bi_transactions_dupe`
WHERE `decision_status` = 'PENDING'
ORDER BY `confidence_score` DESC, `trans_date` DESC;
