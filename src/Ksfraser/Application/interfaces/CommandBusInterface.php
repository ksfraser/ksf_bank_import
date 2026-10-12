<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Interfaces;

interface CommandBusInterface
{
    public function dispatch($command);
    public function register(string $commandClass, $handler): void;
}
