<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\LegacyBankAccountsMigrator;

class LegacyBankAccountsMigratorTest extends TestCase
{
    public function testSkipsWhenTablesMissing(): void
    {
        $queries = array();

        $query = function ($sql, $error = '') use (&$queries) {
            $queries[] = $sql;

            // No tables found
            if (strpos($sql, 'SHOW TABLES LIKE') !== false) {
                return 0;
            }

            return 0;
        };

        $escape = function ($value) {
            return "'" . addslashes($value) . "'";
        };

        $numRows = function ($result) {
            return (int)$result;
        };

        $migrator = new LegacyBankAccountsMigrator($query, $escape, $numRows, '0_');
        $migrator->migrate();

        $this->assertCount(1, $queries);
        $this->assertStringContainsString("SHOW TABLES LIKE '0_bank_accounts'", $queries[0]);
    }

    public function testSkipsWhenRequiredLegacyColumnMissing(): void
    {
        $queries = array();

        $query = function ($sql, $error = '') use (&$queries) {
            $queries[] = $sql;

            if (strpos($sql, "SHOW TABLES LIKE '0_bank_accounts'") !== false) {
                return 1;
            }
            if (strpos($sql, "SHOW TABLES LIKE '0_bi_bank_accounts'") !== false) {
                return 1;
            }

            // Only ACCTID is missing; others would be present but migration should stop immediately.
            if (strpos($sql, "SHOW COLUMNS FROM `0_bank_accounts` LIKE 'ACCTID'") !== false) {
                return 0;
            }

            return 1;
        };

        $escape = function ($value) {
            return "'" . addslashes($value) . "'";
        };

        $numRows = function ($result) {
            return (int)$result;
        };

        $migrator = new LegacyBankAccountsMigrator($query, $escape, $numRows, '0_');
        $migrator->migrate();

        $last = end($queries);
        $this->assertStringContainsString("SHOW COLUMNS FROM `0_bank_accounts` LIKE 'ACCTID'", $last);

        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('INSERT IGNORE INTO `0_bi_bank_accounts`', $sql);
        }
    }

    public function testRunsInsertWhenTablesAndColumnsExist(): void
    {
        $queries = array();

        $query = function ($sql, $error = '') use (&$queries) {
            $queries[] = $sql;

            if (strpos($sql, 'SHOW TABLES LIKE') !== false) {
                return 1;
            }
            if (strpos($sql, 'SHOW COLUMNS FROM') !== false) {
                return 1;
            }

            return 1;
        };

        $escape = function ($value) {
            return "'" . addslashes($value) . "'";
        };

        $numRows = function ($result) {
            return (int)$result;
        };

        $migrator = new LegacyBankAccountsMigrator($query, $escape, $numRows, '0_');
        $migrator->migrate();

        $insertSql = null;
        foreach ($queries as $sql) {
            if (strpos($sql, 'INSERT IGNORE INTO `0_bi_bank_accounts`') !== false) {
                $insertSql = $sql;
                break;
            }
        }

        $this->assertNotNull($insertSql, 'Expected migration INSERT query to be executed');
        $this->assertStringContainsString('FROM `0_bank_accounts` b', $insertSql);
        $this->assertStringContainsString("(b.`ACCTID` IS NOT NULL AND b.`ACCTID` <> '')", $insertSql);
        $this->assertStringContainsString("(b.`BANKID` IS NOT NULL AND b.`BANKID` <> '')", $insertSql);
        $this->assertStringContainsString("(b.`intu_bid` IS NOT NULL AND b.`intu_bid` <> '')", $insertSql);
    }
}
