<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto
 */
class BankTransactionDtoTest extends TestCase
{
    private function makeDate(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }

    // ------------------------------------------------------------------
    // Constructor: happy path
    // ------------------------------------------------------------------

    public function testValidConstructionReturnsCorrectGetters(): void
    {
        $dto = new BankTransactionDto(
            1,
            $this->makeDate('2026-03-15'),
            '99.99',
            'Amazon Prime',
            'debit'
        );

        $this->assertSame(1, $dto->getId());
        $this->assertSame('99.99', $dto->getAmount());
        $this->assertSame('Amazon Prime', $dto->getDescription());
        $this->assertSame('debit', $dto->getType());
        $this->assertSame('2026-03-15', $dto->getDate()->format('Y-m-d'));
        $this->assertNull($dto->getFaTransType());
        $this->assertNull($dto->getFaTransNo());
    }

    public function testCreditTypeAllowed(): void
    {
        $dto = new BankTransactionDto(2, $this->makeDate('2026-03-15'), '50.00', 'Deposit', 'credit');
        $this->assertSame('credit', $dto->getType());
    }

    public function testZeroAmountAllowed(): void
    {
        $dto = new BankTransactionDto(1, $this->makeDate('2026-03-15'), '0', 'Zero', 'debit');
        $this->assertSame('0', $dto->getAmount());
    }

    public function testFaTransTypeAndNoPopulated(): void
    {
        $dto = new BankTransactionDto(1, $this->makeDate('2026-03-15'), '10.00', 'Test', 'debit', 41, 100);
        $this->assertSame(41, $dto->getFaTransType());
        $this->assertSame(100, $dto->getFaTransNo());
    }

    public function testGetAmountFloat(): void
    {
        $dto = new BankTransactionDto(1, $this->makeDate('2026-03-15'), '99.99', 'Test', 'debit');
        $this->assertEqualsWithDelta(99.99, $dto->getAmountFloat(), 0.001);
    }

    // ------------------------------------------------------------------
    // Constructor: validation failures
    // ------------------------------------------------------------------

    public function testIdZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/positive integer/');
        new BankTransactionDto(0, $this->makeDate('2026-03-15'), '10.00', 'Test', 'debit');
    }

    public function testIdNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BankTransactionDto(-1, $this->makeDate('2026-03-15'), '10.00', 'Test', 'debit');
    }

    public function testNegativeAmountThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/non-negative/');
        new BankTransactionDto(1, $this->makeDate('2026-03-15'), '-5.00', 'Test', 'debit');
    }

    public function testNonNumericAmountThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BankTransactionDto(1, $this->makeDate('2026-03-15'), 'abc', 'Test', 'debit');
    }

    public function testInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"credit" or "debit"/');
        new BankTransactionDto(1, $this->makeDate('2026-03-15'), '10.00', 'Test', 'payment');
    }

    // ------------------------------------------------------------------
    // fromArray
    // ------------------------------------------------------------------

    public function testFromArrayBuildsCorrectly(): void
    {
        $dto = BankTransactionDto::fromArray([
            'id'            => 5,
            'date'          => '2026-03-15',
            'amount'        => '100.00',
            'description'   => 'Payment',
            'type'          => 'credit',
            'fa_trans_type' => 42,
            'fa_trans_no'   => 99,
        ]);

        $this->assertSame(5, $dto->getId());
        $this->assertSame('100.00', $dto->getAmount());
        $this->assertSame('credit', $dto->getType());
        $this->assertSame(42, $dto->getFaTransType());
        $this->assertSame(99, $dto->getFaTransNo());
        $this->assertSame('2026-03-15', $dto->getDate()->format('Y-m-d'));
    }

    public function testFromArrayBadDateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/YYYY-MM-DD/');
        BankTransactionDto::fromArray([
            'id'    => 1,
            'date'  => 'not-a-date',
            'amount'=> '10.00',
            'type'  => 'debit',
        ]);
    }

    public function testFromArrayOptionalFaFieldsNullable(): void
    {
        $dto = BankTransactionDto::fromArray([
            'id'    => 1,
            'date'  => '2026-03-15',
            'amount'=> '10.00',
            'type'  => 'debit',
        ]);

        $this->assertNull($dto->getFaTransType());
        $this->assertNull($dto->getFaTransNo());
    }
}
