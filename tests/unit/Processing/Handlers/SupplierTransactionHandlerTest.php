<?php

declare(strict_types=1);

/**
 * Tests for the supplier transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\SupplierTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\SupplierPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * SupplierTransactionHandler coverage.
 */
final class SupplierTransactionHandlerTest extends ProcessingTestCase
{
    /** @var SupplierTransactionHandler */
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

        $this->handler = new SupplierTransactionHandler($this->referenceService());
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
            'transactionAmount' => 250.00,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Supplier invoice 42',
            'account' => '1234',
            'memo' => 'Rent',
        ];
    }

    /**
     * The handler is bound to the supplier partner type.
     *
     * @return void
     */
    public function test_it_handles_the_supplier_partner_type(): void
    {
        $this->assertSame('SP', $this->handler->getPartnerType());
        $this->assertInstanceOf(SupplierPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('SP'));
        $this->assertFalse($this->handler->canProcess('CU'));
    }

    /**
     * A debit transaction creates a supplier payment.
     *
     * @return void
     */
    public function test_a_debit_transaction_creates_a_supplier_payment(): void
    {
        FaStubRegistry::setReturn('sumCharges', 5.0);
        FaStubRegistry::setReturn('write_supp_payment', 31);

        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 7],
            101,
            '1,2',
            ['id' => 3, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(31, $result->getTransNo());
        $this->assertSame(ST_SUPPAYMENT, $result->getTransType());
        $this->assertStringContainsString('Supplier Payment Processed: 31', $result->getMessage());
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_link'));
        $this->assertStringContainsString('supplier_allocate.php', $result->getData('allocate_link'));

        $payment = FaStubRegistry::lastCall('write_supp_payment');
        $this->assertSame(0, $payment[0]);
        $this->assertSame(7, $payment[1]);
        $this->assertSame(3, $payment[2]);
        $this->assertSame(250.0, $payment[5]);
        $this->assertSame(5.0, $payment[8]);

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(101, $update[0]);
        $this->assertSame('1,2', $update[1]);
        $this->assertSame(1, $update[2]);
        $this->assertSame(31, $update[3]);
        $this->assertSame(ST_SUPPAYMENT, $update[4]);
        $this->assertSame('SP', $update[7]);
        $this->assertSame(7, $update[8]);

        $partner = FaStubRegistry::lastCall('update_partner_data');
        $this->assertSame(7, $partner[0]);
        $this->assertSame(PT_SUPPLIER, $partner[1]);
        $this->assertNull($partner[2]);
        $this->assertSame('1234', $partner[3]);
    }

    /**
     * A credit transaction creates a supplier refund.
     *
     * @return void
     */
    public function test_a_credit_transaction_creates_a_supplier_refund(): void
    {
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKDEPOSIT, 88]);

        $result = $this->handler->process(
            $this->transaction('C'),
            ['partnerId' => 9],
            202,
            '5',
            ['id' => 3, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(88, $result->getTransNo());
        $this->assertSame(ST_BANKDEPOSIT, $result->getTransType());
        $this->assertStringContainsString('Supplier Refund Processed: 88', $result->getMessage());
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_link'));

        $supplier = FaStubRegistry::lastCall('get_supplier');
        $this->assertSame(9, $supplier[0]);

        $write = FaStubRegistry::lastCall('write_bank_transaction');
        $this->assertSame(ST_BANKDEPOSIT, $write[0]);
        $this->assertSame(3, $write[2]);
        $this->assertSame(PT_SUPPLIER, $write[5]);
        $this->assertSame(9, $write[6]);
        $this->assertSame(ANY_NUMERIC, $write[7]);
    }

    /**
     * A missing required field is reported as an error.
     *
     * @return void
     */
    public function test_a_missing_required_field_is_reported(): void
    {
        $result = $this->handler->process(
            ['transactionAmount' => 10.0],
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Error processing supplier transaction', $result->getMessage());
        $this->assertStringContainsString('Required field', $result->getMessage());
    }

    /**
     * A missing partner id is reported as an error.
     *
     * @return void
     */
    public function test_a_missing_partner_id_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction('D'),
            [],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner ID not found', $result->getMessage());
    }

    /**
     * An unsupported debit/credit marker is reported as an error.
     *
     * @return void
     */
    public function test_an_unsupported_debit_credit_marker_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction('X'),
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid transaction DC type: X', $result->getMessage());
    }

    /**
     * A failed supplier payment write is reported as an error.
     *
     * @return void
     */
    public function test_a_failed_supplier_payment_write_is_reported(): void
    {
        FaStubRegistry::setReturn('write_supp_payment', 0);

        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to create supplier payment', $result->getMessage());
    }

    /**
     * A failed supplier refund write is reported as an error.
     *
     * @return void
     */
    public function test_a_failed_supplier_refund_write_is_reported(): void
    {
        FaStubRegistry::setReturn('write_bank_transaction', [ST_BANKDEPOSIT]);

        $result = $this->handler->process(
            $this->transaction('C'),
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to create supplier refund', $result->getMessage());
    }

    /**
     * A refund without a usable GL line is refused.
     *
     * @return void
     */
    public function test_a_refund_without_a_usable_gl_line_is_refused(): void
    {
        FaStubRegistry::setReturn('items_cart.add_gl_item', false);

        $result = $this->handler->process(
            $this->transaction('C'),
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('You must enter at least one payment line', $result->getMessage());
    }

    /**
     * A refund of zero is refused because the bank amount would be zero.
     *
     * @return void
     */
    public function test_a_zero_amount_refund_is_refused(): void
    {
        $transaction = $this->transaction('C');
        $transaction['transactionAmount'] = 0;

        $result = $this->handler->process(
            $transaction,
            ['partnerId' => 7],
            1,
            '',
            ['id' => 1]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('The total bank amount cannot be 0', $result->getMessage());
    }
}
