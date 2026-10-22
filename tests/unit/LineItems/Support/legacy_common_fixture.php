<?php

declare(strict_types=1);

/**
 * Publishes the legacy ksf_modules_common helpers the legacy line-item
 * classes load.
 *
 * Several classes under src/Ksfraser/FaBankImport/Models/ load their
 * collaborators from a path that is relative to the file:
 *
 *     __DIR__ . '/../../../../ksf_modules_common/class.fa_bank_accounts.php'
 *     __DIR__ . '/../../../../ksf_modules_common/class.fa_gl.php'
 *
 * In a real installation that directory sits next to the module inside
 * FrontAccounting's modules directory; in this repository it does not exist.
 * The fixture therefore publishes the doubles there for the duration of the
 * test and removes them again afterwards.
 *
 * The doubles are minimal: they expose only the members the production code
 * calls.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

/**
 * Publishes and removes the legacy helper doubles.
 */
final class LegacyCommonFixture
{
    /** @var string Root of the repository. */
    private $root;

    /** @var string The directory the doubles are published in. */
    private $modulesDir;

    /** @var string[] Files this fixture created. */
    private $created = [];

    /** @var bool Whether this fixture created the directory. */
    private $createdDir = false;

    /**
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
        $this->modulesDir = $this->root . '/ksf_modules_common';
    }

    /**
     * Publish the doubles.
     *
     * @param array $files Map of file name to PHP source.
     * @return string The directory the doubles live in.
     */
    public function publish(array $files = []): string
    {
        if (!is_dir($this->modulesDir)) {
            mkdir($this->modulesDir, 0777, true);
            $this->createdDir = true;
        }

        foreach ($files as $name => $source) {
            $path = $this->modulesDir . '/' . $name;
            if (file_exists($path)) {
                continue;
            }
            file_put_contents($path, $source);
            $this->created[] = $path;
        }

        return $this->modulesDir;
    }

    /**
     * Remove the published doubles again.
     *
     * @return void
     */
    public function cleanup(): void
    {
        foreach ($this->created as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->created = [];

        if ($this->createdDir && is_dir($this->modulesDir)) {
            foreach (glob($this->modulesDir . '/*') ?: [] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            rmdir($this->modulesDir);
        }
        $this->createdDir = false;
    }

    /**
     * Source of the global fa_bank_accounts double.
     *
     * @return string The PHP source.
     */
    public static function faBankAccountsSource(): string
    {
        return <<<'PHP'
<?php

if (!class_exists('fa_bank_accounts', false)) {
    class fa_bank_accounts
    {
        /** @var mixed */
        private $transaction;

        /**
         * @param mixed $transaction Transaction the helper was built for.
         */
        public function __construct($transaction = null)
        {
            $this->transaction = $transaction;
        }

        /**
         * @param string $bankAccountNumber Number of the bank account.
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
PHP;
    }

    /**
     * Source of the global fa_gl double.
     *
     * @return string The PHP source.
     */
    public static function faGlSource(): string
    {
        return <<<'PHP'
<?php

if (!class_exists('fa_gl', false)) {
    class fa_gl
    {
        /** @var array */
        public $values = [];

        /** @var array */
        public $matches = [];

        /** @var bool */
        public static $throwOnSearch = false;

        /**
         * @param string $field Field name.
         * @param mixed $value Field value.
         * @param bool $enforce Ignored.
         * @return bool
         */
        public function set($field, $value = null, $enforce = true)
        {
            $this->values[$field] = $value;
            return true;
        }

        /**
         * @param string $memo Optional memo filter.
         * @return array
         */
        public function find_matching_transactions($memo = null)
        {
            if (self::$throwOnSearch || !empty($GLOBALS['__fa_gl_throw'])) {
                throw new Exception('search failed');
            }
            return $this->matches;
        }
    }
}
PHP;
    }

    /**
     * Source of the generic_fa_interface_model double.
     *
     * @return string The PHP source.
     */
    public static function genericFaInterfaceSource(): string
    {
        return <<<'PHP'
<?php

if (!class_exists('generic_fa_interface_model', false)) {
    class generic_fa_interface_model
    {
        /** @var array */
        public $fields_array = [];

        /** @var array */
        public $table_details = [];

        /** @var string */
        public $company_prefix = '';

        /**
         * @param mixed ...$args Ignored legacy arguments.
         */
        public function __construct(...$args)
        {
        }

        /**
         * @param string $field Field name.
         * @param mixed $value Field value.
         * @param bool $enforce Ignored.
         * @return bool
         */
        public function set($field, $value = null, $enforce = true)
        {
            $this->$field = $value;
            return true;
        }

        /**
         * @param string $field Field name.
         * @return mixed
         */
        public function get($field)
        {
            return $this->$field ?? null;
        }
    }
}
PHP;
    }

    /**
     * Source of the defines.inc.php double.
     *
     * @return string The PHP source.
     */
    public static function definesSource(): string
    {
        return <<<'PHP'
<?php

if (!defined('KSF_FIELD_NOT_SET')) {
    define('KSF_FIELD_NOT_SET', 573000);
}
if (!defined('KSF_VALUE_NOT_SET')) {
    define('KSF_VALUE_NOT_SET', 573001);
}
PHP;
    }
}
