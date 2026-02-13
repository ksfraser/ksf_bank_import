<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Db;

interface DbAdapterInterface
{
    /**
     * Execute a query and return the raw driver result.
     *
     * @param string $sql
     * @return mixed
     */
    public function query(string $sql, string $errorMsg = '');

    /**
     * Fetch a single row from a query result.
     *
     * @param mixed $result
     * @return array|false
     */
    public function fetch($result);
}
