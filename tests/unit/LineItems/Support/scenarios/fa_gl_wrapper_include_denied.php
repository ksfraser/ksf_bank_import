<?php

declare(strict_types=1);

/**
 * Scenario: the wrapper is denied the legacy helper file.
 *
 * The constructor of FaGLWrapper throws when include_once answers false.
 * The scenario lifts the whole check out of reach with open_basedir, so
 * the file is there but is no longer readable, and the construction fails
 * with the expected exception.
 *
 * The restriction is one-way and cannot be undone, so the scenario runs in
 * a process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

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

class_exists(\Ksfraser\FaBankImport\FaGLWrapper::class);

ini_set('open_basedir', sys_get_temp_dir() . PATH_SEPARATOR . ini_get('open_basedir'));

try {
    new \Ksfraser\FaBankImport\FaGLWrapper($transaction);
    echo "NO_EXCEPTION\n";
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "SCENARIO_OK\n";
