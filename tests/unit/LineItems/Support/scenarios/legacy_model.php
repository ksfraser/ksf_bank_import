<?php

declare(strict_types=1);

/**
 * Scenario: the legacy line item model in src/Ksfraser/FaBankImport/Model/BiLineItemModel.php.
 *
 * The class declares Ksfraser\FaBankImport\Model\BiLineItemModel, the same
 * name the model in src/Ksfraser/FaBankImport/model/ uses, so it can only be
 * loaded in a process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../legacy_model_stubs.php';
require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_workdir.php';

use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\frontaccounting\FaGl;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyWorkDir;

// The legacy code loads its partner data helper relative to the working
// directory, so the helper is published in a temporary working directory.
$workDir = new LegacyWorkDir('ksf_lineitems_model_');
$workDir->publishRunFile(
    'class.bi_partners_data.php',
    LegacyWorkDir::biPartnersDataSource()
);

register_shutdown_function(function () use ($workDir) {
    $workDir->cleanup();
});

chdir($workDir->getRunDir());

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/Model/BiLineItemModel.php';

$data = [
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
];

$model = new BiLineItemModel($data);

// The transaction type label is derived from the debit/credit flag.
foreach (['C' => 'Credit', 'D' => 'Debit', 'B' => 'Bank Transfer'] as $dc => $expected) {
    $model = new BiLineItemModel(array_merge($data, ['transactionDC' => $dc]));
    $model->determineTransactionTypeLabel();
}

// A short title is completed with the memo.
$model = new BiLineItemModel(array_merge($data, ['transactionTitle' => 'AB']));

// A posted partner id is picked up.
$_POST['partnerId_11'] = 5;
$model = new BiLineItemModel($data);
unset($_POST['partnerId_11']);

// The bank account details are loaded through the FA wrapper.
$model = new BiLineItemModel($data);
$model->getBankAccountDetails();

// The partner type depends on the debit/credit flag.
foreach (['C', 'D', 'B', 'X'] as $dc) {
    $model = new BiLineItemModel(array_merge($data, ['transactionDC' => $dc]));
    $model->setPartnerType();
}

// A posted partner type is left alone.
$_POST['partnerType'][11] = 'MA';
$model = new BiLineItemModel($data);
$model->setPartnerType();
unset($_POST['partnerType']);

// The setter is delegated to the base class.
$model->set('someField', 'someValue');

// Converting a transaction array copies its members.
$model->trz2obj((object) ['copiedField' => 'copiedValue']);

// No matches at all.
FaGl::$matches = [];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();

// Three or more matches are left alone.
FaGl::$matches = [
    ['score' => 90, 'is_invoice' => true, 'type' => 1, 'type_no' => 1],
    ['score' => 90, 'is_invoice' => true, 'type' => 1, 'type_no' => 2],
    ['score' => 90, 'is_invoice' => true, 'type' => 1, 'type_no' => 3],
];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();

// A low score is not a match.
FaGl::$matches = [['score' => 10, 'is_invoice' => true, 'type' => 1, 'type_no' => 1]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();

// An invoice match is a supplier payment.
FaGl::$matches = [['score' => 90, 'is_invoice' => true, 'type' => 1, 'type_no' => 1]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();
$_POST['partnerType'] = [];

// A bank payment match is a quick entry.
FaGl::$matches = [['score' => 90, 'is_invoice' => false, 'type' => ST_BANKPAYMENT, 'type_no' => 1]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();
$_POST['partnerType'] = [];

// A bank deposit match is a quick entry as well.
FaGl::$matches = [['score' => 90, 'is_invoice' => false, 'type' => ST_BANKDEPOSIT, 'type_no' => 1]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();
$_POST['partnerType'] = [];

// Anything else is a matched transaction.
FaGl::$matches = [['score' => 90, 'is_invoice' => false, 'type' => 42, 'type_no' => 1]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();
$_POST['partnerType'] = [];

// The finder may hand back an iterator instead of an array.
FaGl::$matches = new ArrayIterator([['score' => 90, 'is_invoice' => true, 'type' => 1, 'type_no' => 1]]);
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();
$_POST['partnerType'] = [];

// Pairing is not implemented yet.
$model->isPaired();
$model->findPaired();

// The partner lookup loads the legacy partner data class.
$model->seekPartnerByBankAccount(1);

echo "SCENARIO_OK\n";
