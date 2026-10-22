<?php

declare(strict_types=1);

/**
 * Tests for the quick entry transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Config\BankImportConfig;
use Ksfraser\FaBankImport\Handlers\QuickEntryTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\QuickEntryPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * QuickEntryTransactionHandler coverage.
 */
final class QuickEntryTransactionHandlerTest extends ProcessingTestCase
{
    /** @var QuickEntryTransactionHandler */
    private $handler;

    /**
     * Build the handler under test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureGlobalSumCharges();

        $this->handler = new QuickEntryTransactionHandler($this->referenceService());
    }

    /**
     * Restore the module configuration after every test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        set_company_pref('bank_import_trans_ref_logging', '1');
        set_company_pref('bank_import_trans_ref_account', '0000');

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
            'transactionAmount' => 60.00,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Office supplies',
            'account' => '1234',
            'account_name' => 'Somewhere Inc',
            'transactionCode' => 'TX-1',
            'memo' => 'Stationery',
        ];
    }

    /**
     * The handler is bound to the quick entry partner type.
     *
     * @return void
     */
    public function test_it_handles_the_quick_entry_partner_type(): void
    {
        $this->assertSame('QE', $this->handler->getPartnerType());
        $this->assertInstanceOf(QuickEntryPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('QE'));
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
            ['transactionDC' => 'D'],
            ['partnerId' => 3],
            1,
            '',
            ['id' => 1]
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
            ['transactionAmount' => 10.0],
            ['partnerId' => 3],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Missing required field: transactionDC', $result->getMessage());
    }

    /**
     * A missing quick entry template id is reported.
     *
     * @return void
     */
    public function test_a_missing_template_id_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            [],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner ID (Quick Entry template) is required', $result->getMessage());
    }

    /**
     * A debit transaction is written as a bank payment.
     *
     * @return void
     */
    public function test_a_debit_transaction_is_written_as_a_bank_payment(): void
    {
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKPAYMENT, 21]);

        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 3, 'comment' => 'Monthly order'],
            77,
            '1,2',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(21, $result->getTransNo());
        $this->assertSame(ST_BANKPAYMENT, $result->getTransType());
        $this->assertStringContainsString('Quick Entry Bank Payment processed successfully (Trans #21)', $result->getMessage());
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_gl_link'));
        $this->assertStringContainsString('attachments.php', $result->getData('attach_link'));

        $template = FaStubRegistry::lastCall('qe_to_cart');
        $this->assertSame(3, $template[1]);
        $this->assertSame(60.0, $template[2]);
        $this->assertSame(QE_PAYMENT, $template[3]);
        $this->assertStringContainsString('Monthly order', $template[4]);
        $this->assertStringContainsString('Chequing', $template[4]);

        $write = FaStubRegistry::lastCall('write_bank_transaction');
        $this->assertSame(ST_BANKPAYMENT, $write[0]);
        $this->assertSame(4, $write[2]);
        $this->assertSame(PT_QUICKENTRY, $write[5]);
        $this->assertSame(3, $write[6]);

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(77, $update[0]);
        $this->assertSame(21, $update[3]);
        $this->assertSame('QE', $update[7]);

        $bank = FaStubRegistry::lastCall('set_bank_partner_data');
        $this->assertSame(4, $bank[0]);
        $this->assertSame(ST_BANKPAYMENT, $bank[1]);
        $this->assertSame(3, $bank[2]);
    }

    /**
     * A credit transaction is written as a bank deposit.
     *
     * @return void
     */
    public function test_a_credit_transaction_is_written_as_a_bank_deposit(): void
    {
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKDEPOSIT, 22]);

        $result = $this->handler->process(
            $this->transaction('C'),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(ST_BANKDEPOSIT, $result->getTransType());
        $this->assertStringContainsString('Quick Entry Bank Deposit processed successfully', $result->getMessage());

        $template = FaStubRegistry::lastCall('qe_to_cart');
        $this->assertSame(QE_DEPOSIT, $template[3]);
    }

    /**
     * A failing quick entry template load is reported.
     *
     * @return void
     */
    public function test_a_failing_template_load_is_reported(): void
    {
        FaStubRegistry::setException('qe_to_cart', new \Exception('template missing'));

        $result = $this->handler->process(
            $this->transaction(),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to load Quick Entry template: template missing', $result->getMessage());
    }

    /**
     * An empty quick entry cart is reported.
     *
     * @return void
     */
    public function test_an_empty_quick_entry_cart_is_reported(): void
    {
        FaStubRegistry::setReturn('qe_to_cart.add_gl_item', false);

        $result = $this->handler->process(
            $this->transaction(),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Quick Entry not loaded', $result->getMessage());
    }

    /**
     * A date outside the fiscal year is moved to the end of the fiscal year.
     *
     * @return void
     */
    public function test_a_date_outside_the_fiscal_year_is_moved_to_the_fiscal_year_end(): void
    {
        FaStubRegistry::setReturn('is_date_in_fiscalyear', false);
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKPAYMENT, 23]);

        $this->handler->process(
            $this->transaction(),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertSame(1, FaStubRegistry::callCount('end_fiscalyear'));
    }

    /**
     * A bank charge is added to the cart as its own GL line.
     *
     * @return void
     */
    public function test_a_bank_charge_is_added_to_the_cart(): void
    {
        FaStubRegistry::setReturn('sumCharges', 2.5);
        FaStubRegistry::setReturn('get_company_pref', '5690');
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKPAYMENT, 24]);

        $this->handler->process(
            $this->transaction(),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertSame(1, FaStubRegistry::callCount('get_company_pref'));
        $this->assertSame('bank_charge_act', FaStubRegistry::lastCall('get_company_pref')[0]);
    }

    /**
     * A short transaction title is completed with the memo.
     *
     * @return void
     */
    public function test_a_short_transaction_title_is_completed_with_the_memo(): void
    {
        $transaction = $this->transaction();
        $transaction['transactionTitle'] = 'AB';

        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKPAYMENT, 26]);

        $this->handler->process(
            $transaction,
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $template = FaStubRegistry::lastCall('qe_to_cart');
        $this->assertStringContainsString('AB : Stationery', $template[4]);
    }

    /**
     * The transaction reference logging can be switched off.
     *
     * @return void
     */
    public function test_the_transaction_reference_logging_can_be_switched_off(): void
    {
        set_company_pref('bank_import_trans_ref_logging', '0');
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKPAYMENT, 25]);

        $this->handler->process(
            $this->transaction(),
            ['partnerId' => 3],
            1,
            '',
            ['id' => 4, 'bank_account_name' => 'Chequing']
        );

        $this->assertFalse(BankImportConfig::getTransRefLoggingEnabled());
        $this->assertSame('0000', BankImportConfig::getTransRefAccount());
    }
}
