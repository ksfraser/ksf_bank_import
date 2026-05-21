<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database\Migrations;

use Ksfraser\FaBankImport\Infrastructure\Database\Migration;

/**
 * Migration 001: Add occurrence_count to bi_partners_data
 * 
 * Adds occurrence_count column to track frequency of partner matches.
 * Used for occurrence-based scoring multiplier.
 */
final class Migration001AddOccurrenceCount implements Migration
{
    private const TABLE_PREFIX = '0_';
    private const TABLE = self::TABLE_PREFIX . 'bi_partners_data';

    public function name(): string
    {
        return '20260416_140000_add_occurrence_count';
    }

    public function version(): string
    {
        return '001';
    }

    public function description(): string
    {
        return 'Add occurrence_count to bi_partners_data for frequency-based scoring';
    }

    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            ADD COLUMN `occurrence_count` INTEGER DEFAULT 1 AFTER `data`
        ");

        // Ensure not null
        $pdo->exec("
            UPDATE `" . self::TABLE . "` SET `occurrence_count` = 1 WHERE `occurrence_count` IS NULL
        ");

        // Add indexes
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            ADD INDEX `idx_partner_type_data` (`partner_type`, `data`)
        ");

        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            ADD INDEX `idx_occurrence_count` (`occurrence_count` DESC)
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            DROP INDEX IF EXISTS `idx_partner_type_data`,
            DROP INDEX IF EXISTS `idx_occurrence_count`,
            DROP COLUMN IF EXISTS `occurrence_count`
        ");
    }
}
