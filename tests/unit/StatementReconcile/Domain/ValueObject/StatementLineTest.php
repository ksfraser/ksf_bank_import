<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine
 */
class StatementLineTest extends TestCase
{
    private function makeDate(string $ymd = '2026-03-15'): \DateTimeImmutable
    {
        return new \DateTimeImmutable($ymd);
    }

    public function testConstructsSuccessfully(): void
    {
        $line = new StatementLine('L001', $this->makeDate(), 'Amazon', '99.99', 'debit', 'raw');
        $this->assertSame('L001', $line->getLineId());
        $this->assertSame('Amazon', $line->getDescription());
        $this->assertSame('99.99', $line->getAmount());
        $this->assertEqualsWithDelta(99.99, $line->getAmountFloat(), 0.001);
        $this->assertSame('debit', $line->getType());
        $this->assertFalse($line->isCredit());
    }

    public function testCreditType(): void
    {
        $line = new StatementLine('L002', $this->makeDate(), 'Refund', '50.00', 'credit', '');
        $this->assertTrue($line->isCredit());
    }

    public function testRejectsEmptyLineId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementLine('', $this->makeDate(), 'Desc', '10.00', 'debit', '');
    }

    public function testRejectsInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementLine('L001', $this->makeDate(), 'Desc', '10.00', 'unknown', '');
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementLine('L001', $this->makeDate(), 'Desc', '-5.00', 'debit', '');
    }

    public function testRejectsNonNumericAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementLine('L001', $this->makeDate(), 'Desc', 'abc', 'debit', '');
    }

    public function testFromArraySuccessfully(): void
    {
        $line = StatementLine::fromArray([
            'line_id'     => 'L003',
            'date'        => '2026-03-20',
            'description' => 'Grocery',
            'amount'      => '125.50',
            'type'        => 'debit',
            'raw_text'    => '20 MAR GROCERY 125.50',
        ]);

        $this->assertSame('L003', $line->getLineId());
        $this->assertSame('2026-03-20', $line->getDate()->format('Y-m-d'));
    }

    public function testFromArrayThrowsOnInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatementLine::fromArray([
            'line_id'     => 'L001',
            'date'        => '20-03-2026', // wrong format
            'description' => 'X',
            'amount'      => '10',
            'type'        => 'debit',
        ]);
    }

    public function testFromArrayThrowsOnMissingRequiredKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatementLine::fromArray(['line_id' => 'L001', 'date' => '2026-01-01']);
    }

    public function testToArray(): void
    {
        $line = new StatementLine('L004', $this->makeDate('2026-04-01'), 'Coffee', '4.50', 'debit', 'raw');
        $arr  = $line->toArray();

        $this->assertSame('L004', $arr['line_id']);
        $this->assertSame('2026-04-01', $arr['date']);
        $this->assertSame('4.50', $arr['amount']);
        $this->assertSame('debit', $arr['type']);
    }
}
