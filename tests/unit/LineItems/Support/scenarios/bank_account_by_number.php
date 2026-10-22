<?php

declare(strict_types=1);

/**
 * Scenario: the bank account lookup in
 * src/Ksfraser/FaBankImport/Models/BankAccountByNumber.php.
 *
 * The class loads the legacy fa_bank_accounts helper from a path that is
 * relative to the file, so the helper is published next to the repository
 * for the duration of the scenario.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_common_fixture.php';

use Ksfraser\FaBankImport\models\BankAccountByNumber;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyCommonFixture;

$fixture = new LegacyCommonFixture(KSF_LINEITEMS_ROOT);
$fixture->publish([
    'class.fa_bank_accounts.php' => LegacyCommonFixture::faBankAccountsSource(),
]);

register_shutdown_function(function () use ($fixture) {
    $fixture->cleanup();
});

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/Models/BankAccountByNumber.php';

$account = new BankAccountByNumber('000123');

$account->getBankDetails();
$account->getBankAccountName();
$account->getBankAccountCode();
$account->our_account;
$account->somethingElse;

echo "SCENARIO_OK\n";
