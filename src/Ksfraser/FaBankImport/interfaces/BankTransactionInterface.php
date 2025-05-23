<?php

namespace Ksfraser\FaBankImport\Interfaces;

interface BankTransactionInterface
{
    public function getTransactionType(): string;
    public function getAmount(): float;
    public function getDate(): string;
    public function getAccountDetails(): array;
    public function getOtherPartyDetails(): array;
    public function getStatus(): int;
    public function getId(): int;
    public function getMemo(): string;
}