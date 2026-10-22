<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Repositories
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Repositories;

/**
 * The state of the in-memory database the repository tests run against.
 */
final class RepositoryDbStub
{
    /**
     * @var array The statements that were executed.
     */
    public static $queries = [];

    /**
     * @var array The result sets that were queued.
     */
    public static $queued = [];

    /**
     * @var array The rows of the current result set.
     */
    public static $rows = [];

    /**
     * @var int The position inside the current result set.
     */
    public static $pos = 0;

    /**
     * @var int The number of rows the last statement changed.
     */
    public static $affected = 0;

    /**
     * @var bool Whether the double answers the calls.
     *
     * While the double is switched off the calls are handed to the global
     * FrontAccounting functions, so the other tests are not affected.
     */
    public static $active = false;

    /**
     * Resets the state.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$queries = [];
        self::$queued = [];
        self::$rows = [];
        self::$pos = 0;
        self::$affected = 0;
    }

    /**
     * Queues the rows the next statement returns.
     *
     * @param array $rows The rows.
     *
     * @return void
     */
    public static function queue(array $rows): void
    {
        self::$queued[] = $rows;
    }
}

if (!function_exists(__NAMESPACE__ . '\\db_query')) {
    /**
     * Runs a statement.
     *
     * @param string $sql   The statement.
     * @param string $error The error message.
     *
     * @return string
     */
    function db_query(string $sql, $error = null)
    {
        if (!RepositoryDbStub::$active) {
            return \db_query($sql, $error);
        }

        RepositoryDbStub::$queries[] = $sql;
        RepositoryDbStub::$rows = empty(RepositoryDbStub::$queued)
            ? []
            : array_shift(RepositoryDbStub::$queued);
        RepositoryDbStub::$pos = 0;

        return $sql;
    }
}

if (!function_exists(__NAMESPACE__ . '\\db_fetch')) {
    /**
     * Returns the next row of the current result set.
     *
     * @param mixed $res The result handle.
     *
     * @return array|false
     */
    function db_fetch($res)
    {
        if (!RepositoryDbStub::$active) {
            return \db_fetch($res);
        }

        if (RepositoryDbStub::$pos >= count(RepositoryDbStub::$rows)) {
            return false;
        }

        $row = RepositoryDbStub::$rows[RepositoryDbStub::$pos];
        RepositoryDbStub::$pos++;

        return $row;
    }
}

if (!function_exists(__NAMESPACE__ . '\\db_escape')) {
    /**
     * Escapes a value.
     *
     * @param mixed $value The value.
     *
     * @return string
     */
    function db_escape($value): string
    {
        if (!RepositoryDbStub::$active) {
            return \db_escape($value);
        }

        if (is_null($value)) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return addslashes((string) $value);
    }
}

if (!function_exists(__NAMESPACE__ . '\\db_affected_rows')) {
    /**
     * The number of rows the last statement changed.
     *
     * @return int
     */
    function db_affected_rows()
    {
        if (!RepositoryDbStub::$active) {
            return \db_affected_rows();
        }

        return RepositoryDbStub::$affected;
    }
}
