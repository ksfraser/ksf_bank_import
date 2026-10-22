<?php

declare(strict_types=1);

/**
 * Scenario: the display of a warning result.
 *
 * The result falls back to the plain notification when the installation has
 * no dedicated warning helper. The PHPUnit process always has the mock
 * package loaded, which declares that helper, so the fallback is never
 * taken there. The scenario declares the notification only.
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

echo "SCENARIO_OK\n";
