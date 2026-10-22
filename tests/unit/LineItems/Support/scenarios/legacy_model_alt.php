<?php

declare(strict_types=1);

/**
 * Scenario: the legacy line item model in
 * src/Ksfraser/FaBankImport/model/BiLineItemModel.php.
 *
 * The class declares Ksfraser\FaBankImport\Model\BiLineItemModel, which the
 * model in src/Ksfraser/Model/ declares as well. Only one of the two can be
 * loaded in a process, so each gets its own scenario.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../legacy_model_stubs.php';
require_once __DIR__ . '/../fa_stubs.php';

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/FaBankImport/model/BiLineItemModel.php';

use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\frontaccounting\FaGl;

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

// The bank account details are loaded through the FA wrapper.
$model = new BiLineItemModel($data);
$model->getBankAccountDetails();

// The partner type depends on the debit/credit flag.
foreach (['C', 'D', 'B', 'X'] as $dc) {
    $model = new BiLineItemModel(array_merge($data, ['transactionDC' => $dc]));
    $model->setPartnerType();
}

// Matching transactions come back as a plain array ...
FaGl::$matches = [['type' => 1, 'type_no' => 2, 'score' => 90]];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();

// ... and as an empty array when nothing matches.
FaGl::$matches = [];
$model = new BiLineItemModel($data);
$model->findMatchingExistingJE();

echo "SCENARIO_OK\n";
