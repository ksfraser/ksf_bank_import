<?php

declare(strict_types=1);

/**
 * Scenario: the short line item view in
 * src/Ksfraser/FaBankImport/views/BiLineItemView.php.
 *
 * The class declares Ksfraser\FaBankImport\View\BiLineItemView, the same
 * name the view in src/Ksfraser/View/ uses, so it can only be loaded in a
 * process of its own.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_model_stubs.php';

require_once KSF_LINEITEMS_ROOT . '/src/Ksfraser/Model/BiLineItemModel.php';
// The scenario takes one argument: "singular" loads the copy in the
// singular view directory, the default loads the one in the plural one.
$arguments = array_slice($argv, 3);

$target = in_array('singular', $arguments, true)
    ? '/src/Ksfraser/FaBankImport/view/BiLineItemView.php'
    : '/src/Ksfraser/FaBankImport/views/BiLineItemView.php';

require_once KSF_LINEITEMS_ROOT . $target;
require_once __DIR__ . '/../PublicLineItemModel.php';

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

$model = new \Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\PublicLineItemModel($trz);

$view = new \Ksfraser\FaBankImport\View\BiLineItemView($model);

$rendered = $view->render();

echo 'RENDERED: ' . json_encode($rendered) . "\n";
echo "SCENARIO_OK\n";
