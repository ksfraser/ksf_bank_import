<?php

/**
 * FrontAccounting Function Stubs for Testing
 *
 * Provides stub implementations of FrontAccounting global functions
 * for unit testing in isolation from the FrontAccounting environment.
 *
 * @package    Tests\Helpers
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

// In-memory storage for company preferences during tests
global $_test_company_prefs;
$_test_company_prefs = [];

// In-memory transactional dataset for repository/integration tests
global $_test_db_affected_rows;
$_test_db_affected_rows = 0;

global $_test_bi_statements;
$_test_bi_statements = [
    1 => ['id' => 1, 'account' => 'ACCT-001', 'currency' => 'USD'],
    2 => ['id' => 2, 'account' => 'ACCT-002', 'currency' => 'CAD'],
];

global $_test_bi_transactions;
$_test_bi_transactions = [
    1 => [
        'id' => 1,
        'smt_id' => 1,
        'valueTimestamp' => '2024-03-01 10:00:00',
        'amount' => 250.00,
        'transactionAmount' => 250.00,
        'title' => 'Payroll Deposit',
        'transactionTitle' => 'Payroll Deposit',
        'status' => 1,
        'fa_trans_no' => 0,
        'fa_trans_type' => 0,
        'matched' => 0,
        'created' => 0,
        'g_partner' => 'CU',
        'g_option' => '1',
        'account' => 'ACCT-001',
    ],
    2 => [
        'id' => 2,
        'smt_id' => 1,
        'valueTimestamp' => '2024-06-15 12:00:00',
        'amount' => -125.00,
        'transactionAmount' => -125.00,
        'title' => 'Vendor Payment',
        'transactionTitle' => 'Vendor Payment',
        'status' => 0,
        'fa_trans_no' => 0,
        'fa_trans_type' => 0,
        'matched' => 0,
        'created' => 0,
        'g_partner' => 'SP',
        'g_option' => '2',
        'account' => 'ACCT-001',
    ],
    3 => [
        'id' => 3,
        'smt_id' => 2,
        'valueTimestamp' => '2025-01-20 08:30:00',
        'amount' => 999.99,
        'transactionAmount' => 999.99,
        'title' => 'Invoice Settlement',
        'transactionTitle' => 'Invoice Settlement',
        'status' => 1,
        'fa_trans_no' => 0,
        'fa_trans_type' => 0,
        'matched' => 0,
        'created' => 0,
        'g_partner' => 'CU',
        'g_option' => '3',
        'account' => 'ACCT-002',
    ],
];

/** @return array<int, int> */
function _test_extract_int_list_from_in_clause(string $sql): array
{
    if (!preg_match('/\bin\s*\(([^\)]*)\)/i', $sql, $matches)) {
        return [];
    }

    $parts = array_filter(array_map('trim', explode(',', $matches[1])), static function ($v) {
        return $v !== '';
    });

    return array_map('intval', $parts);
}

function _test_normalize_sql_string_literal(string $value): string
{
    $value = trim($value);
    $value = trim($value, "'");
    return str_replace("\\'", "'", $value);
}

/**
 * @param array<string, mixed> $row
 */
function _test_join_statement_data(array $row): array
{
    global $_test_bi_statements;

    $statementId = (int)($row['smt_id'] ?? 0);
    $statement = $_test_bi_statements[$statementId] ?? ['account' => $row['account'] ?? '', 'currency' => null];

    $row['our_account'] = $statement['account'] ?? ($row['account'] ?? '');
    $row['currency'] = $statement['currency'] ?? null;

    return $row;
}

/**
 * Very small in-memory DB result set for unit tests.
 */
class TestDbResult
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;
    private int $index = 0;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    /** @return array<string, mixed>|false */
    public function fetch()
    {
        if ($this->index >= count($this->rows)) {
            return false;
        }
        return $this->rows[$this->index++];
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }
}

/**
 * Return fake rows based on a SQL string.
 *
 * @return array<int, array<string, mixed>>
 */
