<?php

declare(strict_types=1);

/**
 * Tests for the bank transfer transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\BankTransferHandlerWithStubbedHelper;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\KsfModulesCommonStub;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\BankTransferPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/BankTransferHandlerWithStubbedHelper.php';
require_once __DIR__ . '/../Support/KsfModulesCommonStub.php';

/**
 * BankTransferTransactionHandler coverage.
 */
final class BankTransferTransactionHandlerTest extends ProcessingTestCase
{
    /** @var BankTransferHandlerWithStubbedHelper */
    private $handler;

    /** @var KsfModulesCommonStub */
    private $stub;

    /** @var string Working directory the test started in. */
    private $originalCwd;

    /**
     * Remove the helper class double again.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        $root = dirname(__DIR__, 4);
        $stub = new KsfModulesCommonStub($root, sys_get_temp_dir() . '/ksf_bank_import_processing');
        $stub->cleanup();

        parent::tearDownAfterClass();
    }

    /**
     * Build the handler under test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureGlobalSumCharges();

        $this->originalCwd = getcwd();
        chdir(dirname(__DIR__, 4));

        $this->stub = new KsfModulesCommonStub(
            dirname(__DIR__, 4),
            sys_get_temp_dir() . '/ksf_bank_import_processing'
        );
        $helperPath = $this->stub->link();

        $this->handler = new BankTransferHandlerWithStubbedHelper($this->referenceService());
        $this->handler->setHelperPath($helperPath);

        unset($GLOBALS['ksf_processing_bank_transfer_error'], $GLOBALS['ksf_processing_bank_transfer_ref_error']);
    }

    /**
     * Restore the working directory.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->stub->remove();

        if ($this->originalCwd !== false) {
            chdir($this->originalCwd);
        }

        parent::tearDown();
    }

    /**
     * A complete transaction record.
     *
     * @param string $dc Debit/credit marker.
     *
     * @return array
     */
    private function transaction(string $dc = 'D'): array
    {
        return [
            'transactionDC' => $dc,
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Transfer to savings',
            'transactionCode' => 'TX-9',
            'memo' => 'Savings',
        ];
    }

    /**
     * The handler is bound to the bank transfer partner type.
     *
     * @return void
     */
    public function test_it_handles_the_bank_transfer_partner_type(): void
    {
        $this->assertSame('BT', $this->handler->getPartnerType());
        $this->assertInstanceOf(BankTransferPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('BT'));
        $this->assertFalse($this->handler->canProcess('SP'));
    }

    /**
     * A missing amount is reported.
     *
     * @return void
     */
    public function test_a_missing_amount_is_reported(): void
    {
        $result = $this->handler->process(
            ['transactionDC' => 'D', 'valueTimestamp' => '2025-10-20'],
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Missing required field: transactionAmount', $result->getMessage());
    }

    /**
     * A missing debit/credit marker is reported.
     *
     * @return void
     */
    public function test_a_missing_debit_credit_marker_is_reported(): void
    {
        $result = $this->handler->process(
            ['transactionAmount' => 100.0, 'valueTimestamp' => '2025-10-20'],
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Missing required field: transactionDC', $result->getMessage());
    }

    /**
     * A missing partner bank account is reported.
     *
     * @return void
     */
    public function test_a_missing_partner_bank_account_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['comment' => 'Transfer'],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner bank account ID is required for bank transfers', $result->getMessage());
    }

    /**
     * The helper file is only included once per request.
     *
     * require_once() returns true when the file was already included, so a
     * second transfer in the same request is still processed.
     *
     * @return void
     */
    public function test_a_second_transfer_in_the_same_request_is_still_processed(): void
    {
        $first = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($first->isSuccess());

        $second = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 2],
            2,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($second->isSuccess());
        $this->assertSame(123, $second->getTransNo());
    }

    /**
     * A debit transfer moves money out of our account.
     *
     * @return void
     */
    public function test_a_debit_transfer_moves_money_out(): void
    {
        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 2, 'comment' => 'Move it'],
            42,
            '1,2',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(123, $result->getTransNo());
        $this->assertSame(ST_BANKTRANSFER, $result->getTransType());
        $this->assertStringContainsString('Bank Transfer processed successfully (Trans #123, OUT)', $result->getMessage());
        $this->assertSame(1, $result->getData('from_account'));
        $this->assertSame(2, $result->getData('to_account'));
        $this->assertSame('OUT', $result->getData('direction'));
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_gl_link'));

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(42, $update[0]);
        $this->assertSame(123, $update[3]);
        $this->assertSame('BT', $update[7]);
        $this->assertSame(2, $update[8]);

        $bank = FaStubRegistry::lastCall('set_bank_partner_data');
        $this->assertSame(1, $bank[0]);
        $this->assertSame(ST_BANKTRANSFER, $bank[1]);
        $this->assertSame(2, $bank[2]);
        $this->assertSame('Savings', $bank[3]);
    }

    /**
     * A credit transfer moves money into our account.
     *
     * @return void
     */
    public function test_a_credit_transfer_moves_money_in(): void
    {
        $result = $this->handler->process(
            $this->transaction('C'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(2, $result->getData('from_account'));
        $this->assertSame(1, $result->getData('to_account'));
        $this->assertSame('IN', $result->getData('direction'));
    }

    /**
     * A bank transfer marker is treated like a credit.
     *
     * @return void
     */
    public function test_a_bank_transfer_marker_is_treated_as_a_credit(): void
    {
        $result = $this->handler->process(
            $this->transaction('B'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(2, $result->getData('from_account'));
        $this->assertSame(1, $result->getData('to_account'));
    }

    /**
     * An unsupported debit/credit marker is reported.
     *
     * @return void
     */
    public function test_an_unsupported_debit_credit_marker_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction('X'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid transaction DC type: X', $result->getMessage());
    }

    /**
     * A failure while calculating the target amount is reported.
     *
     * @return void
     */
    public function test_a_failing_target_amount_calculation_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 99],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to configure bank transfer', $result->getMessage());
    }

    /**
     * A failing reference generator is reported.
     *
     * @return void
     */
    public function test_a_failing_reference_generator_is_reported(): void
    {
        $GLOBALS['ksf_processing_bank_transfer_ref_error'] = true;

        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to get reference number', $result->getMessage());
    }

    /**
     * A failing bank transfer is reported.
     *
     * @return void
     */
    public function test_a_failing_bank_transfer_is_reported(): void
    {
        $GLOBALS['ksf_processing_bank_transfer_error'] = true;

        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 2],
            1,
            '',
            ['id' => 1, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to execute bank transfer', $result->getMessage());
    }
}
