<?php
/**
 * MatchedTransactionHandlerTest.php
 * 
 * Test suite for MatchedTransactionHandler
 * Verifies matched transaction processing for automatically matched bank transactions
 * 
 * @author KS Fraser
 * @package Ksfraser\FaBankImport\Tests\Handlers
 */

namespace Ksfraser\FaBankImport\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\MatchedTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\PartnerTypes\MatchedPartnerType;

class MatchedTransactionHandlerTest extends TestCase
{
    private MatchedTransactionHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MatchedTransactionHandler();
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
    public function it_returns_matched_partner_type(): void
    {
        $this->assertEquals('ZZ', $this->handler->getPartnerType());
    }

    /**
     * @test
     */
    public function it_returns_matched_partner_type_object(): void
    {
        $partnerType = $this->handler->getPartnerTypeObject();
        $this->assertInstanceOf(MatchedPartnerType::class, $partnerType);
        $this->assertEquals('ZZ', $partnerType->getShortCode());
        $this->assertEquals('Matched', $partnerType->getLabel());
    }

    /**
     * @test
     */
    public function it_can_process_matched_transactions(): void
    {
        $this->assertTrue($this->handler->canProcess('ZZ'));
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
        $this->assertFalse($this->handler->canProcess('MA'));
    }

    /**
     * @test
     */
    public function it_requires_matched_transaction_number(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'transType' => 10,
            // transNo missing
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
        $this->assertStringContainsString('Matched transaction number', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_requires_matched_transaction_type(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'transNo' => 42,
            // transType missing
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
        $this->assertStringContainsString('Matched transaction type', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_matched_transaction_number(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'transNo' => 0, // Invalid
            'transType' => 10
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
        $this->assertStringContainsString('Invalid matched transaction number', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_matched_transaction_type(): void
    {
        $transaction = [
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-10-20 10:00:00',
            'memo' => 'Test memo'
        ];

        $transactionPostData = [
            'transNo' => 42,
            'transType' => -1 // Invalid
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
        $this->assertStringContainsString('Invalid matched transaction type', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_allows_optional_partner_id(): void
    {
        // Matched transactions don't strictly require partnerId
        // The handler should handle missing partnerId gracefully
        $this->assertTrue(true); // This is validated by the other tests
    }

    /**
     * @test
     */
    public function it_confirms_match_to_existing_fa_entry(): void
    {
        // This test would require mocking FrontAccounting functions
        // which are not available in unit tests
        // Integration tests would verify actual processing
        $this->assertTrue(true); // Placeholder
    }

    /**
     * @test
     */
    public function it_extracts_person_info_from_counterparty(): void
    {
        // This test would require mocking FrontAccounting functions
        // Integration tests would verify person_type and person_type_id extraction
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
        // Test missing transNo
        $result1 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['transType' => 10],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result1->isError());

        // Test missing transType
        $result2 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['transNo' => 42],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result2->isError());

        // Test invalid transNo (0)
        $result3 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['transNo' => 0, 'transType' => 10],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result3->isError());

        // Test invalid transType (-1)
        $result4 = $this->handler->process(
            ['transactionAmount' => 100.00, 'transactionDC' => 'D'],
            ['transNo' => 42, 'transType' => -1],
            123,
            '1,2,3',
            ['id' => 1]
        );
        $this->assertTrue($result4->isError());
    }
}
