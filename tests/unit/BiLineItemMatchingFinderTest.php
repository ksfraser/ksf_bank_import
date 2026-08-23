<?php
/**
 * Decoupling slice 2: injectable matching-transactions finder.
 *
 * bi_lineitem::findMatchingExistingJE() must not hard-require
 * ksf_modules_common MatchingJEs; an injected finder supplies matches,
 * with an empty-result fallback in compat mode.
 *
 * @BABOK Related: refactor-psr category 2 (decoupling)
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;

class BiLineItemMatchingFinderTest extends TestCase
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
    public function it_accepts_an_injected_matching_finder(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());

        $finder = new \Ksfraser\FaBankImport\Models\FakeMatchingTransactionsFinder([
            ['type' => 0, 'type_no' => 8811, 'tran_date' => '2023-01-03', 'account' => '2620.frontier', 'memo_' => '', 'amount' => 100.0, 'score' => 111, 'is_invoice' => 0],
        ]);
        $lineItem->setMatchingTransactionsFinder($finder);

        $matches = $lineItem->findMatchingExistingJE();

        $this->assertCount(1, $matches);
        $this->assertSame(8811, $matches[0]['type_no']);
    }

    /**
     * @test
     */
    public function it_falls_back_to_empty_matches_without_fa(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());

        $matches = $lineItem->findMatchingExistingJE();

        $this->assertIsArray($matches);
        $this->assertCount(0, $matches);
    }

    /**
     * @test
     */
    public function it_renders_full_row_with_injected_finder(): void
    {
        $lineItem = new \bi_lineitem($this->makeTransaction());
        $lineItem->setBankAccountDetailsProvider(
            new \Ksfraser\FaBankImport\Models\FakeBankAccountDetailsProvider([
                'bank_account_name' => 'Test Account',
                'account_code' => '9999',
            ])
        );
        $lineItem->setMatchingTransactionsFinder(
            new \Ksfraser\FaBankImport\Models\FakeMatchingTransactionsFinder()
        );

        $html = $lineItem->getHtml();

        $this->assertIsString($html);
        $this->assertStringContainsString('<tr', $html);
        $this->assertStringContainsString('No Matches found automatically', $html);
    }
}
