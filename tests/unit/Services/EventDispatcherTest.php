<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\EventDispatcher;

/**
 * Unit tests for the event dispatcher.
 */
final class EventDispatcherTest extends TestCase
{
    public function testDispatchInvokesTheListenerRegisteredForTheEventClass(): void
    {
        $dispatcher = new EventDispatcher();
        $received = [];

        $dispatcher->addListener(\stdClass::class, function ($event) use (&$received): void {
            $received[] = $event;
        });

        $event = new \stdClass();
        $dispatcher->dispatch($event);

        $this->assertCount(1, $received);
        $this->assertSame($event, $received[0]);
    }

    public function testDispatchInvokesEveryListenerRegisteredForTheEventClass(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = [];

        $dispatcher->addListener(\stdClass::class, function () use (&$calls): void {
            $calls[] = 'first';
        });
        $dispatcher->addListener(\stdClass::class, function () use (&$calls): void {
            $calls[] = 'second';
        });

        $dispatcher->dispatch(new \stdClass());

        $this->assertSame(['first', 'second'], $calls);
    }

    public function testDispatchDoesNothingWhenNoListenerIsRegistered(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = 0;

        $dispatcher->addListener(\stdClass::class, function () use (&$calls): void {
            $calls++;
        });

        $dispatcher->dispatch(new \ArrayObject());

        $this->assertSame(0, $calls);
    }

    public function testDispatchDoesNotCallListenersOfOtherEventClasses(): void
    {
        $dispatcher = new EventDispatcher();
        $calls = 0;

        $dispatcher->addListener(\stdClass::class, function () use (&$calls): void {
            $calls++;
        });

        $dispatcher->dispatch(new \ArrayObject());

        $this->assertSame(0, $calls);
    }
}
