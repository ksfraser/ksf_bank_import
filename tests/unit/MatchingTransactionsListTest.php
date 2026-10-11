<?php

/**
 * Unit tests for MatchingTransactionsList component
 *
 * Tests the matching GL transactions display that shows:
 * - List of matching GL transactions from FA database
 * - Transaction links (type and number)
 * - Score, account matching, amounts
 * - Customer/person details if available
 * - Radio buttons for selection (in future enhancement)
 *
 * @package    KsfBankImport
 * @subpackage Tests\Unit
 * @since      20251019
 */

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\MatchingTransactionsList;

/**
 * Test MatchingTransactionsList component
 *
 * @since 20251019
 */
class MatchingTransactionsListTest extends TestCase
{
    /**
     * Create sample matching transactions array
     */
    private function createMatchingTransactions(): array
    {
        return [
            [
                'type' => 0,
                'type_no' => 8811,
                'tran_date' => '2023-01-03',
                'account' => '2620.frontier',
                'memo_' => '025/2023',
                'amount' => 432.41,
                'person_type_id' => null,
                'person_id' => null,
                'account_name' => 'Auto Loan Frontier (Nissan Finance)',
                'reference' => '025/2023',
                'score' => 111,
                'is_invoice' => false,
            ],
            [
                'type' => 10,
                'type_no' => 1234,
                'tran_date' => '2023-01-05',
                'account' => '1060.checking',
                'memo_' => 'Payment received',
                'amount' => -500.00,
                'person_type_id' => 'C',
                'person_id' => 25,
                'account_name' => 'Checking Account',
                'reference' => 'INV-2023-001',
                'score' => 95,
                'is_invoice' => true,
            ],
        ];
    }

    /**
     * Create bank transaction data
     */
    private function createBankTransactionData(): array
    {
        return [
            'our_account' => '1060.checking',
            'transactionDC' => 'D', // Debit
            'amount' => 432.41,
            'ourBankDetails' => [
                'bank_account_name' => 'Main Checking',
            ],
        ];
    }

    /**
     * @test
     */
    public function testConstruction(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        
        $this->assertInstanceOf(MatchingTransactionsList::class, $list);
    }

    /**
     * @test
     */
    public function testAcceptsMatchingTransactionsArray(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        
        $this->assertCount(2, $list->getMatchingTransactions());
    }

    /**
     * @test
     */
    public function testAcceptsBankTransactionData(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        
        $this->assertSame($bankData, $list->getBankTransactionData());
    }

    /**
     * @test
     */
    public function testRendersWithMatchingTransactions(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('Matching GLs', $html);
        $this->assertStringContainsString('8811', $html); // Transaction number
        $this->assertStringContainsString('432.41', $html); // Amount
    }

    /**
     * @test
     */
    public function testRendersEmptyStateWhenNoMatches(): void
    {
        $list = new MatchingTransactionsList([], $this->createBankTransactionData());
        $html = $list->render();
        
        $this->assertStringContainsString('No Matches found', $html);
    }

    /**
     * @test
     */
    public function testRendersTransactionNumbers(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('8811', $html);
        $this->assertStringContainsString('1234', $html);
    }

    /**
     * @test
     */
    public function testRendersTransactionScores(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('Score', $html);
        $this->assertStringContainsString('111', $html);
        $this->assertStringContainsString('95', $html);
    }

    /**
     * @test
     */
    public function testRendersAccountNames(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('Auto Loan Frontier', $html);
        $this->assertStringContainsString('Checking Account', $html);
    }

    /**
     * @test
     */
    public function testRendersAmounts(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('432.41', $html);
        $this->assertStringContainsString('500.00', $html);
    }

    /**
     * @test
     */
    public function testHighlightsMatchingAmounts(): void
    {
        $matchingTrans = [[
            'type' => 0,
            'type_no' => 8811,
            'tran_date' => '2023-01-03',
            'account' => '2620.frontier',
            'amount' => -432.41, // Negative to match debit
            'account_name' => 'Test Account',
            'score' => 111,
        ]];
        
        $bankData = [
            'our_account' => '1060.checking',
            'transactionDC' => 'D', // Debit (amount should be negated for comparison)
            'amount' => 432.41,
            'ourBankDetails' => ['bank_account_name' => 'Main Checking'],
        ];
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        // Matching amount should be in bold
        $this->assertMatchesRegularExpression('/<b>.*-?432\.41.*<\/b>/i', $html);
    }

    /**
     * @test
     */
    public function testNumbersEachMatchingTransaction(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        $this->assertStringContainsString('<b>1</b>:', $html);
        $this->assertStringContainsString('<b>2</b>:', $html);
    }

    /**
     * @test
     */
    public function testSkipsTransactionsWithoutDate(): void
    {
        $matchingTrans = [
            [
                'type' => 0,
                'type_no' => 8811,
                // Missing 'tran_date'
                'account' => '2620.frontier',
                'amount' => 432.41,
                'account_name' => 'Test Account',
                'score' => 111,
            ],
            [
                'type' => 10,
                'type_no' => 1234,
                'tran_date' => '2023-01-05', // Has date
                'account' => '1060.checking',
                'amount' => 500.00,
                'account_name' => 'Checking Account',
                'score' => 95,
            ],
        ];
        
        $bankData = $this->createBankTransactionData();
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $html = $list->render();
        
        // Should show 1234 but not 8811
        $this->assertStringContainsString('1234', $html);
        $this->assertStringNotContainsString('8811', $html);
    }

    /**
     * @test
     */
    public function testCanUseUrlBuilder(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        $list->setUrlBuilder(new \Ksfraser\UrlBuilder('../../gl/view/gl_trans_view.php'));
        
        $this->assertInstanceOf(\Ksfraser\UrlBuilder::class, $list->getUrlBuilder());
    }

    /**
     * @test
     */
    public function testCanBeReusedForMultipleBankTransactions(): void
    {
        $matchingTrans1 = [['type' => 0, 'type_no' => 111, 'tran_date' => '2023-01-01', 
                            'account' => 'test', 'amount' => 100, 'account_name' => 'Test', 'score' => 50]];
        $matchingTrans2 = [['type' => 0, 'type_no' => 222, 'tran_date' => '2023-01-02', 
                            'account' => 'test2', 'amount' => 200, 'account_name' => 'Test2', 'score' => 60]];
        
        $bankData = $this->createBankTransactionData();
        
        $list1 = new MatchingTransactionsList($matchingTrans1, $bankData);
        $html1 = $list1->render();
        
        $list2 = new MatchingTransactionsList($matchingTrans2, $bankData);
        $html2 = $list2->render();
        
        $this->assertStringContainsString('111', $html1);
        $this->assertStringNotContainsString('222', $html1);
        
        $this->assertStringContainsString('222', $html2);
        $this->assertStringNotContainsString('111', $html2);
    }

    /**
     * @test
     */
    public function testHandlesEmptyBankData(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        
        $list = new MatchingTransactionsList($matchingTrans, []);
        $html = $list->render();
        
        $this->assertStringContainsString('Matching GLs', $html);
    }

    /**
     * @test
     */
    public function testReturnsMatchCount(): void
    {
        $matchingTrans = $this->createMatchingTransactions();
        $bankData = $this->createBankTransactionData();
        
        $list = new MatchingTransactionsList($matchingTrans, $bankData);
        
        $this->assertSame(2, $list->getMatchCount());
    }

    /**
     * @test
     */
    public function testReturnsZeroMatchCountForEmpty(): void
    {
        $list = new MatchingTransactionsList([], []);
        
        $this->assertSame(0, $list->getMatchCount());
    }
}
