<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
abstract class AbstractTransaction implements BankTransactionInterface
{
    protected $data;
    protected $id;
    protected $amount;
    protected $date;
    protected $memo;
    protected $status;
    protected $accountDetails;
    protected $otherPartyDetails;

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->initializeFromData();
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    protected function initializeFromData(): void
    {
        $this->id = $this->data['id'] ?? null;
        $this->amount = $this->data['amount'] ?? 0.0;
        $this->date = $this->data['valueTimestamp'] ?? '';
        $this->memo = $this->data['memo'] ?? '';
        $this->status = $this->data['status'] ?? 0;
        $this->accountDetails = [
            'account' => $this->data['our_account'] ?? '',
            'name' => $this->data['ourBankAccountName'] ?? '',
            'code' => $this->data['ourBankAccountCode'] ?? ''
        ];
        $this->otherPartyDetails = [
            'account' => $this->data['otherBankAccount'] ?? '',
            'name' => $this->data['otherBankAccountName'] ?? ''
        ];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getMemo(): string
    {
        return $this->memo;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getAccountDetails(): array
    {
        return $this->accountDetails;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function getOtherPartyDetails(): array
    {
        return $this->otherPartyDetails;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    abstract public function getTransactionType(): string;
}
