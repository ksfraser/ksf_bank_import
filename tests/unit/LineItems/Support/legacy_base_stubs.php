<?php

declare(strict_types=1);

/**
 * Test doubles for the legacy base class and the FA DAO wrappers the
 * line-item models call.
 *
 * The models under src/Ksfraser/FaBankImport/Models/ extend
 * Ksfraser\common\GenericFaInterface and talk to
 * Ksfraser\frontaccounting\FaGl / FaBankAccounts. None of those classes live
 * in this repository - they belong to the legacy ksf_modules_common package -
 * so they are declared here.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\common {

    if (!class_exists('Ksfraser\common\GenericFaInterface', false)) {
        /**
         * Stand-in for the legacy generic_fa_interface base class.
         */
        class GenericFaInterface
        {
            /** @var int */
            public $matched = 0;

            /** @var int */
            public $created = 0;

            /** @var array */
            public $values = [];

            /**
             * @param mixed ...$args Ignored legacy arguments.
             */
            public function __construct(...$args)
            {
            }

            /**
             * Store a value.
             *
             * @param string $field Field name.
             * @param mixed $value Field value.
             * @param bool $enforce Ignored.
             * @return bool Always true.
             */
            public function set($field, $value = null, $enforce = true)
            {
                $this->values[$field] = $value;

                return true;
            }

            /**
             * Read a value.
             *
             * @param string $field Field name.
             * @return mixed The stored value.
             */
            public function get($field)
            {
                return $this->values[$field] ?? null;
            }
        }
    }
}

namespace Ksfraser\frontaccounting {

    if (!class_exists('Ksfraser\frontaccounting\FaGl', false)) {
        /**
         * Stand-in for the legacy fa_gl DAO wrapper.
         */
        class FaGl
        {
            /** @var array Rows returned by every instance of this double. */
            public static $matches = [];

            /** @var array Criteria handed to the last created instance. */
            public static $lastValues = [];

            /** @var array */
            public $values = [];

            /**
             * @param array|null $matches Rows the finder should return.
             */
            public function __construct(array $matches = null)
            {
                if ($matches !== null) {
                    self::$matches = $matches;
                }
            }

            /**
             * Store a search criterion.
             *
             * @param string $field Field name.
             * @param mixed $value Field value.
             * @param bool $enforce Ignored.
             * @return bool Always true.
             */
            public function set($field, $value = null, $enforce = true)
            {
                $this->values[$field] = $value;
                self::$lastValues[$field] = $value;

                return true;
            }

            /**
             * @param string $memo Optional memo filter.
             * @return array The configured match rows.
             */
            public function find_matching_transactions($memo = null)
            {
                return self::$matches;
            }
        }
    }

    if (!class_exists('Ksfraser\frontaccounting\FaBankAccounts', false)) {
        /**
         * Stand-in for the legacy fa_bank_accounts DAO wrapper.
         */
        class FaBankAccounts
        {
            /** @var mixed */
            private $owner;

            /** @var array */
            public $details = [
                'bank_account_name' => 'Chequing',
                'account_code' => '1061',
            ];

            /**
             * @param mixed $owner Object the wrapper was built for.
             */
            public function __construct($owner = null)
            {
                $this->owner = $owner;
            }

            /**
             * Look a bank account up by its number.
             *
             * @param string $number Bank account number.
             * @return array The account row.
             */
            public function getByBankAccountNumber($number)
            {
                return $this->details;
            }
        }
    }
}
