<?php

declare(strict_types=1);

/**
 * Scenario: the handler discovery finds no directory.
 *
 * The processor looks for the handlers directory with is_dir() and then
 * scans it with glob(). The scenario lifts the directory out of reach with
 * open_basedir:
 *
 *  - the first construction runs while PHP still remembers is_dir() true
 *    for the unresolved path, so glob() answers false and the scan gives
 *    up;
 *  - after the stat cache is cleared the directory lookup itself fails,
 *    so the constructor leaves before even trying to scan.
 *
 * The restriction is one-way and cannot be undone, so the scenario runs in
 * a process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

use Ksfraser\FaBankImport\Services\ReferenceNumberService;
use Ksfraser\FaBankImport\Services\TransactionProcessor;

/*
 * Load everything the scenario needs while the file system is still open,
 * and warm the stat cache for the very path the processor builds later.
 */
class_exists(ReferenceNumberService::class);
class_exists(TransactionProcessor::class);

$processorFile = (string) (new ReflectionClass(TransactionProcessor::class))->getFileName();
$handlersDir = dirname($processorFile) . '/../Handlers';

if (is_dir($handlersDir) !== true) {
    echo "PRECONDITION_FAILED: the handlers directory is not there\n";
    exit(1);
}

/*
 * Restrict every open beyond the temporary directory. The classes loaded
 * above stay usable; the handlers directory is now out of reach.
 */
ini_set('open_basedir', sys_get_temp_dir() . PATH_SEPARATOR . ini_get('open_basedir'));

$warmed = new TransactionProcessor();
echo 'SCAN_DENIED: ' . var_export($warmed->getRegisteredTypes() === [], true) . "\n";

clearstatcache();
$chilled = new TransactionProcessor();
echo 'BAIL_OUT: ' . var_export($chilled->getRegisteredTypes() === [], true) . "\n";

echo "SCENARIO_OK\n";
