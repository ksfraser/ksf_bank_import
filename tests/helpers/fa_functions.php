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
    // Mock result for account existence check
    return ['count' => 1];
}

/**
 * Fetch database result (test stub)
 *
 * @param mixed $result Query result
 * @return array Row array
 */
function db_fetch($result)
{
    return $result;
}

/**
 * Fetch number of rows from database result (test stub)
 *
 * @param mixed $result Query result
 * @return int Number of rows
 */
function db_num_rows($result)
{
    return is_array($result) && isset($result['count']) ? (int)$result['count'] : 10;
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
