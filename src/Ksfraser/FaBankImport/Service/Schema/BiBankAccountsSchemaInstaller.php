<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiBankAccountsSchemaInstaller [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiBankAccountsSchemaInstaller.
 */
namespace Ksfraser\FaBankImport\Service\Schema;

use Ksfraser\FaBankImport\Schema\BiBankAccountsSchema;
use Ksfraser\ModulesDAO\Schema\DatabaseSchemaToolsTrait as SharedDatabaseSchemaToolsTrait;

// TODO(modulesdao-packaging): Once ModulesDAO is fully extracted/imported,
// update composer/autoload to consume the external ModulesDAO package for
// UAT/Prod instead of resolving this namespace from in-repo sources.

class BiBankAccountsSchemaInstaller
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
        $descriptor = BiBankAccountsSchema::descriptor();
        $table = $this->ensureTableFromDescriptor(
            $descriptor,
            $this->tablePrefix,
            'Failed to ensure bi_bank_accounts table'
        );

        // Explicit re-check keeps operation idempotent for existing installations.
        $this->ensureIndexesFromDescriptor($table, $descriptor);
    }
}
