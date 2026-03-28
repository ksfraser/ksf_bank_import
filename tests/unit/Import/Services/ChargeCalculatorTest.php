<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\ChargeCalculator;
use Ksfraser\FaBankImport\Import\Exceptions\ChargeCalculationException;
use PHPUnit\Framework\TestCase;

class ChargeCalculatorTest extends TestCase
{
    private ChargeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChargeCalculator();
    }

    /**
     * Test empty collection IDs returns zero.
     *
     * @test
     */
    public function testEmptyCollectionIdsReturnsZero(): void
    {
        $charge = $this->calculator->calculate(1, '');

        $this->assertEquals(0.0, $charge);
    }

    /**
     * Test whitespace-only collection IDs returns zero.
     *
     * @test
     */
    public function testWhitespaceOnlyReturnsZero(): void
    {
        $charge = $this->calculator->calculate(1, '  ,  ,  ');

        $this->assertEquals(0.0, $charge);
    }

    /**
     * Test amount validation passes with acceptable tolerance.
     *
     * @test
     */
    public function testAmountValidationWithinTolerance(): void
    {
        // Should not throw
        $this->calculator->validateAmount(100.00, 100.01, 1, 0.05);
        $this->assertTrue(true);
    }

    /**
     * Test amount validation fails outside tolerance.
     *
     * @test
     */
    public function testAmountValidationOutsideTolerance(): void
    {
        $this->expectException(ChargeCalculationException::class);

        $this->calculator->validateAmount(100.00, 105.00, 1, 0.05);
    }

    /**
     * Test calculation with invalid ID format in CSV.
     *
     * @test
     */
    public function testInvalidIdFormatInCsv(): void
    {
        $charge = $this->calculator->calculate(1, '100,abc,102');

        // Should skip invalid IDs and return 0 (no valid charges found)
        $this->assertEquals(0.0, $charge);
    }

    /**
     * Test calculation with negative IDs.
     *
     * @test
     */
    public function testNegativeIdsAreSkipped(): void
    {
        $charge = $this->calculator->calculate(1, '-1,0,999');

        // Should skip negative/zero IDs
        $this->assertEquals(0.0, $charge);
    }
}
