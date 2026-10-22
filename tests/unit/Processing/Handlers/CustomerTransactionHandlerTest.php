<?php

declare(strict_types=1);

/**
 * Tests for the customer transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\PartnerTypes\CustomerPartnerType;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * CustomerTransactionHandler coverage.
 */
final class CustomerTransactionHandlerTest extends ProcessingTestCase
{
    /** @var CustomerTransactionHandler */
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

        $this->handler = new CustomerTransactionHandler($this->referenceService());
    }

    /**
     * A complete transaction record.
     *
     * @param string $dc Debit/credit marker.
     *
     * @return array
     */
    private function transaction(string $dc = 'C'): array
    {
        return [
            'transactionDC' => $dc,
            'transactionAmount' => 120.50,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Invoice 77',
            'memo' => 'Thanks',
        ];
    }

    /**
     * The handler is bound to the customer partner type.
     *
     * @return void
     */
    public function test_it_handles_the_customer_partner_type(): void
    {
        $this->assertSame('CU', $this->handler->getPartnerType());
        $this->assertInstanceOf(CustomerPartnerType::class, $this->handler->getPartnerTypeObject());
        $this->assertTrue($this->handler->canProcess('CU'));
        $this->assertFalse($this->handler->canProcess('SP'));
    }

    /**
     * A credit transaction creates a customer payment.
     *
     * @return void
     */
    public function test_a_credit_transaction_creates_a_customer_payment(): void
    {
        FaStubRegistry::setReturn('sumCharges', 1.25);
        FaStubRegistry::setReturn('my_write_customer_payment', 55);

        $result = $this->handler->process(
            $this->transaction(),
            ['partnerId' => 4, 'partnerDetailId' => 6],
            303,
            '8,9',
            ['id' => 2, 'bank_account_name' => 'Chequing']
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(55, $result->getTransNo());
        $this->assertSame(ST_CUSTPAYMENT, $result->getTransType());
        $this->assertStringContainsString('Customer Payment Processed: 55', $result->getMessage());
        $this->assertNull($result->getData('invoice_no'));
        $this->assertStringContainsString('gl_trans_view.php', $result->getData('view_gl_link'));
        $this->assertStringContainsString('view_receipt.php', $result->getData('view_receipt_link'));
        $this->assertStringContainsString('customer_allocate.php', $result->getData('allocate_link'));

        $payment = FaStubRegistry::lastCall('my_write_customer_payment');
        $this->assertSame(0, $payment[0]);
        $this->assertSame(4, $payment[1]);
        $this->assertSame(6, $payment[2]);
        $this->assertSame(2, $payment[3]);
        $this->assertSame(120.50, $payment[6]);
        $this->assertSame(1.25, $payment[10]);
        $this->assertSame(ST_CUSTPAYMENT, $payment[12]);

        $update = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(303, $update[0]);
        $this->assertSame(55, $update[3]);
        $this->assertSame('CU', $update[7]);
        $this->assertSame(4, $update[8]);

        $partnerCalls = FaStubRegistry::calls('update_partner_data');
        $this->assertCount(2, $partnerCalls);
        $this->assertSame(PT_CUSTOMER, $partnerCalls[0][1]);
        $this->assertSame(ST_CUSTPAYMENT, $partnerCalls[1][1]);
    }

    /**
     * A short transaction title is completed with the memo and the comment.
     *
     * @return void
     */
    public function test_the_memo_is_built_from_the_title_memo_and_comment(): void
    {
        $transaction = $this->transaction();
        $transaction['transactionTitle'] = 'AB';
        $transaction['memo'] = 'Memo text';

        $this->handler->process(
            $transaction,
            ['partnerId' => 4, 'comment' => 'Comment text'],
            1,
            '',
            ['id' => 2]
        );

        $payment = FaStubRegistry::lastCall('my_write_customer_payment');
        $this->assertSame('AB : Memo text::Comment text', $payment[8]);
    }

    /**
     * When an invoice is supplied the payment is allocated against it.
     *
     * @return void
     */
    public function test_a_supplied_invoice_is_allocated(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            ['partnerId' => 4, 'invoice' => 'INV-9'],
            1,
            '',
            ['id' => 2]
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame('INV-9', $result->getData('invoice_no'));
        $this->assertStringContainsString('Allocated to Invoice INV-9', $result->getMessage());
        $this->assertNull($result->getData('allocate_link'));

        $allocation = FaStubRegistry::lastCall('add_cust_allocation');
        $this->assertSame(120.50, $allocation[0]);
        $this->assertSame(ST_CUSTPAYMENT, $allocation[1]);
        $this->assertSame(ST_SALESINVOICE, $allocation[3]);
        $this->assertSame('INV-9', $allocation[4]);
        $this->assertSame(4, $allocation[5]);

        $this->assertCount(2, FaStubRegistry::calls('update_debtor_trans_allocation'));
    }

    /**
     * A debit transaction is refused because customers only pay in.
     *
     * @return void
     */
    public function test_a_debit_transaction_is_refused(): void
    {
        $result = $this->handler->process(
            $this->transaction('D'),
            ['partnerId' => 4],
            1,
            '',
            ['id' => 2]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Customer transactions must be Credit (C). Got: D', $result->getMessage());
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
            ['partnerId' => 4],
            1,
            '',
            ['id' => 2]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Error processing customer transaction', $result->getMessage());
    }

    /**
     * A missing partner id is reported as an error.
     *
     * @return void
     */
    public function test_a_missing_partner_id_is_reported(): void
    {
        $result = $this->handler->process(
            $this->transaction(),
            [],
            1,
            '',
            ['id' => 2]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner ID not found', $result->getMessage());
    }

    /**
     * A failed customer payment write is reported as an error.
     *
     * @return void
     */
    public function test_a_failed_customer_payment_write_is_reported(): void
    {
        FaStubRegistry::setReturn('my_write_customer_payment', 0);

        $result = $this->handler->process(
            $this->transaction(),
            ['partnerId' => 4],
            1,
            '',
            ['id' => 2]
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to create customer payment', $result->getMessage());
    }
}
