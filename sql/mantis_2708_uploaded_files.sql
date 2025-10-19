-- Mantis #2708: Store Uploaded Bank Files
-- Add table to track uploaded files

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

-- Link uploaded files to statements
CREATE TABLE IF NOT EXISTS `0_bi_file_statements` (
    `file_id`       INTEGER NOT NULL,
    `statement_id`  INTEGER NOT NULL,
    PRIMARY KEY(`file_id`, `statement_id`),
    FOREIGN KEY (`file_id`) REFERENCES `0_bi_uploaded_files`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`statement_id`) REFERENCES `0_bi_statements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
