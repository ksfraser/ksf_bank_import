<?php

declare(strict_types=1);

/**
 * FrontAccounting stand-ins for the procedural bank-import screens.
 *
 * The bank-import screens (import_statements.php, manage_partners_data.php,
 * view_statements.php, header_table.php) are page level scripts that call
 * FrontAccounting functions directly. This file provides the small set of
 * FrontAccounting functions those scripts rely on but that the shared mock
 * package (vendor/ksfraser/famock) does not ship.
 *
 * IMPORTANT: this file MUST be loaded *before* vendor/ksfraser/famock so that
 * the database doubles below win over the ones in the mock package (the mock
 * package guards every declaration with function_exists()).
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support
 * @license    MIT
 */

namespace {

    if (!defined('TABLESTYLE')) {
        define('TABLESTYLE', 'tablestyle');
    }

    if (!defined('TABLESTYLE_NOBORDER')) {
        define('TABLESTYLE_NOBORDER', 'tablestyle_noborder');
    }

    /**
     * Resets the in-memory database double.
     *
     * @return void
     */
    function screen_db_reset(): void
    {
        $GLOBALS['__screen_db'] = [
            'queries' => [],
            'queued' => [],
            'rows' => [],
            'pos' => 0,
            'insert_id' => 0,
            'affected' => 0,
            'last_sql' => '',
        ];
    }

    /**
     * Queues the rows the next matching db_query() call has to return.
     *
     * @param array       $rows  Rows the query returns.
     * @param string|null $match Only consume the entry when the statement
     *                            contains this string.
     *
     * @return void
     */
    function screen_db_queue(array $rows, $match = null): void
    {
        if (!isset($GLOBALS['__screen_db'])) {
            screen_db_reset();
        }
        $GLOBALS['__screen_db']['queued'][] = ['match' => $match, 'rows' => $rows];
    }

    /**
     * Returns the SQL statements executed so far.
     *
     * @return array
     */
    function screen_db_queries(): array
    {
        if (!isset($GLOBALS['__screen_db'])) {
            screen_db_reset();
        }

        return $GLOBALS['__screen_db']['queries'];
    }

    /**
     * Column list used by the SHOW COLUMNS introspection of the legacy models.
     *
     * @param string $table Table name, with or without company prefix.
     *
     * @return array
     */
    function screen_db_columns($table): array
    {
        $known = [
            'bi_statements' => [
                'id', 'bank', 'account', 'currency', 'startBalance', 'endBalance',
                'smtDate', 'number', 'seq', 'statementId', 'acctid', 'fitid',
                'bankid', 'intu_bid',
            ],
            'bi_transactions' => [
                'id', 'smt_id', 'valueTimestamp', 'entryTimestamp', 'account',
                'accountName', 'transactionType', 'transactionCode',
                'transactionCodeDesc', 'transactionDC', 'transactionAmount',
                'transactionTitle', 'merchant', 'category', 'status', 'memo',
                'sic', 'acctid', 'fitid', 'bankid', 'intu_bid', 'checknumber',
            ],
        ];

        $name = preg_replace('/^[0-9]+_/', '', (string) $table);
        $rows = [];
        foreach ($known[$name] ?? [] as $field) {
            $rows[] = ['Field' => $field];
        }

        return $rows;
    }

