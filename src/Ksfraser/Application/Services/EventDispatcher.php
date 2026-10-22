<?php

namespace Ksfraser\Application\Services;

/**
 * @BABOK FR-LOG-003 | BR-LOG-001 | UAT-LOG-001
 */
class EventDispatcher
{
    private $listeners = [];

    /**
 * @BABOK FR-LOG-003 | BR-LOG-001 | UAT-LOG-001
     */
    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    /**
 * @BABOK FR-LOG-003 | BR-LOG-001 | UAT-LOG-001
     */
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
