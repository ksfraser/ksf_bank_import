<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Services\EventDispatcher;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class EventDispatcherTest extends TestCase
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->dispatcher = new EventDispatcher();
    }

    public function testDispatchingAnEventWithoutListenersDoesNothing(): void
    {
        $event = new \stdClass();

        $this->dispatcher->dispatch($event);

        $this->assertTrue(true);
    }

    public function testTheRegisteredListenerReceivesTheEvent(): void
    {
        $event = new \stdClass();
        $received = [];

        $this->dispatcher->addListener(\stdClass::class, function ($dispatched) use (&$received) {
            $received[] = $dispatched;
        });

        $this->dispatcher->dispatch($event);

        $this->assertCount(1, $received);
        $this->assertSame($event, $received[0]);
    }

    public function testEveryListenerOfAnEventIsCalled(): void
    {
        $calls = [];

        $this->dispatcher->addListener(\stdClass::class, function () use (&$calls) {
            $calls[] = 'first';
        });
        $this->dispatcher->addListener(\stdClass::class, function () use (&$calls) {
            $calls[] = 'second';
        });

        $this->dispatcher->dispatch(new \stdClass());

        $this->assertSame(['first', 'second'], $calls);
    }

    public function testListenersAreOnlyCalledForTheirOwnEventClass(): void
    {
        $called = 0;

        $this->dispatcher->addListener(\stdClass::class, function () use (&$called) {
            $called++;
        });

        $this->dispatcher->dispatch(new \ArrayObject());

        $this->assertSame(0, $called);
    }

    public function testTheEventClassNameIsUsedAsTheEventKey(): void
    {
        $dispatcher = new EventDispatcher();
        $received = null;

        $dispatcher->addListener(\ArrayObject::class, function ($event) use (&$received) {
            $received = $event;
        });

        $event = new \ArrayObject(['a']);
        $dispatcher->dispatch($event);

        $this->assertSame($event, $received);
    }
}
