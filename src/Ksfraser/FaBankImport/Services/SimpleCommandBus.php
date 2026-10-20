<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\CommandBusInterface;

/**
 * @BABOK CLASS ROLLUP: SimpleCommandBus — 2 methods; BR-001 (2); BR-002 (2); FR-008 (2)
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/SimpleCommandBus.php; coverage: docs/TestCases/ (file rollup)
 */
class SimpleCommandBus implements CommandBusInterface
{
    private $handlers = [];

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: register */
    public function register(string $commandClass, $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    /** @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: dispatch */
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