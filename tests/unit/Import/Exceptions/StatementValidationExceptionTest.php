<?php

namespace Tests\Unit\Import\Exceptions;

use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use PHPUnit\Framework\TestCase;

class StatementValidationExceptionTest extends TestCase
{
    /**
     * Test no transactions exception.
     *
     * @test
     */
    public function testNoTransactionsException(): void
    {
        $exception = StatementValidationException::noTransactions();

        $this->assertFalse(empty($exception->getMessage()));
        $this->assertEquals(1001, $exception->getCode());
        $this->assertTrue($exception->isRecoverable());
    }

    /**
     * Test invalid date range exception.
     *
     * @test
     */
    public function testInvalidDateRangeException(): void
    {
        $exception = StatementValidationException::invalidDateRange('2025-12-31', '2025-01-01');

        $this->assertStringContainsString('2025-12-31', $exception->getMessage());
        $this->assertEquals(1002, $exception->getCode());
        $this->assertEquals('2025-12-31', $exception->getContext()['start_date']);
    }

    /**
     * Test duplicate statement exception.
     *
     * @test
     */
    public function testDuplicateStatementException(): void
    {
        $exception = StatementValidationException::duplicateStatement(42, 'statement.csv');

        $this->assertStringContainsString('42', $exception->getMessage());
        $this->assertEquals(1003, $exception->getCode());
        $this->assertEquals(42, $exception->getContext()['statement_id']);
    }

    /**
     * Test missing fields exception.
     *
     * @test
     */
    public function testMissingFieldsException(): void
    {
        $fields = ['date', 'amount', 'reference'];
        $exception = StatementValidationException::missingFields($fields);

        $this->assertStringContainsString('date', $exception->getMessage());
        $this->assertEquals(1004, $exception->getCode());
        $this->assertEquals($fields, $exception->getContext()['missing_fields']);
    }
}
