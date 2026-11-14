<?php
/**
 * Bank-transaction handler happy-path coverage tests.
 *
 * Drives the process() bodies of the six transaction handlers that were
 * previously unreachable in CI (the FA write-side functions they call are
 * stubbed by ksfraser/famock's FaWriteStubs.php). Complements the
 * interface/behaviour tests in tests/unit/Handlers/.
 *
 * @BABOK RTM: FR-049 / BR-002 / IR-001 / ARCH-004; coverage: docs/TestCases/Batch/
 */

namespace Ksfraser\FaBankImport\Tests\Coverage;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Handlers\QuickEntryTransactionHandler;
use Ksfraser\FaBankImport\Handlers\SupplierTransactionHandler;
use Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler;
use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;
use Ksfraser\FaBankImport\Handlers\MatchedTransactionHandler;
use Ksfraser\FaBankImport\Handlers\ManualSettlementHandler;

class BankTransactionHandlersProcessTest extends TestCase
{
    protected function setUp(): void
    {
        // Drive the same namespace-level FA doubles the Processing suite
        // uses. Once any other suite in the process has declared them, the
        // handlers' unqualified calls resolve to these doubles instead of
        // famock's globals, so the tests must not depend on which
        // implementation is active. Configuring FaStubRegistry here makes
        // the outcomes identical either way.
        require_once __DIR__ . '/../Processing/Support/FaStubRegistry.php';
        require_once __DIR__ . '/../Processing/Support/handler_fa_stubs.php';

        FaStubRegistry::reset();
        FaStubRegistry::setReturn('write_bank_transaction', [1, 777]);
        FaStubRegistry::setReturn('write_supp_payment', 555);
        FaStubRegistry::setReturn('my_write_customer_payment', 666);
        FaStubRegistry::setReturn('get_trans_counterparty', [
            [
                'person_id' => 10,
                'person_type' => 'supplier',
                'person_type_id' => 3,
            ],
        ]);
    }

    private function transaction(array $overrides = []): array
    {
        return array_merge([
            'transactionAmount' => 125.50,
            'transactionDC' => 'D',
            'valueTimestamp' => '2026-10-19',
            'transactionTitle' => 'ACME periodic invoice payment',
            'account' => 'BANK-001',
            'account_name' => 'KSF Bank Chequing',
            'memo' => 'eft payment batch 42',
            'transactionCode' => 'TX-2026-0100',
        ], $overrides);
    }

    private function postData(array $overrides = []): array
    {
        return array_merge([
            'partnerId' => 7,
            'comment' => 'batch upload',
        ], $overrides);
    }

    private function ourAccount(): array
    {
        // id 2 exists in famock's get_bank_account data (CAD)
        return ['id' => 2, 'bank_account_name' => 'KSF Bank Chequing', 'bank_curr_code' => 'CAD'];
    }

    public function test_quick_entry_debit_creates_bank_payment(): void
    {
        $handler = new QuickEntryTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(),
            42,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(777, $result->getTransNo());
        $this->assertSame(1, $result->getTransType(), 'Debit should map to ST_BANKPAYMENT');
        $this->assertStringContainsString('Bank Payment', $result->getMessage());
        $this->assertStringContainsString('Trans #777', $result->getMessage());
        $this->assertNotNull(FaStubRegistry::lastCall('qe_to_cart'));
        $this->assertNotNull(FaStubRegistry::lastCall('write_bank_transaction'));
        $this->assertNotNull(FaStubRegistry::lastCall('update_transactions'));

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(42, $call[0], 'transaction id');
        $this->assertSame('1', $call[1], 'collection ids');
        $this->assertSame(777, $call[3]);
        $this->assertSame(1, $call[4], 'ST_BANKPAYMENT');
        $this->assertSame('QE', $call[7]);
        $this->assertSame(7, $call[8]);
    }

