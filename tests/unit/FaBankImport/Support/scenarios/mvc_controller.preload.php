<?php

declare(strict_types=1);

/**
 * Preload for the mvc_controller scenario.
 *
 * Required by isolated_runner.php BEFORE the composer autoloader so this
 * db_query() double is in place before the FA mocks of
 * vendor/ksfraser/famock (pulled in through the autoloader's "files"
 * directive) can define their own. It answers with an array of rows, which
 * is what the legacy MVC controller's loadTransactions()/findTransaction()
 * expect; the FA mocks answer with the SQL string.
 *
 * This file is a test double; it is not production code.
 */

if (!function_exists('db_query')) {
    /**
     * @param string $sql  Statement.
     * @param mixed  $error Error message.
     * @return array
     */
    function db_query($sql, $error = null)
    {
        $GLOBALS['__db_query_calls'][] = $sql;

        $row = [
            'id' => 7,
            'transactionDC' => 'C',
            'amount' => 12.5,
        ];

        return $GLOBALS['__db_query_rows'] ? [$row] : [];
    }
}
