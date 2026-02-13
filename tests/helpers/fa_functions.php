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
    $rows = _test_db_rows_for_sql((string)$sql);
    return new TestDbResult($rows);
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
