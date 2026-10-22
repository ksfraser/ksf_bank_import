<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository;

use InvalidArgumentException;
use ksfraser\CommonDb\Adapter\FaDbAdapter;
use ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * Binds SQL parameters for FrontAccounting, which has NO prepared statements.
 *
 * FrontAccounting's db_query() takes (string $sql, $error = null): the second
 * argument is the error message, NOT a parameter array. Passing an array of
 * values therefore leaves the "?" placeholders in the statement untouched and
 * the query fails at MySQL with a syntax error.
 *
 * The house solution is ksfraser/ksf-common-db: the FaDbAdapter implements the
 * DbConnectionInterface contract and resolves placeholders to escaped literals
 * through mysqli_real_escape_string() before the SQL reaches db_query().
 *
 * This class exposes exactly that binding step for the repositories that still
 * execute through FA's procedural db_query() (so that FA's error handling and
 * the "did the write succeed" semantics of db_query() are preserved), while the
 * quoting itself is delegated to the adapter's DbConnectionInterface::quote().
 *
 * Quoting rules (identical to the FaDbAdapter):
 *   null            -> NULL
 *   int / float     -> the number, unquoted
 *   bool            -> 1 / 0
 *   string          -> 'escaped'
 *
 * @package Ksfraser\FaBankImport\Repository
 * @since 1.0.0
 * @BABOK FR-PROC-002, FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class ParameterBinder
{
    /** @var DbConnectionInterface */
    private $db;

    /**
     * @param DbConnectionInterface|null $db Defaults to the FrontAccounting adapter.
 * @BABOK FR-PROC-002, FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(?DbConnectionInterface $db = null)
    {
        // No table prefix: the repositories qualify their own table names with
        // TB_PREF, and this binder only quotes values.
        $this->db = $db ?? new FaDbAdapter();
    }

    /**
     * Resolve "?" (positional) and ":name" (named) placeholders to literals.
     *
     * @param string               $sql    SQL with placeholders
     * @param array<string, mixed> $params Values for the placeholders
     * @return string              SQL with every placeholder replaced by a quoted literal
     * @throws InvalidArgumentException When the number of placeholders does not match the values
 * @BABOK FR-PROC-002, FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function bind(string $sql, array $params = []): string
    {
        if ($params === []) {
            return $sql;
        }

        $isAssoc = array_keys($params) !== range(0, count($params) - 1);

        if ($isAssoc) {
            foreach ($params as $name => $value) {
                $sql = str_replace(':' . $name, $this->db->quote($value), $sql);
            }

            return $sql;
        }

        $parts = explode('?', $sql);
        if (count($parts) !== count($params) + 1) {
            throw new InvalidArgumentException(sprintf(
                'Cannot bind %d value(s) to %d placeholder(s) in: %s',
                count($params),
                count($parts) - 1,
                $sql
            ));
        }

        $result = $parts[0];
        foreach ($params as $i => $value) {
            $result .= $this->db->quote($value) . $parts[$i + 1];
        }

        return $result;
    }
}
