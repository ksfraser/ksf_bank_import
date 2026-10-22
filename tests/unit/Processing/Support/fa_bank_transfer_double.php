<?php

declare(strict_types=1);

/**
 * Controllable double for FrontAccounting's fa_bank_transfer helper class.
 *
 * The mock package (vendor/ksfraser/famock) declares the class with fixed
 * behaviour, which makes the failure paths of BankTransferTransactionHandler
 * unreachable. This double keeps the same behaviour but lets a test make the
 * reference generator or the transfer itself fail.
 *
 * It is loaded from tests/bootstrap.php *before* the mock package so that the
 * mock package does not declare the class a second time.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace {

    if (!class_exists('fa_bank_transfer', false)) {
        /**
         * Test double for FrontAccounting's fa_bank_transfer helper class.
         */
        class fa_bank_transfer
        {
            /** @var array<string, mixed> Values set on the transfer. */
            private $data = [];

            /**
             * Set a value on the transfer.
             *
             * @param string $key   Field name.
             * @param mixed  $value Field value.
             *
             * @return void
             */
            public function set($key, $value)
            {
                $this->data[$key] = $value;
            }

            /**
             * Read a value from the transfer.
             *
             * @param string $key Field name.
             *
             * @return mixed
             */
            public function get($key)
            {
                return $this->data[$key] ?? null;
            }

            /**
             * Write the transfer.
             *
             * @return int Mock transaction id.
             */
            public function write()
            {
                $this->data['trans_no'] = 123;

                return 123;
            }

            /**
             * Assign the next reference number.
             *
             * @return string Mock reference.
             */
            public function getNextRef()
            {
                if (!empty($GLOBALS['ksf_processing_bank_transfer_ref_error'])) {
                    throw new Exception('reference generator failed');
                }

                return 'BT001';
            }

            /**
             * Execute the transfer.
             *
             * @return bool Mock success.
             */
            public function add_bank_transfer()
            {
                if (!empty($GLOBALS['ksf_processing_bank_transfer_error'])) {
                    throw new Exception('bank transfer failed');
                }

                $this->data['trans_no'] = 123;
                $this->data['trans_type'] = 4;

                return true;
            }
        }
    }
}
