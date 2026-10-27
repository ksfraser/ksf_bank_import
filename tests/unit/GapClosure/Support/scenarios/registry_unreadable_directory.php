<?php

declare(strict_types=1);

/**
 * Scenario: the partner type scanner is denied the directory.
 *
 * The registry scans its own directory for partner types with glob(). The
 * scenario lifts the directory out of reach with open_basedir, so glob()
 * answers false and the scanner gives up. The restriction is one-way and
 * cannot be undone, so the scenario is run in a process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

use Ksfraser\PartnerTypes\PartnerTypeRegistry;

/*
 * Load everything the scenario needs while the file system is still open.
 */
class_exists(PartnerTypeRegistry::class);

/*
 * Restrict every open beyond the temporary directory. The class files
 * loaded above stay usable; loading anything new from the module now has
 * to fail, which is exactly the state that makes glob() answer false.
 */
ini_set('open_basedir', sys_get_temp_dir() . PATH_SEPARATOR . ini_get('open_basedir'));

PartnerTypeRegistry::reset();
$registry = PartnerTypeRegistry::getInstance();

echo 'SCAN_DENIED: ' . var_export($registry->getByCode('SP') === null, true) . "\n";
echo 'SCAN_STILL_DENIED: ' . var_export($registry->getByCode('XX') === null, true) . "\n";

echo "SCENARIO_OK\n";
