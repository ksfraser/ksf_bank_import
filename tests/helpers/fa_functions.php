<?php

/**
 * FrontAccounting Test Environment Setup
 *
 * The FA runtime for unit tests is provided by ksfraser/famock (composer
 * dev dependency, loaded through composer's files autoloader before this
 * file runs), so the historical stub definitions here are no longer the
 * active implementations (famock's function_exists() guards keep ours
 * dormant).
 *
 * This file keeps two responsibilities under the new order:
 *  - re-declared-but-dormant stubs stay harmless for bootstraps that still
 *    require this file by name;
 *  - seed famock's fixture chart of accounts so BankImportConfig's GL
 *    account validation behaves like production (db_query() answers
 *    chart_master COUNT(*) lookups from $__fa_chart_accounts).
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

// Fixture chart of accounts for chart_master lookups (famock) -- mirrors
// the accounts used by FA's default chart so setters accept production-
// shaped codes (9999 stays present because the config reset tests reuse
// it as a valid mutated value).
$GLOBALS['__fa_chart_accounts'] = [
    '0000',
    '1000',
    '1060',
    '1100',
    '1200',
    '1234',
    '1400',
    '1550',
    '2000',
    '2100',
    '2130',
    '9999',
];

/**
 * Get company preference (test stub)
 *
 * @param string $name Preference name
 * @return mixed Preference value or null
 */
if (!function_exists('get_company_pref')) {
    function get_company_pref($name)
    {
        global $_test_company_prefs;
        return isset($_test_company_prefs) ? ($_test_company_prefs[$name] ?? null) : null;
    }
}

/**
 * Set company preference (test stub)
 *
 * @param string $name Preference name
 * @param mixed $value Preference value
 * @return void
 */
if (!function_exists('set_company_pref')) {
    function set_company_pref($name, $value)
    {
        global $_test_company_prefs;
        if (!isset($_test_company_prefs)) {
            $_test_company_prefs = [];
        }
        $_test_company_prefs[$name] = $value;
    }
}

/**
 * Database escape (test stub)
 *
 * @param string $value Value to escape
 * @return string Escaped value
 */
if (!function_exists('db_escape')) {
    function db_escape($value)
    {
        return "'" . addslashes($value) . "'";
    }
}

/**
 * Database query (test stub)
 *
 * @param string $sql SQL query
 * @param string $error_msg Error message
 * @return array Result array
 */
if (!function_exists('db_query')) {
    function db_query($sql, $error_msg = '')
    {
        // Mock result for account existence check
        return ['count' => 1];
    }
}

/**
 * Fetch database result (test stub)
 *
 * @param mixed $result Query result
 * @return array Row array
 */
if (!function_exists('db_fetch')) {
    function db_fetch($result)
    {
        return $result;
    }
}

/**
 * Fetch number of rows from database result (test stub)
 *
 * @param mixed $result Query result
 * @return int Number of rows
 */
if (!function_exists('db_num_rows')) {
    function db_num_rows($result)
    {
        return is_array($result) && isset($result['count']) ? (int)$result['count'] : 10;
    }
}

/**
 * Check if reference is new (test stub)
 *
 * @param string $reference Reference to check
 * @param int $transType Transaction type
 * @return bool Always returns true in tests
 */
if (!function_exists('is_new_reference')) {
    function is_new_reference($reference, $transType)
    {
        return true;
    }
}

/**
 * Get date for beginning of month (test stub)
 *
 * @param string $date Date string
 * @return string First day of month
 */
if (!function_exists('begin_month')) {
    function begin_month($date)
    {
        return date('Y-m-01', strtotime($date));
    }
}

/**
 * Get date for end of month (test stub)
 *
 * @param string $date Date string
 * @return string Last day of month
 */
if (!function_exists('end_month')) {
    function end_month($date)
    {
        return date('Y-m-t', strtotime($date));
    }
}

/**
 * Get today's date (test stub)
 *
 * @return string Today's date in Y-m-d format
 */
if (!function_exists('Today')) {
    function Today()
    {
        return date('Y-m-d');
    }
}

/**
 * Define FrontAccounting table prefix constant if not defined
 */
if (!defined('TB_PREF')) {
    define('TB_PREF', '0_');
}
