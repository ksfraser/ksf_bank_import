<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Interfaces\CommandBusInterface;
use Ksfraser\Application\Services\SimpleCommandBus;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class SimpleCommandBusTest extends TestCase
{
    /**
     * @var SimpleCommandBus
     */
    private $bus;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->bus = new SimpleCommandBus();
    }

    public function testItImplementsTheCommandBusContract(): void
    {
        $this->assertInstanceOf(CommandBusInterface::class, $this->bus);
    }

    public function testTheRegisteredHandlerIsInvokedWithTheCommand(): void
    {
        $command = new \stdClass();
        $handled = [];

        $this->bus->register(\stdClass::class, new class($handled) {
            /** @var array */
            private $handled;

            /**
             * @param array $handled Collector for the handled commands.
             */
            public function __construct(array &$handled)
            {
                $this->handled = &$handled;
            }

            /**
             * @param mixed $command Command being handled.
             * @return string
             */
            public function handle($command): string
            {
                $this->handled[] = $command;

                return 'handled';
            }
        });

        $result = $this->bus->dispatch($command);

        $this->assertSame('handled', $result);
        $this->assertCount(1, $handled);
        $this->assertSame($command, $handled[0]);
    }

    public function testDispatchingAnUnregisteredCommandThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler registered for command stdClass');

        $this->bus->dispatch(new \stdClass());
    }

    public function testRegisteringTheSameCommandTwiceReplacesTheHandler(): void
    {
        $command = new \stdClass();

        $this->bus->register(\stdClass::class, new class() {
            /**
             * @param mixed $command Command being handled.
             * @return string
             */
            public function handle($command): string
            {
                return 'first';
            }
        });
        $this->bus->register(\stdClass::class, new class() {
            /**
             * @param mixed $command Command being handled.
             * @return string
             */
            public function handle($command): string
            {
                return 'second';
            }
        });

        $this->assertSame('second', $this->bus->dispatch($command));
    }
}
