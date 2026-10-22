<?php

declare(strict_types=1);

/**
 * Test doubles for the Ksfraser\FaBankImport classes that the legacy
 * transaction objects instantiate.
 *
 * The legacy Transaction class (src/Ksfraser/FaBankImport/Transaction.php)
 * instantiates a number of collaborators by their short, unqualified name:
 *
 *  - new FaGLWrapper($this)      -> the GL matching wrapper
 *  - new AddVendorButton($id)    -> the "add vendor" button
 *  - new AddNoButton($id)        -> the "no button" placeholder
 *  - new AddCustomerButton($id)  -> the "add customer" button
 *  - new ToggleTransactionTypeButton($id)
 *
 * The button classes live in src/Ksfraser/FaBankImport/Views/ but are
 * declared in the Ksfraser\FaBankImport namespace, so Composer's PSR-4 map
 * cannot reach them (the directory is "views", the namespace is not). The
 * files that can be loaded are loaded here, from their real location, so that
 * the production baseline test - which loads the very same files - does not
 * run into a redeclaration.
 *
 * AddVendorButton.php cannot be loaded at all: it requires
 * views/HTML/HTML_ROW_LABELDecorator.php, a file that does not exist. A
 * double stands in for it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport {

    use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;

    require_once __DIR__ . '/../../../../src/Ksfraser/FaBankImport/Views/AddNoButton.php';
    require_once __DIR__ . '/../../../../src/Ksfraser/FaBankImport/Views/AddCustomerButton.php';
    require_once __DIR__ . '/../../../../src/Ksfraser/FaBankImport/Views/ToggleTransactionTypeButton.php';

    if (!function_exists('Ksfraser\\FaBankImport\\shorten_bankAccount_Names')) {
        /**
         * Namespaced override of the legacy bank account name shortener.
         *
         * The legacy Transaction class calls the helper unqualified, so the
         * call resolves to this override first. The test can make the helper
         * fail to exercise the fallback of the constructor.
         *
         * @param string $name Raw account name.
         *
         * @return string
         */
        function shorten_bankAccount_Names($name)
        {
            FaStubRegistry::record('shorten_bankAccount_Names', [$name]);
            FaStubRegistry::throwIfArmed('shorten_bankAccount_Names');

            return \shorten_bankAccount_Names($name);
        }
    }

    if (!class_exists('Ksfraser\\FaBankImport\\fa_bank_accounts', false)) {
        /**
         * Test double for the fa_bank_accounts helper of the legacy
         * transaction.
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

    if (!class_exists('Ksfraser\\FaBankImport\\FaGLWrapper', false)) {
        /**
         * Test double for the GL matching wrapper.
         */
        class FaGLWrapper
        {
            /** @var mixed The transaction being matched. */
            private $transaction;

            /**
             * Constructor.
             *
             * @param mixed $transaction Transaction being matched.
             */
            public function __construct($transaction)
            {
                $this->transaction = $transaction;
                FaStubRegistry::record('FaGLWrapper::__construct', [$transaction]);
            }

            /**
             * Retrieve the GL rows matching the transaction.
             *
             * @return array<int, array<string, mixed>>
             */
            public function retrieveMatchingTransactions(): array
            {
                FaStubRegistry::record('FaGLWrapper::retrieveMatchingTransactions', []);
                FaStubRegistry::throwIfArmed('FaGLWrapper::retrieveMatchingTransactions');

                return FaStubRegistry::getReturn('FaGLWrapper::retrieveMatchingTransactions', []);
            }
        }
    }

    if (!class_exists('Ksfraser\\FaBankImport\\AddVendorButton', false)) {
        /**
         * Test double for the "add vendor" button.
         */
        class AddVendorButton
        {
            /** @var int Transaction id. */
            private $id;

            /**
             * Constructor.
             *
             * @param int $id Transaction id.
             */
            public function __construct($id = 0)
            {
                $this->id = $id;
            }

            /**
             * Render the button.
             *
             * @return void
             */
            public function toHtml(): void
            {
                FaStubRegistry::record('AddVendorButton::toHtml', [$this->id]);
            }
        }
    }
}
