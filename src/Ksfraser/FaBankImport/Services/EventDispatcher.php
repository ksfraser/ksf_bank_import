<?php

/** @BABOK See docs/Requirements/ for module coverage tracking 
// @BABOK Referenced in RTM for service layer (FR-001 through FR-018) 
 */

namespace Ksfraser\FaBankImport\Services;

class EventDispatcher
{
    private $listeners = [];

    public function addListener(string $eventName, callable $listener): void
        /** @BABOK @BABOK See docs/Requirements/ for module coverage — method: addListener */
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(object $event): void
        /** @BABOK @BABOK ARCH-001 / FR-014 (Event bus dispatch) — method: dispatch */
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