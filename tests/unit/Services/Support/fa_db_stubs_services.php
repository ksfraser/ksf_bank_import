<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbResult;

require_once __DIR__ . '/FaDbFake.php';
require_once __DIR__ . '/FaDbResult.php';

/*
 * Namespaced overrides of the FrontAccounting global helpers used by the
 * services in this namespace. PHP resolves unqualified function calls against
 * the current namespace first and only then against the global scope, so
 * these definitions shadow the FAMock stubs while the fake is active.
 *
 * When no fake is active the real (FAMock) helpers are used, so the rest of
 * the test suite is unaffected.
 */

/**
 * @param string $sql
 * @param mixed $error
 * @return FaDbResult|false
 */
function db_query($sql, $error = null)
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \db_query($sql, $error);
    }

    return $fake->query((string) $sql, $error);
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

/**
 * @param mixed $access
 */
function has_access($access, string $securityArea): bool
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \has_access($access, $securityArea);
    }

    return $fake->accessAllowed;
}

function is_new_reference(string $reference, int $transType): bool
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \is_new_reference($reference, $transType);
    }

    return $fake->isNewReference();
}

function is_uploaded_file(string $filename): bool
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \is_uploaded_file($filename);
    }

    return $fake->uploadIsValid;
}

function move_uploaded_file(string $from, string $to): bool
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \move_uploaded_file($from, $to);
    }

    if (!$fake->moveSucceeds) {
        return false;
    }

    if (is_file($from)) {
        return @rename($from, $to);
    }

    return (bool) @touch($to);
}

/**
 * Captures the headers instead of sending them, so the download path can be
 * exercised in a CLI test run.
 */
function header(string $header, bool $replace = true, int $responseCode = 0): void
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        \header($header, $replace, $responseCode);

        return;
    }

    $fake->headers[] = $header;
}

/**
 * @return int|false
 */
function readfile(string $filename)
{
    $fake = FaDbFake::current();
    if ($fake === null) {
        return \readfile($filename);
    }

    $contents = is_file($filename) ? (string) file_get_contents($filename) : '';
    $fake->readFiles[] = ['path' => $filename, 'contents' => $contents];

    return strlen($contents);
}

/**
 * The tokenizer treats a false return value from preg_split() as "no tokens".
 * preg_split() only fails for an invalid pattern, so the failure is injected
 * here to exercise that defensive branch.
 *
 * @param string|array $pattern
 * @param string|array $subject
 * @param int $limit
 * @param int $flags
 * @return array<int,string>|false
 */
function preg_split($pattern, $subject, $limit = -1, $flags = 0)
{
    $fake = FaDbFake::current();
    if ($fake !== null && $fake->pregSplitFails) {
        return false;
    }

    return \preg_split($pattern, $subject, $limit, $flags);
}
