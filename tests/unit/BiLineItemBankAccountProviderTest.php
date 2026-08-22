<?php
/**
 * Characterization + decoupling tests: bi_lineitem bank account details.
 *
 * bi_lineitem must not hard-require ksf_modules_common; the FA-backed
 * provider is injected, with a null-object fallback in compat mode.
 *
 * @BABOK Related: refactor-psr category 2 (decoupling)
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;

class BiLineItemBankAccountProviderTest extends TestCase
{
    /**
     * Helper: minimal transaction row accepted by bi_lineitem.
     *
     * @return array
     */
    protected function makeTransaction(): array
    {
        return [
            'id' => 1,
            'transactionDC' => 'C',
            'memo' => 'Test memo long enough',
            'our_account' => '00449 12-93230',
            'valueTimestamp' => '2026-08-22',
            'entryTimestamp' => '2026-08-22',
            'accountName' => 'CIBC Savings account',
            'transactionTitle' => 'Test transaction title',
            'transactionCode' => 'EFT',
            'transactionCodeDesc' => 'Electronic transfer',
            'currency' => 'CAD',
            'status' => '0',
            'fa_trans_type' => 1,
            'fa_trans_no' => 0,
            'transactionType' => 'DEP',
            'transactionAmount' => 100.0,
        ];
    }

    /**
     * @test
     */
    public function it_accepts_an_injected_bank_account_provider(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());

        $provider = new \Ksfraser\FaBankImport\Models\FakeBankAccountDetailsProvider([
            'bank_account_name' => 'CIBC Chequing',
            'account_code' => '1061',
        ]);
        $lineItem->setBankAccountDetailsProvider($provider);

        $lineItem->getBankAccountDetails();

        $this->assertSame('CIBC Chequing', $lineItem->ourBankAccountName);
        $this->assertSame('1061', $lineItem->ourBankAccountCode);
    }

    /**
     * @test
     */
    public function it_falls_back_to_null_object_details_without_fa(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());

        // In compat mode (no ksf_modules_common), must not fatal.
        $lineItem->getBankAccountDetails();

        $this->assertSame('', $lineItem->ourBankAccountName);
    }

    /**
     * @test
     *
     * NOTE: scoped to getLeftHtml(). Full-row getHtml() additionally pulls
     * the matching-transactions/GL chain (see BASELINE_REFACTOR_PSR.md
     * category 2) and is covered by the dedicated decoupling slice.
     */
    public function it_renders_left_column_with_injected_provider(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());
        $lineItem->setBankAccountDetailsProvider(
            new \Ksfraser\FaBankImport\Models\FakeBankAccountDetailsProvider([
                'bank_account_name' => 'Test Account',
                'account_code' => '9999',
            ])
        );

        $html = $lineItem->getLeftHtml();

        $this->assertIsString($html);
        $this->assertStringContainsString('<td', $html);
    }
}
