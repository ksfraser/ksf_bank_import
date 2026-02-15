<?php

namespace Ksfraser\FaBankImport\Service\Schema;

require_once __DIR__ . '/../../Seed/BiConfigDefaultSeed.php';

use Ksfraser\FaBankImport\Schema\BiConfigHistorySchema;
use Ksfraser\FaBankImport\Schema\BiConfigSchema;
use Ksfraser\FaBankImport\Seed\BiConfigDefaultSeed;
use Ksfraser\ModulesDAO\Schema\DatabaseSchemaToolsTrait as SharedDatabaseSchemaToolsTrait;

/**
 * Ensures bank import configuration tables and seed data exist.
 */
class BiConfigSchemaInstaller
{
    use SharedDatabaseSchemaToolsTrait;

    /** @var string */
    private $tablePrefix;

    public function __construct(callable $query, $tablePrefix = '')
    {
        $this->tablePrefix = (string)$tablePrefix;
        $this->initSchemaTools($query, static function ($v) {
            return db_escape($v);
        }, static function ($res) {
            return db_num_rows($res);
        });
    }

    public function ensureTables(): void
    {
                $configTable = $this->ensureTableFromDescriptor(
                        BiConfigSchema::descriptor(),
                        $this->tablePrefix,
                        'Failed to ensure bi_config table'
                );
                $this->ensureIndexesFromDescriptor($configTable, BiConfigSchema::descriptor());

                $historyTable = $this->ensureTableFromDescriptor(
                        BiConfigHistorySchema::descriptor(),
                        $this->tablePrefix,
                        'Failed to ensure bi_config_history table'
                );
                $this->ensureIndexesFromDescriptor($historyTable, BiConfigHistorySchema::descriptor());

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $table = $this->tablePrefix . 'bi_config';
        $this->insertIgnoreRows($table, BiConfigDefaultSeed::rows(), 'Failed seeding bi_config defaults');
    }
}
