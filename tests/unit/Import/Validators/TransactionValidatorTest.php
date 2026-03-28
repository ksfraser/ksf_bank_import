<?php

namespace Tests\Unit\Import\Validators;

use Ksfraser\FaBankImport\Import\Validators\TransactionValidator;
use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use Ksfraser\FaBankImport\Import\Exceptions\BankTransferException;
use PHPUnit\Framework\TestCase;

class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TransactionValidator();
    }

    /**
     * Test validating a complete valid transaction.
     *
     * @test
     */
    public function testValidatingCompleteValidTransaction(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 100.50,
            'reference' => 'REF-001',
            'counterparty_name' => 'Vendor Inc'
        ];

        $result = $this->validator->validate($transaction, 100);

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->getCheckedRules()['required_fields']);
        $this->assertTrue($result->getCheckedRules()['valid_amount']);
    }

    /**
     * Test transaction with missing required field throws exception.
     *
     * @test
     */
    public function testMissingRequiredFieldThrowsException(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            // Missing 'amount'
            'reference' => 'REF-001'
        ];

        $result = $this->validator->validate($transaction, 100);

        $this->assertFalse($result->isValid());
    }

    /**
     * Test transaction with zero amount throws exception.
     *
     * @test
     */
    public function testZeroAmountThrowsException(): void
    {
        $this->expectException(TransactionValidationException::class);

        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 0.00,
            'reference' => 'REF-001'
        ];

        $this->validator->validate($transaction, 100);
    }

    /**
     * Test transaction with non-numeric amount throws exception.
     *
     * @test
     */
    public function testNonNumericAmountThrowsException(): void
    {
        $this->expectException(TransactionValidationException::class);

        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 'ABC',
            'reference' => 'REF-001'
        ];

        $this->validator->validate($transaction, 100);
    }

    /**
     * Test transaction with invalid date throws exception.
     *
     * @test
     */
    public function testInvalidDateThrowsException(): void
    {
        $this->expectException(TransactionValidationException::class);

        $transaction = [
            'id' => 1,
            'date' => 'invalid-date',
            'amount' => 100.00,
            'reference' => 'REF-001'
        ];

        $this->validator->validate($transaction, 100);
    }

    /**
     * Test transaction with future date throws exception.
     *
     * @test
     */
    public function testFutureDateThrowsException(): void
    {
        $this->expectException(TransactionValidationException::class);

        $transaction = [
            'id' => 1,
            'date' => date('Y-m-d', strtotime('+1 year')),
            'amount' => 100.00,
            'reference' => 'REF-001'
        ];

        $this->validator->validate($transaction, 100);
    }

    /**
     * Test **CRITICAL**: Same-account transfer throws non-recoverable exception.
     *
     * @test
     */
    public function testSameAccountTransferThrowsException(): void
    {
        $this->expectException(BankTransferException::class);

        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'REF-001',
            'type' => 'TRANSFER',
            'from_account_id' => 100, // Same as bank account
            'to_account_id' => 100    // Same account = ERROR
        ];

        $this->validator->validate($transaction, 100);
    }

    /**
     * Test transfer between different accounts is valid.
     *
     * @test
     */
    public function testDifferentAccountTransferIsValid(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'REF-001',
            'type' => 'TRANSFER',
            'from_account_id' => 100,
            'to_account_id' => 200,
            'counterparty_name' => 'Other Bank'
        ];

        $result = $this->validator->validate($transaction, 100);

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->getCheckedRules()['not_same_account_transfer']);
    }

    /**
     * Test non-transfer transaction ignores account check.
     *
     * @test
     */
    public function testNonTransferIgnoresAccountCheck(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'REF-001',
            'type' => 'DEPOSIT',
            'from_account_id' => 100,
            'to_account_id' => 100,
            'counterparty_name' => 'Vendor'
        ];

        $result = $this->validator->validate($transaction, 100);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test transaction with no counterparty issues warning.
     *
     * @test
     */
    public function testNoCounterpartyIssuesWarning(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15',
            'amount' => 100.00,
            'reference' => 'REF-001'
            // No counterparty_name, counterparty_account, or contact_id
        ];

        $result = $this->validator->validate($transaction, 100);

        $this->assertTrue($result->isValid()); // Still valid, just warning
        $this->assertCount(1, $result->getWarnings());
        $this->assertFalse($result->getCheckedRules()['has_counterparty']);
    }

    /**
     * Test date range validation within bounds.
     *
     * @test
     */
    public function testDateRangeValidationWithinBounds(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-01-15'
        ];

        $result = $this->validator->validateDateRange($transaction, '2025-01-01', '2025-01-31');

        $this->assertTrue($result->isValid());
    }

    /**
     * Test date range validation outside bounds fails.
     *
     * @test
     */
    public function testDateRangeValidationOutsideBoundsFails(): void
    {
        $transaction = [
            'id' => 1,
            'date' => '2025-02-15'
        ];

        $result = $this->validator->validateDateRange($transaction, '2025-01-01', '2025-01-31');

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->getCheckedRules()['date_in_range']);
    }

    /**
     * Test bank transfer validation with different accounts.
     *
     * @test
     */
    public function testBankTransferValidationDifferentAccounts(): void
    {
        $transaction = [
            'type' => 'TRANSFER',
            'from_account_id' => 100,
            'to_account_id' => 200
        ];

        $result = $this->validator->validateForBankTransfer($transaction, ['id' => 100]);

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->getCheckedRules()['different_accounts']);
    }

    /**
     * Test bank transfer validation with same accounts fails.
     *
     * @test
     */
    public function testBankTransferValidationSameAccountsFails(): void
    {
        $transaction = [
            'type' => 'TRANSFER',
            'from_account_id' => 100,
            'to_account_id' => 100
        ];

        $result = $this->validator->validateForBankTransfer($transaction, ['id' => 100]);

        $this->assertFalse($result->isValid());
    }

    /**
     * Test collection validation with valid IDs.
     *
     * @test
     */
    public function testCollectionValidationWithValidIds(): void
    {
        $result = $this->validator->validateCollections([], '100,101,102');

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->getCheckedRules()['has_collections']);
    }

    /**
     * Test collection validation with invalid ID format fails.
     *
     * @test
     */
    public function testCollectionValidationWithInvalidIdFails(): void
    {
        $result = $this->validator->validateCollections([], '100,abc,102');

        $this->assertFalse($result->isValid());
    }

    /**
     * Test collection validation with empty IDs issues warning.
     *
     * @test
     */
    public function testCollectionValidationWithEmptyIds(): void
    {
        $result = $this->validator->validateCollections([], '');

        $this->assertFalse($result->getCheckedRules()['has_collections']);
    }
}
