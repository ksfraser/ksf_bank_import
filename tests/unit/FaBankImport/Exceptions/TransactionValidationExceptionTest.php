<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Exceptions;

use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;
use PHPUnit\Framework\TestCase;

final class TransactionValidationExceptionTest extends TestCase
{
    public function testItBuildsTheMessageFromTheValidationErrors(): void
    {
        $exception = new TransactionValidationException(['Invalid transaction type']);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Transaction validation failed: Invalid transaction type', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(['Invalid transaction type'], $exception->getValidationErrors());
    }

    public function testItJoinsSeveralValidationErrors(): void
    {
        $exception = new TransactionValidationException(['first problem', 'second problem']);

        $this->assertSame(
            'Transaction validation failed: first problem, second problem',
            $exception->getMessage()
        );
        $this->assertSame(['first problem', 'second problem'], $exception->getValidationErrors());
    }

    public function testAnExplicitMessageWins(): void
    {
        $exception = new TransactionValidationException(['ignored'], 'Custom message', 42);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame(['ignored'], $exception->getValidationErrors());
    }
}
