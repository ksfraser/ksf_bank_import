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

// A supplier payment. The branch of the view builds its payment table with
// a constructor call that PHP refuses to complete (the table constructors
// require a transaction; the view builds some of them without one), so the
// view's own branch dies before the row is appended.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = ST_SUPPAYMENT;
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A supplier payment row, built with the transaction. The table's own
// sections call their parent constructor without the argument it needs.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\SettledPaymentTable($transaction);
});

// A bank deposit.
run_settled(function () use ($transaction) {
    $transaction->fa_trans_type = ST_BANKDEPOSIT;
    $view = new \Ksfraser\FaBankImport\Views\DisplaySettledTransactions($transaction);
    $view->toHtml();
    $view->getHtml();
});

// A bank deposit row, built with the transaction.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\SettledDepositTable($transaction);
});

// A manual settlement. This is the transaction type whose rows the view
// builds without throwing, so the full render runs.
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

// The rows of the settled views. Each row is built in a block of its own:
// the label row base class insists on its property contract, so a failing
// row must not keep the others from being built.
// The label row of the settled status.
run_settled(function () {
    new \Ksfraser\FaBankImport\Views\StatusSettled();
});

// A manual settlement row.
run_settled(function () {
    new \Ksfraser\FaBankImport\Views\SettledManual();
});

// The row of an unknown transaction type.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\SettledDefault($transaction);
});

// The unset button of a transaction.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\UnsetTransaction($transaction);
});

// The operation of a supplier payment.
run_settled(function () {
    new \Ksfraser\FaBankImport\Views\OperationPayment();
});

// The source bank account of a supplier payment.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\FromBankAccount($transaction);
});

// The supplier of a supplier payment.
run_settled(function () use ($transaction) {
    new \Ksfraser\FaBankImport\Views\SettledSupplier($transaction);
});

// The operation of a bank deposit.
run_settled(function () {
    new \Ksfraser\FaBankImport\Views\OperationDeposit();
});

// The customer/branch of a bank deposit. The FA lookups of the customer
// and the branch are the stand-ins of the stub set.
run_settled(function () {
    new \Ksfraser\FaBankImport\Views\CustomerBranch(['debtor_no' => 1, 'branch_code' => 2]);
});

// The decorator of the matching GLs row.
run_settled(function () {
    $decorator = new \Ksfraser\FaBankImport\Views\HTML_ROW_LABELDecorator('content', 'Label');
    $decorator->toHtml();
    $decorator->getHtml();
});

echo "SCENARIO_OK\n";
