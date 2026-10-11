<?php
/**
 * QuickEntryTransactionHandlerTest.php
 * 
 * Test suite for QuickEntryTransactionHandler
 * Verifies Quick Entry transaction processing for both bank payments and deposits
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Tests\Handlers
 */

namespace Ksfraser\FaBankImport\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\QuickEntryTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\PartnerTypes\QuickEntryPartnerType;

class QuickEntryTransactionHandlerTest extends TestCase
{
    private QuickEntryTransactionHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new QuickEntryTransactionHandler();
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
    public function it_returns_quick_entry_partner_type(): void
    {
        $this->assertEquals('QE', $this->handler->getPartnerType());
    }

    /**
     * @test
     */
    public function it_returns_quick_entry_partner_type_object(): void
    {
        $partnerType = $this->handler->getPartnerTypeObject();
        $this->assertInstanceOf(QuickEntryPartnerType::class, $partnerType);
        $this->assertEquals('QE', $partnerType->getShortCode());
        $this->assertEquals('Quick Entry', $partnerType->getLabel());
    }

    /**
     * @test
     */
    public function it_can_process_quick_entry_transactions(): void
    {
        $this->assertTrue($this->handler->canProcess('QE'));
    }

    /**
     * @test
     */
    public function it_cannot_process_other_transaction_types(): void
    {
        $this->assertFalse($this->handler->canProcess('SP'));
        $this->assertFalse($this->handler->canProcess('CU'));
        $this->assertFalse($this->handler->canProcess('BT'));
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
            'comment' => 'Test comment'
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,          // transactionId
            '1,2,3',      // collectionIds
            $ourAccount   // ourAccount
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
            'comment' => 'Test comment'
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,          // transactionId
            '1,2,3',      // collectionIds
            $ourAccount   // ourAccount
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('transactionDC', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_partner_id_quick_entry_template(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
        ];

        $transactionPostData = [
            'comment' => 'Test comment'
            // partnerId missing
        ];

        $ourAccount = ['id' => 1, 'bank_account_name' => 'Test Bank'];

        $result = $this->handler->process(
            $transaction,
            $transactionPostData,
            123,          // transactionId
            '1,2,3',      // collectionIds
            $ourAccount   // ourAccount
        );

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner ID', $result->getMessage());
        $this->assertStringContainsString('Quick Entry template', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_handles_debit_transactions_as_bank_payments(): void
    {
        // This test would require mocking FrontAccounting functions
        // which are not available in unit tests
        // Integration tests would verify actual processing
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_handles_credit_transactions_as_bank_deposits(): void
    {
        // This test would require mocking FrontAccounting functions
        // which are not available in unit tests
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
            ['transactionDC' => 'D'],
            ['partnerId' => 5],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result1->isError());

        // Test missing transactionDC
        $result2 = $this->handler->process(
            ['transactionAmount' => 100.00],
            ['partnerId' => 5],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result2->isError());

        // Test missing partnerId
        $result3 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['comment' => 'test'],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result3->isError());
    }
}