    if (!function_exists('db_query')) {
        /**
         * Records the statement and prepares the rows the next fetch returns.
         *
         * @param string $sql   Statement to run.
         * @param string $error Optional error message.
         *
         * @return string
         */
        function db_query($sql, $error = null)
        {
            if (!isset($GLOBALS['__screen_db'])) {
                screen_db_reset();
            }

            $GLOBALS['__screen_db']['queries'][] = $sql;
            $GLOBALS['__screen_db']['last_sql'] = $sql;

            if (!empty($GLOBALS['__screen_db']['queued'])) {
                $index = null;
                foreach ($GLOBALS['__screen_db']['queued'] as $key => $entry) {
                    if ($entry['match'] === null || strpos((string) $sql, (string) $entry['match']) !== false) {
                        $index = $key;
                        break;
                    }
                }
                if ($index === null) {
                    $GLOBALS['__screen_db']['rows'] = [];
                } else {
                    $entry = $GLOBALS['__screen_db']['queued'][$index];
                    unset($GLOBALS['__screen_db']['queued'][$index]);
                    $GLOBALS['__screen_db']['queued'] = array_values($GLOBALS['__screen_db']['queued']);
                    $GLOBALS['__screen_db']['rows'] = $entry['rows'];
                }
            } elseif (preg_match('/^\s*SHOW\s+COLUMNS\s+FROM\s+(\S+)/i', (string) $sql, $matches)) {
                $GLOBALS['__screen_db']['rows'] = screen_db_columns($matches[1]);
            } else {
                $GLOBALS['__screen_db']['rows'] = [];
            }

            $GLOBALS['__screen_db']['pos'] = 0;

            return $sql;
        }
    }

    if (!function_exists('db_fetch')) {
        /**
         * Returns the next row of the current result set.
         *
         * @param mixed $res Result handle.
         *
         * @return array|false
         */
        function db_fetch($res)
        {
            if (!isset($GLOBALS['__screen_db'])) {
                return false;
            }

            $rows = $GLOBALS['__screen_db']['rows'];
            $pos = $GLOBALS['__screen_db']['pos'];
            if ($pos >= count($rows)) {
                return false;
            }

            $GLOBALS['__screen_db']['pos'] = $pos + 1;

            return $rows[$pos];
        }
    }

    if (!function_exists('db_fetch_assoc')) {
        /**
         * Alias of db_fetch() used by the legacy models.
         *
         * @param mixed $res Result handle.
         *
         * @return array|false
         */
        function db_fetch_assoc($res)
        {
            return db_fetch($res);
        }
    }

    if (!function_exists('db_num_rows')) {
        /**
         * Number of rows in the current result set.
         *
         * @param mixed $res Result handle.
         *
         * @return int
         */
        function db_num_rows($res): int
        {
            if (!isset($GLOBALS['__screen_db'])) {
                return 0;
            }

            return count($GLOBALS['__screen_db']['rows']);
        }
    }

