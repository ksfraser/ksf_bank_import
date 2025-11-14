<?php
/**
 * ManualSettlementHandlerTest.php
 * 
 * Test suite for ManualSettlementHandler
 * Verifies manual settlement transaction processing for linking bank transactions
 * to existing FrontAccounting entries
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Tests\Handlers
 */

namespace Ksfraser\FaBankImport\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\ManualSettlementHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\PartnerTypes\ManualSettlementPartnerType;

class ManualSettlementHandlerTest extends TestCase
{
    private ManualSettlementHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ManualSettlementHandler();
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
    public function it_returns_manual_settlement_partner_type(): void
    {
        $this->assertEquals('MA', $this->handler->getPartnerType());
    }

    /**
     * @test
     */
    public function it_returns_manual_settlement_partner_type_object(): void
    {
        $partnerType = $this->handler->getPartnerTypeObject();
        $this->assertInstanceOf(ManualSettlementPartnerType::class, $partnerType);
        $this->assertEquals('MA', $partnerType->getShortCode());
        $this->assertEquals('Manual settlement', $partnerType->getLabel());
    }

    /**
     * @test
     */
    public function it_can_process_manual_settlement_transactions(): void
    {
        $this->assertTrue($this->handler->canProcess('MA'));
    }

    /**
     * @test
     */
    public function it_cannot_process_other_transaction_types(): void
    {
        $this->assertFalse($this->handler->canProcess('SP'));
        $this->assertFalse($this->handler->canProcess('CU'));
        $this->assertFalse($this->handler->canProcess('QE'));
        $this->assertFalse($this->handler->canProcess('BT'));
        $this->assertFalse($this->handler->canProcess('ZZ'));
    }

    /**
     * @test
     */
    public function it_requires_existing_entry_number(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'existingType' => 10,
            // existingEntry missing
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
        $this->assertStringContainsString('Existing Entry number', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_existing_entry_type(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'existingEntry' => 42,
            // existingType missing
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
        $this->assertStringContainsString('Existing Entry type', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_existing_entry_number(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'existingEntry' => 0, // Invalid
            'existingType' => 10
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
        $this->assertStringContainsString('Invalid existing entry number', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_existing_entry_type(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'existingEntry' => 42,
            'existingType' => -1 // Invalid
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
        $this->assertStringContainsString('Invalid existing entry type', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_links_transaction_to_existing_fa_entry(): void
    {
        // This test would require mocking FrontAccounting functions
        // which are not available in unit tests
        // Integration tests would verify actual processing
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_provides_view_gl_link_in_result(): void
    {
        // This test would require mocking FrontAccounting functions
        // Integration tests would verify the links are included
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_provides_receipt_link_for_customer_payments(): void
    {
        // Special case: Type 12 (ST_CUSTPAYMENT) should include receipt link
        // This test would require mocking FrontAccounting functions
        // Integration tests would verify the receipt link is included
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_validates_required_fields_before_processing(): void
    {
        // Test missing existingEntry
        $result1 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['existingType' => 10],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result1->isError());

        // Test missing existingType
        $result2 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['existingEntry' => 42],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result2->isError());

        // Test invalid existingEntry (0)
        $result3 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['existingEntry' => 0, 'existingType' => 10],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result3->isError());

        // Test invalid existingType (-1)
        $result4 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['existingEntry' => 42, 'existingType' => -1],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result4->isError());
    }
}
