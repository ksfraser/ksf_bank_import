<?php

/**
 * Test double for FrontAccounting's get_vendor_list().
 *
 * VendorListManager (namespace KsfBankImport) calls get_vendor_list() from
 * inside its own namespace, so the double has to live in that namespace too.
 *
 * The double counts how often the "database" was hit, which is what the
 * caching tests actually assert: a cached load must NOT call the loader
 * again.
 *
 * @package    Tests\Helpers
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace KsfBankImport {
    /**
     * Records the calls made to the get_vendor_list() double.
     */
    class VendorListDouble
    {
        /** @var int Number of times the loader was called */
        public static $calls = 0;

        /** @var array<int, string> The vendor list the double hands out */
        public static $vendors = [
            1 => 'Test Vendor 1',
            2 => 'Test Vendor 2',
        ];

        /**
         * Reset the call counter and the vendor list.
         *
         * @return void
         */
        public static function reset(): void
        {
            self::$calls = 0;
            self::$vendors = [
                1 => 'Test Vendor 1',
                2 => 'Test Vendor 2',
            ];
        }

        /**
         * Hand out the current vendor list.
         *
         * @return array<int, string>
         */
        public static function list(): array
        {
            self::$calls++;

            return self::$vendors;
        }
    }

    if (!function_exists('KsfBankImport\get_vendor_list')) {
        /**
         * FrontAccounting vendor list (test double).
         *
         * @return array<int, string>
         */
        function get_vendor_list()
        {
            return VendorListDouble::list();
        }
    }
}
