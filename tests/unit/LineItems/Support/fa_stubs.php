<?php

/**
 * FrontAccounting function and constant stand-ins for the isolated scenarios.
 *
 * The famock package supplies most of the FA surface, but the legacy
 * line-item code also calls a handful of helpers the mocks do not know
 * about. They are declared here so the isolated scenarios can run the
 * production code unmodified.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

require_once __DIR__ . '/../../../../vendor/ksfraser/famock/php/FAMock.php';

if (!defined('TABLESTYLE2')) {
    define('TABLESTYLE2', 'TABLESTYLE2');
}

if (!defined('QE_DEPOSIT')) {
    define('QE_DEPOSIT', 'QE_DEPOSIT');
}

if (!defined('QE_PAYMENT')) {
    define('QE_PAYMENT', 'QE_PAYMENT');
}

if (!defined('ST_SALESDELIVERY')) {
    define('ST_SALESDELIVERY', 13);
}

if (!function_exists('shorten_bankAccount_Names')) {
    /**
     * Shorten long bank account descriptive names.
     *
     * @param string $name Raw account name.
     * @return string Shortened name.
     */
    function shorten_bankAccount_Names(string $name): string
    {
        return $name;
    }
}

if (!function_exists('array_selector')) {
    /**
     * Render a drop down of the given options.
     *
     * @param string $name Form field name.
     * @param mixed $selected_id Selected option.
     * @param array $items Option list.
     * @param mixed $options Rendering options.
     * @return string The rendered control.
     */
    function array_selector($name, $selected_id, $items, $options = null)
    {
        $html = "<select name='" . $name . "'>";
        foreach ((array) $items as $key => $label) {
            $html .= "<option value='" . $key . "'>" . $label . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}

if (!function_exists('bank_accounts_list')) {
    /**
     * Render a list of bank accounts.
     *
     * @param string $name Form field name.
     * @param mixed $selected_id Selected option.
     * @param mixed $submit_on_change Submit on change flag.
     * @param mixed $spec_option Special option flag.
     * @return string The rendered control.
     */
    function bank_accounts_list($name, $selected_id = null, $submit_on_change = false, $spec_option = false)
    {
        return "<select name='" . $name . "'></select>";
    }
}

if (!function_exists('quick_entries_list')) {
    /**
     * Render a list of quick entries.
     *
     * @param string $name Form field name.
     * @param mixed $selected_id Selected option.
     * @param mixed $type Quick entry type.
     * @param mixed $submit_on_change Submit on change flag.
     * @return string The rendered control.
     */
    function quick_entries_list($name, $selected_id = null, $type = null, $submit_on_change = false)
    {
        return "<select name='" . $name . "'></select>";
    }
}

if (!function_exists('get_quick_entry')) {
    /**
     * Look up a quick entry.
     *
     * @param mixed $id Quick entry id.
     * @return array The quick entry row.
     */
    function get_quick_entry($id)
    {
        return ['base_desc' => 'Quick entry ' . $id, 'id' => $id];
    }
}

if (!function_exists('db_customer_has_branches')) {
    /**
     * Whether a customer has branches.
     *
     * @param mixed $customer_id Customer id.
     * @return bool True when the customer has branches.
     */
    function db_customer_has_branches($customer_id)
    {
        return empty($GLOBALS['__customer_has_no_branches']);
    }
}

if (!function_exists('customer_branches_list')) {
    /**
     * Render the branch list of a customer.
     *
     * @param mixed $customer_id Customer id.
     * @param string $name Form field name.
     * @param mixed $selected_id Selected option.
     * @param mixed $submit_on_change Submit on change flag.
     * @param mixed $enabled Enabled flag.
     * @param mixed $spec_option Special option flag.
     * @return string The rendered control.
     */
    function customer_branches_list(
        $customer_id,
        $name,
        $selected_id = null,
        $submit_on_change = false,
        $enabled = true,
        $spec_option = false
    ) {
        return "<select name='" . $name . "'></select>";
    }
}

if (!function_exists('get_customer_name')) {
    /**
     * Look up a customer name.
     *
     * @param mixed $customer_id Customer id.
     * @return string The customer name.
     */
    function get_customer_name($customer_id)
    {
        return 'Customer ' . $customer_id;
    }
}

if (!function_exists('get_branch_name')) {
    /**
     * Look up a branch name.
     *
     * @param mixed $branch_id Branch id.
     * @return string The branch name.
     */
    function get_branch_name($branch_id)
    {
        return 'Branch ' . $branch_id;
    }
}

if (!function_exists('get_customer_details_from_trans')) {
    /**
     * Look up the customer attached to a transaction.
     *
     * @param mixed $type Transaction type.
     * @param mixed $type_no Transaction number.
     * @return array The customer details.
     */
    function get_customer_details_from_trans($type, $type_no)
    {
        return [
            'name' => 'Customer for ' . $type_no,
            'br_name' => 'Branch for ' . $type_no,
        ];
    }
}

if (!function_exists('sql2date')) {
    /**
     * Convert an SQL date to the FA display format.
     *
     * @param mixed $date Date to convert.
     * @return string The converted date.
     */
    function sql2date($date)
    {
        return (string) $date;
    }
}
