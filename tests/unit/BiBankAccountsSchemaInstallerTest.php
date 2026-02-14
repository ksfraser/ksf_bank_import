<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\Schema\BiBankAccountsSchemaInstaller;

class BiBankAccountsSchemaInstallerTest extends TestCase
{
    public function testEnsureTableCreatesAndChecksExpectedIndexes(): void
    {
        $queries = array();

        $query = function ($sql, $error = '') use (&$queries) {
            $queries[] = $sql;

            if (strpos($sql, 'SHOW INDEX FROM `0_bi_bank_accounts`') !== false) {
                return 0; // index missing, force ensureIndex flow
            }
            if (strpos($sql, "SHOW TABLES LIKE '0_bi_bank_accounts'") !== false) {
                return 1; // table exists for index checks
            }

            return 1;
        };

        $escape = function ($value) {
            return "'" . addslashes($value) . "'";
        };

        $numRows = function ($result) {
            return (int)$result;
        };

        $installer = new BiBankAccountsSchemaInstaller($query, $escape, $numRows, '0_');
        $installer->ensureTable();

        $createSql = null;
        foreach ($queries as $sql) {
            if (strpos($sql, 'CREATE TABLE IF NOT EXISTS `0_bi_bank_accounts`') !== false) {
                $createSql = $sql;
                break;
            }
        }

        $this->assertNotNull($createSql, 'Expected CREATE TABLE query for prefixed bi_bank_accounts table.');
        $this->assertStringNotContainsString('CONSTRAINT `uniq_detected_identity` UNIQUE (`acctid`, `bankid`, `intu_bid`)', $createSql);

        $indexAdds = array_filter($queries, function ($sql) {
            return strpos($sql, 'ALTER TABLE `0_bi_bank_accounts` ADD INDEX') !== false;
        });

        $this->assertGreaterThanOrEqual(4, count($indexAdds), 'Expected index ensure operations for bi_bank_accounts table.');
    }

    public function testEnsureTableIncludesUniqueConstraintOnFreshCreate(): void
    {
        $queries = array();

        $query = function ($sql, $error = '') use (&$queries) {
            $queries[] = $sql;

            if (strpos($sql, "SHOW TABLES LIKE '0_bi_bank_accounts'") !== false) {
                return 0; // table does not exist yet
            }
            if (strpos($sql, 'SHOW INDEX FROM `0_bi_bank_accounts`') !== false) {
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

        $installer = new BiBankAccountsSchemaInstaller($query, $escape, $numRows, '0_');
        $installer->ensureTable();

        $createSql = null;
        foreach ($queries as $sql) {
            if (strpos($sql, 'CREATE TABLE IF NOT EXISTS `0_bi_bank_accounts`') !== false) {
                $createSql = $sql;
                break;
            }
        }

        $this->assertNotNull($createSql, 'Expected CREATE TABLE query for prefixed bi_bank_accounts table.');
        $this->assertStringContainsString('CONSTRAINT `uniq_detected_identity` UNIQUE (`acctid`, `bankid`, `intu_bid`)', $createSql);
    }
}
