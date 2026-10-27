<?php

declare(strict_types=1);

/**
 * Scenario: the GL wrapper in src/Ksfraser/FaBankImport/FaGLWrapper.php.
 *
 * The wrapper loads the legacy fa_gl helper from a path that is missing its
 * separator, so the include never succeeds and the constructor always
 * throws. The scenario pins that behaviour.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/FaGLWrapper.php';

$transaction = new class {
    /** @var float */
    public $amount = 25.5;
    /** @var string */
    public $transactionDC = 'D';
    /** @var int */
    public $days_spread = 2;
    /** @var string */
    public $valueTimestamp = '2026-01-31';
    /** @var string */
    public $entryTimestamp = '2026-02-01';
    /** @var string */
    public $otherBankAccountName = 'Counterparty';
    /** @var string */
    public $transactionCode = 'REF-1';
    /** @var string */
    public $memo = 'A memo';
};

try {
    new \Ksfraser\FaBankImport\FaGLWrapper($transaction);
    echo "NO_EXCEPTION\n";
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

/*
 * The success path.
 *
 * The wrapper resolves the legacy helper at
 * <repo-parent>/ksf_modules_common/class.fa_gl.php. The first construction
 * above left that file marked as already included (PHP registers the file
 * even when its own require chain throws), so this second construction
 * does not re-execute the legacy chain. What is left is to have a fa_gl
 * class present, and - on a box without the legacy checkout - make the
 * include call itself return true by publishing the guarded double of
 * LegacyCommonFixture there. Nothing outside this run is modified:
 * the double is only written when the target file does not exist, and it
 * and its directory are removed again afterwards.
 */
require_once KSF_LINEITEMS_ROOT . '/tests/unit/LineItems/Support/legacy_common_fixture.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyCommonFixture;

$helperFile = dirname(KSF_LINEITEMS_ROOT) . '/ksf_modules_common/class.fa_gl.php';
$fixture = new LegacyCommonFixture(dirname(KSF_LINEITEMS_ROOT));
$published = false;

if (!class_exists('fa_gl', false)) {
    eval('class fa_gl
    {
        /** @var mixed */
        public $memo;
        /** @var array */
        public $values = [];
        /** @var array */
        public $matches = ["stubbed_match" => "A memo"];

        /**
         * @param string $field Field name.
         * @param mixed $value Field value.
         * @return bool
         */
        public function set($field, $value = null)
        {
            $this->values[$field] = $value;
            if ($field === "memo_") {
                $this->memo = $value;
            }

            return true;
        }

        /**
         * @param string $memo Optional memo filter.
         * @return array
         */
        public function find_matching_transactions($memo = null)
        {
            return $this->matches;
        }
    }');
    echo 'FA_GL_SOURCE: scenario-defined' . "\n";
}

if (!is_file($helperFile)) {
    $fixture->publish(['class.fa_gl.php' => LegacyCommonFixture::faGlSource()]);
    $published = true;
}

register_shutdown_function(static function () use ($fixture, $published): void {
    if ($published) {
        $fixture->cleanup();
    }
});

try {
    $wrapper = new \Ksfraser\FaBankImport\FaGLWrapper($transaction);
    $matches = $wrapper->retrieveMatchingTransactions();

    echo 'MATCHED: ' . (is_array($matches) ? (string) count($matches) : 'not-array') . "\n";
    echo 'MATCHED_MEMO: ' . (isset($matches['stubbed_match']) ? (string) $matches['stubbed_match'] : 'missing') . "\n";
} catch (\Throwable $e) {
    echo 'CAUGHT_ON_SUCCESS_PATH: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
} finally {
    if ($published) {
        $fixture->cleanup();
    }
}

echo 'HELPER_FILE_LEFT_BEHIND: ' . var_export(is_file($helperFile), true) . "\n";
echo "SCENARIO_OK\n";
