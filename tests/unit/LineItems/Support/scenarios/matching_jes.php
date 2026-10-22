<?php

declare(strict_types=1);

/**
 * Scenario: the journal entry lookup in
 * src/Ksfraser/FaBankImport/models/MatchingJEs.php.
 *
 * The class loads the legacy fa_gl helper from a path that is relative to
 * the file, so the helper is published next to the repository for the
 * duration of the scenario.
 *
 * The scenario takes one argument:
 *  - "missing"    the helper is not published, so the include fails;
 *  - "throwing"   the helper throws while searching.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_common_fixture.php';

use Ksfraser\FaBankImport\models\MatchingJEs;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyCommonFixture;

$arguments = array_slice($argv, 3);
$mode = $arguments[0] ?? 'default';

$fixture = new LegacyCommonFixture(KSF_LINEITEMS_ROOT);

if ($mode !== 'missing') {
    $fixture->publish([
        'class.fa_gl.php' => LegacyCommonFixture::faGlSource(),
    ]);
}

register_shutdown_function(function () use ($fixture) {
    $fixture->cleanup();
});

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/models/MatchingJEs.php';

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

if ($mode === 'throwing') {
    // The published helper throws when it is asked for the matches.
    $GLOBALS['__fa_gl_throw'] = true;
}

$matcher = new MatchingJEs($lineItem);
$matcher->getMatchArr();

echo "SCENARIO_OK\n";
