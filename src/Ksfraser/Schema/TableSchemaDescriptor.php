<?php

namespace Ksfraser\Schema;

class TableSchemaDescriptor
{
    /** @var string */
    private $tableName;

    /** @var string */
    private $createTableSql;

    /** @var array<string, string> */
    private $columns;

    /** @var array<string, string> */
    private $indexes;

    /**
     * @param array<string, string> $columns
     * @param array<string, string> $indexes
     */
    public function __construct(string $tableName, string $createTableSql, array $columns = [], array $indexes = [])
    {
        $this->tableName = $tableName;
        $this->createTableSql = $createTableSql;
        $this->columns = $columns;
        $this->indexes = $indexes;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getCreateTableSql(): string
    {
        return $this->createTableSql;
    }

    /**
     * @return array<string, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<string, string>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }
}
