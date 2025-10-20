<?php

/**
 * Unit tests for SettledTransactionDisplay component
 *
 * Tests the settled transaction display that shows:
 * - Settled status indicator
 * - Transaction type (Payment, Deposit, Manual, etc.)
 * - Supplier/Customer/Branch details
 * - Bank account information
 * - Unset transaction button
 *
 * @package    KsfBankImport
 * @subpackage Tests\Unit
 * @since      20251019
 */

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\SettledTransactionDisplay;

/**
 * Test SettledTransactionDisplay component
 *
 * @since 20251019
 */
class SettledTransactionDisplayTest extends TestCase
{
    /**
     * FA Transaction type constants (from FrontAccounting)
     * ST_SUPPAYMENT = 22 (Supplier Payment)
     * ST_BANKDEPOSIT = 12 (Bank Deposit)
     */
    private const ST_SUPPAYMENT = 22;
    private const ST_BANKDEPOSIT = 12;
    private const ST_MANUAL = 0;

    /**
     * Create settled transaction data for supplier payment
     */
    private function createSupplierPaymentData(): array
    {
        return [
            'id' => 123,
            'fa_trans_type' => self::ST_SUPPAYMENT,
            'fa_trans_no' => 8811,
            'supplier_name' => 'Acme Corp',
            'bank_account_name' => 'Main Checking Account',
        ];
    }

    /**
     * Create settled transaction data for bank deposit
     */
    private function createBankDepositData(): array
    {
        return [
            'id' => 456,
            'fa_trans_type' => self::ST_BANKDEPOSIT,
            'fa_trans_no' => 1234,
            'customer_name' => 'John Doe',
            'branch_name' => 'Main Branch',
        ];
    }

    /**
     * Create settled transaction data for manual settlement
     */
    private function createManualSettlementData(): array
    {
        return [
            'id' => 789,
            'fa_trans_type' => self::ST_MANUAL,
            'fa_trans_no' => 5555,
        ];
    }

    /**
     * @test
     */
    public function testConstruction(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        
        $this->assertInstanceOf(SettledTransactionDisplay::class, $display);
    }

    /**
     * @test
     */
    public function testAcceptsTransactionData(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        
        $this->assertSame($data, $display->getTransactionData());
    }

    /**
     * @test
     */
    public function testRendersSettledStatus(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Transaction is settled', $html);
        $this->assertStringContainsString('Status:', $html);
    }

    /**
     * @test
     */
    public function testRendersSupplierPaymentOperation(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Operation:', $html);
        $this->assertStringContainsString('Payment', $html);
    }

    /**
     * @test
     */
    public function testRendersSupplierName(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Supplier:', $html);
        $this->assertStringContainsString('Acme Corp', $html);
    }

    /**
     * @test
     */
    public function testRendersBankAccountForSupplierPayment(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('From bank account:', $html);
        $this->assertStringContainsString('Main Checking Account', $html);
    }

    /**
     * @test
     */
    public function testRendersBankDepositOperation(): void
    {
        $data = $this->createBankDepositData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Operation:', $html);
        $this->assertStringContainsString('Deposit', $html);
    }

    /**
     * @test
     */
    public function testRendersCustomerAndBranchForDeposit(): void
    {
        $data = $this->createBankDepositData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Customer/Branch:', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('Main Branch', $html);
    }

    /**
     * @test
     */
    public function testRendersManualSettlement(): void
    {
        $data = $this->createManualSettlementData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Operation:', $html);
        $this->assertStringContainsString('Manual settlement', $html);
    }

    /**
     * @test
     */
    public function testRendersUnsetButton(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('Unset Transaction Association', $html);
        $this->assertStringContainsString('UnsetTrans', $html);
        $this->assertStringContainsString('8811', $html); // Transaction number
    }

    /**
     * @test
     */
    public function testUnsetButtonIncludesLineItemId(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('UnsetTrans[123]', $html);
    }

    /**
     * @test
     */
    public function testHandlesUnknownTransactionType(): void
    {
        $data = [
            'id' => 999,
            'fa_trans_type' => 99, // Unknown type
            'fa_trans_no' => 7777,
        ];
        
        $display = new SettledTransactionDisplay($data);
        $html = $display->render();
        
        $this->assertStringContainsString('other transaction type', $html);
    }

    /**
     * @test
     */
    public function testCanBeReusedForMultipleTransactions(): void
    {
        $data1 = $this->createSupplierPaymentData();
        $data2 = $this->createBankDepositData();
        
        $display1 = new SettledTransactionDisplay($data1);
        $html1 = $display1->render();
        
        $display2 = new SettledTransactionDisplay($data2);
        $html2 = $display2->render();
        
        $this->assertStringContainsString('Payment', $html1);
        $this->assertStringNotContainsString('Deposit', $html1);
        
        $this->assertStringContainsString('Deposit', $html2);
        $this->assertStringNotContainsString('Payment', $html2);
    }

    /**
     * @test
     */
    public function testReturnsTransactionType(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        
        $this->assertSame(self::ST_SUPPAYMENT, $display->getTransactionType());
    }

    /**
     * @test
     */
    public function testReturnsTransactionNumber(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        
        $this->assertSame(8811, $display->getTransactionNumber());
    }

    /**
     * @test
     */
    public function testReturnsLineItemId(): void
    {
        $data = $this->createSupplierPaymentData();
        
        $display = new SettledTransactionDisplay($data);
        
        $this->assertSame(123, $display->getLineItemId());
    }
}
