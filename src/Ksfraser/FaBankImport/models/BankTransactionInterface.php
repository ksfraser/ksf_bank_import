<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BankTransactionInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BankTransactionInterface.
 */
namespace Ksfraser\FaBankImport\Models;

interface BankTransactionInterface
{
    public function getId(): int;
    public function getAmount(): float;
    public function getDate(): string;
    public function getMemo(): string;
    public function getTransactionType(): string;
    public function getAccountDetails(): array;
    public function getOtherPartyDetails(): array;
    public function processTransaction(): bool;
}