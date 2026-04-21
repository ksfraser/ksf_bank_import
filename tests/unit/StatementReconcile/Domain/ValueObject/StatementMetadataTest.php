<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata
 */
class StatementMetadataTest extends TestCase
{
    private function makeDate(string $ymd): \DateTimeImmutable
    {
        return new \DateTimeImmutable($ymd);
    }

    public function testConstructsWithAllFields(): void
    {
        $meta = new StatementMetadata(
            '1234',
            $this->makeDate('2026-03-01'),
            $this->makeDate('2026-03-31'),
            '500.00',
            '1200.50',
            $this->makeDate('2026-04-20')
        );

        $this->assertSame('1234', $meta->getAccountIdentifier());
        $this->assertSame('2026-03-01', $meta->getStatementStartDate()->format('Y-m-d'));
        $this->assertSame('2026-03-31', $meta->getStatementEndDate()->format('Y-m-d'));
        $this->assertSame('500.00', $meta->getOpeningBalance());
        $this->assertEqualsWithDelta(500.0, $meta->getOpeningBalanceFloat(), 0.001);
        $this->assertSame('1200.50', $meta->getClosingBalance());
        $this->assertSame('2026-04-20', $meta->getDueDate()->format('Y-m-d'));
    }

    public function testNullableAccountIdentifierAndDueDate(): void
    {
        $meta = new StatementMetadata(
            null,
            $this->makeDate('2026-01-01'),
            $this->makeDate('2026-01-31'),
            '0.00',
            '100.00'
        );

        $this->assertNull($meta->getAccountIdentifier());
        $this->assertNull($meta->getDueDate());
    }

    public function testRejectsEndDateBeforeStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementMetadata(
            null,
            $this->makeDate('2026-03-31'),
            $this->makeDate('2026-03-01'), // end before start
            '0.00',
            '0.00'
        );
    }

    public function testRejectsNonNumericOpeningBalance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementMetadata(
            null,
            $this->makeDate('2026-01-01'),
            $this->makeDate('2026-01-31'),
            'abc',
            '0.00'
        );
    }

    public function testRejectsNonNumericClosingBalance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StatementMetadata(
            null,
            $this->makeDate('2026-01-01'),
            $this->makeDate('2026-01-31'),
            '0.00',
            'notANumber'
        );
    }

    public function testNegativeBalancesAccepted(): void
    {
        $meta = new StatementMetadata(
            null,
            $this->makeDate('2026-01-01'),
            $this->makeDate('2026-01-31'),
            '-200.00',
            '-50.00'
        );
        $this->assertEqualsWithDelta(-200.0, $meta->getOpeningBalanceFloat(), 0.001);
    }

    public function testFromArraySuccessfully(): void
    {
        $meta = StatementMetadata::fromArray([
            'account_identifier'   => '9876',
            'statement_start_date' => '2026-02-01',
            'statement_end_date'   => '2026-02-28',
            'opening_balance'      => '300.00',
            'closing_balance'      => '150.75',
            'due_date'             => '2026-03-15',
        ]);

        $this->assertSame('9876', $meta->getAccountIdentifier());
        $this->assertSame('2026-03-15', $meta->getDueDate()->format('Y-m-d'));
    }

    public function testFromArrayWithoutOptionalFields(): void
    {
        $meta = StatementMetadata::fromArray([
            'statement_start_date' => '2026-01-01',
            'statement_end_date'   => '2026-01-31',
            'opening_balance'      => '0',
            'closing_balance'      => '500',
        ]);

        $this->assertNull($meta->getAccountIdentifier());
        $this->assertNull($meta->getDueDate());
    }

    public function testFromArrayThrowsOnMissingKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatementMetadata::fromArray([
            'statement_start_date' => '2026-01-01',
            // missing statement_end_date, opening/closing balance
        ]);
    }

    public function testToArray(): void
    {
        $meta = StatementMetadata::fromArray([
            'account_identifier'   => '0001',
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '100.00',
            'closing_balance'      => '200.00',
        ]);

        $arr = $meta->toArray();
        $this->assertSame('0001', $arr['account_identifier']);
        $this->assertSame('2026-03-01', $arr['statement_start_date']);
        $this->assertNull($arr['due_date']);
    }
}
