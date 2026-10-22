<?php

declare(strict_types=1);

/**
 * Scenario: the FA backed bank account provider.
 *
 * The provider loads the legacy bank account class of the module common
 * package, which is not part of this repository. The scenario publishes the
 * helper next to the module for the duration of the run, so the provider
 * finds it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/fa_stubs.php';
require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/legacy_common_fixture.php';

use Ksfraser\FaBankImport\Models\FaBankAccountsProvider;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyCommonFixture;

$fixture = new LegacyCommonFixture(KSF_GAP_ROOT);
$fixture->publish([
    'class.fa_bank_accounts.php' => LegacyCommonFixture::faBankAccountsSource(),
]);

register_shutdown_function(static function () use ($fixture): void {
    $fixture->cleanup();
});

$provider = new FaBankAccountsProvider();

try {
    $details = $provider->getByNumber('000123');
    echo 'DETAILS: ' . json_encode($details) . "\n";
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "SCENARIO_OK\n";
