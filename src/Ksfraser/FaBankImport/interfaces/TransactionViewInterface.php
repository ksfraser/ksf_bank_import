<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionViewInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionViewInterface.
 */
namespace Ksfraser\FaBankImport\Interfaces;

interface TransactionViewInterface
{
    public function render(): string;
    public function renderActions(): string;
    public function addButton(string $type, array $params): void;
}
