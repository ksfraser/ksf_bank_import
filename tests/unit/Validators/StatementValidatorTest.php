<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Validators\StatementValidator;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use DateTime;

/**
 * Unit Tests for StatementValidator
 *
 * Tests all 7 business rules:
 * 1. Date range validation (start ≤ end, reasonable)
 * 2. Amount validation (presence and format)
 * 3. Merchant details (completeness)
 * 4. Transaction count (min/max)
 * 5. Account reference (presence and format)
 * 6. Currency format (ISO 4217 codes)
 * 7. Duplicate detection (heuristic-based)
 *
 * @covers \Ksfraser\FaBankImport\Import\Validators\StatementValidator
 */
class StatementValidatorTest extends TestCase
{
    private StatementValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StatementValidator();
    }

    /**
     * Test 1: Valid statement passes all rules
     */
    public function testValidStatementPassesAllRules(): void
    {
        $statement = $this->buildValidStatement();

        $result = $this->validator->validate($statement);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEquals(0, $result->getErrorCount());
    }

    /**
     * Test 2: Invalid date range - exceeds maximum
     */
    public function testInvalidDateRangeExceedsMaximum(): void
    {
        $start = new DateTime('2023-01-01');
        $end = new DateTime('2025-02-01');

        $statement = $this->buildStatementWithDates($start, $end);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('range', implode(' ', $errors));
    }

    /**
     * Test 4: Missing transaction amounts
     */
    public function testMissingTransactionAmounts(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A'],  // Missing amount
                ['date' => '2024-01-15', 'merchant' => 'Store B', 'amount' => 50],
                ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 5: Insufficient merchant details
     */
    public function testInsufficientMerchantDetails(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'amount' => 100],  // No merchant
                ['date' => '2024-01-15', 'amount' => 50],   // No merchant
                ['date' => '2024-01-20', 'amount' => 75],   // No merchant
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
    }

    /**
     * Test 6: Transaction count too low
     */
    public function testTransactionCountTooLow(): void
    {
        $validator = new StatementValidator();
        $validator->setMinTransactions(5);

        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100],
            ],
            'parserType' => 'csv'
        ]);

        $result = $validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 7: Transaction count too high
     */
    public function testTransactionCountTooHigh(): void
    {
        $validator = new StatementValidator();
        $validator->setMaxTransactions(10);

        // Build statement with many transactions
        $transactions = [];
        for ($i = 0; $i < 15; $i++) {
            $transactions[] = [
                'date' => '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT),
                'merchant' => 'Store ' . $i,
                'amount' => 100.00 + $i
            ];
        }

        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 2500.00,
            'transactions' => $transactions,
            'parserType' => 'csv'
        ]);

        $result = $validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 8: Missing account reference
     */
    public function testMissingAccountReference(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => '',  // Empty
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100],
                ['date' => '2024-01-15', 'merchant' => 'Store B', 'amount' => 50],
                ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 9: Invalid account reference format
     */
    public function testInvalidAccountReferenceFormat(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'SHORT',  // Too short (5 chars, need 8-20)
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100],
                ['date' => '2024-01-15', 'merchant' => 'Store B', 'amount' => 50],
                ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 10: Invalid currency code
     */
    public function testInvalidCurrencyCode(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'INVALID',  // Not 3 letters
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100],
                ['date' => '2024-01-15', 'merchant' => 'Store B', 'amount' => 50],
                ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
    }

    /**
     * Test 11: Duplicate transactions detected (warning, not error)
     */
    public function testDuplicateTransactionsDetected(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100.00],
                ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100.00],  // Duplicate
                ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        // Valid (duplicates generate warnings, not errors)
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
    }

    /**
     * Test 12: Multiple violations collected together
     */
    public function testMultipleViolationsCollected(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'SHORT',  // Invalid: too short
            'currency' => 'INVALID',  // Invalid: not 3 letters
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10'],  // Missing merchant and amount
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertGreaterThan(1, $result->getErrorCount());
    }

    /**
     * Test 13: Valid statement with various currencies
     */
    public function testValidStatementWithVariousCurrencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF'];

        foreach ($currencies as $currency) {
            $statement = ParsedStatementDTO::create([
                'statementDate' => '2024-01-15',
                'accountReference' => 'ACC123456',
                'currency' => $currency,
                'openingBalance' => 1000.00,
                'closingBalance' => 1500.00,
                'transactions' => [
                    ['date' => '2024-01-10', 'merchant' => 'Store A', 'amount' => 100],
                    ['date' => '2024-01-15', 'merchant' => 'Store B', 'amount' => 50],
                    ['date' => '2024-01-20', 'merchant' => 'Store C', 'amount' => 75],
                ],
                'parserType' => 'csv'
            ]);

            $result = $this->validator->validate($statement);
            $this->assertTrue($result->isValid(), "Currency $currency failed validation");
        }
    }

    /**
     * Test 14: Fluent interface configuration
     */
    public function testFluentInterfaceConfiguration(): void
    {
        $validator = new StatementValidator();
        $result = $validator
            ->setMinTransactions(1)
            ->setMaxTransactions(5000)
            ->setMaxDateRangeDays(365);

        $this->assertInstanceOf(StatementValidator::class, $result);
    }

    /**
     * Test 15: Validation result summary
     */
    public function testValidationResultSummary(): void
    {
        $statement = ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'SHORT',
            'currency' => 'INVALID',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                ['date' => '2024-01-10', 'merchant' => 'Store', 'amount' => 100]
            ],
            'parserType' => 'csv'
        ]);

        $result = $this->validator->validate($statement);

        $summary = $result->getSummary();
        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);
        $this->assertStringContainsString('FAILED', $summary);
    }

    // ========== Helper Methods ==========

    /**
     * Build a valid parsed statement for testing
     */
    private function buildValidStatement(): ParsedStatementDTO
    {
        return ParsedStatementDTO::create([
            'statementDate' => '2024-01-15',
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                [
                    'date' => '2024-01-10',
                    'amount' => 100.00,
                    'merchant' => 'Walmart',
                    'reference' => 'TXN001'
                ],
                [
                    'date' => '2024-01-15',
                    'amount' => 200.00,
                    'merchant' => 'Target',
                    'reference' => 'TXN002'
                ],
                [
                    'date' => '2024-01-20',
                    'amount' => 50.00,
                    'merchant' => 'Gas Station',
                    'reference' => 'TXN003'
                ]
            ],
            'parserType' => 'csv',
            'metadata' => []
        ]);
    }

    /**
     * Build statement with specific dates
     */
    private function buildStatementWithDates(DateTime $start, DateTime $end): ParsedStatementDTO
    {
        return ParsedStatementDTO::create([
            'statementDate' => $start->format('Y-m-d'),
            'accountReference' => 'ACC123456',
            'currency' => 'USD',
            'openingBalance' => 1000.00,
            'closingBalance' => 1500.00,
            'transactions' => [
                [
                    'date' => $start->format('Y-m-d'),
                    'amount' => 100.00,
                    'merchant' => 'Store A',
                    'reference' => 'TXN001'
                ],
                [
                    'date' => $end->format('Y-m-d'),
                    'amount' => 200.00,
                    'merchant' => 'Store B',
                    'reference' => 'TXN002'
                ]
            ],
            'parserType' => 'csv',
            'metadata' => []
        ]);
    }
}
