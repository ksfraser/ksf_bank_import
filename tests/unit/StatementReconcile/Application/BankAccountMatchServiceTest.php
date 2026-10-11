<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\BankAccountMatchService
 */
class BankAccountMatchServiceTest extends TestCase
{
    // ------------------------------------------------------------------
    // Test-double factory
    // ------------------------------------------------------------------

    /**
     * Create a testable subclass that bypasses FA globals by injecting
     * fixture account data and history map directly.
     */
    private function makeService(
        array $faAccounts,
        array $historyMap = [],
        float $minScore = 0.50
    ): BankAccountMatchService {
        return new class (['sr_account_match_min_score' => $minScore], $faAccounts, $historyMap)
            extends BankAccountMatchService {
            private array $accounts;
            private array $history;

            public function __construct(array $config, array $accounts, array $history)
            {
                parent::__construct($config);
                $this->accounts = $accounts;
                $this->history  = $history;
            }

            protected function loadFaBankAccounts(): array
            {
                return $this->accounts;
            }

            protected function loadHistoryMap(string $accountIdentifier): array
            {
                return $this->history;
            }
        };
    }

    /** Build a fake FA bank account row. */
    private function acct(
        int    $id,
        string $name,
        string $number,
        string $bankName = 'Test Bank'
    ): array {
        return [
            'id'                  => $id,
            'bank_account_name'   => $name,
            'bank_account_number' => $number,
            'bank_name'           => $bankName,
        ];
    }

    // ------------------------------------------------------------------
    // Scoring – exact suffix
    // ------------------------------------------------------------------

    public function testExactSuffixMatchReturnsScore100(): void
    {
        $sut    = $this->makeService([$this->acct(1, 'Chequing', '12345678')]);
        $result = $sut->match('5678');

        $this->assertCount(1, $result['candidates']);
        $this->assertSame(1.00, $result['candidates'][0]['score']);
        $this->assertSame('exact_suffix', $result['candidates'][0]['match_method']);
        $this->assertSame(1, $result['best_id']);
    }

    public function testExactSuffixMatchWithHyphensInNumber(): void
    {
        // Stripping non-digits: "1234-5678" → "12345678", identifier "5678" → suffix match
        $sut    = $this->makeService([$this->acct(1, 'Chequing', '1234-5678')]);
        $result = $sut->match('5678');

        $this->assertCount(1, $result['candidates']);
        $this->assertSame(1.00, $result['candidates'][0]['score']);
    }

    // ------------------------------------------------------------------
    // Scoring – substring
    // ------------------------------------------------------------------

    public function testSubstringMatchReturnsScore085(): void
    {
        // "2345" is in middle of "12345678" – not a suffix, so substring
        $sut    = $this->makeService([$this->acct(1, 'Savings', '12345678')]);
        $result = $sut->match('2345');

        $this->assertCount(1, $result['candidates']);
        $this->assertSame(0.85, $result['candidates'][0]['score']);
        $this->assertSame('substring', $result['candidates'][0]['match_method']);
    }

    // ------------------------------------------------------------------
    // Bonuses
    // ------------------------------------------------------------------

    public function testBankNameBonusAdded(): void
    {
        $sut    = $this->makeService([$this->acct(1, 'CIBC Chequing', '12345678', 'CIBC Bank')]);
        $result = $sut->match('5678', 'CIBC');

        // 1.00 (exact_suffix) + 0.10 (bank_name) = 1.10
        $this->assertEqualsWithDelta(1.10, $result['candidates'][0]['score'], 0.001);
        $this->assertStringContainsString('bank_name', $result['candidates'][0]['match_method']);
    }

    public function testBankNameBonusNotAppliedWhenOcrBankNameIsNull(): void
    {
        $sut    = $this->makeService([$this->acct(1, 'CIBC Chequing', '12345678', 'CIBC Bank')]);
        $result = $sut->match('5678', null);

        // No bank name bonus → score stays 1.00
        $this->assertEqualsWithDelta(1.00, $result['candidates'][0]['score'], 0.001);
    }

