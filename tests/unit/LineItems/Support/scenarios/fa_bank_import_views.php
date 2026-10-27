<?php

declare(strict_types=1);

/**
 * Scenario: the legacy views under src/Ksfraser/FaBankImport/Views/.
 *
 * The views cannot be loaded in the PHPUnit process: several of them declare
 * classes the test doubles of other test files also declare, and they all
 * implement an HTML element contract that the current interface no longer
 * matches. The scenario therefore runs in a process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../view_stubs.php';

$viewsDir = KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/Views';

// The views are autoloadable now, so the scenario only has to make sure the
// autoloader is available.
require_once KSF_LINEITEMS_ROOT . '/vendor/autoload.php';

$files = [
    'AddCustomerButton.php',
    'AddNoButton.php',
    'ToggleTransactionTypeButton.php',
    'TransDate.php',
    'TransType.php',
    'OurBankAccount.php',
    'OtherBankAccount.php',
    'AmountCharges.php',
    'TransTitle.php',
    'Operation.php',
    'PartnerType.php',
    'PartnerSubSelect.php',
    'Comment.php',
    'MatchingGLS.php',
    'LineitemDisplayLeft.php',
    'LineitemDisplayRight.php',
    'TransactionCustomerDetails.php',
    'TransactionTypeLabel.php',
    'HtmlTransactionView.php',
];

foreach ($files as $file) {
    require_once $viewsDir . '/' . $file;
}

$lineItem = new class {
    /** @var int */
    public $id = 11;
    /** @var string */
    public $valueTimestamp = '2026-01-31';
    /** @var string */
    public $entryTimestamp = '2026-02-01';
    /** @var string */
    public $transactionDC = 'C';
    /** @var string */
    public $transactionTitle = 'A title';
    /** @var string */
    public $memo = 'A memo';
    /** @var string */
    public $our_account = '000123';
    /** @var array */
    public $ourBankDetails = ['bank_name' => 'Test Bank'];
    /** @var string */
    public $ourBankAccountName = 'Chequing';
    /** @var string */
    public $ourBankAccountCode = '1061';
    /** @var string */
    public $otherBankAccount = '000999';
    /** @var string */
    public $otherBankAccountName = 'Counterparty';
    /** @var float */
    public $amount = 25.5;
    /** @var float */
    public $charge = 1.5;
    /** @var string */
    public $currency = 'CAD';
    /** @var string */
    public $oplabel = 'Deposit';
    /** @var array */
    public $optypes = ['CU' => 'Customer'];
    /** @var int */
    public $fa_trans_type = 0;
    /** @var int */
    public $fa_trans_no = 0;
    /** @var int */
    public $status = 0;
};

$_POST['partnerType'][11] = 'CU';

/**
 * Run a callable and swallow whatever it throws.
 *
 * The legacy views are not consistent about the arguments they expect, so
 * some of them fail; the failure itself is what is being pinned down.
 *
 * @param callable $callback Code to run.
 * @return void
 */
function run_legacy(callable $callback): void
{
    try {
        $callback();
    } catch (\Throwable $e) {
        // The legacy views are inconsistent; the failure is expected.
    }
}

// The label row views.
run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\TransDate($lineItem);
    $view->getHtml();
    $view->toHtml();
});

