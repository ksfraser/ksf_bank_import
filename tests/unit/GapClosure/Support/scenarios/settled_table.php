<?php

declare(strict_types=1);

/**
 * Scenario: the settled transaction tables.
 *
 * The view in src/Ksfraser/FaBankImport/Views/DisplaySettledTransactions.php
 * cannot be loaded from the repository: it implements an HTML element
 * contract without the return types the current interface asks for and it
 * loads that interface itself, and it loads a label row base class that does
 * not exist. The scenario therefore runs against a patched copy of the tree,
 * whose coverage is mapped back onto the repository.
 *
 * The scenario also builds a table of its own, because the two tables of the
 * view build their rows without the arguments they need.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/fa_stubs.php';
require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/view_stubs.php';
require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/FixtureTree.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;

$tree = new FixtureTree(KSF_GAP_ROOT);
$srcDir = $tree->create();

register_shutdown_function(static function () use ($tree): void {
    $tree->cleanup();
});

echo 'FIXTURE_SRC: ' . $srcDir . "\n";

require_once $srcDir . '/Ksfraser/HTML/Composites/LabelRowBase.php';

class_alias(\Ksfraser\HTML\Composites\LabelRowBase::class, 'Ksfraser\FaBankImport\Views\LabelRowBase');
class_alias(\Ksfraser\HTML\Composites\HTML_TABLE::class, 'Ksfraser\FaBankImport\HTML_TABLE');

require_once $srcDir . '/Ksfraser/FaBankImport/Views/DisplaySettledTransactions.php';

/**
 * A settled table that hands the transaction to its base class.
 */
class ProbeSettledTable extends \Ksfraser\FaBankImport\Views\SettledTable
{
    /**
     * @param mixed $transaction The transaction of the table.
     */
    public function __construct($transaction)
    {
        parent::__construct($transaction);
    }
}

$table = new ProbeSettledTable(null);
$table->toHtml();
$table->getHtml();

// The deposit table builds its rows without the arguments they need, so
// the table itself is built here.
try {
    new \Ksfraser\FaBankImport\Views\SettledDepositTable(null);
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "SCENARIO_OK\n";
