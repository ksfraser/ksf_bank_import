<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
 */
interface CommandBusInterface
{
    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function dispatch($command);
    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function register(string $commandClass, $handler): void;
}