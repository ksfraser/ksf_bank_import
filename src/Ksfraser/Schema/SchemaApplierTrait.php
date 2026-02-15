<?php

namespace Ksfraser\Schema;

trait SchemaApplierTrait
{
    protected static function ensure_table_schema(TableSchemaDescriptor $descriptor, string $errorPrefix = 'Schema migration failed'): void
    {
        $table = $descriptor->getTableName();

        if (!self::schema_table_exists($table)) {
            db_query($descriptor->getCreateTableSql(), $errorPrefix . ': create table');
        }

        foreach ($descriptor->getColumns() as $column => $definition) {
            if (!self::schema_column_exists($table, $column)) {
                $sql = 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition;
                db_query($sql, $errorPrefix . ': add column ' . $column);
            }
        }

        foreach ($descriptor->getIndexes() as $indexName => $indexDefinition) {
            if (!self::schema_index_exists($table, $indexName)) {
                $sql = 'ALTER TABLE `' . $table . '` ADD ' . $indexDefinition;
                db_query($sql, $errorPrefix . ': add index ' . $indexName);
            }
        }
    }

    protected static function schema_table_exists(string $table): bool
    {
        $res = db_query('SHOW TABLES LIKE ' . db_escape($table), 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    protected static function schema_column_exists(string $table, string $column): bool
    {
        $res = db_query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . db_escape($column), 'Failed checking column existence');
        return db_num_rows($res) > 0;
    }

    protected static function schema_index_exists(string $table, string $indexName): bool
    {
        $res = db_query('SHOW INDEX FROM `' . $table . '` WHERE Key_name=' . db_escape($indexName), 'Failed checking index existence');
        return db_num_rows($res) > 0;
    }
}
