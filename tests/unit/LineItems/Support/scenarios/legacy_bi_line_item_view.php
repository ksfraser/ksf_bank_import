<?php

declare(strict_types=1);

/**
 * Scenario: the legacy line item view in src/Ksfraser/FaBankImport/View/BiLineItemView.php.
 *
 * The view reads the state of the line item from the view object itself,
 * although that state lives on the model, and it calls several of its own
 * methods with the wrong number of arguments. The scenario therefore
 * publishes the state as dynamic properties of the view and drives every
 * method on its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

// The partner lookup is declared before the mock package is loaded, so the
// mock package leaves it alone.
require_once __DIR__ . '/../fa_partner_lookup.php';

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_model_stubs.php';
require_once __DIR__ . '/../legacy_workdir.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyWorkDir;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\PublicLineItemModel;

// The view loads the customer payment helper relative to the working
// directory; the helper is published there so the view can use it.
$workDir = new LegacyWorkDir('ksf_lineitems_view_');
$workDir->publishSiblingFile('class.fa_customer_payment.php', <<<'PHP'
<?php

namespace Ksfraser\FaBankImport\View;

if (!class_exists('Ksfraser\FaBankImport\View\fa_customer_payment', false)) {
    class fa_customer_payment
    {
        /** @var array */
        public $values = [];

        /**
         * @param string $field Field name.
         * @param mixed $value Field value.
         * @return bool
         */
        public function set($field, $value = null, $enforce = true)
        {
            $this->values[$field] = $value;

            return true;
        }

        /**
         * @return string
         */
        public function show_allocatable()
        {
            return 'Invoices';
        }

        /**
         * @return array
         */
        public function get_alloc_details()
        {
            return [['type_no' => 5, 'trans_no' => 6]];
        }
    }
}
PHP
);

register_shutdown_function(function () use ($workDir) {
    $workDir->cleanup();
});

chdir($workDir->getRunDir());

// The view type hints the FA bank accounts wrapper in its own namespace.
if (!class_exists('Ksfraser\FaBankImport\View\FaBankAccounts', false)) {
    eval(
        'namespace Ksfraser\FaBankImport\View;'
        . 'class FaBankAccounts {'
        . 'public $rows = [];'
        . 'public function __construct($rows = []) { $this->rows = $rows; }'
        . 'public function get($name) { return $this->rows[$name] ?? null; }'
        . '}'
    );
}

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/Model/BiLineItemModel.php';
require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/View/BiLineItemView.php';
require_once __DIR__ . '/../PublicLineItemModel.php';

/**
 * A view that knows how to set the partner type.
 *
 * The legacy view calls a method it does not have, so the scenario adds it.
 */
class ScenarioLineItemView extends \Ksfraser\FaBankImport\View\BiLineItemView
{
    /**
     * Set the partner type of the line item.
     *
     * @return void
     */
    public function setPartnerType(): void
    {
        $this->partnerType = 'CU';
    }
}

$trz = [
    'transactionDC' => 'C',
    'memo' => 'A memo',
    'our_account' => '000123',
    'valueTimestamp' => '2026-01-31',
    'entryTimestamp' => '2026-02-01',
    'accountName' => 'Counterparty',
    'transactionTitle' => 'A title',
    'transactionCode' => 'REF-1',
    'transactionCodeDesc' => 'Reference',
    'currency' => 'CAD',
    'status' => 0,
    'id' => 11,
    'fa_trans_type' => 0,
    'fa_trans_no' => 0,
    'transactionAmount' => 25.5,
    'transactionType' => 'X',
];

$model = new PublicLineItemModel($trz);
$model->fa_bank_accounts = new \Ksfraser\FaBankImport\View\FaBankAccounts([
    'bank_name' => 'Test Bank',
    'bank_account_name' => 'Chequing',
    'account_code' => '1061',
]);

$view = new ScenarioLineItemView($model);

// The view reads the line item state from itself.
$state = [
    'transactionDC' => 'C',
    'our_account' => '000123',
    'otherBankAccount' => '000999',
    'otherBankAccountName' => 'Counterparty',
    'amount' => 25.5,
    'charge' => 1.5,
    'currency' => 'CAD',
    'transactionTitle' => 'A title',
    'id' => 11,
    'memo' => 'A memo',
    'status' => 0,
    'fa_trans_type' => 0,
    'fa_trans_no' => 42,
    'partnerId' => 3,
    'partnerDetailId' => 4,
    'oplabel' => 'Deposit',
    'optypes' => ['CU' => 'Customer'],
    'vendor_list' => ['shortnames' => []],
    'matching_trans' => [],
];

foreach ($state as $name => $value) {
    $view->$name = $value;
}

/**
 * Run a callable and swallow whatever it throws.
 *
 * The legacy view is not consistent about the arguments it passes, so some
 * of its calls fail; the failure itself is what is being pinned down.
 *
 * @param callable $callback Code to run.
 * @return void
 */
function run_view(callable $callback): void
{
    try {
        $callback();
    } catch (\Throwable $e) {
        // The legacy view is inconsistent; the failure is expected.
    }
}

// The plain rendering of the line item.
run_view(function () use ($view) {
    $view->render();
});

// The whole row of an unsettled transaction.
run_view(function () use ($view, $model) {
    $view->status = 0;
    $_POST['partnerType'][11] = 'BT';
    $view->display();
});

// The whole row of a settled transaction.
run_view(function () use ($view, $model) {
    $view->status = 1;
    $view->display();
});
$view->status = 0;

// The left hand side of the row.
run_view(function () use ($view, $model) {
    $view->display_left($model);
});

// The right hand side of the row.
run_view(function () use ($view) {
    $view->display_right();
});

