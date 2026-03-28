<?php

namespace Tests\Unit\Import\Exceptions;

use Ksfraser\FaBankImport\Import\Exceptions\TransactionValidationException;
use PHPUnit\Framework\TestCase;

class TransactionValidationExceptionTest extends TestCase
{
    /**
     * Test invalid amount exception.
     *
     * @test
     */
    public function testInvalidAmountException(): void
    {
        $exception = TransactionValidationException::invalidAmount('abc123', 999);

        $this->assertStringContainsString('999', $exception->getMessage());
        $this->assertEquals(2001, $exception->getCode());
        $this->assertEquals(999, $exception->getContext()['transaction_id']);
        $this->assertTrue($exception->isRecoverable());
    }

    /**
     * Test missing counterparty exception.
     *
     * @test
     */
    public function testMissingCounterpartyException(): void
    {
        $exception = TransactionValidationException::missingCounterparty(777);

        $this->assertEquals(2002, $exception->getCode());
        $this->assertTrue($exception->isRecoverable());
        $this->assertEquals(777, $exception->getContext()['transaction_id']);
    }

    /**
     * Test same-account transfer exception (should NOT be recoverable).
     *
     * @test
     */
    public function testSameAccountTransferException(): void
    {
        $exception = TransactionValidationException::sameAccountTransfer(555, 123);

        $this->assertStringContainsString('same account', strtolower($exception->getMessage()));
        $this->assertEquals(2003, $exception->getCode());
        $this->assertFalse($exception->isRecoverable()); // Non-recoverable!
        $this->assertEquals(555, $exception->getContext()['transaction_id']);
        $this->assertEquals(123, $exception->getContext()['bank_account_id']);
    }

    /**
     * Test invalid date exception.
     *
     * @test
     */
    public function testInvalidDateException(): void
    {
        $exception = TransactionValidationException::invalidDate(888, '2025-13-45', 'Invalid month');

        $this->assertStringContainsString('2025-13-45', $exception->getMessage());
        $this->assertEquals(2004, $exception->getCode());
        $this->assertTrue($exception->isRecoverable());
    }

    /**
     * Test duplicate transaction exception.
     *
     * @test
     */
    public function testDuplicateTransactionException(): void
    {
        $exception = TransactionValidationException::duplicateTransaction(666, 'REF-001', 'Already imported');

        $this->assertStringContainsString('REF-001', $exception->getMessage());
        $this->assertEquals(2005, $exception->getCode());
        $this->assertTrue($exception->isRecoverable());
        $this->assertEquals('Already imported', $exception->getContext()['reason']);
    }
}
