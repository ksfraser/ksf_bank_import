<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Interfaces\CommandBusInterface;

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