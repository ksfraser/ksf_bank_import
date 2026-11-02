<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Infrastructure\Error;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Infrastructure\Error\ErrorHandler;
use Ksfraser\FaBankImport\Infrastructure\Logger\NullLogger;
use Ksfraser\FaBankImport\Exception\PartnerException;

class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ErrorHandler(new NullLogger());
    }

    /**
     * Test handle executes callable and returns result
     */
    public function testHandleExecutesCallableAndReturnsResult(): void
    {
        $result = $this->handler->handle(
            fn() => 'success',
            'test operation'
        );

        $this->assertEquals('success', $result);
    }

    /**
     * Test handle rethrows PartnerException
     */
    public function testHandleRethrowsPartnerException(): void
    {
        $this->expectException(PartnerException::class);
        $this->expectExceptionMessage('Test error');

        $this->handler->handle(
            fn() => throw new PartnerException('Test error'),
            'failing operation'
        );
    }

    /**
     * Test handle rethrows other exceptions
     */
    public function testHandleRethrowsOtherExceptions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected error');

        $this->handler->handle(
            fn() => throw new \RuntimeException('Unexpected error'),
            'failing operation'
        );
    }

    /**
     * Test handleWithRecovery executes recovery on exception
     */
    public function testHandleWithRecoveryExecutesRecovery(): void
    {
        $result = $this->handler->handleWithRecovery(
            fn() => throw new PartnerException('Failed'),
            fn($e) => 'recovered',
            'operation with recovery'
        );

        $this->assertEquals('recovered', $result);
    }

    /**
     * Test handleWithRecovery returns callable result on success
     */
    public function testHandleWithRecoveryReturnsCallableResultOnSuccess(): void
    {
        $result = $this->handler->handleWithRecovery(
            fn() => 'success',
            fn($e) => 'recovered',
            'operation with recovery'
        );

        $this->assertEquals('success', $result);
    }

    /**
     * Test handleWithRecovery passes exception to recovery
     */
    public function testHandleWithRecoveryPassesExceptionToRecovery(): void
    {
        $caughtException = null;

        $result = $this->handler->handleWithRecovery(
            fn() => throw new PartnerException('Test error'),
            function($e) use (&$caughtException) {
                $caughtException = $e;
                return 'recovered';
            },
            'operation'
        );

        $this->assertInstanceOf(PartnerException::class, $caughtException);
        $this->assertEquals('Test error', $caughtException->getMessage());
        $this->assertEquals('recovered', $result);
    }

    /**
     * Test logException logs the exception
     */
    public function testLogExceptionLogsWithContext(): void
    {
        $exc = new PartnerException('Test error');
        
        // Should not throw
        $this->handler->logException($exc, 'in test operation');
        
        $this->assertTrue(true); // Success if no exception thrown
    }

    /**
     * Test handle with callable returning null
     */
    public function testHandleWithCallableReturningNull(): void
    {
        $result = $this->handler->handle(
            fn() => null,
            'operation returning null'
        );

        $this->assertNull($result);
    }

    /**
     * Test handle with callable returning array
     */
    public function testHandleWithCallableReturningArray(): void
    {
        $data = ['key' => 'value'];
        
        $result = $this->handler->handle(
            fn() => $data,
            'operation returning array'
        );

        $this->assertEquals($data, $result);
    }

    /**
     * Test handle with callable returning object
     */
    public function testHandleWithCallableReturningObject(): void
    {
        $obj = new \stdClass();
        $obj->prop = 'value';
        
        $result = $this->handler->handle(
            fn() => $obj,
            'operation returning object'
        );

        $this->assertSame($obj, $result);
    }

    /**
     * Test handleWithRecovery rethrows from recovery
     */
    public function testHandleWithRecoveryRethrowsFromRecovery(): void
    {
        $this->expectException(\LogicException::class);

        $this->handler->handleWithRecovery(
            fn() => throw new PartnerException('Original error'),
            fn($e) => throw new \LogicException('Recovery failed'),
            'operation'
        );
    }
}
