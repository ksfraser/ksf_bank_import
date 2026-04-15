<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Database\Migrations;

use Ksfraser\FaBankImport\Contracts\Migration;

/**
 * Migration 002: Add last_matched_ts to bi_partners_data
 * 
 * Adds last_matched_ts column to track when each partner was last matched.
 * Used for recency-based scoring decay.
 */
final class Migration002AddLastMatchedTs implements Migration
{
    private const TABLE_PREFIX = '0_';
    private const TABLE = self::TABLE_PREFIX . 'bi_partners_data';

    public function version(): string
    {
        return '002';
    }

    public function description(): string
    {
        return 'Add last_matched_ts to bi_partners_data for recency-based scoring';
    }

    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            ADD COLUMN `last_matched_ts` DATETIME NULL DEFAULT NULL AFTER `occurrence_count`
        ");

        // Add index for timestamp queries
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            ADD INDEX `idx_last_matched_ts` (`last_matched_ts`)
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `" . self::TABLE . "` 
            DROP INDEX IF EXISTS `idx_last_matched_ts`,
            DROP COLUMN IF EXISTS `last_matched_ts`
        ");
    }
}
