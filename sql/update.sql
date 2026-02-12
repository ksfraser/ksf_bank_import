-- NOTE: This file is executed by FrontAccounting's module activation.
-- It must be NON-DESTRUCTIVE. Do not DROP tables here.

CREATE TABLE IF NOT EXISTS `0_bi_statements` (
    `id`            INTEGER NOT NULL AUTO_INCREMENT,
    `updated_ts`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bank`          VARCHAR(64),
    `account`       VARCHAR(64),
    `currency`      VARCHAR(3),
    `startBalance`  DOUBLE,
    `endBalance`    DOUBLE,
    `smtDate`       DATE,
    `number`        INTEGER,
    `seq`           INTEGER,
    `statementId`   VARCHAR(64),
    `acctid`        VARCHAR(64),
    `fitid`         VARCHAR(64),
    `bankid`        VARCHAR(64),
    `intu_bid`      VARCHAR(64),
    PRIMARY KEY(`id`),
    CONSTRAINT `unique_smt` UNIQUE(`bank`, `statementId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `0_bi_transactions` (
    `id`                  INTEGER NOT NULL AUTO_INCREMENT,
    `updated_ts`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `smt_id`              INTEGER NOT NULL,

    `valueTimestamp`      DATE,
    `entryTimestamp`      DATE,
    `account`             VARCHAR(24),
    `accountName`         VARCHAR(60),
    `transactionType`     VARCHAR(3),
    `transactionCode`     VARCHAR(255),
    `transactionCodeDesc` VARCHAR(32),
    `transactionDC`       VARCHAR(2),
    `transactionAmount`   DOUBLE,
    `transactionTitle`    VARCHAR(256),

    -- information
    `status`              INTEGER DEFAULT 0,
    `matchinfo`           VARCHAR(256),

    -- settled info
    `fa_trans_type`       INTEGER DEFAULT 0,
    `fa_trans_no`         INTEGER DEFAULT 0,

    -- transaction info
    `fitid`               VARCHAR(32),
    `acctid`              VARCHAR(32),
    `bankid`              VARCHAR(64),
    `intu_bid`            VARCHAR(64),
    `merchant`            VARCHAR(64),
    `category`            VARCHAR(64),
    `sic`                 VARCHAR(64),
    `memo`                VARCHAR(64),
    `checknumber`         INTEGER,

    -- module state
    `matched`             INTEGER DEFAULT 0,
    `created`             INTEGER DEFAULT 0,
    `g_partner`           VARCHAR(32),
    `g_option`            VARCHAR(32),

    PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Partner keyword storage (used for matching / scoring)
CREATE TABLE IF NOT EXISTS `0_bi_partners_data` (
    `updated_ts`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `partner_id`        INTEGER NOT NULL,
    `partner_detail_id` INTEGER NOT NULL,
    `partner_type`      INTEGER NOT NULL,
    `data`              VARCHAR(256) NOT NULL,
    `occurrence_count`  INTEGER DEFAULT 1,
    CONSTRAINT `idx_partner_keyword` UNIQUE(`partner_id`, `partner_detail_id`, `partner_type`, `data`),
    INDEX `idx_partner_type_data` (`partner_type`, `data`),
    INDEX `idx_occurrence_count` (`occurrence_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Mantis #2708: Store Uploaded Bank Files
CREATE TABLE IF NOT EXISTS `0_bi_uploaded_files` (
    `id`                INTEGER NOT NULL AUTO_INCREMENT,
    `filename`          VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `file_path`         VARCHAR(512) NOT NULL,
    `file_size`         INTEGER NOT NULL,
    `file_type`         VARCHAR(100),
    `upload_date`       DATETIME NOT NULL,
    `upload_user`       VARCHAR(60) NOT NULL,
    `parser_type`       VARCHAR(50),
    `bank_account_id`   INTEGER,
    `statement_count`   INTEGER DEFAULT 0,
    `notes`             TEXT,
    PRIMARY KEY(`id`),
    INDEX `idx_upload_date` (`upload_date`),
    INDEX `idx_upload_user` (`upload_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `0_bi_file_statements` (
    `file_id`      INTEGER NOT NULL,
    `statement_id` INTEGER NOT NULL,
    PRIMARY KEY(`file_id`, `statement_id`),
    FOREIGN KEY (`file_id`) REFERENCES `0_bi_uploaded_files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`statement_id`) REFERENCES `0_bi_statements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Mantis #2708: Configuration Management System
CREATE TABLE IF NOT EXISTS `0_bi_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` varchar(20) NOT NULL DEFAULT 'string',
  `description` text,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `0_bi_config` (`config_key`, `config_value`, `config_type`, `description`, `category`, `is_system`) VALUES
('upload.check_duplicates', '0', 'boolean', 'Enable duplicate file detection', 'upload', 0),
('upload.duplicate_window_days', '90', 'integer', 'How many days back to check for duplicates', 'upload', 0),
('upload.duplicate_action', 'warn', 'string', 'Action on duplicate: allow, warn, block', 'upload', 0),
('upload.max_file_size', '10485760', 'integer', 'Maximum upload file size in bytes (10MB default)', 'upload', 0),
('upload.allowed_extensions', 'qfx,ofx,csv,mt940,sta', 'string', 'Comma-separated list of allowed file extensions', 'upload', 0),
('storage.retention_days', '730', 'integer', 'How many days to retain uploaded files (2 years default)', 'storage', 0),
('storage.compression_enabled', '0', 'boolean', 'Enable file compression for storage', 'storage', 0),
('logging.enabled', '1', 'boolean', 'Enable import logging', 'logging', 0),
('logging.level', 'info', 'string', 'Log level: debug, info, warning, error', 'logging', 0),
('logging.retention_days', '30', 'integer', 'How many days to retain logs', 'logging', 0),
('performance.batch_size', '100', 'integer', 'Number of transactions to process per batch', 'performance', 0),
('performance.memory_limit', '256M', 'string', 'PHP memory limit for imports', 'performance', 0),
('security.require_permission', 'SA_BANKFILEVIEW', 'string', 'Required permission for file management', 'security', 1),
('security.htaccess_enabled', '1', 'boolean', 'Protect upload directory with .htaccess', 'security', 1);

CREATE TABLE IF NOT EXISTS `0_bi_config_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `old_value` text,
  `new_value` text,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` varchar(60) NOT NULL,
  `change_reason` text,
  PRIMARY KEY (`id`),
  KEY `config_key` (`config_key`),
  KEY `changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PROD previously stored OFX/Intuit identifiers on 0_bank_accounts.
-- Store them in a module-owned table instead.
-- One FA bank account may have multiple OFX/Intuit identities (e.g. multiple cards).
CREATE TABLE IF NOT EXISTS `0_bi_bank_accounts` (
    `id`              INT NOT NULL AUTO_INCREMENT,
    `bank_account_id` SMALLINT(6) NOT NULL,
    `updated_ts`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `intu_bid`        VARCHAR(64) NOT NULL DEFAULT '',
    `bankid`          VARCHAR(64) NOT NULL DEFAULT '',
    `acctid`          VARCHAR(64) NOT NULL DEFAULT '',
    `accttype`        VARCHAR(32) NULL,
    `curdef`          VARCHAR(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `uniq_detected_identity` UNIQUE (`acctid`, `bankid`, `intu_bid`),
    INDEX `idx_bank_account_id` (`bank_account_id`),
    INDEX `idx_acctid` (`acctid`),
    INDEX `idx_bankid` (`bankid`),
    INDEX `idx_intu_bid` (`intu_bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
