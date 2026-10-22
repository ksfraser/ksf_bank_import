<?php

declare(strict_types=1);

/**
 * Scenario: the FA backed matching journal entry finder.
 *
 * The finder loads the legacy journal entry matcher of the module common
 * package, which is not part of this repository. The scenario publishes the
 * helper next to the module for the duration of the run, so the finder
 * finds it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/fa_stubs.php';
require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/legacy_common_fixture.php';

use Ksfraser\FaBankImport\Models\FaMatchingJEsFinder;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyCommonFixture;

$fixture = new LegacyCommonFixture(KSF_GAP_ROOT);
$fixture->publish([
    'class.fa_gl.php' => LegacyCommonFixture::faGlSource(),
]);

register_shutdown_function(static function () use ($fixture): void {
    $fixture->cleanup();
});

$lineItem = new class {
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

$finder = new FaMatchingJEsFinder();

echo 'LEGACY_AVAILABLE: ' . var_export(FaMatchingJEsFinder::legacyAvailable(), true) . "\n";
echo 'MATCHES: ' . json_encode($finder->findFor($lineItem)) . "\n";

echo "SCENARIO_OK\n";
