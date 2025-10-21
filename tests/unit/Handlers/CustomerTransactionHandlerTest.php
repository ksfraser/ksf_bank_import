<?php

/**
 * Customer Transaction Handler Test
 *
 * Tests for the CustomerTransactionHandler class.
 * STEP 5: Extract CU (Customer) case logic into dedicated handler class.
 *
 * @package    Tests\Unit\Handlers
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit\Handlers;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Handlers\CustomerTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Customer Transaction Handler Test
 *
 * Verifies CustomerTransactionHandler correctly processes customer transactions
 * 
 * Note: This handler processes customer payments (Credit transactions) which
 * can optionally be allocated against specific invoices.
 */
class CustomerTransactionHandlerTest extends TestCase
{
    /**
     * Test handler implements interface
     *
     * @test
     */
    public function it_implements_transaction_handler_interface(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $this->assertInstanceOf(TransactionHandlerInterface::class, $handler);
    }

    /**
     * Test returns correct partner type
     *
     * @test
     */
    public function it_returns_customer_partner_type(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $this->assertSame('CU', $handler->getPartnerType());
    }

    /**
     * Test can process when partner type is CU
     *
     * @test
     */
    public function it_can_process_customer_transactions(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $this->assertTrue($handler->canProcess('CU'));
    }

    /**
     * Test cannot process when partner type is not CU
     *
     * @test
     */
    public function it_cannot_process_non_customer_transactions(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $this->assertFalse($handler->canProcess('SP'));
        $this->assertFalse($handler->canProcess('QE'));
    }

    /**
     * Test validates required transaction fields
     *
     * @test
     */
    public function it_validates_required_transaction_fields(): void
    {
        $handler = new CustomerTransactionHandler();
        
        // Missing required fields
        $transaction = ['transactionAmount' => 100];
        $transactionPostData = [
            'partnerId' => 10,
            'invoice' => null,
            'comment' => null,
            'partnerDetailId' => null,
        ];
        
        $result = $handler->process(
            $transaction,
            $transactionPostData,
            1,
            'cid123',
            ['id' => 1, 'bank_account_name' => 'Test']
        );
        
        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Required field', $result->getMessage());
    }

    /**
     * Test requires partner ID in POST data
     *
     * @test
     */
    public function it_requires_partner_id(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $transaction = [
            'transactionDC' => 'C',
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test payment'
        ];
        $transactionPostData = [
            'partnerId' => null,  // Missing partner ID
            'invoice' => null,
            'comment' => null,
            'partnerDetailId' => null,
        ];
        
        $result = $handler->process(
            $transaction,
            $transactionPostData,
            1,
            'cid123',
            ['id' => 1]
        );
        
        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Partner ID not found', $result->getMessage());
    }

    /**
     * Test rejects Debit transactions (customers only pay, don't receive)
     *
     * @test
     */
    public function it_rejects_debit_transactions(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $transaction = [
            'transactionDC' => 'D', // Debit - not allowed for customers
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test'
        ];
        $transactionPostData = [
            'partnerId' => 10,
            'invoice' => null,
            'comment' => null,
            'partnerDetailId' => null,
        ];
        
        $result = $handler->process(
            $transaction,
            $transactionPostData,
            1,
            'cid123',
            ['id' => 1]
        );
        
        $this->assertTrue($result->isError());
        $this->assertStringContainsString('must be Credit (C)', $result->getMessage());
    }

    /**
     * Test does not require controller dependency
     *
     * @test
     */
    public function it_does_not_require_controller_dependency(): void
    {
        // Handler should be instantiated without any dependencies
        $handler = new CustomerTransactionHandler();
        
        $this->assertInstanceOf(CustomerTransactionHandler::class, $handler);
    }

    /**
     * Test checks specific partner type
     *
     * @test
     */
    public function it_only_checks_customer_partner_type(): void
    {
        $handler = new CustomerTransactionHandler();
        
        // Should return FALSE for Supplier
        $this->assertFalse($handler->canProcess('SP'));
        
        // Should return TRUE for Customer
        $this->assertTrue($handler->canProcess('CU'));
        
        // Should return FALSE for Quick Entry
        $this->assertFalse($handler->canProcess('QE'));
    }

    /**
     * Test accepts Credit transactions only
     *
     * @test
     */
    public function it_only_accepts_credit_transactions(): void
    {
        $handler = new CustomerTransactionHandler();
        
        $debitTransaction = [
            'transactionDC' => 'D',
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test'
        ];
        
        $transactionPostData = [
            'partnerId' => 10,
            'invoice' => null,
            'comment' => null,
            'partnerDetailId' => 128,
        ];
        
        $result = $handler->process(
            $debitTransaction,
            $transactionPostData,
            1,
            'cid123',
            ['id' => 1]
        );
        
        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Credit (C)', $result->getMessage());
    }
}