    if (!function_exists('db_escape')) {
        /**
         * Escapes a value for use inside a statement.
         *
         * @param mixed $value Value to escape.
         *
         * @return string
         */
        function db_escape($value): string
        {
            if (is_null($value)) {
                return '';
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return addslashes((string) $value);
        }
    }

    if (!function_exists('db_insert_id')) {
        /**
         * Identifier generated by the last insert.
         *
         * @return int
         */
        function db_insert_id(): int
        {
            if (!isset($GLOBALS['__screen_db'])) {
                return 0;
            }

            return (int) $GLOBALS['__screen_db']['insert_id'];
        }
    }

    if (!function_exists('db_affected_rows')) {
        /**
         * Number of rows changed by the last statement.
         *
         * @return int
         */
        function db_affected_rows()
        {
            global $_test_db_affected_rows;
            if (isset($_test_db_affected_rows)) {
                return (int) $_test_db_affected_rows;
            }
            if (!isset($GLOBALS['__screen_db'])) {
                return 0;
            }

            return (int) $GLOBALS['__screen_db']['affected'];
        }
    }

    if (!function_exists('hidden')) {
        /**
         * Renders a hidden input.
         *
         * The FrontAccounting signature is hidden($name, $value); the screens
         * also call hidden($name) without a value.
         *
         * @param string $name  Field name.
         * @param string $value Field value.
         *
         * @return void
         */
        function hidden($name, $value = '')
        {
            echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars((string) $value) . '">';
        }
    }

    if (!function_exists('submit_center_last')) {
        /**
         * Renders the last submit button of a form.
         *
         * @param string $name  Field name.
         * @param string $value Button label.
         * @param string $params Extra markup.
         *
         * @return void
         */
        function submit_center_last($name, $value, $params = '')
        {
            echo "<tr><td colspan='2' align='center'><input type='submit' name='$name' value='$value'></td></tr>";
        }
    }

    if (!function_exists('date_cells')) {
        /**
         * Renders a date input pair.
         *
         * @param string $label Label.
         * @param string $name  Field name.
         * @param string $value Current value.
         * @param mixed  $extra Unused.
         * @param int    $inc  Unused.
         *
         * @return void
         */
        function date_cells($label, $name, $value = '', $extra = null, $inc = 0)
        {
            echo '<tr><td>' . $label . '</td><td><input type="text" name="' . $name .
                '" value="' . htmlspecialchars((string) $value) . '"></td></tr>';
        }
    }

    if (!function_exists('label_cells')) {
        /**
         * Renders a label/value pair.
         *
         * @param string $label Label.
         * @param mixed  $value Value.
         *
         * @return void
         */
        function label_cells($label, $value)
        {
            echo '<tr><td class="label">' . $label . '</td><td>' . $value . '</td></tr>';
        }
    }

    if (!function_exists('amount_cell')) {
        /**
         * Renders a currency amount.
         *
         * @param float $amount Amount.
         *
         * @return void
         */
        function amount_cell($amount, $bold = false)
        {
            echo '<td align="right">' . number_format((float) $amount, 2) . '</td>';
        }
    }

    if (!function_exists('div_start')) {
        /**
         * Opens a named div.
         *
         * @param string $id    Div id.
         * @param string $class Div class.
         *
         * @return void
         */
        function div_start($id, $class = '')
        {
            echo '<div id="' . $id . '"' . ($class ? ' class="' . $class . '"' : '') . '>';
        }
    }

    if (!function_exists('div_end')) {
        /**
         * Closes the current div.
         *
         * @return void
         */
        function div_end()
        {
            echo '</div>';
        }
    }

    if (!function_exists('array_selector')) {
        /**
         * Renders a select box for the given options.
         *
         * @param string $name     Field name.
         * @param mixed  $selected Selected value.
         * @param array  $options  Options.
         * @param array  $params   Extra parameters.
         *
         * @return string
         */
        function array_selector($name, $selected, $options, $params = [])
        {
            $html = '<select name="' . $name . '">';
            foreach ($options as $value => $label) {
                $html .= '<option value="' . $value . '"' . ((string) $value === (string) $selected ? ' selected' : '') . '>' .
                    $label . '</option>';
            }

            return $html . '</select>';
        }
    }

    if (!function_exists('get_js_open_window')) {
        /**
         * Javascript opening a popup window.
         *
         * @param int $width  Window width.
         * @param int $height Window height.
         *
         * @return string
         */
        function get_js_open_window($width, $height)
        {
            return '<script>function open_window(a,b){window.open(a,b,"width=' . $width . ',height=' . $height . '");}</script>';
        }
    }

    if (!function_exists('get_js_date_picker')) {
        /**
         * Javascript enabling the date picker.
         *
         * @return string
         */
        function get_js_date_picker()
        {
            return '<script>function datepicker(){/* noop */}</script>';
        }
    }

    if (!function_exists('bank_accounts_list_row')) {
        /**
         * Renders the bank account picker row.
         *
         * @param string $label            Label.
         * @param string $name             Field name.
         * @param mixed  $selected_id      Selected account.
         * @param bool   $submit_on_change Submit on change.
         *
         * @return void
         */
        function bank_accounts_list_row($label, $name, $selected_id = null, $submit_on_change = false)
        {
            echo '<tr><td>' . $label . '</td><td><select name="' . $name . '">';
            foreach (get_bank_accounts() as $id => $account) {
                echo '<option value="' . $id . '">' . $account['bank_account_name'] . '</option>';
            }
            echo '</select></td></tr>';
        }
    }

    if (!function_exists('get_bank_accounts')) {
        /**
         * The bank accounts known to the screen.
         *
         * @return array
         */
        function get_bank_accounts()
        {
            return [
                1 => [
                    'id' => 1,
                    'bank_account_name' => 'Chequing Account',
                    'bank_account_number' => 'ACC-0001',
                    'bank_curr_code' => 'CAD',
                    'account_code' => '1060',
                    'account_type' => 'B',
                    'bank_charge_act' => '2230',
                    'inactive' => 0,
                ],
            ];
        }
    }

    if (!function_exists('get_bank_account')) {
        /**
         * A single bank account.
         *
         * @param int $id Account id.
         *
         * @return array
         */
        function get_bank_account($id)
        {
            $accounts = get_bank_accounts();
            if (isset($accounts[$id])) {
                return $accounts[$id];
            }

            return [];
        }
    }

    if (!function_exists('db_customer_has_branches')) {
        /**
         * Whether the customer has branches.
         *
         * @param int $customer_id Customer id.
         *
         * @return bool
         */
        function db_customer_has_branches($customer_id)
        {
            return (bool) $GLOBALS['__screen_customer_has_branches'];
        }
    }

    if (!function_exists('textarea_row')) {
        /**
         * Renders a textarea row.
         *
         * The FrontAccounting signature accepts a null value (the screens pass
         * @$_POST['data'] which is unset on the first render); the mock
         * package declares the parameter as a non-nullable string.
         *
         * @param string $label Label.
         * @param string $name  Field name.
         * @param string $value Current value.
         * @param int    $rows  Number of rows.
         * @param int    $cols  Number of columns.
         * @param string $title Title attribute.
         *
         * @return void
         */
        function textarea_row($label, $name, $value = '', $rows = 5, $cols = 40, $title = '')
        {
            echo '<tr><td>' . $label . '</td><td><textarea name="' . $name . '" rows="' . (int) $rows .
                '" cols="' . (int) $cols . '">' . htmlspecialchars((string) $value) . '</textarea></td></tr>';
        }
    }

    if (!function_exists('display_error')) {
        /**
         * Records an error.
         *
         * The harness can make the call fail so that the calling screen's own
         * error handling can be exercised.
         *
         * @param string $message Error message.
         *
         * @return void
         *
         * @throws \RuntimeException When the harness asks for it.
         */
        function display_error($message)
        {
            if (!isset($GLOBALS['test_errors'])) {
                $GLOBALS['test_errors'] = [];
            }
            $GLOBALS['test_errors'][] = $message;

            if (!empty($GLOBALS['__screen_display_error_throws'])) {
                throw new \RuntimeException('display_error failed: ' . $message);
            }
        }
    }

    if (!function_exists('list_updated')) {
        /**
         * Whether the given list was refreshed by the browser.
         *
         * @param string $name List name.
         *
         * @return bool
         */
        function list_updated($name): bool
        {
            return !empty($GLOBALS['__screen_list_updated'][$name]);
        }
    }

    if (!function_exists('get_branch')) {
        /**
         * A customer branch.
         *
         * @param int $branch_code Branch id.
         *
         * @return array
         */
        function get_branch($branch_code): array
        {
            $branches = $GLOBALS['__screen_branches'] ?? [];

            return $branches[$branch_code] ?? [];
        }
    }

    if (!function_exists('set_focus')) {
        /**
         * Records the field the screen wants the cursor in.
         *
         * @param string $name Field name.
         *
         * @return void
         */
        function set_focus($name): void
        {
            $GLOBALS['__screen_focus'] = $name;
        }
    }

    if (!function_exists('date2sql')) {
        /**
         * Converts a date into its SQL representation.
         *
         * @param string $date Date.
         *
         * @return string
         */
        function date2sql($date)
        {
            return (string) $date;
        }
    }
}