function _test_db_rows_for_sql(string $sql): array
{
    $sqlLower = strtolower($sql);

    // In-memory transaction table support for repository/integration tests
    if (strpos($sqlLower, 'bi_transactions') !== false) {
        global $_test_bi_transactions;

        // Normal pairing aggregation query
        if (strpos($sqlLower, 'group by account, g_option, g_partner') !== false) {
            $accountFilter = null;
            if (preg_match('/\bwhere\s+account\s*=\s*([^\s]+)/i', $sql, $m)) {
                $accountFilter = _test_normalize_sql_string_literal($m[1]);
            }

            $grouped = [];
            foreach ($_test_bi_transactions as $row) {
                $account = (string)($row['account'] ?? '');
                if ($accountFilter !== null && $account !== $accountFilter) {
                    continue;
                }
                $gOption = (string)($row['g_option'] ?? '');
                $gPartner = (string)($row['g_partner'] ?? '');
                $key = $account . '|' . $gOption . '|' . $gPartner;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'count' => 0,
                        'account' => $account,
                        'g_option' => $gOption,
                        'g_partner' => $gPartner,
                    ];
                }
                $grouped[$key]['count']++;
            }

            return array_values($grouped);
        }

        // Query by ID (plain SELECT * FROM ... WHERE id = N)
        if (preg_match('/\bwhere\s+id\s*=\s*(\d+)/i', $sql, $m)) {
            $id = (int)$m[1];
            if (!isset($_test_bi_transactions[$id])) {
                return [];
            }
            $row = $_test_bi_transactions[$id];
            return [
                _test_join_statement_data($row),
            ];
        }

        $rows = array_values($_test_bi_transactions);

        // status filter
        if (preg_match('/\bt\.status\s*=\s*(\d+)/i', $sql, $m)) {
            $status = (int)$m[1];
            $rows = array_values(array_filter($rows, static function ($row) use ($status) {
                return (int)($row['status'] ?? 0) === $status;
            }));
        }

        // date range filters
        if (preg_match('/\bt\.valuetimestamp\s*>=\s*([^\s]+)/i', $sql, $m)) {
            $dateFrom = _test_normalize_sql_string_literal($m[1]);
            $rows = array_values(array_filter($rows, static function ($row) use ($dateFrom) {
                return strcmp(substr((string)$row['valueTimestamp'], 0, 10), substr($dateFrom, 0, 10)) >= 0;
            }));
        }

        if (preg_match('/\bt\.valuetimestamp\s*<\s*([^\s]+)/i', $sql, $m)) {
            $dateTo = _test_normalize_sql_string_literal($m[1]);
            $rows = array_values(array_filter($rows, static function ($row) use ($dateTo) {
                return strcmp(substr((string)$row['valueTimestamp'], 0, 10), substr($dateTo, 0, 10)) < 0;
            }));
        }

        // amount range filters
        if (preg_match('/abs\(t\.transactionamount\)\s*>=\s*([\d\.]+)/i', $sql, $m)) {
            $min = (float)$m[1];
            $rows = array_values(array_filter($rows, static function ($row) use ($min) {
                return abs((float)($row['transactionAmount'] ?? 0)) >= $min;
            }));
        }

        if (preg_match('/abs\(t\.transactionamount\)\s*<=\s*([\d\.]+)/i', $sql, $m)) {
            $max = (float)$m[1];
            $rows = array_values(array_filter($rows, static function ($row) use ($max) {
                return abs((float)($row['transactionAmount'] ?? 0)) <= $max;
            }));
        }

        // title search filter
        if (preg_match('/\bt\.transactiontitle\s+like\s+([^\s]+)/i', $sql, $m)) {
            $needle = trim(_test_normalize_sql_string_literal($m[1]), '%');
            $rows = array_values(array_filter($rows, static function ($row) use ($needle) {
                return stripos((string)($row['transactionTitle'] ?? ''), $needle) !== false;
            }));
        }

        // bank account filter via joined statements table
        if (preg_match('/\bs\.account\s*=\s*([^\s]+)/i', $sql, $m)) {
            $account = _test_normalize_sql_string_literal($m[1]);
            $rows = array_values(array_filter($rows, static function ($row) use ($account) {
                global $_test_bi_statements;
                $statement = $_test_bi_statements[(int)($row['smt_id'] ?? 0)] ?? null;
                return (string)($statement['account'] ?? '') === $account;
            }));
        }

        // sort and limit
        usort($rows, static function ($a, $b) {
            $cmp = strcmp((string)$a['valueTimestamp'], (string)$b['valueTimestamp']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int)$a['id'] <=> (int)$b['id']);
        });

        if (preg_match('/\blimit\s+(\d+)/i', $sql, $m)) {
            $rows = array_slice($rows, 0, (int)$m[1]);
        }

        return array_map('_test_join_statement_data', $rows);
    }

    // Customers
    if (strpos($sqlLower, 'debtors_master') !== false) {
        return [
            [
                'debtor_no' => 1,
                'name' => 'Acme Customer',
                'debtor_ref' => 'ACME',
                'address' => '123 Main St',
                'email' => 'acme@example.com',
                'inactive' => 0,
            ],
            [
                'debtor_no' => 2,
                'name' => 'Beta Customer',
                'debtor_ref' => 'BETA',
                'address' => '456 Side St',
                'email' => 'beta@example.com',
                'inactive' => 0,
            ],
        ];
    }

    // Customer branches
    if (strpos($sqlLower, 'cust_branch') !== false) {
        return [
            [
                'branch_code' => 10,
                'debtor_no' => 1,
                'br_name' => 'Acme Branch',
                'br_address' => '123 Main St',
                'contact_name' => 'Alice',
                'email' => 'alice@example.com',
                'inactive' => 0,
            ],
            [
                'branch_code' => 20,
                'debtor_no' => 2,
                'br_name' => 'Beta Branch',
                'br_address' => '456 Side St',
                'contact_name' => 'Bob',
                'email' => 'bob@example.com',
                'inactive' => 0,
            ],
        ];
    }

    // Suppliers
    if (strpos($sqlLower, 'suppliers') !== false) {
        return [
            [
                'supplier_id' => 1,
                'supp_name' => 'Acme Supplier',
                'supp_ref' => 'ACME-S',
                'address' => '1 Supplier Way',
                'email' => 'sup@example.com',
                'inactive' => 0,
            ],
            [
                'supplier_id' => 2,
                'supp_name' => 'Beta Supplier',
                'supp_ref' => 'BETA-S',
                'address' => '2 Supplier Way',
                'email' => 'beta-sup@example.com',
                'inactive' => 0,
            ],
        ];
    }

    // Generic COUNT queries
    if (strpos($sqlLower, 'count(') !== false) {
        return [
            ['count' => 1],
        ];
    }

    return [];
}

