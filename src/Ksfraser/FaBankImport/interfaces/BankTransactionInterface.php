<?php

namespace Ksfraser\FaBankImport\Interfaces;

/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
interface BankTransactionInterface
{
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionType(): string;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAmount(): float;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDate(): string;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAccountDetails(): array;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOtherPartyDetails(): array;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStatus(): int;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getId(): int;
    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMemo(): string;
}