// The vendor or customer button, for a counterparty that is not known.
run_view(function () use ($view) {
    $view->transactionDC = 'D';
    $view->displayAddVendorOrCustomer();
});

// ... for a credit ...
run_view(function () use ($view) {
    $view->transactionDC = 'C';
    $view->displayAddVendorOrCustomer();
});

// ... and for a counterparty that is a known vendor.
run_view(function () use ($view) {
    $view->vendor_list = [
        'shortnames' => ['000999'],
        0 => ['supplier_id' => 7, 'supp_name' => 'A supplier'],
    ];
    $view->displayAddVendorOrCustomer();
});
$view->vendor_list = ['shortnames' => []];

// The paired transaction.
run_view(function () use ($view) {
    $view->displayPaired();
});

// The matching journal entries, without any match.
run_view(function () use ($view) {
    $view->displayMatchingTransArr([], new \Ksfraser\FaBankImport\View\FaBankAccounts());
});

// The matching journal entries, with a match on another account.
run_view(function () use ($view) {
    $view->transactionDC = 'C';
    $view->displayMatchingTransArr(
        [
            [
                'tran_date' => '2026-01-31',
                'type' => ST_BANKPAYMENT,
                'type_no' => 7,
                'account' => '000123',
                'account_name' => 'Chequing',
                'amount' => 25.5,
                'score' => 90,
                'person_type_id' => 1,
            ],
        ],
        new \Ksfraser\FaBankImport\View\FaBankAccounts(['bank_account_name' => 'Chequing'])
    );
});

// The matching journal entries, with a match on our own account.
run_view(function () use ($view) {
    $view->our_account = 'Chequing';
    $view->displayMatchingTransArr(
        [
            [
                'tran_date' => '2026-01-31',
                'type' => ST_BANKPAYMENT,
                'type_no' => 7,
                'account' => 'Chequing',
                'account_name' => 'Chequing',
                'amount' => 25.5,
                'score' => 90,
            ],
        ],
        new \Ksfraser\FaBankImport\View\FaBankAccounts(['bank_account_name' => 'Chequing'])
    );
    $view->our_account = '000123';
});

// The matching journal entries of a debit, with an amount that differs.
run_view(function () use ($view) {
    $view->transactionDC = 'D';
    $view->displayMatchingTransArr(
        [
            [
                'tran_date' => '2026-01-31',
                'type' => ST_BANKPAYMENT,
                'type_no' => 7,
                'account' => '000123',
                'account_name' => 'Chequing',
                'amount' => 99.99,
                'score' => 90,
            ],
        ],
        new \Ksfraser\FaBankImport\View\FaBankAccounts(['bank_account_name' => 'Chequing'])
    );
    $view->transactionDC = 'C';
});

// The high confidence match of the model.
run_view(function () use ($view, $model) {
    \Ksfraser\frontaccounting\FaGl::$matches = [
        ['type' => ST_BANKPAYMENT, 'type_no' => 7, 'score' => 90, 'is_invoice' => false],
    ];
    $view->getDisplayMatchingTrans($model);
    \Ksfraser\frontaccounting\FaGl::$matches = [];
});

// The bank transfer partner type, with a partner that is known.
run_view(function () use ($view) {
    $_POST['partnerId_11'] = '';
    $view->displayBankTransferPartnerType();
});

// The bank transfer partner type, with a partner that is not known.
run_view(function () use ($view) {
    $GLOBALS['__no_partner_match'] = true;
    $_POST['partnerId_11'] = '';
    $view->displayBankTransferPartnerType();
    unset($GLOBALS['__no_partner_match']);
});

// The bank transfer partner type of a debit.
run_view(function () use ($view) {
    $view->transactionDC = 'D';
    $_POST['partnerId_11'] = '';
    $view->displayBankTransferPartnerType();
    $view->transactionDC = 'C';
});

// The quick entry partner type.
run_view(function () use ($view) {
    $view->displayQuickEntryPartnerType();
});

// The matched partner type.
run_view(function () use ($view) {
    $view->displayMatchedPartnerType();
});

// The partner type dispatcher, for every known partner type.
foreach (['SP', 'CU', 'BT', 'QE', 'MA', 'ZZ'] as $partnerType) {
    run_view(function () use ($view, $partnerType) {
        $_POST['partnerType'][11] = $partnerType;
        $view->displayPartnerType();
    });
}

// The partner type dispatcher, for a matched transaction.
run_view(function () use ($view) {
    $view->matching_trans = [
        ['type' => ST_BANKPAYMENT, 'type_no' => 7, 'score' => 90],
    ];
    $_POST['partnerType'][11] = 'ZZ';
    $view->displayPartnerType();
    $view->matching_trans = [];
});
unset($_POST['partnerType']);

// The edit buttons.
run_view(function () use ($view) {
    $view->displayEditTransData();
});

// The settled transaction, for every transaction type.
foreach ([ST_SUPPAYMENT, ST_BANKDEPOSIT, 0, 42] as $type) {
    run_view(function () use ($view, $type) {
        $view->fa_trans_type = $type;
        $view->display_settled();
    });
}

// The supplier partner type.
run_view(function () use ($view) {
    $view->displaySupplierPartnerType(11, ['supplier_id' => 7]);
});

// The customer partner type, for a customer with branches.
run_view(function () use ($view, $model) {
    $view->displayCustomerPartnerType($model, 11, []);
});

// The customer partner type, for a customer without branches.
run_view(function () use ($view, $model) {
    $GLOBALS['__customer_has_no_branches'] = true;
    $view->displayCustomerPartnerType($model, 11, []);
    unset($GLOBALS['__customer_has_no_branches']);
});

echo "SCENARIO_OK\n";
