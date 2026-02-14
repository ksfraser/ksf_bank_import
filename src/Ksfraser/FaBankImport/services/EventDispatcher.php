<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :EventDispatcher [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for EventDispatcher.
 */
namespace Ksfraser\FaBankImport\Services;

class EventDispatcher
{
    private $listeners = [];

    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventName = get_class($event);

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }
}