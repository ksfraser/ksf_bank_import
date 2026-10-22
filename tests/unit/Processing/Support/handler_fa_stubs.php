<?php

declare(strict_types=1);

/**
 * FrontAccounting function doubles for the transaction handlers.
 *
 * The handlers live in the Ksfraser\FaBankImport\Handlers namespace and call
 * FrontAccounting helpers unqualified (write_supp_payment(), db_escape(),
 * get_bank_accounts(), ...). PHP resolves an unqualified function call to the
 * current namespace first and only then falls back to the global scope, so
 * declaring the doubles in this namespace intercepts the handler calls
 * without touching the global scope for the rest of the test suite.
 *
 * Every double records its call in FaStubRegistry and returns whatever the
 * test configured (or a sane default).
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Handlers {

    use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;

    if (!function_exists(__NAMESPACE__ . '\\user_numeric')) {
        /**
         * Convert a user-entered number into a float.
         *
         * @param mixed $number Value to convert.
         *
         * @return float
         */
        function user_numeric($number): float
        {
            FaStubRegistry::record('user_numeric', [$number]);
            FaStubRegistry::throwIfArmed('user_numeric');

            return (float) FaStubRegistry::getReturn('user_numeric', $number);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sql2date')) {
        /**
         * Convert a SQL date into a display date.
         *
         * @param string $date SQL date.
         *
         * @return string
         */
        function sql2date($date): string
        {
            FaStubRegistry::record('sql2date', [$date]);
            FaStubRegistry::throwIfArmed('sql2date');

            return (string) FaStubRegistry::getReturn('sql2date', $date);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sumCharges')) {
        /**
         * Sum the bank charges recorded against a transaction.
         *
         * @param int $transactionId Transaction id.
         *
         * @return float
         */
        function sumCharges($transactionId): float
        {
            FaStubRegistry::record('sumCharges', [$transactionId]);
            FaStubRegistry::throwIfArmed('sumCharges');

            return (float) FaStubRegistry::getReturn('sumCharges', 0.0);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\write_supp_payment')) {
        /**
         * Write a supplier payment.
         *
         * @param mixed ...$arguments Payment arguments.
         *
         * @return int Payment number.
         */
        function write_supp_payment(...$arguments): int
        {
            FaStubRegistry::record('write_supp_payment', $arguments);
            FaStubRegistry::throwIfArmed('write_supp_payment');

            return (int) FaStubRegistry::getReturn('write_supp_payment', 42);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\my_write_customer_payment')) {
        /**
         * Write a customer payment.
         *
         * @param mixed ...$arguments Payment arguments.
         *
         * @return int Payment number.
         */
        function my_write_customer_payment(...$arguments): int
        {
            FaStubRegistry::record('my_write_customer_payment', $arguments);
            FaStubRegistry::throwIfArmed('my_write_customer_payment');

            return (int) FaStubRegistry::getReturn('my_write_customer_payment', 77);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\write_bank_transaction')) {
        /**
         * Write a bank transaction.
         *
         * @param mixed ...$arguments Transaction arguments.
         *
         * @return array{0:int,1:int} Transaction type and number.
         */
        function write_bank_transaction(...$arguments): array
        {
            FaStubRegistry::record('write_bank_transaction', $arguments);
            FaStubRegistry::throwIfArmed('write_bank_transaction');

            return FaStubRegistry::getReturn('write_bank_transaction', [1, 99]);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\get_trans_counterparty')) {
        /**
         * Read the counterparty rows of a transaction.
         *
         * @param int $type    Transaction type.
         * @param int $typeNo  Transaction number.
         *
         * @return array<int, array<string, mixed>>
         */
        function get_trans_counterparty($type, $typeNo): array
        {
            FaStubRegistry::record('get_trans_counterparty', [$type, $typeNo]);
            FaStubRegistry::throwIfArmed('get_trans_counterparty');

            return FaStubRegistry::getReturn('get_trans_counterparty', []);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\update_transactions')) {
        /**
         * Update the status of one or more imported transactions.
         *
         * @param mixed ...$arguments Update arguments.
         *
         * @return bool
         */
        function update_transactions(...$arguments): bool
        {
            FaStubRegistry::record('update_transactions', $arguments);
            FaStubRegistry::throwIfArmed('update_transactions');

            return (bool) FaStubRegistry::getReturn('update_transactions', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\update_partner_data')) {
        /**
         * Store the counterparty data observed for a transaction.
         *
         * @param mixed ...$arguments Update arguments.
         *
         * @return bool
         */
        function update_partner_data(...$arguments): bool
        {
            FaStubRegistry::record('update_partner_data', $arguments);
            FaStubRegistry::throwIfArmed('update_partner_data');

            return (bool) FaStubRegistry::getReturn('update_partner_data', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\set_partner_data')) {
        /**
         * Store partner data for a counterparty.
         *
         * @param mixed ...$arguments Update arguments.
         *
         * @return bool
         */
        function set_partner_data(...$arguments): bool
        {
            FaStubRegistry::record('set_partner_data', $arguments);
            FaStubRegistry::throwIfArmed('set_partner_data');

            return (bool) FaStubRegistry::getReturn('set_partner_data', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\set_bank_partner_data')) {
        /**
         * Store bank partner data for a transaction.
         *
         * @param mixed ...$arguments Update arguments.
         *
         * @return bool
         */
        function set_bank_partner_data(...$arguments): bool
        {
            FaStubRegistry::record('set_bank_partner_data', $arguments);
            FaStubRegistry::throwIfArmed('set_bank_partner_data');

            return (bool) FaStubRegistry::getReturn('set_bank_partner_data', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\get_supplier')) {
        /**
         * Read a supplier record.
         *
         * @param int $supplierId Supplier id.
         *
         * @return array<string, mixed>
         */
        function get_supplier($supplierId): array
        {
            FaStubRegistry::record('get_supplier', [$supplierId]);
            FaStubRegistry::throwIfArmed('get_supplier');

            return FaStubRegistry::getReturn('get_supplier', [
                'payable_account' => '4000',
                'dimension_id' => 0,
                'dimension2_id' => 0,
            ]);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\add_cust_allocation')) {
        /**
         * Allocate a customer payment against an invoice.
         *
         * @param mixed ...$arguments Allocation arguments.
         *
         * @return bool
         */
        function add_cust_allocation(...$arguments): bool
        {
            FaStubRegistry::record('add_cust_allocation', $arguments);
            FaStubRegistry::throwIfArmed('add_cust_allocation');

            return (bool) FaStubRegistry::getReturn('add_cust_allocation', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\update_debtor_trans_allocation')) {
        /**
         * Recalculate the allocation of a debtor transaction.
         *
         * @param mixed ...$arguments Update arguments.
         *
         * @return bool
         */
        function update_debtor_trans_allocation(...$arguments): bool
        {
            FaStubRegistry::record('update_debtor_trans_allocation', $arguments);
            FaStubRegistry::throwIfArmed('update_debtor_trans_allocation');

            return (bool) FaStubRegistry::getReturn('update_debtor_trans_allocation', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\qe_to_cart')) {
        /**
         * Load a quick entry template into an items cart.
         *
         * @param mixed ...$arguments Template arguments.
         *
         * @return mixed
         */
        function qe_to_cart(...$arguments)
        {
            FaStubRegistry::record('qe_to_cart', $arguments);
            FaStubRegistry::throwIfArmed('qe_to_cart');

            $cart = $arguments[0] ?? null;
            $amount = $arguments[2] ?? 0;

            if (is_object($cart) && FaStubRegistry::getReturn('qe_to_cart.add_gl_item', true)) {
                $cart->add_gl_item('1000', 0, 0, (float) $amount, 'Quick Entry');
            }

            return FaStubRegistry::getReturn('qe_to_cart', 1);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\is_date_in_fiscalyear')) {
        /**
         * Check whether a date falls inside the open fiscal year.
         *
         * @param string $date Date to check.
         *
         * @return bool
         */
        function is_date_in_fiscalyear($date): bool
        {
            FaStubRegistry::record('is_date_in_fiscalyear', [$date]);
            FaStubRegistry::throwIfArmed('is_date_in_fiscalyear');

            return (bool) FaStubRegistry::getReturn('is_date_in_fiscalyear', true);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\end_fiscalyear')) {
        /**
         * Last day of the open fiscal year.
         *
         * @return string
         */
        function end_fiscalyear(): string
        {
            FaStubRegistry::record('end_fiscalyear', []);
            FaStubRegistry::throwIfArmed('end_fiscalyear');

            return (string) FaStubRegistry::getReturn('end_fiscalyear', '2025-12-31');
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\begin_transaction')) {
        /**
         * Start a database transaction.
         *
         * @return void
         */
        function begin_transaction(): void
        {
            FaStubRegistry::record('begin_transaction', []);
            FaStubRegistry::throwIfArmed('begin_transaction');
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\commit_transaction')) {
        /**
         * Commit the open database transaction.
         *
         * @return void
         */
        function commit_transaction(): void
        {
            FaStubRegistry::record('commit_transaction', []);
            FaStubRegistry::throwIfArmed('commit_transaction');
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\get_company_pref')) {
        /**
         * Read a company preference.
         *
         * @param string $name    Preference name.
         * @param mixed  $default Value used when the preference is not set.
         *
         * @return mixed
         */
        function get_company_pref($name, $default = '')
        {
            FaStubRegistry::record('get_company_pref', [$name, $default]);

            return FaStubRegistry::getReturn('get_company_pref', $default);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\number_format2')) {
        /**
         * Format a number with two decimals.
         *
         * @param float $number Number to format.
         *
         * @return string
         */
        function number_format2($number): string
        {
            FaStubRegistry::record('number_format2', [$number]);

            return number_format((float) $number, 2, '.', ',');
        }
    }
}
