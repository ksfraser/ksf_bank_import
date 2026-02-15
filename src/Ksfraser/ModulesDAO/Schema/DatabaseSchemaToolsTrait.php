<?php

namespace Ksfraser\ModulesDAO\Schema;

/**
 * Shared schema maintenance helpers for installer/migrator services.
 *
 * Framework-agnostic by design: callers inject DB callbacks.
 *
 * Expected callback signatures:
 * - $query(string $sql, string $errorMsg = ''): mixed
 * - $escape(string $value): string
 * - $numRows(mixed $result): int
 */
trait DatabaseSchemaToolsTrait
{
    /** @var callable */
    private $query;

    /** @var callable */
    private $escape;

    /** @var callable */
    private $numRows;

    protected function initSchemaTools(callable $query, callable $escape, callable $numRows)
    {
        $this->query = $query;
        $this->escape = $escape;
        $this->numRows = $numRows;
    }

    protected function runQuery($sql, $errorMsg = '')
    {
        return call_user_func($this->query, $sql, $errorMsg);
    }

    protected function tableExists($table)
    {
        $sql = "SHOW TABLES LIKE " . call_user_func($this->escape, $table);
        $res = $this->runQuery($sql, 'Failed checking table existence');
        return call_user_func($this->numRows, $res) > 0;
    }

    protected function columnExists($table, $column)
    {
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE " . call_user_func($this->escape, $column);
        $res = $this->runQuery($sql, 'Failed checking column existence');
        return call_user_func($this->numRows, $res) > 0;
    }

    protected function indexExists($table, $indexName)
    {
        $sql = "SHOW INDEX FROM `{$table}` WHERE Key_name = " . call_user_func($this->escape, $indexName);
        $res = $this->runQuery($sql, 'Failed checking index existence');
        return call_user_func($this->numRows, $res) > 0;
    }

    protected function ensureColumn($table, $column, $definition)
    {
        if (!$this->tableExists($table) || $this->columnExists($table, $column)) {
            return;
        }

        $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
        $this->runQuery($sql, 'Failed adding column to bank import schema');
    }

