<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionValidator;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;
use Ksfraser\Application\Config\Config;

/*
 * Ksfraser\Application\Config\Config lives in src/Ksfraser/Application/Config/
 * but composer's PSR-4 map points at src/Ksfraser/Application/Config/ - the
 * case mismatch makes the class unloadable through the autoloader on a
 * case-sensitive filesystem. The file is required explicitly here so the
 * validator can still be exercised.
 */
if (!class_exists(Config::class, false)) {
    require_once __DIR__ . '/../../../src/Ksfraser/Application/Config/Config.php';
}

/**
 * Behavioural tests for the transaction validator.
 */
final class TransactionValidatorTest extends TestCase
{
    /** @var TransactionValidator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $config = Config::getInstance();
        $config->set('transaction.allowed_types', ['C', 'D']);
        $config->set('transaction.max_amount', 1000.0);

        $this->validator = new TransactionValidator();
    }

    protected function tearDown(): void
    {
        Config::resetInstance();

        parent::tearDown();
    }

    public function testValidateAcceptsAValidTransaction(): void
    {
        $transaction = $this->createTransaction('C', 100.0, '2026-01-15');

        $this->assertTrue($this->validator->validate($transaction));
        $this->assertSame([], $this->validator->getErrors());
    }

    public function testValidateRejectsAnUnknownTransactionType(): void
    {
        $transaction = $this->createTransaction('X', 100.0, '2026-01-15');

        $this->assertFalse($this->validator->validate($transaction));
        $this->assertContains('Invalid transaction type: X', $this->validator->getErrors());
    }

    public function testValidateRejectsAnAmountAboveTheConfiguredMaximum(): void
    {
        $transaction = $this->createTransaction('C', 5000.0, '2026-01-15');

        $this->assertFalse($this->validator->validate($transaction));
        $this->assertContains('Transaction amount exceeds maximum allowed: 1000', $this->validator->getErrors());
    }

    public function testValidateRejectsAZeroAmount(): void
    {
        $transaction = $this->createTransaction('C', 0.0, '2026-01-15');

        $this->assertFalse($this->validator->validate($transaction));
        $this->assertContains('Transaction amount must be greater than zero', $this->validator->getErrors());
    }

    public function testValidateRejectsAnInvalidDate(): void
    {
        $transaction = $this->createTransaction('C', 100.0, '15/01/2026');

        $this->assertFalse($this->validator->validate($transaction));
        $this->assertContains('Invalid transaction date format: 15/01/2026', $this->validator->getErrors());
    }

    public function testValidateCollectsEveryError(): void
    {
        $transaction = $this->createTransaction('X', 0.0, 'not-a-date');

        $this->assertFalse($this->validator->validate($transaction));
        $this->assertCount(3, $this->validator->getErrors());
        $this->assertContains('Invalid transaction type: X', $this->validator->getErrors());
        $this->assertContains('Transaction amount must be greater than zero', $this->validator->getErrors());
        $this->assertContains('Invalid transaction date format: not-a-date', $this->validator->getErrors());
    }

    public function testErrorsAreResetBetweenValidations(): void
    {
        $this->validator->validate($this->createTransaction('X', 100.0, '2026-01-15'));
        $this->assertNotSame([], $this->validator->getErrors());

        $this->assertTrue($this->validator->validate($this->createTransaction('C', 100.0, '2026-01-15')));
        $this->assertSame([], $this->validator->getErrors());
    }

    /**
     * @return BankTransactionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createTransaction(string $type, float $amount, string $date)
    {
        $transaction = $this->createMock(BankTransactionInterface::class);
        $transaction->method('getTransactionType')->willReturn($type);
        $transaction->method('getAmount')->willReturn($amount);
        $transaction->method('getDate')->willReturn($date);

        return $transaction;
    }
}
