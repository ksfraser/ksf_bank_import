<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :CommandBusInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for CommandBusInterface.
 */
namespace Ksfraser\FaBankImport\Interfaces;

interface CommandBusInterface
{
    public function dispatch($command);
    public function register(string $commandClass, $handler): void;
}