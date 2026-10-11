<?php

namespace Ksfraser\FaBankImport\Interfaces;

interface CommandBusInterface
{
    public function dispatch($command);
    public function register(string $commandClass, $handler): void;
}