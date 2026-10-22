<?php

declare(strict_types=1);

/**
 * FrontAccounting function stubs that the FA mocks of
 * vendor/ksfraser/famock do not provide.
 *
 * \Ksfraser\Application\Bootstrap::verifyDatabaseConnection() calls the
 * global db_connect() function, which does not exist in the mock package.
 *
 * The stub records the call in $GLOBALS['__db_connect_calls'] and throws
 * when $GLOBALS['__db_connect_exception'] is set.
 */

if (!function_exists('db_connect')) {
    /**
     * @param string|null $host     Database host.
     * @param string|null $dbname   Database name.
     * @param string|null $user     Database user.
     * @param string|null $password Database password.
     * @return bool
     */
    function db_connect($host = null, $dbname = null, $user = null, $password = null)
    {
        $GLOBALS['__db_connect_calls'][] = [
            'host' => $host,
            'dbname' => $dbname,
            'user' => $user,
            'password' => $password,
        ];

        if (!empty($GLOBALS['__db_connect_exception'])) {
            throw new \Exception((string) $GLOBALS['__db_connect_exception']);
        }

        return true;
    }
}
