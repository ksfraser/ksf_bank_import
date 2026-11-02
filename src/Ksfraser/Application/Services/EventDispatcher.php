<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
