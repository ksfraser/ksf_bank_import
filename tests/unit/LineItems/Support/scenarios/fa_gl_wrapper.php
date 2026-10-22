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

echo "SCENARIO_OK\n";
