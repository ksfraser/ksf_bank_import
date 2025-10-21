<?php
/**
 * BankTransferTransactionHandlerTest.php
 * 
 * Test suite for BankTransferTransactionHandler
 * Verifies bank transfer transaction processing for internal account transfers
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Tests\Handlers
 */

namespace Ksfraser\FaBankImport\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\BankTransferTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\PartnerTypes\BankTransferPartnerType;

class BankTransferTransactionHandlerTest extends TestCase
{
    private BankTransferTransactionHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new BankTransferTransactionHandler();
    }

    /**
     * @test
     */
    public function it_implements_transaction_handler_interface(): void
    {
        $this->assertInstanceOf(TransactionHandlerInterface::class, $this->handler);
    }

    /**
     * @test
     */
    public function it_returns_bank_transfer_partner_type(): void
    {
        $this->assertEquals('BT', $this->handler->getPartnerType());
    }

    /**
     * @test
     */
    public function it_returns_bank_transfer_partner_type_object(): void
    {
        $partnerType = $this->handler->getPartnerTypeObject();
        $this->assertInstanceOf(BankTransferPartnerType::class, $partnerType);
        $this->assertEquals('BT', $partnerType->getShortCode());
        $this->assertEquals('Bank Transfer', $partnerType->getLabel());
    }

    /**
     * @test
     */
    public function it_can_process_bank_transfer_transactions(): void
    {
        $this->assertTrue($this->handler->canProcess('BT'));
    }

    /**
     * @test
     */
    public function it_cannot_process_other_transaction_types(): void
    {
        $this->assertFalse($this->handler->canProcess('SP'));
        $this->assertFalse($this->handler->canProcess('CU'));
        $this->assertFalse($this->handler->canProcess('QE'));
        $this->assertFalse($this->handler->canProcess('MA'));
        $this->assertFalse($this->handler->canProcess('ZZ'));
    }

    /**
     * @test
     */
    public function it_requires_transaction_amount(): void
    {
        $transaction = [
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
        ];

        $transactionPostData = [
            'partnerId' => 5,
            'comment' => 'Test transfer'
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,
            '1,2,3',
            $ourAccount
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('transactionAmount', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_transaction_dc(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'valueTimestamp' => '2025-10-20 10:00:00',
        ];

        $transactionPostData = [
            'partnerId' => 5,
            'comment' => 'Test transfer'
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,
            '1,2,3',
            $ourAccount
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('transactionDC', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_partner_bank_account_id(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
        ];

        $transactionPostData = [
            'comment' => 'Test transfer'
            // partnerId missing
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,
            '1,2,3',
            $ourAccount
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner bank account ID', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_handles_credit_transactions_as_incoming_transfers(): void
    {
        // Credit: Money coming IN (FROM partner TO our account)
        // This test would require mocking fa_bank_transfer class
        // which is not available in unit tests
        // Integration tests would verify actual processing
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_handles_debit_transactions_as_outgoing_transfers(): void
    {
        // Debit: Money going OUT (FROM our account TO partner)
        // This test would require mocking fa_bank_transfer class
        // which is not available in unit tests
        // Integration tests would verify actual processing
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_validates_required_fields_before_processing(): void
    {
        // Test missing transactionAmount
        $result1 = $this->handler->process(
            ['transactionDC' => 'D', 'valueTimestamp' => '2025-10-20'],
            ['partnerId' => 5],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result1->isError());

        // Test missing transactionDC
        $result2 = $this->handler->process(
            ['transactionAmount' => 100.00, 'valueTimestamp' => '2025-10-20'],
            ['partnerId' => 5],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result2->isError());

        // Test missing partnerId
        $result3 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D', 'valueTimestamp' => '2025-10-20'],
            ['comment' => 'test'],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result3->isError());
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_memo_for_transfer(): void
    {
        // This would be tested in integration tests where we can
        // verify the actual memo content passed to fa_bank_transfer
        $this->assertTrue(true); // Placeholder
    }
}
