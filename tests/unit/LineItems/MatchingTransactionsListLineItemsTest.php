<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\MatchingTransactionsList;
use Ksfraser\UrlBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the matching transactions list component.
 */
final class MatchingTransactionsListLineItemsTest extends TestCase
{
    /**
     * @return array A matching GL row.
     */
    private function match(array $overrides = []): array
    {
        return $overrides + [
            'tran_date' => '2026-01-31',
            'type' => 1,
            'type_no' => 7,
            'account' => '000123',
            'account_name' => 'Chequing',
            'amount' => 25.5,
            'score' => 90,
        ];
    }

    /**
     * @return array The bank transaction the matches are compared against.
     */
    private function bankTransaction(array $overrides = []): array
    {
        return $overrides + [
            'our_account' => '000123',
            'amount' => 25.5,
            'transactionDC' => 'C',
            'ourBankDetails' => ['bank_account_name' => 'Chequing'],
        ];
    }

    public function testTheDataIsExposed(): void
    {
        $list = new MatchingTransactionsList([$this->match()], $this->bankTransaction());

        $this->assertCount(1, $list->getMatchingTransactions());
        $this->assertSame($this->bankTransaction(), $list->getBankTransactionData());
    }

    public function testTheUrlBuilderIsOptional(): void
    {
        $list = new MatchingTransactionsList([], []);

        $this->assertNull($list->getUrlBuilder());
    }

    public function testTheUrlBuilderCanBeSet(): void
    {
        $list = new MatchingTransactionsList([], []);
        $builder = new UrlBuilder('/inquiry.php');

        $this->assertSame($list, $list->setUrlBuilder($builder));
        $this->assertSame($builder, $list->getUrlBuilder());
    }

    public function testOnlyMatchesWithADateAreCounted(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(), ['type' => 1, 'type_no' => 2]],
            $this->bankTransaction()
        );

        $this->assertSame(1, $list->getMatchCount());
    }

    public function testAnEmptyListIsRendered(): void
    {
        $list = new MatchingTransactionsList([], $this->bankTransaction());

        $html = $list->render();

        $this->assertStringContainsString('No Matches found automatically', $html);
    }

    public function testAMatchIsRendered(): void
    {
        $list = new MatchingTransactionsList([$this->match()], $this->bankTransaction());

        $html = $list->render();

        $this->assertStringContainsString('Transaction 1:7', $html);
        $this->assertStringContainsString('Score 90', $html);
        $this->assertStringContainsString('Chequing', $html);
        $this->assertStringContainsString('25.50', $html);
    }

    public function testAMatchWithoutADateIsSkipped(): void
    {
        $list = new MatchingTransactionsList(
            [['type' => 1, 'type_no' => 2, 'account' => 'x', 'account_name' => 'y', 'amount' => 1, 'score' => 1]],
            $this->bankTransaction()
        );

        $this->assertStringNotContainsString('Transaction 1:2', $list->render());
    }

    public function testAMatchOnAnotherAccountIsHighlighted(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(['account' => '999999'])],
            $this->bankTransaction()
        );

        $this->assertStringContainsString('Account <b>999999</b>', $list->render());
    }

    public function testAMatchOnOurOwnAccountIsMarked(): void
    {
        $list = new MatchingTransactionsList([$this->match()], $this->bankTransaction());

        $this->assertStringContainsString('MATCH BANK::', $list->render());
    }

    public function testAMatchOnTheBankAccountNameIsMarked(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(['account' => 'Chequing'])],
            $this->bankTransaction(['our_account' => '000123'])
        );

        $this->assertStringContainsString('MATCH BANK::', $list->render());
    }

    public function testAMatchingAmountIsHighlighted(): void
    {
        $list = new MatchingTransactionsList([$this->match()], $this->bankTransaction());

        $this->assertStringContainsString('<b> 25.50</b>', $list->render());
    }

    public function testADifferentAmountIsNotHighlighted(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(['amount' => 99.99])],
            $this->bankTransaction()
        );

        $this->assertStringNotContainsString('<b> 99.99</b>', $list->render());
    }

    public function testADebitNegatesTheAmount(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(['amount' => -25.5])],
            $this->bankTransaction(['transactionDC' => 'D'])
        );

        $this->assertStringContainsString('<b> -25.50</b>', $list->render());
    }

    public function testAMatchWithPersonDetailsIsRendered(): void
    {
        $list = new MatchingTransactionsList(
            [$this->match(['person_type_id' => 1])],
            $this->bankTransaction()
        );

        $this->assertStringContainsString('Score 90', $list->render());
    }

    public function testTheBankTransactionDataMayBeEmpty(): void
    {
        $list = new MatchingTransactionsList([$this->match()], []);

        $html = $list->render();

        $this->assertStringContainsString('Account <b>000123</b>', $html);
        $this->assertStringContainsString('25.50', $html);
    }
}
