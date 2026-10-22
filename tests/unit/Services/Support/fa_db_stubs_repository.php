<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbResult;

require_once __DIR__ . '/FaDbFake.php';
require_once __DIR__ . '/FaDbResult.php';

/*
 * Namespaced overrides of the FrontAccounting global helpers used by the
 * repositories in this namespace. See fa_db_stubs_services.php for details.
 */

/**
 * @param string $sql
 * @param mixed $params
 * @return FaDbResult|false
 */
function db_query($sql, $params = null)
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \db_query($sql, $params);
    }

    return $fake->query((string) $sql, $params);
}

/**
 * @param mixed $result
 * @return array<string,mixed>|false
 */
function db_fetch($result)
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \db_fetch($result);
    }

    if ($result instanceof FaDbResult) {
        return $result->next();
    }

    return false;
}

/**
 * @param mixed $result
 */
function db_num_rows($result): int
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return (int) \db_num_rows($result);
    }

    if ($result instanceof FaDbResult) {
        return $result->count();
    }

    return 0;
}

function db_insert_id(): int
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return (int) \db_insert_id();
    }

    return $fake->insertId;
}

/**
 * @param mixed $value
 */
/**
 * Mirrors FrontAccounting's db_escape() contract.
 *
 * FA 2.4.3 (includes/db/connect_db_mysqli.inc:135) returns a READY-TO-USE
 * SQL literal: strings come back quoted and escaped, numbers come back
 * unquoted, and an empty value becomes ''. A mock that only escapes (without
 * quoting) makes the tests assert a SQL shape production never produces -
 * which is exactly how the double-quoting bug in the repositories survived.
 *
 * @param mixed $value
 */
function db_escape($value): string
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \db_escape($value);
    }

    if ($value === null || $value === '') {
        return "''";
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    return "'" . addslashes((string) $value) . "'";
}
