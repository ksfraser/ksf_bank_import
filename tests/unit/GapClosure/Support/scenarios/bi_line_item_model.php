<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Model {

    /*
     * The model catches "Exception" without a leading backslash, so the
     * catch clause refers to this class and not to the global one. The
     * module does not declare it, so the scenario declares it: that is the
     * only way the fallback of the model can run.
     */
    if (!class_exists(Exception::class, false)) {
        /**
         * The exception the model catches.
         */
        class Exception extends \Exception
        {
        }
    }
}

namespace {

    require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/legacy_model_stubs.php';

    if (!function_exists('shorten_bankAccount_Names')) {
        /**
         * Shorten long bank account descriptive names.
         *
         * @param string $name Raw account name.
         * @return string Shortened name.
         * @throws \Ksfraser\FaBankImport\Model\Exception Always, to provoke
         *         the fallback of the model.
         */
        function shorten_bankAccount_Names(string $name): string
        {
            throw new \Ksfraser\FaBankImport\Model\Exception(
                'the account name cannot be shortened'
            );
        }
    }

    require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/fa_stubs.php';
    require_once KSF_GAP_ROOT . '/tests/unit/LineItems/Support/legacy_workdir.php';

    use Ksfraser\FaBankImport\Model\BiLineItemModel;
    use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyWorkDir;

    $workDir = new LegacyWorkDir('ksf_gap_model_');
    $workDir->publishRunFile(
        'class.bi_partners_data.php',
        LegacyWorkDir::biPartnersDataSource()
    );

    register_shutdown_function(static function () use ($workDir): void {
        $workDir->cleanup();
    });

    chdir($workDir->getRunDir());

    require_once KSF_GAP_ROOT . '/src/Ksfraser/Model/BiLineItemModel.php';

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
        'transactionType' => 'C',
    ];

    $model = new BiLineItemModel($data);

    echo 'OTHER_ACCOUNT: ' . $model->get('otherBankAccount') . "\n";

    echo "SCENARIO_OK\n";
}
