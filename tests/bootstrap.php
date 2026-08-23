<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Initialize any test-specific configurations
define('FA_ROOT', dirname(__DIR__));

// Start the session BEFORE any test emits output; later session_start()
// calls in tests see an active session instead of fataling on sent headers.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

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

// KSF module-common event/status constants (defines.inc.php equivalents).
// Values mirror defines.inc.php's counter scheme; exact values don't matter,
// they only need to be distinct.
$ksfEventCount = 573000;
foreach (array(
    'KSF_DUMMY_EVENT',
    'KSF_FIELD_NOT_SET',
    'KSF_VALUE_NOT_SET',
    'KSF_VALUE_SET_NO_REPLACE',
    'KSF_VALUE_SET',
    'KSF_VALUE_REPLACED',
    'KSF_VAR_NOT_SET',
    'KSF_RESULT_NOT_SET',
    'KSF_FIELD_NOT_CLASS_VAR',
    'KSF_PRIKEY_NOT_SET',
    'KSF_PRIKEY_NOT_DEFINED',
    'KSF_TABLE_NOT_DEFINED',
) as $ksfConstant) {
    if (!defined($ksfConstant)) {
        define($ksfConstant, $ksfEventCount++);
    }
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

        /**
         * Read declared (incl. protected) properties, mirroring the real
         * generic_fa_interface magic-getter behaviour relied upon by views.
         *
         * @param string $name Property name.
         * @return mixed Null when unset.
         */
        public function __get($name)
        {
            $reader = \Closure::bind(
                function () use ($name) {
                    return isset($this->$name) ? $this->$name : null;
                },
                $this,
                static::class
            );
            return $reader();
        }

        /**
         * Write declared (incl. protected) properties.
         *
         * @param string $name  Property name.
         * @param mixed  $value Value.
         * @return void
         */
        public function __set($name, $value): void
        {
            $writer = \Closure::bind(
                function () use ($name, $value) {
                    $this->$name = $value;
                },
                $this,
                static::class
            );
            $writer();
        }
    }
}

// Load the legacy class under compat mode so global \bi_lineitem exists.
if (!class_exists('bi_lineitem') && file_exists(__DIR__ . '/../class.bi_lineitem.php')) {
    require_once __DIR__ . '/../class.bi_lineitem.php';
}

// Legacy global view class (no namespace); loadable without FA context.
if (!class_exists('ViewBiLineItems') && file_exists(__DIR__ . '/../src/Ksfraser/FaBankImport/class.ViewBiLineItems.php')) {
    require_once __DIR__ . '/../src/Ksfraser/FaBankImport/class.ViewBiLineItems.php';
}

// Load test base classes (not autoloaded)
require_once __DIR__ . '/integration/DatabaseTestCase.php';
