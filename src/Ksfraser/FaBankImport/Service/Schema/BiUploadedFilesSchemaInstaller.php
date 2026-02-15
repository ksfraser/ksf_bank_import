<?php

namespace Ksfraser\FaBankImport\Service\Schema;

use Ksfraser\FaBankImport\Schema\BiFileStatementsSchema;
use Ksfraser\FaBankImport\Schema\BiUploadedFilesSchema;
use Ksfraser\ModulesDAO\Schema\DatabaseSchemaToolsTrait as SharedDatabaseSchemaToolsTrait;

/**
 * Ensures uploaded-file tracking tables exist.
 */
class BiUploadedFilesSchemaInstaller
{
    use SharedDatabaseSchemaToolsTrait;

    /** @var callable */
    private $query;

    /** @var string */
    private $tablePrefix;

    public function __construct(callable $query, $tablePrefix = '')
    {
        $this->query = $query;
        $this->tablePrefix = (string)$tablePrefix;
        $this->initSchemaTools($query, static function ($v) {
            return db_escape($v);
        }, static function ($res) {
            return db_num_rows($res);
        });
    }

    public function ensureTables(): void
    {
        $uploadedFilesTable = $this->ensureTableFromDescriptor(
            BiUploadedFilesSchema::descriptor(),
            $this->tablePrefix,
            'Failed to ensure bi_uploaded_files table'
        );
        $this->ensureIndexesFromDescriptor($uploadedFilesTable, BiUploadedFilesSchema::descriptor());

        // Keep this table FK-free for maximum compatibility with existing installs.
        $fileStatementsTable = $this->ensureTableFromDescriptor(
            BiFileStatementsSchema::descriptor(),
            $this->tablePrefix,
            'Failed to ensure bi_file_statements table'
        );
        $this->ensureIndexesFromDescriptor($fileStatementsTable, BiFileStatementsSchema::descriptor());
    }

    private function runQuery(string $sql, string $error = ''): void
    {
        call_user_func($this->query, $sql, $error);
    }
}
