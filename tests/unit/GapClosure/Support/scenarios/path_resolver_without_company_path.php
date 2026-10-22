<?php

declare(strict_types=1);

/**
 * Scenario: the path resolver without a FrontAccounting installation.
 *
 * The resolver asks the installation for the path of the company directory.
 * The PHPUnit process always has the stand-in for that helper loaded, so
 * the resolver never reports that the helper is missing. The scenario does
 * not declare the helper.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

require_once KSF_GAP_ROOT . '/src/Ksfraser/FaBankImport/Service/BankImportPathResolver.php';

try {
    \Ksfraser\FaBankImport\Service\BankImportPathResolver::forCurrentCompany();
    echo "NO_EXCEPTION\n";
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "SCENARIO_OK\n";
