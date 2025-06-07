<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

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

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->initializeFromData();
    }

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getMemo(): string
    {
        return $this->memo;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getAccountDetails(): array
    {
        return $this->accountDetails;
    }

    public function getOtherPartyDetails(): array
    {
        return $this->otherPartyDetails;
    }

    abstract public function getTransactionType(): string;
}
