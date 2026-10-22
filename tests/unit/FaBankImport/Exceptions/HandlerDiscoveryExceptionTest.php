<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Exceptions;

use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;
use PHPUnit\Framework\TestCase;

final class HandlerDiscoveryExceptionTest extends TestCase
{
    public function testCannotInstantiateNamesTheHandler(): void
    {
        $exception = HandlerDiscoveryException::cannotInstantiate('App\\Handler');

        $this->assertInstanceOf(HandlerDiscoveryException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Cannot instantiate handler: App\\Handler', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCannotInstantiateKeepsThePreviousException(): void
    {
        $previous = new \RuntimeException('inner failure');

        $exception = HandlerDiscoveryException::cannotInstantiate('App\\Handler', $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame('Cannot instantiate handler: App\\Handler', $exception->getMessage());
    }

    public function testInvalidConstructorUsesTheDefaultReason(): void
    {
        $exception = HandlerDiscoveryException::invalidConstructor('App\\Handler');

        $this->assertSame(
            'Handler has invalid constructor (incompatible signature): App\\Handler',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
    }

    public function testInvalidConstructorUsesTheGivenReason(): void
    {
        $previous = new \LogicException('inner failure');

        $exception = HandlerDiscoveryException::invalidConstructor('App\\Handler', 'too many arguments', $previous);

        $this->assertSame(
            'Handler has invalid constructor (too many arguments): App\\Handler',
            $exception->getMessage()
        );
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testMissingDependencyNamesTheMissingClass(): void
    {
        $exception = HandlerDiscoveryException::missingDependency('App\\Handler', 'App\\Service');

        $this->assertSame(
            "Handler missing dependency 'App\\Service': App\\Handler",
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testMissingDependencyKeepsThePreviousException(): void
    {
        $previous = new \DomainException('inner failure');

        $exception = HandlerDiscoveryException::missingDependency('App\\Handler', 'App\\Service', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
