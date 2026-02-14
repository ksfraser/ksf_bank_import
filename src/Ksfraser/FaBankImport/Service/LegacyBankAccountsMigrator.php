<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :LegacyBankAccountsMigrator [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for LegacyBankAccountsMigrator.
 */
namespace Ksfraser\FaBankImport\Service;

/**
 * Migrates legacy OFX/Intuit identifiers from FA bank_accounts table
 * into the module-owned bi_bank_accounts table.
 */
class LegacyBankAccountsMigrator
{
    /** @var callable */
    private $query;

    /** @var callable */
    private $escape;

    /** @var callable */
    private $numRows;

    /** @var string */
    private $tablePrefix;

    /**
     * @param callable $query   function(string $sql, string $error=''): mixed
     * @param callable $escape  function(string $value): string
     * @param callable $numRows function(mixed $result): int
     * @param string   $tablePrefix FA table prefix (e.g. "0_")
     */
    public function __construct(callable $query, callable $escape, callable $numRows, $tablePrefix = '')
    {
        $this->query = $query;
        $this->escape = $escape;
        $this->numRows = $numRows;
        $this->tablePrefix = (string)$tablePrefix;
    }

    public function migrate()
    {
        $faTable = $this->tablePrefix . 'bank_accounts';
        $biTable = $this->tablePrefix . 'bi_bank_accounts';

        if (!$this->tableExists($faTable) || !$this->tableExists($biTable)) {
            return;
        }

        $requiredLegacyCols = array('ACCTID', 'BANKID', 'ACCTTYPE', 'CURDEF', 'intu_bid');
        foreach ($requiredLegacyCols as $col) {
            if (!$this->columnExists($faTable, $col)) {
                return;
            }
        }

        $sql = "INSERT IGNORE INTO `{$biTable}` (`bank_account_id`, `intu_bid`, `bankid`, `acctid`, `accttype`, `curdef`)\n"
            . "\t\t\tSELECT b.`id`, IFNULL(b.`intu_bid`, ''), IFNULL(b.`BANKID`, ''), IFNULL(b.`ACCTID`, ''), b.`ACCTTYPE`, b.`CURDEF`\n"
            . "\t\t\tFROM `{$faTable}` b\n"
            . "\t\t\tWHERE (\n"
            . "\t\t\t\t(b.`ACCTID` IS NOT NULL AND b.`ACCTID` <> '')\n"
            . "\t\t\t\tOR (b.`BANKID` IS NOT NULL AND b.`BANKID` <> '')\n"
            . "\t\t\t\tOR (b.`intu_bid` IS NOT NULL AND b.`intu_bid` <> '')\n"
            . "\t\t\t  )";

        call_user_func($this->query, $sql, 'Failed migrating legacy bank_accounts OFX metadata into bi_bank_accounts');
    }

    private function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE " . call_user_func($this->escape, $table);
        $res = call_user_func($this->query, $sql, 'Failed checking table existence');
        return call_user_func($this->numRows, $res) > 0;
    }

    private function columnExists($table, $column)
    {
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE " . call_user_func($this->escape, $column);
        $res = call_user_func($this->query, $sql, 'Failed checking column existence');
        return call_user_func($this->numRows, $res) > 0;
    }
}
