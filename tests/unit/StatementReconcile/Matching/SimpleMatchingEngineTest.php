<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Matching;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;
use Ksfraser\FaBankImport\StatementReconcile\Matching\SimpleMatchingEngine;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Matching\SimpleMatchingEngine
 */
class SimpleMatchingEngineTest extends TestCase
{
    private function makeStatementOcr(array $lines): StatementOcr
    {
        $metadata = StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500',
            'closing_balance'      => '1200',
        ]);

        $rawOcr = new RawOcrResult('{"model":"gemma4"}', 'gemma4');

        return StatementOcr::create($metadata, $lines, $rawOcr);
    }

    private function makeLine(
        string $id,
        string $date,
        string $amount,
        string $desc,
        string $type = 'debit'
    ): StatementLine {
        return new StatementLine($id, new \DateTimeImmutable($date), $desc, $amount, $type, '');
    }

    private function makeBankTx(
        int $id,
        string $date,
        string $amount,
        string $desc,
        string $type = 'debit'
    ): BankTransactionDto {
        return new BankTransactionDto($id, new \DateTimeImmutable($date), $amount, $desc, $type);
    }

    public function testExactAmountAndDateMatchReturnsHighConfidence(): void
    {
        $lines  = [$this->makeLine('L001', '2026-03-15', '99.99', 'Amazon')];
        $bankTx = [$this->makeBankTx(1, '2026-03-15', '99.99', 'Amazon')];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $pairs = $session->getMatchedPairs();
        $this->assertCount(1, $pairs);
        $this->assertSame('L001', $pairs[0]->getStatementLineId());
        $this->assertGreaterThanOrEqual(0.70, $pairs[0]->getMatchConfidence());
        $this->assertContains('EXACT_AMOUNT_DATE', $pairs[0]->getRulesMatched());
    }

    public function testDifferentAmountProducesNoMatch(): void
    {
        $lines  = [$this->makeLine('L001', '2026-03-15', '99.99', 'Amazon')];
        $bankTx = [$this->makeBankTx(1, '2026-03-15', '100.00', 'Amazon')];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $this->assertCount(0, $session->getMatchedPairs());
        $this->assertContains('L001', $session->getUnmatchedStatementLineIds());
    }

    public function testSameBankTxNotMatchedTwice(): void
    {
        // Two statement lines with the same amount/date – only the first should match bank tx #1.
        $lines = [
            $this->makeLine('L001', '2026-03-15', '50.00', 'Coffee'),
            $this->makeLine('L002', '2026-03-15', '50.00', 'Coffee'),
        ];
        $bankTx = [$this->makeBankTx(1, '2026-03-15', '50.00', 'Coffee Shop')];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $pairs = $session->getMatchedPairs();
        $this->assertCount(1, $pairs);
        $this->assertCount(1, $session->getUnmatchedStatementLineIds());
    }

    public function testUnmatchedBankTransactionsReported(): void
    {
        $lines  = [];
        $bankTx = [
            $this->makeBankTx(10, '2026-03-10', '20.00', 'Taxi'),
            $this->makeBankTx(11, '2026-03-12', '30.00', 'Bus'),
        ];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $this->assertCount(0, $session->getMatchedPairs());
        $unmatched = $session->getUnmatchedBankTransactionIds();
        $this->assertContains(10, $unmatched);
        $this->assertContains(11, $unmatched);
    }

    public function testDescriptionSimilarityAddsConfidence(): void
    {
        $lines  = [$this->makeLine('L001', '2026-03-20', '75.00', 'Starbucks Coffee Shop')];
        $bankTx = [$this->makeBankTx(5, '2026-03-20', '75.00', 'STARBUCKS COFFEE')];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $pairs = $session->getMatchedPairs();
        $this->assertCount(1, $pairs);
        // Should include both EXACT_AMOUNT_DATE and DESCRIPTION_FUZZY.
        $this->assertContains('DESCRIPTION_FUZZY', $pairs[0]->getRulesMatched());
        $this->assertGreaterThanOrEqual(0.90, $pairs[0]->getMatchConfidence());
    }

    public function testEmptyStatementAndBankProduceEmptySession(): void
    {
        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr([]), []);

        $this->assertCount(0, $session->getMatchedPairs());
        $this->assertSame(ReconciliationSession::STATUS_PENDING, $session->getStatus());
    }

    public function testCustomThresholdBlocksLowConfidenceMatch(): void
    {
        // Amount and date match (EXACT_AMOUNT_DATE +0.70) but:
        //   - descriptions are dissimilar → DESCRIPTION_FUZZY does NOT fire
        //   - types differ (credit vs debit) → TYPE_MATCH does NOT fire
        // Total confidence = 0.70, which is below the 0.80 threshold → no match.
        $lines  = [$this->makeLine('L001', '2026-03-05', '100.00', 'AcmeVendorInc', 'credit')];
        $bankTx = [$this->makeBankTx(1, '2026-03-05', '100.00', 'BetaCorpLtd', 'debit')];

        $engine  = new SimpleMatchingEngine(0.80);
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        // Only EXACT_AMOUNT_DATE (0.70) matched → 0.70 < 0.80 threshold → blocked.
        $this->assertCount(0, $session->getMatchedPairs());
    }

    public function testMultipleLinesMatchCorrectly(): void
    {
        $lines = [
            $this->makeLine('L001', '2026-03-01', '10.00', 'Netflix'),
            $this->makeLine('L002', '2026-03-05', '200.00', 'Supermarket'),
            $this->makeLine('L003', '2026-03-10', '50.00', 'Gas Station'),
        ];
        $bankTx = [
            $this->makeBankTx(1, '2026-03-01', '10.00', 'Netflix'),
            $this->makeBankTx(2, '2026-03-05', '200.00', 'Grocery Store'),
            $this->makeBankTx(3, '2026-03-15', '50.00', 'Gas'),   // date mismatch for L003
        ];

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr($lines), $bankTx);

        $matchedLineIds = array_map(
            static function (MatchedPair $p): string { return $p->getStatementLineId(); },
            $session->getMatchedPairs()
        );

        $this->assertContains('L001', $matchedLineIds);
        $this->assertContains('L002', $matchedLineIds);
        // L003 has date mismatch → unmatched.
        $this->assertContains('L003', $session->getUnmatchedStatementLineIds());
    }

    /**
     * Line 190: when either description tokenises to an empty array, scoresDescriptionFuzzy
     * returns false (no DESCRIPTION_FUZZY bonus).  The engine should still match via
     * EXACT_AMOUNT_DATE + TYPE_MATCH (0.70 + 0.10 = 0.80 ≥ default threshold 0.70).
     */
    public function testEmptyDescriptionTokensDoNotPreventMatchOnAmountAndDate(): void
    {
        $line   = $this->makeLine('L001', '2026-03-15', '50.00', '');
        $bankTx = $this->makeBankTx(1, '2026-03-15', '50.00', '');

        $engine  = new SimpleMatchingEngine();
        $session = $engine->match($this->makeStatementOcr([$line]), [$bankTx]);

        // Still matches on amount+date+type; fuzzy false-return branch (line 190) covered.
        $this->assertCount(1, $session->getMatchedPairs());
    }
}
