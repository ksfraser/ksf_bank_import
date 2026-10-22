<?php

declare(strict_types=1);

/**
 * Helper that makes the global sumCharges() helper appear.
 *
 * AbstractTransactionHandler::calculateCharge() probes the *global* scope
 * with function_exists('sumCharges') before it calls the helper. Both the
 * "helper present" and the "helper absent" paths therefore have to be
 * exercised, which means the global function cannot be declared when the
 * test support files are loaded.
 *
 * This helper declares it on demand, at runtime.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace {

    if (!function_exists('ksf_processing_define_global_sum_charges')) {
        /**
         * Declare the global sumCharges() helper when it does not exist yet.
         *
         * @return bool True when the helper is available after the call.
         */
        function ksf_processing_define_global_sum_charges(): bool
        {
            if (!function_exists('sumCharges')) {
                eval('function sumCharges($transactionId) { return 0.0; }');
            }

            return function_exists('sumCharges');
        }
    }
}
