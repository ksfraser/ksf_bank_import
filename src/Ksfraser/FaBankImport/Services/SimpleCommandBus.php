<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\CommandBusInterface;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class SimpleCommandBus implements CommandBusInterface
{
    private $handlers = [];

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function register(string $commandClass, $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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