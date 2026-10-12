<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Services;

use Ksfraser\Application\Interfaces\CommandBusInterface;

class SimpleCommandBus implements CommandBusInterface
{
    private $handlers = [];

    public function register(string $commandClass, $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch($command)
    {
        $commandClass = get_class($command);
        
        if (!isset($this->handlers[$commandClass])) {
            throw new \RuntimeException("No handler registered for command {$commandClass}");
        }

        $handler = $this->handlers[$commandClass];
        return $handler->handle($command);
    }
}
