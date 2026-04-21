<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair
 */
class MatchedPairTest extends TestCase
{
    public function testConstructsSuccessfully(): void
    {
        $pair = new MatchedPair('L001', 42, 0.90, ['EXACT_AMOUNT_DATE', 'TYPE_MATCH']);

        $this->assertSame('L001', $pair->getStatementLineId());
        $this->assertSame(42, $pair->getBankTransactionId());
        $this->assertEqualsWithDelta(0.90, $pair->getMatchConfidence(), 0.001);
        $this->assertSame(['EXACT_AMOUNT_DATE', 'TYPE_MATCH'], $pair->getRulesMatched());
    }

    public function testIsHighConfidence(): void
    {
        $high = new MatchedPair('L001', 1, 0.95, []);
        $low  = new MatchedPair('L002', 2, 0.80, []);

        $this->assertTrue($high->isHighConfidence(0.90));
        $this->assertFalse($low->isHighConfidence(0.90));
    }

    public function testRejectsEmptyStatementLineId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MatchedPair('', 1, 0.5, []);
    }

    public function testRejectsZeroBankTransactionId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MatchedPair('L001', 0, 0.5, []);
    }

    public function testRejectsNegativeBankTransactionId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MatchedPair('L001', -1, 0.5, []);
    }

    public function testRejectsConfidenceAboveOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MatchedPair('L001', 1, 1.01, []);
    }

    public function testRejectsNegativeConfidence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MatchedPair('L001', 1, -0.01, []);
    }

    public function testFromArray(): void
    {
        $pair = MatchedPair::fromArray([
            'statement_line_id'   => 'L005',
            'bank_transaction_id' => 100,
            'match_confidence'    => 0.75,
            'rules_matched'       => ['EXACT_AMOUNT_DATE'],
        ]);

        $this->assertSame('L005', $pair->getStatementLineId());
        $this->assertSame(100, $pair->getBankTransactionId());
    }

    public function testToArray(): void
    {
        $pair = new MatchedPair('L001', 7, 0.80, ['DESCRIPTION_FUZZY']);
        $arr  = $pair->toArray();

        $this->assertSame('L001', $arr['statement_line_id']);
        $this->assertSame(7, $arr['bank_transaction_id']);
        $this->assertEqualsWithDelta(0.80, $arr['match_confidence'], 0.001);
        $this->assertSame(['DESCRIPTION_FUZZY'], $arr['rules_matched']);
    }

    public function testGetFaTransTypeAndNoWhenSet(): void
    {
        $pair = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE'], 41, 100);

        $this->assertSame(41, $pair->getFaTransType());
        $this->assertSame(100, $pair->getFaTransNo());
    }

    public function testGetFaTransTypeAndNoNullByDefault(): void
    {
        $pair = new MatchedPair('L001', 1, 0.95, []);

        $this->assertNull($pair->getFaTransType());
        $this->assertNull($pair->getFaTransNo());
    }
}
