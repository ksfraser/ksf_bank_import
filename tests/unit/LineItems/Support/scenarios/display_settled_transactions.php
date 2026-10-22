<?php

declare(strict_types=1);

/**
 * Scenario: the settled transaction view in
 * src/Ksfraser/FaBankImport/views/DisplaySettledTransactions.php.
 *
 * The view cannot be loaded from the repository: it implements an HTML
 * element contract without the return types the current interface asks for
 * and it loads the interface itself, and it loads a label row base class
 * that does not exist. The scenario therefore runs against a patched copy
 * of the tree, which the coverage is mapped back onto.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../view_stubs.php';
require_once __DIR__ . '/../FixtureTree.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;

$tree = new FixtureTree(KSF_LINEITEMS_ROOT);
$srcDir = $tree->create();

register_shutdown_function(function () use ($tree) {
    $tree->cleanup();
});

echo 'FIXTURE_SRC: ' . $srcDir . "\n";

// The label row base class of the views declares Ksfraser\HTML\LabelRowBase.
require_once $srcDir . '/Ksfraser/HTML/LabelRowBase.php';

class_alias(\Ksfraser\HTML\LabelRowBase::class, 'Ksfraser\FaBankImport\LabelRowBase');
class_alias(\Ksfraser\HTML\Composites\HTML_TABLE::class, 'Ksfraser\FaBankImport\HTML_TABLE');

require_once $srcDir . '/Ksfraser/FaBankImport/views/DisplaySettledTransactions.php';

/**
 * Run a callable and swallow whatever it throws.
 *
 * @param callable $callback Code to run.
 * @return void
 */
function run_settled(callable $callback): void
{
    try {
        $callback();
    } catch (\Throwable $e) {
        echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    }
}

$transaction = new class {
    /** @var int */
    public $id = 11;
    /** @var int */
    public $fa_trans_type = 22;
    /** @var int */
    public $fa_trans_no = 42;
    /** @var string */
    public $OurBankAccount = 'Chequing';
    /** @var string */
    public $SupplierName = 'A supplier';
};

// A supplier payment.
run_settled(function () use ($transaction) {
    $view = new \Ksfraser\FaBankImport\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A bank deposit.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 12;
    $view = new \Ksfraser\FaBankImport\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A manual settlement.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 0;
    $view = new \Ksfraser\FaBankImport\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A transaction of an unknown type.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 99;
    $view = new \Ksfraser\FaBankImport\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// The rows of the settled views are built directly, because the view
// itself constructs some of them without the arguments they need.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\StatusSettled();
    new \Ksfraser\FaBankImport\SettledManual();
    new \Ksfraser\FaBankImport\SettledDefault($transaction);
    new \Ksfraser\FaBankImport\UnsetTransaction($transaction);
    new \Ksfraser\FaBankImport\OperationPayment();
    new \Ksfraser\FaBankImport\FromBankAccount($transaction);
    new \Ksfraser\FaBankImport\SettledSupplier($transaction);
    new \Ksfraser\FaBankImport\OperationDeposit();
    new \Ksfraser\FaBankImport\CustomerBranch(['debtor_no' => 1, 'branch_code' => 2]);
    new \Ksfraser\FaBankImport\SettledPaymentTable($transaction);
    new \Ksfraser\FaBankImport\SettledDepositTable($transaction);
});

echo "SCENARIO_OK\n";