    public function test_quick_entry_credit_creates_bank_deposit(): void
    {
        $handler = new QuickEntryTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'C']),
            $this->postData(),
            43,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(2, $result->getTransType(), 'Credit should map to ST_BANKDEPOSIT');
    }

    public function test_quick_entry_error_when_template_missing(): void
    {
        $handler = new QuickEntryTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            ['comment' => 'no partner id'],
            44,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('required', $result->getMessage());
    }

    public function test_supplier_debit_writes_supplier_payment(): void
    {
        $handler = new SupplierTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(),
            52,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(555, $result->getTransNo(), 'write_supp_payment returns 555');
        $this->assertNotNull(FaStubRegistry::lastCall('write_supp_payment'));
        $this->assertNotNull(FaStubRegistry::lastCall('update_partner_data'));

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(52, $call[0], 'transaction id');
        $this->assertSame(555, $call[3]);
        $this->assertSame('SP', $call[7]);
    }

    public function test_supplier_credit_writes_supplier_refund_via_bank_transaction(): void
    {
        $handler = new SupplierTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'C']),
            $this->postData(),
            53,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(777, $result->getTransNo(), 'write_bank_transaction returns 777');
        $this->assertNotNull(FaStubRegistry::lastCall('write_bank_transaction'));
    }

    public function test_supplier_invalid_dc_type_returns_error(): void
    {
        $handler = new SupplierTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'X']),
            $this->postData(),
            54,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Invalid transaction DC type', $result->getMessage());
    }

    public function test_customer_credit_writes_customer_payment_with_allocation(): void
    {
        $handler = new CustomerTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'C']),
            $this->postData(['partnerDetailId' => 11, 'invoice' => 902]),
            62,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(666, $result->getTransNo(), 'my_write_customer_payment returns 666');
        $this->assertNotNull(FaStubRegistry::lastCall('my_write_customer_payment'));
        $this->assertNotNull(FaStubRegistry::lastCall('add_cust_allocation'));
        $this->assertNotNull(FaStubRegistry::lastCall('update_debtor_trans_allocation'));

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(62, $call[0], 'transaction id');
        $this->assertSame(666, $call[3]);
        $this->assertSame('CU', $call[7]);
    }

    public function test_customer_debit_returns_error(): void
    {
        $handler = new CustomerTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'D']),
            $this->postData(),
            63,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('must be Credit', $result->getMessage());
    }

    public function test_bank_transfer_debit_executes_transfer_out(): void
    {
        $handler = new BankTransferTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(['partnerId' => 1]),
            72,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertNotNull(FaStubRegistry::lastCall('set_bank_partner_data'));

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(72, $call[0], 'transaction id');
        $this->assertSame(123, $call[3], 'fa_bank_transfer mock sets trans_no 123');
        $this->assertSame(4, $call[4], 'ST_BANKTRANSFER');
        $this->assertSame('BT', $call[7]);
    }

    public function test_bank_transfer_credit_executes_transfer_in(): void
    {
        $handler = new BankTransferTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'C']),
            $this->postData(['partnerId' => 1]),
            73,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
    }

    public function test_bank_transfer_invalid_dc_returns_error(): void
    {
        $handler = new BankTransferTransactionHandler();

        $result = $handler->process(
            $this->transaction(['transactionDC' => 'Z']),
            $this->postData(['partnerId' => 1]),
            74,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
    }

    public function test_matched_transaction_confirms_existing_entry(): void
    {
        $handler = new MatchedTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(['transNo' => 88, 'transType' => 22, 'memo' => 'matched EFT']),
            82,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(88, $result->getTransNo());
        $this->assertSame(22, $result->getTransType());
        $this->assertStringContainsString('MATCH', $result->getMessage());
        $this->assertNotNull(FaStubRegistry::lastCall('set_partner_data'));

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(82, $call[0], 'transaction id');
        $this->assertSame(88, $call[3]);
        $this->assertSame(22, $call[4]);
        $this->assertTrue($call[5], 'matched flag');
    }

    public function test_matched_transaction_requires_trans_no(): void
    {
        $handler = new MatchedTransactionHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(),
            83,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Matched transaction number is required', $result->getMessage());
    }

    public function test_manual_settlement_links_existing_entry(): void
    {
        $handler = new ManualSettlementHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(['existingEntry' => 91, 'existingType' => 22]),
            92,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(91, $result->getTransNo());
        $this->assertSame(22, $result->getTransType());
        $this->assertStringContainsString('manually settled', $result->getMessage());

        $call = FaStubRegistry::lastCall('update_transactions');
        $this->assertSame(92, $call[0], 'transaction id');
        $this->assertSame(91, $call[3]);
    }

    public function test_manual_settlement_adds_receipt_link_for_customer_payment_type(): void
    {
        $handler = new ManualSettlementHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(['existingEntry' => 93, 'existingType' => 12]),
            94,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame(12, $result->getTransType());
        $this->assertArrayHasKey('view_receipt_link', $result->getData());
    }

    public function test_manual_settlement_failure_to_retrieve_counterparty_is_reported(): void
    {
        FaStubRegistry::setException(
            'get_trans_counterparty',
            new \RuntimeException('db unavailable')
        );
        $handler = new ManualSettlementHandler();

        $result = $handler->process(
            $this->transaction(),
            $this->postData(['existingEntry' => 95, 'existingType' => 22]),
            96,
            '1',
            $this->ourAccount()
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('counterparty', $result->getMessage());
    }
}
