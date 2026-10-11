<?php

/**
 * Supplier Transaction Handler Test
 *
 * Tests for the SupplierTransactionHandler class.
 * STEP 4: Extract SP (Supplier) case logic into dedicated handler class.
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
use Ksfraser\FaBankImport\Handlers\SupplierTransactionHandler;
use Ksfraser\FaBankImport\Handlers\TransactionHandlerInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Supplier Transaction Handler Test
 *
 * Verifies SupplierTransactionHandler correctly processes supplier transactions
 * 
 * Note: This handler now contains the full business logic extracted from
 * bank_import_controller::processSupplierTransaction(). It no longer depends
 * on the controller - that was just a middleman.
 */
class SupplierTransactionHandlerTest extends TestCase
{
    /**
     * Test handler implements interface
     *
     * @test
     */
    public function it_implements_transaction_handler_interface(): void
    {
        $handler = new SupplierTransactionHandler();
        
        $this->assertInstanceOf(TransactionHandlerInterface::class, $handler);
    }

    /**
     * Test returns correct partner type
     *
     * @test
     */
    public function it_returns_supplier_partner_type(): void
    {
        $handler = new SupplierTransactionHandler();
        
        $this->assertSame('SP', $handler->getPartnerType());
    }

    /**
     * Test can process when partner type is SP
     *
     * @test
     */
    public function it_can_process_supplier_transactions(): void
    {
        $handler = new SupplierTransactionHandler();
        
        $this->assertTrue($handler->canProcess('SP'));
    }

    /**
     * Test cannot process when partner type is not SP
     *
     * @test
     */
    public function it_cannot_process_non_supplier_transactions(): void
    {
        $handler = new SupplierTransactionHandler();
        
        $this->assertFalse($handler->canProcess('CU'));
    }

    /**
     * Test validates required transaction fields
     *
     * @test
     */
    public function it_validates_required_transaction_fields(): void
    {
        $handler = new SupplierTransactionHandler();
        
        // Missing required fields
        $transaction = ['transactionAmount' => 100];
        $transactionPostData = [
            'partnerId' => 5,
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
        $handler = new SupplierTransactionHandler();
        
        $transaction = [
            'transactionDC' => 'D',
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
     * Test rejects invalid transaction DC type
     *
     * @test
     */
    public function it_rejects_invalid_transaction_dc_type(): void
    {
        $handler = new SupplierTransactionHandler();
        
        $transaction = [
            'transactionDC' => 'X', // Invalid
            'transactionAmount' => 100,
            'valueTimestamp' => '2025-10-20',
            'transactionTitle' => 'Test'
        ];
        $transactionPostData = [
            'partnerId' => 5,
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
        $this->assertStringContainsString('Invalid transaction DC type', $result->getMessage());
    }

    /**
     * Test no longer requires controller dependency
     *
     * @test
     */
    public function it_does_not_require_controller_dependency(): void
    {
        // Handler should be instantiated without any dependencies
        $handler = new SupplierTransactionHandler();
        
        $this->assertInstanceOf(SupplierTransactionHandler::class, $handler);
    }

    /**
     * Test only checks specific transaction, not entire batch
     * 
     * This test validates that the canProcess method now uses PartnerType
     * enum-style static methods instead of checking arrays.
     *
     * @test
     */
    public function it_only_checks_specific_transaction_in_batch(): void
    {
        $handler = new SupplierTransactionHandler();
        
        // Should return FALSE for Customer
        $this->assertFalse($handler->canProcess('CU'));
        
        // Should return TRUE for Supplier
        $this->assertTrue($handler->canProcess('SP'));
        
        // Should return FALSE for Quick Entry
        $this->assertFalse($handler->canProcess('QE'));
    }
}
