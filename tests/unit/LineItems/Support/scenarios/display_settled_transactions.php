<?php

declare(strict_types=1);

/**
 * Scenario: the settled transaction views of the module.
 *
 * The views live in src/Ksfraser/FaBankImport/Views/ and are autoloadable
 * now, so the scenario runs against the real classes. It executes every
 * partner type the view supports and builds the rows it assembles.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../view_stubs.php';

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
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A bank deposit.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 12;
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A manual settlement.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 0;
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A transaction of an unknown type.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = 99;
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// The rows of the settled views are built directly, because the view
// itself constructs some of them without the arguments they need.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\StatusSettled();
    new \Ksfraser\FaBankImport\Views\SettledManual();
    new \Ksfraser\FaBankImport\Views\SettledDefault($transaction);
    new \Ksfraser\FaBankImport\Views\UnsetTransaction($transaction);
    new \Ksfraser\FaBankImport\Views\OperationPayment();
    new \Ksfraser\FaBankImport\Views\FromBankAccount($transaction);
    new \Ksfraser\FaBankImport\Views\SettledSupplier($transaction);
    new \Ksfraser\FaBankImport\Views\OperationDeposit();
    new \Ksfraser\FaBankImport\Views\CustomerBranch(['debtor_no' => 1, 'branch_code' => 2]);
    new \Ksfraser\FaBankImport\Views\SettledPaymentTable($transaction);
    new \Ksfraser\FaBankImport\Views\SettledDepositTable($transaction);
});

echo "SCENARIO_OK\n";
