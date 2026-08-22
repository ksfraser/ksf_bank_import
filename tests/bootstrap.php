<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Initialize any test-specific configurations
define('FA_ROOT', dirname(__DIR__));

/**
 * Legacy compat layer (refactor-psr).
 *
 * The legacy root classes (bi_lineitem etc.) hard-require ksf_modules_common
 * and FrontAccounting globals. Until they are decoupled from the
 * generic_fa_interface hierarchy, tests run with KSF_TEST_COMPAT set so those
 * requires are skipped, and minimal stand-ins are provided here:
 * - includes/fa_stubs.php: function_exists-guarded FA function stubs
 * - generic_fa_interface_model: inert base class stub
 *
 * NOTE: these stand-ins exist ONLY so legacy behaviour can be pinned by
 * characterization tests ahead of decoupling. They are NOT a substitute for
 * the refactor.
 */
define('KSF_TEST_COMPAT', true);

if (!defined('KSF_TEST_COMPAT_SKIP_STUBS')) {
    require_once __DIR__ . '/../includes/fa_stubs.php';
}

// Helper used by bi_lineitem's constructor; not part of fa_stubs yet.
if (!function_exists('shorten_bankAccount_Names')) {
    /**
     * Shorten long bank account descriptive names.
     *
     * @param string $name Raw account name.
     * @return string Shortened name (identity in test compat mode).
     */
    function shorten_bankAccount_Names(string $name): string {
        return $name;
    }
}

// Inert stand-in for the deep-inheritance legacy base class.
// bi_lineitem calls parent::__construct(null, null, null, null, null) and
// relies on inherited helpers only at display/render time, which the
// characterization tests exercise through the concrete class.
if (!class_exists('generic_fa_interface_model')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
    class generic_fa_interface_model
    {
        /** @var array Dynamically set properties. */
        public $config_values = array();

        /**
         * Tolerant constructor mirroring the legacy signature.
         *
         * @param mixed ...$args Ignored legacy arguments.
         */
        public function __construct(...$args)
        {
        }

        /**
         * Catch-all for inherited helper calls not needed under test.
         *
         * @param string $name Method name.
         * @param array  $args Arguments.
         * @return mixed|null Null in compat mode.
         */
        public function __call($name, $args)
        {
            return null;
        }

        /**
         * Catch-all static helper calls.
         *
         * @param string $name Method name.
         * @param array  $args Arguments.
         * @return mixed|null Null in compat mode.
         */
        public static function __callStatic($name, $args)
        {
            return null;
        }
    }
}

// Load the legacy class under compat mode so global \bi_lineitem exists.
if (!class_exists('bi_lineitem') && file_exists(__DIR__ . '/../class.bi_lineitem.php')) {
    require_once __DIR__ . '/../class.bi_lineitem.php';
}

// Load test base classes (not autoloaded)
require_once __DIR__ . '/integration/DatabaseTestCase.php';
