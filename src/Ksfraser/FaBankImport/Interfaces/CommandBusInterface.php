<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK CLASS ROLLUP: CommandBusInterface — 2 methods; FR-015 (2)
 * @BABOK RTM: FR-015 / ARCH-001; file: Interfaces/CommandBusInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface CommandBusInterface
{
    /** @BABOK FR-015 / ARCH-001 — method: dispatch */
    public function dispatch($command);
    /** @BABOK FR-015 / ARCH-001 — method: register */
    public function register(string $commandClass, $handler): void;
}