<?php

declare(strict_types=1);

/**
 * Doubles for the legacy base class and the FA DAO wrappers the isolated
 * line-item scenarios use.
 *
 * The two legacy models under src/Ksfraser/Model/ and
 * src/Ksfraser/FaBankImport/model/ extend
 * Ksfraser\FaBankImport\Model\GenericFaInterfaceModel and talk to
 * Ksfraser\frontaccounting\FaGl / FaBankAccounts. None of those classes
 * exist in this repository - they belong to the legacy ksf_modules_common
 * package - so they are declared here.
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
                $this->$field = $value;

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

namespace Ksfraser\FaBankImport\Model {

    if (!class_exists('Ksfraser\FaBankImport\Model\GenericFaInterfaceModel', false)) {
        /**
         * Stand-in for the legacy generic_fa_interface base class.
         */
        class GenericFaInterfaceModel
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
                $this->$field = $value;

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

            /**
             * Copy the public members of one object onto another.
             *
             * @param object $source Object to copy from.
             * @return int Number of members copied.
             */
            public function obj2obj($source)
            {
                $count = 0;
                foreach (get_object_vars($source) as $name => $value) {
                    $this->$name = $value;
                    $count++;
                }

                return $count;
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
            /** @var array Rows the finder returns. */
            public static $matches = [];

            /** @var bool Whether the finder throws. */
            public static $throwOnSearch = false;

            /** @var array */
            public $values = [];

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

                return true;
            }

            /**
             * @param string $memo Optional memo filter.
             * @return array The configured match rows.
             */
            public function find_matching_transactions($memo = null)
            {
                if (self::$throwOnSearch) {
                    throw new \Exception('search failed');
                }

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