    protected function ensureUniqueIndex($table, $indexName, array $columns)
    {
        if (!$this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $colsSql = array();
        foreach ($columns as $col) {
            $colsSql[] = "`{$col}`";
        }

        $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$indexName}` UNIQUE(" . implode(', ', $colsSql) . ")";
        $this->runQuery($sql, 'Failed adding unique index for bank import schema');
    }

    protected function ensureIndex($table, $indexName, array $columns)
    {
        if (!$this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $colsSql = array();
        foreach ($columns as $col) {
            $colsSql[] = "`{$col}`";
        }

        $sql = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (" . implode(', ', $colsSql) . ")";
        $this->runQuery($sql, 'Failed adding index for bank import schema');
    }

    protected function ensureTableFromDescriptor(array $descriptor, $tablePrefix = '', $errorMsg = 'Failed ensuring table from descriptor')
    {
        $table = (string)$tablePrefix . (string)$descriptor['table'];
        $tableAlreadyExists = $this->tableExists($table);
        $fields = isset($descriptor['fields']) && is_array($descriptor['fields']) ? $descriptor['fields'] : array();
        $primaryKey = isset($descriptor['primaryKey']) ? (string)$descriptor['primaryKey'] : '';
        $db = isset($descriptor['db']) && is_array($descriptor['db']) ? $descriptor['db'] : array();

        $lines = array();
        foreach ($fields as $name => $meta) {
            if (!is_array($meta) || !isset($meta['type'])) {
                continue;
            }

            $line = "`{$name}` " . $meta['type'];
            if (isset($meta['null'])) {
                $line .= " " . $meta['null'];
            }
            if (!empty($meta['auto_increment'])) {
                $line .= " AUTO_INCREMENT";
            }
            if (array_key_exists('default', $meta)) {
                $line .= " DEFAULT " . $this->normalizeDefaultSql($meta['default']);
            }
            if (isset($meta['on_update'])) {
                $line .= " ON UPDATE " . $meta['on_update'];
            }
            $lines[] = $line;
        }

        if ($primaryKey !== '') {
            $pkParts = array_map('trim', explode(',', $primaryKey));
            $pkCols = array();
            foreach ($pkParts as $pkCol) {
                if ($pkCol === '') {
                    continue;
                }
                $pkCols[] = "`{$pkCol}`";
            }
            if (!empty($pkCols)) {
                $lines[] = "PRIMARY KEY(" . implode(', ', $pkCols) . ")";
            }
        }

        if (!$tableAlreadyExists) {
            $uniqueConstraints = isset($db['uniqueConstraints']) && is_array($db['uniqueConstraints']) ? $db['uniqueConstraints'] : array();
            foreach ($uniqueConstraints as $constraint) {
                if (!is_array($constraint) || empty($constraint['name']) || empty($constraint['columns']) || !is_array($constraint['columns'])) {
                    continue;
                }
                $cols = array();
                foreach ($constraint['columns'] as $col) {
                    $cols[] = "`{$col}`";
                }
                $lines[] = "CONSTRAINT `{$constraint['name']}` UNIQUE (" . implode(', ', $cols) . ")";
            }
        }

        $indexes = isset($db['indexes']) && is_array($db['indexes']) ? $db['indexes'] : array();
        foreach ($indexes as $index) {
            if (!is_array($index) || empty($index['name']) || empty($index['columns']) || !is_array($index['columns'])) {
                continue;
            }
            $cols = array();
            foreach ($index['columns'] as $col) {
                $cols[] = "`{$col}`";
            }
            $lines[] = "INDEX `{$index['name']}` (" . implode(', ', $cols) . ")";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (\n\t\t    " . implode(",\n\t\t    ", $lines) . "\n\t\t)";

        if (!empty($db['engine'])) {
            $sql .= " ENGINE=" . $db['engine'];
        }
        if (!empty($db['charset'])) {
            $sql .= " DEFAULT CHARSET=" . $db['charset'];
        }
        if (!empty($db['collation'])) {
            $sql .= " COLLATE=" . $db['collation'];
        }

        $this->runQuery($sql, $errorMsg);

        return $table;
    }

    /**
     * Normalize descriptor default values into SQL-safe default clauses.
     *
     * Accepts already-quoted SQL, SQL constants/functions, numeric values,
     * or plain strings that should be single-quoted.
     *
     * @param mixed $default
     * @return string
     */
    protected function normalizeDefaultSql($default)
    {
        if ($default === null) {
            return 'NULL';
        }

        $value = (string)$default;
        $trimmed = trim($value);

        if ($trimmed === '') {
            return "''";
        }

        // Already quoted literal
        if ((substr($trimmed, 0, 1) === "'" && substr($trimmed, -1) === "'") ||
            (substr($trimmed, 0, 1) === '"' && substr($trimmed, -1) === '"')) {
            return $trimmed;
        }

        // SQL keywords/functions commonly used as defaults
        $upper = strtoupper($trimmed);
        if (in_array($upper, array('CURRENT_TIMESTAMP', 'NULL', 'TRUE', 'FALSE'), true)) {
            return $trimmed;
        }

        if (is_numeric($trimmed)) {
            return $trimmed;
        }

        return "'" . str_replace("'", "''", $trimmed) . "'";
    }

    protected function ensureIndexesFromDescriptor($table, array $descriptor)
    {
        $db = isset($descriptor['db']) && is_array($descriptor['db']) ? $descriptor['db'] : array();
        $indexes = isset($db['indexes']) && is_array($db['indexes']) ? $db['indexes'] : array();

        foreach ($indexes as $index) {
            if (!is_array($index) || empty($index['name']) || empty($index['columns']) || !is_array($index['columns'])) {
                continue;
            }
            $this->ensureIndex($table, (string)$index['name'], $index['columns']);
        }
    }

    /**
     * Seed defaults into a table using INSERT IGNORE and associative row arrays.
     *
     * Example row format:
     * [
     *   'config_key' => 'upload.max_file_size',
     *   'config_value' => '10485760'
     * ]
     *
     * @param string $table Fully-qualified table name (including prefix)
     * @param array<int,array<string,mixed>> $rows
     * @param string $errorMsg
     * @return void
     */
    protected function insertIgnoreRows($table, array $rows, $errorMsg = 'Failed seeding table defaults')
    {
        if (empty($rows)) {
            return;
        }

        $firstRow = reset($rows);
        if (!is_array($firstRow) || empty($firstRow)) {
            return;
        }

        $columns = array_keys($firstRow);
        $columnSql = array();
        foreach ($columns as $col) {
            $columnSql[] = "`{$col}`";
        }

        $valuesSql = array();
        foreach ($rows as $row) {
            $parts = array();
            foreach ($columns as $col) {
                $parts[] = $this->toSqlLiteral(array_key_exists($col, $row) ? $row[$col] : null);
            }
            $valuesSql[] = '(' . implode(', ', $parts) . ')';
        }

        $sql = "INSERT IGNORE INTO `{$table}` (" . implode(', ', $columnSql) . ") VALUES\n"
             . implode(",\n", $valuesSql);

        $this->runQuery($sql, $errorMsg);
    }

    /**
     * Convert a PHP value to a SQL literal suitable for INSERT.
     *
     * @param mixed $value
     * @return string
     */
    protected function toSqlLiteral($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $escaped = (string) call_user_func($this->escape, (string)$value);
        $trimmed = trim($escaped);

        // If escape callback already returned quoted SQL, trust it.
        if ((substr($trimmed, 0, 1) === "'" && substr($trimmed, -1) === "'") ||
            (substr($trimmed, 0, 1) === '"' && substr($trimmed, -1) === '"')) {
            return $trimmed;
        }

        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}
