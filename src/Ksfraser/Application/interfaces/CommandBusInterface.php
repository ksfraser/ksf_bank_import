<?php

namespace Ksfraser\Application\Interfaces;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
interface CommandBusInterface
{
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function dispatch($command);
    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function register(string $commandClass, $handler): void;
}
