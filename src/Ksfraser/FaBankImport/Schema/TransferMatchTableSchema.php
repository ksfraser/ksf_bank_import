<?php

namespace Ksfraser\FaBankImport\Schema;

use Ksfraser\Schema\TableSchemaDescriptor;

class TransferMatchTableSchema
{
    public static function descriptor(string $tableName): TableSchemaDescriptor
    {
        $createSql = "CREATE TABLE `{$tableName}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `debit_transaction_id` INT(11) NOT NULL,
            `credit_transaction_id` INT(11) NOT NULL,
            `from_transaction_id` INT(11) NULL,
            `to_transaction_id` INT(11) NULL,
            `match_status` VARCHAR(16) NOT NULL DEFAULT 'candidate',
            `match_confidence` DECIMAL(5,2) NULL,
            `match_group` VARCHAR(64) NULL,
            `requires_review` INT(1) NOT NULL DEFAULT 0,
            `source` VARCHAR(32) NULL DEFAULT 'auto',
            `reason_code` VARCHAR(64) NULL,
            `notes` TEXT NULL,
            `suggested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `confirmed_at` DATETIME NULL,
            `confirmed_by` VARCHAR(64) NULL,
            `updated_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_debit_credit` (`debit_transaction_id`,`credit_transaction_id`),
            KEY `idx_status` (`match_status`),
            KEY `idx_review` (`requires_review`),
            KEY `idx_debit` (`debit_transaction_id`),
            KEY `idx_credit` (`credit_transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        return new TableSchemaDescriptor(
            $tableName,
            $createSql,
            [
                'from_transaction_id' => 'INT(11) NULL',
                'to_transaction_id' => 'INT(11) NULL',
                'match_status' => "VARCHAR(16) NOT NULL DEFAULT 'candidate'",
                'match_confidence' => 'DECIMAL(5,2) NULL',
                'match_group' => 'VARCHAR(64) NULL',
                'requires_review' => 'INT(1) NOT NULL DEFAULT 0',
                'source' => "VARCHAR(32) NULL DEFAULT 'auto'",
                'reason_code' => 'VARCHAR(64) NULL',
                'notes' => 'TEXT NULL',
                'suggested_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                'confirmed_at' => 'DATETIME NULL',
                'confirmed_by' => 'VARCHAR(64) NULL',
                'updated_ts' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
            [
                'uniq_debit_credit' => 'UNIQUE KEY `uniq_debit_credit` (`debit_transaction_id`,`credit_transaction_id`)',
                'idx_status' => 'KEY `idx_status` (`match_status`)',
                'idx_review' => 'KEY `idx_review` (`requires_review`)',
                'idx_debit' => 'KEY `idx_debit` (`debit_transaction_id`)',
                'idx_credit' => 'KEY `idx_credit` (`credit_transaction_id`)',
            ]
        );
    }
}
