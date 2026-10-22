<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\SettledTransactionDisplay;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the settled transaction display component.
 */
final class SettledTransactionDisplayLineItemsTest extends TestCase
{
    /**
     * @return array A complete settled transaction row.
     */
    private function transactionData(array $overrides = []): array
    {
        return $overrides + [
            'id' => 11,
            'fa_trans_type' => 22,
            'fa_trans_no' => 42,
            'supplier_name' => 'A supplier',
            'bank_account_name' => 'Chequing',
            'customer_name' => 'A customer',
            'branch_name' => 'Main',
        ];
    }

    public function testTheTransactionDataIsExposed(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData());

        $this->assertSame(11, $display->getLineItemId());
        $this->assertSame(22, $display->getTransactionType());
        $this->assertSame(42, $display->getTransactionNumber());
        $this->assertSame($this->transactionData(), $display->getTransactionData());
    }

    public function testMissingValuesFallBackToZero(): void
    {
        $display = new SettledTransactionDisplay([]);

        $this->assertSame(0, $display->getLineItemId());
        $this->assertSame(0, $display->getTransactionType());
        $this->assertSame(0, $display->getTransactionNumber());
    }

    public function testASupplierPaymentIsRendered(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData());

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('Transaction is settled!', $html);
        $this->assertStringContainsString('Payment', $html);
        $this->assertStringContainsString('A supplier', $html);
        $this->assertStringContainsString('Chequing', $html);
        $this->assertStringContainsString('UnsetTrans[11]', $html);
    }

    public function testABankDepositIsRendered(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData(['fa_trans_type' => 12]));

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('Deposit', $html);
        $this->assertStringContainsString('A customer', $html);
        $this->assertStringContainsString('Main', $html);
    }

    public function testAManualSettlementIsRendered(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData(['fa_trans_type' => 0]));

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('Manual settlement', $html);
    }

    public function testAnUnknownTransactionTypeIsRendered(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData(['fa_trans_type' => 99]));

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('other transaction type; no info yet', $html);
    }

    public function testUnknownSupplierAndAccountFallBack(): void
    {
        $display = new SettledTransactionDisplay([
            'id' => 1,
            'fa_trans_type' => 22,
            'fa_trans_no' => 2,
        ]);

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('Unknown Supplier', $html);
        $this->assertStringContainsString('Unknown Account', $html);
    }

    public function testUnknownCustomerAndBranchFallBack(): void
    {
        $display = new SettledTransactionDisplay([
            'id' => 1,
            'fa_trans_type' => 12,
            'fa_trans_no' => 2,
        ]);

        $html = $display->render()->getHtml();

        $this->assertStringContainsString('Unknown Customer', $html);
        $this->assertStringContainsString('Unknown Branch', $html);
    }

    public function testTheDisplayCanBeEchoed(): void
    {
        $display = new SettledTransactionDisplay($this->transactionData());

        ob_start();
        $display->display();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Transaction is settled!', $html);
    }
}
