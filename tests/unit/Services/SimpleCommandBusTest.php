<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\SimpleCommandBus;

/**
 * Unit tests for the simple command bus.
 */
final class SimpleCommandBusTest extends TestCase
{
    public function testDispatchDelegatesToTheRegisteredHandler(): void
    {
        $bus = new SimpleCommandBus();
        $command = new \stdClass();

        $handler = new class {
            /** @var \stdClass|null */
            public $handled;

            public function handle($command)
            {
                $this->handled = $command;

                return 'handled';
            }
        };

        $bus->register(\stdClass::class, $handler);

        $this->assertSame('handled', $bus->dispatch($command));
        $this->assertSame($command, $handler->handled);
    }

    public function testDispatchThrowsWhenNoHandlerIsRegisteredForTheCommand(): void
    {
        $bus = new SimpleCommandBus();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler registered for command stdClass');

        $bus->dispatch(new \stdClass());
    }

    public function testDispatchUsesTheHandlerRegisteredForTheExactCommandClass(): void
    {
        $bus = new SimpleCommandBus();

        $bus->register(\stdClass::class, new class {
            public function handle($command)
            {
                return 'std';
            }
        });

        $bus->register(\ArrayObject::class, new class {
            public function handle($command)
            {
                return 'array';
            }
        });

        $this->assertSame('array', $bus->dispatch(new \ArrayObject()));
    }

    public function testRegisteringTheSameCommandTwiceReplacesTheHandler(): void
    {
        $bus = new SimpleCommandBus();

        $bus->register(\stdClass::class, new class {
            public function handle($command)
            {
                return 'first';
            }
        });
        $bus->register(\stdClass::class, new class {
            public function handle($command)
            {
                return 'second';
            }
        });

        $this->assertSame('second', $bus->dispatch(new \stdClass()));
    }
}
