<?php

namespace Ksfraser\Application\Interfaces;

interface CommandBusInterface
{
    public function dispatch($command);
    public function register(string $commandClass, $handler): void;
}
