<?php

namespace Tests\Unit\Import\Validators;

use Ksfraser\FaBankImport\Import\Validators\ImportStatementValidator;
use Ksfraser\FaBankImport\Import\Exceptions\StatementValidationException;
use PHPUnit\Framework\TestCase;

class ImportStatementValidatorTest extends TestCase
{
    private ImportStatementValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ImportStatementValidator();
    }

    /**
     * Test validating a complete valid statement.
     *
     * @test
     */
    public function testValidatingCompleteValidStatement(): void
    {
        $statement = [
            'id' => 100,
            'date' => '2025-01-15',
            'end_date' => '2025-01-31',
            'transactions' => [
                ['id' => 1, 'amount' => 100.00],
                ['id' => 2, 'amount' => 200.00],
            ],
            'source_file' => 'statement.csv'
        ];

        $result = $this->validator->validate($statement);

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->getCheckedRules()['required_fields']);
        $this->assertTrue($result->getCheckedRules()['valid_dates']);
        $this->assertTrue($result->getCheckedRules()['has_transactions']);
    }

    /**
     * Test statement with empty data throws exception.
     *
     * @test
     */
    public function testEmptyStatementThrowsException(): void
    {
        $this->expectException(StatementValidationException::class);

        $this->validator->validate([]);
    }

    /**
     * Test statement with missing required field.
     *
     * @test
     */
    public function testMissingRequiredFieldFails(): void
    {
        $statement = [
            'id' => 100,
            // Missing 'date'
            'transactions' => [['id' => 1]]
        ];

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->getCheckedRules()['required_fields']);
    }

    /**
     * Test statement with no transactions throws exception.
     *
     * @test
     */
    public function testStatementWithNoTransactionsThrowsException(): void
    {
        $this->expectException(StatementValidationException::class);

        $statement = [
            'id' => 100,
            'date' => '2025-01-15',
            'transactions' => []
        ];

        $this->validator->validate($statement);
    }

    /**
     * Test statement with invalid date range.
     *
     * @test
     */
    public function testInvalidDateRangeFails(): void
    {
        $statement = [
            'id' => 100,
            'date' => '2025-12-31',
            'end_date' => '2025-01-01', // End before start
            'transactions' => [['id' => 1]]
        ];

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->getCheckedRules()['valid_dates']);
    }

    /**
     * Test transaction count validation.
     *
     * @test
     */
    public function testTransactionCountValidation(): void
    {
        $statement = [
            'id' => 100,
            'transactions' => array_fill(0, 5, ['id' => 1])
        ];

        $result = $this->validator->validateTransactionCount($statement, 3, 10);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test transaction count too low fails.
     *
     * @test
     */
    public function testTransactionCountTooLowFails(): void
    {
        $statement = [
            'id' => 100,
            'transactions' => [['id' => 1]]
        ];

        $result = $this->validator->validateTransactionCount($statement, 5, 10);

        $this->assertFalse($result->isValid());
    }

    /**
     * Test amount reconciliation with matching totals.
     *
     * @test
     */
    public function testAmountReconciliationMatches(): void
    {
        $statement = [
            'transactions' => [
                ['amount' => 100.00],
                ['amount' => 200.00],
                ['amount' => 50.00],
            ]
        ];

        $result = $this->validator->validateAmountReconciliation($statement, 350.00);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test amount reconciliation with tolerance.
     *
     * @test
     */
    public function testAmountReconciliationWithTolerance(): void
    {
        $statement = [
            'transactions' => [
                ['amount' => 100.00],
                ['amount' => 200.00],
            ]
        ];

        $result = $this->validator->validateAmountReconciliation($statement, 300.02, 0.05);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test amount reconciliation mismatch fails.
     *
     * @test
     */
    public function testAmountReconciliationMismatchFails(): void
    {
        $statement = [
            'transactions' => [
                ['amount' => 100.00],
                ['amount' => 200.00],
            ]
        ];

        $result = $this->validator->validateAmountReconciliation($statement, 400.00, 0.01);

        $this->assertFalse($result->isValid());
    }
}
