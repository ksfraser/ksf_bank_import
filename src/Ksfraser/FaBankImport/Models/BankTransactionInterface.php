<?php

namespace Ksfraser\FaBankImport\Models;

/**
 * @BABOK CLASS ROLLUP: BankTransactionInterface — 8 methods; FR-008 (8); BR-003 (8)
 * @BABOK RTM: FR-008 / BR-003; file: Models/BankTransactionInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface BankTransactionInterface
{
    /** @BABOK FR-008 / BR-003 — method: getId */
    public function getId(): int;
    /** @BABOK FR-008 / BR-003 — method: getAmount */
    public function getAmount(): float;
    /** @BABOK FR-008 / BR-003 — method: getDate */
    public function getDate(): string;
    /** @BABOK FR-008 / BR-003 — method: getMemo */
    public function getMemo(): string;
    /** @BABOK FR-008 / BR-003 — method: getTransactionType */
    public function getTransactionType(): string;
    /** @BABOK FR-008 / BR-003 — method: getAccountDetails */
    public function getAccountDetails(): array;
    /** @BABOK FR-008 / BR-003 — method: getOtherPartyDetails */
    public function getOtherPartyDetails(): array;
    /** @BABOK FR-008 / BR-003 — method: processTransaction */
    public function processTransaction(): bool;
}