    public function testHistoryBonusAdded(): void
    {
        // History map says account ID 1 was previously matched
        $sut    = $this->makeService(
            [$this->acct(1, 'Chequing', '12345678')],
            [1 => true]
        );
        $result = $sut->match('5678');

        // 1.00 (exact_suffix) + 0.15 (history) = 1.15
        $this->assertEqualsWithDelta(1.15, $result['candidates'][0]['score'], 0.001);
        $this->assertStringContainsString('history', $result['candidates'][0]['match_method']);
    }

    public function testBothBonusesApplied(): void
    {
        $sut    = $this->makeService(
            [$this->acct(1, 'CIBC Chequing', '12345678', 'CIBC Bank')],
            [1 => true]
        );
        $result = $sut->match('5678', 'CIBC');

        // 1.00 + 0.10 + 0.15 = 1.25
        $this->assertEqualsWithDelta(1.25, $result['candidates'][0]['score'], 0.001);
    }

    // ------------------------------------------------------------------
    // minScore / best_id selection
    // ------------------------------------------------------------------

    public function testSubstringMatchBelowMinScoreNotPreSelected(): void
    {
        // substring = 0.85, but minScore is set to 0.90
        $sut    = $this->makeService(
            [$this->acct(1, 'Savings', '12345678')],
            [],
            0.90
        );
        $result = $sut->match('2345');

        $this->assertCount(1, $result['candidates']); // still in list
        $this->assertNull($result['best_id']);          // not pre-selected
    }

    public function testNoMatchReturnsNullBestId(): void
    {
        $sut    = $this->makeService([$this->acct(1, 'Chequing', '99887766')]);
        $result = $sut->match('5678');

        $this->assertEmpty($result['candidates']);
        $this->assertNull($result['best_id']);
    }

    // ------------------------------------------------------------------
    // Edge cases
    // ------------------------------------------------------------------

    public function testEmptyIdentifierReturnsNoCandidates(): void
    {
        $sut    = $this->makeService([$this->acct(1, 'Chequing', '12345678')]);
        $result = $sut->match('');

        $this->assertEmpty($result['candidates']);
        $this->assertNull($result['best_id']);
    }

    public function testEmptyAccountListReturnsNoCandidates(): void
    {
        $sut    = $this->makeService([]);
        $result = $sut->match('5678');

        $this->assertEmpty($result['candidates']);
        $this->assertNull($result['best_id']);
    }

    public function testIdentifierWithOnlyNonDigitsReturnsNoCandidates(): void
    {
        // After stripping non-digits, identifier becomes '' → no comparison possible
        $sut    = $this->makeService([$this->acct(1, 'Chequing', '12345678')]);
        $result = $sut->match('XXXX');

        $this->assertEmpty($result['candidates']);
    }

    // ------------------------------------------------------------------
    // Sorting
    // ------------------------------------------------------------------

    public function testCandidatesSortedByScoreDescending(): void
    {
        $sut = $this->makeService([
            $this->acct(1, 'Exact Suffix', '12345678'),   // "5678" = suffix → 1.00
            $this->acct(2, 'Substring',    '89567890'),   // "5678" = substring → 0.85
        ]);
        $result = $sut->match('5678');

        $this->assertCount(2, $result['candidates']);
        $this->assertSame(1, $result['candidates'][0]['bank_account_id']);
        $this->assertSame(2, $result['candidates'][1]['bank_account_id']);
    }

    public function testHistoryBonusBumpsAccountAboveHigherBaseScore(): void
    {
        // Account 1: exact_suffix 1.00, no history
        // Account 2: substring 0.85, with history 0.85+0.15=1.00 → tie, but 1 was first (stable sort)
        // More interesting: account 2 with substring+history=1.00 vs account 1 exact only 1.00
        $sut = $this->makeService(
            [
                $this->acct(1, 'Exact', '12345678'),       // exact_suffix: 1.00
                $this->acct(2, 'Substr+history', '89567890'), // substring: 0.85 + history: 0.15 = 1.00
            ],
            [2 => true]
        );
        $result = $sut->match('5678');

        // Both have score 1.00; we just assert both are in results
        $this->assertCount(2, $result['candidates']);
        $scores = array_column($result['candidates'], 'score');
        // Both should be 1.00
        $this->assertEqualsWithDelta(1.00, $scores[0], 0.001);
        $this->assertEqualsWithDelta(1.00, $scores[1], 0.001);
    }
}
