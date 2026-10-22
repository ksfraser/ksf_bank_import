<?php

declare(strict_types=1);

/**
 * Global double for the fa_bank_accounts helper the legacy transaction
 * object loads.
 *
 * The double lives outside the repository (the test publishes it through a
 * working directory) so that the coverage report is not polluted with it.
 *
 * Only fa_bank_accounts is declared here: the legacy transaction object
 * declares the GL matcher in a block that is commented out, and a global
 * fa_gl class would collide with the doubles other tests publish.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace {

    if (!class_exists('fa_bank_accounts', false)) {
        /**
         * Test double for FrontAccounting's fa_bank_accounts helper class.
         */
        class fa_bank_accounts
        {
            /** @var mixed The transaction the helper was built for. */
            private $transaction;

            /**
             * Constructor.
             *
             * @param mixed $transaction Transaction the helper is built for.
             */
            public function __construct($transaction = null)
            {
                $this->transaction = $transaction;
            }

            /**
             * Look the bank account up by its number.
             *
             * @param string $bankAccountNumber Number of the bank account.
             *
             * @return array<string, mixed>
             */
            public function getByBankAccountNumber($bankAccountNumber)
            {
                return [
                    'id' => 1,
                    'bank_account_name' => 'Chequing ' . $bankAccountNumber,
                    'account_code' => '1001',
                    'bank_curr_code' => 'CAD',
                ];
            }
        }
    }
}
