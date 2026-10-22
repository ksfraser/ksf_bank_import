<?php

declare(strict_types=1);

/**
 * Scenario: the facade for the FrontAccounting UI functions without an
 * installation.
 *
 * The facade names the installation itself when FA_ROOT is not known yet.
 * The PHPUnit process always knows FA_ROOT, because the test bootstrap
 * names it, so that line is never taken there. The scenario loads the
 * facade before FA_ROOT is known.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

require_once KSF_GAP_ROOT . '/src/Ksfraser/HTML/FaUiFunctions.php';

echo 'FA_ROOT: ' . FA_ROOT . "\n";
echo 'UI_AVAILABLE: ' . var_export(KSF_FA_UI_AVAILABLE, true) . "\n";

\Ksfraser\HTML\FaUiFunctions::label_row('A label', 'A value');
\Ksfraser\HTML\FaUiFunctions::start_table();
\Ksfraser\HTML\FaUiFunctions::end_table();

echo "SCENARIO_OK\n";
