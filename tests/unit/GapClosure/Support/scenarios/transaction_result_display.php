<?php

declare(strict_types=1);

/**
 * Scenario: the display of a warning result.
 *
 * The mock package (loaded by the composer autoloader before the scenario)
 * declares FA's display_warning() helper, so the result routes the warning
 * to it; famock's helper captures into $GLOBALS['test_warnings']. The
 * scenario reports that capture so the routing is observable in the child's
 * output. (The plain-notification fallback of TransactionResult::display()
 * is unreachable in any process that autoloads famock.)
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

if (!function_exists('display_notification')) {
    /**
     * @param string $message The message to display.
     * @return void
     */
    function display_notification($message)
    {
        echo 'NOTIFICATION: ' . $message . "\n";
    }
}

require_once KSF_GAP_ROOT . '/src/Ksfraser/FaBankImport/Results/TransactionResult.php';

\Ksfraser\FaBankImport\Results\TransactionResult::warning('Careful')->display();

$warnings = isset($GLOBALS['test_warnings']) ? (array) $GLOBALS['test_warnings'] : [];
foreach ($warnings as $warning) {
    echo 'WARNING: ' . $warning . "\n";
}

echo "SCENARIO_OK\n";
