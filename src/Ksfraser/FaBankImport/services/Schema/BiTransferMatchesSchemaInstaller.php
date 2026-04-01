<?php

namespace Ksfraser\FaBankImport\Services\Schema;

use Ksfraser\FaBankImport\Schema\BiTransferMatchesSchema;
use Ksfraser\ModulesDAO\Schema\DatabaseSchemaToolsTrait as SharedDatabaseSchemaToolsTrait;

class BiTransferMatchesSchemaInstaller
{
    use SharedDatabaseSchemaToolsTrait;

    /** @var string */
    private $tablePrefix;

    public function __construct(callable $query, callable $escape, callable $numRows, $tablePrefix = '')
    {
        $this->initSchemaTools($query, $escape, $numRows);
        $this->tablePrefix = (string)$tablePrefix;
    }

    public function ensureTable()
    {
        $descriptor = BiTransferMatchesSchema::descriptor();
        $table = $this->ensureTableFromDescriptor(
            $descriptor,
            $this->tablePrefix,
            'Failed to ensure bi_transfer_matches table'
        );

        $this->ensureIndexesFromDescriptor($table, $descriptor);
    }
}
