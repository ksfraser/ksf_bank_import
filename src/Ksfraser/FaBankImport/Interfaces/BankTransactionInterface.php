<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK CLASS ROLLUP: BankTransactionInterface — 8 methods; FR-015 (8)
 * @BABOK RTM: FR-015 / ARCH-001; file: Interfaces/BankTransactionInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface BankTransactionInterface
{
    /** @BABOK FR-015 / ARCH-001 — method: getTransactionType */
    public function getTransactionType(): string;
    /** @BABOK FR-015 / ARCH-001 — method: getAmount */
    public function getAmount(): float;
    /** @BABOK FR-015 / ARCH-001 — method: getDate */
    public function getDate(): string;
    /** @BABOK FR-015 / ARCH-001 — method: getAccountDetails */
    public function getAccountDetails(): array;
    /** @BABOK FR-015 / ARCH-001 — method: getOtherPartyDetails */
    public function getOtherPartyDetails(): array;
    /** @BABOK FR-015 / ARCH-001 — method: getStatus */
    public function getStatus(): int;
    /** @BABOK FR-015 / ARCH-001 — method: getId */
    public function getId(): int;
    /** @BABOK FR-015 / ARCH-001 — method: getMemo */
    public function getMemo(): string;
}