/**
 * Get company preference (test stub)
 *
 * @param string $name Preference name
 * @return mixed Preference value or null
 */
function get_company_pref($name)
{
    global $_test_company_prefs;
    return $_test_company_prefs[$name] ?? null;
}

/**
 * Set company preference (test stub)
 *
 * @param string $name Preference name
 * @param mixed $value Preference value
 * @return void
 */
function set_company_pref($name, $value)
{
    global $_test_company_prefs;
    $_test_company_prefs[$name] = $value;
}

/**
 * Database escape (test stub)
 *
 * @param string $value Value to escape
 * @return string Escaped value
 */
function db_escape($value)
{
    return "'" . addslashes($value) . "'";
}

/**
 * Database query (test stub)
 *
 * @param string $sql SQL query
 * @param string $error_msg Error message
 * @return array Result array
 */
function db_query($sql, $error_msg = '')
{
    global $_test_bi_transactions, $_test_db_affected_rows;
    $_test_db_affected_rows = 0;

    $sqlText = (string)$sql;
    $sqlLower = strtolower($sqlText);

    // Support UPDATE operations against in-memory bi_transactions dataset
    if (strpos($sqlLower, 'update') !== false && strpos($sqlLower, 'bi_transactions') !== false) {
        // prevoid update by FA transaction references
        if (strpos($sqlLower, 'where fa_trans_no') !== false && strpos($sqlLower, 'and fa_trans_type') !== false) {
            $faTransNo = null;
            $faTransType = null;
            if (preg_match('/where\s+fa_trans_no\s*=\s*(\d+)/i', $sqlText, $m1)) {
                $faTransNo = (int)$m1[1];
            }
            if (preg_match('/and\s+fa_trans_type\s*=\s*(\d+)/i', $sqlText, $m2)) {
                $faTransType = (int)$m2[1];
            }

            foreach ($_test_bi_transactions as &$row) {
                if (
                    ($faTransNo === null || (int)$row['fa_trans_no'] === $faTransNo)
                    && ($faTransType === null || (int)$row['fa_trans_type'] === $faTransType)
                    && (int)$row['status'] === 1
                ) {
                    $row['status'] = 0;
                    $row['fa_trans_no'] = 0;
                    $row['fa_trans_type'] = 0;
                    $row['created'] = 0;
                    $row['matched'] = 0;
                    $row['g_partner'] = '';
                    $row['g_option'] = '';
                    $_test_db_affected_rows++;
                }
            }
            unset($row);

            return new TestDbResult([]);
        }

        // Update by ID list (id IN (...))
        $ids = _test_extract_int_list_from_in_clause($sqlText);
        if (empty($ids)) {
            if (preg_match('/\bwhere\s+id\s*=\s*(\d+)/i', $sqlText, $m)) {
                $ids = [(int)$m[1]];
            }
        }

        foreach ($ids as $id) {
            if (!isset($_test_bi_transactions[$id])) {
                continue;
            }

            // status updates
            if (preg_match('/\bset\s+status\s*=\s*(\d+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['status'] = (int)$m[1];
            }

            if (preg_match('/fa_trans_no\s*=\s*(\d+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['fa_trans_no'] = (int)$m[1];
            }

            if (preg_match('/fa_trans_type\s*=\s*(\d+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['fa_trans_type'] = (int)$m[1];
            }

            if (preg_match('/matched\s*=\s*(\d+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['matched'] = (int)$m[1];
            }

            if (preg_match('/created\s*=\s*(\d+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['created'] = (int)$m[1];
            }

            if (preg_match('/g_partner\s*=\s*([^,\s]+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['g_partner'] = _test_normalize_sql_string_literal($m[1]);
            }

            if (preg_match('/g_option\s*=\s*([^,\s]+)/i', $sqlText, $m)) {
                $_test_bi_transactions[$id]['g_option'] = _test_normalize_sql_string_literal($m[1]);
            }

            $_test_db_affected_rows++;
        }

        return new TestDbResult([]);
    }

    $rows = _test_db_rows_for_sql((string)$sql);
    return new TestDbResult($rows);
}

/**
 * Number of affected rows from the latest update query.
 */
function db_affected_rows()
{
    global $_test_db_affected_rows;
    return (int)$_test_db_affected_rows;
}

/**
 * Fetch database result (test stub)
 *
 * @param mixed $result Query result
 * @return array Row array
 */
function db_fetch($result)
{
    if ($result instanceof TestDbResult) {
        return $result->fetch();
    }

    // Fallback: if a single row array is passed, return it once.
    if (is_array($result)) {
        return $result;
    }

    return false;
}

/**
 * Fetch number of rows from database result (test stub)
 *
 * @param mixed $result Query result
 * @return int Number of rows
 */
function db_num_rows($result)
{
    if ($result instanceof TestDbResult) {
        return $result->rowCount();
    }
    if (is_array($result) && isset($result['count'])) {
        return (int)$result['count'];
    }
    return 0;
}

/**
 * Check if reference is new (test stub)
 *
 * @param string $reference Reference to check
 * @param int $transType Transaction type
 * @return bool Always returns true in tests
 */
function is_new_reference($reference, $transType)
{
    return true;
}

/**
 * Get date for beginning of month (test stub)
 *
 * @param string $date Date string
 * @return string First day of month
 */
function begin_month($date)
{
    return date('Y-m-01', strtotime($date));
}

/**
 * Get date for end of month (test stub)
 *
 * @param string $date Date string
 * @return string Last day of month
 */
function end_month($date)
{
    return date('Y-m-t', strtotime($date));
}

/**
 * Get today's date (test stub)
 *
 * @return string Today's date in Y-m-d format
 */
function Today()
{
    return date('Y-m-d');
}

/**
 * Define FrontAccounting table prefix constant if not defined
 */
if (!defined('TB_PREF')) {
    define('TB_PREF', '0_');
}