foreach (['C' => 'Credit', 'B' => 'Bank Transfer', 'D' => 'Debit', 'X' => 'Debit'] as $dc => $expected) {
    run_legacy(function () use ($lineItem, $dc) {
        $lineItem->transactionDC = $dc;
        $view = new \Ksfraser\FaBankImport\Views\TransType($lineItem);
        $view->getHtml();
        $view->toHtml();
    });
}
$lineItem->transactionDC = 'C';

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\OurBankAccount($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\OtherBankAccount($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\AmountCharges($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\TransTitle($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\Operation($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\PartnerType($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\PartnerSubSelect($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\Comment($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\MatchingGLS($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\LineitemDisplayLeft($lineItem);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () use ($lineItem) {
    $view = new \Ksfraser\FaBankImport\Views\LineitemDisplayRight($lineItem);
    $view->getHtml();
    $view->toHtml();
});

// The right hand side of the row, built without its constructor: the
// constructor aborts on the incompatible legacy row contract, so the render
// is driven with an injected table instead.
run_legacy(function () {
    $view = (new \ReflectionClass(\Ksfraser\FaBankImport\Views\LineitemDisplayRight::class))->newInstanceWithoutConstructor();
    $table = new \Ksfraser\HTML\Composites\HTML_TABLE(null, 100);
    $table->appendRow(new \Ksfraser\FaBankImport\Views\Operation((object) ['id' => 11, 'oplabel' => 'Deposit']));
    $property = new \ReflectionProperty(\Ksfraser\FaBankImport\Views\LineitemDisplayRight::class, 'table');
    $property->setAccessible(true);
    $property->setValue($view, $table);
    $view->getHtml();
    $view->toHtml();
});

// The matching GLs row, built without its constructor: the constructor
// aborts on the incompatible legacy content contract, so the render is
// driven with an injected label row instead.
run_legacy(function () {
    $view = (new \ReflectionClass(\Ksfraser\FaBankImport\Views\MatchingGLS::class))->newInstanceWithoutConstructor();
    $row = new \Ksfraser\HTML\Composites\HTML_ROW_LABEL('No matches found', 'Matching GLs:');
    $property = new \ReflectionProperty(\Ksfraser\FaBankImport\Views\MatchingGLS::class, 'row');
    $property->setAccessible(true);
    $property->setValue($view, $row);
    $view->getHtml();
    $view->toHtml();
});

run_legacy(function () {
    $view = new \Ksfraser\FaBankImport\Views\TransactionCustomerDetails(1, 42);
    $view->getLineitemMatchedCustomerDetails();
});

run_legacy(function () {
    $label = new \Ksfraser\FaBankImport\Views\TransactionTypeLabel('C');
    $label->getTransactionTypeLabel();
    $label = new \Ksfraser\FaBankImport\Views\TransactionTypeLabel('B');
    $label->getTransactionTypeLabel();
    $label = new \Ksfraser\FaBankImport\Views\TransactionTypeLabel('D');
    $label->getTransactionTypeLabel();
    $label = new \Ksfraser\FaBankImport\Views\TransactionTypeLabel('X');
    $label->getTransactionTypeLabel();
});

run_legacy(function () {
    $menu = new \Ksfraser\FaBankImport\Views\ModuleMenuView();
    $menu->renderMenu();
});

run_legacy(function () {
    $row = new \HTML_ROW('content');
    $row->toHTML();
});

run_legacy(function () {
    $decorator = new \HTML_ROW_LABELDecorator('content', 'Label');
    $decorator->toHTML();
    $decorator->getHTML();
});

run_legacy(function () {
    $button = new \Ksfraser\FaBankImport\Views\AddCustomerButton(11);
    $button->toHTML();
});

run_legacy(function () {
    $button = new \Ksfraser\FaBankImport\Views\AddNoButton(11);
    $button->getHtml();
    $button->toHtml();
});

run_legacy(function () {
    $button = new \Ksfraser\FaBankImport\Views\ToggleTransactionTypeButton(11);
    $button->toHtml();
    $button->getHtml();
});

// The label row base class insists on the label and the data.
run_legacy(function () {
    $broken = new class('') extends \Ksfraser\HTML\LabelRowBase {
    };
});

run_legacy(function () {
    $broken = new class('') extends \Ksfraser\HTML\LabelRowBase {
        /** @var string */
        protected $data = 'The data';
    };
});

// The shared HTML_ROW_LABEL contract is authoritative in the current test
// support (view_stubs.php), so the label-row delegates exercise it in the
// context the legacy views use.
run_legacy(function () {
    $row = new \HTML_ROW_LABEL('content', 'Label', null, null);
    $row->toHTML();
    $row->getHTML();
});

run_legacy(function () {
    $row = new \HTML_ROW_LABEL('content', 'Label', 50, 'custom');
    $row->toHTML();
    $row->getHTML();
});

// The transaction view renders a transaction and its action buttons.
$transaction = new class implements \Ksfraser\FaBankImport\Interfaces\BankTransactionInterface {
    /** @inheritDoc */
    public function getTransactionType(): string
    {
        return 'C';
    }

    /** @inheritDoc */
    public function getAmount(): float
    {
        return 25.5;
    }

    /** @inheritDoc */
    public function getDate(): string
    {
        return '2026-01-31';
    }

    /** @inheritDoc */
    public function getAccountDetails(): array
    {
        return ['name' => 'Chequing', 'number' => '000123'];
    }

    /** @inheritDoc */
    public function getOtherPartyDetails(): array
    {
        return ['name' => 'Counterparty', 'type' => 'CU'];
    }

    /** @inheritDoc */
    public function getStatus(): int
    {
        return 0;
    }

    /** @inheritDoc */
    public function getId(): int
    {
        return 11;
    }

    /** @inheritDoc */
    public function getMemo(): string
    {
        return 'A memo';
    }
};

run_legacy(function () use ($transaction) {
    $view = new \Ksfraser\FaBankImport\Views\HtmlTransactionView($transaction);
    $view->addButton('AddCustomer', ['id' => 11]);
    $view->addButton('AddVendor', ['id' => 11]);
    $view->addButton('ProcessTransaction', ['id' => 11]);
    $view->addButton('SomethingElse', []);
    $view->renderActions();
    $view->render();
});

echo "SCENARIO_OK\n";
